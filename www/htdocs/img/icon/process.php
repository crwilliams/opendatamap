<?

$cols['Nature'] = '128e4d';
$cols['Industry'] = '265cb2';
$cols['Offices'] = '3875d7';
$cols['Stores'] = '5ec8bd';
$cols['Tourism'] = '66c547';
$cols['Restaurants-and-Hotels'] = '8c4eb8';
$cols['Transportation'] = '9d7050';
$cols['Media'] = 'a8a8a8';
$cols['Events'] = 'c03638';
$cols['Culture-and-Entertainment'] = 'c259b5';
$cols['Health'] = 'f34648';
$cols['Sports'] = 'ff8a22';
$cols['Education'] = 'ffc11f';

error_reporting(0);
if($argc == 3)
{
	processFile($argv[1], $argv[2]);
}
elseif($argc == 2)
{
	include 'modules/'.$argv[1].'/icons.php';
	foreach($icons as $category => $caticons)
	{
		foreach($caticons as $icon)
		{
			processFile($category, $icon, '../../modules/'.$argv[1].'/icons/');
		}
	}
}

function processFile($category, $file, $outputdir="")
{
	echo "Processing $file in category $category.\n";
	global $cols;
	global $argv;
	
	$no_tail = false;
	$wide = false;
	
	// Process command line arguments.
	if($argv[3] == 'nt')
	{
		$no_tail = true;
	}
	if($argv[3] == 'ntw')
	{
		$no_tail = true;
		$wide = true;
	}
	
	// Set base colour based on category.
	$color = $cols[$category];
	@mkdir($category.'/');
	
	$basecolorarr['r'] = hexdec(substr($color, 0, 2));
	$basecolorarr['g'] = hexdec(substr($color, 2, 2));
	$basecolorarr['b'] = hexdec(substr($color, 4, 2));
	
	// Check the icon file extension.
	if(substr($file, -4, 4) != '.png')
	{
		echo "Not a png file.\n";
		return;
	}
	
	// Try to load the icon file.
	$im = @imagecreatefrompng('src/'.$file);
	
	// Check the size of the icon file.
	if(imagesx($im) != 32 || imagesy($im) != 37)
	{
		echo "File is wrong shape.\n";
		return;
	}
	
	// Copy the relevant part of the icon file into a new image object of size 24x24.
	$dst_im = imagecreate(24, 24);
	imagecopy($dst_im, $im, 0, 0, 4, 14, 24, 24);
	
	$color=0;
	
	for($y = 0; $y < 24; $y++)
	{
		for($x = 0; $x < 24; $x++)
		{
			$sat[$x][$y] = getAverageColor($dst_im, $x, $y);
			$color = max($color, $sat[$x][$y]);
		}
	}
	
	$w = 32;
	if($no_tail)
	{
		$h = 32;
	}
	else
	{
		$h = 37;
	}
	if($wide)
	{
		$w = 240;
		$h = 28;
	}
	
	$gs_im = imagecreatetruecolor($w, $h);
	
	$basecolorobj = imagecolorallocate($gs_im, $basecolorarr['r'], $basecolorarr['g'], $basecolorarr['b']);
	
	imagealphablending($gs_im,false);
	imagefilledrectangle($gs_im, 0, 0, $w, $h, imagecolorallocatealpha($gs_im, 255, 255, 255, 127));
	imagealphablending($gs_im, true);
	
	$bh = min($h, 32);
	
	$markershape = array(
		1,0,
		$w-2,0,
		$w-1,1,
		$w-1,$bh-2,
		$w-2,$bh-1,
		1,$bh-1,
		0,$bh-2,
		0,1,
	);
	$markershape2 = array(
		20,32,
		16,36,
		15,36,
		11,32,
	);
	
	// Draw the box (base colour only).
	imagefilledpolygon($gs_im, $markershape, count($markershape)/2, $basecolorobj);
	
	if(!$no_tail)
	{
		// Draw the tail (base colour only).
		imagefilledpolygon($gs_im, $markershape2, count($markershape2)/2, $basecolorobj);
	}
	
	// Work out where the box gradient should stop.
	if($no_tail)
	{
		$stoph = $h-2;
		$stopw = $w-2;
	}
	else
	{
		$stoph = 30;
		$stopw = 30;
	}
	
	for($i = 1; $i <= $stoph; $i++)
	{
		// Build up the colour gradient on the box.
		imageline($gs_im, 1, $i, $stopw, $i, imagecolorallocatealpha($gs_im, 0, 0, 0, 127 - $i));
	}
	
	if(!$no_tail)
	{
		for($i = 0; $i < 5; $i++)
		{
			// Build up the colour gradient on the tail.
			imageline($gs_im, 11+$i, 31+$i, 20-$i, 31+$i, imagecolorallocatealpha($gs_im, 0, 0, 0, 127 - (31+$i)));
		}
	}
	
	// Allocate the border colour.
	$border = imagecolorallocatealpha($gs_im, 0, 0, 0, 64);
	
	if(!$no_tail)
	{
		// Draw the border.
		imageline($gs_im, 1, 0, 30, 0, $border);
		imageline($gs_im, 0, 1, 0, 30, $border);
		imageline($gs_im, 31, 1, 31, 30, $border);
		imageline($gs_im, 1, 31, 10, 31, $border);
		imageline($gs_im, 21, 31, 30, 31, $border);
		imageline($gs_im, 11, 32, 15, 36, $border);
		imageline($gs_im, 20, 32, 16, 36, $border);
	}
	else
	{
		// Draw the border.
		imageline($gs_im, 1, 0, $w-2, 0, $border);
		imageline($gs_im, 0, 1, 0, $h-2, $border);
		imageline($gs_im, $w-1, 1, $w-1, $h-2, $border);
		imageline($gs_im, 1, $h-1, $w-2, $h-1, $border);
	}
	
	// Save the 'blank' image.
	imagesavealpha($gs_im, true);
	if($no_tail)
	{
		if($wide)
		{
			imagepng($gs_im, $outputdir.$category.'/ntw.blank.png');
		}
		else
		{
			imagepng($gs_im, $outputdir.$category.'/nt.blank.png');
		}
		exit;
	}
	else
	{
		imagepng($gs_im, $outputdir.$category.'/blank.png');
	}
	
	$pl_im = imagecreatetruecolor(24, 24);
	imagealphablending($pl_im,false);
	imagefilledrectangle($pl_im, 0, 0, $w, $h, imagecolorallocatealpha($pl_im, 255, 255, 255, 127));
	imagealphablending($pl_im, true);

	// For all pixels in the icon.
	for($y = 0; $y < 24; $y++)
	{
		for($x = 0; $x < 24; $x++)
		{
			// Get the pixel level.
			$level = max(0, min(127, round(127*$sat[$x][$y]/$color, 0)));
			
			// Sanitise the pixel level.
			if(floor($level) <= 24 && floor($level) >= 16)
			{
				$level = 0;
			}
			if(floor($level) <= 44 && floor($level) >= 44 && (($x == 1 || $x == 22) || ($y == 1 || $y == 22)))
			{
				$level = 0;
			}
			if(floor($level) <= 73 && floor($level) >= 73 && (($x == 1 || $x == 22) && ($y == 1 || $y == 22)))
			{
				$level = 0;
			}
			
			// Set the pixel colour according to its level.
			imagesetpixel($gs_im, $x+4, $y+4, imagecolorallocatealpha($gs_im, 255, 255, 255, 127-$level));
			imagesetpixel($pl_im, $x, $y, imagecolorallocatealpha($pl_im, 0, 0, 0, 127-$level));
		}
	}
	
	imagesavealpha($pl_im, true);
	imagepng($pl_im, $outputdir.'plain/'.$file);
	imagepng($gs_im, $outputdir.$category.'/'.$file);
}

