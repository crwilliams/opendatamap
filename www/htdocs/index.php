<!DOCTYPE html>
<?php
error_reporting(0);

$q = $_GET['q'];
if(isset($_GET['lat']) && $_GET['lat'] != "")
	$lat = $_GET['lat'];
else
	$lat = 50.9355;
if(isset($_GET['long']) && $_GET['long'] != "")
	$long = $_GET['long'];
else
	$long = -1.39595;
if(isset($_GET['zoom']) && $_GET['zoom'] != "")
	$zoom = $_GET['zoom'];
else
	$zoom = 17;
$uri = $_GET['uri'];
?>
<html>
	<head>
		<title>University of Southampton Linked Open Data Map</title>
		<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
		<meta name="apple-mobile-web-app-capable" content="yes">
		<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
		<meta name="keywords" content="University of Southampton,map,Southampton,amenity,bus stop,building,site,campus" />
		<meta name="description" content="Map of the University of Southampton, generated from Linked Open Data" />
		<link rel="apple-touch-icon" href="img/opendatamap.png" />
		<link rel="apple-touch-icon-precomposed" href="img/opendatamap.png" />
		<link rel="shortcut icon" href="img/opendatamap.png" />
		<script src="http://www.google.com/jsapi"></script>
		<script type="text/javascript" src="js/jquery-1.5.min.js"></script>
		<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
		<script type="text/javascript" src="js/all.js"></script>
		<link href='http://fonts.googleapis.com/css?family=Ubuntu' rel='stylesheet' type='text/css'>
		<link rel="stylesheet" href="css/reset.css" type="text/css">
		<link rel="stylesheet" href="css/index.css" type="text/css">
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
	<body onload="initialize(<?php echo $lat.', '.$long.', '.$zoom.", '".$uri."'" ?>)">
		<div id="spinner"><img src="img/ajax-loader.gif"></div>
<?php/*
		<div style="width:210px; height:100%;">
			<ul style='height:40px;'>
				<li><img id="layersbutton" src='img/geoloc.png' onclick="geoloc()" alt="Toggle layers" title="Toggle layers" />Toggle layers</li>
				<li><img id="searchbutton" src='img/geoloc.png' onclick="geoloc()" alt="Search" title="Search" />Search</li>
			</ul>
			<div id='scroll-container' style='position:absolute; top:40px; left:0px; bottom:0px; width:200px; overflow: scroll; margin-left:5px; padding:0px'>
				<div id='content' style=''>
				</div>
			</div>
		</div>
*/?>
		<div id="map_canvas" style=''></div>
		<img id="geobutton" src='img/geoloc.png' onclick="geoloc()" alt="Geo-locate me!" title="Geo-locate me!" />
		<div class="toggleicons" id="toggleicons">
			<img src="img/transport.png" id="Transport" title="Transport" alt="Transport" onclick="toggle('Transport');" />
			<img src="img/catering.png" id="Catering" title="Catering" alt="Catering" onclick="toggle('Catering');" />
			<img src="img/services.png" id="Services" title="Services" alt="Services" onclick="toggle('Services');" />
			<img src="img/entertainment.png" id="Entertainment" title="Entertainment" alt="Entertainment" onclick="toggle('Entertainment');" />
			<img src="img/health.png" id="Health" title="Health" alt="Health" onclick="toggle('Health');" />
			<img src="img/religion.png" id="Religion" title="Religion" alt="Religion" onclick="toggle('Religion');" />
			<img src="img/retail.png" id="Retail" title="Retail" alt="Retail" onclick="toggle('Retail')" />
			<img src="img/education.png" id="Education" title="Education" alt="Education" onclick="toggle('Education');" />
			<img src="img/general.png" id="General" title="General" alt="General" onclick="toggle('General');" />
		</div>
		<form id='search' action="" onsubmit="return false">
			<input id="inputbox" style='width:200px' value='<?php echo $q ?>' onFocus="show('list');" onBlur="delayHide('list', 1000);">
				<img id="clear" src='http://www.picol.org/images/icons/files/png/16/search_16.png' onclick="document.getElementById('inputbox').value=''; updateFunc();" alt="Clear search" title="Clear search">
			</input>
			<ul style='display:none' id="list"></ul>
		</form>
		<div id="search-small"><img src='img/search.png' onclick="window.location='m'" alt="Search" title="Search" /></div>
		<div id="credits"><?php $include = true; include 'credits.php' ?></div>
		<div id="credits-small"><a href="credits.php">Application Credits</a></div>
	</body>
	<!--<script type="text/javascript" src="alldata.php"></script>-->
</html>
