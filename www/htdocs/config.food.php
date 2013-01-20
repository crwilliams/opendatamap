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
$config['selection_required'] = true;
for($i = 0; $i <= 5; $i++)
{
	$config['categories']['fhrs_'.$i.'_en-gb'] = 'Food Hygiene Rating: '.$i;
}

foreach(glob('/home/opendatamap/FHRS/*.xml') as $version)
{
	$version = str_replace(array('/home/opendatamap/FHRS/', '.xml'), '', $version);
	$placename = str_replace('_', ' ', $version);
	$config['versions'][$version]['datafile'] = $version;
	$config['versions'][$version]['Site title'] = "Food Hygiene Map for ".$placename;
	$config['versions'][$version]['Site subtitle'] = $placename;
	$config['versions'][$version]['Site description'] = "Interactive map showing food establishments in ".$placename." and their hygiene ratings";
	$config['versions'][$version]['Site keywords'] = "Food Hygiene,map,interactive,".$placename;
	$config['versions'][$version]['hidden'] = true;
}

if(isset($config['versions'][$versionparts[1]]))
{
	foreach($config['versions'][$versionparts[1]] as $key => $value)
	{
		$config[$key] = $value;
	}
}
?>
