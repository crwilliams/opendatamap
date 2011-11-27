<?php
require_once('/home/opendatamap/mysql.inc.php');
$params[] = mysql_real_escape_string($_GET['u']);
$params[] = mysql_real_escape_string($_GET['m']);
$q = 'SELECT name, username as corrections, base FROM maps WHERE username = \''.$params[0].'\' AND mapid = \''.$params[1].'\'';
$res = mysql_query($q);
if($row = mysql_fetch_assoc($res))
{
	$name = $row['name'];
	$corrections = $row['corrections'];
	$base = $row['base'];
	header('Content-Type: application/vnd.google-earth.kml+xml');
	echo '<?xml version="1.0" encoding="UTF-8"?>';
}
else
{
	header('HTTP/1.0 404 Not Found');
	die('Map not found.');
}
?>
<kml xmlns="http://www.opengis.net/kml/2.2">
  <Document>
    <name><?php echo $name ?></name>
<?php /*
  <rdf:Description rdf:about="http://opendatamap.ecs.soton.ac.uk/mymap/<?php echo $_GET['u'] ?>/<?php echo $_GET['m'] ?>">
    <oo:corrections><?php echo $corrections ?></oo:corrections>
    <dct:license rdf:resource="http://creativecommons.org/licenses/by-sa/3.0/" />
    <cc:attributionName>Ordnance Survey</cc:attributionName>
    <cc:attributionURL rdf:resource="http://www.ordnancesurvey.co.uk/opendata/licence" />
    <dct:modified>2010-01-12</dct:modified>
  </rdf:Description>
*/ ?>
<?php
$q = 'SELECT uri, md5(uri) as name, lat, lon FROM mappoints WHERE username = \''.$params[0].'\' AND map = \''.$params[1].'\' order by `name`';
$res = mysql_query($q);
while($row = mysql_fetch_assoc($res))
{
?>
    <Placemark>
<?php /*
      <name><?php echo $row['name'] ?></name>
*/ ?>
      <Point>
        <coordinates><?php echo $row['lon'] ?>,<?php echo $row['lat'] ?></coordinates>
      </Point>
    </Placemark>
<?php
}
?>
  </Document>
</kml>
