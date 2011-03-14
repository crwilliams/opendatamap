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
}
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
}
");

$pos = array();
$label = array();
foreach($data as $point) {
	if(!in_cat($iconcats, $point['icon'], $cats))
		continue;
	$pos[] = $point['pos'];
	if(preg_match('/'.$q.'/i', $point['label']))
		$label[$point['label']] ++;
	if(preg_match('/'.$q.'/i', $point['poslabel']))
		$label[$point['poslabel']] ++;
}
foreach($busdata as $point) {
	if(!in_cat($iconcats, $point['icon'], $cats))
		continue;
	$pos[] = $point['pos'];
	if(preg_match('/'.$q.'/i', $point['label']))
		$label[$point['label']] ++;
	if(preg_match('/'.$q.'/i', $point['poslabel']))
		$label[$point['poslabel']] ++;
}
arsort($label);
$limit = 100;
if(count($label) > 100)
	$label = array_slice($label, 0, 100);
echo 'matches = '.json_encode($pos).';';
echo 'labelmatches = '.json_encode(array_keys($label)).';';

function escape($str)
{
    return preg_replace("!([\b\t\n\r\f\"\\'])!", "\\\\\\1", $str);
}
function json_encode($in, $indent = 0, $from_array = false)
{
    $_myself = __FUNCTION__;
    $_escape = "escape";

    $out = '';

    foreach ($in as $key=>$value)
    {
        $out .= str_repeat("\t", $indent + 1);
        $out .= "\"".$_escape((string)$key)."\": ";

        if (is_object($value) || is_array($value))
        {
            $out .= "\n";
            $out .= $_myself($value, $indent + 1);
        }
        elseif (is_bool($value))
        {
            $out .= $value ? 'true' : 'false';
        }
        elseif (is_null($value))
        {
            $out .= 'null';
        }
        elseif (is_string($value))
        {
            $out .= "\"" . $_escape($value) ."\"";
        }
        else
        {
            $out .= $value;
        }

        $out .= ",\n";
    }

    if (!empty($out))
    {
        $out = substr($out, 0, -2);
    }

    $out = str_repeat("\t", $indent) . "{\n" . $out;
    $out .= "\n" . str_repeat("\t", $indent) . "}";

    return $out;
}

?>
