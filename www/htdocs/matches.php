<?php
error_reporting(0);
include_once "inc/sparqllib.php";
include_once "inc/categories.php";

$q = trim($_GET['q']);
$q = str_replace("\\", "\\\\\\\\\\\\\\", $q);
if($_GET['ec'] == "")
{
	$cats = array('Transport','Catering','Services','Entertainment', 'Health', 'Religion', 'Retail', 'Education', 'General');
}
else
{
	$cats = explode(',', $_GET['ec']);
}

$endpoint = "http://sparql.data.southampton.ac.uk";

if($q == '')
{
	$filter = "";
	$addfilter = "";
}
else
{
	$filter = "FILTER ( REGEX( ?label, '$q', 'i') || REGEX( ?poslabel, '$q', 'i') )";
	$addfilter = " && ( REGEX( ?label, '$q', 'i') || REGEX( ?poslabel, '$q', 'i') )";
}
$data = sparql_get($endpoint, "
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
$busdata = sparql_get($endpoint, "
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
$clsdata = sparql_get($endpoint, "
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
  FILTER ( REGEX(?label, '^(WORKSTATION|SOFTWARE) -') $addfilter)
} ORDER BY ?poslabel
");
$qbd = trim(str_replace(array('building', 'buildin', 'buildi', 'build', 'buil', 'bui', 'bu', 'b'), '', strtolower($q)));
$buildingdata = sparql_get($endpoint, "
SELECT DISTINCT ?url ?name ?number WHERE {
  ?url a <http://vocab.deri.ie/rooms#Building> .
  ?url <http://www.w3.org/2000/01/rdf-schema#label> ?name .
  ?url <http://www.w3.org/2004/02/skos/core#notation> ?number .
  FILTER ( REGEX( ?name, '$q', 'i') || REGEX( ?number, '$qbd', 'i') )
} ORDER BY ?number
");
$sitedata = sparql_get($endpoint, "
SELECT DISTINCT ?url ?name WHERE {
  ?url a <http://www.w3.org/ns/org#Site> .
  ?url <http://www.w3.org/2000/01/rdf-schema#label> ?name .
  ?url <http://purl.org/dc/terms/spatial> ?outline .
  FILTER ( REGEX( ?name, '$q', 'i') )
} ORDER BY ?url
");


$pos = array();
$label = array();

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

foreach($data as $point) {
	if(!in_cat($iconcats, $point['icon'], $cats))
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
foreach($busdata as $point) {
	if(!in_cat($iconcats, $point['icon'], $cats))
		continue;
	$pos[$point['pos']] ++;
	if(preg_match('/'.$q.'/i', $point['label']))
		$label[$point['label']] ++;
		$type[$point['label']] = "bus-route";
	if(preg_match('/'.$q.'/i', $point['poslabel']))
	{
		$label[$point['poslabel']] += 10;
		$type[$point['poslabel']] = "bus-stop";
		$url[$point['poslabel']] = $point['pos'];
		$icon[$point['poslabel']] = $point['icon'];
	}
}
foreach($clsdata as $point) {
	$point['icon'] = 'http://opendatamap.ecs.soton.ac.uk/img/icon/computer.png';
	if(!in_cat($iconcats, $point['icon'], $cats))
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
foreach($buildingdata as $point) {
	$pos[$point['url']] += 100;
	if(preg_match('/'.$q.'/i', $point['name']))
	{
		$label[$point['name']] += 100;
		$type[$point['name']] = "building";
		$url[$point['name']] = $point['url'];
		if($point['number'] === substr($point['number'], 0, 2))
			$icon[$point['name']] = 'http://google-maps-icons.googlecode.com/files/black'.str_pad($point['number'], 2, 0, STR_PAD_LEFT).'.png';
		else
			$icon[$point['name']] = 'http://google-maps-icons.googlecode.com/files/black00.png';
	}
	if(preg_match('/'.$qbd.'/i', $point['number']))
	{
		$label['Building '.$point['number']] += 100;
		$type['Building '.$point['number']] = "building";
		$url['Building '.$point['number']] = $point['url'];
		if($point['number'] === substr($point['number'], 0, 2))
			$icon['Building '.$point['number']] = 'http://google-maps-icons.googlecode.com/files/black'.str_pad($point['number'], 2, 0, STR_PAD_LEFT).'.png';
		else
			$icon['Building '.$point['number']] = 'http://google-maps-icons.googlecode.com/files/black00.png';
	}
}
foreach($sitedata as $point) {
	$pos[$point['url']] += 1000;
	$label[$point['name']] += 1000;
	$type[$point['name']] = "site";
	$url[$point['name']] = $point['url'];
	$icon[$point['name']] = 'http://google-maps-icons.googlecode.com/files/black'.strtoupper(substr($point['name'], 0, 1)).'.png';
}
arsort($label);
$limit = 100;
if(count($label) > 100)
	$label = array_slice($label, 0, 100);
echo '[';
echo '[';
foreach (array_keys($pos) as $x)
	echo '"'.$x.'",';
echo '[]],';
echo '[';
foreach (array_keys($label) as $x)
{
	if($type[$x] == 'postcode' && preg_match('/[A-Z]([A-Z][0-9][0-9]?)|([0-9][A-Z]) [0-9][A-Z][A-Z]/', $x))
	{
		$postcodedata = sparql_get("http://api.talis.com/stores/ordnance-survey/services/sparql", "
SELECT ?lat ?long ?wlabel ?dlabel WHERE {
	?p <http://www.w3.org/2000/01/rdf-schema#label> '$x' .
	?p <http://www.w3.org/2003/01/geo/wgs84_pos#lat> ?lat .
	?p <http://www.w3.org/2003/01/geo/wgs84_pos#long> ?long .
	?p <http://data.ordnancesurvey.co.uk/ontology/postcode/ward> ?w .
	?w <http://www.w3.org/2004/02/skos/core#prefLabel> ?wlabel .
	?p <http://data.ordnancesurvey.co.uk/ontology/postcode/district> ?d .
	?d <http://www.w3.org/2004/02/skos/core#prefLabel> ?dlabel .
		}");
		if(count($postcodedata) == 1)
		{
			$postcodedata = $postcodedata[0];
			echo '["'.$x.' '.$postcodedata['wlabel'].', '.$postcodedata['dlabel'].'","'.$type[$x];
				echo '","postcode:'.$x.','.$postcodedata['lat'].','.$postcodedata['long'].'"';
		}
		else
		{
			echo '["'.$x.'","'.$type[$x];
				echo '",null';
		}
		if(isset($icon[$x]))
			echo ',"'.$icon[$x].'"';
		echo '],';
	}
	else
	{
		echo '["'.$x.'","'.$type[$x];
		if($type[$x] == 'building' || $type[$x] == 'site' || $type[$x] == 'bus-stop' || $type[$x] == 'point-of-service' || $type[$x] == 'workstation')
		{
			echo '","'.$url[$x];
			if(isset($icon[$x]))
				echo '","'.$icon[$x];
		}
		else if($type[$x] == 'postcode')
		{
			if(preg_match('/[A-Z]([A-Z][0-9][0-9]?)|([0-9][A-Z]) [0-9][A-Z][A-Z]/', $x))
			{
				echo '","'.$rdfurl.'#'.$lat.','.$long.'"';
			}
			else
			{
				echo '",null';
			}
			if(isset($icon[$x]))
			echo ',"'.$icon[$x];
		}
		echo '"],';
	}
}
echo '[]]';
echo ']';
?>
