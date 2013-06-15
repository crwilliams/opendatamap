<?php
if(isset($include) && !$include && substr($_SERVER['REQUEST_URI'], -4, 4) == '.php')
	header('Location: credits');
include_once "config.php";

function creditLine($line)
{
	echo '<div draggable="false" style="-webkit-user-select: none; display: inline-block; position: relative; bottom: 2px; right: -3px;" class="gm-style-cc">';
	echo '<div style="opacity: 0.7; width: 100%; height: 12px; position: absolute;"><div style="width: 1px;"></div><div style="background-color: rgb(245, 245, 245); width: auto; height: 100%; margin-left: 1px;"></div></div>';
	echo '<div style="position: relative; padding-right: 6px; padding-left: 6px; font-family: Roboto, Arial, sans-serif; font-size: 10px; color: rgb(68, 68, 68); white-space: nowrap; direction: ltr; text-align: right; top:1px"><span style="">'.$line.'</span></div>';
	echo '</div>';
}

/*
$creators = sparql_get($endpoint, "
SELECT DISTINCT ?name ?uri {
  ?app <http://xmlns.com/foaf/0.1/homepage> <$uri> .
  ?app <http://purl.org/dc/terms/creator> ?uri .
  ?uri <http://xmlns.com/foaf/0.1/name> ?name .
} ORDER BY ?name
");
*/

list($datasetlinks, $datasetextras) = getDataSetLinks($include);


/*
foreach($creators as $creator)
{
	$creatorlinks[]=  "<a href='".$creator['uri']."'>".$creator['name']."</a>";
}
*/
$creatorlinks[]=  "<a href='http://www.crwilliams.co.uk/'>Colin R. Williams</a>";
$addcreatorlinks[]=  "<a href='http://id.ecs.soton.ac.uk/person/24273'>electronic Max</a>";
$addcreatorlinks[]=  "<a href='http://id.ecs.soton.ac.uk/person/23796'>Jarutas Pattanaphanchai</a>";
$addcreatorlinks[]=  "<a href='http://id.ecs.soton.ac.uk/person/23470'>Harry Rose</a>";

$suggestionlinks[]=  "<a href='http://id.ecs.soton.ac.uk/person/1248'>Christopher Gutteridge</a>";
$suggestionlinks[]=  "<a href='http://id.ecs.soton.ac.uk/person/9455'>David Tarrant</a>";

if($include)
{
	creditLine("Application created by ".implode(", ", $creatorlinks)." and <a href='".$_GET['v']."/credits' title='Full Application Credits'>others</a>");
	echo "<br />";
	creditLine("using the following datasets: ".implode(", ", $datasetlinks));
	foreach($datasetextras as $datasetextra)
	{
		echo "<br />";
		creditLine($datasetextra);
	}
}
else
{
?>
<html>
<head>
	<link rel="stylesheet" href="/css/reset.css" type="text/css">
	<link rel="stylesheet" href="/css/index.css" type="text/css">
	<link rel="stylesheet" href="/css/credits.css" type="text/css">
	<title><?= $config['Site title'] ?> | credits</title>
</head>
<body>
<? include_once 'googleanalytics.php'; ?>
<h1><?= $config['Site title'] ?></h1>
<h2>credits</h2>
<h3>Development</h3>
<?php
	echo "<p>The <a href='.'>opendatamap ".$config['Site title']."</a> was developed by:";
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
	echo "<p>It makes use of the following datasets:";
	echo "<ul>";
	foreach($datasetlinks as $datasetlink)
		echo "<li>$datasetlink</li>";
	echo "</ul>";
	echo "</p>";
	echo "<p>";
	echo implode('<br/>', $datasetextras);
	echo "</p>";
?>
<h3>Icons</h3>
	<p>The opendatamap <a href='http://opendatamap.ecs.soton.ac.uk/iconset'>iconset</a> is available under the <a href='http://creativecommons.org/licenses/by-sa/3.0/' title='Creative Commons - Attribution-ShareAlike 3.0 Unported'>CC BY-SA 3.0</a> licence.  The attribution should be to <em>opendatamap iconset</em>, with a link provided to <a href='http://opendatamap.ecs.soton.ac.uk/iconset'>http://opendatamap.ecs.soton.ac.uk/iconset</a>.  Is is based on the <a href='http://mapicons.nicolasmollet.com'>Map Icons Collection</a> which is also available under the same licence.
	</p>
<h3>Libraries</h3>
<?
	echo "<p>It uses the <a href='http://graphite.ecs.soton.ac.uk/sparqllib'>SPARQL RDF Library for PHP</a>, developed by:";
	echo "<ul>";
	echo "<li><a href='http://id.ecs.soton.ac.uk/person/1248'>Christopher Gutteridge</a></li>";
	echo "</ul>";
	echo "The library is available under the <a href='http://creativecommons.org/licenses/by/3.0' title='Creative Commons - Attribution 3.0 Unported'>CC BY 3.0</a> licence.";
	echo "</p>";
	echo "<p>It also uses the <a href='http://sourceforge.net/projects/simplehtmldom/'>Simple HTML DOM Library</a>, developed by:";
	echo "<ul>";
	echo "<li><a href='https://sourceforge.net/projects/php-html/'>Jose Solorzano</a></li>";
	echo "</ul>";
	echo "The library is available under the MIT licence.";
	echo "</p>";
?>
<h3>Source</h3>
	<p>The opendatamap <a href='http://opendatamap.ecs.soton.ac.uk/source'>source code</a> is available under the <a href='http://creativecommons.org/licenses/by-sa/3.0' title='Creative Commons - Attribution-ShareAlike 3.0 Unported'>CC BY-SA 3.0</a> licence.  The attribution should be to <em>opendatamap</em>, with a link provided to <a href='http://opendatamap.ecs.soton.ac.uk/source'>http://opendatamap.ecs.soton.ac.uk/source</a>.</p>
<?
?>
<h3>Other</h3>
<?
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

function getDataSetLinks($include)
{
	global $config;

	$licencenames['http://reference.data.gov.uk/id/open-government-licence'] = "Open Government Licence";
	$licencenames['http://creativecommons.org/licenses/by/2.0/uk/'] = "Creative Commons Attribution 2.0 UK: England & Wales License";
	$licencenames['http://www.opendefinition.org/licenses/cc-zero'] = "Creative Commons CC Zero License";
	$licencenames['http://www.food.gov.uk/ratings-terms-and-conditions'] = "FHRS/FHIS Brand Standard, terms and conditions";

	$datasets = array();
	foreach($config['datasource'] as $ds)
	{
		$dsclass = ucwords($ds).'DataSource';
		foreach(call_user_func(array($dsclass, 'getDataSets')) as $dataset)
		{
			if(!in_array($dataset['uri'], $config['unused-datasets']))
			{
				$datasets[] = $dataset;
			}
		}
		foreach(call_user_func(array($dsclass, 'getDataSetExtras')) as $datasetextra)
		{
			$datasetextras[] = $datasetextra;
		}
	}

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
	$datasetlinks = array_unique($datasetlinks);
	$datasetextras = array_unique($datasetextras);
	return array($datasetlinks, $datasetextras);
}

?>





