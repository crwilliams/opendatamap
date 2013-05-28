<?
include 'import.php';
error_reporting(E_ERROR);
include '/home/opendatamap/mysql-pdo.inc.php';

//runQuery('BEGIN TRANSACTION');
runQuery($dbh->prepare('TRUNCATE matches'));
runQuery($dbh->prepare('TRUNCATE places'));
runQuery($dbh->prepare('TRUNCATE points'));

$stmt = $dbh->prepare('INSERT INTO points (uri, lat, lng, label, icon, category) VALUES (?, ?, ?, ?, ?, ?)');
foreach(SouthamptonDataSource::getAll() as $d)
{	
	runQuery($stmt, array($d['id'], $d['lat'], $d['lng'], $d['label'], $d['icon'], getCategory($d['icon'])));
}

$stmt = $dbh->prepare('INSERT INTO matches (uri, poslabel, label, icon, type, category) VALUES (?, ?, ?, ?, ?, ?)');
foreach(SouthamptonDataSource::getAllOfferings() as $d)
{	
	if(is_null($d['label']))
	{
		$d['label'] = '';
	}
	runQuery($stmt, array($d['pos'], $d['poslabel'], $d['label'], $d['icon'], $d['type'], getCategory($d['icon'])));
}

$stmt = $dbh->prepare('INSERT INTO places (uri, name, num, type, outline, lat, lng) VALUES (?, ?, ?, ?, ?, ?, ?)');
foreach(SouthamptonDataSource::getAllPlaces() as $d)
{
	runQuery($stmt, array($d['uri'], $d['name'], $d['num'], $d['type'], $d['outline'], $d['lat'], $d['lng']));
}

$equivs[] = array('Wi-Fi Access', 'Wifi Internet Access');
$equivs[] = array('Wi-Fi Access', 'Wireless Internet Access');
$equivs[] = array('Wi-Fi Access', 'eduroam');
$equivs[] = array('iSolutions Workstations', 'Computer Use');

$stmt = $dbh->prepare('INSERT INTO matches (uri, poslabel, label, icon, type, category) SELECT uri, poslabel, ? AS label, icon, type, category FROM matches WHERE label = ?');
foreach($equivs as $equiv)
{
	runQuery($stmt, array($equiv[1], $equiv[0]));
}

runQuery($dbh->prepare('DELETE FROM points WHERE uri LIKE "http://id.southampton.ac.uk/vending-machine/%"'));
runQuery($dbh->prepare('DELETE FROM matches WHERE uri LIKE "http://id.southampton.ac.uk/vending-machine/%"'));
runQuery($dbh->prepare('UPDATE points SET icon = "http://data.southampton.ac.uk/map-icons/Stores/conveniencestore.png" WHERE icon = "http://data.southampton.ac.uk/map-icons/Stores/convenience.png"'));
runQuery($dbh->prepare('UPDATE matches SET icon = "http://data.southampton.ac.uk/map-icons/Stores/conveniencestore.png" WHERE icon = "http://data.southampton.ac.uk/map-icons/Stores/convenience.png"'));
runQuery($dbh->prepare('UPDATE matches SET type = "workstation" WHERE icon = "http://data.southampton.ac.uk/map-icons/Education/computers.png"'));
runQuery($dbh->prepare('UPDATE points SET icon = CONCAT("http://opendatamap.ecs.soton.ac.uk/resources/workstationicon.php?pos=", uri) WHERE icon = "http://data.southampton.ac.uk/map-icons/Education/computers.png"'));
runQuery($dbh->prepare('UPDATE matches SET icon = CONCAT("http://opendatamap.ecs.soton.ac.uk/resources/workstationicon.php?pos=", uri) WHERE icon = "http://data.southampton.ac.uk/map-icons/Education/computers.png"'));
runQuery($dbh->prepare('UPDATE matches SET icon = "http://opendatamap.ecs.soton.ac.uk/resources/busicon.php" WHERE icon = "http://google-maps-icons.googlecode.com/files/bus.png"'));
//runQuery('COMMIT TRANSACTION');

function getCategory($icon)
{
	if(is_null($icon))
	{
		return null;
	}
	$category = explode('/', $icon);
	if($category[3] == 'map-icons')
	{
		return $category[4];
	}
	elseif(substr($category[4], 0, 7) == 'busicon')
	{
		return 'Transportation';
	}
	elseif(substr($category[4], 0, 20) == 'workstationicon.php?')
	{
		return 'Education';
	}
	elseif($category[3] == 'img' && $category[4] == 'icon')
	{
		return $category[5];
	}
	else
	{
		return '';
	}
}

function runQuery($stmt, $p = array())
{
	if(!$stmt->execute($p))
	{
//		echo $stmt;
		print_r($p);
		print_r($stmt->errorInfo());
	}
}

?>

