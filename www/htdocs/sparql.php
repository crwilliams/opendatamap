<?
include_once "inc/sparqllib.php";

$endpoint = "http://sparql.data.southampton.ac.uk";

function getAllDataPoints()
{
	$points = array();
	$points = array_merge($points, getAllPointsOfService());
	$points = array_merge($points, getAllBusStops());
	$points = array_merge($points, getAllWorkstationRooms());
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
	createPointOfServiceEntries($pos, $label, $type, $url, $icon, $q);
	createBusEntries($pos, $label, $type, $url, $icon, $q);
	createWorkstationEntries($pos, $label, $type, $url, $icon, $q);
	createBuildingEntries($pos, $label, $type, $url, $icon, $q);
	createSiteEntries($pos, $label, $type, $url, $icon, $q);
	
	arsort($label);
	if(count($label) > $labellimit)
		$label = array_slice($label, 0,$labellimit);

	return array($pos, $label, $type, $url, $icon);
}

function getAllPointsOfService()
{
	global $endpoint;
	$points = sparql_get($endpoint, "
PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
PREFIX org: <http://www.w3.org/ns/org#>
PREFIX gr: <http://purl.org/goodrelations/v1#>

SELECT DISTINCT ?pos ?lat ?long ?poslabel ?icon WHERE {
  ?pos a gr:LocationOfSalesOrServiceProvisioning .
  ?pos rdfs:label ?poslabel .
  OPTIONAL { ?pos spacerel:within ?b .
             ?b geo:lat ?lat . 
             ?b geo:long ?long .
             ?b a <http://vocab.deri.ie/rooms#Building> .
           }
  OPTIONAL { ?pos spacerel:within ?s .
             ?s geo:lat ?lat . 
             ?s geo:long ?long .
             ?s a org:Site .
           }
  OPTIONAL { ?pos geo:lat ?lat .
             ?pos geo:long ?long .
           }
  OPTIONAL { ?pos <http://purl.org/openorg/mapIcon> ?icon . }
  FILTER ( BOUND(?long) && BOUND(?lat) )
} ORDER BY ?poslabel
	");
	for($i = 0; $i < count($points); $i++)
	{
		$points[$i]['poslabel'] = str_replace('\'', '\\\'', $points[$i]['poslabel']);
		$points[$i]['icon'] = str_replace("http://google-maps-icons.googlecode.com/files/", "http://opendatamap.ecs.soton.ac.uk/img/icon/", $points[$i]['icon']);
		$points[$i]['icon'] = str_replace("http://data.southampton.ac.uk/map-icons/lattes.png", "http://opendatamap.ecs.soton.ac.uk/img/icon/coffee.png", $points[$i]['icon']);
		if($points[$i]['icon'] == "")
			$points[$i]['icon'] = "img/blackness.png";
		$points[$i]['poslabel'] = str_replace("\\", "\\\\", $points[$i]['poslabel']);
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
	$points = sparql_get($endpoint, "
PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
PREFIX org: <http://www.w3.org/ns/org#>

SELECT ?pos ?poslabel ?lat ?long (GROUP_CONCAT(?code) as ?codes) {
  ?rstop <http://id.southampton.ac.uk/ns/inBusRoute> ?route .
  ?rstop <http://id.southampton.ac.uk/ns/busStoppingAt> ?pos .
  ?route <http://www.w3.org/2004/02/skos/core#notation> ?code .
  ?pos rdfs:label ?poslabel .
  ?pos geo:lat ?lat .
  ?pos geo:long ?long .
  FILTER ( REGEX( ?code, '^U', 'i') )
} GROUP BY ?pos ?poslabel ?lat ?long ORDER BY ?poslabel
	");
	for($i = 0; $i < count($points); $i++)
	{
		$codes = explode(' ', $points[$i]['codes']);
		sort($codes);
		$codes = array_unique($codes);
		$codes = implode('/', $codes);
		$points[$i]['icon'] = "http://opendatamap.ecs.soton.ac.uk/resources/busicon.php?r=".$codes;
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
	return sparql_get($endpoint, "
PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
PREFIX org: <http://www.w3.org/ns/org#>
PREFIX gr: <http://purl.org/goodrelations/v1#>
PREFIX skos: <http://www.w3.org/2004/02/skos/core#>

SELECT DISTINCT ?pos ?lat ?long ?poslabel WHERE {
  ?pos <http://purl.org/openorg/hasFeature> ?f .
  ?f a ?ft .
  ?ft rdfs:label ?ftl .
  ?pos skos:notation ?poslabel .
  OPTIONAL { ?pos spacerel:within ?b .
             ?b geo:lat ?lat . 
             ?b geo:long ?long .
             ?b a <http://vocab.deri.ie/rooms#Building> .
           }
  OPTIONAL { ?pos spacerel:within ?s .
             ?s geo:lat ?lat . 
             ?s geo:long ?long .
             ?s a org:Site .
           }
  OPTIONAL { ?pos geo:lat ?lat .
             ?pos geo:long ?long .
           }
  OPTIONAL { ?pos <http://purl.org/openorg/mapIcon> ?icon . }
  FILTER ( BOUND(?long) && BOUND(?lat) && REGEX(?ftl, '^WORKSTATION -') )
} ORDER BY ?poslabel
	");
	for($i = 0; $i < count($points); $i++)
	{
		$points[$i]['icon'] = "http://opendatamap.ecs.soton.ac.uk/img/icon/computer.png";
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

function visibleCategory($icon)
{
	include_once "inc/categories.php";
	global $iconcats;
	global $cats;
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
function createPointOfServiceEntries(&$pos, &$label, &$type, &$url, &$icon, $q)
{
	$data = getPointsOfService($q);
	foreach($data as $point) {
		if(!visibleCategory($point['icon']))
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
function createBusEntries(&$pos, &$label, &$type, &$url, &$icon, $q)
{
	$data = getBusStops($q);
	foreach($data as $point) {
		if(!visibleCategory($point['icon']))
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
function createWorkstationEntries(&$pos, &$label, &$type, &$url, &$icon, $q)
{
	$data = getWorkstationRooms($q);
	foreach($data as $point) {
		$point['icon'] = 'http://opendatamap.ecs.soton.ac.uk/img/icon/computer.png';
		if(!visibleCategory($point['icon']))
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
function createBuildingEntries(&$pos, &$label, &$type, &$url, &$icon, $q)
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
function createSiteEntries(&$pos, &$label, &$type, &$url, &$icon, $q)
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