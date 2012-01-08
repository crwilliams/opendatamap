<?
session_start();
?>
<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>OpenDataMap mymap: Geo Data Set Editor</title>
    <link rel="stylesheet" type="text/css" href="../../css/jquery-ui.css" />
<?php

function getLatLongFromPostcode($postcode)
{
	require_once('/home/opendatamap/mysql.inc.php');
	$params[] = mysql_real_escape_string(str_replace(' ', '', $postcode));
	$q = 'SELECT latitude AS lat, longitude AS lon FROM postcode WHERE code = \''.$params[0].'\'';
	$res = mysql_query($q);
	if($row = mysql_fetch_assoc($res))
	{
		return $row;
	}
	return null;
}

function loadCSV($filename, $base="", $idcolname, $namecolname, $iconcolname, $latcolname, $loncolname, $pccolname, $location)
{
	$colnames = null;
	if (($handle = fopen($filename, "r")) !== FALSE) {
		while (($row = @fgetcsv($handle, 1000, ",")) !== FALSE) {
			if($row[0] == '*COMMENT' || $row[0] == '')
				continue;
			$num = count($row);
			if($colnames == null)
			{
				for ($c=0; $c < $num; $c++) {
					$colnames[strtolower($row[$c])] = $c;
				}
			}
			else
			{
				@$data[$base.$row[$colnames[$idcolname]]] = array(
					'label' => str_replace('\'', '&apos;', $row[$colnames[$namecolname]]),
					'icon' => $row[$colnames[$iconcolname]],
					'lat' => $row[$colnames[$latcolname]],
					'lon' => $row[$colnames[$loncolname]],
					'pc' => $row[$colnames[$pccolname]],
				);
				if(
					isset($data[$base.$row[$colnames[$idcolname]]]['lat']) &&
					'' != $data[$base.$row[$colnames[$idcolname]]]['lat'] &&
					isset($data[$base.$row[$colnames[$idcolname]]]['lon']) &&
					'' != $data[$base.$row[$colnames[$idcolname]]]['lon']
				)
				{
					$data[$base.$row[$colnames[$idcolname]]]['source'] = 'CSV';
				}
				else if(isset($data[$base.$row[$colnames[$idcolname]]]['pc']) && '' != $data[$base.$row[$colnames[$idcolname]]]['pc'])
				{
					$ll = getLatLongFromPostcode($data[$base.$row[$colnames[$idcolname]]]['pc']);
					if(!is_null($ll))
					{
						$data[$base.$row[$colnames[$idcolname]]]['lat'] = $ll['lat'];
						$data[$base.$row[$colnames[$idcolname]]]['lon'] = $ll['lon'];
						$data[$base.$row[$colnames[$idcolname]]]['source'] = '<em>'.$data[$base.$row[$colnames[$idcolname]]]['pc'].'</em>';
					}
				}
				if(isset($location[$row[$colnames[$idcolname]]]))
				{
					$data[$base.$row[$colnames[$idcolname]]]['lat'] = $location[$row[$colnames[$idcolname]]][0];
					$data[$base.$row[$colnames[$idcolname]]]['lon'] = $location[$row[$colnames[$idcolname]]][1];
					$data[$base.$row[$colnames[$idcolname]]]['source'] = $location[$row[$colnames[$idcolname]]][2];
				}
			}
		}
		fclose($handle);
	}
	
	foreach($location as $id => $r)
	{
		if(!isset($data[$id]))
		{
			$data[$id] = array('label' => $r[3], 'icon' => $r[4], 'lat' => $r[0], 'lon' => $r[1], 'source' => $r[2]);
		}
	}

	return $data;
}

