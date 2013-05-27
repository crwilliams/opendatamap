<?php
set_include_path('../');
require 'config.php';

$hash = md5($_SERVER['QUERY_STRING']);
$filename = 'cache/tn_'.$hash.'.png';
$cachable = true;
if(!file_exists($filename))
{
	$uri = urldecode($_GET['uri']);
	$type = urldecode($_GET['type']);
	$info = getPointInfo($uri);

	if($type == 'SAT')
	{
		$maptype = 'satellite';
	}
	else if($type == 'MAP')
	{
		$maptype = 'roadmap';
	}
	else
	{
		die();
	}
	
	$imageurl = 'http://maps.googleapis.com/maps/api/staticmap?center='.$info['lat'].','.$info['long'].'&zoom=17&size=200x200&markers=icon:'.$info['icon'].'%7C'.$info['lat'].','.$info['long'].'&maptype='.$maptype.'&sensor=false';
	$img = imagecreatefrompng($imageurl);
	if(!$cachable)
	{
		header('Content-type: image/png');
		imagepng($img);
		die();
	}
	imagepng($img, $filename);
}
header('Content-type: image/png');
header('Cache-Control: max-age=3600');
fpassthru(fopen($filename, 'r'));
?>