function getAverageColor($im, $x, $y)
{
	$color = imagecolorsforindex($im, imagecolorat($im, $x, $y));
	if($y == 23)
	{
		return 0;
	}
	if($color['alpha'] >= 127 - 20)
	{
		return 0;
	}
	
	return 1 - ((($color['red'] + $color['green'] + $color['blue']) / (3 * 255)));
}

function getPrimaryColor($im, $x, $y)
{
	$color = imagecolorsforindex($im, imagecolorat($im, $x, $y));
	return $color['red']."/".$color['green']."/".$color['blue'];
}

function getColorHex($color)
{
	return str_pad(dechex($color[0]), 2, '0').str_pad(dechex($color[1]), 2, '0').str_pad(dechex($color[2]), 2, '0');
}

function pickColor($color)
{
	$ashex = getColorHex($color);
	switch($ashex)
	{
		case "000000":
		case "00e13c":
		case "7e55fc":
		case "a9a9a9":
		case "ef9e40":
		case "fcf357":
			break;
		case "9d7050":
		case "9e7151":
			$ashex = "9d7050";
			break;
		case "54d7d7":
		case "55d7d7":
			$ashex = "54d7d7";
			break;
		case "567ffc":
		case "3037d1":
		case "5680fc":
		case "5781fc":
			$ashex = "5680fc";
			break;
		case "e14f9d":
		case "e14f9e":
			$ashex = "e14f9d";
			break;
		case "fc6254":
		case "fc6355":
			$ashex = "fc6254";
			break;
	}
	$asdec[0] = hexdec(substr($ashex, 0, 2));
	$asdec[1] = hexdec(substr($ashex, 2, 2));
	$asdec[2] = hexdec(substr($ashex, 4, 2));
	return $asdec;
}

