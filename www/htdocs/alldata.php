<?php
error_reporting(0);
include_once "config.php";

header('Content-Type: application/json');

// This script should return info on all of the markers that can be visible on the map.

$points = getAllDataPoints();

//Begin response
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
