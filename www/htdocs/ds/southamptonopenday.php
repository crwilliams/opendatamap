<?
include_once "inc/sparqllib.php";

class SouthamptonopendayDataSource extends DataSource
{
	static $endpoint = "http://sparql.data.southampton.ac.uk";

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

	static function getDataSetExtras()
	{
		return array("Contains Ordnance Survey data &copy; Crown copyright and database right 2011.  Contains Royal Mail data &copy; Royal Mail copyright and database right 2011.");
	}

/*
*/
	static function getAllPointsOfService()
	{
		$points = array();
		$tpoints = sparql_get(self::$endpoint, "
		SELECT DISTINCT ?id ?lat ?long ?label ?number WHERE {
		  ?id a <http://vocab.deri.ie/rooms#Building> .
		  OPTIONAL { ?id <http://purl.org/dc/terms/spatial> ?outline . }
		  ?id <http://www.w3.org/2003/01/geo/wgs84_pos#lat> ?lat .
		  ?id <http://www.w3.org/2003/01/geo/wgs84_pos#long> ?long .
		  ?id <http://www.w3.org/2000/01/rdf-schema#label> ?label .
		  OPTIONAL { ?id <http://www.w3.org/2004/02/skos/core#notation> ?number . }
		} 
		");
		foreach($tpoints as $point)
		{
			$vbuildings = array(36,12,13,18,2,30,32,34,38,4,40,42,44,45,46,48,52,53,54,58,'58A',6,65,67,68,7,'76Z',85);
			if(!in_array($point['number'], $vbuildings))
				continue;
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
	  FILTER ( BOUND(?long) && BOUND(?lat) && !BOUND(?s) && ?icon != <http://google-maps-icons.googlecode.com/files/gazstation.png> && (?icon != <http://google-maps-icons.googlecode.com/files/parking.png> || ?id = <http://id.southampton.ac.uk/point-of-service/parking-7326>) )
	} ORDER BY ?label
		");
		foreach($tpoints as $point)
		{
			$point['label'] = str_replace('\'', '\\\'', $point['label']);
			$point['label'] = str_replace("\\", "\\\\", $point['label']);
			$point['icon'] = str_replace("http://google-maps-icons.googlecode.com/files/", "http://opendatamap.ecs.soton.ac.uk/img/icon/", $point['icon']);
			$point['icon'] = str_replace("http://data.southampton.ac.uk/map-icons/lattes.png", "http://opendatamap.ecs.soton.ac.uk/img/icon/coffee.png", $point['icon']);
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
	    ?id <http://purl.org/openorg/mapIcon> <http://google-maps-icons.googlecode.com/files/convenience.png>
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
			$point['icon'] = str_replace("http://google-maps-icons.googlecode.com/files/", "http://opendatamap.ecs.soton.ac.uk/img/icon/", $point['icon']);
			$point['icon'] = str_replace("http://data.southampton.ac.uk/map-icons/lattes.png", "http://opendatamap.ecs.soton.ac.uk/img/icon/coffee.png", $point['icon']);
			if($point['icon'] == "")
				$point['icon'] = "img/blackness.png";
			$points[] = $point;
		}
		return $points;
	}

	static function getPointsOfService($q)
	{
		if($q == '')
			$filter = '';
		else
			$filter = "FILTER ( REGEX( ?label, '$q', 'i') || REGEX( ?poslabel, '$q', 'i') )";
		return sparql_get(self::$endpoint, "
	PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
	PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
	PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
	PREFIX org: <http://www.w3.org/ns/org#>
	PREFIX gr: <http://purl.org/goodrelations/v1#>

	SELECT DISTINCT ?poslabel ?label ?pos ?icon WHERE {
	  ?pos a gr:LocationOfSalesOrServiceProvisioning .
	  OPTIONAL {
	    ?offering gr:availableAtOrFrom ?pos .
	    ?offering a gr:Offering .
	    ?offering gr:includes ?ps .
	    ?ps rdfs:label ?label .
	  }
	  ?pos rdfs:label ?poslabel .
	  ?pos <http://purl.org/openorg/mapIcon> ?icon .
	  $filter
	} ORDER BY ?poslabel
		");
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
	  FILTER ( REGEX( ?code, '^U', 'i') && !REGEX( ?label, 'RTI ghost', 'i') && ?s != <http://id.southampton.ac.uk/site/18>)
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

	SELECT DISTINCT ?poslabel ?label ?pos ?icon WHERE {
	  ?rstop <http://id.southampton.ac.uk/ns/inBusRoute> ?route .
	  ?rstop <http://id.southampton.ac.uk/ns/busStoppingAt> ?pos .
	  ?route <http://www.w3.org/2004/02/skos/core#notation> ?label .
	  ?pos rdfs:label ?poslabel .
	  ?pos <http://purl.org/openorg/mapIcon> ?icon .
	  FILTER ( ( REGEX( ?label, '$q', 'i') || REGEX( ?poslabel, '$q', 'i')
	  ) && REGEX( ?label, '^U', 'i') )
	} ORDER BY ?poslabel
		");
	}

	static function getAllWorkstationRooms()
	{
		$tpoints = sparql_get(self::$endpoint, "
	PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
	PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
	PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
	PREFIX org: <http://www.w3.org/ns/org#>
	PREFIX gr: <http://purl.org/goodrelations/v1#>
	PREFIX skos: <http://www.w3.org/2004/02/skos/core#>

	SELECT DISTINCT ?id ?lat ?long ?label WHERE {
	  ?id <http://purl.org/openorg/hasFeature> ?f .
	  ?f a ?ft .
	  ?ft rdfs:label ?ftl .
	  ?id skos:notation ?label .
	  OPTIONAL { ?id spacerel:within ?b .
	             ?b geo:lat ?lat . 
	             ?b geo:long ?long .
	             ?b a <http://vocab.deri.ie/rooms#Building> .
	           }
	  OPTIONAL { ?id spacerel:within ?s .
	             ?s geo:lat ?lat . 
	             ?s geo:long ?long .
	             ?s a org:Site .
	           }
	  OPTIONAL { ?id geo:lat ?lat .
	             ?id geo:long ?long .
	           }
	  OPTIONAL { ?id <http://purl.org/openorg/mapIcon> ?icon . }
	  FILTER ( BOUND(?long) && BOUND(?lat) && REGEX(?ftl, '^WORKSTATION -') )
	} ORDER BY ?label
		");
		$points = array();
		foreach($tpoints as $point)
		{
			$point['icon'] = "http://opendatamap.ecs.soton.ac.uk/img/icon/computer.png";
			$points[] = $point;
		}
		return $points;
	}

	static function getWorkstationRooms($q)
	{
		if($q == '')
			$filter = '';
		else
			$filter = "&& ( REGEX( ?label, '$q', 'i') || REGEX( ?poslabel, '$q', 'i') )";
		return sparql_get(self::$endpoint, "
	PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
	PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
	PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
	PREFIX org: <http://www.w3.org/ns/org#>
	PREFIX skos: <http://www.w3.org/2004/02/skos/core#>

	SELECT DISTINCT ?poslabel ?label ?pos ?icon WHERE {
	  ?pos <http://purl.org/openorg/hasFeature> ?f .
	  ?f a ?ft .
	  ?ft rdfs:label ?label .
	  ?pos skos:notation ?poslabel .
	  FILTER ( REGEX(?label, '^(WORKSTATION|SOFTWARE) -') $filter)
	} ORDER BY ?poslabel
		");
	}

	static function getBuildings($q, $qbd)
	{
		return sparql_get(self::$endpoint, "
	SELECT DISTINCT ?url ?name ?number WHERE {
	  ?url a <http://vocab.deri.ie/rooms#Building> .
	  ?url <http://www.w3.org/2000/01/rdf-schema#label> ?name .
	  ?url <http://www.w3.org/2004/02/skos/core#notation> ?number .
	  FILTER ( REGEX( ?name, '$q', 'i') || REGEX( ?number, '$qbd', 'i') )
	} ORDER BY ?number
		");
	}
	
	static function getAllSites()
	{
		return sparql_get(self::$endpoint, "
		SELECT DISTINCT ?url ?name ?outline WHERE {
		  ?url a <http://www.w3.org/ns/org#Site> .
		  ?url <http://purl.org/dc/terms/spatial> ?outline .
		  ?url <http://www.w3.org/2000/01/rdf-schema#label> ?name .
		} 
		");
	}
	
	static function getAllBuildings()
	{
		return sparql_get(self::$endpoint, "
		SELECT DISTINCT ?url ?name ?outline ?lat ?long ?hfeature ?lfeature ?number WHERE {
		  ?url a <http://vocab.deri.ie/rooms#Building> .
		  ?url <http://purl.org/dc/terms/spatial> ?outline .
		  ?url <http://www.w3.org/2003/01/geo/wgs84_pos#lat> ?lat .
		  ?url <http://www.w3.org/2003/01/geo/wgs84_pos#long> ?long .
		  ?url <http://www.w3.org/2000/01/rdf-schema#label> ?name .
		  OPTIONAL { ?url <http://purl.org/openorg/hasFeature> ?hfeature . 
		           ?hfeature <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://id.southampton.ac.uk/ns/PlaceFeature-ResidentialUse> }
		  OPTIONAL { ?url <http://purl.org/openorg/lacksFeature> ?lfeature . 
		           ?lfeature <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://id.southampton.ac.uk/ns/PlaceFeature-ResidentialUse> }
		  OPTIONAL { ?url <http://www.w3.org/2004/02/skos/core#notation> ?number . }
		} 
		");
	}

	static function getSites($q)
	{
		return sparql_get(self::$endpoint, "
	SELECT DISTINCT ?url ?name WHERE {
	  ?url a <http://www.w3.org/ns/org#Site> .
	  ?url <http://www.w3.org/2000/01/rdf-schema#label> ?name .
	  ?url <http://purl.org/dc/terms/spatial> ?outline .
	  FILTER ( REGEX( ?name, '$q', 'i') )
	} ORDER BY ?url
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
			if(!self::visibleCategory($point['icon'], $cats))
				continue;
			$point['icon'] = str_replace("http://google-maps-icons.googlecode.com/files/", "http://opendatamap.ecs.soton.ac.uk/img/icon/", $point['icon']);
			$point['icon'] = str_replace("http://data.southampton.ac.uk/map-icons/lattes.png", "http://opendatamap.ecs.soton.ac.uk/img/icon/coffee.png", $point['icon']);
			$pos[$point['pos']] ++;
			if(preg_match('/'.$q.'/i', $point['label']))
			{
				$label[$point['label']] ++;
				$type[$point['label']] = "offering";
			}
			if(preg_match('/'.$q.'/i', $point['poslabel']))
			{
				$label[$point['poslabel']] += 10;
				$type[$point['poslabel']] = "point-of-service";
				$url[$point['poslabel']] = $point['pos'];
				$icon[$point['poslabel']] = $point['icon'];
			}
		}
	}

	// Process bus data
	static function createBusEntries(&$pos, &$label, &$type, &$url, &$icon, $q, $cats)
	{
		$data = self::getBusStops($q);
		foreach($data as $point) {
			if(!self::visibleCategory($point['icon'], $cats))
				continue;
			$point['icon'] = str_replace("http://google-maps-icons.googlecode.com/files/bus.png", "http://opendatamap.ecs.soton.ac.uk/resources/busicon.php", $point['icon']);
			$pos[$point['pos']] ++;
			if(preg_match('/'.$q.'/i', $point['label']))
				$label[$point['label']] ++;
				$type[$point['label']] = "bus-route";
			if(preg_match('/'.$q.'/i', $point['poslabel']))
			{
				$routes[$point['poslabel']][] = $point['label'];
				$label[$point['poslabel']] += 10;
				$type[$point['poslabel']] = "bus-stop";
				$url[$point['poslabel']] = $point['pos'];
				$icon[$point['poslabel']] = $point['icon'].'?r='.implode('/', $routes[$point['poslabel']]);
			}
		}
	}

	// Process workstation data
	static function createWorkstationEntries(&$pos, &$label, &$type, &$url, &$icon, $q, $cats)
	{
		$data = self::getWorkstationRooms($q);
		foreach($data as $point) {
			$point['icon'] = 'http://opendatamap.ecs.soton.ac.uk/img/icon/computer.png';
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
		else if(substr($uri, 0, strlen('http://id.southampton.ac.uk/')) == 'http://id.southampton.ac.uk/')
			return self::processSouthamptonURI($uri);
		else if(substr($uri, 0, strlen('http://id.sown.org.uk/')) == 'http://id.sown.org.uk/')
			return self::processSouthamptonURI($uri);
		else
			return false;
	}

	static function processSownURI($uri)
	{
		return true;
		echo '<div id="content">';
		echo '<pre>';
		$data = simplexml_load_file('https://sown-auth.ecs.soton.ac.uk/status-nagios/generateNodesXML.php');
		print_r($data);
		echo '</pre>';
		echo '</div>';
		return true;
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
		echo "<h3> Served by: (click to filter) </h3>";
		echo "<ul class='offers'>"; 
		foreach($allbus as $code) {
			echo "<li ".self::routestyle($code['code'])."onclick=\"setInputBox('^".str_replace(array("(", ")"), array("\(", "\)"), $code['code'])."$'); updateFunc();\">".$code['code']."</li>";
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

	static function processSouthamptonURI($uri)
	{
		$allpos = self::getURIInfo($uri);
		echo "<div id='content'>";
		$computer = false;
		if(!isset($allpos[0]['icon']))
		{
			if(substr($uri, 0, 33) == "http://id.southampton.ac.uk/room/")
			{
				$icon = "http://opendatamap.ecs.soton.ac.uk/img/icon/computer.png";
				$computer = "true";
			}
			else
			{
				$icon = "";
			}
		}
		else
			$icon = $allpos[0]['icon'];
		$icon = str_replace("http://google-maps-icons.googlecode.com/files/", "http://opendatamap.ecs.soton.ac.uk/img/icon/", $icon);
		$icon = str_replace("http://data.southampton.ac.uk/map-icons/lattes.png", "http://opendatamap.ecs.soton.ac.uk/img/icon/coffee.png", $icon);

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
			echo "<h3> Offers: (click to filter) </h3>";
			echo "<ul class='offers'>"; 
			foreach($allpos as $point) {
				echo "<li onclick=\"setInputBox('^".str_replace(array("(", ")"), array("\(", "\)"), $point['label'])."$'); updateFunc();\">".$point['label']."</li>";
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

		if(count($allpos) > 0)
		{
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

			foreach($ot as $valid => $otv)
			{
				list($from, $to) = explode('-',$valid);
				$now = mktime();
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
				echo "<h3>Today's opening hours:</h3>";
				echo "<ul style='padding-top:8px;'>";
				foreach($todayopening as $opening)
				{
					echo $opening;
				}
				echo "</ul>";
				echo "</div>";
			}
		}
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