$data = null;
if($_REQUEST['m'] == 'iss-wifi')
{
	foreach(array('A', 'B', 'C', 'D', 'E', 'F', 'G') as $l)
		$data[$l] = array('label' => 'Access Point '.$l, 'icon' => 'http://data.southampton.ac.uk/map-icons/Offices/wifi.png');
}
elseif($_REQUEST['m'] == 'amenities')
{
	$data = loadCSV('https://spreadsheets.google.com/pub?hl=en&hl=en&key=0AqodCQwjuWZXdDhaVzVrWVlfMGNfUmFrTW5nZmRyVHc&output=csv', 'http://id.southampton.ac.uk/point-of-service/', 'code', 'name', 'icon', 'latitude', 'longitude', 'postcode', array());
}
else
{
	require_once('/home/opendatamap/mysql.inc.php');
	$params[] = mysql_real_escape_string($_REQUEST['u']);
	$params[] = mysql_real_escape_string($_REQUEST['m']);
	$q = 'SELECT uri, lat, lon, source, name, icon FROM mappoints WHERE username = \''.$params[0].'\' AND map = \''.$params[1].'\'';
	$res = mysql_query($q);
	$location = array();
	while($row = mysql_fetch_assoc($res))
	{
		$location[$row['uri']] = array($row['lat'], $row['lon'], $row['source'], $row['name'], $row['icon']);
	}
	$q = 'SELECT source FROM maps WHERE username = \''.$params[0].'\' AND mapid = \''.$params[1].'\'';
	$res = mysql_query($q);
	if($row = mysql_fetch_assoc($res))
	{
		if(substr($row['source'], 0, 7) == 'http://' || substr($row['source'], 0, 8) == 'https://')
		{
			$data = loadCSV($row['source'], '', 'code', 'name', 'icon', 'latitude', 'longitude', 'postcode', $location);
		}
		else
			$data = null;
	}
	else
	{
		$data = null;
	}
}

//if(!isset($_SESSION['username']) || !isset($_REQUEST['graphuri']) || $_REQUEST['graphuri'] == '')
if(is_null($data))
{
?>
  </head>
	<body>
		Map not found.
	</body>
<?php
}
else
{
?>
    <style type="text/css">
	html, body, #map {
	    height: 100%;
	    margin: 0px;
	    padding: 0px;
	}

	#map {
		z-index: 0;
		position: fixed;
		top: 0;
		left: 0;
		width: 80%;
	}
        .olControlAttribution { bottom: 0px!important }

        /* avoid pink tiles */
        .olImageLoadError {
            background-color: transparent !important;
        }

	#controls {
		position: absolute;
		width: 18%;
		height: 100%;
		top: 1%;
		right: 1%;
		z-index: 1000;
		background-color:white;
	}

	#list {
		margin: 0;
		padding: 0;
	}
	
	#listheader {
		position: fixed;
		right: 21%;
		top: 1%;
		z-index: 1000;
		margin: 0;
		padding: 0;
		border: none;
		background-color: white;
	}

	#links,#actionText,#save,#list {
	}

	#actionText {
		margin: 5px;
		height: 50%;
	}

	#links,#save {
		margin: 5px;
		height: 20%;
		text-align: right;
	}

	span.small {
		font-size: 0.6em;
		color: gray;
	}

	ul {
		list-style: none;
		margin: 0;
		padding: 0;
	}

	#list li {
		padding: 5px;
		border: solid 1px black;
		margin: 3px;
	}

	#icon-classes {
		font-size: 0.5em;
	}

	#icon-classes img {
		padding: 1px;
	}

	#dialog-modal td {
		vertical-align: top;
	}

	#dialog-modal label {
		top: 5px;
		position: relative;
	}

	#dialog-modal input {
		width: 35em;
	}

<?php
$col['Nature'] = '128e4d';
$col['Industry'] = '265cb2';
$col['Offices'] = '3875d7';
$col['Stores'] = '5ec8bd';
$col['Tourism'] = '66c547';
$col['Restaurants-and-Hotels'] = '8c4eb8';
$col['Transportation'] = '9d7050';
$col['Media'] = 'a8a8a8';
$col['Events'] = 'c03638';
$col['Culture-and-Entertainment'] = 'c259b5';
$col['Health'] = 'f34648';
$col['Sports'] = 'ff8a22';
$col['Education'] = 'ffc11f';
$col['Suggestions'] = '333333';
foreach($col as $name => $colour)
{
?>
	li#tab-<?php echo $name ?> {
		background:#<?php echo $colour ?>;
	}
	#tab-<?php echo $name ?> span {
		color:white;
	}
<?
}
?>

	a:link, a:hover, a:visited {
		text-decoration: none;
		color: blue;
	}

