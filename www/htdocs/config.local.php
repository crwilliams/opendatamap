<?
$config['Site title'] = "University of Southampton Linked Open Data Map";
$config['Site keywords'] = "University of Southampton,map,Southampton,amenity,bus stop,building,site,campus,interactive";
$config['Site description'] = "Interactive Map of the University of Southampton, generated from Linked Open Data";
$config['default lat'] = 50.9355;
$config['default long'] = -1.39595;
$config['default zoom'] = 17;
$config['datasource'] = array('southamptoncached', 'postcode', 'sucu', /*'oxford', 'cambridge'*/);
$config['prefix'] = "http://id.southampton.ac.uk/";

if($versionparts[1] == 'embed')
{
	$config['Site title'] = "embed";
	$config['enabled'] = array();
}

if($versionparts[1] == 'catering')
{
	$config['Site title'] = "Catering map";
	$config['enabled'] = array('search', 'geobutton');
	$_GET['ec'] = 'Restaurants-and-Hotels';
}

if($versionparts[1] == 'isolutions-wifi')
{
	$config['Site title'] = "iSolutions WiFi map";
	$config['enabled'] = array('geobutton');
	$_GET['q'] = 'isolutions wi-fi';
}
?>
