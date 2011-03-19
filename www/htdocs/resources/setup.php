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
?>
var map;
var updateFunc;
var nav;

var initmarkers = function(cont) {
	// console.log('initmarkers');
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
	jQuery.get('polygons.php', function(data,textstatus,xhr) {
		// do party!!!!
		// clear em out, babes. 
		window.polygons = {};
		window.polygoninfowindows = {};
		// refill ...
		data.map(function(markpt) {
			if (markpt.length == 0) return;
			var pos = markpt[0];
			var poslabel = markpt[1];
			var zindex = markpt[2];
			var points = markpt[3];
 			var paths = new Array();
			var i;
			for(i=0; i < points.length-1; i++) {
				paths.push(new google.maps.LatLng(points[i][1], points[i][0])); 
			}
			if(paths.length == 0)
			{
				if(polygons[pos] === undefined)
					polygons[pos] = new Array();
				polygons[pos] = new google.maps.Marker({
					position: new google.maps.LatLng(points[i][1], points[i][0]),
					title: new String(poslabel).replace("'", "&apos;"),
					icon: 'http://google-maps-icons.googlecode.com/files/black00.png',
					map: window.map,
					visible: true
				});
				console.log(polygons[pos].getPosition());
			}
			else
			{
				var fc = '#0000FF';
				var sc = '#0000FF';
				var pType = 'Building';
				if(zindex == -10)
				{
					fc = '#0099FF';
					sc = '#0099FF';
					var pType = 'Site';
				}

				if(polygons[pos] === undefined)
					polygons[pos] = new Array();
				polygons[pos].push(new google.maps.Polygon({
					paths: paths,
					title: poslabel,
					map: window.map,
					zIndex: zindex,
					fillColor: fc,
					fillOpacity: 0.2,
					strokeColor: sc,
					strokeOpacity: 1.0,
					strokeWeight: 2.0,
					visible: true
				}));
			}
			polygoninfowindows[pos] = new google.maps.InfoWindow({ content: '<div id="content"><h2 id="title">'+poslabel+'</h2></div>'});
//			with ({ j: pos, pType: pType })
//			{
				var listener;
				if(paths.length == 0)
				{
					listener = polygons[pos];
				}
				else
				{
					listener = polygons[pos][polygons[pos].length-1];
				}
				google.maps.event.addListener(listener, 'click', function(event) {
					closeAll();
					_gaq.push(['_trackEvent', 'InfoWindow', pType, pos]);
					if(event.latLng !== undefined)
					{
						polygoninfowindows[pos].setPosition(event.latLng);
						polygoninfowindows[pos].open(window.map);
					}
					else
					{
						polygoninfowindows[pos].open(window.map, polygons[pos]);
					}
					//console.log(polygoninfowindows[pos].getContent());
				});
//			}
		// console.log('markers:', markers, 'info ', infowindows);
		});
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

//	var bldgLayer = new google.maps.KmlLayer('http://opendatamap.ecs.soton.ac.uk/buildings.php', kmlOptions);
//	bldgLayer.setMap(map);
//	var siteLayer = new google.maps.KmlLayer('http://opendatamap.ecs.soton.ac.uk/sites.php', kmlOptions);
//	siteLayer.setMap(map);

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
