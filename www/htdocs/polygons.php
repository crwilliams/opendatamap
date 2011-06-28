<?php
include_once "config.php";

echo '[';
foreach($config['datasource'] as $ds)
{
	$dsclass = ucwords($ds).'DataSource';
	foreach(call_user_func(array($dsclass, 'getAllSites')) as $site)
	{
		echo '[';
		echo '["'.$site['url'].'"],';
		echo '["'.$site['name'].'"],';
		echo '-10,';
		echo '[';
		$site['outline'] = explode(",", str_replace(array("POLYGON((", "))"), "", $site['outline']));
		foreach($site['outline'] as $polygon)
		{
			echo '[';
			echo str_replace(' ', ',', $polygon);
			echo '],';
		}
		echo '[]]';
		echo '],';
	}
}

foreach($config['datasource'] as $ds)
{
	$dsclass = ucwords($ds).'DataSource';
	foreach(call_user_func(array($dsclass, 'getAllBuildings')) as $building)
	{
		echo '[';
		echo '["'.$building['url'].'"],';
		echo '["<img class=\'icon\' style=\'width:20px;\' src=\'resources/numbericon.php?n='.$building['number'].'\' /> '.$building['name'].'"],';
		echo '-5,';
		echo '[';
		if($building['outline'] != "")
		{
			$building['outline'] = str_replace(" -1", ",-1", $building['outline']);
			$building['outline'] = explode(",", str_replace(array("POLYGON((", "))"), "", $building['outline']));
			foreach($building['outline'] as $polygon)
			{
				echo '[';
				echo str_replace(' ', ',', $polygon);
				echo '],';
			}
			echo '[]]';
		}
		else
		{
			echo '['.$building['long'].','.$building['lat'].']]';
		}
		echo '],';
	}
}
echo '[]]';
?>
