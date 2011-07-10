<?

$cat['128e4d'] = 'Nature';
$cat['265cb2'] = 'Industry';
$cat['3875d7'] = 'Offices';
$cat['5ec8bd'] = 'Stores';
$cat['66c547'] = 'Tourism';
$cat['8c4eb8'] = 'Restaurants-and-Hotels';
$cat['9d7050'] = 'Transportation';
$cat['a8a8a8'] = 'Media';
$cat['c03638'] = 'Events';
$cat['c259b5'] = 'Culture-and-Entertainment';
$cat['f34648'] = 'Health'; //Health-and-Education
$cat['ff8a22'] = 'Sports';
$cat['ffc11f'] = 'Education'; //Friends-and-Family

//while (false !== ($file = readdir($handle))) {
//}
processFile($argv[1], $argv[2]);

function processFile($color, $file)
{
	echo "Processing $file in color $color.\n";
	global $cat;
	$category = $cat[$color];
	@mkdir($category.'/');
	
	$basecolorarr['r'] = hexdec(substr($color, 0, 2));
	$basecolorarr['g'] = hexdec(substr($color, 2, 2));
	$basecolorarr['b'] = hexdec(substr($color, 4, 2));
	
	if(substr($file, -4, 4) != '.png')
	{
		echo "Not a png file.\n";
		return;
	}
	$im = @imagecreatefrompng('src/'.$file);
	if(imagesx($im) != 32 || imagesy($im) != 37)
	{
		echo "File is wrong shape.\n";
		return;
	}
	
	$dst_im = imagecreate(24, 24);
	imagecopy($dst_im, $im, 0, 0, 4, 14, 24, 24);
	
	$gs = true;
	/*
	$colors = array();
	for($i = 2; $i <= 22; $i++)
	{
		$colors[(string)getColor($dst_im, 0, $i)]++;
		$colors[(string)getColor($dst_im, 23, $i)]++;
		$colors[(string)getColor($dst_im, $i, 0)]++;
		$colors[(string)getColor($dst_im, $i, 23)]++;
	}
	arsort($colors);
	$color = array_shift(array_keys($colors));
	
	echo $color;
	
	$gs = true;
	*/
	//if($color == "0")
	{
		$gs = true;
		$colors = array();
		
		for($i = 2; $i <= 22; $i++)
		{
			$colors[(string)getAverageColor($dst_im, 0, $i)]++;
			$colors[(string)getAverageColor($dst_im, 23, $i)]++;
			$colors[(string)getAverageColor($dst_im, $i, 0)]++;
			$colors[(string)getAverageColor($dst_im, $i, 23)]++;
		}
		
		/*
		$t = 2;
		
		$colors[(string)getAverageColor($dst_im, 0+$t, 0+$t)]++;
		$colors[(string)getAverageColor($dst_im, 23-$t, 0+$t)]++;
		$colors[(string)getAverageColor($dst_im, 0+$t, 23-$t)]++;
		$colors[(string)getAverageColor($dst_im, 23-$t, 23-$t)]++;
		*/
		
		arsort($colors);
		print_r($colors);
		$color = array_shift(array_keys($colors));
		echo $color;
	}
	$color=1;
	
	//$maxsat = 0;
	//$minsat = 1;
	for($y = 0; $y < 24; $y++)
	{
		for($x = 0; $x < 24; $x++)
		{	
			/*
			if($x + $y <= 1 || (23-$x) + $y <= 1 || $x + (23-$y) <= 1 || (23-$x) + (23-$y) <= 1)
			{
				$sat[$x][$y] = $color;
				$sat[$x][$y] = 0;
			}
			else
			{
				*/
				if($gs)
					$sat[$x][$y] = getAverageColor($dst_im, $x, $y);
				else
					$sat[$x][$y] = getColor($dst_im, $x, $y);
				/*
			}
			*/
			
			//$maxsat = max($maxsat, $sat[$x][$y]);
			//$minsat = max($minsat, $sat[$x][$y]);
		}
	}
	
	
	$gs_im = imagecreatetruecolor(32, 37);
	/*
	if($basecolor == 'default')
	{
		$primcolors = array();
		for($i = 2; $i <= 22; $i++)
		{
			$primcolors[(string)getPrimaryColor($dst_im, 0, $i)]++;
			$primcolors[(string)getPrimaryColor($dst_im, 23, $i)]++;
			$primcolors[(string)getPrimaryColor($dst_im, $i, 0)]++;
			$primcolors[(string)getPrimaryColor($dst_im, $i, 23)]++;
		}
		arsort($primcolors);
		$primcolor = array_shift(array_keys($primcolors));
		$primcolor = explode("/", $primcolor);
		$primcolor = pickColor($primcolor);
		$basecolorobj = imagecolorallocate($gs_im, $primcolor[0], $primcolor[1], $primcolor[2]);
	}
	else
	{
		*/
		$basecolorobj = imagecolorallocate($gs_im, $basecolorarr['r'], $basecolorarr['g'], $basecolorarr['b']);
		/*
	}
	*/
	
	imagealphablending($gs_im,false);
	imagefilledrectangle($gs_im, 0, 0, 32, 37, imagecolorallocatealpha($gs_im, 255, 255, 255, 127));
	imagealphablending($gs_im, true);
	
	$markershape = array(
		1,0,
		30,0,
		31,1,
		31,30,
		30,31,
		1,31,
		0,30,
		0,1,
	);
	$markershape2 = array(
		20,32,
		16,36,
		15,36,
		11,32,
	);
	imagefilledpolygon($gs_im, $markershape, count($markershape)/2, $basecolorobj);
	imagefilledpolygon($gs_im, $markershape2, count($markershape2)/2, $basecolorobj);
	
	for($i = 1; $i <= 30; $i++)
		imageline($gs_im, 1, $i, 30, $i, imagecolorallocatealpha($gs_im, 0, 0, 0, 127 - $i));
	for($i = 0; $i < 5; $i++)
		imageline($gs_im, 11+$i, 31+$i, 20-$i, 31+$i, imagecolorallocatealpha($gs_im, 0, 0, 0, 127 - (31+$i)));
	
	$border = imagecolorallocatealpha($gs_im, 0, 0, 0, 64);
	imageline($gs_im, 1, 0, 30, 0, $border);
	imageline($gs_im, 0, 1, 0, 30, $border);
	imageline($gs_im, 31, 1, 31, 30, $border);
	imageline($gs_im, 1, 31, 10, 31, $border);
	imageline($gs_im, 21, 31, 30, 31, $border);
	imageline($gs_im, 11, 32, 15, 36, $border);
	imageline($gs_im, 20, 32, 16, 36, $border);
	
	imagesavealpha($gs_im, true);
	imagepng($gs_im, $category.'/blank.png');
	
	print_r($sat);
	for($y = 0; $y < 24; $y++)
	{
		for($x = 0; $x < 24; $x++)
		{
			$level = max(0, min(127, round(127*$sat[$x][$y]/$color, 0)));
			if(floor($level) <= 24 && floor($level) >= 16)
				$level = 0;
			if(floor($level) <= 44 && floor($level) >= 44 && (($x == 1 || $x == 22) || ($y == 1 || $y == 22)))
				$level = 0;
			if(floor($level) <= 73 && floor($level) >= 73 && (($x == 1 || $x == 22) && ($y == 1 || $y == 22)))
				$level = 0;
			imagesetpixel($gs_im, $x+4, $y+4, imagecolorallocatealpha($gs_im, 255, 255, 255, 127-$level));
		}
	}
	
	imagepng($gs_im, $category.'/'.$file);
}

function getAverageColor($im, $x, $y)
{
	$color = imagecolorsforindex($im, imagecolorat($im, $x, $y));
	if($y == 23)
		return 0;
	if($color['alpha'] == 127)
		return 0;
	return 1 - (($color['red'] + $color['green'] + $color['blue']) / (3 * 255));
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
