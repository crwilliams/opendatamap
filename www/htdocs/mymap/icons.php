<?php
$handle = opendir('../img/icon/');

while (false !== ($file = readdir($handle))) {
		//echo $file.'<br/>';
	if(is_dir('../img/icon/'.$file) && $file[0] >= 'A' && $file[0] <= 'Z')
	{
		$handle2 = opendir('../img/icon/'.$file.'/');
		while (false !== ($file2 = readdir($handle2))) {
			if(substr($file2, -4, 4) != '.png')
				continue;
			$files[] =  $file.'/'.$file2;
		}
		closedir($handle2);
	}
}
sort($files);
foreach($files as $file)
{
	list($cat, $filename) = explode('/', $file);
	$filename = substr($filename, 0, -4);
	if($filename == 'blank' || $filename == 'nt.blank' || $filename == 'ntw.blank')
		continue;
	$icons[$cat][] = $file;
}

foreach($icons[$_GET['cat']] as $file)
{
	list($cat, $filename) = explode('/', $file);
	$filename = substr($filename, 0, -4);
	echo "<img id='img-$filename' src='http://data.southampton.ac.uk/map-icons/$file' alt='$filename icon' title='$filename' onclick='selectIcon(\"http://data.southampton.ac.uk/map-icons/$file\")' />";
}
?>
