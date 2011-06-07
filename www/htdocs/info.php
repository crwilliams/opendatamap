<?php
error_reporting(0);
include_once "inc/sparqllib.php";

// This script should return, for a given marker ID (passed in as $_GET['uri']), an html fragment that can be displayed in that icon's infowindow.
// It is called via ajax when the infowindow is opened.

$uri = urldecode($_GET['uri']);

$endpoint = "http://sparql.data.southampton.ac.uk";

$allpos = sparql_get($endpoint, "
PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
PREFIX org: <http://www.w3.org/ns/org#>

SELECT DISTINCT ?name ?icon WHERE {
    <$uri> rdfs:label ?name .
    OPTIONAL { <$uri> <http://purl.org/openorg/mapIcon> ?icon . }
}
");
echo "<div id='content'>";
$computer = false;
if(!isset($allpos[0]['icon']))
{
	if(substr($uri, 0, 33) == "http://id.southampton.ac.uk/room/")
	{
		$icon = "http://opendatamap.ecs.soton.ac.uk/img/icon/computer.png";
		$computer = "true";
	}
	else
	{
		$icon = "";
	}
}
else
	$icon = $allpos[0]['icon'];
$icon = str_replace("http://google-maps-icons.googlecode.com/files/", "http://opendatamap.ecs.soton.ac.uk/img/icon/", $icon);
$icon = str_replace("http://data.southampton.ac.uk/map-icons/lattes.png", "http://opendatamap.ecs.soton.ac.uk/img/icon/coffee.png", $icon);

function routestyle($code)
{
	$color['U1'] = array(  0, 142, 207);
	$color['U2'] = array(226,   2,  20);
	$color['U6'] = array(246, 166,  24);
	$color['U9'] = array(232,  84, 147);
	if(isset($color[$code]))
	{
		return "style='background-color:#".str_pad(dechex($color[$code][0]), 2, '0').str_pad(dechex($color[$code][1]), 2, '0').str_pad(dechex($color[$code][2]), 2, '0').";' ";
	}
}

if(preg_match('/http:\/\/id\.southampton\.ac\.uk\/bus-stop\/(.*)/', $uri, $matches))
{
$allbus = sparql_get($endpoint, "
SELECT DISTINCT ?code WHERE {
  ?rstop <http://id.southampton.ac.uk/ns/inBusRoute> ?route .
  ?rstop <http://id.southampton.ac.uk/ns/busStoppingAt> <$uri> .
  ?route <http://www.w3.org/2004/02/skos/core#notation> ?code .
  FILTER ( REGEX( ?code, '^U', 'i') )
} ORDER BY ?code
");
	$codes = array();
	foreach($allbus as $code)
		$codes[] = $code['code'];
	echo "<h2><img class='icon' src='http://opendatamap.ecs.soton.ac.uk/resources/busicon.php?r=".implode('/', $codes)."' />".$allpos[0]['name'];
	echo "<a class='odl' href='$uri'>Visit&nbsp;page</a></h2>";
	echo "<h3> Served by: (click to filter) </h3>";
	echo "<ul class='offers'>"; 
	foreach($allbus as $code) {
		echo "<li ".routestyle($code['code'])."onclick=\"setInputBox('^".str_replace(array("(", ")"), array("\(", "\)"), $code['code'])."$'); updateFunc();\">".$code['code']."</li>";
	}
	echo "</ul>";
	echo "<iframe style='border:none' src='bus.php?uri=".$_GET['uri']."' />";
	die();
}

$page = sparql_get($endpoint, "
PREFIX foaf: <http://xmlns.com/foaf/0.1/>
	
SELECT DISTINCT ?page WHERE {
	<$uri> foaf:page ?page .
} ORDER BY ?page
");

//if(count($page) > 0)
echo "<h2><img class='icon' src='".($icon!=""?$icon:"img/blackness.png")."' />".$allpos[0]['name'];
if(preg_match('/http:\/\/id\.southampton\.ac\.uk\/.*/', $uri))
{
	//print_r($page[0]);
	//echo "<a class='odl' href='".$page[0]['page']."'>Visit page</a>";
	echo "<a class='odl' href='".$uri."'>Visit page</a>";
}
echo "</h2>";

if($computer)
{
	$allpos = sparql_get($endpoint, "
PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
PREFIX org: <http://www.w3.org/ns/org#>
PREFIX gr: <http://purl.org/goodrelations/v1#>
	
SELECT DISTINCT ?label WHERE {
	<$uri> <http://purl.org/openorg/hasFeature> ?f .
	?f a ?ft .
	?ft rdfs:label ?label .
	FILTER ( REGEX(?label, '^(WORKSTATION|SOFTWARE) -') )
} ORDER BY ?label
	");
}
else
{
	$allpos = sparql_get($endpoint, "
PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
PREFIX org: <http://www.w3.org/ns/org#>
PREFIX gr: <http://purl.org/goodrelations/v1#>

SELECT DISTINCT ?label WHERE {
	?o gr:availableAtOrFrom <$uri> .
	?o gr:includes ?ps .
	?ps a gr:ProductOrServicesSomeInstancesPlaceholder .
	?ps rdfs:label ?label .
} ORDER BY ?label 
	");
}

if(count($allpos) == 0)
{
	$allpos = sparql_get($endpoint, "
	PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
	PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
	PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
	PREFIX org: <http://www.w3.org/ns/org#>
	PREFIX gr: <http://purl.org/goodrelations/v1#>
	
	SELECT DISTINCT ?label WHERE {
		?o gr:availableAtOrFrom <$uri> .
		?o gr:includes ?ps .
		?ps a gr:ProductOrService .
		?ps rdfs:label ?label .
	} ORDER BY ?label 
	");
}
if(count($allpos) > 0)
{
	echo "<h3> Offers: (click to filter) </h3>";
	echo "<ul class='offers'>"; 
	foreach($allpos as $point) {
		echo "<li onclick=\"setInputBox('^".str_replace(array("(", ")"), array("\(", "\)"), $point['label'])."$'); updateFunc();\">".$point['label']."</li>";
	}
	echo "</ul>";
}

if(preg_match('/http:\/\/id\.southampton\.ac\.uk\/point-of-service\/PARKING-(.*)/', $uri, $matches))
{
	echo "<iframe style='border:none' src='parking.php?uri=".$_GET['uri']."' />";
	die();
}


$allpos = sparql_get($endpoint, "
PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
PREFIX org: <http://www.w3.org/ns/org#>
PREFIX gr: <http://purl.org/goodrelations/v1#>

SELECT DISTINCT * WHERE {
	<$uri> gr:hasOpeningHoursSpecification ?time .
	OPTIONAL { ?time gr:validFrom ?start . }
	OPTIONAL { ?time gr:validThrough ?end . }
	?time gr:hasOpeningHoursDayOfWeek ?day .
	?time gr:opens ?opens .
	?time gr:closes ?closes .
} ORDER BY ?start ?end ?day ?opens ?closes
");

if(count($allpos) > 0)
{
	//echo "<div id='openings'>";
	//echo "<h3>Opening detail:</h3>";
	foreach($allpos as $point)
	{
		if ($point['start'] != '')
		{
			$start = strtotime($point['start']);
			$start = date('d/m/Y',$start);
		}
		else 
		{
			$start = '';
		}
		if ($point['end'] != '')
		{
			$end = strtotime($point['end']);
			$end = date('d/m/Y',$end);
		}
		else
		{
			$end = '';
		}
		$open = strtotime($point['opens']);
		$open = date('H:i',$open);
		$close = strtotime($point['closes']);
		$close = date('H:i',$close);
		$ot[$start."-".$end][$point['day']][] = $open."-".$close;
	}

	$weekday = array('Monday', 'Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday');
	//echo "<table id='openings' style='font-size:0.8em'>";
	//echo "<tr>";
	foreach($weekday as $day)
	{
		$short_day = substr($day, 0,3); 
		//echo "<th>".$short_day."</th>";
	}
	//echo "<th>Valid Dates</th>";
	//echo "</tr>";

	foreach($ot as $valid => $otv)
	{
		list($from, $to) = explode('-',$valid);
		$now = mktime();
		if ($from == '')
		{
			$from = $now - 86400;
		}
		else
		{
			$from = mktime(0,0,0,substr($from,3,2),substr($from,0,2),substr($from,7,4));
		}
		if ($to == '')
		{
			$to = $now+86400;
		}
		else
		{
			$to = mktime(0,0,0,substr($to,3,2),substr($to,0,2),substr($to,7,4));
		} 

		if ( $to < $now )
		{
			continue;
		}
		if ($from > $now + (60*60*24*30))
		{ 
			continue;
		}
		$current = ($from <=  $now )&&( $to >= $now);
		if ($current)
		{ 
			//echo "<tr class='current'>"; //start of row
			foreach($weekday as $day)
			{
				//echo "<td width=\"350\">";
				if(array_key_exists('http://purl.org/goodrelations/v1#'.$day, $otv))
				{
					foreach($otv['http://purl.org/goodrelations/v1#'.$day] as $dot)
					{
						if($dot == '00:00-00:00')
							$dot = '24 hour';
						//echo $dot."<br/>";
						if($day == date('l', $now))
						{
							$todayopening[] = "<li>$dot</li>";
						}
					}
				}
				//echo "</td>";
			}
		}
		else
		{
			//echo "<tr>";
		}
		//echo "<td>".$valid."</td>";
		//echo "</tr>";
	}
	//echo "</table>";
	//echo "</div>";

	if($todayopening != null)
	{
		echo "<div id='todayopenings'>";
		echo "<h3>Today's opening hours:</h3>";
		echo "<ul style='padding-top:8px;'>";
		foreach($todayopening as $opening)
		{
			echo $opening;
		}
		echo "</ul>";
		echo "</div>";
	}
}
echo "</div>";
?>





