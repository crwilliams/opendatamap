<?php
error_reporting(0);
include_once "inc/sparqllib.php";

$endpoint = "http://sparql.data.southampton.ac.uk";

$now = mktime();
function formatDate($i)
{
	global $now;
	if(date('Y/m/d', $i) == date('Y/m/d', $now + (60*60*24)))
		return "Tomorrow (".date('l', $i).")";
	else if (date('Y/m/d', $i) > date('Y/m/d', $now) && date('Y/m/d', $i) <= date('Y/m/d', $now + (60*60*24*7)))
		return "Next ".date('l \(jS F\)', $i);
	else
		return date('l jS F Y' , $i);
}

function getTimes($uri)
{
	if($uri['s'] == 'http://id.southampton.ac.uk/point-of-service/42-cafe')
		return;

	global $endpoint;
	//echo "<h1>".$uri['l']." (".$uri['s'].")</h1>";
	$allopen = sparql_get($endpoint, "
PREFIX gr: <http://purl.org/goodrelations/v1#>

SELECT ?day ?opens ?closes ?start ?end WHERE {
  <".$uri['s']."> gr:hasOpeningHoursSpecification ?o.
  OPTIONAL { ?o gr:validFrom ?start . }
  OPTIONAL { ?o gr:validThrough ?end . }
  ?o gr:hasOpeningHoursDayOfWeek ?day .
  ?o gr:opens ?opens .
  ?o gr:closes ?closes .
}
	");
	
	processOpeningTimes($allopen, $uri['s']);
}

$uris = sparql_get($endpoint, "
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX oo: <http://purl.org/openorg/>

SELECT DISTINCT ?s ?l ?i WHERE {
  ?s <http://purl.org/goodrelations/v1#hasOpeningHoursSpecification> ?o .
  ?s rdfs:label ?l .
  ?s oo:mapIcon ?i .
} ORDER BY ?l
");

$opening = array();
$noopening = array();

foreach($uris as $uri)
{
	$name[$uri['s']] = $uri['l'];
	$icon[$uri['s']] = $uri['i'];
	getTimes($uri);
}

$dfrom = mktime(0,0,0,1,1,2011);
$dto = mktime(0,0,0,1,1,2013);

$nowdate = date('Y/m/d', $now);

foreach($uris as $uri)
{
	if($uri['s'] == 'http://id.southampton.ac.uk/point-of-service/42-cafe')
		continue;
	//echo $uri['l']."<br />";
	$po = $opening[$uri['s']];
	$pno = $noopening[$uri['s']];
	$tprevopen = null;
	$tnextopen = null;
	for($i = $dfrom; $i < $dto; $i += (60*60*24))
	{
		$idate = date('Y/m/d', $i);
		if(isset($po[$idate]))
		{
	//		echo 'O';
			if($idate < $nowdate)
			{
				sort($po[$idate]);
				$tprevopen = formatDate($i) . "</td><td>" . implode($po[$idate], ', ');
			}
			else if($tnextopen == null && $idate > $nowdate)
			{
				sort($po[$idate]);
				$tnextopen = formatDate($i) . "</td><td>" . implode($po[$idate], ', ');
			}
		}
		else if(isset($pno[$idate]))
		{
	//		echo 'C';
		}
		else
		{
	//		echo 'U';
		}
	}
	if(isset($po[$nowdate]))
	{
		$otoday[$uri['s']] = $po[$nowdate];
	}
	else
	{
		$nextopen[$uri['s']] = $tnextopen;
		$prevopen[$uri['s']] = $tprevopen;
		if(isset($pno[$nowdate]))
		{
			$ctoday[$uri['s']] = true;
		}
		elseif($tnextopen != null)
		{
			$ctoday[$uri['s']] = true;//should be utoday
		}
		else
		{
			$utodaynf[$uri['s']] = true;
		}
	}
	//echo "<br />";
}

echo "<h1>Opening times for ".date('l jS F Y', $now)."</h1>";
echo "<h2>Open today (".count($otoday).")</h2>";
echo "<table>";
echo "<tr><th /><th>Name</th><th>Open today</th></tr>";
foreach($otoday as $uri => $times)
{
	echo "<tr>";
	echo "<td>";
	echo "<a href='$uri'><img src='".$icon[$uri]."' /></a>";
	echo "</td>";
	echo "<td>";
	echo "<a href='$uri'>".$name[$uri]."</a>";
	echo "</td>";
	echo "<td>";
	echo implode(" ", $times);
	echo "</td>";
	echo "</tr>";
}
echo "</table>";
echo "<h2>Assumed closed today (".count($ctoday).")</h2>";
echo "<table>";
echo "<tr><th /><th>Name</th><th>Next open</th></tr>";
foreach($ctoday as $uri => $times)
{
	echo "<tr>";
	echo "<td>";
	echo "<a href='$uri'><img src='".$icon[$uri]."' /></a>";
	echo "</td>";
	echo "<td>";
	echo "<a href='$uri'>".$name[$uri]."</a>";
	echo "</td>";
	echo "<td>";
	if(isset($nextopen[$uri]))
		echo $nextopen[$uri];
	foreach($times as $time)
	{
		echo $time;
	}
	echo "</td>";
	echo "</tr>";
	//echo $name[$uri]."<br />";
}
echo "</table>";
if(count($utoday) > 0)
{
	echo "<h2>Unknown opening today (".count($utoday).")</h2>";
	echo "<table>";
	echo "<tr><th /><th>Name</th><th>Next open</th></tr>";
	foreach($utoday as $uri => $times)
	{
		echo "<tr>";
		echo "<td>";
		echo "<a href='$uri'><img src='".$icon[$uri]."' /></a>";
		echo "</td>";
		echo "<td>";
		echo "<a href='$uri'>".$name[$uri]."</a>";
		echo "</td>";
		echo "<td>";
		echo $nextopen[$uri];
		foreach($times as $time)
		{
			echo $time;
		}
		echo "</td>";
		echo "</tr>";
		//echo $name[$uri]."<br />";
	}
	echo "</table>";
}
echo "<h2>No known future opening (".count($utodaynf).")</h2>";
echo "<table>";
echo "<tr><th /><th>Name</th><th>Last known open</th></tr>";
foreach($utodaynf as $uri => $times)
{
	echo "<tr>";
	echo "<td>";
	echo "<a href='$uri'><img src='".$icon[$uri]."' /></a>";
	echo "</td>";
	echo "<td>";
	echo "<a href='$uri'>".$name[$uri]."</a>";
	echo "</td>";
	echo "<td>";
	echo $prevopen[$uri];
	foreach($times as $time)
	{
		echo $time;
	}
	echo "</td>";
	echo "</tr>";
	//echo $name[$uri]."<br />";
}
echo "</table>";

	function processOpeningTimes($allopen, $uri)
	{
		global $now;
		global $opening;
		global $noopening;
		$weekday = array('Monday', 'Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday');
		//echo "<table>";
		//echo "<tr>";
		//echo "<td />";
		foreach($weekday as $day)
		{
		//	echo "<th>$day</th>";
		}
		//echo "</tr>";
		if(count($allopen) > 0)
		{
			foreach($allopen as $point)
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
			/*
			foreach($weekday as $day)
			{
				$short_day = substr($day, 0,3); 
			}
			*/
			$sortvalid = array_keys($ot);
			sort($sortvalid);

			foreach($sortvalid as $valid)
			{
				$otv = $ot[$valid];

				list($from, $to) = explode('-',$valid);
				if ($from == '')
				{
					$from = mktime(0,0,0,1,1,2011);
				}
				else
				{
					$from = mktime(0,0,0,substr($from,3,2),substr($from,0,2),substr($from,7,4));
				}
				if ($to == '')
				{
					$to = mktime(0,0,0,1,1,2012);
				}
				else
				{
					$to = mktime(0,0,0,substr($to,3,2),substr($to,0,2),substr($to,7,4));
				} 

		//		echo "<tr><td>";
				for($i = $from; $i < $to; $i += (60*60*24))
				{
					$day = date('l', $i);
					$noopening[$uri][date('Y/m/d', $i)] = true;
					if(array_key_exists('http://purl.org/goodrelations/v1#'.$day, $otv))
					{
						foreach($otv['http://purl.org/goodrelations/v1#'.$day] as $dot)
						{
							$opening[$uri][date('Y/m/d', $i)][] = $dot;
						}
					}
				}
		//		echo "</td></tr>";
				$current = ($from <=  $now )&&( $to >= $now);
				$current = true;
		//		echo "<tr>";
		//		echo "<td>";
		//		if($valid == '-')
		//			echo 'always';
		//		else
		//			echo $valid;
		//		echo "</td>";
				if ($current)
				{ 
					foreach($weekday as $day)
					{
						$dots = array();
		//				echo "<td>";
						if(array_key_exists('http://purl.org/goodrelations/v1#'.$day, $otv))
						{
							foreach($otv['http://purl.org/goodrelations/v1#'.$day] as $dot)
							{
								if($dot == '00:00-00:00')
									$dot = '24 hour';
								$dots[] = $dot;
								if($day == date('l', $now))
								{
									$todayopening[] = "<li>$dot</li>";
								}
							}
							sort($dots);
		//					echo implode('<br />', $dots);
						}
		//				echo "</td>";
					}
				}
			}

			if(false && $todayopening != null)
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
		//echo "</table>";
	}
