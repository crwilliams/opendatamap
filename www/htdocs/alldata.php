<?php
error_reporting(0);
include_once "config.php";
include_once $config['datasource'].".php";

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
	echo '["'.$point['pos'].'",'.$point['lat'].','.$point['long'].',"'.$point['poslabel'].'","'.$point['icon'].'"],';
}
//End response (including empty element for convenience (required))
echo "[]]";
?>
