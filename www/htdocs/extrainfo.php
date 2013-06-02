<?php
include_once "config.php";

header('Content-Type: application/json');

// This script should return info on all of the markers that can be visible on the map.

$update = isset($_GET['update']);
$extraInfo = getExtraInfo($update);

//Begin response
echo "[";
//Each entry in the response is an array containing the following data:
// * ID of marker
// * Info of marker
// * Hash of marker
if($update) {
	foreach($extraInfo as $info) {
		echo '["'.md5($info['id']).'","'.str_replace(array('"', '\\'), array('\"', '\\\\'), $info['extra']).'"],';
	}
} else {
	foreach($extraInfo as $info) {
		echo '["'.$info['id'].'","'.str_replace(array('"', '\\'), array('\"', '\\\\'), $info['extra']).'","'.md5($info['id']).'"],';
	}
}
//End response (including empty element for convenience (required))
echo "[]]";
?>
