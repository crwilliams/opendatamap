<?
include_once "inc/sparqllib.php";

class DataSource{}

class SouthamptonDataSource extends DataSource
{
	static $endpoint = "http://sparql.data.southampton.ac.uk";

	static function getAll()
	{
		$points = array();
		foreach(SouthamptonDataSource::getAllPointsOfService()	as $point) $points[] = $point;
		foreach(SouthamptonDataSource::getAllBusStops()	 	as $point) $points[] = $point;
		foreach(SouthamptonDataSource::getAllWorkstationRooms()	as $point) $points[] = $point;
		return $points;
	}

	static function getEntries($q, $cats)
	{
		$pos = array();
		$label = array();
		$type = array();
		$url = array();
		$icon = array();
		SouthamptonDataSource::createPointOfServiceEntries($pos, $label, $type, $url, $icon, $q, $cats);
		SouthamptonDataSource::createBusEntries($pos, $label, $type, $url, $icon, $q, $cats);
		SouthamptonDataSource::createWorkstationEntries($pos, $label, $type, $url, $icon, $q, $cats);
		SouthamptonDataSource::createBuildingEntries($pos, $label, $type, $url, $icon, $q, $cats);
		SouthamptonDataSource::createSiteEntries($pos, $label, $type, $url, $icon, $q, $cats);
		return array($pos, $label, $type, $url, $icon);
	}

	static function getAllPointsOfService()
	{
		$tpoints = sparql_get(self::$endpoint, "
	PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
	PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
	PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
	PREFIX org: <http://www.w3.org/ns/org#>
	PREFIX gr: <http://purl.org/goodrelations/v1#>

	SELECT DISTINCT ?id ?lat ?long ?label ?icon WHERE {
	  ?id a gr:LocationOfSalesOrServiceProvisioning .
	  ?id rdfs:label ?label .
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
	  FILTER ( BOUND(?long) && BOUND(?lat) )
	} ORDER BY ?label
		");
		$points = array();
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

	SELECT ?id ?lat ?long ?label (GROUP_CONCAT(?code) as ?codes) WHERE {
	  ?rstop <http://id.southampton.ac.uk/ns/inBusRoute> ?route .
	  ?rstop <http://id.southampton.ac.uk/ns/busStoppingAt> ?id .
	  ?route <http://www.w3.org/2004/02/skos/core#notation> ?code .
	  ?id rdfs:label ?label .
	  ?id geo:lat ?lat .
	  ?id geo:long ?long .
	  FILTER ( REGEX( ?code, '^U', 'i') )
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
}
?>
