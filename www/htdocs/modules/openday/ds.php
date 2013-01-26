<?
include_once "inc/sparqllib.php";

class OpendayDataSource extends DataSource
{
	static $endpoint = "http://sparql.data.southampton.ac.uk";
	static $dates = array('2012/07/06', '2012/07/07');

	static function getAll()
	{
		$points = array();
		foreach(self::getAllPointsOfService()	as $point) $points[] = $point;
		foreach(self::getAllBusStops()	 	as $point) $points[] = $point;
		return $points;
	}

	static function getEntries($q, $cats)
	{
		$q = str_replace("\\", "\\\\\\\\\\\\\\", trim($q));
		
		$pos = array();
		$label = array();
		$type = array();
		$url = array();
		$icon = array();
		self::createPointOfServiceEntries($pos, $label, $type, $url, $icon, $q, $cats);
		self::createBusEntries($pos, $label, $type, $url, $icon, $q, $cats);
		self::createBuildingEntries($pos, $label, $type, $url, $icon, $q, $cats);
		self::createSiteEntries($pos, $label, $type, $url, $icon, $q, $cats);
		return array($pos, $label, $type, $url, $icon);
	}

	static function getDataSets()
	{
		$uri = "http://opendatamap.ecs.soton.ac.uk";
		$ds = sparql_get(self::$endpoint, "
		SELECT DISTINCT ?name ?uri ?l {
		  ?app <http://xmlns.com/foaf/0.1/homepage> <$uri> .
		  ?app <http://purl.org/dc/terms/requires> ?uri .
		  ?uri <http://purl.org/dc/terms/title> ?name .
		  OPTIONAL { ?uri <http://purl.org/dc/terms/license> ?l . }
		} ORDER BY ?name
		");
		$ds[] = array('name' => 'Ordnance Survey Linked Data', 'uri' => 'http://data.ordnancesurvey.co.uk', 'l' => 'http://reference.data.gov.uk/id/open-government-licence');
		return $ds;
	}

	static function getBookmarks()
	{
		$bookmarks[] = array('img' => 'http://users.ecs.soton.ac.uk/crw104/img/logo/uos.png');
		$bookmarks[] = array('area' => 'http://id.southampton.ac.uk/site/1', 'label' => 'Highfield Campus');
		$bookmarks[] = array('area' => 'http://id.southampton.ac.uk/site/3', 'label' => 'Avenue Campus');
		$bookmarks[] = array('area' => 'http://id.southampton.ac.uk/site/6', 'label' => 'Oceanography Campus');
		$bookmarks[] = array('area' => 'southampton-centre', 'label' => 'City Centre');
		$bookmarks[] = array('area' => 'southampton-overview', 'label' => 'Southampton');
		return $bookmarks;
	}

	static function getSubjects()
	{
		return sparql_get(self::$endpoint, "
PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>

SELECT DISTINCT ?uri ?label WHERE {
  GRAPH <http://id.southampton.ac.uk/dataset/opendays-".strtolower(date('F-Y', strtotime(self::$dates[0])))."/latest> {
    ?uri a skos:Concept .
    ?uri skos:broader <http://id.southampton.ac.uk/opendays/".date('Y/m', strtotime(self::$dates[0]))."/subject/Subject> .
    ?uri rdfs:label ?label .
  }
} ORDER BY ?label
		");
	}

	static function getDataSetExtras()
	{
		return array("Contains Ordnance Survey data &copy; Crown copyright and database right 2011.  Contains Royal Mail data &copy; Royal Mail copyright and database right 2011.");
	}

	static function getAllPointsOfService()
	{
		$uri = 'http://id.southampton.ac.uk/opendays/'.date('Y/m', strtotime(self::$dates[0]));
		$points = array();
		$tpoints = sparql_get(self::$endpoint, "
		SELECT DISTINCT ?id ?lat ?long ?label ?number WHERE {
		  ?id a <http://vocab.deri.ie/rooms#Building> .
		  OPTIONAL { ?id <http://purl.org/dc/terms/spatial> ?outline . }
		  ?id <http://www.w3.org/2003/01/geo/wgs84_pos#lat> ?lat .
		  ?id <http://www.w3.org/2003/01/geo/wgs84_pos#long> ?long .
		  ?id <http://www.w3.org/2000/01/rdf-schema#label> ?label .
		  OPTIONAL { ?id <http://www.w3.org/2004/02/skos/core#notation> ?number . }
		  ?s <http://purl.org/NET/c4dm/event.owl#place> ?id .
		  ?s <http://purl.org/dc/terms/isPartOf> <$uri> .
		} 
		");
		foreach($tpoints as $point)
		{
			$point['label'] = str_replace('\'', '\\\'', $point['label']);
			$point['label'] = str_replace("\\", "\\\\", $point['label']);
			if($point['icon'] == "")
				$point['icon'] = "http://opendatamap.ecs.soton.ac.uk/resources/numbericon.php?n=".$point['number'];
			$points[] = $point;
		}
		$tpoints = sparql_get(self::$endpoint, "
	PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
	PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
	PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
	PREFIX org: <http://www.w3.org/ns/org#>
	PREFIX gr: <http://purl.org/goodrelations/v1#>

	SELECT DISTINCT ?id ?lat ?long ?label ?icon ?s WHERE {
	  ?id a gr:LocationOfSalesOrServiceProvisioning .
	  ?id rdfs:label ?label .
	  ?id <http://purl.org/dc/terms/subject> <http://id.southampton.ac.uk/point-of-interest-category/Transport> .
	  OPTIONAL { ?id spacerel:within ?s .
		     ?s a org:Site .
		   }
	  OPTIONAL { ?id geo:lat ?lat .
	             ?id geo:long ?long .
	           }
	  OPTIONAL { ?id <http://purl.org/openorg/mapIcon> ?icon . }
	  FILTER ( BOUND(?long) && BOUND(?lat) && !BOUND(?s) && ?icon != <http://data.southampton.ac.uk/map-icons/Transportation/fillingstation.png> && (?icon != <http://data.southampton.ac.uk/map-icons/Transportation/parking.png> || ?id = <http://id.southampton.ac.uk/point-of-service/parking-7326>) )
	} ORDER BY ?label
		");
		foreach($tpoints as $point)
		{
			$point['label'] = str_replace('\'', '\\\'', $point['label']);
			$point['label'] = str_replace("\\", "\\\\", $point['label']);
			$point['icon'] = self::convertIcon($point['icon']);
			if($point['icon'] == "")
				$point['icon'] = "img/blackness.png";
			$points[] = $point;
		}
		$tpoints = sparql_get(self::$endpoint, "
	PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
	PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
	PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
	PREFIX org: <http://www.w3.org/ns/org#>
	PREFIX gr: <http://purl.org/goodrelations/v1#>

	SELECT DISTINCT ?id ?lat ?long ?label ?icon WHERE {
	  ?id a gr:LocationOfSalesOrServiceProvisioning .
	  ?id rdfs:label ?label .
	  {
	    ?id spacerel:within ?s .
	    ?s geo:lat ?lat . 
            ?s geo:long ?long .
	  } UNION {
	    ?id spacerel:within ?b .
	    ?b a <http://vocab.deri.ie/rooms#Building> .
	    ?b spacerel:within ?s .
            ?b geo:lat ?lat . 
            ?b geo:long ?long .
	  } .
	  ?s a org:Site .
	  {
	    ?id <http://purl.org/openorg/mapIcon> <http://data.southampton.ac.uk/map-icons/Stores/conveniencestore.png>
	  } UNION {
	    ?id <http://purl.org/dc/terms/subject> <http://id.southampton.ac.uk/point-of-interest-category/Catering>
	  } .
	  OPTIONAL { ?id <http://purl.org/openorg/mapIcon> ?icon . }
	  FILTER ( BOUND(?long) && BOUND(?lat) && !REGEX( ?label, 'Performance Nights ONLY', 'i')
		&& ( ?s = <http://id.southampton.ac.uk/site/1> || ?s = <http://id.southampton.ac.uk/site/3> || ?s = <http://id.southampton.ac.uk/site/6> )
          )
	} ORDER BY ?label
		");
		foreach($tpoints as $point)
		{
			$point['label'] = str_replace('\'', '\\\'', $point['label']);
			$point['label'] = str_replace("\\", "\\\\", $point['label']);
			$point['icon'] = self::convertIcon($point['icon']);
			if($point['icon'] == "")
				$point['icon'] = "img/blackness.png";
			$points[] = $point;
		}
		return $points;
	}

	static function getPointsOfService($q)
	{
		$uri = 'http://id.southampton.ac.uk/opendays/'.date('Y/m', strtotime(self::$dates[0]));
		$q = explode('/', $q);
		$subject = $q[0];
		if(count($q) > 0)
		{
			$date = null;
			foreach(self::$dates as $d)
			{
				$d = strtotime($d);
				if($q[1] == strtolower(date('l', $d)))
				{
					$date = date('Y-m-d', $d);
				}
			}
		}
		if($date != null)
		{
			$tpoints = sparql_get(self::$endpoint, "
		SELECT DISTINCT ?pos ?s ?l ?start ?b WHERE {
                  ?pos a <http://vocab.deri.ie/rooms#Building> .
                  OPTIONAL { ?id <http://purl.org/dc/terms/spatial> ?outline . }
                  ?pos <http://www.w3.org/2003/01/geo/wgs84_pos#lat> ?lat .
                  ?pos <http://www.w3.org/2003/01/geo/wgs84_pos#long> ?long .
                  ?pos <http://www.w3.org/2000/01/rdf-schema#label> ?label .
                  OPTIONAL { ?pos <http://www.w3.org/2004/02/skos/core#notation> ?number . }
                  ?s <http://purl.org/NET/c4dm/event.owl#place> ?pos .
                  ?s <http://purl.org/dc/terms/isPartOf> <$uri> .
		  ?s <http://purl.org/dc/terms/subject> ?subj .
		  OPTIONAL { ?subj <http://www.w3.org/2004/02/skos/core#broader> ?b }
		  ?s <http://www.w3.org/2000/01/rdf-schema#label> ?l .
    		  ?s <http://purl.org/NET/c4dm/event.owl#time> ?time .
    		  ?time <http://purl.org/NET/c4dm/timeline.owl#start> ?start .
                } 
			");
			foreach($tpoints as $point)
			{
				$d = strtotime(self::$dates[0]);
				if(substr($point['start'], 0, 10) == $date && 
						($subject == '' || 
						$point['b'] == 'http://id.southampton.ac.uk/opendays/'.date('Y/m', $d).'/subject/InformationStand' || 
						$point['b'] == 'http://id.southampton.ac.uk/opendays/'.date('Y/m', $d).'/subject/General' || 
						preg_match('/^http:\/\/id\.southampton\.ac\.uk\/opendays\/'.str_replace('/', '\/', date('Y/m', $d)).'\/event\/'.$subject.'-/', $point['s'])))
				{
					$points[] = $point;
				}
			}
		}
		$tpoints = sparql_get(self::$endpoint, "
	PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
	PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
	PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
	PREFIX org: <http://www.w3.org/ns/org#>
	PREFIX gr: <http://purl.org/goodrelations/v1#>

	SELECT DISTINCT ?pos WHERE {
	  ?pos a gr:LocationOfSalesOrServiceProvisioning .
	  ?pos <http://purl.org/dc/terms/subject> <http://id.southampton.ac.uk/point-of-interest-category/Transport> .
	  OPTIONAL { ?pos spacerel:within ?s .
		     ?s a org:Site .
		   }
	  OPTIONAL { ?pos <http://purl.org/openorg/mapIcon> ?icon . }
	  FILTER ( !BOUND(?s) && ?icon != <http://data.southampton.ac.uk/map-icons/Transportation/fillingstation.png> && (?icon != <http://data.southampton.ac.uk/map-icons/Transportation/parking.png> || ?pos = <http://id.southampton.ac.uk/point-of-service/parking-7326>) )
	}
		");
		foreach($tpoints as $point)
			$points[] = $point;
		$tpoints = sparql_get(self::$endpoint, "
	PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
	PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
	PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
	PREFIX org: <http://www.w3.org/ns/org#>
	PREFIX gr: <http://purl.org/goodrelations/v1#>

	SELECT DISTINCT ?pos WHERE {
	  ?pos a gr:LocationOfSalesOrServiceProvisioning .
	  ?pos rdfs:label ?label .
	  {
	    ?pos spacerel:within ?s .
	  } UNION {
	    ?pos spacerel:within ?b .
	    ?b a <http://vocab.deri.ie/rooms#Building> .
	    ?b spacerel:within ?s .
	  } .
	  ?s a org:Site .
	  {
	    ?pos <http://purl.org/openorg/mapIcon> <http://data.southampton.ac.uk/map-icons/Stores/conveniencestore.png>
	  } UNION {
	    ?pos <http://purl.org/dc/terms/subject> <http://id.southampton.ac.uk/point-of-interest-category/Catering>
	  } .
	  FILTER ( !REGEX( ?label, 'Performance Nights ONLY', 'i')
		&& ( ?s = <http://id.southampton.ac.uk/site/1> || ?s = <http://id.southampton.ac.uk/site/3> || ?s = <http://id.southampton.ac.uk/site/6> )
          )
	} ORDER BY ?label
		");
		foreach($tpoints as $point)
			$points[] = $point;
		return $points;
	}

	static function getAllBusStops()
	{
		$tpoints = sparql_get(self::$endpoint, "
	PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
	PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
	PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
	PREFIX org: <http://www.w3.org/ns/org#>
	PREFIX foaf: <http://xmlns.com/foaf/0.1/>

	SELECT ?id ?lat ?long ?label (GROUP_CONCAT(?code) as ?codes) WHERE {
	  ?rstop <http://id.southampton.ac.uk/ns/inBusRoute> ?route .
	  ?rstop <http://id.southampton.ac.uk/ns/busStoppingAt> ?id .
	  ?route <http://www.w3.org/2004/02/skos/core#notation> ?code .
	  ?id rdfs:label ?label .
	  ?id geo:lat ?lat .
	  ?id geo:long ?long .
	  ?id foaf:based_near ?s .
	  FILTER ( REGEX( ?code, '^U', 'i') && !REGEX( ?label, 'RTI ghost', 'i') && ?s != <http://id.southampton.ac.uk/site/18> && ?s != <http://id.southampton.ac.uk/site/62>)
	} GROUP BY ?id ?label ?lat ?long ORDER BY ?label
		");
		$points = array();
		foreach($tpoints as $point)
		{
			$codes = explode(' ', $point['codes']);
			sort($codes);
			$codes = array_unique($codes);
			$codes = implode('/', $codes);
			$point['icon'] = "http://opendatamap.ecs.soton.ac.uk/resources/busicon.php?r=".$codes;
			$points[] = $point;
		}
		return $points;
	}

	static function getBusStops($q)
	{
		return sparql_get(self::$endpoint, "
	PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
	PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
	PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
	PREFIX org: <http://www.w3.org/ns/org#>
	PREFIX foaf: <http://xmlns.com/foaf/0.1/>

	SELECT ?pos WHERE {
	  ?rstop <http://id.southampton.ac.uk/ns/inBusRoute> ?route .
	  ?rstop <http://id.southampton.ac.uk/ns/busStoppingAt> ?pos .
	  ?route <http://www.w3.org/2004/02/skos/core#notation> ?code .
	  ?pos rdfs:label ?label .
	  ?pos foaf:based_near ?s .
	  FILTER ( REGEX( ?code, '^U', 'i') && !REGEX( ?label, 'RTI ghost', 'i') && ?s != <http://id.southampton.ac.uk/site/18> && ?s != <http://id.southampton.ac.uk/site/62>)
	}
		");
	}

	static function getBuildings($q, $qbd)
	{
		return sparql_get(self::$endpoint, "
	SELECT DISTINCT ?uri ?name ?num WHERE {
	  ?uri a <http://vocab.deri.ie/rooms#Building> .
	  ?uri <http://www.w3.org/2000/01/rdf-schema#label> ?name .
	  ?uri <http://www.w3.org/2004/02/skos/core#notation> ?num .
	  FILTER ( REGEX( ?name, '$q', 'i') || REGEX( ?num, '$qbd', 'i') )
	} ORDER BY ?num
		");
	}

	static function getAllTimetables()
	{
		$uri = 'http://id.southampton.ac.uk/opendays/'.date('Y/m', strtotime(self::$dates[0]));
		$data = sparql_get(self::$endpoint, "
PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
PREFIX org: <http://www.w3.org/ns/org#>

SELECT DISTINCT ?uri ?broader ?label ?event ?start ?end ?desc ?building ?site ?placelabel ?number ?name WHERE {
    ?event <http://purl.org/dc/terms/isPartOf> <$uri> .
    ?uri a skos:Concept .
    ?uri skos:broader ?broaderuri .
    ?broaderuri rdfs:label ?broader .
    ?uri rdfs:label ?label .
    ?event <http://purl.org/dc/terms/subject> ?uri .
    ?event <http://purl.org/NET/c4dm/event.owl#time> ?time .
    ?time <http://purl.org/NET/c4dm/timeline.owl#start> ?start .
    ?time <http://purl.org/NET/c4dm/timeline.owl#end> ?end .
    ?event <http://purl.org/dc/terms/description> ?desc .
    ?event <http://purl.org/NET/c4dm/event.owl#place> ?building .
    ?event <http://purl.org/NET/c4dm/event.owl#place> ?place .
    ?building a <http://vocab.deri.ie/rooms#Building> .
    ?building spacerel:within ?site .
    ?site a org:Site .
    ?building <http://www.w3.org/2004/02/skos/core#notation> ?number .
    ?building rdfs:label ?name .
    ?place a <http://www.w3.org/2003/01/geo/wgs84_pos#SpatialThing> .
    ?place rdfs:label ?placelabel .
} ORDER BY DESC(?broader = 'Subject') ?label
		");
		return $data;
	}
	
	static function getAllSites()
	{
		return sparql_get(self::$endpoint, "
		SELECT DISTINCT ?uri ?name ?outline WHERE {
		  ?uri a <http://www.w3.org/ns/org#Site> .
		  ?uri <http://purl.org/dc/terms/spatial> ?outline .
		  ?uri <http://www.w3.org/2000/01/rdf-schema#label> ?name .
		} 
		");
	}
	
	static function getAllBuildings()
	{
		return sparql_get(self::$endpoint, "
		SELECT DISTINCT ?uri ?name ?outline ?lat ?lng ?num WHERE {
		  ?uri a <http://vocab.deri.ie/rooms#Building> .
		  ?uri <http://purl.org/dc/terms/spatial> ?outline .
		  ?uri <http://www.w3.org/2003/01/geo/wgs84_pos#lat> ?lat .
		  ?uri <http://www.w3.org/2003/01/geo/wgs84_pos#long> ?lng .
		  ?uri <http://www.w3.org/2000/01/rdf-schema#label> ?name .
		  OPTIONAL { ?uri <http://www.w3.org/2004/02/skos/core#notation> ?num . }
		} 
		");
	}

	static function getSites($q)
	{
		return sparql_get(self::$endpoint, "
	SELECT DISTINCT ?uri ?name WHERE {
	  ?uri a <http://www.w3.org/ns/org#Site> .
	  ?uri <http://www.w3.org/2000/01/rdf-schema#label> ?name .
	  ?uri <http://purl.org/dc/terms/spatial> ?outline .
	  FILTER ( REGEX( ?name, '$q', 'i') )
	} ORDER BY ?uri
		");
	}

	static function visibleCategory($icon, $cats)
	{
		global $iconcats;
		if($iconcats == null) include_once "inc/categories.php";
		return in_cat($iconcats, $icon, $cats);
	}

	// Process point of service data
	static function createPointOfServiceEntries(&$pos, &$label, &$type, &$url, &$icon, $q, $cats)
	{
		$data = self::getPointsOfService($q);
		foreach($data as $point) {
			$pos[$point['pos']] ++;
		}
	}

	// Process bus data
	static function createBusEntries(&$pos, &$label, &$type, &$url, &$icon, $q, $cats)
	{
		$data = self::getBusStops($q);
		foreach($data as $point) {
			$pos[$point['pos']] ++;
		}
	}

	// Process workstation data
	static function createWorkstationEntries(&$pos, &$label, &$type, &$url, &$icon, $q, $cats)
	{
		$data = self::getWorkstationRooms($q);
		foreach($data as $point) {
			$point['icon'] = self::$iconpath.'Education/computers.png';
			if(!self::visibleCategory($point['icon'], $cats))
				continue;
			$pos[$point['pos']] ++;
			if(preg_match('/'.$q.'/i', $point['label']))
			{
				$label[$point['label']] ++;
				$type[$point['label']] = "offering";
			}
			if(preg_match('/'.$q.'/i', $point['poslabel']))
			{
				$label[$point['poslabel']] += 10;
				$type[$point['poslabel']] = "workstation";
				$url[$point['poslabel']] = $point['pos'];
				$icon[$point['poslabel']] = $point['icon'];
			}
		}
	}

	// Process building data
	static function createBuildingEntries(&$pos, &$label, &$type, &$url, &$icon, $q, $cats)
	{
		$qbd = trim(str_replace(array('building', 'buildin', 'buildi', 'build', 'buil', 'bui', 'bu', 'b'), '', strtolower($q)));
		$data = self::getBuildings($q, $qbd);
		foreach($data as $point) {
			$pos[$point['url']] += 100;
			if(preg_match('/'.$q.'/i', $point['name']))
			{
				$label[$point['name']] += 100;
				$type[$point['name']] = "building";
				$url[$point['name']] = $point['url'];
				$icon[$point['name']] = 'http://opendatamap.ecs.soton.ac.uk/resources/numbericon.php?n='.$point['number'];
			}
			if(preg_match('/'.$qbd.'/i', $point['number']))
			{
				$label['Building '.$point['number']] += 100;
				$type['Building '.$point['number']] = "building";
				$url['Building '.$point['number']] = $point['url'];
				$icon['Building '.$point['number']] = 'http://opendatamap.ecs.soton.ac.uk/resources/numbericon.php?n='.$point['number'];
			}
		}
	}

	// Process site data
	static function createSiteEntries(&$pos, &$label, &$type, &$url, &$icon, $q, $cats)
	{
		$data = self::getSites($q);
		foreach($data as $point) {
			$pos[$point['url']] += 1000;
			$label[$point['name']] += 1000;
			$type[$point['name']] = "site";
			$url[$point['name']] = $point['url'];
			$icon[$point['name']] = 'http://opendatamap.ecs.soton.ac.uk/resources/numbericon.php?n='.substr($point['name'], 0, 1);
		}
	}
	
	static function processURI($uri)
	{
		if(substr($uri, 0, strlen('http://id.southampton.ac.uk/bus-stop/')) == 'http://id.southampton.ac.uk/bus-stop/')
			return self::processSouthamptonBusStopURI($uri);
		if(substr($uri, 0, strlen('http://id.southampton.ac.uk/building/')) == 'http://id.southampton.ac.uk/building/')
			return self::processSouthamptonBuildingURI($uri);
		else if(substr($uri, 0, strlen('http://id.southampton.ac.uk/')) == 'http://id.southampton.ac.uk/')
			return self::processSouthamptonURI($uri);
		else
			return false;
	}

	static function processSouthamptonBusStopURI($uri)
	{	
		$allpos = self::getURIInfo($uri);
		$allbus = sparql_get(self::$endpoint, "
		SELECT DISTINCT ?code WHERE {
		  ?rstop <http://id.southampton.ac.uk/ns/inBusRoute> ?route .
		  ?rstop <http://id.southampton.ac.uk/ns/busStoppingAt> <$uri> .
		  ?route <http://www.w3.org/2004/02/skos/core#notation> ?code .
		  FILTER ( REGEX( ?code, '^U', 'i') )
		} ORDER BY ?code
		");
		$codes = array();
		foreach($allbus as $code)
			$codes[] = $code['code'];
		echo "<h2><img class='icon' src='http://opendatamap.ecs.soton.ac.uk/resources/busicon.php?r=".implode('/', $codes)."' />".$allpos[0]['name'];
		echo "<a class='odl' href='$uri'>Visit&nbsp;page</a></h2>";
		echo "<h3> Served by: </h3>";
		echo "<ul class='offers'>"; 
		foreach($allbus as $code) {
			echo "<li ".self::routestyle($code['code']).">".$code['code']."</li>";
		}
		echo "</ul>";
		echo "<iframe style='border:none' src='bus.php?uri=".$uri."' />";
		return true;
	}

	static function getURIInfo($uri)
	{
		return sparql_get(self::$endpoint, "
		PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
		PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
		PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
		PREFIX org: <http://www.w3.org/ns/org#>

		SELECT DISTINCT ?name ?icon WHERE {
		    <$uri> rdfs:label ?name .
		    OPTIONAL { <$uri> <http://purl.org/openorg/mapIcon> ?icon . }
		}
		");
	}

	static function processSouthamptonBuildingURI($uri)
	{
		$allpos = sparql_get(self::$endpoint, "
		SELECT DISTINCT ?name ?number WHERE {
		  <$uri> <http://www.w3.org/2000/01/rdf-schema#label> ?name .
		  <$uri> <http://www.w3.org/2004/02/skos/core#notation> ?number .
		}");
		echo "<h2><img class='icon' src='resources/numbericon.php?n=".$allpos[0]['number']."' />".$allpos[0]['name'];
		echo "<a class='odl' href='".$uri."'>Visit page</a>";
		echo "</h2>";
		return true;
	}

	static function processSouthamptonURI($uri)
	{
		$allpos = self::getURIInfo($uri);
		echo "<div id='content'>";
		$computer = false;
		if(!isset($allpos[0]['icon']))
		{
			if(substr($uri, 0, 33) == "http://id.southampton.ac.uk/room/")
			{
				$icon = self::$iconpath.'Education/computers.png';
				$computer = "true";
			}
			else
			{
				$icon = "";
			}
		}
		else
			$icon = $allpos[0]['icon'];
		$icon = self::convertIcon($icon);

		$page = sparql_get(self::$endpoint, "
		PREFIX foaf: <http://xmlns.com/foaf/0.1/>

		SELECT DISTINCT ?page WHERE {
			<$uri> foaf:page ?page .
		} ORDER BY ?page
		");

		//if(count($page) > 0)
		echo "<h2><img class='icon' src='".($icon!=""?$icon:"img/blackness.png")."' />".$allpos[0]['name'];
		if(preg_match('/http:\/\/id\.southampton\.ac\.uk\/.*/', $uri))
		{
			//print_r($page[0]);
			//echo "<a class='odl' href='".$page[0]['page']."'>Visit page</a>";
			echo "<a class='odl' href='".$uri."'>Visit page</a>";
		}
		echo "</h2>";

		if($computer)
		{
			$allpos = sparql_get(self::$endpoint, "
		PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
		PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
		PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
		PREFIX org: <http://www.w3.org/ns/org#>
		PREFIX gr: <http://purl.org/goodrelations/v1#>

		SELECT DISTINCT ?label WHERE {
			<$uri> <http://purl.org/openorg/hasFeature> ?f .
			?f a ?ft .
			?ft rdfs:label ?label .
			FILTER ( REGEX(?label, '^(WORKSTATION|SOFTWARE) -') )
		} ORDER BY ?label
			");
		}
		else
		{
			$allpos = sparql_get(self::$endpoint, "
		PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
		PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
		PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
		PREFIX org: <http://www.w3.org/ns/org#>
		PREFIX gr: <http://purl.org/goodrelations/v1#>

		SELECT DISTINCT ?label WHERE {
			?o gr:availableAtOrFrom <$uri> .
			?o gr:includes ?ps .
			?ps a gr:ProductOrServicesSomeInstancesPlaceholder .
			?ps rdfs:label ?label .
		} ORDER BY ?label 
			");
		}

		if(count($allpos) == 0)
		{
			$allpos = sparql_get(self::$endpoint, "
			PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
			PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
			PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
			PREFIX org: <http://www.w3.org/ns/org#>
			PREFIX gr: <http://purl.org/goodrelations/v1#>

			SELECT DISTINCT ?label WHERE {
				?o gr:availableAtOrFrom <$uri> .
				?o gr:includes ?ps .
				?ps a gr:ProductOrService .
				?ps rdfs:label ?label .
			} ORDER BY ?label 
			");
		}
		if(count($allpos) > 0)
		{
			echo "<h3> Offers:</h3>";
			echo "<ul class='offers'>"; 
			foreach($allpos as $point) {
				echo "<li>".$point['label']."</li>";
			}
			echo "</ul>";
		}

		if(preg_match('/http:\/\/id\.southampton\.ac\.uk\/point-of-service\/parking-(.*)/', $uri, $matches))
		{
			echo "<iframe style='border:none' src='parking.php?uri=".$_GET['uri']."' />";
			echo "</div>";
			die();
		}

		$allpos = sparql_get(self::$endpoint, "
		PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
		PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
		PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
		PREFIX org: <http://www.w3.org/ns/org#>
		PREFIX gr: <http://purl.org/goodrelations/v1#>

		SELECT DISTINCT * WHERE {
			<$uri> gr:hasOpeningHoursSpecification ?time .
			OPTIONAL { ?time gr:validFrom ?start . }
			OPTIONAL { ?time gr:validThrough ?end . }
			?time gr:hasOpeningHoursDayOfWeek ?day .
			?time gr:opens ?opens .
			?time gr:closes ?closes .
		} ORDER BY ?start ?end ?day ?opens ?closes
		");
		//	OPTIONAL { <$uri> <http://purl.org/dc/terms/description> ?desc }

		if(count($allpos) > 0)
		{
			$today = $_GET['date'];

			//echo "<div id='openings'>";
			//echo "<h3>Opening detail:</h3>";
			foreach($allpos as $point)
			{
				if ($point['start'] != '')
				{
					$start = strtotime($point['start']);
					$start = date('d/m/Y',$start);
				}
				else 
				{
					$start = '';
				}
				if ($point['end'] != '')
				{
					$end = strtotime($point['end']);
					$end = date('d/m/Y',$end);
				}
				else
				{
					$end = '';
				}
				$open = strtotime($point['opens']);
				$open = date('H:i',$open);
				$close = strtotime($point['closes']);
				$close = date('H:i',$close);
				$ot[$start."-".$end][$point['day']][] = $open."-".$close;
			}

			$weekday = array('Monday', 'Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday');
			//echo "<table id='openings' style='font-size:0.8em'>";
			//echo "<tr>";
			foreach($weekday as $day)
			{
				$short_day = substr($day, 0,3); 
				//echo "<th>".$short_day."</th>";
			}
			//echo "<th>Valid Dates</th>";
			//echo "</tr>";

			$now = strtotime($today.' 12:00');
			foreach($ot as $valid => $otv)
			{
				list($from, $to) = explode('-',$valid);
				if ($from == '')
				{
					$from = $now - 86400;
				}
				else
				{
					$from = mktime(0,0,0,substr($from,3,2),substr($from,0,2),substr($from,7,4));
				}
				if ($to == '')
				{
					$to = $now+86400;
				}
				else
				{
					$to = mktime(0,0,0,substr($to,3,2),substr($to,0,2),substr($to,7,4));
				} 

				if ( $to < $now )
				{
					continue;
				}
				if ($from > $now + (60*60*24*30))
				{ 
					continue;
				}
				$current = ($from <=  $now )&&( $to >= $now);
				if ($current)
				{ 
					//echo "<tr class='current'>"; //start of row
					foreach($weekday as $day)
					{
						//echo "<td width=\"350\">";
						if(array_key_exists('http://purl.org/goodrelations/v1#'.$day, $otv))
						{
							foreach($otv['http://purl.org/goodrelations/v1#'.$day] as $dot)
							{
								if($dot == '00:00-00:00')
									$dot = '24 hour';
								//echo $dot."<br/>";
								if($day == date('l', $now))
								{
									$todayopening[] = "<li>$dot</li>";
								}
							}
						}
						//echo "</td>";
					}
				}
				else
				{
					//echo "<tr>";
				}
				//echo "<td>".$valid."</td>";
				//echo "</tr>";
			}
			//echo "</table>";
			//echo "</div>";

			if($todayopening != null)
			{
				echo "<div id='todayopenings'>";
				echo "<h3>Opening hours on ".date('l jS F Y', $now).":</h3>";
				echo "<ul style='padding-top:8px;'>";
				foreach($todayopening as $opening)
				{
					echo $opening;
				}
				echo "</ul>";
				echo "</div>";
			}
		}

		//if($allpos[0]['desc'] != null)
		//{
		//	echo '<div style="font-size:0.7em; text-align:justify">'.$allpos[0]['desc'].'</div>';
		//}

		if(substr($uri, 0, strlen('http://id.sown.org.uk/')) == 'http://id.sown.org.uk/')
			self::processSownURI($uri);
		echo "</div>";
		return true;
	}

	static function routestyle($code)
	{
		$color['U1'] = array(  0, 142, 207);
		$color['U2'] = array(226,   2,  20);
		$color['U6'] = array(246, 166,  24);
		$color['U9'] = array(232,  84, 147);
		if(isset($color[$code]))
		{
			return "style='background-color:#".str_pad(dechex($color[$code][0]), 2, '0').str_pad(dechex($color[$code][1]), 2, '0').str_pad(dechex($color[$code][2]), 2, '0').";' ";
		}
	}	
}
?>
