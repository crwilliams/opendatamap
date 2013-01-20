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
foreach(glob('config.*.php') as $cfg_file)
{
	$config = array();
	include $cfg_file;
	if($config['hidden'])
	{
		continue;
	}
	$path = explode(".", $cfg_file);
	$path = $path[1];
	if($path == 'local')
	{
		$path = '';
	}
	$maps[$path] = $config;
}
ksort($maps);

echo "<ul>";
foreach($maps as $path => $config)
{
	echo "<li>";
	echo "<a href='".format($path)."'>".$config['Site title']."</a>";
	if(isset($config['versions']))
	{
		$open = false;
		foreach($config['versions'] as $version => $subconfig)
		{
			if($subconfig['hidden'])
			{
				continue;
			}
			if(!$open)
			{
				echo "<ul>";
				$open = true;
			}
			echo "<li>";
			echo "<a href='".format($path."_".$version)."'>".$subconfig['Site title']."</a>";
			echo "</li>";
		}
		if($open)
		{
			echo "</ul>";
		}
	}
	echo "</li>";
}
echo "</ul>";

function format($path)
{
	if($path == '')
	{
		$path = '.';
	}
	return $path;
}
?>
</body>
</html>
