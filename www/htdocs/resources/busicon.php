<?php
$_SERVER['QUERY_STRING'] = str_replace('+', '/', $_SERVER['QUERY_STRING']);
$_GET['r'] = str_replace('+', '/', $_GET['r']);
$hash = md5($_SERVER['QUERY_STRING']);
$filename = 'cache/bi_'.$hash.'.png';
if(!file_exists($filename))
{
	$rs = explode('/', $_GET['r']);
	$oimg = imagecreatefrompng("../img/icon/Transportation/bus.png");
	$white = imagecolorallocate($oimg, 255, 255, 255);
	$color['U1'] = imagecolorallocate($oimg,   0, 142, 207);
	$color['U1A'] = $color['U1'];
	$color['U1C'] = $color['U1'];
	$color['U1E'] = $color['U1'];
	$color['U2'] = imagecolorallocate($oimg, 226,   2,  20);
	$color['U2B'] = $color['U2'];
	$color['U2C'] = $color['U2'];
	$color['U6'] = imagecolorallocate($oimg, 246, 166,  24);
	$color['U6C'] = $color['U6'];
	$color['U6H'] = $color['U6'];
	$color['U9'] = imagecolorallocate($oimg, 232,  84, 147);
	$i = 0;
	foreach($rs as $r)
	{
		if(!isset($color[$r]))
			continue;
		$colors[] = $color[$r];
	}
	$colors = array_unique($colors);
	foreach($colors as $c)
	{
		imagefilledrectangle($oimg, 26, 1+($i*6), 30, 5+($i*6), $c);
		$i++;
	}
	imagesavealpha($oimg,true);
	imagepng($oimg, $filename);
}
header('Content-type: image/png');
header('Cache-Control: max-age=604800');
fpassthru(fopen($filename, 'r'));
?>
