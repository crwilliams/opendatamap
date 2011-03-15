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

var initmarkers = function(cont) {
  console.log('initmarkers');
  jQuery.get('alldata.php', function(data,textstatus,xhr) {
	       // do party!!!!
	       // clear em out, babes. 
	       window.markers = {};
	       window.infowindows = {};
	       // refill ...
	       data.map(function(markpt) {
			  if (markpt.length == 0) return;
			  var pos = markpt[0];
			  var lat = markpt[1];
			  var lon = markpt[2];
			  var poslabel = markpt[3];
			  var icon = markpt[4];
			  markers[pos] = new google.maps.Marker({
					  position: new google.maps.LatLng(lat, lon), 
					      title: poslabel,
					      map: window.map,
					      icon: icon,
					      visible: false
					      });
			  infowindows[pos] = new google.maps.InfoWindow({ content: '<div id="content"><h2 id="title"><img style="width:20px;" src="'+icon+'" />'+poslabel+'</h2><div id="bodyContent">Loading...</div></div>'});
			});
	       // console.log('markers:', markers, 'info ', infowindows);
	       cont();
	     },'json');
};

var initialize = function() {
	var myLatlng = new google.maps.LatLng(<?php echo $lat; ?>, <?php echo $long; ?>);
	var myOptions = {
		zoom: <?php echo $zoom; ?>,
		center: myLatlng,
		mapTypeId: google.maps.MapTypeId.ROADMAP
	};
	map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);

	var kmlOptions = {
		preserveViewport: true
	}

	var bldgLayer = new google.maps.KmlLayer('http://opendatamap.ecs.soton.ac.uk/buildings.php', kmlOptions);
	bldgLayer.setMap(map);
	var siteLayer = new google.maps.KmlLayer('http://opendatamap.ecs.soton.ac.uk/sites.php', kmlOptions);
	siteLayer.setMap(map);

	initmarkers(function() {
		      	initmarkerevents();
			initgeoloc();
			inittoggle();
			initcredits();
			initsearch();
			
			var inputBox = document.getElementById("inputbox");
			inputBox.onkeyup = updateFunc;
			inputBox.onkeydown = nav;
			updateFunc();
		    });
}
