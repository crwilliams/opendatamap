<?
if(file_exists('config.local.php'))
{
	include 'config.local.php';
	return;
}
$config['Site title'] = "Map";
$config['Site keywords'] = "map,interactive";
$config['Site description'] = "Interactive Map";
$config['default lat'] = 50.9355;
$config['default long'] = -1.39595;
$config['default zoom'] = 17;
$config['datasource'] = 'sqldemo';
?>