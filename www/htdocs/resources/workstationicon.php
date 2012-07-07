<?php
set_include_path('../');
require 'config.php';
$hash = md5($_SERVER['QUERY_STRING']);
$filename = 'cache/wi_'.$hash.'.png';
if(true || !file_exists($filename))
{
	$pos = $_GET['pos'];
	if(is_array($pos))
	{
		$n = 0;
		foreach($pos as $p)
		{
			$n += SouthamptoncachedDataSource::getFreeSeats($p);
		}
	}
	else
	{
		$n = SouthamptoncachedDataSource::getFreeSeats($pos);
	}
	$oimg = imagecreatefrompng("../img/icon/Education/computers.png");
	$white = imagecolorallocate($oimg, 0, 0, 0);
/*
	switch(strlen($n))
	{
		case 1:
			$fs = 20;
			break;
		case 2:
			$fs = 15;
			break;
		case 3:
			$fs = 10;
			break;
		case 4:
			$fs = 8;
			break;
		default:
			$fs = 0;
			break;
	}
*/
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
fpassthru(fopen($filename, 'r'));
?>
