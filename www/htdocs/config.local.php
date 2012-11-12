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
	$config['datasource'] = array('southamptoncached', 'postcode');
	$_GET['ec'] = 'Restaurants-and-Hotels';
	$config['unused-datasets'] = array('http://id.southampton.ac.uk/dataset/bus-routes', 'http://id.southampton.ac.uk/dataset/bus-stops', 'http://id.southampton.ac.uk/dataset/room-features', 'http://id.southampton.ac.uk/dataset/wifi', 'http://data.ordnancesurvey.co.uk');
}

if($versionparts[1] == 'isolutions-wifi')
{
	$config['Site title'] = "iSolutions WiFi map";
	$config['enabled'] = array('geobutton');
	$config['datasource'] = array('southamptoncached');
	$_GET['q'] = 'isolutions wi-fi';
	$config['unused-datasets'] = array('http://id.southampton.ac.uk/dataset/amenities', 'http://id.southampton.ac.uk/dataset/bus-routes', 'http://id.southampton.ac.uk/dataset/bus-stops', 'http://id.southampton.ac.uk/dataset/room-features', 'http://id.southampton.ac.uk/dataset/catering', 'http://data.ordnancesurvey.co.uk');
}
?>