function getColor($im, $x, $y)
{
	$color = imagecolorsforindex($im, imagecolorat($im, $x, $y));
	$hsv = RGB_TO_HSV($color['red'], $color['green'], $color['blue']);
	return round($hsv['S'], 3);
}

function RGB_TO_HSV ($R, $G, $B) // RGB Values:Number 0-255
{ // HSV Results:Number 0-1
	$HSL = array();

	$var_R = ($R / 255);
	$var_G = ($G / 255);
	$var_B = ($B / 255);

	$var_Min = min($var_R, $var_G, $var_B);
	$var_Max = max($var_R, $var_G, $var_B);
	$del_Max = $var_Max - $var_Min;

	$V = $var_Max;

	if ($del_Max == 0)
	{
		$H = 0;
		$S = 0;
	}
	else
	{
		$S = $del_Max / $var_Max;

		$del_R = ( ( ( $max - $var_R ) / 6 ) + ( $del_Max / 2 ) ) / $del_Max;
		$del_G = ( ( ( $max - $var_G ) / 6 ) + ( $del_Max / 2 ) ) / $del_Max;
		$del_B = ( ( ( $max - $var_B ) / 6 ) + ( $del_Max / 2 ) ) / $del_Max;

		if ($var_R == $var_Max) $H = $del_B - $del_G;
		else if ($var_G == $var_Max) $H = ( 1 / 3 ) + $del_R - $del_B;
		else if ($var_B == $var_Max) $H = ( 2 / 3 ) + $del_G - $del_R;

		if (H<0) $H++;
		if (H>1) $H--;
	}

	$HSL['H'] = $H;
	$HSL['S'] = $S;
	$HSL['V'] = $V;

	return $HSL;
}

function HSV_TO_RGB ($H, $S, $V) // HSV Values:Number 0-1
{ // RGB Results:Number 0-255
	$RGB = array();

	if($S == 0)
	{
		$R = $G = $B = $V * 255;
	}
	else
	{
		$var_H = $H * 6;
		$var_i = floor( $var_H );
		$var_1 = $V * ( 1 - $S );
		$var_2 = $V * ( 1 - $S * ( $var_H - $var_i ) );
		$var_3 = $V * ( 1 - $S * (1 - ( $var_H - $var_i ) ) );

		if ($var_i == 0) { $var_R = $V ; $var_G = $var_3 ; $var_B = $var_1 ; }
		else if ($var_i == 1) { $var_R = $var_2 ; $var_G = $V ; $var_B = $var_1 ; }
		else if ($var_i == 2) { $var_R = $var_1 ; $var_G = $V ; $var_B = $var_3 ; }
		else if ($var_i == 3) { $var_R = $var_1 ; $var_G = $var_2 ; $var_B = $V ; }
		else if ($var_i == 4) { $var_R = $var_3 ; $var_G = $var_1 ; $var_B = $V ; }
		else { $var_R = $V ; $var_G = $var_1 ; $var_B = $var_2 ; }

		$R = $var_R * 255;
		$G = $var_G * 255;
		$B = $var_B * 255;
	}

	$RGB['R'] = $R;
	$RGB['G'] = $G;
	$RGB['B'] = $B;

	return $RGB;
}
?>
