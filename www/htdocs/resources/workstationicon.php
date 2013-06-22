<?php
set_include_path('../');
require 'config.php';
if(substr($_GET['pos'], 0, 3) == 'ws:')
{
	$_GET['pos'] = 'http://id.southampton.ac.uk/point-of-service/'.substr($_GET['pos'], 3);
}
$hash = md5($_GET['pos']);
$filename = 'cache/wi_'.$hash.'.png';
if(true || !file_exists($filename))
{
	$pos = $_GET['pos'];
	if(is_array($pos))
	{
		$n = 0;
		foreach($pos as $p)
		{
			$n += SouthamptonDataSource::getFreeSeats($p);
		}
	}
	else
	{
		$n = SouthamptonDataSource::getFreeSeats($pos);
	}
	$oimg = imagecreatefrompng("../img/icon/Education/computers.png");
	$white = imagecolorallocate($oimg, 0, 0, 0);
	$fs = 6;
	$bbox = imagettfbbox($fs, 0, 'ubuntu.ttf', $n);
	$w = $bbox[2] - $bbox[0];
	$h = $bbox[1] - $bbox[7];
	if($fs > 0)
		imagettftext($oimg, $fs, 0, (32-$w)/2, ((24-$h)/2)-$bbox[7] - 1, $white, 'ubuntu.ttf', $n) or die('foo');
	imagesavealpha($oimg,true);
	imagepng($oimg, $filename);
}
header('Content-type: image/png');
header('Cache-Control: max-age=60');
fpassthru(fopen($filename, 'r'));
?>
