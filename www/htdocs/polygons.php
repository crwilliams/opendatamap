<?php
error_reporting(0);
include_once "sparqllib.php";

$endpoint = "http://sparql.data.southampton.ac.uk";

$sites = sparql_get($endpoint, "
SELECT DISTINCT ?url ?name ?outline WHERE {
  ?url a <http://www.w3.org/ns/org#Site> .
  ?url <http://purl.org/dc/terms/spatial> ?outline .
  ?url <http://www.w3.org/2000/01/rdf-schema#label> ?name .
} 
");
$buildings = sparql_get($endpoint, "
SELECT DISTINCT ?url ?name ?outline ?hfeature ?lfeature ?number WHERE {
  ?url a <http://vocab.deri.ie/rooms#Building> .
  ?url <http://purl.org/dc/terms/spatial> ?outline .
  ?url <http://www.w3.org/2000/01/rdf-schema#label> ?name .
  OPTIONAL { ?url <http://purl.org/openorg/hasFeature> ?hfeature . 
           ?hfeature <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://id.southampton.ac.uk/ns/PlaceFeature-ResidentialUse> }
  OPTIONAL { ?url <http://purl.org/openorg/lacksFeature> ?lfeature . 
           ?lfeature <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://id.southampton.ac.uk/ns/PlaceFeature-ResidentialUse> }
  OPTIONAL { ?url <http://www.w3.org/2004/02/skos/core#notation> ?number . }
} 
");
echo '[';
foreach($sites as $x) {
	echo '[';
	echo '["'.$x['url'].'"],';
	echo '["'.$x['name'].'"],';
	echo '[-10],';
	echo '[';
	$x['outline'] = explode(",", str_replace(array("POLYGON((", "))"), "", $x['outline']));
	foreach($x['outline'] as $p)
	{
		echo '[';
		echo str_replace(' ', ',', $p);
		echo '],';
	}
	echo '[]]';
	echo '],';
}
foreach($buildings as $x) {
	echo '[';
	echo '["'.$x['url'].'"],';
	echo '["'.$x['number'].': '.$x['name'].'"],';
	echo '[-5],';
	echo '[';
	$x['outline'] = explode(",", str_replace(array("POLYGON((", "))"), "", $x['outline']));
	foreach($x['outline'] as $p)
	{
		echo '[';
		echo str_replace(' ', ',', $p);
		echo '],';
	}
	echo '[]]';
	echo '],';
}
echo '[]]';
?>
