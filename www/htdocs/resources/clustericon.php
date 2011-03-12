<?php
function simagecreatefrompng($url) {
	if(preg_match("/^https?:\/\//", $url) || !preg_match("/\.\./", $url))
	{
		return imagecreatefrompng($url);
	}
}
$hash = md5($_SERVER['QUERY_STRING']);
$filename = 'cache/'.$hash.'.png';
if(!file_exists($filename))
{
	$imgs = $_GET['i'];
	if(array_key_exists('base', $_GET))
		$rimg = simagecreatefrompng($_GET['base']);
	else
		$rimg = simagecreatefrompng('blackness.png');
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
		imagecopyresampled($rimg, $img1, 6, 6, 5, 5, 10, 10, 22, 22);
		imagecopyresampled($rimg, $img2, 16, 6, 5, 5, 10, 10, 22, 22);
		imagecopyresampled($rimg, $img3, 6, 16, 5, 5, 10, 10, 22, 22);
		imagecopyresampled($rimg, $img4, 16, 16, 5, 5, 10, 10, 22, 22);
	}
	if($i == 3)
	{
		imagecopyresampled($rimg, $img1, 6, 6, 5, 5, 10, 10, 22, 22);
		imagecopyresampled($rimg, $img2, 16, 6, 5, 5, 10, 10, 22, 22);
		imagecopyresampled($rimg, $img3, 11, 16, 5, 5, 10, 10, 22, 22);
	}
	if($i == 2)
	{
		imagecopyresampled($rimg, $img1, 6, 6, 5, 5, 10, 10, 22, 22);
		imagecopyresampled($rimg, $img2, 16, 16, 5, 5, 10, 10, 22, 22);
	}
	if($i == 1)
	{
		imagesavealpha($img1,true);
		imagepng($img1, $filename);
	}
	imagesavealpha($rimg,true);
	imagepng($rimg, $filename);
}
header('Content-type: image/png');
fpassthru(fopen($filename, 'r'));
?>
