<?php
include_once "config.php";

header('Content-Type: application/json');

// This script should return details of the markers (and terms) that match the query (provided in $_GET['q']).
// It should only return those markers which belong to the categories specified in $_GET['ec'].

$q = $_GET['q'];
//getQueryTerm();
$cats = getEnabledCategories();

function getEnabledCategories()
{
	if($_GET['ec'] == "")
	{
		return array('Transportation','Restaurants-and-Hotels','Offices','Culture-and-Entertainment', 'Health', 'Tourism', 'Stores', 'Education', 'Sports');
	}
	else
	{
		return explode(',', $_GET['ec']);
	}
}

list($pos, $label, $type, $url, $icon) = getAllMatches($q, $cats);

//Begin response
echo '[';
//First array, IDs of matched markers
echo '[';
foreach (array_keys($pos) as $x)
{
	//Each entry in the first array is a string (the ID of a matched marker)
	echo '"'.$x.'",';
}
//End of first array, empty element for convenience (required)
echo '[]],';

//Second array, matched entries for search drop-down box
echo '[';
foreach (array_keys($label) as $x)
{
	//Each entry in the second array is an array containing the following data:
	// * String of matched entry
	// * Type of matched entry
	//If the entry has a related marker, the array can also contain:
	// * ID of marker (may be null)
	// * URL to marker icon (optional)
	echo '["'.$x.'","'.$type[$x].'"';
	if($type[$x] == 'building' || $type[$x] == 'site' || $type[$x] == 'bus-stop' || $type[$x] == 'point-of-service' || $type[$x] == 'workstation' || $type[$x] == 'postcode' )
	{
		echo ',';
		if($url[$x] == null)
			echo 'null';
		else
			echo '"'.$url[$x].'"';

		if(isset($icon[$x]))
			echo ',"'.$icon[$x].'"';
	}
	echo '],';
}
//End of first array, empty element for convenience (required)
echo '[]]';
//End response
echo ']';

function getAllMatches($q, $cats)
{
	global $config;
	
	$labellimit = 100;

	$pos = array();
	$label = array();
	$type = array();
	$url = array();
	$icon = array();

	foreach($config['datasource'] as $ds)
	{
		$dsclass = ucwords($ds).'DataSource';
		list($npos, $nlabel, $ntype, $nurl, $nicon) = call_user_func(array($dsclass, 'getEntries'), $q, $cats);
		foreach($npos as $k => $v)
			$pos[$k] += $v;
		foreach($nlabel as $k => $v)
			$label[$k] += $v;
		foreach($ntype as $k => $v)
			$type[$k] = $v;
		foreach($nurl as $k => $v)
			$url[$k] = $v;
		foreach($nicon as $k => $v)
			$icon[$k] = $v;
	}
	
	arsort($label);
	if(count($label) > $labellimit)
		$label = array_slice($label, 0,$labellimit);

	return array($pos, $label, $type, $url, $icon);
}
?>