/* Vertical Tabs
----------------------------------*/
.ui-tabs-vertical { width: 55em; }
.ui-tabs-vertical .ui-tabs-nav { padding: .2em .1em .2em .2em; float: left; width: 15em; }
.ui-tabs-vertical .ui-tabs-nav li { clear: left; width: 100%; border-bottom-width: 1px !important; border-right-width: 0 !important; margin: 0 -1px .2em 0; }
.ui-tabs-vertical .ui-tabs-nav li a { display:block; }
.ui-tabs-vertical .ui-tabs-nav li.ui-tabs-selected { padding-bottom: 0; padding-right: .1em; border-right-width: 1px; border-right-width: 1px; }
.ui-tabs-vertical .ui-tabs-panel { padding: 1em; float: right; width: 37em;}

    </style>

    <script src="../../OpenLayers-2.11/OpenLayers.js"></script>
    <script src="../../OS.js"></script>
    <script src="../../jquery-1.6.2.min.js"></script>
    <script src="../../jquery-ui-1.8.16.min.js"></script>
    <script type="text/javascript">
$(function() {
});

// make map available for easy debugging
var map;
var vector;
var markers;
var p = new Array();
var changed = new Array();
var ll = new Array();
var wgs84 = new OpenLayers.Projection("EPSG:4326");
var positionUri;
var label = new Array();
var icons = new Array();
var iconCounts = new Array();

// increase reload attempts 
OpenLayers.IMAGE_RELOAD_ATTEMPTS = 3;

function focusPoint(positionUri) {
	var existingMarker = markers.getFeatureByFid(positionUri);
	if(existingMarker != null)
	{
        	map.panTo(new OpenLayers.LonLat(existingMarker.geometry.x, existingMarker.geometry.y));
	}
}

function selectIcon(uri) {
	$('#icon')[0].value = uri;
	$('#selected-icon')[0].src = uri;
}

function drop(positionUri, pixel, requireUpdateFeature) {
    if(positionUri == undefined)
	return;
    var lonlat = map.getLonLatFromViewPortPx(pixel);
    var llc = lonlat.clone();

    if(requireUpdateFeature)
    {
	var existingMarker = markers.getFeatureByFid(positionUri);
	if(existingMarker != null)
	{
	    markers.removeFeatures(existingMarker);
	}

	p[positionUri] = new OpenLayers.Feature.Vector(new OpenLayers.Geometry.Point(llc.lon, llc.lat), positionUri, { externalGraphic: icons[positionUri], graphicWidth: 32, graphicHeight: 37, graphicXOffset: -16, graphicYOffset: -37, graphicTitle: label[positionUri] });
	p[positionUri].fid = positionUri;
	markers.addFeatures(p[positionUri]);
    }
    changed[positionUri] = true;
    llc.transform(map.getProjectionObject(), wgs84);
    document.getElementById('loc_'+positionUri).innerHTML = Math.round(llc.lat*1000000)/1000000+'/'+Math.round(llc.lon*1000000)/1000000;
    positionUri = undefined;
    document.getElementById('save_link').style.display = "block";
}

function save(){
	var str = '';
	var i = 0;
	for (var q in changed)
	{
		var llc = p[q].geometry.clone();
		llc.transform(map.getProjectionObject(), wgs84);
		str += q + '|' + llc.y + '|' + llc.x + '|' + label[q] + '|' + icons[q] + '||';
		i++;
	}
	OpenLayers.Request.POST( {
		url : 'http://opendatamap.ecs.soton.ac.uk/dev/colin/edit/save.php?username=<?php echo $_REQUEST['u'] ?>&map=<?php echo $_REQUEST['m'] ?>',
		data : str,
		success : function(response) {
			for (var q in changed)
			{
				document.getElementById('loc_'+q).innerHTML += ' (OS)';
			}
			changed = new Array();
			document.getElementById('save_link').style.display = 'none';
		},
		failure : function(response) { alert(response.responseText) },
	} );
	return i;
}

var lastevent;
var prevname = '';

function processName() {
	if(prevname != $('#name')[0].value)
		$('#uri')[0].value = $('#name')[0].value.toLowerCase().replace(/[^a-z0-9]/g, '-');
	prevname = $('#name')[0].value;
}

