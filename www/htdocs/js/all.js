google.maps.visualRefresh = true;

var map;
var version;
var mcOptions = {gridSize: 50, maxZoom: 15};
var markers = {};
var infowindows = {};
var postcodeMarkers = {};
var postcodeInfowindows = {};
var polygons = {};
var polygoninfowindows = {};
var clusterMarkers = {};
var clusterInfowindows = {};
var hashfields = {};
var firstAtPos = {};
var tempInfowindow = undefined;

var DEFAULT_SEARCH_ICON = "http://www.picol.org/images/icons/files/png/32/search_32.png";
var CLEAR_SEARCH_ICON = "img/nt-left.png";

var searchTerm = null;
var searchResults_exactMatch = false;
var xmlhttp = undefined;

var selecteddate = null;

var t;
var selectIndex = -1;
var limit = 0;

var zoomuri;
var clickuri;
var uri;

var contcount = 0;

var bb;

function compare(a,b){
	return b-a;
}

// Subject functions.

var refreshSubjectChoice = function () {
	var str = getHash('subject');
	if (str === '') {
		$('.General').hide();
		$('.InformationStand').hide();
		$('.Subject').show();
		$('.Subject h2').show();
		$('.Subject li').hide();
		$('#selectedsubject').html("Choose a subject:");
	} else {
		$('.General').show();
		$('.InformationStand').show();
		$('.Subject').hide();
		$('.Subject h2').hide();
		$('.Subject.subj_' + str).show();
		$('.Subject.subj_' + str + ' li').show();
	}
};

var chooseSubject = function (name) {
	$('#selectedsubject').html(name + '<br/><span style="font-size:0.8em">(click to change subject)</span>');
	$('#selectedsubject').addClass('clickable');
	$('#selectedsubject').attr('title', 'Click to change subject');
	$('#selectedsubject').css('background-color', '#007C92');
	$('#selectedsubject').css('color', 'white');
	$('#selectedsubject').click(changeSubject);
	refreshSubjectChoice();
};

var changeSubject = function () {
	$('#inputbox').val('/' + getHash('day'));
	$('#selectedsubject').removeClass('clickable');
	$('#selectedsubject').attr('title', null);
	$('#selectedsubject').css('background-color', 'inherit');
	$('#selectedsubject').css('color', 'inherit');
	$('#selectedsubject').click(null);
	refreshSubjectChoice();
	searchResults_updateFunc();
	updateHash('subject', '');
};

//Misc functions.

var removePostcodeMarker = function (postcode) {
	postcodeMarkers[postcode].setMap(null);
};

