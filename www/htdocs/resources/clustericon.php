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
function catsort($a, $b)
{
	$cats['Special'] = 1;
	$cats['Education'] = 2;
	$cats['Health'] = 3;
	if(array_key_exists($a, $cats) && !array_key_exists($b, $cats))
	{
		return -1;
	}
	if(!array_key_exists($a, $cats) && array_key_exists($b, $cats))
	{
		return 1;
	}
	if(array_key_exists($a, $cats) && array_key_exists($b, $cats))
	{
		if($cats[$a] == $cats[$b])
		{
			return 0;
		}
		else if($cats[$a] > $cats[$b])
		{
			return -1;
		}
		else
		{
			return 1;
		}
	}
	else
	{
		if($a == $b)
		{
			return 0;
		}
		else if($a > $b)
		{
			return -1;
		}
		else
		{
			return 1;
		}
	}
}
if(isset($_GET['icons']))
{
	$_GET['i'] = array();
	$icons = explode('|', $_GET['icons']);
	foreach($icons as $icon)
	{
		if(substr($icon, 0, 6) == 'soton:')
		{
			$_GET['i'][] = 'http://data.southampton.ac.uk/map-icons/'.substr($icon, 6).'.png';
		}
		else if(substr($icon, 0, 3) == 'ws:')
		{
			$_GET['i'][] = 'http://opendatamap.ecs.soton.ac.uk/resources/workstationicon.php?pos=http://id.southampton.ac.uk/point-of-service/'.substr($icon, 3);
		}
		else
		{
			$_GET['i'][] = $icon;
		}
	}
}
$hash = md5(implode('_', $_GET['i']));
$filename = 'cache/ci_'.$hash.'.png';
$cachable = true;
if(!file_exists($filename))
{
	$imgs = $_GET['i'];
	$imgs = array_values(array_unique($imgs));
	foreach($imgs as $img)
	{
		$parts = explode('/', $img);
		if($parts[2] == 'data.southampton.ac.uk')
		{
			$cat = $parts[4];
		}
		else if(preg_match('|^http://opendatamap.ecs.soton.ac.uk/resources/busicon/|', $img))
		{
			$cat = 'Special';
		}
		else if(preg_match('|^http://opendatamap.ecs.soton.ac.uk/resources/workstationicon.php|', $img))
		{
			$cat = 'Special';
			$workstations[] = preg_replace('|^http://opendatamap.ecs.soton.ac.uk/resources/workstationicon.php\?pos=|', '', $img);
			$cachable = false;
		}
		else
		{
			$cat = '?';
		}
		$gimgs[$cat][] = $img;
	}
	$limit = 10;
	if(count($gimgs) == 1)
	{
		$limit = 2;
	}
	else
	{
		$imgs = array();
		uksort($gimgs, 'catsort');
		foreach($gimgs as $cat => $catimgs)
		{
			//$catimgs = array_values($catimgs);
			$imgs[] = $catimgs[0];
		}
	}
	/*
	if(array_key_exists('base', $_GET))
		$rimg = simagecreatefrompng($_GET['base']);
	else
		$rimg = simagecreatefrompng('img/blackness.png');
	*/
	$offsetx = 3;
	$offsety = 3;
	$count = min($limit, count($imgs));
	$oimg = imagecreatetruecolor(32 + $offsetx*count($imgs), 37-$offsety + $offsety*$count);
	imagealphablending($oimg,false);
	imagefilledrectangle($oimg, 0, 0, imagesx($oimg), imagesy($oimg), imagecolorallocatealpha($oimg, 255, 255, 255, 127));
	imagealphablending($oimg, true);
	for($i=$count-1; $i>=0; $i--)
	{
		if($i == 0 && preg_match('|^http://opendatamap.ecs.soton.ac.uk/resources/workstationicon.php|', $imgs[$i]))
		{
			$imgs[$i] = 'http://opendatamap.ecs.soton.ac.uk/resources/workstationicon.php?';
			foreach($workstations as $pos)
			{
				$qstr[] = "pos[]=".$pos;
			}
			$imgs[$i] .= implode('&', $qstr);
		}
		$img = simagecreatefrompng($imgs[$i]);
		//if($i != $count-1)
		//	imagecopymerge($oimg, $img, ($count - $i - 1) * $offsetx, $i * $offsety, 0, 0, 32, 32, max(100 - ($count - $i - 1) * 20, 0));
		//else
			imagecopy($oimg, $img, $i * $offsetx, ($count - $i - 1) * $offsety, 0, 0, 32, 37);
	}
	imagesavealpha($oimg,true);
	if(!$cachable)
	{
		header('Content-type: image/png');
		header('Cache-Control: max-age=60');
		imagepng($oimg);
		die();
	}
	imagepng($oimg, $filename);
}
header('Content-type: image/png');
header('Cache-Control: max-age=604800');
fpassthru(fopen($filename, 'r'));
?>
