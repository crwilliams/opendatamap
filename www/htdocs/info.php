<?php
error_reporting(0);
include_once "inc/sparqllib.php";

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
echo "<h2><img style='width:20px' src='".($allpos[0]['icon']!=""?$allpos[0]['icon']:"img/blackness.png")."' />".$allpos[0]['name']."</h2>";

if(preg_match('/http:\/\/id\.southampton\.ac\.uk\/bus-stop\/(.*)/', $uri, $matches))
{
	echo "<iframe style='border:none' src='bus.php?uri=".$_GET['uri']."' />";
	die();
}

$allpos = sparql_get($endpoint, "
PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
PREFIX org: <http://www.w3.org/ns/org#>
PREFIX gr: <http://purl.org/goodrelations/v1#>

SELECT DISTINCT * WHERE {
	?o gr:availableAtOrFrom <$uri> .
	?o gr:includes ?ps .
	?ps a gr:ProductOrServicesSomeInstancesPlaceholder .
	?ps rdfs:label ?label .
} ORDER BY ?label 
");
echo "<h3> Offers: </h3>";
echo "<ul class='offers'>"; 
foreach($allpos as $point) {
	echo "<li onclick=\"setInputBox('^".$point['label']."$'); updateFunc();\">".$point['label'].$point['type']."</li>";
}
echo "</ul>";

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
	echo "<div id='openings'>";
	echo "<h3>Opening detail:</h3>";
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
	echo "<table id='openings' style='font-size:0.8em'>";
	echo "<tr>";
	foreach($weekday as $day)
	{
		$short_day = substr($day, 0,3); 
		echo "<th>".$short_day."</th>";
	}
	echo "<th>Valid Dates</th>";
	echo "</tr>";

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
			echo "<tr class='current'>"; //start of row
		}
		else
		{
			echo "<tr>";
		}
		foreach($weekday as $day)
		{
			echo "<td width=\"350\">";
			foreach($otv['http://purl.org/goodrelations/v1#'.$day] as $dot)
			{
				if($dot == '00:00-00:00')
					$dot = '24 hour';
				echo $dot."<br/>";
				if($day == date('l', $now))
				{
					$todayopening[] = "<li>$dot</li>";
				}
			}
			echo "</td>";
		}
		echo "<td>".$valid."</td>";
		echo "</tr>";
	}
	echo "</table>";
	echo "</div>";

	if($todayopening != null)
	{
		echo "<div id='todayopenings'>";
		echo "<h3>Today's opening hours:</h3>";
		echo "<ul style='font-size:0.8em'>";
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