var zoomTo = function (uri, click, pan) {
	click = (click !== undefined) ? click : true;
	pan = (pan !== undefined) ? pan : true;
	var bounds = new google.maps.LatLngBounds();
	if (uri.substring(0, 9) === 'postcode:') {
		var postcodeData = uri.substring(9).split(',');
		var latlng = new google.maps.LatLng(postcodeData[1], postcodeData[2]);
		postcodeMarkers[postcodeData[0]] = new google.maps.Marker({
			position: latlng,
			map: map,
			title: postcodeData[0],
			icon: 'http://opendatamap.ecs.soton.ac.uk/resources/postcodeicon.php?pc=' + postcodeData[0]
		});
		postcodeInfowindows[postcodeData[0]] = new google.maps.InfoWindow({
			content: '<div id="content">' +
				'<h2 id="title">' + postcodeData[0] + '</h2>' +
				'<a class="odl" href="' + postcodeData[3] + '">Visit page</a><br />' +
				'<a class="odl" href="javascript:removePostcodeMarker(\'' + postcodeData[0] + '\')">Remove this marker</a>' +
				'</div>'
		});
		/*
		with ({postcode: postcodeData[0]})
		{
			google.maps.event.addListener(postcodeMarkers[postcode], 'click', function() {
				postcodeInfowindows[postcode].open(map,postcodeMarkers[postcode]);
			});
		}
		*/
		_gaq.push(['_trackEvent', 'JumpTo', 'Postcode', postcodeData[0]]);
		if (pan) { map.panTo(latlng); map.setZoom(15); }
	} else if (polygons[uri] !== undefined) {
		if (polygons[uri].length !== undefined) {
			_gaq.push(['_trackEvent', 'JumpTo', 'Polygon', uri]);
			for (var i = 0; i<polygons[uri].length; i++) {
				polygons[uri][i].getPath().forEach(function(el, i) {
					bounds.extend(el);
				});
			}
			if (pan) { map.fitBounds(bounds); }
			if (click) { google.maps.event.trigger(polygons[uri][0], 'click', bounds.getCenter()); }
		} else {
			_gaq.push(['_trackEvent', 'JumpTo', 'Point', uri]);
			if (pan) { map.panTo(polygons[uri].getPosition()); }
			if (click) { google.maps.event.trigger(polygons[uri], 'click'); }
		}
	} else if(markers[uri] !== undefined) {
		_gaq.push(['_trackEvent', 'JumpTo', 'Point', uri]);
		if (pan) { map.panTo(markers[uri].getPosition()); }
		if (click) { google.maps.event.trigger(markers[uri], 'click'); }
	} else if(uri === 'southampton-overview') {
		bounds.extend(new google.maps.LatLng(50.9667011,-1.4444580));
		bounds.extend(new google.maps.LatLng(50.9326431,-1.4438220));
		bounds.extend(new google.maps.LatLng(50.8887047,-1.3935115));
		bounds.extend(new google.maps.LatLng(50.9554826,-1.3560130));
		bounds.extend(new google.maps.LatLng(50.9667013,-1.4178855));
		if (pan) { map.fitBounds(bounds); }
	} else if(uri === 'southampton-centre') {
		bounds.extend(new google.maps.LatLng(50.9072471,-1.4186829));
		bounds.extend(new google.maps.LatLng(50.9111925,-1.4029262));
		bounds.extend(new google.maps.LatLng(50.9079644,-1.3979205));
		bounds.extend(new google.maps.LatLng(50.8930407,-1.4004233));
		if (pan) { map.fitBounds(bounds); }
	}
};

var getLiveInfo = function (i) {
	return "";//" <span class='live'>[" + i + "]</span>";
};

var renderClusterItem = function (uri, ll) {
	if (polygonlls[uri] === undefined) {
		var lltrim = ll.replace(/[^0-9]/g, '_');
		var onclick = "loadWindow('" + uri + "', $('#" + lltrim + "-content'), $('#" + lltrim + "-listcontent'), '" + ll + "')";
		return '<div class="clusteritem" onclick="' + onclick + '">' +
			'<img class="icon" src="' + markers[uri].getIcon() + '" />' +
			markers[uri].getTitle().replace('\\\'', '\'') + getLiveInfo(uri) + '</div>';
	} else {
		return '';
	}
};

