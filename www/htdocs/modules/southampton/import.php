<?
include_once "inc/sparqllib.php";
include_once "inc/icons.php";

class SouthamptonDataSource
{
	static $endpoint = 'http://sparql.data.southampton.ac.uk';
	static $iconpath = 'http://data.southampton.ac.uk/map-icons/';

	static function getAll()
	{
		$points = array();
		foreach(self::_getAllPointsOfService()		as $point) $points[] = $point;
		foreach(self::_getAllBusStops()	 		as $point) $points[] = $point;
		foreach(self::_getAllWorkstationRooms()		as $point) $points[] = $point;
		foreach(self::_getAllISolutionsWifiPoints()	as $point) $points[] = $point;
		foreach(self::_getAllShowers()			as $point) $points[] = $point;
		foreach(self::_getAllEvents()			as $point) $points[] = $point;
		return $points;
	}
	
	static function getAllOfferings()
	{
		$points = array();
		foreach(self::_getAllPointOfServiceOfferings()		as $point) $points[] = $point;
		foreach(self::_getAllBusStopOfferings()	 		as $point) $points[] = $point;
		foreach(self::_getAllWorkstationRoomOfferings()		as $point) $points[] = $point;
		foreach(self::_getAllISolutionsWifiPointOfferings()	as $point) $points[] = $point;
		foreach(self::_getAllShowerOfferings()			as $point) $points[] = $point;
		foreach(self::_getAllEventOfferings()			as $point) $points[] = $point;
		return $points;
	}

	static function getAllPlaces()
	{
		$points = array();
		foreach(self::_getAllSites()		as $point) $points[] = $point;
		foreach(self::_getAllBuildings()	as $point) $points[] = $point;
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
		return array("Contains Ordnance Survey data &copy; Crown copyright and database right 2011.");
	}

