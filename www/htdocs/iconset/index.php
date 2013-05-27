<?
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
function img($filename)
{
	if(isset($_GET['i']))
	{
		$filename = str_replace('../img/icon/', '', $filename);
	}
	return '<img src="'.$filename.'" />';
}
?>
<html>
<head>
	<?php include_stylesheet('../css/reset.css') ?>
	<?php include_stylesheet('../css/index.css') ?>
	<?php include_stylesheet('../css/credits.css') ?>
	<title>opendatamap iconset</title>
</head>
<body>
<?
if(!isset($_GET['i']))
{
	include_once '../googleanalytics.php';
}

$total = 0;
foreach($cats as $color => $cat)
{
	$icons = '../img/icon/'.$cat.'/*.png';
	foreach(glob($icons) as $icon)
	{
		if(!preg_match('/(ntw?_)?blank.png$/', $icon, $match))
		{
			$catcounts[$cat]++;
			$total++;
			$files[$cat][] = $icon;
		}
	}
}
?>
<h1>opendatamap iconset</h1>
<h3>About</h3>
<p>
	The opendatamap iconset was developed for the University of Southampton <a href='http://opendatamap.ecs.soton.ac.uk'>linked open data map</a>.
</p>
<p>
	The <em>core</em> set of icons consist of <?= $total ?> icons, organised into <?= count($cats) ?> categories.  Each category is represented by a different colour, as follows:
</p>
<table style='margin-left:20px;'>
<?
$header = array(
	'Category',
	'Colour',
	'Blank icon',
	'Sample icon',
);
foreach($cats as $color => $cat)
{
	$table[] = array(
		'<td class="fw"><div class="rot90">'.str_replace('-and-', ' &amp; ', $cat).'</div></td>',
		'<td style="background-color:#'.$color.'; color:white; font-size:0.8em; padding:5px;">'.strtoupper($color).'</td>',
		'<td style="padding:5px;">'.img('../img/icon/'.$cat.'/blank.png').'</td>',
		'<td style="padding:5px;">'.img($files[$cat][rand(0, count($files[$cat])-1)]).'</td>',
	);
}
for($rid=0; $rid<4; $rid++)
{
	echo '<tr>';
	for($cid=0; $cid<count($table); $cid++)
	{
		echo $table[$cid][$rid];
	}
	echo '<th style="padding:5px; vertical-align:'.($rid == 0 ? 'bottom' : 'middle').'; text-align:left;">'.$header[$rid].'</th>';
	echo '</tr>';
}
?>
</table>
<ul>
</ul>
<p>
	<a href='view<? if(isset($_GET['i'])) echo '.html' ?>'>Show all core icons</a>
</p>
<?
$cats['000000'] = null;
?>
<p>
	The <em>numbers</em> set of icons consists of icons displaying:
</p>
<ul>
	<li>All one digit numbers in the range 0 to 9 (each in one of <?= count($cats) ?> colours).
	<br>
<?
foreach(array_keys($cats) as $color)
{
	echo img('../img/icon/numbers/'.$color.'/0.png');
	echo img('../img/icon/numbers/'.$color.'/9.png');
}
?>
	</li>
	<li>All two digit numbers in the range 00 to 99 (each in one of <?= count($cats) ?> colours).
	<br>
<?
foreach(array_keys($cats) as $color)
{
	echo img('../img/icon/numbers/'.$color.'/00.png');
	echo img('../img/icon/numbers/'.$color.'/99.png');
}
?>
	</li>
	<li>All three digit numbers in the range 000 to 999 (each in one of <?= count($cats) ?> colours).
	<br>
<?
foreach(array_keys($cats) as $color)
{
	echo img('../img/icon/numbers/'.$color.'/000.png');
	echo img('../img/icon/numbers/'.$color.'/999.png');
}
?>
	</li>
</ul>
<p>
	The <em>letters</em> set of icons consists of icons displaying:
</p>
<ul>
	<li>All 26 letters in the Latin alphabet from A to Z (each in one of <?= count($cats) ?> colours).
	<br>
<?
foreach(array_keys($cats) as $color)
{
	echo img('../img/icon/letters/'.$color.'/A.png');
	echo img('../img/icon/letters/'.$color.'/Z.png');
}
?>
	</li>
</ul>
<?
function fileinfo($filename)
{
	$stats = stat($filename);
	$size = $stats['size'];
	if($size > 1024*1024)
		$size = round($size/1024/1024, 1) . 'MB';
	else if($size > 1024)
		$size = round($size/1024, 1) . 'KB';
	else
		$size = $size . 'B';
	return 'zip file, size: '.$size.', last modified: '.date('Y/m/d H:i', $stats['mtime']);
}
?>
<h3>Downloads</h3>
<?
if(isset($_GET['i']))
{
?>
<p>
	The latest version of the opendatamap iconset can be downloaded from <a href='http://opendatamap.ecs.soton.ac.uk/iconset'>http://opendatamap.ecs.soton.ac.uk/iconset</a>.
</p>
<?
}
else
{
?>
<ul>
	<li><a href='opendatamap-iconset.zip'>opendatamap iconset (core)</a> <?= fileinfo('opendatamap-iconset.zip') ?></li>
	<li><a href='opendatamap-iconset-numbers.zip'>opendatamap iconset (numbers)</a> <?= fileinfo('opendatamap-iconset-numbers.zip') ?></li>
	<li><a href='opendatamap-iconset-letters.zip'>opendatamap iconset (letters)</a> <?= fileinfo('opendatamap-iconset-letters.zip') ?></li>
</ul>
<h3>Licence</h3>
<p>
	This iconset is based on the <a href='http://mapicons.nicolasmollet.com'>Map Icons Collection</a>.  Our iconset is available under the <a href='http://creativecommons.org/licenses/by-sa/3.0/' title='Creative Commons - Attribution-ShareAlike 3.0 Unported'>CC BY-SA 3.0</a> licence.  The attribution should be to <em>opendatamap iconset</em>, with a link provided to this page (<a href='http://opendatamap.ecs.soton.ac.uk/iconset'>http://opendatamap.ecs.soton.ac.uk/iconset</a>).
</p>
<?
}
?>
</body>
</html>
