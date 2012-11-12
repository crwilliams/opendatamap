<?php
include_once "config.php";
header('Content-type: application/json');
echo '[';
foreach($config['datasource'] as $ds)
{
	$dsclass = ucwords($ds).'DataSource';
	foreach(call_user_func(array($dsclass, 'getAllSites')) as $site)
	{
		echo '[';
		echo '["'.$site['uri'].'"],';
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
		if(isset($site['color']))
		{
			echo ',"'.$site['color'].'"';
		}
		else
		{
			echo ',""';
		}
		echo ',[0, 0]';
		echo '],';
	}
}

foreach($config['datasource'] as $ds)
{
	$dsclass = ucwords($ds).'DataSource';
	foreach(call_user_func(array($dsclass, 'getAllBuildings')) as $building)
	{
		echo '[';
		echo '["'.$building['uri'].'"],';
		echo '["<img class=\'icon\' style=\'width:20px;\' src=\'resources/numbericon.php?n='.$building['num'].'\' /> '.$building['name'].' <a class=\'odl\' href=\''.$building['uri'].'\'>Visit building page</a>"],';
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
			echo '['.$building['lng'].','.$building['lat'].']]';
		}
		if(isset($building['color']))
		{
			echo ',"'.$building['color'].'"';
		}
		else
		{
			echo ',""';
		}
		echo ',['.$building['lng'].','.$building['lat'].']';
		echo '],';
	}
}
echo '[]]';
?>
