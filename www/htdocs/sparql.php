<?
include_once "inc/sparqllib.php";

$endpoint = "http://sparql.data.southampton.ac.uk";

function getAllDataPoints()
{
	$points = array();
	foreach(getAllPointsOfService()	 as $point) $points[] = $point;
	foreach(getAllBusStops()	 as $point) $points[] = $point;
	foreach(getAllWorkstationRooms() as $point) $points[] = $point;
//	foreach(getAllLibraries()        as $point) $points[] = $point;
//	foreach(getAllOxPoints()         as $point) $points[] = $point;
	return $points;
}

function getAllMatches($q, $cats)
{
	global $endpoint;
	
	$labellimit = 100;

	$pos = array();
	$label = array();
	$type = array();
	$url = array();
	$icon = array();

	createPostcodeEntries($label, $type, $url);
	createPointOfServiceEntries($pos, $label, $type, $url, $icon, $q, $cats);
	createBusEntries($pos, $label, $type, $url, $icon, $q, $cats);
	createWorkstationEntries($pos, $label, $type, $url, $icon, $q, $cats);
	createLibraryEntries($pos, $label, $type, $url, $icon, $q, $cats);
//	createOxPointEntries($pos, $label, $type, $url, $icon, $q, $cats);
//	createBuildingEntries($pos, $label, $type, $url, $icon, $q, $cats);
	createSiteEntries($pos, $label, $type, $url, $icon, $q, $cats);
	
	arsort($label);
	if(count($label) > $labellimit)
		$label = array_slice($label, 0,$labellimit);

	return array($pos, $label, $type, $url, $icon);
}

