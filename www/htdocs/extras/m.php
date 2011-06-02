<!DOCTYPE html>
<?php
$pathtoroot = "../";
error_reporting(0);
if(!$include && substr($_SERVER['REQUEST_URI'], -4, 4) == '.php')
	header('Location: m');
include $pathtoroot.'inc/options.php';
?>
<html>
	<head>
		<title>University of Southampton Linked Open Data Map</title>
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
		<form onsubmit='window.location.href="/?"+$("#loc").get(0).value+"&q="+$("#q").get(0).value; return false;'>
			<div style='border:solid 3px gray; margin:10px; padding:10px;'>
			I'm looking for...<br/>
			<select id='generic' onchange='selectGenericOffering(this.options[selectedIndex].value)'>
				<?php getGenericOfferings(); ?>
			</select><br/>
			or...<br/>
			<select id='specific' onchange='selectSpecificOffering(this.options[selectedIndex].value)'>
				<?php getSpecificOfferings(); ?>
			</select>
			</div>
			<div style='border:solid 3px gray; margin:10px; padding:10px;'>
			near...<br/>
			<select name='site' id='site' onchange='selectSite(this.options[selectedIndex].value)'>
				<?php getSites(); ?>
			</select><br/>
			or...<br/>
			<select id='building-number' onchange='selectBuilding(this.options[selectedIndex].value)'>
				<?php getBuildingsNumber(); ?>
			</select>
			<select id='building-name' onchange='selectBuilding(this.options[selectedIndex].value)'>
				<?php getBuildingsName(); ?>
			</select><br/>
			or...<br/>
			<input type='button' value='Near my current location' onclick='geoloc()' /><span id='geoinfo'></span>
			</div>
			<input id='loc' type='hidden' value='' />
			<input id='q' type='hidden' value='' />
			<div style='border:solid 3px gray; margin:10px; padding:10px;'>
				<input type='submit' value='Show me' />
			</div>
		<form>
		<div id="credits"><?php $include = true; include '../credits.php' ?></div>
	</body>
</html>