	private static function _getAllPointsOfService()
	{
		$tpoints = sparql_get(self::$endpoint, "
		PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
		PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
		PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
		PREFIX org: <http://www.w3.org/ns/org#>
		PREFIX gr: <http://purl.org/goodrelations/v1#>
		
		SELECT DISTINCT ?id ?lat ?lng ?label ?icon WHERE {
		  ?id a gr:LocationOfSalesOrServiceProvisioning .
		  ?id rdfs:label ?label .
		  OPTIONAL { ?id spacerel:within ?b .
		             ?b geo:lat ?lat . 
		             ?b geo:long ?lng .
		             ?b a <http://vocab.deri.ie/rooms#Building> .
		           }
		  OPTIONAL { ?id spacerel:within ?s .
		             ?s geo:lat ?lat . 
		             ?s geo:long ?lng .
		             ?s a org:Site .
		           }
		  OPTIONAL { ?id geo:lat ?lat .
		             ?id geo:long ?lng .
		           }
		  OPTIONAL { ?id <http://purl.org/openorg/mapIcon> ?icon . }
		  FILTER ( BOUND(?lng) && BOUND(?lat) )
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

	private static function _getAllPointOfServiceOfferings()
	{
		$tpoints = sparql_get(self::$endpoint, "
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
		} ORDER BY ?poslabel
		");
		$points = array();
		foreach($tpoints as $point)
		{
			$point['type'] = 'point-of-service';
			$points[] = $point;
		}
		return $points;
	}

	private static function _getAllBusStops()
	{
		$tpoints = sparql_get(self::$endpoint, "
		PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
		PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
		PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
		PREFIX org: <http://www.w3.org/ns/org#>
		
		SELECT ?id ?lat ?lng ?label (GROUP_CONCAT(?code) as ?codes) WHERE {
		  ?rstop <http://id.southampton.ac.uk/ns/inBusRoute> ?route .
		  ?rstop <http://id.southampton.ac.uk/ns/busStoppingAt> ?id .
		  ?route <http://www.w3.org/2004/02/skos/core#notation> ?code .
		  ?id rdfs:label ?label .
		  ?id geo:lat ?lat .
		  ?id geo:long ?lng .
		} GROUP BY ?id ?label ?lat ?lng ORDER BY ?label
		");
		$points = array();
		foreach($tpoints as $point)
		{
			$codes = implode('+', reduceBusCodes(explode(' ', $point['codes'])));
			$point['icon'] = "http://opendatamap.ecs.soton.ac.uk/resources/busicon/".$codes;
			$points[] = $point;
		}
		return $points;
	}

	private static function _getAllBusStopOfferings()
	{
		$tpoints = sparql_get(self::$endpoint, "
		SELECT DISTINCT ?poslabel ?label ?pos ?icon WHERE {
		  ?rstop <http://id.southampton.ac.uk/ns/inBusRoute> ?route .
		  ?rstop <http://id.southampton.ac.uk/ns/busStoppingAt> ?pos .
		  ?route <http://www.w3.org/2004/02/skos/core#notation> ?label .
		  ?pos <http://www.w3.org/2000/01/rdf-schema#label> ?poslabel .
		  ?pos <http://purl.org/openorg/mapIcon> ?icon .
		  FILTER ( REGEX( ?label, '^U', 'i') )
		} ORDER BY ?poslabel
		");
		$points = array();
		foreach($tpoints as $point)
		{
			$point['type'] = 'bus-stop';
			$points[] = $point;
		}
		return $points;
	}

	private static function _getAllWorkstationRooms()
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
		
		SELECT DISTINCT ?id ?label ?lat ?lng ?icon ?seats ?freeseats WHERE {
		  ?offering gr:includes <http://id.southampton.ac.uk/generic-products-and-services/iSolutionsWorkstations> .
		  ?offering <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> gr:Offering .
		  ?offering gr:availableAtOrFrom ?id .
		  ?id rdfs:label ?label .
		  ?id soton:workstationSeats ?seats .
		  ?id soton:workstationFreeSeats ?freeseats .
		  ?id oo:mapIcon ?icon .
		  OPTIONAL { ?id spacerel:within ?b .
		             ?b geo:lat ?lat . 
		             ?b geo:long ?lng .
		             ?b a <http://vocab.deri.ie/rooms#Building> .
		           }
		  OPTIONAL { ?id spacerel:within ?s .
		             ?s geo:lat ?lat . 
		             ?s geo:long ?lng .
		             ?s a org:Site .
		           }
		  OPTIONAL { ?id geo:lat ?lat .
		             ?id geo:long ?lng .
		           }
		} ORDER BY ?label
		");
		$points = array();
		foreach($tpoints as $point)
		{
			$point['icon'] = "http://opendatamap.ecs.soton.ac.uk/resources/workstationicon.php?pos=".$point['id'];
			$points[] = $point;
		}
		return $points;
	}

	private static function _getAllWorkstationRoomOfferings()
	{
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
		  FILTER ( REGEX(?label, '^(WORKSTATION|SOFTWARE) -') )
		} ORDER BY ?poslabel
		");
		$points = array();
		foreach($tpoints as $point)
		{
			$point['icon'] = "http://opendatamap.ecs.soton.ac.uk/resources/workstationicon.php?pos=".$point['pos'];
			$point['type'] = 'workstation';
			$points[] = $point;
		}
		return $points;
	}

	private static function _getAllISolutionsWifiPoints()
	{
		$tpoints = sparql_get(self::$endpoint, "
		PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
		PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
		
		SELECT ?id ?lat ?lng ?label WHERE {
		  GRAPH <http://id.southampton.ac.uk/dataset/wifi/latest> {
		    ?id geo:lat ?lat .
		    ?id geo:long ?lng .
		    ?id rdfs:label ?label .
		  }
		}
		");
		$points = array();
		foreach($tpoints as $point)
		{
			$point['icon'] = self::$iconpath.'Offices/wifi.png';
			$points[] = $point;
		}
		return $points;
	}

	private static function _getAllISolutionsWifiPointOfferings()
	{
		$tpoints =  sparql_get(self::$endpoint, "
		SELECT ?pos ?poslabel WHERE {
		  GRAPH <http://id.southampton.ac.uk/dataset/wifi/latest> {
		    ?pos <http://www.w3.org/2000/01/rdf-schema#label> ?poslabel .
		  }
		}
		");
		$points = array();
		foreach($tpoints as $point)
		{
			$point['label'] = 'Wi-Fi Access';
			$point['label'] = 'iSolutions Wi-Fi';
			$point['icon'] = self::$iconpath.'Offices/wifi.png';
			$point['type'] = 'wifi';
			$points[] = $point;
		}
		return $points;
	}

	private static function _getAllShowers()
	{
		$tpoints = sparql_get(self::$endpoint, "
		PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
		PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
		PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
		PREFIX org: <http://www.w3.org/ns/org#>
		PREFIX gr: <http://purl.org/goodrelations/v1#>
		PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
		
		SELECT DISTINCT ?id ?lat ?lng ?label WHERE {
		  ?id <http://purl.org/openorg/hasFeature> ?f .
		  ?f a <http://id.southampton.ac.uk/location-feature/Shower> .
		  ?f rdfs:label ?label .
		  OPTIONAL { ?id spacerel:within ?b .
		             ?b geo:lat ?lat . 
		             ?b geo:long ?lng .
		             ?b a <http://vocab.deri.ie/rooms#Building> .
		           }
		  OPTIONAL { ?id spacerel:within ?s .
		             ?s geo:lat ?lat . 
		             ?s geo:long ?lng .
		             ?s a org:Site .
		           }
		  OPTIONAL { ?id geo:lat ?lat .
		             ?id geo:long ?lng .
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

	private static function _getAllShowerOfferings()
	{
		$tpoints =  sparql_get(self::$endpoint, "
		SELECT DISTINCT ?poslabel ?pos ?icon WHERE {
		  ?pos <http://purl.org/openorg/hasFeature> ?f .
		  ?f a <http://id.southampton.ac.uk/location-feature/Shower> .
		  ?f <http://www.w3.org/2000/01/rdf-schema#label> ?poslabel .
		} ORDER BY ?poslabel
		");
		$points = array();
		foreach($tpoints as $point)
		{
			$point['label'] = 'Shower';
			$point['icon'] = self::$iconpath.'Offices/shower.png';
			$point['type'] = 'shower';
			$points[] = $point;
		}
		return $points;
	}

	private static function _getAllEvents()
	{
		$tpoints = sparql_get(self::$endpoint, "
		PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
		PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
		PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
		PREFIX org: <http://www.w3.org/ns/org#>
		PREFIX gr: <http://purl.org/goodrelations/v1#>
		
		SELECT DISTINCT ?id ?lat ?lng ?label ?ts ?te WHERE {
		  GRAPH ?g {
                    ?id a <http://purl.org/NET/c4dm/event.owl#Event> .
		  }
		  ?id <http://purl.org/NET/c4dm/event.owl#time> ?t .
		  ?t <http://purl.org/NET/c4dm/timeline.owl#start> ?ts .
		  OPTIONAL { ?t <http://purl.org/NET/c4dm/timeline.owl#end> ?te . }
                  ?id <http://purl.org/NET/c4dm/event.owl#place> ?p .
                  ?id rdfs:label ?label .
		  OPTIONAL { ?p spacerel:within ?b .
		             ?b geo:lat ?lat . 
		             ?b geo:long ?lng .
		             ?b a <http://vocab.deri.ie/rooms#Building> .
		           }
		  OPTIONAL { ?p spacerel:within ?s .
		             ?s geo:lat ?lat . 
		             ?s geo:long ?lng .
		             ?s a org:Site .
		           }
		  OPTIONAL { ?p geo:lat ?lat .
		             ?p geo:long ?lng .
		           }
		  FILTER ( BOUND(?lng) && BOUND(?lat) && 
		           ( ?g = <http://id.southampton.ac.uk/dataset/events-diary/latest> ||
		             ?g = <http://id.southampton.ac.uk/dataset/susu-events/latest> )
		         )
		} ORDER BY ?label
		");
		$points = array();
		foreach($tpoints as $point)
		{
			$dates = static::_checkDates($point);
			if(!$dates) continue;
			$point['label'] = str_replace('\'', '\\\'', $point['label']);
			$point['label'] = str_replace("\\", "\\\\", $point['label']);
			$point['extra'] = '('.$dates.')';
			$point['type'] = 'event';
			$point['icon'] = "http://opendatamap.ecs.soton.ac.uk/img/icon/Media/calendar-3.png";
			$points[] = $point;
		}
		return $points;
	}
	
	private static function _getAllEventOfferings()
	{
		$tpoints = sparql_get(self::$endpoint, "
		PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
		PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
		PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
		PREFIX org: <http://www.w3.org/ns/org#>
		
		SELECT DISTINCT ?poslabel ?pos ?ts ?te WHERE {
		  GRAPH ?g {
		    ?pos a <http://purl.org/NET/c4dm/event.owl#Event> .
		  }
		  ?pos <http://purl.org/NET/c4dm/event.owl#time> ?t .
		  ?t <http://purl.org/NET/c4dm/timeline.owl#start> ?ts .
		  OPTIONAL { ?t <http://purl.org/NET/c4dm/timeline.owl#end> ?te . }
		  ?pos rdfs:label ?poslabel .
                  ?pos <http://purl.org/NET/c4dm/event.owl#place> ?p .
		  OPTIONAL { ?p spacerel:within ?b .
		             ?b geo:lat ?lat . 
		             ?b geo:long ?lng .
		             ?b a <http://vocab.deri.ie/rooms#Building> .
		           }
		  OPTIONAL { ?p spacerel:within ?s .
		             ?s geo:lat ?lat . 
		             ?s geo:long ?lng .
		             ?s a org:Site .
		           }
		  OPTIONAL { ?p geo:lat ?lat .
		             ?p geo:long ?lng .
		           }
		  FILTER ( BOUND(?lng) && BOUND(?lat) && 
		           ( ?g = <http://id.southampton.ac.uk/dataset/events-diary/latest> ||
		             ?g = <http://id.southampton.ac.uk/dataset/susu-events/latest> )
		         )
		} ORDER BY ?poslabel
		");
		$points = array();
		foreach($tpoints as $point)
		{
			$dates = static::_checkDates($point);
			if(!$dates) continue;
			//$point['poslabel'] .= ' ' . $dates;
			$point['type'] = 'event';
			$point['icon'] = "http://opendatamap.ecs.soton.ac.uk/img/icon/Media/calendar-3.png";
			$points[] = $point;
		}
		return $points;
	}

	private static function _checkDates($point)
	{
		date_default_timezone_set('Europe/London');
		try
		{
			$n = new DateTime();
			$ts = new DateTime($point['ts']);
			if(isset($point['te']))
			{
				$te = new DateTime($point['te']);
				if($te < $n || $ts > $n->add(new DateInterval('P1M')))
				{
					return false;
				}
				if($ts->format('m/d') == $te->format('m/d'))
				{
					return $ts->format('jS M H:i') . ' - ' . $te->format('H:i');
				}
				else
				{
					return $ts->format('jS M H:i') . ' - ' . $te->format('jS M H:i');
				}
			}
			else
			{
				if($ts < $n || $ts > $n->add(new DateInterval('P1M')))
				{
					return false;
				}
				return $ts->format('jS M H:i');
			}
		}
		catch(Exception $ex)
		{
			if(isset($point['id'])) echo $point['id'];
			if(isset($point['pos'])) echo $point['pos'];
			echo $ex;
			return false;
		}
		return '';
	}

	private static function _getAllBuildings()
	{
		$tpoints = sparql_get(self::$endpoint, "
		SELECT DISTINCT ?uri ?name ?outline ?lat ?lng ?hfeature ?lfeature ?num WHERE {
		  ?uri a <http://id.southampton.ac.uk/ns/UoSBuilding> .
		  OPTIONAL { ?uri <http://purl.org/dc/terms/spatial> ?outline . }
		  ?uri <http://www.w3.org/2003/01/geo/wgs84_pos#lat> ?lat .
		  ?uri <http://www.w3.org/2003/01/geo/wgs84_pos#long> ?lng .
		  ?uri <http://www.w3.org/2000/01/rdf-schema#label> ?name .
		  OPTIONAL { ?uri <http://www.w3.org/2004/02/skos/core#notation> ?num . }
		} ORDER BY ?num
		");
		$points = array();
		foreach($tpoints as $point)
		{
			$point['type'] = 'building';
			$points[] = $point;
		}
		return $points;
	}
	
	private static function _getAllSites()
	{
		$tpoints = sparql_get(self::$endpoint, "
		SELECT DISTINCT ?uri ?name ?outline WHERE {
		  ?uri a <http://www.w3.org/ns/org#Site> .
		  ?uri <http://purl.org/dc/terms/spatial> ?outline .
		  ?uri <http://www.w3.org/2000/01/rdf-schema#label> ?name .
		} ORDER BY ?uri
		");
		$points = array();
		foreach($tpoints as $point)
		{
			$point['type'] = 'site';
			$points[] = $point;
		}
		return $points;
	}
}
?>

