<?php
if(isset($include) && !$include && substr($_SERVER['REQUEST_URI'], -4, 4) == '.php')
	header('Location: credits');
error_reporting(0);
include_once "inc/sparqllib.php";

$endpoint = "http://sparql.data.southampton.ac.uk";
$uri = "http://opendatamap.ecs.soton.ac.uk";

$datasets = sparql_get($endpoint, "
SELECT DISTINCT ?name ?uri ?l {
  ?app <http://xmlns.com/foaf/0.1/homepage> <$uri> .
  ?app <http://purl.org/dc/terms/requires> ?uri .
  ?uri <http://purl.org/dc/terms/title> ?name .
  OPTIONAL { ?uri <http://purl.org/dc/terms/license> ?l . }
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

$licencenames['http://reference.data.gov.uk/id/open-government-licence'] = "Open Government Licence";

foreach($datasets as $dataset)
{
	$datasetlink =  "<a href='".$dataset['uri']."'>".$dataset['name']."</a>";
	if($dataset['l'] != "")
	{
		$ln = $licencenames[$dataset['l']];
		if(!$include)
			$datasetlink .= " (available under the <a href='".$dataset['l']."'>$ln</a>)";
	}
	$datasetlinks[] = $datasetlink;
}

/*
foreach($creators as $creator)
{
	$creatorlinks[]=  "<a href='".$creator['uri']."'>".$creator['name']."</a>";
}
*/
$creatorlinks[]=  "<a href='http://id.ecs.soton.ac.uk/person/23977'>Colin R. Williams</a>";
$addcreatorlinks[]=  "<a href='http://id.ecs.soton.ac.uk/person/24273'>electronic Max</a>";
$addcreatorlinks[]=  "<a href='http://id.ecs.soton.ac.uk/person/23796'>Jarutas Pattanaphanchai</a>";
$addcreatorlinks[]=  "<a href='http://id.ecs.soton.ac.uk/person/23470'>Harry Rose</a>";

$suggestionlinks[]=  "<a href='http://id.ecs.soton.ac.uk/person/1248'>Christopher Gutteridge</a>";
$suggestionlinks[]=  "<a href='http://id.ecs.soton.ac.uk/person/9455'>David Tarrant</a>";

if($include)
{
	echo "Application created by ";
	echo implode(", ", $creatorlinks);
	echo " and <a href='credits' title='Full Application Credits'>others</a>";
	//echo ", ".implode(", ", $addcreatorlinks);
	echo "<br/>using the following datasets: ";
	echo implode(", ", $datasetlinks);
	//echo "<br/><a href='credits'>Full Application Credits</a>";
}
else
{
?>
<html>
<head>
	<link rel="stylesheet" href="css/reset.css" type="text/css">
	<link rel="stylesheet" href="css/index.css" type="text/css">
	<link rel="stylesheet" href="css/credits.css" type="text/css">
	<title>opendatamap | credits</title>
</head>
<body>
<h1>opendatamap</h1>
<h2>credits</h2>
<h3>Development</h3>
<?php
	echo "<p>The <a href='.'>linked open data map</a> was developed by:";
	echo "<ul>";
	foreach($creatorlinks as $creatorlink)
		echo "<li>$creatorlink</li>";
	echo "</ul>";
	echo "</p>";
	echo "<p>";
	echo "with the assistance of:";
	echo "<ul>";
	foreach($addcreatorlinks as $creatorlink)
		echo "<li>$creatorlink</li>";
	echo "</ul>";
	echo "</p>";
?>
<h3>Data</h3>
<?
	echo "<p>It makes use of the following datasets, provided by the <a href='http://id.southampton.ac.uk/'>University of Southampton</a>'s <a href='http://data.southampton.ac.uk'>Open Data Service</a>:";
	echo "<ul>";
	foreach($datasetlinks as $datasetlink)
		echo "<li>$datasetlink</li>";
	echo "</ul>";
	echo "</p>";
?>
<h3>Icons</h3>
	<p>The opendatamap <a href='iconset'>iconset</a> is available under the <a href='http://creativecommons.org/licenses/by-sa/3.0/' title='Creative Commons - Attribution-ShareAlike 3.0 Unported'>CC BY-SA 3.0</a> licence.  The attribution should be to <em>opendatamap iconset</em>, with a link provided to <a href='http://opendatamap.ecs.soton.ac.uk/iconset'>http://opendatamap.ecs.soton.ac.uk/iconset</a>.  Is is based on the <a href='http://code.google.com/p/google-maps-icons/'>Map Icons Collection</a> which is also available under the same licence.
	</p>
<h3>Libraries</h3>
<?
	echo "<p>It uses the <a href='http://graphite.ecs.soton.ac.uk/sparqllib'>SPARQL RDF Library for PHP</a>, developed by:";
	echo "<ul>";
	echo "<li><a href='http://id.ecs.soton.ac.uk/person/1248'>Christopher Gutteridge</a></li>";
	echo "</ul>";
	echo "The library is available under the <a href='http://creativecommons.org/licenses/by/3.0' title='Creative Commons - Attribution 3.0 Unported'>CC BY 3.0</a> licence.";
	echo "</p>";
?>
<h3>Source</h3>
	<p>The opendatamap <a href='source'>source code</a> is available under the <a href='http://creativecommons.org/licenses/by-sa/3.0' title='Creative Commons - Attribution-ShareAlike 3.0 Unported'>CC BY-SA 3.0</a> licence.  The attribution should be to <em>opendatamap</em>, with a link provided to <a href='http://opendatamap.ecs.soton.ac.uk/source'>http://opendatamap.ecs.soton.ac.uk/source</a>.</p>
<?
?>
<h3>Other</h3>
<?
	echo "<p>The information presented on our opendatamap is also available via the <a href='http://m.layar.com/open/universityofsouthampton'>University of Southampton layer</a>, which has been developed for the <a href='http://www.layar.com'>layar</a> application, available on Android, iPhone and Nokia Ovi.</p>";
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





