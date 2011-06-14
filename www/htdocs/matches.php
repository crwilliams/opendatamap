<?php
error_reporting(0);
include_once "config.php"
include_once $config['datasource'].".php";

// This script should return details of the markers (and terms) that match the query (provided in $_GET['q']).
// It should only return those markers which belong to the categories specified in $_GET['ec'].

$q = getQueryTerm();
$cats = getEnabledCategories();

function getQueryTerm()
{
	$q = trim($_GET['q']);
	$q = str_replace("\\", "\\\\\\\\\\\\\\", $q);
	return $q;
}

function getEnabledCategories()
{
	if($_GET['ec'] == "")
	{
		return array('Transport','Catering','Services','Entertainment', 'Health', 'Religion', 'Retail', 'Education', 'General');
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
?>
