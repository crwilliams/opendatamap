<!DOCTYPE html>
<?php
$pathtoroot = "../";
error_reporting(0);
if(!$include && substr($_SERVER['REQUEST_URI'], -4, 4) == '.php')
	header('Location: bus-route-finder');
include $pathtoroot.'inc/options.php';
?>
<html>
	<head>
		<title>Find a bus route between campuses</title>
		<!--<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />-->
		<meta name="apple-mobile-web-app-capable" content="yes">
		<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
		<meta name="keywords" content="University of Southampton,map,Southampton,amenity,bus stop,building,site,campus" />
		<meta name="description" content="Map of the University of Southampton, generated from Linked Open Data" />
		<link rel="apple-touch-icon" href="img/opendatamap.png" />
		<link rel="apple-touch-icon-precomposed" href="img/opendatamap.png" />
		<link rel="shortcut icon" href="img/opendatamap.png" />
		<script src="http://www.google.com/jsapi"></script>
		<script type="text/javascript" src="js/jquery-1.5.min.js"></script>
		<script type="text/javascript" src="js/m.js"></script>
		<link href='http://fonts.googleapis.com/css?family=Ubuntu' rel='stylesheet' type='text/css'>
		<link rel="stylesheet" href="css/reset.css" type="text/css">
		<link rel="stylesheet" href="css/m.css" type="text/css">
		<script type="text/javascript">
		var _gaq = _gaq || [];
		_gaq.push(['_setAccount', 'UA-20609696-4']);
		_gaq.push(['_trackPageview']);

		(function() {
			var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
			ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
			var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		})();

		</script>
	</head>
	<body>
		<form onsubmit='window.location.href="?from="+$("#from").get(0).value+"&to="+$("#to").get(0).value; return false;'>
			<div style='border:solid 3px gray; margin:10px; padding:10px;'>
			I'd like to travel from...<br/>
			<select name='from' id='from' onchange='selectSite(this.options[selectedIndex].value)'>
				<?php getPlaces($_GET['from']); ?>
			</select><br/>
			to...<br/>
			<select name='to' id='to' onchange='selectSite(this.options[selectedIndex].value)'>
				<?php getPlaces($_GET['to']); ?>
			</select><br/>
			</div>
			<div style='border:solid 3px gray; margin:10px; padding:10px;'>
				<input type='submit' value='Show me' />
			</div>
		<form>
		<div style='border:solid 3px gray; margin:10px; padding:10px;'>
<?php
if(isset($_GET['from']) && isset($_GET['to']))
{
	getRoutes($_GET['from'], $_GET['to']);
}
?>
		</div>
		<div id="credits"><?php $include = true; include '../credits.php' ?></div>
	</body>
</html>