function newDialog(pixel){
	$('#name')[0].value = '';
	$('#uri')[0].value = '';
	$('#dialog-modal').dialog({
		width: '40em',
		modal: true,
		buttons: {
			Ok: function() {
				var uri = $('#uri')[0].value;
				if($('#name')[0].value == '')
					alert('Title not set.');
				else if(uri == '')
					alert('ID not set.');
				else if(uri != uri.toLowerCase().replace(/[^a-z0-9]/g, '-'))
				{
					alert('ID does not meet requirements.  Updating ID...');
					$('#uri')[0].value = uri.toLowerCase().replace(/[^a-z0-9]/g, '-');
				}
				else if($('#icon')[0].value == '')
					alert('Icon not set.');
				else if(p[uri] != undefined)
					alert('Point with this ID already exists.  Please choose a new ID.');
				else
				{
					$( this ).dialog( "close" );
					icons[uri] = $('#icon')[0].value;
					label[uri] = $('#name')[0].value;
					var newli = "<li id='" + uri + "' onclick=\"focusPoint('" + uri + "');\"><img class='draggable' style='z-index:1000; float:left; margin-right:5px' src='" + icons[uri] + "' />" + label[uri] + "<br/><span class='small' id='loc_" + uri + "'>Location not set</span></li>";
					$("#points").append(newli);
    					$("#" + uri + " .draggable").draggable({
						cursorAt: {cursor: "crosshair", top: 39, left: 17},
						helper: function(event) {lastevent = event; return $("<img src='"+event.currentTarget.src+"' />")},
						revert: "invalid"
					});
					drop(uri, pixel, true);
				}
			}
		}
	});
}

function init(){
    $(".draggable").draggable({
	cursorAt: {cursor: "crosshair", top: 39, left: 17},
	helper: function(event) {lastevent = event; return $("<img src='"+event.currentTarget.src+"' />")},
	revert: "invalid"
    });

    $('#icon-classes').tabs({
			ajaxOptions: {
				error: function( xhr, status, index, anchor ) {
					$( anchor.hash ).html("Failed");
				}
			}
    }).addClass('ui-tabs-vertical ui-helper-clearfix');
    $('#icon-classes li').removeClass('ui-corner-top').addClass('ui-corner-left');

    $("#map").droppable({
	drop: function(event, ui) {
		var id = lastevent.currentTarget.parentElement.id;
		lastevent = event;
		var pixel = new OpenLayers.Pixel(event.pageX-window.pageXOffset-1, event.pageY-window.pageYOffset-2);
		if(id == '_new_')
		{
			newDialog(pixel);
		}
		else
		{
			drop(id, pixel, true);
		}
		lastevent = event;
	 },
    });

    var maxExtent = new OpenLayers.Bounds(-20037508, -20037508, 20037508, 20037508),
        restrictedExtent = maxExtent.clone(),
        maxResolution = 156543.0339;
    
    var options = {
        projection: new OpenLayers.Projection("EPSG:900913"),
        displayProjection: new OpenLayers.Projection("EPSG:4326"),
        units: "m",
        numZoomLevels: 18,
        maxResolution: maxResolution,
        maxExtent: maxExtent,
        restrictedExtent: restrictedExtent
    };
    map = new OpenLayers.Map('map', options);

    var streetview = new OpenLayers.Layer.StreetView("OS StreetView (1:10000)");

    markers = new OpenLayers.Layer.Vector("Editable Markers");

    map.addLayers([streetview, markers]);

    var features = new Array();
<?
foreach($data as $uri => $point)
{
	if($point['lat'] == '' || $point['lon'] == '')
		continue;
	echo "ll['$uri'] = new OpenLayers.LonLat(".$point['lon'].", ".$point['lat'].");\n";
	echo "ll['$uri'].transform(wgs84, map.getProjectionObject());\n";
	echo "p['$uri'] = new OpenLayers.Feature.Vector(new OpenLayers.Geometry.Point(ll['$uri'].lon, ll['$uri'].lat), '$uri', { externalGraphic: icons['$uri'], graphicWidth: 32, graphicHeight: 37, graphicXOffset: -16, graphicYOffset: -37, graphicTitle: label['$uri'] });\n";
	echo "p['$uri'].fid = '$uri';\n";
	echo "features.push(p['$uri']);\n";
}
?>
    markers.addFeatures(features);
    if (!map.getCenter()) {
	if(markers.features.length == 0)
	{
		bounds = new OpenLayers.Bounds(-6.379880, 49.871159, 1.768960, 55.811741);
        	bounds.transform(wgs84, map.getProjectionObject());
	}
	else
	{
		bounds = markers.getDataExtent();
	}
        map.zoomToExtent(bounds);
        if (map.getZoom() < 6) map.zoomTo(6);
    }

                var drag = new OpenLayers.Control.DragFeature(markers, {
		    onComplete : function(feature, pixel)
		    {
			drop(feature.fid, pixel, false);
		    }
		});
                map.addControl(drag);
                drag.activate();
}


