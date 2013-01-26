<html>
<head>
	<link rel="stylesheet" href="css/reset.css" type="text/css">
	<link rel="stylesheet" href="css/index.css" type="text/css">
	<link rel="stylesheet" href="css/credits.css" type="text/css">
	<title>opendatamap | <?= $config['Site title'] ?></title>
</head>
<body>
<h1>opendatamap</h1>
<h2><?= $config['Site title'] ?></h2>
<? include_once 'googleanalytics.php'; ?>
<?
	echo "<p>Please choose a map from the following:</p>";
	if(count($config['versions']) > 10)
	{
		foreach($config['versions'] as $version => $subconfig)
		{
			$sites[strtoupper($subconfig['Site subtitle'][0])][$version] = $subconfig;
		}
		foreach(range('A', 'Z') as $l)
		{
			if(isset($sites[$l]))
			{
				echo '<a href="#'.$l.'" style="padding: 0.5em;">'.$l.'</a>';
			}
			else
			{
				echo '<span style="padding: 0.5em;">'.$l.'</span>';
			}
		}
	}
	foreach(range('A', 'Z') as $l)
	{
		if(isset($sites[$l]))
		{
			echo "<div name='".$l."' id='".$l."'>";
			echo "<h3>".$l."</h3>";
			echo "<ul>";
			foreach($sites[$l] as $version => $subconfig)
			{
				echo "<li>";
				echo "<a href='".$path."_".$version."'>".$subconfig['Site subtitle']."</a>";
				echo "</li>";
			}
			echo "</ul>";
			echo "</div>";
		}
	}
?>
</body>
</html>
