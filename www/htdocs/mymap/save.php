<?
session_start();
require_once('/home/opendatamap/mysql.inc.php');
$items = explode('||', $HTTP_RAW_POST_DATA);
$i = 0;

$username = mysql_real_escape_string($_SESSION['username']);
$map = mysql_real_escape_string($_GET['map']);

foreach($items as $item)
{
	if(trim($item) == "")
		continue;
	$d = explode('|', $item);
	$d[0] = mysql_real_escape_string($d[0]);
	$d[1] = (float)$d[1];
	$d[2] = (float)$d[2];
	$q = 'INSERT INTO mappoints (map, username, uri, lat, lon) VALUES (\''.$map.'\', \''.$username.'\', \''.$d[0].'\', '.$d[1].', '.$d[2].') ON DUPLICATE KEY UPDATE lat = '.$d[1].', lon = '.$d[2].';';
	mysql_query($q) or die(mysql_error());
	$i++;
}
echo $i . ' locations saved';
?>
