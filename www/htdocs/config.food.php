<?
error_reporting(E_ALL);
$config['Site title'] = "Food Hygiene Open Data Map";
$config['Site keywords'] = "Food Hygiene,map,interactive";
$config['Site description'] = "Interactive map showing food establishments and their hygiene ratings";
$config['default lat'] = 50.92;
//$config['default long'] = -1.257778;
$config['default zoom'] = 13;
$config['datasource'] = array('food');
$config['enabled'] = array('search', 'geobutton', 'toggleicons');
$config['categories'] = array();
for($i = 0; $i <= 5; $i++)
{
	$config['categories']['fhrs_'.$i.'_en-gb'] = 'Food Hygiene Rating: '.$i;
}
?>
