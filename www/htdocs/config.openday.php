<?
switch($versionparts[1])
{
	case 'friday':
		$date = ' | Friday 8th July 2011';
		break;
	case 'saturday':
		$date = ' | Saturday 9th July 2011';
		break;
}

$file = fopen('resources/opendaycourses.csv', 'r');
while($data = fgetcsv($file))
{
	if($data[0] == $versionparts[2])
	{
		$course = $data[1];
		break;
	}
}
fclose($file);

$config['Site title'] = "University of Southampton Open Day Map$date";
if($course)
	$config['Site title'] .= " | $course";
$config['Site keywords'] = "University of Southampton,open day,map,Southampton,amenity,bus stop,building,site,campus,interactive";
$config['Site description'] = "Interactive Open Day Map of the University of Southampton";
$config['default lat'] = 50.9355;
$config['default long'] = -1.39595;
$config['default zoom'] = 13;
//$config['default map'] = "google.maps.MapTypeId.SATELLITE";
$config['datasource'] = array('southamptonopenday', 'postcode', /*'oxford', 'cambridge'*/);
//if(isset($versionparts[2]) && isset($versionparts[3])
//	$q = $versionparts[2].'/'.$;
if($versionparts[1] == 'iframe')
{
	$config['enabled'] = array('opendayhidden', 'bookmarks');
}
else
{
	$config['enabled'] = array('openday', 'bookmarks');
	$config['map style'] = 'left:300px;';
}
?>