var cluster = function (reopen) {
	closeAll();
	for (var i in clusterMarkers) {
		if (typeof (clusterMarkers[i]) === "object") {
			clusterMarkers[i].setMap(null);
		}
	}
	clusterMarkers = {};
	clusterInfowindows = {};
	var positions = {};
	firstAtPos = {};
	var str = "";
	var count = 0;
	var count2 = 0;
	for (var i in markers) {
		if (markers[i].getVisible() === true) {
			count ++;
			str += markers[i].getTitle();
			str += markers[i].getPosition().toString();
			var ll = markers[i].getPosition().toString();
			if (positions[ll] !== undefined) {
				positions[ll] ++;
				markers[i].setVisible(false);
				if (clusterMarkers[ll] === undefined) {
					clusterMarkers[ll] = new google.maps.Marker({
						position: markers[i].getPosition(),
						title: '2',
						map: map,
						visible: true
					});
					clusterInfowindows[ll] = new google.maps.InfoWindow({
						content: renderClusterItem(firstAtPos[ll], ll) + renderClusterItem(i, ll)
					});
					clusterMarkers[ll].setIcon('resources/clustericon.php?' + 
						'i[]=' + markers[firstAtPos[ll]].getIcon() + 
						'&i[]=' + markers[i].getIcon());
					markers[firstAtPos[ll]].setVisible(false);
				} else {
					clusterInfowindows[ll].setContent(clusterInfowindows[ll].getContent() + 
						renderClusterItem(i, ll));
					if (markers[i].getIcon() !== clusterMarkers[ll].getIcon())
					{
						clusterMarkers[ll].setIcon(clusterMarkers[ll].getIcon() + 
							'&i[]=' + markers[i].getIcon());
					}
					var oldc = parseInt(clusterMarkers[ll].getTitle(), 10);
					clusterMarkers[ll].setTitle('' + (oldc + 1));
				}
				count2 ++;
			} else {
				positions[ll] = 1;
				firstAtPos[ll] = i;
			}
		}
	}
	for(var i in clusterInfowindows) {
		with({i: i})
		{
			var clusterTitle = '';
			if (polygonnames[i] !==undefined)
			{
				clusterTitle = '<h1>' + polygonnames[i] + '</h1><hr />';
			}
			var id = i.replace(/[^0-9]/g, '_');
			clusterInfowindows[i].setContent(clusterTitle + '<div id="'+id+'-listcontent">' + 
				clusterInfowindows[i].getContent() + 
				'<div class="listcontent-footer">click icon for more information</div></div>'+
				'<div id="'+i.replace(/[^0-9]/g, '_')+'-content"></div>');
		}
	}
	for(var i in infowindows) {
		with({i: i})
		{
			var ll = markers[i].getPosition().toString();
			var content = '';
			if (polygonnames[ll] !== undefined)
			{
				content += '<h1>' + polygonnames[ll] + '</h1><hr />';
			}
			if (polygonlls[i] === undefined)
			{
				content += infowindows[i].getContent();
			}
			infowindows[i].setContent(content);
		}
	}
	for(var i in clusterMarkers) {
		with({i: i})
		{
			clusterMarkers[i].setTitle(clusterMarkers[i].getTitle() + ' items');
			google.maps.event.addListener(clusterMarkers[i], 'click', function() {
				closeAll();
				_gaq.push(['_trackEvent', 'InfoWindow', 'Cluster', i]);
				clusterInfowindows[i].open(map,clusterMarkers[i]);
			});
		}
	}
	if(reopen !== undefined) {
		zoomTo(reopen, true, false);
	}
};

var addControl = function (elementID, position) {
	var element = document.getElementById(elementID);
	map.controls[position].push(element);
};

var geoloc = function () {
	_gaq.push(['_trackEvent', 'Geolocation', 'Request']);
	navigator.geolocation.getCurrentPosition(
		function (position) {        
			if (position.coords.accuracy < 5000) {
				map.setCenter(new google.maps.LatLng(position.coords.latitude, position.coords.longitude));
			} else {
				alert('Sorry, geo location wildly inaccurate (' + position.coords.accuracy + " meters)"); 
			}
			_gaq.push(['_trackEvent', 'Geolocation', 'Response', null, position.coords.accuracy]);
		},        
		function (e) {
			alert('Sorry, geo location failed'); 
			_gaq.push(['_trackEvent', 'Geolocation', 'Failed']);
		}
	);                                                                  
};

var resetSearchIcon = function () {
	var val = $("#inputbox").val();
	if (val.length > 0) {
		$("#clear").attr("src", CLEAR_SEARCH_ICON);
	} else {
		$("#clear").attr("src", DEFAULT_SEARCH_ICON);
	}
};

