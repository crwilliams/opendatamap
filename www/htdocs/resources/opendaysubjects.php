<div id='day'><a title='Show Friday&apos;s events (8th July 2011)' id='link_2011-07-08' href="#friday">Friday</a><a title='Show Saturday&apos;s events (9th July 2011)' id='link_2011-07-09' href="#saturday">Saturday</a></div>
<div id='selectedsubject'>Choose a subject:</div>
<div id='subjects'>
<ul style='overflow:scroll; position:absolute; top:100px; bottom:0px; width:300px;'>
<?
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

	foreach(SouthamptonopendayDataSource::getAllTimetables() as $timetableevent)
	{
	//	print_r($timetableevent);
		$subjname[(string)$timetableevent['uri']] = (string)$timetableevent['label'];
		$subjbroader[(string)$timetableevent['uri']] = (string)$timetableevent['broader'];
		$timetable[(string)$timetableevent['uri']][substr((string)$timetableevent['start'], 0, 10)][md5($timetableevent['desc'])][] = $timetableevent;
	}
	//print_r($timetable);
	foreach($subjname as $uri => $name)
	{
		$short = str_replace('http://id.southampton.ac.uk/opendays/2011/07/subject/', '', $uri);
		if($subjbroader[$uri] == 'Subject')
			echo "<li class='".$subjbroader[$uri]." subj_".$short."'><h2 id='subj_".$short."' class='clickable' onclick='setInputBox(\"".$short."\"+(location.hash.replace(/^#/, \"/\"))); chooseSubject(\"".$name."\"); updateFunc();' title='Select ".htmlspecialchars($name, ENT_QUOTES)."'>".$name."</h2>";
		else
			echo "<li style='display:none' class='".str_replace(' ', '', $subjbroader[$uri])."'><h2>".$name."</h2>";
		foreach(array('2011-07-08', '2011-07-09') as $date)
		{
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
					foreach($placeinstances as $placeinstance)
					{
						echo $placeinstance." ";
					}
					echo "<div class='clickable' onclick='zoomTo(\"".$sites[$place]."\", false, true); zoomTo(\"".$buildings[$place]."\", true, false)' title='Jump to ".htmlspecialchars($names[$place], ENT_QUOTES)."'><img class='icon' src='resources/numbericon.php?n=".$numbers[$place]."' /><span class='location' style='font-style:italic'>".$place."</span></div>";
				}
				echo $eventinstances[0]['desc']."<br/>";
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
