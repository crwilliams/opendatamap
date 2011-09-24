<?
$_GET['v'] = 'cache';
include 'config.php';
error_reporting(E_ERROR);
include '/home/opendatamap/mysql.inc.php';

//runQuery('BEGIN TRANSACTION');
runQuery('TRUNCATE matches');
runQuery('TRUNCATE places');
runQuery('TRUNCATE points');


$ds = SouthamptonDataSource::getAll();
foreach($ds as $d)
{
	$q = "INSERT INTO points (uri, lat, lng, label, icon, category) VALUES ('".mysql_real_escape_string($d['id'])."', ".((float)$d['lat']).", ".((float)$d['long']).", '".mysql_real_escape_string($d['label'])."', '".mysql_real_escape_string($d['icon'])."', '".mysql_real_escape_string(getCategory($d['icon']))."')";
	runQuery($q);
}

function getCategory($icon)
{
	
		$category = explode('/', $icon);
		if($category[3] == 'map-icons')
		{
			return $category[4];
		}
		elseif(substr($category[4], 0, 12) == 'busicon.php?')
		{
			return 'Transportation';
		}
		else
		{
			return '';
		}
}

function runQuery($q)
{
	if(!mysql_query($q))
	{
		echo "Failed to execute: $q\n";
		echo mysql_error()."\n";
	}
}

//print_r(SouthamptonDataSource::getEntries('', null));
$ds = SouthamptonDataSource::getPointsOfService('');
processMatches($ds, 'point-of-service');
$ds = SouthamptonDataSource::getBusStops('');
processMatches($ds, 'bus-stop');
$ds = SouthamptonDataSource::getWorkstationRooms('');
processMatches($ds, 'workstation');
$ds = SouthamptonDataSource::getISolutionsWifiPoints('');
processMatches($ds, 'workstation');
$ds = SouthamptonDataSource::getShowers('');
processMatches($ds, 'workstation');

function processMatches($ds, $type)
{
	foreach($ds as $d)
	{
		if(!is_null($d['label']) && !is_null($d['icon']))
			$q = "INSERT INTO matches (uri, poslabel, label, icon, type, category) VALUES ('".mysql_real_escape_string($d['pos'])."', '".mysql_real_escape_string($d['poslabel'])."', '".mysql_real_escape_string($d['label'])."', '".mysql_real_escape_string($d['icon'])."', '".mysql_real_escape_string($type)."', '".mysql_real_escape_string(getCategory($d['icon']))."')";
		elseif(!is_null($d['label']))
			$q = "INSERT INTO matches (uri, poslabel, label, type) VALUES ('".mysql_real_escape_string($d['pos'])."', '".mysql_real_escape_string($d['poslabel'])."', '".mysql_real_escape_string($d['label'])."', '".mysql_real_escape_string($type)."')";
		elseif(!is_null($d['icon']))
			$q = "INSERT INTO matches (uri, poslabel, icon, type, category) VALUES ('".mysql_real_escape_string($d['pos'])."', '".mysql_real_escape_string($d['poslabel'])."', '".mysql_real_escape_string($d['icon'])."', '".mysql_real_escape_string($type)."', '".mysql_real_escape_string(getCategory($d['icon']))."')";
		else
			$q = "INSERT INTO matches (uri, poslabel, type) VALUES ('".mysql_real_escape_string($d['pos'])."', '".mysql_real_escape_string($d['poslabel'])."', '".mysql_real_escape_string($type)."')";
		runQuery($q);
	}
}

$ds = SouthamptonDataSource::getBuildings('');
processPlaces($ds, 'building');
$ds = SouthamptonDataSource::getSites('');
processPlaces($ds, 'site');

function processPlaces($ds, $type)
{
	foreach($ds as $d)
	{
		if(is_null($d['number']))
			$q = "INSERT INTO places (uri, name, type) VALUES ('".mysql_real_escape_string($d['url'])."', '".mysql_real_escape_string($d['name'])."', '".mysql_real_escape_string($type)."')";
		else
			$q = "INSERT INTO places (uri, name, num, type) VALUES ('".mysql_real_escape_string($d['url'])."', '".mysql_real_escape_string($d['name'])."', '".mysql_real_escape_string($d['number'])."', '".mysql_real_escape_string($type)."')";
		runQuery($q);
	}
}

function process($ds)
{
	$c = array();
	foreach($ds as $d)
	{
		foreach(array_keys($d) as $k)
		{
			$c[$k]++;
		}
	}
	print_r($c);
}

runQuery('DELETE FROM points WHERE uri LIKE "http://id.southampton.ac.uk/vending-machine/%"');
runQuery('DELETE FROM matches WHERE uri LIKE "http://id.southampton.ac.uk/vending-machine/%"');
runQuery('UPDATE points SET icon = "http://data.southampton.ac.uk/map-icons/Stores/conveniencestore.png" where icon = "http://data.southampton.ac.uk/map-icons/Stores/convenience.png"');
runQuery('UPDATE matches SET icon = "http://data.southampton.ac.uk/map-icons/Stores/conveniencestore.png" where icon = "http://data.southampton.ac.uk/map-icons/Stores/convenience.png"');
//runQuery('COMMIT TRANSACTION');
?>
