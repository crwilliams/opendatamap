<?
$config['Site title'] = "University of Southampton Linked Open Data Map";
$config['Site keywords'] = "University of Southampton,map,Southampton,amenity,bus stop,building,site,campus,interactive";
$config['Site description'] = "Interactive Map of the University of Southampton, generated from Linked Open Data";
$config['default lat'] = 50.9355;
$config['default long'] = -1.39595;
$config['default zoom'] = 17;
$config['datasource'] = array('southamptoncached', 'postcode', 'sucu', /*'oxford', 'cambridge'*/);
$config['prefix'] = "http://id.southampton.ac.uk/";

$config['versions']['embed']['Site title'] = "embed";
$config['versions']['embed']['enabled'] = array('-title');
$config['versions']['embed']['hidden'] = true;

$config['versions']['catering']['Site title'] = "Catering map";
$config['versions']['catering']['enabled'] = array('search', 'geobutton', '-title');
$config['versions']['catering']['datasource'] = array('southamptoncached', 'postcode');
$config['versions']['catering']['unused-datasets'] = array('http://id.southampton.ac.uk/dataset/bus-routes', 'http://id.southampton.ac.uk/dataset/bus-stops', 'http://id.southampton.ac.uk/dataset/room-features', 'http://id.southampton.ac.uk/dataset/wifi', 'http://data.ordnancesurvey.co.uk');

$config['versions']['isolutions-wifi']['Site title'] = "iSolutions WiFi map";
$config['versions']['isolutions-wifi']['enabled'] = array('geobutton', '-title');
$config['versions']['isolutions-wifi']['datasource'] = array('southamptoncached');
$config['versions']['isolutions-wifi']['unused-datasets'] = array('http://id.southampton.ac.uk/dataset/amenities', 'http://id.southampton.ac.uk/dataset/bus-routes', 'http://id.southampton.ac.uk/dataset/bus-stops', 'http://id.southampton.ac.uk/dataset/room-features', 'http://id.southampton.ac.uk/dataset/catering', 'http://data.ordnancesurvey.co.uk');

if(isset($config['versions'][$versionparts[1]]))
{
	foreach($config['versions'][$versionparts[1]] as $key => $value)
	{
		$config[$key] = $value;
	}
}

if($versionparts[1] == 'catering')
{
	$_GET['ec'] = 'Restaurants-and-Hotels';
}

if($versionparts[1] == 'isolutions-wifi')
{
	$_GET['q'] = 'isolutions wi-fi';
}
?>
