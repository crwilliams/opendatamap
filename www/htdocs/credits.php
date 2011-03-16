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
} ORDER BY ?name
");
$creators = sparql_get($endpoint, "
SELECT DISTINCT ?name ?uri {
  ?app <http://xmlns.com/foaf/0.1/homepage> <$uri> .
  ?app <http://purl.org/dc/terms/creator> ?uri .
  ?uri <http://xmlns.com/foaf/0.1/name> ?name .
} ORDER BY ?name
");

foreach($datasets as $dataset)
{
	$datasetlinks[]=  "<a href='".$dataset['uri']."'>".$dataset['name']."</a>";
}

foreach($creators as $creator)
{
	$creatorlinks[]=  "<a href='".$creator['uri']."'>".$creator['name']."</a>";
}

$suggestionlinks[]=  "<a href='http://id.ecs.soton.ac.uk/person/1248'>Christopher Gutteridge</a>";
$suggestionlinks[]=  "<a href='http://id.ecs.soton.ac.uk/person/9455'>David Tarrant</a>";

if($include)
{
	echo "Application created by ";
	echo implode(", ", $creatorlinks);
	echo "<br/>using the following datasets: ";
	echo implode(", ", $datasetlinks);
	echo "<br/><a href='credits.php'>Full Application Credits</a>";
}
else
{
	echo "The <a href='.'>linked open data map</a> was developed by:";
	echo "<ul>";
	foreach($creatorlinks as $creatorlink)
		echo "<li>$creatorlink</li>";
	echo "</ul>";
	echo "It makes use of the following datasets:";
	echo "<ul>";
	foreach($datasetlinks as $datasetlink)
		echo "<li>$datasetlink</li>";
	echo "</ul>";

	echo "Thanks also to suggestions from:";
	echo "<ul>";
	foreach($suggestionlinks as $suggestionlink)
		echo "<li>$suggestionlink</li>";
	echo "</ul>";
	
}
?>





