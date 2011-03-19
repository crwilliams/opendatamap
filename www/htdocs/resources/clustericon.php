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
$filename = 'cache/'.$hash.'.png';
if(!file_exists($filename))
{
	$imgs = $_GET['i'];
	if(array_key_exists('base', $_GET))
		$rimg = simagecreatefrompng($_GET['base']);
	else
		$rimg = simagecreatefrompng('img/blackness.png');
	$img1 = simagecreatefrompng($imgs[0]);
	$i = count($imgs);
	if($i >= 2)
	{
		$img2 = simagecreatefrompng($imgs[1]);
	}
	if($i >= 3)
	{
		$img3 = simagecreatefrompng($imgs[2]);
	}
	if($i >= 4)
	{
		$img4 = simagecreatefrompng($imgs[3]);
	}
	if($i >= 4)
	{
		positionimg($rimg, $img1, 6, 6);
		positionimg($rimg, $img2, 16, 6);
		positionimg($rimg, $img3, 6, 16);
		positionimg($rimg, $img4, 16, 16);
	}
	if($i == 3)
	{
		positionimg($rimg, $img1, 6, 6);
		positionimg($rimg, $img2, 16, 6);
		positionimg($rimg, $img3, 11, 16);
	}
	if($i == 2)
	{
		positionimg($rimg, $img1, 6, 6);
		positionimg($rimg, $img2, 16, 16);
	}
	if($i == 1)
	{
		imagesavealpha($img1,true);
		imagepng($img1, $filename);
	}
	else
	{
		imagesavealpha($rimg,true);
		imagepng($rimg, $filename);
	}
}
header('Content-type: image/png');
fpassthru(fopen($filename, 'r'));
?>
