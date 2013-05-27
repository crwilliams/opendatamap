<html>
<head>
	<link rel="stylesheet" href="css/reset.css" type="text/css">
	<link rel="stylesheet" href="css/index.css" type="text/css">
	<link rel="stylesheet" href="css/credits.css" type="text/css">
	<title>opendatamap | list of available maps</title>
</head>
<body>
<h1>opendatamap</h1>
<h2>list of available maps</h2>
<? include_once 'googleanalytics.php'; ?>
<?php
foreach(glob('modules/*/config.php') as $cfg_file)
{
	$config = array();
	include $cfg_file;
	if($config['hidden'])
	{
		continue;
	}
	$path = explode("/", $cfg_file);
	$path = $path[1];
	if($path == 'default')
	{
		$path = '';
	}
	$maps[$path] = $config;
	if(is_link(dirname($cfg_file)))
	{
		$dst = readlink(dirname($cfg_file));
		$dst = explode("/", $dst);
		$symlinks[] = $dst[0];
	}
}
ksort($maps);

foreach($maps as $path => $config)
{
	if(in_array($path, $symlinks))
	{
		continue;
	}
	echo "<a href='".format($path)."'>";
	echo "<div style='width: 200px; float:left; padding: 5px'>";
	echo "<div style='height: 3em; text-align: center'>";
	echo $config['Site title'];
	echo "</div>";
	echo "<img src='/thumbnails/".format($path, 'default')."/_MAP.png' />";
	echo "</div>";
	echo "</a>";
	if(isset($config['versions']))
	{
		foreach($config['versions'] as $version => $subconfig)
		{
			if($subconfig['hidden'])
			{
				continue;
			}
			echo "<a href='".format($path."_".$version)."'>";
			echo "<div style='width: 200px; float:left; padding: 5px;'>";
			echo "<div style='height: 3em; text-align: center'>";
			echo $subconfig['Site title'];
			echo "</div>";
			echo "<img src='/thumbnails/".format($path, 'default')."/".$version."_MAP.png' />";
			echo "</div>";
			echo "</a>";
		}
	}
}

function format($path, $default='.')
{
	if($path == '')
	{
		$path = $default;
	}
	return $path;
}
?>
</body>
</html>
