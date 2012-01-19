<?
include_once "inc/sparqllib.php";

class TsinghuaDataSource extends DataSource
{
	static $endpoint = 'http://opendatamap.ecs.soton.ac.uk/tsinghua-proxy.php';

	static function getAll()
	{
		$points = array();
		//foreach(self::getAllPointsOfService()		as $point) $points[] = $point;
		//foreach(self::getAllBusStops()	 		as $point) $points[] = $point;
		//foreach(self::getAllWorkstationRooms()		as $point) $points[] = $point;
		//foreach(self::getAllISolutionsWifiPoints()	as $point) $points[] = $point;
		//foreach(self::getAllResidences()		as $point) $points[] = $point;
		//foreach(self::getAllShowers()			as $point) $points[] = $point;
		return $points;
	}

	/*
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
		self::createWorkstationEntries($pos, $label, $type, $url, $icon, $q, $cats);
		self::createISolutionsWifiPointEntries($pos, $label, $type, $url, $icon, $q, $cats);
		self::createShowerEntries($pos, $label, $type, $url, $icon, $q, $cats);
		self::createBuildingEntries($pos, $label, $type, $url, $icon, $q, $cats);
		self::createSiteEntries($pos, $label, $type, $url, $icon, $q, $cats);
		return array($pos, $label, $type, $url, $icon);
	}
	*/

	static function getDataSets()
	{
		$ds = array();
		$ds[] = array('name' => 'Tsinghua University: Buildings and Places', 'uri' => 'http://data.cs.tsinghua.edu.cn/OpenData/datasets/places.jsp', 'l' => 'null');
		return $ds;
	}

	static function getDataSetExtras()
	{
		return array();
	}

