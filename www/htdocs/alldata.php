<?php
error_reporting(0);
include_once "config.php";

$html = false;
if(isset($_GET['html']))
	$html = true;

if(!$html)
	header('Content-Type: application/json');

// This script should return info on all of the markers that can be visible on the map.

$points = getAllDataPoints();

//Begin response
if(!$html)
{
	echo "[";
	//Each entry in the response is an array containing the following data:
	// * ID of marker
	// * Latitude of marker
	// * Longitude of marker
	// * Label of marker
	// * URL to marker icon
	foreach($points as $point) {
		echo '["'.$point['id'].'",'.$point['lat'].','.$point['long'].',"'.$point['label'].'","'.$point['icon'].'"],';
	}
	//End response (including empty element for convenience (required))
	echo "[]]";
}
else
{
	foreach($points as $point) {
		$d[$point['icon']][] = $point;
	}
	ksort($d);
	foreach($d as $icon => $points)
	{
		echo '<img src="'.$points[0]['icon'].'" />';
		echo '<ul>';
		foreach($points as $point)
		{
			$cid = $point['id'];
			$cicon = $point['icon'];
			echo '<li><input type="checkbox" id="'.$cid.'" name="'.$cid.'" class="'.$cicon.'" /><label for="'.$cid.'">'.str_replace('\\', '', $point['label']).' (<a href="'.$point['id'].'">link</a>)</label></li>';
		}
		echo '</ul>';
	}
}

function getAllDataPoints()
{
	global $config;

	$points = array();
	foreach($config['datasource'] as $ds)
	{
		$dsclass = ucwords($ds).'DataSource';
		foreach(call_user_func(array($dsclass, 'getAll')) as $point) $points[] = $point;
	}
	return $points;
}
?>
