<?php
$hash = md5($_SERVER['QUERY_STRING']);
$filename = 'cache/pc_'.$hash.'.png';
if(!file_exists($filename))
{
	$pc = explode(' ', $_GET['pc']);
	$oimg = imagecreatefrompng("../img/icon/blank_a9a9a9.png");
	$white = imagecolorallocate($oimg, 255, 255, 255);
	imagestring($oimg, 2, 3, 3, $pc[0], $white);
	imagestring($oimg, 2, 13, 14, $pc[1], $white);
	imagesavealpha($oimg,true);
	imagepng($oimg, $filename);
}
header('Content-type: image/png');
header('Cache-Control: max-age=3600');
fpassthru(fopen($filename, 'r'));
?>
