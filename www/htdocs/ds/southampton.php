<?
include_once "inc/sparqllib.php";

class SouthamptonDataSource extends DataSource
{
	static $endpoint = 'http://sparql.data.southampton.ac.uk';

	static function getAll()
	{
		$points = array();
		foreach(self::getAllPointsOfService()		as $point) $points[] = $point;
		foreach(self::getAllBusStops()	 		as $point) $points[] = $point;
		foreach(self::getAllWorkstationRooms()		as $point) $points[] = $point;
		foreach(self::getAllISolutionsWifiPoints()	as $point) $points[] = $point;
		foreach(self::getAllResidences()		as $point) $points[] = $point;
		foreach(self::getAllShowers()			as $point) $points[] = $point;
		return $points;
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
			if(!isset($point['icon']) || $point['icon'] == "")
			{
				$point['icon'] = "img/blackness.png";
			}
			if($point['icon'] == self::$iconpath.'Education/computers.png')
			{
				$point['icon'] = "http://opendatamap.ecs.soton.ac.uk/resources/workstationicon.php?pos=".$point['id'];
			}
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
	PREFIX soton: <http://id.southampton.ac.uk/ns/>
	PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
	PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
	PREFIX org: <http://www.w3.org/ns/org#>
	PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
	PREFIX gr: <http://purl.org/goodrelations/v1#>
	PREFIX oo: <http://purl.org/openorg/>

	SELECT DISTINCT ?id ?label ?lat ?long ?icon ?seats ?freeseats WHERE {
	  ?offering gr:includes <http://id.southampton.ac.uk/generic-products-and-services/iSolutionsWorkstations> .
	  ?offering <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> gr:Offering .
	  ?offering gr:availableAtOrFrom ?id .
	  ?id rdfs:label ?label .
	  ?id soton:workstationSeats ?seats .
	  ?id soton:workstationFreeSeats ?freeseats .
	  ?id oo:mapIcon ?icon .
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
	} ORDER BY ?label
		");
		$points = array();
		foreach($tpoints as $point)
		{
			//$point['icon'] = self::$iconpath.'Education/computers.png';
			$point['icon'] = "http://opendatamap.ecs.soton.ac.uk/resources/workstationicon.php?pos=".$point['id'];
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
		$tpoints =  sparql_get(self::$endpoint, "
        PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
        PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
        PREFIX gr: <http://purl.org/goodrelations/v1#>
        PREFIX oo: <http://purl.org/openorg/>

        SELECT DISTINCT ?poslabel ?label ?pos ?icon WHERE {
          ?offering gr:includes <http://id.southampton.ac.uk/generic-products-and-services/iSolutionsWorkstations> .
          ?offering <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> gr:Offering .
          ?offering gr:availableAtOrFrom ?pos .
          ?pos rdfs:label ?poslabel .
          ?pos oo:mapIcon ?icon .
          ?pos spacerel:within ?s .
          ?s a <http://vocab.deri.ie/rooms#Room> .
          ?s oo:hasFeature ?f .
          ?f a ?ft .
          ?ft rdfs:label ?label .
          FILTER ( REGEX(?label, '^(WORKSTATION|SOFTWARE) -') $filter)
        } ORDER BY ?poslabel
		");
		$points = array();
		foreach($tpoints as $point)
		{
			//$point['icon'] = self::$iconpath.'Education/computers.png';
			$point['icon'] = "http://opendatamap.ecs.soton.ac.uk/resources/workstationicon.php?pos=".$point['pos'];
			$points[] = $point;
		}
		return $points;
	}

	static function getAllISolutionsWifiPoints()
	{
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
	  ?url a <http://id.southampton.ac.uk/ns/UoSBuilding> .
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
		  ?url a <http://id.southampton.ac.uk/ns/UoSBuilding> .
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
