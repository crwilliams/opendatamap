<?
include 'import.php';
error_reporting(E_ERROR);
include '/home/opendatamap/mysql-pdo.inc.php';

$queuedQueries = array();
//runQuery('BEGIN TRANSACTION');
try
{
	$tables = array('matches', 'places', 'points');
	foreach($tables as $t)
	{
		queueQuery($dbh->prepare('TRUNCATE '.$t));
	}

	$stmt = $dbh->prepare('INSERT INTO points (uri, lat, lng, label, icon, category, extra) VALUES (?, ?, ?, ?, ?, ?, ?)');
	foreach(SouthamptonDataSource::getAll() as $d)
	{
		queueQuery($stmt, array($d['id'], $d['lat'], $d['lng'], $d['label'], $d['icon'], getCategory($d['icon']), $d['extra']));
	}

	$stmt = $dbh->prepare('INSERT INTO matches (uri, poslabel, label, icon, type, category) VALUES (?, ?, ?, ?, ?, ?)');
	foreach(SouthamptonDataSource::getAllOfferings() as $d)
	{	
		if(is_null($d['label']))
		{
			$d['label'] = '';
		}
		queueQuery($stmt, array($d['pos'], $d['poslabel'], $d['label'], $d['icon'], $d['type'], getCategory($d['icon'])));
	}

	$stmt = $dbh->prepare('INSERT INTO places (uri, name, num, type, outline, lat, lng) VALUES (?, ?, ?, ?, ?, ?, ?)');
	foreach(SouthamptonDataSource::getAllPlaces() as $d)
	{
		queueQuery($stmt, array($d['uri'], $d['name'], $d['num'], $d['type'], $d['outline'], $d['lat'], $d['lng']));
	}

	$equivs[] = array('Wi-Fi Access', 'Wifi Internet Access');
	$equivs[] = array('Wi-Fi Access', 'Wireless Internet Access');
	$equivs[] = array('Wi-Fi Access', 'eduroam');
	$equivs[] = array('iSolutions Workstations', 'Computer Use');
	
	$stmt = $dbh->prepare('INSERT INTO matches (uri, poslabel, label, icon, type, category) SELECT uri, poslabel, ? AS label, icon, type, category FROM matches WHERE label = ?');
	foreach($equivs as $equiv)
	{
		queueQuery($stmt, array($equiv[1], $equiv[0]));
	}
	
	queueQuery($dbh->prepare('DELETE FROM points WHERE uri LIKE "http://id.southampton.ac.uk/vending-machine/%"'));
	queueQuery($dbh->prepare('DELETE FROM matches WHERE uri LIKE "http://id.southampton.ac.uk/vending-machine/%"'));
	queueQuery($dbh->prepare('UPDATE points SET icon = "http://data.southampton.ac.uk/map-icons/Stores/conveniencestore.png" WHERE icon = "http://data.southampton.ac.uk/map-icons/Stores/convenience.png"'));
	queueQuery($dbh->prepare('UPDATE matches SET icon = "http://data.southampton.ac.uk/map-icons/Stores/conveniencestore.png" WHERE icon = "http://data.southampton.ac.uk/map-icons/Stores/convenience.png"'));
	queueQuery($dbh->prepare('UPDATE matches SET type = "workstation" WHERE icon = "http://data.southampton.ac.uk/map-icons/Education/computers.png"'));
	queueQuery($dbh->prepare('UPDATE points SET icon = CONCAT("http://opendatamap.ecs.soton.ac.uk/resources/workstationicon/", uri) WHERE icon = "http://data.southampton.ac.uk/map-icons/Education/computers.png"'));
	queueQuery($dbh->prepare('UPDATE matches SET icon = CONCAT("http://opendatamap.ecs.soton.ac.uk/resources/workstationicon/", uri) WHERE icon = "http://data.southampton.ac.uk/map-icons/Education/computers.png"'));
	queueQuery($dbh->prepare('UPDATE matches SET icon = "http://opendatamap.ecs.soton.ac.uk/resources/busicon/" WHERE icon = "http://google-maps-icons.googlecode.com/files/bus.png"'));
	//queueQuery('COMMIT TRANSACTION');
	runQueuedQueries();
}
catch (SparqlException $ex)
{
	echo $ex;
}





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
	elseif(substr($category[4], 0, 15) == 'workstationicon')
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

function queueQuery($stmt, $p = array())
{
	global $queuedQueries;
	$queuedQueries[] = array('stmt' => $stmt, 'p' => $p);
}

function runQueuedQueries()
{
	global $queuedQueries;
	foreach($queuedQueries as $q)
	{
		runQuery($q['stmt'], $q['p']);
	}
}

function runQuery($stmt, $p)
{
	if(!$stmt->execute($p))
	{
//		echo $stmt;
		print_r($p);
		print_r($stmt->errorInfo());
	}
}

?>

