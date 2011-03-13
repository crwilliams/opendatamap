<?php
error_reporting(0);

$q = $_GET['q'];
if(isset($_GET['lat']) && $_GET['lat'] != "")
	$lat = $_GET['lat'];
else
	$lat = 50.93463;
if(isset($_GET['long']) && $_GET['long'] != "")
	$long = $_GET['long'];
else
	$long = -1.39595;
if(isset($_GET['zoom']) && $_GET['zoom'] != "")
	$zoom = $_GET['zoom'];
else
	$zoom = 15;
?>
var map;
var updateFunc;
var nav;

var initialize = function() {
	var myLatlng = new google.maps.LatLng(<?php echo $lat; ?>, <?php echo $long; ?>);
	var myOptions = {
		zoom: <?php echo $zoom; ?>,
		center: myLatlng,
		mapTypeId: google.maps.MapTypeId.ROADMAP
	};
	map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
	
	initmarkers();
	initmarkerevents();
	initgeoloc();
	inittoggle();
	initcredits();
	initsearch();

	var inputBox = document.getElementById("inputbox");
	inputBox.onkeyup = updateFunc;
	inputBox.onkeydown = nav;
	updateFunc();
}