function getAllPointsOfService()
{
	global $endpoint;
	$tpoints = sparql_get($endpoint, "
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

function getPointsOfService($q)
{
	global $endpoint;
	if($q == '')
		$filter = '';
	else
		$filter = "FILTER ( REGEX( ?label, '$q', 'i') || REGEX( ?poslabel, '$q', 'i') )";
	return sparql_get($endpoint, "
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

function getAllBusStops()
{
	global $endpoint;
	$tpoints = sparql_get($endpoint, "
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

function getBusStops($q)
{
	global $endpoint;
	return sparql_get($endpoint, "
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

function getAllWorkstationRooms()
{
	global $endpoint;
	$tpoints = sparql_get($endpoint, "
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
		$point['icon'] = "http://opendatamap.ecs.soton.ac.uk/img/icon/Education/computer.png";
		$points[] = $point;
	}
	return $points;
}

function getAllOxPoints()
{
	$tpoints = sparql_get('http://oxpoints.oucs.ox.ac.uk/sparql', "
PREFIX oxp: <http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#>
PREFIX foaf: <http://xmlns.com/foaf/0.1/>
PREFIX dc: <http://purl.org/dc/elements/1.1/>
PREFIX dct: <http://purl.org/dc/terms/>
PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>

SELECT ?id ?lat ?long ?label ?type WHERE {
  ?id a ?type .
  ?id dc:title ?label .
    ?id oxp:occupies ?c .
    ?c geo:lat ?lat .
    ?c geo:long ?long .
}
	");
  //?id foaf:logo ?icon .
	$points = array();
	foreach($tpoints as $point)
	{
		//if($point['icon'] == '')
		switch($point['type'])
		{
			case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#Building':
			case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#Room':
			case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#Site':
				$point['icon'] = "http://opendatamap.ecs.soton.ac.uk/img/icon/Education/university.png";
				break;
			case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#Hall':
			case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#College':
				$point['icon'] = "http://opendatamap.ecs.soton.ac.uk/img/icon/Education/university.png";
				break;
			case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#Department':
			case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#Unit':
			case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#Division':
				$point['icon'] = "http://opendatamap.ecs.soton.ac.uk/img/icon/Education/school.png";
				break;
			case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#StudentGroup':
				$point['icon'] = "http://opendatamap.ecs.soton.ac.uk/img/icon/Education/library.png";
				break;
			case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#Carpark':
				$point['icon'] = "http://opendatamap.ecs.soton.ac.uk/img/icon/Transportation/parking.png";
				break;
			case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#OpenSpace':
				$point['icon'] = "http://opendatamap.ecs.soton.ac.uk/img/icon/Tourism/urbanpark.png";
				break;
			case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#Library':
			case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#SubLibrary':
				$point['icon'] = "http://opendatamap.ecs.soton.ac.uk/img/icon/Education/library.png";
				break;
			case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#Museum':
				$point['icon'] = "http://opendatamap.ecs.soton.ac.uk/img/icon/Culture-and-Entertainment/temple-2.png";
				break;
		}
		$points[] = $point;
	}
	return $points;
}

function getWorkstationRooms($q)
{
	global $endpoint;
	if($q == '')
		$filter = '';
	else
		$filter = "&& ( REGEX( ?label, '$q', 'i') || REGEX( ?poslabel, '$q', 'i') )";
	return sparql_get($endpoint, "
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

function getAllLibraries()
{
	//id, lat, long, label
	$libs = simplexml_load_file('camlib.xml');
	foreach($libs->library as $lib)
	{
		if($lib->lat == null || $lib->lng == null)
			continue;
		$point['id'] = 'http://www.lib.cam.ac.uk/#'.(string)$lib->code;
		$point['lat'] = (float)$lib->lat;
		$point['long'] = (float)$lib->lng;
		$point['label'] = (string)$lib->name;
		$point['icon'] = "http://opendatamap.ecs.soton.ac.uk/img/icon/Education/library.png";
		$points[] = $point;
	}
	return $points;
}

function getLibraries($q)
{
	//poslabel, label, pos, icon
	$libs = simplexml_load_file('camlib.xml');
	foreach($libs->library as $lib)
	{
		if($lib->lat == null || $lib->lng == null)
			continue;
		$point['poslabel'] = (string)$lib->name;
		$point['pos'] = 'http://www.lib.cam.ac.uk/#'.(string)$lib->code;
		$point['label'] = 'library';//(string)$lib->name;
		$point['icon'] = "http://opendatamap.ecs.soton.ac.uk/img/icon/Education/library.png";
		$points[] = $point;
	}
	return $points;
}

function getOxPoints($q)
{
	$tpoints = sparql_get('http://oxpoints.oucs.ox.ac.uk/sparql', "
PREFIX oxp: <http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#>
PREFIX foaf: <http://xmlns.com/foaf/0.1/>
PREFIX dc: <http://purl.org/dc/elements/1.1/>
PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>

SELECT ?pos ?poslabel WHERE {
  ?pos a ?type .
  ?pos dc:title ?label .
    ?pos oxp:occupies ?c .
    ?c geo:lat ?lat .
    ?c geo:long ?long .
    ?pos dc:title ?poslabel .
}
	");
	$points = array();
	foreach($tpoints as $point)
	{
		if(!preg_match('/'.$q.'/i', $point['label']) && !preg_match('/'.$q.'/i', $point['poslabel']))
			continue;
		$point['icon'] = "http://opendatamap.ecs.soton.ac.uk/img/icon/Education/computer.png";
		$point['label'] = 'point';
		$points[] = $point;
	}
	return $points;
}

function getBuildings($q, $qbd)
{
	global $endpoint;
	return sparql_get($endpoint, "
SELECT DISTINCT ?url ?name ?number WHERE {
  ?url a <http://vocab.deri.ie/rooms#Building> .
  ?url <http://www.w3.org/2000/01/rdf-schema#label> ?name .
  ?url <http://www.w3.org/2004/02/skos/core#notation> ?number .
  FILTER ( REGEX( ?name, '$q', 'i') || REGEX( ?number, '$qbd', 'i') )
} ORDER BY ?number
	");
}

function getSites($q)
{
	global $endpoint;
	return sparql_get($endpoint, "
SELECT DISTINCT ?url ?name WHERE {
  ?url a <http://www.w3.org/ns/org#Site> .
  ?url <http://www.w3.org/2000/01/rdf-schema#label> ?name .
  ?url <http://purl.org/dc/terms/spatial> ?outline .
  FILTER ( REGEX( ?name, '$q', 'i') )
} ORDER BY ?url
	");
}

function getPostcodeData($postcode)
{
	$data = sparql_get("http://api.talis.com/stores/ordnance-survey/services/sparql", "
SELECT ?p ?lat ?long ?wlabel ?dlabel WHERE {
	?p <http://www.w3.org/2000/01/rdf-schema#label> '$postcode' .
	?p <http://www.w3.org/2003/01/geo/wgs84_pos#lat> ?lat .
	?p <http://www.w3.org/2003/01/geo/wgs84_pos#long> ?long .
	?p <http://data.ordnancesurvey.co.uk/ontology/postcode/ward> ?w .
	?w <http://www.w3.org/2004/02/skos/core#prefLabel> ?wlabel .
	?p <http://data.ordnancesurvey.co.uk/ontology/postcode/district> ?d .
	?d <http://www.w3.org/2004/02/skos/core#prefLabel> ?dlabel .
}
	");
	if(count($data) == 1)
		return $data[0];
	else
		return null;
}

function visibleCategory($icon, $cats)
{
	global $iconcats;
	if($iconcats == null) include_once "inc/categories.php";
	return in_cat($iconcats, $icon, $cats);
}

function createPostcodeEntries(&$label, &$type, &$url)
{
	$postcodedata = array();
	$postcodefile = "resources/postcodetypes";
	$file = fopen($postcodefile, 'r');
	while($line = fgets($file))
	{
		$postcodedata[] = trim($line);
	}
	fclose($file);

	$fullq = strtoupper($_GET['q']);
	$fullqs = explode(' ', $fullq);

	if(count($fullqs) == 1 || (count($fullqs) == 2 && preg_match('/^([0-9]([A-Z][A-Z]?)?)?$/', $fullqs[1])))
	{
		if(in_array($fullqs[0], $postcodedata))
		{
			$postcode = $fullq.substr($fullqs[0]." ...", strlen($fullq));
			if(strpos($postcode, '.') === false)
			{
				$data = getPostcodeData($fullq);
				if($data != null)
				{
					$postcode =  $fullq.' '.$data['wlabel'].', '.$data['dlabel'];
					$url[$postcode] = 'postcode:'.$fullq.','.$data['lat'].','.$data['long'].','.$data['p'];
				}
				else
				{
					$postcode =  $fullq.' (postcode not found)';
					$url[$postcode] = null;
				}
			}
			$label[$postcode] = 99;
			$type[$postcode] = "postcode";
		}
		if(strpos($fullq, ' ') === false && in_array($fullqs[0].'?', $postcodedata))
		{
			$postcode = $fullq.substr($fullqs[0].". ...", strlen($fullq));
			$label[$postcode] = 100;
			$type[$postcode] = "postcode";
		}
	}
}

// Process point of service data
function createPointOfServiceEntries(&$pos, &$label, &$type, &$url, &$icon, $q, $cats)
{
	$data = getPointsOfService($q);
	foreach($data as $point) {
		if(!visibleCategory($point['icon'], $cats))
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
function createBusEntries(&$pos, &$label, &$type, &$url, &$icon, $q, $cats)
{
	$data = getBusStops($q);
	foreach($data as $point) {
		if(!visibleCategory($point['icon'], $cats))
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
function createWorkstationEntries(&$pos, &$label, &$type, &$url, &$icon, $q, $cats)
{
	$data = getWorkstationRooms($q);
	foreach($data as $point) {
		$point['icon'] = 'http://opendatamap.ecs.soton.ac.uk/img/icon/Education/computer.png';
		if(!visibleCategory($point['icon'], $cats))
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

// Process library data
function createLibraryEntries(&$pos, &$label, &$type, &$url, &$icon, $q, $cats)
{
	$data = getLibraries($q);
	foreach($data as $point) {
		$point['icon'] = 'http://opendatamap.ecs.soton.ac.uk/img/icon/Education/library.png';
		if(!visibleCategory($point['icon'], $cats))
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

// Process glasto data
function createOxPointEntries(&$pos, &$label, &$type, &$url, &$icon, $q, $cats)
{
	$data = getOxPoints($q);
	foreach($data as $point) {
		if(!visibleCategory($point['icon'], $cats))
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
function createBuildingEntries(&$pos, &$label, &$type, &$url, &$icon, $q, $cats)
{
	$qbd = trim(str_replace(array('building', 'buildin', 'buildi', 'build', 'buil', 'bui', 'bu', 'b'), '', strtolower($q)));
	$data = getBuildings($q, $qbd);
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
function createSiteEntries(&$pos, &$label, &$type, &$url, &$icon, $q, $cats)
{
	$data = getSites($q);
	foreach($data as $point) {
		$pos[$point['url']] += 1000;
		$label[$point['name']] += 1000;
		$type[$point['name']] = "site";
		$url[$point['name']] = $point['url'];
		$icon[$point['name']] = 'http://opendatamap.ecs.soton.ac.uk/resources/numbericon.php?n='.substr($point['name'], 0, 1);
	}
}

?>