// someone's clicked on something, you need to load the real data into it
var loadWindow = function (j, dest, hide, reload) {
	_gaq.push(['_trackEvent', 'InfoWindow', 'Single', j]);
	if (dest === undefined && polygonlls[j] !== undefined) {
		return;
	}
	$.get("info.php?v=" + version + "&date=" + selecteddate + "&uri=" + encodeURIComponent(j), function (data) {
		var ll = markers[j].getPosition().toString();
		if (polygonnames[ll] !== undefined) {
			clusterTitle = '<h1>' + polygonnames[ll] + '</h1><hr />';
		} else {
			clusterTitle = '';
		}
		if (dest === undefined) {
			infowindows[j].setContent(clusterTitle + data);
		} else {
			tempInfowindow = new google.maps.InfoWindow({
				content: clusterTitle + data +
					'<a href="#" class="back" onclick="return goBack(\'' + reload + '\')\">Back to list</a>'
			});
			if (clusterInfowindows[reload].get('anchor') !== undefined) {
				tempInfowindow.open(map, clusterInfowindows[reload].get('anchor'));
			} else {
				tempInfowindow.setPosition(clusterInfowindows[reload].getPosition());
				tempInfowindow.open(map);
			}
			clusterInfowindows[reload].close();
		}
	});
};

var goBack = function (reload) {
	tempInfowindow.close();
	clusterInfowindows[reload].open(map);
	return false;
};

var closeAll = function () {
	for (var i in markers) {
		infowindows[i].close();
	}
	for (var i in clusterMarkers) {
		if (typeof (clusterInfowindows[i]) === "object") {
			clusterInfowindows[i].close();
		}
	}
	for (var i in polygons) {
		polygoninfowindows[i].close();
	}
	if (tempInfowindow !== undefined) {
		tempInfowindow.close();
	}
};

// Search functions.

var searchResults_setInputBox = function (str, exact) {
	if (exact === true) {
		searchResults_exactMatch = true;
	} else {
		searchResults_exactMatch = false;
	}
	$('#inputbox').get(0).value = str;
};

var searchResults_updateFunc = function (force, reopen) {
	if(force !== true) {
		force = false;
	}
	var enabledCategories = getSelectedCategories();
	resetSearchIcon();
	var inputbox = $("#inputbox").get(0);
	var list = $("#list").get(0);

	var newSearchTerm = inputbox.value;
	if (searchResults_exactMatch) {
		newSearchTerm = '^' + newSearchTerm + '$';
	}
	if (!force && newSearchTerm === searchTerm) {
		return;
	}
	searchTerm = newSearchTerm;

	if (xmlhttp !== undefined) {
		xmlhttp.abort();
	}
	xmlhttp = new XMLHttpRequest();
	xmlhttp.open("GET","matches.php?v=" + version + "&q=" + searchTerm + '&ec=' + enabledCategories,true);
	_gaq.push(['_trackEvent', 'Search', 'Request', searchTerm]);
	xmlhttp.send();
	xmlhttp.onreadystatechange = function() {
		if (xmlhttp.readyState === 4 && xmlhttp.status === 200) {
			var response_data = JSON.parse(xmlhttp.responseText);
			var matches = [], labelmatches = [];
			if (response_data !== undefined) {
				matches = response_data[0];
				labelmatches = response_data[1];
			}
			searchResults_processResponse(matches, labelmatches, reopen);
		}
	};
};

