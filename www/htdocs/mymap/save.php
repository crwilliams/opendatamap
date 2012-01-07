<?
session_start();
require_once('/home/opendatamap/mysql.inc.php');
$items = explode('||', $HTTP_RAW_POST_DATA);
$i = 0;

$username = mysql_real_escape_string($_SESSION['username']);
if($username != $_GET['username'])
{
	header("HTTP/1.0 403 Forbidden");
	echo "Failed to save."
	die();
}
$map = mysql_real_escape_string($_GET['map']);

foreach($items as $item)
{
	if(trim($item) == "")
		continue;
	$d = explode('|', $item);
	$d[0] = mysql_real_escape_string($d[0]);
	$d[1] = (float)$d[1];
	$d[2] = (float)$d[2];
	$d[3] = mysql_real_escape_string($d[3]);
	$d[4] = mysql_real_escape_string($d[4]);
	$q = 'INSERT INTO mappoints (map, username, uri, lat, lon, source, name, icon) VALUES (\''.$map.'\', \''.$username.'\', \''.$d[0].'\', '.$d[1].', '.$d[2].', \'OS\', \''.$d[3].'\', \''.$d[4].'\') ON DUPLICATE KEY UPDATE lat = '.$d[1].', lon = '.$d[2].', source = \'OS\', name = \''.$d[3].'\', icon = \''.$d[4].'\';';
	mysql_query($q) or die(mysql_error());
	$i++;
}
echo $i . ' locations saved';
?>
