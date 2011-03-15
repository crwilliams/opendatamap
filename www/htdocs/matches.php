<?php
error_reporting(0);
include_once "sparqllib.php";

$q = $_GET['q'];
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

$pos = array();
$label = array();
foreach($data as $point) {
	if(!in_cat($iconcats, $point['icon'], $cats))
		continue;
	$pos[$point['pos']] ++;
	if(preg_match('/'.$q.'/i', $point['label']))
		$label[$point['label']] ++;
	if(preg_match('/'.$q.'/i', $point['poslabel']))
		$label[$point['poslabel']] ++;
}
foreach($busdata as $point) {
	if(!in_cat($iconcats, $point['icon'], $cats))
		continue;
	$pos[$point['pos']] ++;
	if(preg_match('/'.$q.'/i', $point['label']))
		$label[$point['label']] ++;
	if(preg_match('/'.$q.'/i', $point['poslabel']))
		$label[$point['poslabel']] ++;
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
	echo '"'.$x.'",';
echo '[]]';
echo ']';
?>
