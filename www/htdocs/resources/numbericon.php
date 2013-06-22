<?php
$cats['128e4d'] = 'Nature';
$cats['265cb2'] = 'Industry';
$cats['3875d7'] = 'Offices';
$cats['5ec8bd'] = 'Stores';
$cats['66c547'] = 'Tourism';
$cats['8c4eb8'] = 'Restaurants-and-Hotels';
$cats['9d7050'] = 'Transportation';
$cats['a8a8a8'] = 'Media';
$cats['c03638'] = 'Events';
$cats['c259b5'] = 'Culture-and-Entertainment';
$cats['f34648'] = 'Health'; //Health-and-Education
$cats['ff8a22'] = 'Sports';
$cats['ffc11f'] = 'Education'; //Friends-and-Family
$cats['000000'] = null;
if(php_sapi_name()==="cli")
{
	mkdir("../img/icon/numbers");
	chmod("../img/icon/numbers", 0755);
	mkdir("../img/icon/letters");
	chmod("../img/icon/letters", 0755);
	foreach($cats as $color => $cat)
	{
		if($cat == null)
		{
			$blank = "../img/icon/blank_$color.png";
		}
		else
		{
			$blank = "../img/icon/$cat/blank.png";
		}
		mkdir("../img/icon/numbers/$color");
		chmod("../img/icon/numbers/$color", 0755);
		mkdir("../img/icon/letters/$color");
		chmod("../img/icon/letters/$color", 0755);
		for($i = 0; $i < 1000; $i++)
		{
			$l = $i;
			while(strlen($l) < 4)
			{
				$filename = "../img/icon/numbers/$color/$l.png";
				echo $filename."\n";
				generateIcon($l, $blank, $filename);
				$l = '0'.$l;
			}
		}
		for($i = ord('A'); $i <= ord('Z'); $i++)
		{
			$l = chr($i);
			$filename = "../img/icon/letters/$color/$l.png";
			echo $filename."\n";
			generateIcon($l, $blank, $filename, 18);
		}
		/*
		for($i = ord('a'); $i <= ord('z'); $i++)
		{
			$l = chr($i);
			$filename = "../img/icon/lowercase/$color/$l.png";
			echo $filename."\n";
			generateIcon($l, $blank, $filename);
		}
		*/
	}
}
else
{
	$hash = md5($_SERVER['QUERY_STRING']);
	$filename = 'cache/ni_'.$hash.'.png';
	if(!file_exists($filename))
	{
		generateIcon($_GET['n'], "../img/icon/blank_000000.png", $filename);
	}
	header('Content-type: image/png');
	header('Cache-Control: max-age=604800');
	fpassthru(fopen($filename, 'r'));
}

function generateIcon($n, $blank, $filename, $fixedheight=null)
{
	$oimg = imagecreatefrompng($blank);
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
	if($fixedheight != null)
	{
		$h = $fixedheight;
	}
	if($fs > 0)
	{
		imagettftext($oimg, $fs, 0, (32-$w)/2 - 1, ((32-$h)/2)-$bbox[7] - 1, $white, 'ubuntu.ttf', $n) or die('foo');
	}
	imagesavealpha($oimg,true);
	imagepng($oimg, $filename);
}
?>
