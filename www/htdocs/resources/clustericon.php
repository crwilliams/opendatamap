<?php
function simagecreatefrompng($url) {
	if(preg_match("/^https?:\/\//", $url))
	{
		return imagecreatefrompng($url);
	}
	if(!preg_match("/\.\./", $url))
	{
		return imagecreatefrompng("../".$url);
	}
	return imagecreatefrompng("blackness.png");
}
function positionimg($rimg, $img, $destx, $desty) {
	$x = imagesx($img);
	$y = imagesy($img);
	imagecopyresampled($rimg, $img, $destx, $desty, 5, 5, 10, 10, $x - 10, $y - 15);
}
$hash = md5($_SERVER['QUERY_STRING']);
$filename = 'cache/ci_'.$hash.'.png';
if(!file_exists($filename))
{
	$imgs = $_GET['i'];
	$imgs = array_values(array_unique($imgs));
	/*
	if(array_key_exists('base', $_GET))
		$rimg = simagecreatefrompng($_GET['base']);
	else
		$rimg = simagecreatefrompng('img/blackness.png');
	*/
	$offsetx = 3;
	$offsety = 3;
	$count = min(10, count($imgs));
	$oimg = imagecreatetruecolor(32 + $offsetx*count($imgs), 37-$offsety + $offsety*$count);
	imagealphablending($oimg,false);
	imagefilledrectangle($oimg, 0, 0, imagesx($oimg), imagesy($oimg), imagecolorallocatealpha($oimg, 255, 255, 255, 127));
	imagealphablending($oimg, true);
	for($i=$count-1; $i>=0; $i--)
	{
		$img = simagecreatefrompng($imgs[$i]);
		//if($i != $count-1)
		//	imagecopymerge($oimg, $img, ($count - $i - 1) * $offsetx, $i * $offsety, 0, 0, 32, 32, max(100 - ($count - $i - 1) * 20, 0));
		//else
			imagecopy($oimg, $img, $i * $offsetx, ($count - $i - 1) * $offsety, 0, 0, 32, 37);
	}
	imagesavealpha($oimg,true);
	imagepng($oimg, $filename);
}
header('Content-type: image/png');
fpassthru(fopen($filename, 'r'));
?>
