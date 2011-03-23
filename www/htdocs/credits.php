<?php
if(isset($include) && !$include && substr($_SERVER['REQUEST_URI'], -4, 4) == '.php')
	header('Location: credits');
error_reporting(0);
include_once "inc/sparqllib.php";

$endpoint = "http://sparql.data.southampton.ac.uk";
$uri = "http://opendatamap.ecs.soton.ac.uk";

$datasets = sparql_get($endpoint, "
SELECT DISTINCT ?name ?uri {
  ?app <http://xmlns.com/foaf/0.1/homepage> <$uri> .
  ?app <http://purl.org/dc/terms/requires> ?uri .
  ?uri <http://purl.org/dc/terms/title> ?name .
} ORDER BY ?name
");
/*
$creators = sparql_get($endpoint, "
SELECT DISTINCT ?name ?uri {
  ?app <http://xmlns.com/foaf/0.1/homepage> <$uri> .
  ?app <http://purl.org/dc/terms/creator> ?uri .
  ?uri <http://xmlns.com/foaf/0.1/name> ?name .
} ORDER BY ?name
");
*/

foreach($datasets as $dataset)
{
	$datasetlinks[]=  "<a href='".$dataset['uri']."'>".$dataset['name']."</a>";
}

/*
foreach($creators as $creator)
{
	$creatorlinks[]=  "<a href='".$creator['uri']."'>".$creator['name']."</a>";
}
*/
$creatorlinks[]=  "<a href='http://id.ecs.soton.ac.uk/person/23977'>Colin R. Williams</a>";
$creatorlinks[]=  "<a href='http://id.ecs.soton.ac.uk/person/24273'>electronic Max</a>";
$creatorlinks[]=  "<a href='http://id.ecs.soton.ac.uk/person/23796'>Jarutas Pattanaphanchai</a>";

$suggestionlinks[]=  "<a href='http://id.ecs.soton.ac.uk/person/1248'>Christopher Gutteridge</a>";
$suggestionlinks[]=  "<a href='http://id.ecs.soton.ac.uk/person/9455'>David Tarrant</a>";

if($include)
{
	echo "Application created by ";
	echo implode(", ", $creatorlinks);
	echo "<br/>using the following datasets: ";
	echo implode(", ", $datasetlinks);
	echo "<br/><a href='credits'>Full Application Credits</a>";
}
else
{
?>
<html>
<head>
		<link rel="stylesheet" href="css/reset.css" type="text/css">
		<link rel="stylesheet" href="css/index.css" type="text/css">
		<link rel="stylesheet" href="css/credits.css" type="text/css">
</head>
<body>
<?php
	echo "<p>The <a href='.'>linked open data map</a> was developed by:";
	echo "<ul>";
	foreach($creatorlinks as $creatorlink)
		echo "<li>$creatorlink</li>";
	echo "</ul>";
	echo "</p>";
	echo "<p>It makes use of the following datasets, provided by the <a href='http://id.southampton.ac.uk/'>University of Southampton</a>'s <a href='http://data.southampton.ac.uk'>Open Data Service</a>:";
	echo "<ul>";
	foreach($datasetlinks as $datasetlink)
		echo "<li>$datasetlink</li>";
	echo "</ul>";
	echo "</p>";
	echo "<p>It uses icons from the excellent <a href='http://code.google.com/p/google-maps-icons/'>Map Icons Collection</a>:";
	echo "<ul>";
	echo "<li><a href='http://code.google.com/p/google-maps-icons/'><img src='http://google-maps-icons.googlecode.com/files/banner88x31.gif' /></a></li>";
	echo "</ul>";
	echo "</p>";
	echo "<p>It uses the <a href='http://graphite.ecs.soton.ac.uk/sparqllib'>SPARQL RDF Library for PHP</a>, developed by:";
	echo "<ul>";
	echo "<li><a href='http://id.ecs.soton.ac.uk/person/1248'>Christopher Gutteridge</a></li>";
	echo "</ul>";
	echo "</p>";
	echo "<p>Thanks also to suggestions from:";
	echo "<ul>";
	foreach($suggestionlinks as $suggestionlink)
		echo "<li>$suggestionlink</li>";
	echo "</ul>";
	echo "</p>";
?>
</body>
</html>
<?php
}
?>