<?php
foreach($data as $uri => $item)
{
	echo "label['$uri'] = '".$item['label']."';\n";
	echo "icons['$uri'] = '".$item['icon']."';\n";
	@$iconcounts[$item['icon']]++;
}
foreach($iconcounts as $k => $v)
{
	echo "iconCounts['$k'] = $v;\n";
}
?>

    </script>
  </head>
  <body onload="init()">
    <div id='listheader'>
	<ul id='links'>
		<li><a href='../../<?= $_REQUEST['u'] ?>'>Back to map list <img src='../../icons/map.png' /></a></li>
		<li><a href='../<?= $_REQUEST['m'] ?>.rdf'>View RDF <img src='../../icons/page_white_code.png' /></a></li>
		<li><a href='../<?= $_REQUEST['m'] ?>.kml'>View KML <img src='../../icons/page_white_code.png' /></a></li>
		<li id='save_link' style='display: none'><a href='#' onclick='save();'>Save <img src='../../icons/disk.png' /></a></li>
	</div>
    </div>
    <div id="controls">
	<div id='list'>
	<ul id='points'>
		<li id='_new_'><img class='draggable' style='z-index:1000; float:left; margin-right:5px' src='http://opendatamap.ecs.soton.ac.uk/img/icon/Media/blank.png' />New Point<br /><span class='small'>Drag to location to add new point.</span></li>
<?php
foreach($data as $uri => $item)
{
	if(isset($item['lat']) && isset($item['lon']) && $item['lat'] != '' && $item['lon'] != '')
		continue;
	echo "<li id='$uri' onclick=\"focusPoint('$uri');\"><img class='draggable' style='z-index:1000; float:left; margin-right:5px' src='".$item['icon']."' />".$item['label']."<br/><span class='small' id='loc_$uri'>";
	echo "Location not set.";
	echo "</span></li>\n";
}

foreach($data as $uri => $item)
{
	if(!(isset($item['lat']) && isset($item['lon']) && $item['lat'] != '' && $item['lon'] != ''))
		continue;
	echo "<li id='$uri' onclick=\"focusPoint('$uri');\"><img class='draggable' style='float:left; margin-right:5px' src='".$item['icon']."' />".$item['label']."<br/><span class='small' id='loc_$uri'>";
	echo round($item['lat'], 6).'/'.round($item['lon'], 6).' ('.$item['source'].')';
	echo "</span></li>\n";
}
?>
	</ul>
    	</div>
    </div>
    <div id="dialog-modal" style="display:none" title="Add Location">
	<form>
		<table style='margin-left:auto; margin-right:auto;'>
			<tr><td><label for='name'>Title:</label></td><td><input id='name' name='name' onchange='processName()' onkeyup='processName()' /></td></tr>
			<tr><td><label for='uri'>ID:</label></td><td><input id='uri' name='uri' /></td></tr>
			<tr><td><label for='icon'>Icon:</label></td><td><img id='selected-icon' src='' title='Selected icon' style='width:32px; height:37px;'/><br /><input id='icon' name='icon' style='display:none'/>
				<div id="icon-classes">
					<ul>
						<li id="tab-Suggestions"><a href="#suggestions"><span>Suggestions</span></a></li>
<?php
ksort($col);
foreach(array_keys($col) as $cat)
{
	if($cat == 'Suggestions')
		continue;
	echo '<li id="tab-'.$cat.'"><a href="../../icons.php?cat='.$cat.'"><span>'.$cat.'</span></a></li>';
}
?>
					</ul>
					<div id='suggestions'>
<?
	arsort($iconcounts);
	foreach($iconcounts as $file => $count)
	{
		echo '<!-- '.$file.' -->';
		$parts = explode('/', $file);
		$filename = array_pop($parts);
		$filename = substr($filename, 0, -4);
		echo "<img id='img-$filename' src='$file' alt='$filename icon' title='$filename' onclick='selectIcon(\"$file\")' />";
	}
?>						
					</div>
				</div>
			</td></tr>
		</table>
	</form>
    </div>
    <div id="map" class="smallmap"></div>
  </body>
<?php
}
?>
</html>



