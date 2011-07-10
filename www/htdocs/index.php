<!DOCTYPE html>
<?php
include 'config.php';

$q = $_GET['q'];
if(isset($_GET['lat']) && $_GET['lat'] != "")
	$lat = $_GET['lat'];
else
	$lat = $config['default lat'];
if(isset($_GET['long']) && $_GET['long'] != "")
	$long = $_GET['long'];
else
	$long = $config['default long'];
if(isset($_GET['zoom']) && $_GET['zoom'] != "")
	$zoom = $_GET['zoom'];
else
	$zoom = $config['default zoom'];;
$uri = $_GET['uri'];
$zoomuri = $_GET['zoomuri'];
$clickuri = $_GET['clickuri'];
?>
<html>
	<head>
		<title><?php echo $config['Site title'] ?></title>
		<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
		<meta name="apple-mobile-web-app-capable" content="yes">
		<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
		<meta name="keywords" content="<?php echo $config['Site keywords'] ?>" />
		<meta name="description" content="<?php echo $config['Site description'] ?>" />
		<meta http-equiv="X-UA-Compatible" content="IE=8" />
		<link rel="apple-touch-icon" href="img/opendatamap.png" />
		<link rel="apple-touch-icon-precomposed" href="img/opendatamap.png" />
		<link rel="shortcut icon" href="img/opendatamap.png" />
		<script src="http://www.google.com/jsapi"></script>
		<script type="text/javascript" src="js/fixie.js"></script>
		<script type="text/javascript" src="js/jquery-1.6.1.min.js"></script>
		<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
		<script type="text/javascript" src="js/all.js"></script>
		<link href='http://fonts.googleapis.com/css?family=Ubuntu' rel='stylesheet' type='text/css'>
		<link rel="stylesheet" href="css/reset.css" type="text/css">
		<link rel="stylesheet" href="css/index.css" type="text/css">
		<link rel="stylesheet" href="css/print.css" type="text/css" media="print">
	</head>
	<body onload="initialize(<?php echo $lat.', '.$long.', '.$zoom.", '".$uri."', '".$zoomuri."', '".$clickuri."', '".$_GET['v']."', ".$config['default map'] ?>)">
<? include_once 'googleanalytics.php'; ?>
		<div id="spinner"><img src="img/ajax-loader.gif"></div>
<?php if(has('openday')) { ?>
		<div id="openday">
			<?php include 'resources/opendaysubjects.php' ?>
		</div>
<?php } ?>
<?php if(has('opendayhidden')) { ?>
		<div id="openday" style='display:none'>
			<?php include 'resources/opendaysubjects.php' ?>
		</div>
<?php } ?>
		<div id="map_canvas" style='<?php echo $config['map style'] ?>'></div>
		<img id="geobutton" <?php show('geobutton') ?> src='img/geoloc.png' onclick="geoloc()" alt="Geo-locate me!" title="Geo-locate me!" />
		<div class="toggleicons" id="toggleicons" <?php show('toggleicons') ?>>
<?
foreach($config['categories'] as $catid => $catname)
{
?>
			<img class="deselected" src="img/icon/<?= $catid ?>/blank.png" id="<?= $catid ?>" title="<?= $catname ?>" alt="<?= $catname ?>" onclick="toggle('<?= $catid ?>');" />
<?
}
?>
		</div>
		<form id='search' <?php show('search') ?>action="" onsubmit='return false'>
			<input id="inputbox" style='width:200px' value='<?php echo $q ?>' onFocus="show('list');" onBlur="delayHide('list', 1000);">
				<img id="clear" src='http://www.picol.org/images/icons/files/png/16/search_16.png' onclick="document.getElementById('inputbox').value=''; updateFunc();" alt="Clear search" title="Clear search" />
			</input>
			<ul style='display:none' id="list"></ul>
		</form>
<?php if(has('bookmarks')) { ?>
		<div id="bookmarks" style='display:none'>
			<?php include 'resources/opendaybookmarks.php' ?>
		</div>
<?php } ?>
		<div id="search-small" <?php show('search') ?>><img src='img/search.png' onclick="window.location='m'" alt="Search" title="Search" /></div>
		<div id="credits"><?php $include = true; include 'credits.php' ?></div>
		<div id="credits-small"><a href="credits.php">Application Credits</a></div>
	</body>
</html>
