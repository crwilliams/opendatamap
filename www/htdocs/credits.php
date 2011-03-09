<?php
error_reporting(0);
include_once "sparqllib.php";

$endpoint = "http://sparql.data.southampton.ac.uk";
$uri = "http://opendatamap.ecs.soton.ac.uk";

$datasets = sparql_get($endpoint, "
SELECT DISTINCT ?name ?uri {
  ?app <http://xmlns.com/foaf/0.1/homepage> <$uri> .
  ?app <http://purl.org/dc/terms/requires> ?uri .
  ?uri <http://purl.org/dc/terms/title> ?name .
}
");
$creators = sparql_get($endpoint, "
SELECT DISTINCT ?name ?uri {
  ?app <http://xmlns.com/foaf/0.1/homepage> <$uri> .
  ?app <http://purl.org/dc/terms/creator> ?uri .
  ?uri <http://xmlns.com/foaf/0.1/name> ?name .
}
");

foreach($datasets as $dataset)
{
	$datasetlinks[]=  "<a href='".$dataset['uri']."'>".$dataset['name']."</a>";
}

foreach($creators as $creator)
{
	$creatorlinks[]=  "<a href='".$creator['uri']."'>".$creator['name']."</a>";
}

echo "Application created by ";
echo implode(", ", $creatorlinks);
echo "<br/>using the following datasets: ";
echo implode(", ", $datasetlinks);
?>





