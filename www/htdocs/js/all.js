var map;
var mcOptions = {gridSize: 50, maxZoom: 15};
var markers = new Array();
var infowindows = new Array();
var polygons = new Array();
var polygoninfowindows = new Array();
var clusterMarkers = new Array();
var clusterInfowindows = new Array();

var DEFAULT_SEARCH_ICON = "http://www.picol.org/images/icons/files/png/32/search_32.png";
var CLEAR_SEARCH_ICON = "img/nt-left.png";

var oldString = null;
var xmlhttp = undefined;

var t;
var selectIndex = -1;
var limit = 0;

var addControl = function(elementID, position) {
	map.controls[position].push(document.getElementById(elementID));
}

var initcredits = function() {
	addControl('credits', google.maps.ControlPosition.RIGHT_BOTTOM);
	addControl('credits-small', google.maps.ControlPosition.RIGHT_BOTTOM);
}

var geoloc = function() {
	_gaq.push(['_trackEvent', 'Geolocation', 'Request']);
	navigator.geolocation.getCurrentPosition(
		function(position) {        
			if( position.coords.accuracy < 5000 ) {
				map.setCenter(new google.maps.LatLng(position.coords.latitude, position.coords.longitude));
			} else {
				alert('Sorry, geo location wildly inaccurate ('+ position.coords.accuracy+" meters)"); 
			}
			_gaq.push(['_trackEvent', 'Geolocation', 'Response', null, position.coords.accuracy]);
		},        
		function(e) {
			alert('Sorry, geo location failed'); 
			_gaq.push(['_trackEvent', 'Geolocation', 'Failed']);
		}
	);                                                                  
}

var initgeoloc = function() {
	if (navigator.geolocation) {
		addControl('geobutton', google.maps.ControlPosition.TOP_RIGHT);
	} else {
		$('#geobutton').get(0).style.display = 'none';
	}
}

var reset_search_icon = function() {
	var val = $("#inputbox").val();
	if (val.length > 0) {
		$("#clear").attr("src", CLEAR_SEARCH_ICON);
	} else {
		$("#clear").attr("src", DEFAULT_SEARCH_ICON);
	}
}

// Colin sez: "someone's clicked on something, you need to load the real data into it"
var loadWindow = function(j) {
	_gaq.push(['_trackEvent', 'InfoWindow', 'Single', j]);
	$.get("info.php?uri="+encodeURI(j), function(data) {
		infowindows[j].setContent(data);
	});
}

var closeAll = function() {
	for(var i in markers) {
		infowindows[i].close();
	}
	for(var i in clusterMarkers) {
		clusterInfowindows[i].close();
	}
	for(var i in polygons) {
		polygoninfowindows[i].close();
	}
}

var initmarkerevents = function() {
	for(var i in markers) {
		with({i: i})
		{
			google.maps.event.addListener(markers[i], 'click', function() {
				closeAll();
				infowindows[i].open(map,markers[i]);
				loadWindow(i);
			});
		}
	}
}

var updateFunc = function(force) {
	if(force !== true) force = false;
	var enabledCategories = getSelectedCategories();
	reset_search_icon();
	var inputbox = document.getElementById("inputbox");
	var list = document.getElementById("list");
	if(!force && inputbox.value == oldString) return;
	oldString = inputbox.value;
	if(xmlhttp !== undefined) xmlhttp.abort();
	xmlhttp = new XMLHttpRequest();
	xmlhttp.open("GET","matches.php?q="+inputbox.value+'&ec='+enabledCategories,true);
	_gaq.push(['_trackEvent', 'Search', 'Request', inputbox.value]);
	xmlhttp.send();
	xmlhttp.onreadystatechange=function() {
		if (xmlhttp.readyState==4 && xmlhttp.status==200) {
			var response_data = JSON.parse(xmlhttp.responseText);
			// console.log('got ', xmlhttp.responseText, response_data);
			var matches = [], labelmatches = [];
			if (response_data !== undefined) {
				matches = response_data[0];
				labelmatches = response_data[1];
			}
			
			var matchesd = {};
			matches.map(function(x) { if (x !== undefined) { matchesd[x] = true; } });
			
			for (var uri in markers) {
				markers[uri].setVisible(matchesd[uri] !== undefined);
			}
			
			selectIndex = -1;
			list.innerHTML = "";
			
			var re = new RegExp('('+$.trim(inputbox.value)+')',"gi");
			limit = 0;
			for(var m in labelmatches) {
				// if it's colins' special last box continue
				if (m === undefined) continue;
				var dispStr;
				if (labelmatches[m][0] === undefined) {
					try {  console.log('warning colin, labelmatches for ',m,' was undefined'); } catch(e) { }
					continue;	
				}
				
				if(inputbox.value != "") {
					dispStr = new String(labelmatches[m][0]).replace(re, "<span style='background-color:#FFFF66'>$1</span>");
				} else {
					dispStr = labelmatches[m][0];
				}
				if(labelmatches[m][2] !== undefined) {
					if(labelmatches[m][3] !== undefined) {
						list.innerHTML += '<li id="li'+limit+'" onclick="zoomTo(\''+labelmatches[m][2]+'\'); setInputBox(\'\'); updateFunc();"><img src="'+labelmatches[m][3]+'" style="width:15px" />'+dispStr+'</li>';
					} else {
						list.innerHTML += '<li id="li'+limit+'" onclick="zoomTo(\''+labelmatches[m][2]+'\'); setInputBox(\'\'); updateFunc();"><span style="font-size:0.5em">'+labelmatches[m][1]+': </span>'+dispStr+'</li>';
					}
				} else {
					list.innerHTML += '<li id="li'+limit+'" onclick="setInputBox(\'^'+labelmatches[m][0]+'$\'); updateFunc();">'+dispStr+'</li>';
				} 
				limit++;
			}
			cluster();
			
			if ($("#spinner").is(":visible")) {
			  $("#spinner").fadeOut();
			}
			// end of initialize
		}
	}
}

