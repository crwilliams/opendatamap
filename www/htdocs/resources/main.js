var mcOptions = {gridSize: 50, maxZoom: 15};
var markers = new Array();
var infowindows = new Array();
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

window.loadWindow = function(j) {
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

var initmarkerevents = function() {
	for(var i in markers) {
		with ({ j: i }) {
		google.maps.event.addListener(markers[i], 'click', function() {
			for(var i in markers) {
				infowindows[i].close();
			}
			for(var i in clusterMarkers) {
				clusterInfowindows[i].close();
			}
			infowindows[j].open(map,markers[j]);
	
			loadWindow(j);
		});
			}
	}
}

var oldString = null;

var xmlhttp = undefined;

updateFunc = function() {
	var enabledCategories = getSelectedCategories();
     reset_search_icon();
	var inputBox = document.getElementById("inputbox");
	var list = document.getElementById("list");
	if(inputBox.value == oldString)
		return;
	oldString = inputBox.value;
	if(xmlhttp !== undefined)
	  xmlhttp.abort();
	xmlhttp = new XMLHttpRequest();
	xmlhttp.open("GET","matches.php?q="+inputBox.value+'&ec='+enabledCategories,true);
	xmlhttp.send();
	xmlhttp.onreadystatechange=function()
	{
		if (xmlhttp.readyState==4 && xmlhttp.status==200)
		{
			for(var i in markers) {
				markers[i].setVisible(false);
			}
			eval(xmlhttp.responseText);
			selectIndex = -1;
			//alert(matches.length);
			for(var m in matches) {
				if(markers[matches[m]] !== undefined)
					markers[matches[m]].setVisible(true);
			}
			list.innerHTML = "";
			var re = new RegExp('('+inputBox.value+')',"gi");
			limit = 0;
			for(var m in labelmatches) {
				var dispStr;
				if(inputBox.value != "")
					dispStr = new String(labelmatches[m]).replace(re, "<span style='background-color:#FFFF66'>$1</span>");
				else
					dispStr = labelmatches[m];
				list.innerHTML += '<li id="li'+limit+'" onclick="setInputBox(\'^'+labelmatches[m]+'$\'); updateFunc();">'+dispStr+'</li>';
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
				for(var i in markers) {
					infowindows[i].close();
				}
				for(var i in clusterMarkers) {
					clusterInfowindows[i].close();
				}
				clusterInfowindows[j].open(map,clusterMarkers[j]);
			});
		}
	}
}