	static function getAllPointsOfService()
	{
		return array();
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
			if(!isset($point['icon']) || $point['icon'] == "")
				$point['icon'] = "img/blackness.png";
			$points[] = $point;
		}
		return $points;
	}

	static function getPointsOfService($q)
	{
		return array();
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
	} GROUP BY ?id ?label ?lat ?long ORDER BY ?label
		");
	  //FILTER ( REGEX( ?code, '^U', 'i') )
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
		return array();
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
		return array();
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
			$point['icon'] = self::$iconpath.'Education/computers.png';
			$points[] = $point;
		}
		return $points;
	}

	static function getWorkstationRooms($q)
	{
		return array();
		if($q == '')
			$filter = '';
		else
			$filter = "&& ( REGEX( ?label, '$q', 'i') || REGEX( ?poslabel, '$q', 'i') )";
		$tpoints =  sparql_get(self::$endpoint, "
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
		$points = array();
		foreach($tpoints as $point)
		{
			$point['icon'] = self::$iconpath.'Education/computers.png';
			$points[] = $point;
		}
		return $points;
	}

	static function getAllISolutionsWifiPoints()
	{
		return array();
		$tpoints = sparql_get(self::$endpoint, "
        PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
        PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
        PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
        PREFIX org: <http://www.w3.org/ns/org#>
        PREFIX gr: <http://purl.org/goodrelations/v1#>
        PREFIX skos: <http://www.w3.org/2004/02/skos/core#>

        SELECT DISTINCT ?id ?lat ?long ?label WHERE {
          ?fid <http://purl.org/openorg/hasFeature> ?f .
          ?f a <http://id.southampton.ac.uk/syllabus/feature/RSC-_WIRELESS_NETWORK> .
          ?fid spacerel:within ?id .
          ?id geo:lat ?lat . 
          ?id geo:long ?long .
          ?id skos:notation ?label .
          ?id a <http://vocab.deri.ie/rooms#Building> .
          FILTER ( BOUND(?long) && BOUND(?lat) )
        } ORDER BY ?label
		");
		$points = array();
		foreach($tpoints as $point)
		{
			$point['id'] = $point['id'].'#wifi';
			$point['label'] = 'Wi-Fi Internet Access Points in Building '.$point['label'];
			$point['icon'] = self::$iconpath.'Offices/wifi.png';
			$points[] = $point;
		}
		return $points;
	}

	static function getISolutionsWifiPoints($q)
	{
		return array();
		if($q == '')
			$filter = '';
		else
			$filter = "&& ( REGEX( ?label, '$q', 'i') || REGEX( ?poslabel, '$q', 'i') )";
		$tpoints =  sparql_get(self::$endpoint, "
	PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
	PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
	PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
	PREFIX org: <http://www.w3.org/ns/org#>
	PREFIX skos: <http://www.w3.org/2004/02/skos/core#>

	SELECT DISTINCT ?poslabel ?pos WHERE {
          ?fid <http://purl.org/openorg/hasFeature> ?f .
          ?f a <http://id.southampton.ac.uk/syllabus/feature/RSC-_WIRELESS_NETWORK> .
          ?fid spacerel:within ?pos .
          ?pos skos:notation ?poslabel .
          ?pos a <http://vocab.deri.ie/rooms#Building> .
        } ORDER BY ?poslabel
		");
		$points = array();
		foreach($tpoints as $point)
		{
			$point['pos'] = $point['pos'].'#wifi';
			$point['poslabel'] = 'Wi-Fi Internet Access Points in Building '.$point['poslabel'];
			$point['label'] = 'Wi-Fi Access';
			$point['icon'] = self::$iconpath.'Offices/wifi.png';
			$points[] = $point;
		}
		return $points;
	}

	static function getAllResidences()
	{
		return array();
		$tpoints = sparql_get(self::$endpoint, "
	PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
	PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
	PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
	PREFIX org: <http://www.w3.org/ns/org#>
	PREFIX gr: <http://purl.org/goodrelations/v1#>
	PREFIX skos: <http://www.w3.org/2004/02/skos/core#>

	SELECT DISTINCT ?id ?lat ?long ?label WHERE {
	  ?b rdfs:label ?label .
	  ?b <http://purl.org/openorg/hasFeature> ?id .
	  ?id a <http://id.southampton.ac.uk/ns/PlaceFeature-ResidentialUse> .
	  OPTIONAL { ?b geo:lat ?lat . 
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
			$point['icon'] = self::$iconpath.'Restaurants-and-Hotels/lodging_0star.png';
			$points[] = $point;
		}
		return $points;
	}

	static function getResidences($q)
	{
		return array();
		if($q == '')
			$filter = '';
		else
			$filter = "FILTER ( REGEX( ?label, '$q', 'i') || REGEX( ?poslabel, '$q', 'i') )";
		$tpoints =  sparql_get(self::$endpoint, "
	PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
	PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
	PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
	PREFIX org: <http://www.w3.org/ns/org#>
	PREFIX skos: <http://www.w3.org/2004/02/skos/core#>

	SELECT DISTINCT ?poslabel ?pos WHERE {
	  ?b rdfs:label ?poslabel .
	  ?b <http://purl.org/openorg/hasFeature> ?pos .
	  ?pos a <http://id.southampton.ac.uk/ns/PlaceFeature-ResidentialUse> .
	  $filter
	} ORDER BY ?poslabel
		");
		$points = array();
		foreach($tpoints as $point)
		{
			$point['label'] = 'Accommodation';
			$point['icon'] = self::$iconpath.'Restaurants-and-Hotels/lodging_0star.png';
			$points[] = $point;
		}
		return $points;
	}

	static function getAllShowers()
	{
		return array();
		$tpoints = sparql_get(self::$endpoint, "
	PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
	PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
	PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
	PREFIX org: <http://www.w3.org/ns/org#>
	PREFIX gr: <http://purl.org/goodrelations/v1#>
	PREFIX skos: <http://www.w3.org/2004/02/skos/core#>

	SELECT DISTINCT ?id ?lat ?long ?label WHERE {
	  ?id <http://purl.org/openorg/hasFeature> ?f .
	  ?f a <http://id.southampton.ac.uk/location-feature/Shower> .
	  ?f rdfs:label ?label .
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
	} ORDER BY ?label
		");
		$points = array();
		foreach($tpoints as $point)
		{
			$point['icon'] = self::$iconpath.'Offices/shower.png';
			$points[] = $point;
		}
		return $points;
	}

	static function getShowers($q)
	{
		return array();
		if($q == '')
			$filter = '';
		else
			$filter = "FILTER ( REGEX( ?label, '$q', 'i') || REGEX( ?poslabel, '$q', 'i') )";
		$tpoints =  sparql_get(self::$endpoint, "
	PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
	PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
	PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
	PREFIX org: <http://www.w3.org/ns/org#>
	PREFIX skos: <http://www.w3.org/2004/02/skos/core#>

	SELECT DISTINCT ?poslabel ?pos ?icon WHERE {
	  ?pos <http://purl.org/openorg/hasFeature> ?f .
	  ?f a <http://id.southampton.ac.uk/location-feature/Shower> .
	  ?f rdfs:label ?poslabel .
	  $filter
	} ORDER BY ?poslabel
		");
		$points = array();
		foreach($tpoints as $point)
		{
			$point['label'] = 'Shower';
			$point['icon'] = self::$iconpath.'Offices/shower.png';
			$points[] = $point;
		}
		return $points;
	}

	static function getBuildings($q, $qbd)
	{
		return sparql_get(self::$endpoint, "
	SELECT DISTINCT ?url ?name ?number WHERE {
	  ?url a <http://data.cs.tsinghua.edu.cn/ns/BuildingsAndPlaces> .
	  ?url <http://www.w3.org/2000/01/rdf-schema#label> ?name .
	  ?url <http://www.w3.org/2004/02/skos/core#notation> ?number .
	  FILTER ( REGEX( ?name, '$q', 'i') || REGEX( ?number, '$qbd', 'i') )
	} ORDER BY ?number
		");
	}
	
	static function getAllSites()
	{
		return array();
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
		  ?url a <http://data.cs.tsinghua.edu.cn/ns/BuildingsAndPlaces> .
		  OPTIONAL { ?url <http://purl.org/dc/terms/spatial> ?outline . }
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
		return array();
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

	/*
	// Process point of service data
	static function createPointOfServiceEntries(&$pos, &$label, &$type, &$url, &$icon, $q, $cats)
	{
		$data = self::getPointsOfService($q);
		foreach($data as $point) {
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

	// Process wifi data
	static function createISolutionsWifiPointEntries(&$pos, &$label, &$type, &$url, &$icon, $q, $cats)
	{
		$data = self::getISolutionsWifiPoints($q);
		foreach($data as $point) {
			$point['icon'] = self::$iconpath.'Offices/wifi.png';
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
				$type[$point['poslabel']] = "wifi";
				$url[$point['poslabel']] = $point['pos'];
				$icon[$point['poslabel']] = $point['icon'];
			}
		}
	}

	// Process shower data
	static function createShowerEntries(&$pos, &$label, &$type, &$url, &$icon, $q, $cats)
	{
		$data = self::getShowers($q);
		foreach($data as $point) {
			$point['icon'] = self::$iconpath.'Offices/shower.png';
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
	*/

	// Process building data
	static function createBuildingEntries(&$pos, &$label, &$type, &$url, &$icon, $q, $cats)
	{
		$qbd = trim(str_replace(array('building', 'buildin', 'buildi', 'build', 'buil', 'bui', 'bu', 'b'), '', strtolower($q)));
		$data = self::getBuildings($q, $qbd);
		foreach($data as $point) {
			$pos[$point['url']] += 100;
			if(preg_match('/'.$q.'/i', $point['name']))
			{
				if($point['number'] < 100)
					$label[$point['name']] += 1000;
				else
					$label[$point['name']] += 100;
				$type[$point['name']] = "building";
				$url[$point['name']] = $point['url'];
				$icon[$point['name']] = 'http://opendatamap.ecs.soton.ac.uk/resources/numbericon.php?n='.$point['number'];
			}
			if(preg_match('/^'.$qbd.'/i', $point['number']))
			{
				if($point['number'] < 100)
					$label['Building '.$point['number']] += 1000;
				else
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
			$pos[$point['url']] += 100000;
			$label[$point['name']] += 100000;
			if(preg_match('/Campus/i', $point['name']))
			{
				$pos[$point['url']] += 100000;
				$label[$point['name']] += 100000;
			}
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

		SELECT DISTINCT ?name ?icon ?type WHERE {
		    OPTIONAL { <$uri> rdfs:label ?name . }
		    OPTIONAL { <$uri> <http://purl.org/openorg/mapIcon> ?icon . }
		    OPTIONAL { <$uri> <http://purl.org/openorg/hasFeature> ?feature . 
		        OPTIONAL { ?feature a ?type . }
		        OPTIONAL { ?feature rdfs:label ?label . }
		    }
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
			if($allpos[0]['type'] == "http://id.southampton.ac.uk/location-feature/Shower")
			{
				$icon = self::$iconpath."Offices/shower.png";
			}
			else if(substr($uri, 0, 33) == "http://id.southampton.ac.uk/room/")
			{
				$icon = self::$iconpath."Education/computers.png";
				$computer = "true";
			}
			else
			{
				$icon = "";
			}
		}
		else
			$icon = $allpos[0]['icon'];

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

		self::processOffers($allpos);

		if(preg_match('/http:\/\/id\.southampton\.ac\.uk\/point-of-service\/parking-(.*)/', $uri, $matches))
		{
			echo "<iframe style='border:none' src='parking.php?uri=".$_GET['uri']."' />";
			echo "</div>";
			die();
		}

		$allopen = sparql_get(self::$endpoint, "
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

		self::processOpeningTimes($allopen);

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
