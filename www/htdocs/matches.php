<?php
error_reporting(0);
include_once "inc/sparqllib.php";

$q = trim($_GET['q']);
$endpoint = "http://sparql.data.southampton.ac.uk";

$cats = explode(',', $_GET['ec']);

$file = fopen('catlist.csv', 'r');
while($row = fgetcsv($file))
{
	$iconcats[$row[0]] = $row[1];
}
fclose($file);

function in_cat($iconcats, $icon, $cats)
{
	return in_array($iconcats[$icon], $cats);
}

$data = sparql_get($endpoint, "
PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
PREFIX org: <http://www.w3.org/ns/org#>
PREFIX gr: <http://purl.org/goodrelations/v1#>

SELECT DISTINCT ?poslabel ?label ?pos ?icon WHERE {
  ?offering a gr:Offering .
  ?offering gr:availableAtOrFrom ?pos .
  ?offering gr:includes ?ps .
  ?pos rdfs:label ?poslabel .
  ?ps rdfs:label ?label .
  ?pos <http://purl.org/openorg/mapIcon> ?icon .
  FILTER ( REGEX( ?label, '$q', 'i') || REGEX( ?poslabel, '$q', 'i') 
  )
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
foreach($data as $point) {
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
	echo '["'.$x.'","'.$type[$x];
	if($type[$x] == 'building' || $type[$x] == 'site' || $type[$x] == 'bus-stop' || $type[$x] == 'point-of-service')
	{
		echo '","'.$url[$x];
		if(isset($icon[$x]))
			echo '","'.$icon[$x];
	}
	echo '"],';
}
echo '[]]';
echo ']';
?>