var searchResults_processResponse = function (matches, labelmatches, reopen){
	var matchesd = {};
	matches.map(function (x) {
		if (x !== undefined) {
			matchesd[x] = true;
		}
	});
	
	for (var uri in markers) {
		markers[uri].setVisible(matchesd[uri] !== undefined);
	}

	selectIndex = -1;
	list.innerHTML = "";
	
	var re = new RegExp('(' + $.trim(searchTerm) + ')',"gi");
	limit = 0;
	for (var m in labelmatches) {
		// if it's the special last element, continue
		if (m === undefined) {
			continue;
		}
		var dispStr;
		if (labelmatches[m][0] === undefined) {
			continue;	
		}
		
		var dispStr = labelmatches[m][0];
		if (searchTerm !== "") {
			dispStr = new String(dispStr).replace(re,
				"<span style='background-color:#FFFF66'>$1</span>");
		}
		if (labelmatches[m][2] !== undefined) {
			var onclick = '';
			if(labelmatches[m][2] !== null) {
				onclick = "zoomTo('" + labelmatches[m][2] + "');" + 
					"searchResults_setInputBox('');" + 
					"searchResults_updateFunc(false, '" + labelmatches[m][2] + "');";
			} else {
				var escapeLabelmatch = labelmatches[m][0].replace('(', '\\\\(').replace(')', '\\\\)');
				onclick = "searchResults_setInputBox('" + escapeLabelmatch + "', true);" + 
					"searchResults_updateFunc();";
			}
			var element = '<li id="li' + limit + '" onclick="' + onclick + '">';
			if(labelmatches[m][3] !== undefined) {
				element += '<img class="icon" src="' + labelmatches[m][3] + '" />';
			} else {
				element += '<span style="font-size:0.5em">' + labelmatches[m][1] + ': </span>';
			}
			element += dispStr;
			element += '</li>';
			list.innerHTML += element;
		} else {
			var escapeLabelmatch = labelmatches[m][0].replace('(', '\\\\(').replace(')', '\\\\)');
			var onclick = "searchResults_setInputBox('" + escapeLabelmatch + "', true);" + 
				"searchResults_updateFunc();";
			list.innerHTML += '<li id="li' + limit + '" onclick="' + onclick + '">' + dispStr + '</li>';
		} 
		limit++;
	}
	if(limit === 0) {
		list.innerHTML += '<li><i>No results found</i></li>';
	}
	cluster(reopen);
	
	if ($("#spinner").is(":visible")) {
		$("#spinner").fadeOut();
	}
};

var searchResults_keypress = function (e) {
	if (e.keyCode === 40) {
		return searchResults_moveDown();
	}
	else if (e.keyCode === 38) {
		return searchResults_moveUp();
	}
	else if (e.keyCode === 13) {
		return searchResults_select();
	}
	else if (e.keyCode === 27) {
		return searchResults_blursearch();
	}
};

var searchResults_moveUp = function () {
	searchResults_removeHighlight();
	if (selectIndex >= 0) {
		selectIndex--;
	}
	searchResults_updateHighlight();
	return false;
};

var searchResults_moveDown = function () {
	searchResults_removeHighlight();
	if (selectIndex < limit - 1) {
		selectIndex++;
	}
	searchResults_updateHighlight();
	return false;
};

var searchResults_enter = function() {
	searchResults_exactMatch = false;
	searchResults_updateFunc();
	show('list');
	$('#search').css('z-index', 10);
};

var searchResults_select = function () {
	if (selectIndex >= 0) {
		$('#li' + selectIndex).get(0).onclick();
	}
	searchResults_blursearch();
};

var searchResults_blursearch = function () {
	searchResults_removeHighlight();
	$('#inputbox').blur();
};

var searchResults_removeHighlight = function () {
	if (selectIndex >= 0) {
		$('#li' + selectIndex).get(0).style.backgroundColor = 'inherit';
	}
};

var searchResults_updateHighlight = function () {
	if (selectIndex >= 0) {
		$('#li' + selectIndex).get(0).style.backgroundColor = '#CCCCFF';
	}
};

// Show and hide functions.

var show = function (id) {
	selectIndex = -1;
	clearTimeout(t);
	$('#' + id).get(0).style.display = "block";
	if (id === 'list') {
		$('#toggleicons').get(0).style.zIndex = 5;
	}
};

var hide = function (id) {
	$('#' + id).get(0).style.display = "none";
};

var delayHide = function (id, delay) {
	t = setTimeout("hide('" + id + "');", delay);
};

