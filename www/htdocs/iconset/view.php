<?
function include_stylesheet($filename)
{
	if(isset($_GET['i']))
	{
		echo '<style>'."\n";
		echo '/* Stylesheet included from '.$filename.' */'."\n";
		include $filename;
		echo "\n".'</style>'."\n";
	}
	else
	{
		echo '<link rel="stylesheet" href="'.$filename.'" type="text/css">'."\n";
	}
}
?>
<html>
<head>
	<?php include_stylesheet('../css/reset.css') ?>
	<?php include_stylesheet('../css/index.css') ?>
	<?php include_stylesheet('../css/credits.css') ?>
	<script type="text/javascript" src="<? if(!isset($_GET['i'])) echo '../js/'; ?>jquery-1.6.1.min.js"></script>
	<title>opendatamap iconset | listing</title>
<script type='text/javascript' lang='javascript'>
function searchyou()
{
	var sb = document.getElementById('search').value;
	$('img').each(function(value){
		if($(this).attr('id').substr(0, sb.length) == sb)
			$(this).show();
		else
			$(this).hide();
	});
}
</script>
</head>
<body>
<?
if(!isset($_GET['i']))
{
	include_once '../googleanalytics.php';
}
?>
<h1>opendatamap iconset</h1>
<h2>Listing</h2>
<p>Below is a listing of all icons in the <a href='.'>opendatamap iconset</a>.</p>
<form>
<label for='search'>Search: </label><input type='text' name='search' id='search' onkeyup='searchyou()' />
</form>
<br/>
<?

$handle = opendir('../img/icon/');

while (false !== ($file = readdir($handle))) {
		//echo $file.'<br/>';
	if(is_dir('../img/icon/'.$file) && $file[0] >= 'A' && $file[0] <= 'Z')
	{
		$handle2 = opendir('../img/icon/'.$file.'/');
		while (false !== ($file2 = readdir($handle2))) {
			if(substr($file2, -4, 4) != '.png')
				continue;
			if($file2 == 'blank.png' || $file2 == 'nt.blank.png' || $file2 == 'ntw.blank.png')
				continue;
			$files[] =  $file.'/'.$file2;
		}
		closedir($handle2);
	}
}
sort($files);
$head = '';
foreach($files as $file)
{
	list($cat, $filename) = explode('/', $file);
	$filename = substr($filename, 0, -4);
	if($cat != $head)
	{
		echo '<h3>'.$cat.'</h3>';
		if(isset($_GET['i']))
		{
			echo "<img id='blank' src='$cat/blank.png' alt='blank icon' title='blank' />";
		}
		else
		{
			echo "<img id='blank' src='../img/icon/$cat/blank.png' alt='blank icon' title='blank' />";
		}
		//echo "<img id='nt.blank' src='../img/icon/$cat/nt.blank.png' alt='no tail blank icon' title='no tail blank' />";
		//echo "<img id='ntw.blank' src='../img/icon/$cat/ntw.blank.png' alt='no tail wide blank icon' title='no tail wide blank' />";
		$head = $cat;
	}
	if(isset($_GET['i']))
	{
		echo "<img id='$filename' src='$file' alt='$filename icon' title='$filename' />";
	}
	else
	{
		echo "<img id='$filename' src='../img/icon/$file' alt='$filename icon' title='$filename' />";
	}
}
?>
</body>
</html>
