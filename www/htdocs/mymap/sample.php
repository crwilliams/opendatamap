<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>OpenLayers: Geo Data Set Editor</title>
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
		right: 5%;
		z-index: 1000;
		background-color:white;
	}

	#list {
		overflow: scroll;
		height: 90%;
	}
	
	#actionText {
		padding: 5px;
		height: 10%;
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
    </style>

    <script src="OpenLayers-2.11/OpenLayers.js"></script>
    <script src="OSecs.js"></script>
    <script type="text/javascript">

// make map available for easy debugging
var map;
var vector;
var markers;
var p = new Array();
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
	            llc.transform(map.getProjectionObject(), wgs84);
		    document.getElementById('loc_'+positionUri).innerHTML = Math.round(llc.lat*1000000)/1000000+'/'+Math.round(llc.lon*1000000)/1000000;
		    positionUri = undefined;
		    document.getElementById('actionText').innerHTML = 'Please select an item...';
                }

            });


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

    var streetview = new OpenLayers.Layer.StreetView("OS StreetView (1:10000)");

    markers = new OpenLayers.Layer.Markers("Editable Markers");

    map.addLayers([streetview, markers]);

    var size = new OpenLayers.Size(32,37);
    var offset = new OpenLayers.Pixel(-(size.w/2), -size.h);

    if (!map.getCenter()) {
        var gb = new OpenLayers.Bounds(-1.403, 50.931, -1.389, 50.939);
        gb.transform(wgs84, map.getProjectionObject());
        map.zoomToExtent(gb);
        if (map.getZoom() < 6) map.zoomTo(6);
    }

                var click = new OpenLayers.Control.Click();
                map.addControl(click);
                click.activate();
}


function position(uri)
{
	document.getElementById('actionText').innerHTML = 'Setting location of '+label[uri];
	positionUri = uri;
}
    </script>
  </head>
  <body onload="init()">
    <div id="map" class="smallmap"></div>
  </body>
</html>



