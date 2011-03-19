var mcOptions = {gridSize: 50, maxZoom: 15};
var markers = new Array();
var infowindows = new Array();
var polygons = new Array();
var polygoninfowindows = new Array();
var clusterMarkers = new Array();
var clusterInfowindows = new Array();

var DEFAULT_SEARCH_ICON = "http://www.picol.org/images/icons/files/png/32/search_32.png";
var CLEAR_SEARCH_ICON = "resources/nt-left.png";

var reset_search_icon = function() {
	var val = jQuery("#inputbox").val();
	if (val.length > 0) {
		jQuery("#clear").attr("src", CLEAR_SEARCH_ICON);
	} else {
		jQuery("#clear").attr("src", DEFAULT_SEARCH_ICON);
	}
}



// TODO merge this in with initialize
jQuery(document).ready(function() {
		      jQuery("#inputbox").bind("keyUp", reset_search_icon);
});

// Colin sez: "someone's clicked on something, you need to load the real data into it"
window.loadWindow = function(j) {
	_gaq.push(['_trackEvent', 'InfoWindow', 'Single', j]);
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.open("GET","info.php?uri="+encodeURI(j),true);
	xmlhttp.send();
	xmlhttp.onreadystatechange=function()
	{
		if (xmlhttp.readyState==4 && xmlhttp.status==200)
		{
			infowindows[j].setContent(xmlhttp.responseText);
		}
	}
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
		with ({ j: i }) {
			google.maps.event.addListener(markers[i], 'click', function() {
				closeAll();
				infowindows[j].open(map,markers[j]);
	
				loadWindow(j);
			});
		}
	}
}

var oldString = null;

var xmlhttp = undefined;

updateFunc = function(force) {
	if(force !== true)
		force = false;
	var enabledCategories = getSelectedCategories();
     reset_search_icon();
	var inputBox = document.getElementById("inputbox");
	var list = document.getElementById("list");
	if(!force && inputBox.value == oldString)
		return;
	oldString = inputBox.value;
	if(xmlhttp !== undefined)
	  xmlhttp.abort();
	xmlhttp = new XMLHttpRequest();
	xmlhttp.open("GET","matches.php?q="+inputBox.value+'&ec='+enabledCategories,true);
	_gaq.push(['_trackEvent', 'Search', 'Request', inputBox.value]);
	xmlhttp.send();
	xmlhttp.onreadystatechange=function()
	{
		if (xmlhttp.readyState==4 && xmlhttp.status==200)
		{
		        
			
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

			var re = new RegExp('('+$.trim(inputBox.value)+')',"gi");
			limit = 0;
			for(var m in labelmatches) {
			    // if it's colins' special last box continue
			    if (m === undefined) continue;
				var dispStr;
				if (labelmatches[m][0] === undefined) {
					try {  console.log('warning colin, labelmatches for ',m,' was undefined'); } catch(e) { }
					continue;	
				}

				if(inputBox.value != "")
					dispStr = new String(labelmatches[m][0]).replace(re, "<span style='background-color:#FFFF66'>$1</span>");
				else
					dispStr = labelmatches[m][0];


				if(labelmatches[m][2] !== undefined)
				{
					if(labelmatches[m][3] !== undefined)
					{
						list.innerHTML += '<li id="li'+limit+'" onclick="zoomTo(\''+labelmatches[m][2]+'\'); setInputBox(\'\'); updateFunc();"><img src="'+labelmatches[m][3]+'" style="width:15px" />'+dispStr+'</li>';
					}
					else
					{
						list.innerHTML += '<li id="li'+limit+'" onclick="zoomTo(\''+labelmatches[m][2]+'\'); setInputBox(\'\'); updateFunc();"><span style="font-size:0.5em">'+labelmatches[m][1]+': </span>'+dispStr+'</li>';
					}
				}
				else
				{
					list.innerHTML += '<li id="li'+limit+'" onclick="setInputBox(\'^'+labelmatches[m][0]+'$\'); updateFunc();">'+dispStr+'</li>';
				} 
				limit++;
			}
			cluster();
			
			if (jQuery("#spinner").is(":visible")) {
			  jQuery("#spinner").fadeOut();
			}
			// end of initialize 
			
		}
	}
}

var nav = function(e)
{
	if(e.keyCode == 40)
		return moveDown();
	else if(e.keyCode == 38)
		return moveUp();
	else if(e.keyCode == 13)
		return select();
}

var zoomTo = function(uri)
{
	var bounds = new google.maps.LatLngBounds();
	if(polygons[uri] !== undefined)
	{
		if(polygons[uri].length !== undefined)
		{
			_gaq.push(['_trackEvent', 'JumpTo', 'Polygon', uri]);
			for(var i = 0; i<polygons[uri].length; i++)
			{
        			polygons[uri][i].getPath().forEach(function(el, i) {
					bounds.extend(el);
				});
			}
			map.fitBounds(bounds);
		}
		else
		{
			_gaq.push(['_trackEvent', 'JumpTo', 'Point', uri]);
			map.panTo(polygons[uri].getPosition());
		}
	}
	else if(markers[uri] !== undefined)
	{
		_gaq.push(['_trackEvent', 'JumpTo', 'Point', uri]);
		map.panTo(markers[uri].getPosition());
	}
}

/*
var showWindow = function(clusterID, individualID) {
	infowindows[individualID].open(map, clusterMarkers[clusterID]);
	loadWindow(individualID);
	_gaq.push(['_trackEvent', 'InfoWindow', 'Cluster', j]);
}
*/

var cluster = function() {
	for(var i in clusterMarkers)
	{
		clusterMarkers[i].setMap(null);
	}
	clusterMarkers = new Array();
	clusterInfoWindows = new Array();
	var positions = new Array();
	var firstAtPos = new Array();
	var str = "";
	var count = 0;
	var count2 = 0;
	for(var i in markers) {
		if(markers[i].getVisible() === true)
		{
			count ++;
			str += markers[i].getTitle();
			str += markers[i].getPosition().toString();
			if(positions[markers[i].getPosition().toString()] !== undefined)
			{
				positions[markers[i].getPosition().toString()] ++;
				markers[i].setVisible(false);
				if(clusterMarkers[markers[i].getPosition().toString()] === undefined)
				{
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
				}
				else
				{
					clusterInfowindows[markers[i].getPosition().toString()].setContent(clusterInfowindows[markers[i].getPosition().toString()].getContent()+
    '<div class="clusteritem" onclick="infowindows[\''+i+'\'].open(map, clusterMarkers[\''+markers[i].getPosition().toString()+'\']); loadWindow(\''+i+'\')"><img class="icon" src="'+markers[i].getIcon()+'" />'+markers[i].getTitle()+'</div>');
					if(markers[i].getIcon() != clusterMarkers[markers[i].getPosition().toString()].getIcon())
					{
						clusterMarkers[markers[i].getPosition().toString()].setIcon(clusterMarkers[markers[i].getPosition().toString()].getIcon()+'&i[]='+markers[i].getIcon());
					}
				}
				count2 ++;
			}
			else
			{
				positions[markers[i].getPosition().toString()] = 1;
				firstAtPos[markers[i].getPosition().toString()] = i;
			}
		}
	}
	for(var i in clusterMarkers) {
		with ({ j: i }) {
			google.maps.event.addListener(clusterMarkers[i], 'click', function() {
				closeAll();
				_gaq.push(['_trackEvent', 'InfoWindow', 'Cluster', j]);
				clusterInfowindows[j].open(map,clusterMarkers[j]);
			});
		}
	}
}