var keypress = function(e) {
	if(e.keyCode == 40) return moveDown();
	else if(e.keyCode == 38) return moveUp();
	else if(e.keyCode == 13) return select();
}

var zoomTo = function(uri) {
	var bounds = new google.maps.LatLngBounds();
	if(polygons[uri] !== undefined) {
		if(polygons[uri].length !== undefined) {
			_gaq.push(['_trackEvent', 'JumpTo', 'Polygon', uri]);
			for(var i = 0; i<polygons[uri].length; i++) {
				polygons[uri][i].getPath().forEach(function(el, i) {
					bounds.extend(el);
				});
			}
			map.fitBounds(bounds);
			google.maps.event.trigger(polygons[uri][0], 'click', bounds.getCenter());
		} else {
			_gaq.push(['_trackEvent', 'JumpTo', 'Point', uri]);
			map.panTo(polygons[uri].getPosition());
			google.maps.event.trigger(polygons[uri], 'click');
		}
	} else if(markers[uri] !== undefined) {
		_gaq.push(['_trackEvent', 'JumpTo', 'Point', uri]);
		map.panTo(markers[uri].getPosition());
		google.maps.event.trigger(markers[uri], 'click');
	}
}

var cluster = function() {
	for(var i in clusterMarkers) {
		clusterMarkers[i].setMap(null);
	}
	clusterMarkers = new Array();
	clusterInfowindows = new Array();
	var positions = new Array();
	var firstAtPos = new Array();
	var str = "";
	var count = 0;
	var count2 = 0;
	for(var i in markers) {
		if(markers[i].getVisible() === true) {
			count ++;
			str += markers[i].getTitle();
			str += markers[i].getPosition().toString();
			if(positions[markers[i].getPosition().toString()] !== undefined) {
				positions[markers[i].getPosition().toString()] ++;
				markers[i].setVisible(false);
				if(clusterMarkers[markers[i].getPosition().toString()] === undefined) {
					clusterMarkers[markers[i].getPosition().toString()] = new google.maps.Marker({
						position: markers[i].getPosition(),
						title: 'Cluster',
						map: map,
						visible: true
					});
					clusterInfowindows[markers[i].getPosition().toString()] = new google.maps.InfoWindow({
						content: '<div class="clusteritem" onclick="infowindows[\''+firstAtPos[markers[i].getPosition().toString()]+'\'].open(map, clusterMarkers[\''+markers[i].getPosition().toString()+'\']); loadWindow(\''+firstAtPos[markers[i].getPosition().toString()]+'\')"><img class="icon" src="'+markers[firstAtPos[markers[i].getPosition().toString()]].getIcon()+'" />'+markers[firstAtPos[markers[i].getPosition().toString()]].getTitle()+'</div>'+
							'<div class="clusteritem" onclick="infowindows[\''+i+'\'].open(map, clusterMarkers[\''+markers[i].getPosition().toString()+'\']); loadWindow(\''+i+'\')"><img class="icon" src="'+markers[i].getIcon()+'" />'+markers[i].getTitle()+'</div>'	
					});
					clusterMarkers[markers[i].getPosition().toString()].setIcon('resources/clustericon.php?i[]='+markers[firstAtPos[markers[i].getPosition().toString()]].getIcon()+'&i[]='+markers[i].getIcon());
					markers[firstAtPos[markers[i].getPosition().toString()]].setVisible(false);
				} else {
					clusterInfowindows[markers[i].getPosition().toString()].setContent(clusterInfowindows[markers[i].getPosition().toString()].getContent()+
						'<div class="clusteritem" onclick="infowindows[\''+i+'\'].open(map, clusterMarkers[\''+markers[i].getPosition().toString()+'\']); loadWindow(\''+i+'\')"><img class="icon" src="'+markers[i].getIcon()+'" />'+markers[i].getTitle()+'</div>');
					if(markers[i].getIcon() != clusterMarkers[markers[i].getPosition().toString()].getIcon())
					{
						clusterMarkers[markers[i].getPosition().toString()].setIcon(clusterMarkers[markers[i].getPosition().toString()].getIcon()+'&i[]='+markers[i].getIcon());
					}
				}
				count2 ++;
			} else {
				positions[markers[i].getPosition().toString()] = 1;
				firstAtPos[markers[i].getPosition().toString()] = i;
			}
		}
	}
	for(var i in clusterMarkers) {
		with({i: i})
		{
			google.maps.event.addListener(clusterMarkers[i], 'click', function() {
				closeAll();
				_gaq.push(['_trackEvent', 'InfoWindow', 'Cluster', i]);
				clusterInfowindows[i].open(map,clusterMarkers[i]);
			});
		}
	}
}