var cont = function () {
	contcount++;
	if (contcount !== 2) {
		return;
	}
	initMarkerEvents();
	initGeoloc();
	initToggle();
	initBookmarks();
	initCredits();
	initSearch();
	
	$('#inputbox').keydown(searchResults_keypress);
	$('#inputbox').keyup(searchResults_updateFunc);
	var hashstring = location.hash.replace( /^#/, '' );
	location.hash = location.hash.replace(/\/.*/, '');
	hashstring = hashstring.split('/');
	if (hashstring.length > 1) {
		hashstring = hashstring[1];
		$('#subj_' + hashstring).click();
	} else {
		hashstring = '';
	}
	if(uri) {
		searchResults_updateFunc(false, uri);
	} else if(clickuri) {
		searchResults_updateFunc(false, clickuri);
	} else {
		searchResults_updateFunc(false, undefined);
	}
	if(bb !== undefined) {
		var llnelat = new Array();
		var llnelng = new Array();
		var llswlat = new Array();
		var llswlng = new Array();
		for(var i = 0; i < 100; i++)
		{
			if(!bb[i].isEmpty())
			{
				llnelat.push(bb[i].getNorthEast().lat());
				llnelng.push(bb[i].getNorthEast().lng());
				llswlat.push(bb[i].getSouthWest().lat());
				llswlng.push(bb[i].getSouthWest().lng());
			}
		}
		llnelat.sort(compare);
		llnelng.sort(compare);
		llswlat.sort(compare);
		llswlng.sort(compare);
		llswlat.reverse();
		llswlng.reverse();
		console.log(llnelat);
		console.log(llnelng);
		console.log(llswlat);
		console.log(llswlng);
		map.fitBounds(new google.maps.LatLngBounds(new google.maps.LatLng(llswlat[10], llswlng[10]), new google.maps.LatLng(llnelat[10], llnelng[10])));
	}
	if (uri !== '')
	{
		zoomTo(uri, true, true);
	}
	if (zoomuri !== '')
	{
		zoomTo(zoomuri, false, true);
	}
	if (clickuri !== '')
	{
		zoomTo(clickuri, true, false);
	}
};

// Hash functions.

var updateHash = function (key, value) {
	if (key !== undefined && value !== undefined) {
		hashfields[key] = value;
	}
	var hashstring = '';
	for (var i in hashfields) {
		if (hashfields[i] !== '' && hashfields[i] !== undefined) {
			hashstring += ',' + i + '=' + hashfields[i];
		}
	}
	location.hash = '#' + hashstring.substring(1);
};

var getHash = function (key) {
	if (hashfields[key] === undefined) {
		return '';
	} else {
		return hashfields[key];
	}
};

var hashChange = function () {
	var hashstring = location.hash.replace( /^#/, '' );
	var hashstringparts = hashstring.split(',');
	hashfields = {};
	for (var i in hashstringparts) {
		var hashfield = hashstringparts[i].split('=');
		hashfields[hashfield[0]] = hashfield[1];
	}

	if (document.title.replace( / \| .*/, '' ) !== 'University of Southampton Open Day Map') {
		return;
	}
	var dates = {};
	var fulldates = {};
	$('#day a').each(function (i, v) {
		var d = v.id.substring(5, 15);
		dates[v.innerHTML.toLowerCase()] = d;
		fulldates[v.innerHTML.toLowerCase()] = v.title.replace('Show ', '').replace('\'s events (', ' ').replace(')', '');
		$('._' + d).hide();
	});
	$('#day a').each(function (i, v) {
		var d = v.id.substring(5, 15);
		$('#link_' + d).removeClass('selected');
	});
	document.title = document.title.replace( / \| .*/, '' );

	var d = getHash('day');
	var fulldate;
	if(d === '') {
		d = Object.keys(dates)[0];
		hashfields.day = d;
		updateHash();
	}
	if(dates[d] === undefined) {
		return;
	} else {
		selecteddate = dates[d];
		fulldate = fulldates[d];
	}

	document.title += ' | ' + fulldate;
	$('._' + selecteddate).show();
	$('#link_' + selecteddate).addClass('selected');

	var s = getHash('subject');
	if ($('#subj_' + s).get(0) !== undefined) {
		chooseSubject($('#subj_' + s).get(0).innerHTML);
	}

	var hashvals = '';
	if (hashfields.subject !== undefined) {
		hashvals += hashfields.subject;
	}
	hashvals += '/';
	if (hashfields.day !== undefined) {
		hashvals += hashfields.day;
	}
	$('#inputbox').val(hashvals);
	searchResults_updateFunc();
};

// Category functions.

var toggle = function (category) {
	var cEl = $('#'+category.replace('/', '\\/')).get(0);
	if (cEl.checked) {
		cEl.checked = false;
		_gaq.push(['_trackEvent', 'Categories', 'Toggle', category, 0]);
	} else {
		cEl.checked = true;
		_gaq.push(['_trackEvent', 'Categories', 'Toggle', category, 1]);
	}
	searchResults_updateFunc(true);
};

var getSelectedCategories = function () {
	var boxes = $('.togglebox');
	var selected = "";
	for (var i in boxes) {
		if (boxes[i] !== null && boxes[i].checked) {
			selected += boxes[i].id + ",";
		}
	}
	return selected;
};

// Initilaization functions.

var initialize = function (lat, long, zoom, puri, pzoomuri, pclickuri, pversion, defaultMap) {
	zoomuri = pzoomuri;
	clickuri = pclickuri;
	uri = puri;
	version = pversion;
        if(zoom < 0) {
		bb = new Array();
		for (var i = 0; i < 100; i++)
		{
			bb[i] = new google.maps.LatLngBounds();
		}
	}
	map = new google.maps.Map($('#map_canvas').get(0), {
		zoom: Math.abs(zoom),
		center: new google.maps.LatLng(lat, long),
		mapTypeControlOptions: {
			mapTypeIds: ['Map2', google.maps.MapTypeId.SATELLITE],
			style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR,
			position: google.maps.ControlPosition.TOP_LEFT
		},
		mapTypeId: defaultMap
	});

	var styledMapType = new google.maps.StyledMapType([
		{
			featureType: "poi",
			elementType: "all",
			stylers: [
				{ visibility: "off" }
			]
		},{
			featureType: "landscape.man_made",
			elementType: "all",
			stylers: [
				{ visibility: "off" }
			]
		},{
			featureType: "transit.station",
			elementType: "all",
			stylers: [
				{ visibility: "off" }
			]
		}
	], {name: 'Map'});

	map.mapTypes.set('Map2', styledMapType);

	initMarkers();

	$(window).bind('hashchange', hashChange);

	hashChange();
};

var initCredits = function () {
	addControl('credits', google.maps.ControlPosition.RIGHT_BOTTOM);
	addControl('credits-small', google.maps.ControlPosition.RIGHT_BOTTOM);
};

var initToggle = function () {
	addControl('toggleicons', google.maps.ControlPosition.RIGHT_TOP);
};

var initBookmarks = function () {
	if ($('#bookmarks') === null) {
		return;
	}
	addControl('bookmarks', google.maps.ControlPosition.TOP_RIGHT);
	$('#bookmarks').show();
};

var initGeoloc = function () {
	if (navigator.geolocation) {
		addControl('geobutton', google.maps.ControlPosition.TOP_RIGHT);
	} else {
		$('#geobutton').get(0).style.display = 'none';
	}
};

var initMarkers = function () {
	$.get('alldata.php?v='+version, function(data,textstatus,xhr) {
		window.markers = {};
		window.infowindows = {};
		data.map(function(markpt) {
			if (markpt.length == 0) {
				return;
			}
			var pos = markpt[0];
			var lat = markpt[1];
			var lon = markpt[2];
			var poslabel = markpt[3];
			var icon = markpt[4];
			var ll = new google.maps.LatLng(lat, lon);
			markers[pos] = new google.maps.Marker({
				position: ll, 
				title: poslabel.replace('\\\'', '\''),
				map: window.map,
				icon: icon,
				visible: false
			});
			infowindows[pos] = new google.maps.InfoWindow({ content: '<div id="content">' +
				'<h2 id="title"><img class="icon" style="width:20px;" src="' + icon + '" />' + poslabel + '</h2>' +
				'<a class="odl" href="' + pos + '">Visit page</a><div id="bodyContent">Loading...</div></div>'
			});
			if(bb !== undefined) {
				bb[Math.floor(Math.random()*100)].extend(ll);
			}
		});
		cont();
	},'json');
	$.get('polygons.php?v=' + version, function(data,textstatus,xhr) {
		window.polygons = {};
		window.polygoninfowindows = {};
		window.polygonnames = {};
		window.polygonlls = {};
		var buildingIcon = new google.maps.MarkerImage('img/building.png',
			new google.maps.Size(20, 20),
			new google.maps.Point(0, 0),
			new google.maps.Point(10, 10)
		);
		data.map(function (markpt) {
			if (markpt.length === 0) {
				return;
			}
			var pos = markpt[0];
			var poslabel = markpt[1];
			var zindex = markpt[2];
			var points = markpt[3];
			var ll = new google.maps.LatLng(markpt[5][1], markpt[5][0]).toString();
			polygonnames[ll] = poslabel;
			polygonlls[pos] = ll;
			var paths = [];
			var i;
			for (i=0; i < points.length-1; i++) {
				paths.push(new google.maps.LatLng(points[i][1], points[i][0])); 
			}	
			var polygonType = 'Building';
			if (paths.length == 0) {
				if (polygons[pos] === undefined) {
					polygons[pos] = [];
				}
				polygons[pos] = new google.maps.Marker({
					position: new google.maps.LatLng(points[i][1], points[i][0]),
					icon: buildingIcon,
					map: window.map,
					visible: true
				});
			} else {
				var fillColor = '#694B28';
				var strokeColor = '#694B28';
				if (zindex === -10) {
					fillColor = '#2B7413';
					strokeColor = '#2B7413';
					polygonType = 'Site';
				}
				
				if (markpt[4] !== '') {
					fillColor = markpt[4];
					strokeColor = markpt[4];
				}

				if (polygons[pos] === undefined) {
					polygons[pos] = [];
				}
				polygons[pos].push(new google.maps.Polygon({
					paths: paths,
					title: poslabel,
					map: window.map,
					zIndex: zindex,
					fillColor: fillColor,
					fillOpacity: 0.2,
					strokeColor: strokeColor,
					strokeOpacity: 1.0,
					strokeWeight: 2.0,
					visible: true
				}));
			}
			polygoninfowindows[pos] = new google.maps.InfoWindow({ content: 
				'<div id="content"><h2 id="title">'+poslabel+'</h2></div>'});
			
			var listener;
			var position;
			if(paths.length == 0) {
				listener = polygons[pos];
				position = listener.getPosition();
			} else {
				listener = polygons[pos][polygons[pos].length-1];
				var bounds = new google.maps.LatLngBounds();
					
				listener.getPath().forEach(function (el, i) {
					bounds.extend(el);
				});
				position = bounds.getCenter();
			}
			google.maps.event.addListener(listener, 'click', function(event) {
				closeAll();
				_gaq.push(['_trackEvent', 'InfoWindow', polygonType, pos]);
				var infowindow = polygoninfowindows[pos];
				var requireload = false;
				if (polygonlls[pos] !== undefined && clusterInfowindows[polygonlls[pos]] !== undefined) {
					infowindow = clusterInfowindows[polygonlls[pos]];
				} else if (firstAtPos[polygonlls[pos]] && infowindows[firstAtPos[polygonlls[pos]]] !== undefined) {
					infowindow = infowindows[firstAtPos[polygonlls[pos]]];
					requireload = firstAtPos[polygonlls[pos]];
				}
				if (event !== undefined) {
					if (event.latLng !== undefined) {
						infowindow.setPosition(event.latLng);
					} else {
						infowindow.setPosition(position);
					}
					infowindow.open(window.map);
				} else {
					infowindow.open(window.map, listener);
				}
				if(requireload) {
					loadWindow(requireload);
				}
			});
		});
		cont();
	},'json');
};

var initMarkerEvents = function () {
	for (var i in markers) {
		with ({i: i})
		{
			google.maps.event.addListener(markers[i], 'click', function () {
				closeAll();
				infowindows[i].open(map,markers[i]);
				loadWindow(i);
			});
		}
	}
};

var initSearch = function () {
	$('#search').index = 2;
	addControl('search', google.maps.ControlPosition.TOP_RIGHT);
	$('#search-small').index = 2;
	addControl('search-small', google.maps.ControlPosition.TOP_RIGHT);
};
