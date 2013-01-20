<?
error_reporting(E_ALL);
$config['Site title'] = "Food Hygiene Map";
$config['Site keywords'] = "Food Hygiene,map,interactive";
$config['Site description'] = "Interactive map showing food establishments and their hygiene ratings";
$config['default lat'] = 52.9355;
$config['default long'] = -1.39595;
$config['default zoom'] = -6;
$config['datasource'] = array('food');
$config['enabled'] = array('search', 'geobutton', 'toggleicons');
$config['categories'] = array();
for($i = 0; $i <= 5; $i++)
{
	$config['categories']['fhrs_'.$i.'_en-gb'] = 'Food Hygiene Rating: '.$i;
}
?>
