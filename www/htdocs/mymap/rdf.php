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
	if($base == '')
		$base = "http://opendatamap.ecs.soton.ac.uk/mymap/".$_GET['u']."/".$_GET['m']."#";
	header('Content-Type: text/xml');
	echo '<?xml version="1.0" encoding="UTF-8"?>';
}
else
{
	header('HTTP/1.0 404 Not Found');
	die('Map not found.');
}
?>
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
  xmlns:oo="http://purl.org/openorg/"
  xmlns:geo="http://www.w3.org/2003/01/geo/wgs84_pos#"
  xmlns:dct="http://purl.org/dc/terms/"
  xmlns:cc="http://creativecommons.org/ns"
  xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#">
  <rdf:Description rdf:about="http://opendatamap.ecs.soton.ac.uk/mymap/<?php echo $_GET['u'] ?>/<?php echo $_GET['m'] ?>">
    <dct:title><?php echo $name ?></dct:title>
    <oo:corrections><?php echo $corrections ?></oo:corrections>
    <dct:license rdf:resource="http://creativecommons.org/licenses/by-sa/3.0/" />
    <cc:attributionName>Ordnance Survey</cc:attributionName>
    <cc:attributionURL rdf:resource="http://www.ordnancesurvey.co.uk/opendata/licence" />
<?php /*
    <dct:modified>2010-01-12</dct:modified>
*/ ?>
  </rdf:Description>
<?php
$q = 'SELECT uri, name, icon, lat, lon FROM mappoints WHERE username = \''.$params[0].'\' AND map = \''.$params[1].'\' order by `name`';
$res = mysql_query($q);
while($row = mysql_fetch_assoc($res))
{
?>
  <rdf:Description rdf:about="<?php echo $base.$row['uri'] ?>">
    <rdfs:label><?php echo $row['name'] ?></rdfs:label>
    <oo:mapIcon rdf:resource="<?php echo $row['icon'] ?>" />
    <geo:lat rdf:datatype="http://www.w3.org/2001/XMLSchema#float"><?php echo $row['lat'] ?></geo:lat>
    <geo:long rdf:datatype="http://www.w3.org/2001/XMLSchema#float"><?php echo $row['lon'] ?></geo:long>
  </rdf:Description>
<?php
}
?>
</rdf:RDF>
