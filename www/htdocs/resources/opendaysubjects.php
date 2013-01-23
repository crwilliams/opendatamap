<div id='day'><?php
foreach(OpendayDataSource::$dates as $d)
{
	$d = strtotime($d);
	echo "<a title='Show ".date('l', $d)."&apos;s events (".date('jS F Y', $d).")' id='link_".date('Y-m-d', $d)."' onclick=\"updateHash('day', '".strtolower(date('l', $d))."')\">".date('l', $d)."</a>";
}
?></div>
<div id='selectedsubject'>Choose a subject:</div>
<div id='subjects'>
<ul style='overflow:scroll; position:absolute; top:100px; bottom:0px; width:300px;'>
<?php
function sortinstances($a, $b)
{
	$astart = '24:00';
	foreach($a as $ac)
		$astart = min(substr($ac['start'], 11, 5), $astart);
	$bstart = '24:00';
	foreach($b as $bc)
		$bstart = min(substr($bc['start'], 11, 5), $bstart);
	if($astart != $bstart)
		return ($astart < $bstart) ? -1 : 1;
	return 0;
}
function sortdate($a, $b)
{
	$astart = '24:00';
	foreach($a as $ac)
		$astart = min(substr($ac['start'], 11, 5), $astart);
	$bstart = '24:00';
	foreach($b as $bc)
		$bstart = min(substr($bc['start'], 11, 5), $bstart);
	if($astart != $bstart)
		return ($astart < $bstart) ? -1 : 1;
	
	$aend = '00:00';
	foreach($a as $ac)
		$aend = max(substr($ac['end'], 11, 5), $aend);
	$bend = '00:00';
	foreach($b as $bc)
		$bend = min(substr($bc['end'], 11, 5), $bend);
	if($aend != $bend)
		return ($aend < $bend) ? -1 : 1;
	
	if(count($a) != $count($b))
		return (count($a) < count($b)) ? -1 : 1;

	if($a[0]['desc'] != $b[0]['desc'])
		return ($a[0]['desc'] < $b[0]['desc']) ? -1 : 1;
	
	return 0;
}

	foreach(OpendayDataSource::getAllTimetables() as $timetableevent)
	{
	//	print_r($timetableevent);
		$subjname[(string)$timetableevent['uri']] = (string)$timetableevent['label'];
		$subjbroader[(string)$timetableevent['uri']] = (string)$timetableevent['broader'];
		$timetable[(string)$timetableevent['uri']][substr((string)$timetableevent['start'], 0, 10)][md5($timetableevent['desc'])][] = $timetableevent;
	}
	//print_r($timetable);
	foreach($subjname as $uri => $name)
	{
		$short = str_replace('http://id.southampton.ac.uk/opendays/'.date('Y/m', strtotime(OpendayDataSource::$dates[0])).'/subject/', '', $uri);
		if($subjbroader[$uri] == 'Subject')
			echo "<li class='".$subjbroader[$uri]." subj_".$short."'><h2 id='subj_".$short."' class='clickable' onclick='chooseSubject(\"".$name."\"); updateHash(\"subject\", \"".$short."\")' title='Select ".htmlspecialchars($name, ENT_QUOTES)."'>".$name."</h2>";
		else
			echo "<li style='display:none' class='".str_replace(' ', '', $subjbroader[$uri])."'><h2>".$name."</h2>";
		foreach(OpendayDataSource::$dates as $date)
		{
			$date = str_replace('/', '-', $date);
			echo "<div class='_$date'>";
			$events = $timetable[$uri][$date];
			usort($events, 'sortdate');
			echo "<ul>";
			foreach($events as $md5 => $eventinstances)
			{
				$instances = array();
				$buildings = array();
				$sites = array();
				$numbers = array();
				$names = array();
				if($subjbroader[$uri] == 'Subject')
					echo "<li class='event' style='display:none'>";
				else
					echo "<li class='event'>";
				foreach($eventinstances as $eventinstance)
				{
					$instances[$eventinstance['placelabel']][] = substr($eventinstance['start'], 11, 5)."-".substr($eventinstance['end'], 11, 5);
					$buildings[$eventinstance['placelabel']] = $eventinstance['building'];
					$sites[$eventinstance['placelabel']] = $eventinstance['site'];
					$numbers[$eventinstance['placelabel']] = $eventinstance['number'];
					$names[$eventinstance['placelabel']] = $eventinstance['name'];
				}
				//usort($instances, 'sortinstances');
				foreach($instances as $place => $placeinstances)
				{
	// onclick='zoomTo(\"".$event['building']."\")'>";
					sort($placeinstances);
					echo "<div class='dates'>"; 
					foreach($placeinstances as $placeinstance)
					{
						echo $placeinstance." ";
					}
					echo "</div>";
					echo "<div class='location clickable' onclick='zoomTo(\"".$sites[$place]."\", false, true); zoomTo(\"".$buildings[$place]."\", true, false)' title='Jump to ".htmlspecialchars($names[$place], ENT_QUOTES)."'>";
					echo "<img class='icon' src='resources/numbericon.php?n=".$numbers[$place]."' />";
					echo $place;
					echo "</div>";
				}
				echo "<div class='description'>"; 
				echo $eventinstances[0]['desc']."<br/>";
				echo "</div>";
				echo "</li>";
			}
			echo "</ul>";
			echo "</div>";
		}
		echo "</li>";
	}
?>
</ul>
</div>
