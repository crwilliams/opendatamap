<?php
include 'functions.inc.php';
require_once('/home/opendatamap/mysql.inc.php');
outputHeader("Map list for USERNAME", "", "GENERIC", true, true);
?>
	<h3>Your maps</h3>
<?php
$params[] = mysql_real_escape_string($_SESSION['username']);
$q = 'SELECT mapid, name FROM maps WHERE username = \''.$params[0].'\' order by `name`';
$res = mysql_query($q);
echo '<ul>';
while($row = mysql_fetch_assoc($res))
{
	echo '<li>'.$row['name'];
	echo ' <a href=\''.$_SESSION['username'].'/'.$row['mapid'].'\'>(View RDF)</a>';
	echo ' <a href=\''.$_SESSION['username'].'/'.$row['mapid'].'/edit\'>(Edit)</a>';
	echo '</li>';
}
echo '</ul>';
?>
	<a href='new'>Add map</a>
<?php

outputFooter();
?>

