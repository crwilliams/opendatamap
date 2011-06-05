<?php
$hash = md5($_SERVER['QUERY_STRING']);
$filename = 'cache/ni_'.$hash.'.png';
if(!file_exists($filename))
{
	$n = $_GET['n'];
	$oimg = imagecreatefrompng("../img/icon/blank_000000.png");
	$white = imagecolorallocate($oimg, 255, 255, 255);
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
	$bbox = imagettfbbox($fs, 0, 'ubuntu.ttf', $n);
	$w = $bbox[2] - $bbox[0];
	$h = $bbox[1] - $bbox[7];
	if($fs > 0)
		imagettftext($oimg, $fs, 0, (32-$w)/2 - 1, ((32-$h)/2)-$bbox[7] - 1, $white, 'ubuntu.ttf', $n) or die('foo');
	imagesavealpha($oimg,true);
	imagepng($oimg, $filename);
}
header('Content-type: image/png');
fpassthru(fopen($filename, 'r'));
?>
