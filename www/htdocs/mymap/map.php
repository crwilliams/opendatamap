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
<?php

function loadCSV($filename, $base="", $idcolname, $namecolname, $iconcolname, $latcolname, $loncolname, $location)
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
				$data[$base.$row[$colnames[$idcolname]]] = array(
					'label' => str_replace('\'', '&apos;', $row[$colnames[$namecolname]]),
					'icon' => $row[$colnames[$iconcolname]],
					'lat' => $row[$colnames[$latcolname]],
					'lon' => $row[$colnames[$loncolname]],
				);
				if(isset($data[$base.$row[$colnames[$idcolname]]]['lat']) && isset($data[$base.$row[$colnames[$idcolname]]]['lon']))
				{
					$data[$base.$row[$colnames[$idcolname]]]['source'] = 'CSV';
				}
				if(isset($location[$row[$colnames[$idcolname]]]))
				{
					$data[$row[$colnames[$idcolname]]]['lat'] = $location[$row[$colnames[$idcolname]]][0];
					$data[$row[$colnames[$idcolname]]]['lon'] = $location[$row[$colnames[$idcolname]]][1];
					$data[$row[$colnames[$idcolname]]]['source'] = $location[$row[$colnames[$idcolname]]][2];
				}
			}
		}
		fclose($handle);
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
	$data = loadCSV('https://spreadsheets.google.com/pub?hl=en&hl=en&key=0AqodCQwjuWZXdDhaVzVrWVlfMGNfUmFrTW5nZmRyVHc&output=csv', 'http://id.southampton.ac.uk/point-of-service/', 'code', 'name', 'icon', 'latitude', 'longitude', array());
}
else
{
	require_once('/home/opendatamap/mysql.inc.php');
	$params[] = mysql_real_escape_string($_REQUEST['u']);
	$params[] = mysql_real_escape_string($_REQUEST['m']);
	$q = 'SELECT uri, lat, lon, source FROM mappoints WHERE username = \''.$params[0].'\' AND map = \''.$params[1].'\'';
	$res = mysql_query($q);
	$location = array();
	while($row = mysql_fetch_assoc($res))
	{
		$location[$row['uri']] = array($row['lat'], $row['lon'], $row['source']);
	}
	$q = 'SELECT source FROM maps WHERE username = \''.$params[0].'\' AND mapid = \''.$params[1].'\'';
	$res = mysql_query($q);
	if($row = mysql_fetch_assoc($res))
	{
		if(substr($row['source'], 0, 7) == 'http://' || substr($row['source'], 0, 8) == 'https://')
		{
			$data = loadCSV($row['source'], '', 'code', 'name', 'icon', 'latitude', 'longitude', $location);
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
	}
        .olControlAttribution { bottom: 0px!important }

        /* avoid pink tiles */
        .olImageLoadError {
            background-color: transparent !important;
        }

	#controls {
		position: absolute;
		width: 200px;
		height: 90%;
		top: 5%;
		right: 2%;
		z-index: 1000;
		background-color:white;
		overflow:hidden;
	}

	#list {
		overflow: scroll;
		height: 84%;
		margin: 0;
		padding: 0;
	}
	
	#listheader {
		height: 15%;
		margin: 0;
		padding: 0;
		border: none;
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

	#list ul {
		list-style: none;
		margin: 0;
		padding: 0;
	}

	#list li {
		padding: 5px;
		border: solid 1px black;
		margin: 3px;
	}

	a:link, a:hover, a:visited {
		text-decoration: none;
		color: blue;
	}
    </style>

    <script src="../../OpenLayers-2.11/OpenLayers.js"></script>
    <script src="../../OS.js"></script>
    <script type="text/javascript">

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

// increase reload attempts 
OpenLayers.IMAGE_RELOAD_ATTEMPTS = 3;

            OpenLayers.Control.Click = OpenLayers.Class(OpenLayers.Control, {                
                defaultHandlerOptions: {
                    'single': true,
                    'double': false,
                    'pixelTolerance': 0,
                    'stopSingle': false,
                    'stopDouble': false
                },

                initialize: function(options) {
                    this.handlerOptions = OpenLayers.Util.extend(
                        {}, this.defaultHandlerOptions
                    );
                    OpenLayers.Control.prototype.initialize.apply(
                        this, arguments
                    ); 
                    this.handler = new OpenLayers.Handler.Click(
                        this, {
                            'click': this.trigger
                        }, this.handlerOptions
                    );
                }, 

                trigger: function(e) {
		    if(positionUri == undefined)
			return;
                    var lonlat = map.getLonLatFromViewPortPx(e.xy);
		    var llc = lonlat.clone();
		    var size = new OpenLayers.Size(32,37);
 		    var offset = new OpenLayers.Pixel(-(size.w/2), -size.h);
		    var icon = new OpenLayers.Icon(icons[positionUri], size, offset);
		    if(p[positionUri] != undefined)
		    {
		    	markers.removeMarker(p[positionUri]);
		    }
		    //else
		    //{
			p[positionUri] = new OpenLayers.Marker(lonlat, icon);
			markers.addMarker(p[positionUri]);
		    //}
		    changed[positionUri] = true;
	            llc.transform(map.getProjectionObject(), wgs84);
		    document.getElementById('loc_'+positionUri).innerHTML = Math.round(llc.lat*1000000)/1000000+'/'+Math.round(llc.lon*1000000)/1000000;
		    positionUri = undefined;
		    document.getElementById('actionText').innerHTML = 'Please select an item...';
		    document.getElementById('save_link').innerHTML = 'Save';
                }

            });