var initsearch = function() {
	$('#search').index = 2;
	addControl('search', google.maps.ControlPosition.TOP_RIGHT);
}

var setInputBox = function(str) {
	$('#inputbox').get(0).value = str;
}

var show = function(id) {
	selectIndex = -1;
	clearTimeout(t);
	$('#'+id).get(0).style.display = "block";
}

var hide = function(id) {
	$('#'+id).get(0).style.display = "none";
}

var moveUp = function() {
	removeHighlight();
	if(selectIndex >= 0) selectIndex--;
	updateHighlight();
	return false;
}

var moveDown = function() {
	removeHighlight();
	if(selectIndex < limit - 1) selectIndex++;
	updateHighlight();
	return false;
}

var select = function() {
	if(selectIndex >= 0) $('#li'+selectIndex).get(0).onclick();
	$('#inputbox').blur();
}

var removeHighlight = function() {
	if(selectIndex >= 0) $('#li'+selectIndex).get(0).style.backgroundColor = 'inherit';
}

var updateHighlight = function() {
	if(selectIndex >= 0) $('#li'+selectIndex).get(0).style.backgroundColor = '#CCCCFF';
}

var delayHide = function(id, delay) {
	t = setTimeout("hide('"+id+"');", delay);
}

var initmarkers = function(cont) {
	$.get('alldata.php', function(data,textstatus,xhr) {
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
		cont();
	},'json');
	$.get('polygons.php', function(data,textstatus,xhr) {
		// do party!!!!
		// clear em out, babes. 
		window.polygons = {};
		window.polygoninfowindows = {};
		var buildingIcon = new google.maps.MarkerImage('img/building.png',
			new google.maps.Size(20, 20),
			new google.maps.Point(0, 0),
			new google.maps.Point(10, 10)
		);
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
			if(paths.length == 0) {
				if(polygons[pos] === undefined) polygons[pos] = new Array();
				polygons[pos] = new google.maps.Marker({
					position: new google.maps.LatLng(points[i][1], points[i][0]),
					title: new String(poslabel).replace("'", "&apos;"),
					icon: buildingIcon,
					map: window.map,
					visible: true
				});
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
				
				if(polygons[pos] === undefined) polygons[pos] = new Array();
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
			
			var listener;
			var position;
			if(paths.length == 0) {
				listener = polygons[pos];
				position = listener.getPosition();
			} else {
				listener = polygons[pos][polygons[pos].length-1];
				var bounds = new google.maps.LatLngBounds();
					
				listener.getPath().forEach(function(el, i) {
					bounds.extend(el);
				});
				position = bounds.getCenter();
			}
			google.maps.event.addListener(listener, 'click', function(event) {
				closeAll();
				_gaq.push(['_trackEvent', 'InfoWindow', pType, pos]);
				if(event !== undefined) {
					if(event.latLng !== undefined) {
						polygoninfowindows[pos].setPosition(event.latLng);
					} else {
						polygoninfowindows[pos].setPosition(position);
					}
					polygoninfowindows[pos].open(window.map);
				} else {
					polygoninfowindows[pos].open(window.map, listener);
				}
			});
		});
	},'json');
};

var initialize = function(lat, long, zoom, uri) {
	map = new google.maps.Map(document.getElementById('map_canvas'), {
		zoom: zoom,
		center: new google.maps.LatLng(lat, long),
		mapTypeId: google.maps.MapTypeId.ROADMAP
	});

	initmarkers(function() {
		initmarkerevents();
		initgeoloc();
		inittoggle();
		initcredits();
		initsearch();
		
		$('#inputbox').keypress(keypress);
		$('#inputbox').keyup(updateFunc);
		updateFunc();
		if(uri != '') zoomTo(uri);
	});

}

var toggle = function(category) {
	var cEl = $('#'+category).get(0);
	if(cEl.className == "") {
		cEl.className = "deselected";
		_gaq.push(['_trackEvent', 'Categories', 'Toggle', category, 0]);
	} else {
		cEl.className = "";
		_gaq.push(['_trackEvent', 'Categories', 'Toggle', category, 1]);
	}
	updateFunc(true);
}

var getSelectedCategories = function() {
	var icons = $('#toggleicons').get(0).childNodes;
	var selected = "";
	for(var i in icons) {
		if(icons[i] !== null && icons[i].className == "")
			selected += icons[i].id + ",";
	}
	return selected;
}

var inittoggle = function() {
	addControl('toggleicons', google.maps.ControlPosition.RIGHT_TOP);
}
