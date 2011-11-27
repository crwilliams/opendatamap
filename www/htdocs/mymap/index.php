<?php
include 'functions.inc.php';
require_once('/home/opendatamap/mysql.inc.php');
$username = "";
$editmode = false;
$header = false;
if(isset($_GET['username']))
{
	$username = $_GET['username'];
	outputHeader("Map list for ".$username, "", "GENERIC", true, false);
	$header = true;
	if(isset($_SESSION['username']) && $username == $_SESSION['username'])
	{
		$editmode = true;
	}
}
else
{
	$editmode = true;
}

if($editmode && !$header)
{
	outputHeader("Map list for USERNAME", "", "GENERIC", true, true);
}
if($editmode)
{
	echo '<h3>Your maps</h3>';
	$username = $_SESSION['username'];
}
else
{
	echo '<h3>'.$username.'&apos;s maps</h3>';
}

$params[] = mysql_real_escape_string($username);
$q = 'SELECT mapid, name FROM maps WHERE username = \''.$params[0].'\' order by `name`';
$res = mysql_query($q);
echo '<ul>';
while($row = mysql_fetch_assoc($res))
{
	echo '<li>'.$row['name'];
	echo ' | <a href=\''.$username.'/'.$row['mapid'].'.rdf\'>(View RDF)</a>';
	echo ' | <a href=\''.$username.'/'.$row['mapid'].'.kml\'>(View KML)</a>';
	if($editmode)
		echo ' | <a href=\''.$username.'/'.$row['mapid'].'/edit\'>(Edit)</a>';
	echo '</li>';
}
echo '</ul>';
if($editmode)
{
?>
	<a href='new'>Add map</a>
<?php
}

outputFooter();
?>