function save(){
	var str = '';
	var i = 0;
	for (var q in changed)
	{
		var llc = p[q].lonlat.clone();
		llc.transform(map.getProjectionObject(), wgs84);
		str += q + '|' + llc.lat + '|' + llc.lon + '||';
		i++;
	}
	OpenLayers.Request.POST( {
		url : 'http://opendatamap.ecs.soton.ac.uk/dev/colin/edit/save.php?map=<?php echo $_GET['m'] ?>',
		data : str,
		success : function(response) {
			alert(response.responseText);
			for (var q in changed)
			{
				document.getElementById('loc_'+q).innerHTML += ' (OS)';
			}
			changed = new Array();
			document.getElementById('save_link').innerHTML = '';
		},
		failure : function(response) { alert(response.responseText) },
	} );
	return i;
}

function init(){
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

/*
    // create OSM layer
    var mapnik = new OpenLayers.Layer.OSM();

    // create OSM layer
    var osmarender = new OpenLayers.Layer.OSM(
        "OpenStreetMap (Tiles@Home)",
        "http://tah.openstreetmap.org/Tiles/tile/${z}/${x}/${y}.png"
    );
*/


    // create WMS layer
/*
    var wms = new OpenLayers.Layer.WMS(
        "World Map",
        "http://world.freemap.in/tiles/",
        {'layers': 'factbook-overlay', 'format':'png'},
        {
            'opacity': 0.4, visibility: false,
            'isBaseLayer': false,'wrapDateLine': true
        }
    );
*/
    
    var streetview = new OpenLayers.Layer.StreetView("OS StreetView (1:10000)");

    // create a vector layer for drawing
/*
    vector = new OpenLayers.Layer.Vector("Editable Vectors",
	{
	onFeatureInsert: function (foo) {
		alert(vector.features.length);
		for(var f in vector.features)
		{
			alert(f.lonlat);
		}
	},
	style: {externalGraphic: 'http://opendatamap.ecs.soton.ac.uk/img/icon/Offices/wifi.png', graphicWidth: 32, graphicHeight:37, graphicOpacity:1, graphicYOffset: -37},
	}
    );
*/

    markers = new OpenLayers.Layer.Markers("Editable Markers"/*,
	{
	onFeatureInsert: function (foo) {
		alert(markers.markers.length);
		for(var f in markers.markers)
		{
			alert(f.lonlat);
		}
	},
	style: {externalGraphic: 'http://opendatamap.ecs.soton.ac.uk/img/icon/Offices/wifi.png', graphicWidth: 32, graphicHeight:37, graphicOpacity:1, graphicYOffset: -37},
	}*/
    );

    map.addLayers([streetview, markers]);
    //map.addControl(new OpenLayers.Control.LayerSwitcher());
    //map.addControl(new OpenLayers.Control.EditingToolbar(markers));
    //map.addControl(new OpenLayers.Control.Permalink());
    //map.addControl(new OpenLayers.Control.MousePosition());

    var size = new OpenLayers.Size(32,37);
    var offset = new OpenLayers.Pixel(-(size.w/2), -size.h);
<?
foreach($data as $uri => $point)
{
	if($point['lat'] == '' || $point['lon'] == '')
		continue;
	echo "ll['$uri'] = new OpenLayers.LonLat(".$point['lon'].", ".$point['lat'].");\n";
	echo "ll['$uri'].transform(wgs84, map.getProjectionObject());\n";
	echo "p['$uri'] = new OpenLayers.Marker(ll['$uri'], new OpenLayers.Icon(icons['$uri'], size, offset));\n";
	echo "markers.addMarker(p['$uri']);\n";
}
?>
    if (!map.getCenter()) {
	if(markers.markers.length == 0)
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

                var click = new OpenLayers.Control.Click();
                map.addControl(click);
                click.activate();
}


<?php
foreach($data as $uri => $item)
{
	echo "label['$uri'] = '".$item['label']."';\n";
	echo "icons['$uri'] = '".$item['icon']."';\n";
}
?>

function position(uri)
{
	document.getElementById('actionText').innerHTML = 'Setting location of '+label[uri];
	positionUri = uri;
}
    </script>
  </head>
  <body onload="init()">
    <div id="controls">
	<div id='listheader'>
		<div id='links'><a href='../../<?= $_REQUEST['u'] ?>'>Back to map list</a> | <a href='../<?= $_REQUEST['m'] ?>'>View RDF</a></div>
		<div id='actionText'>Please select an item...</div>
		<div id='save'><a id='save_link' href='#' onclick='save();'></a></div>
	</div>
	<div id='list'>
	<ul>
<?php
foreach($data as $uri => $item)
{
	if(isset($item['lat']) && isset($item['lon']) && $item['lat'] != '' && $item['lon'] != '')
		continue;
	echo "<li onclick='position(\"$uri\")'><img style='float:left; margin-right:5px' src='".$item['icon']."' />".$item['label']."<br/><span class='small' id='loc_$uri'>";
	echo "Location not set.";
	echo "</span></li>";
}

foreach($data as $uri => $item)
{
	if(!(isset($item['lat']) && isset($item['lon']) && $item['lat'] != '' && $item['lon'] != ''))
		continue;
	echo "<li onclick='position(\"$uri\")'><img style='float:left; margin-right:5px' src='".$item['icon']."' />".$item['label']."<br/><span class='small' id='loc_$uri'>";
	echo round($item['lat'], 6).'/'.round($item['lon'], 6).' ('.$item['source'].')';
	echo "</span></li>";
}
?>
	</ul>
    	</div>
    </div>
    <div id="map" class="smallmap"></div>
  </body>
<?php
}
?>
</html>



