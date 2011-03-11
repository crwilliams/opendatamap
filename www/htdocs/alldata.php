<?php
error_reporting(0);
include_once "sparqllib.php";

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

$endpoint = "http://sparql.data.southampton.ac.uk";

$allpos = sparql_get($endpoint, "
PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
PREFIX org: <http://www.w3.org/ns/org#>

SELECT DISTINCT ?pos ?lat ?long ?poslabel ?icon WHERE {
  ?offering a <http://purl.org/openorg/GenericOffering> .
  ?offering <http://purl.org/goodrelations/v1#availableAtOrFrom> ?pos .
  ?pos rdfs:label ?poslabel .
  OPTIONAL { ?pos spacerel:within ?b .
             ?b geo:lat ?lat . 
             ?b geo:long ?long .
             ?b a <http://vocab.deri.ie/rooms#Building> .
           }
  OPTIONAL { ?pos spacerel:within ?s .
             ?s geo:lat ?lat . 
             ?s geo:long ?long .
             ?s a org:Site .
           }
  OPTIONAL { ?pos geo:lat ?lat . }
  OPTIONAL { ?pos geo:long ?long . }
  OPTIONAL { ?pos <http://purl.org/openorg/mapIcon> ?icon . }
  FILTER ( BOUND(?long) && BOUND(?lat) )
}
");

$allbus = sparql_get($endpoint, "
PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
PREFIX org: <http://www.w3.org/ns/org#>

SELECT DISTINCT ?pos ?poslabel ?lat ?long {
  ?rstop <http://id.southampton.ac.uk/ns/inBusRoute> ?route .
  ?rstop <http://id.southampton.ac.uk/ns/busStoppingAt> ?pos .
  ?route <http://www.w3.org/2004/02/skos/core#notation> ?code .
  ?pos rdfs:label ?poslabel .
  ?pos geo:lat ?lat .
  ?pos geo:long ?long .
  FILTER ( REGEX( ?code, '^U', 'i') )
}
");
?>
var myLatlng = new google.maps.LatLng(<?php echo $lat; ?>, <?php echo $long; ?>);
var myOptions = {
  zoom: <?php echo $zoom; ?>,
  center: myLatlng,
  mapTypeId: google.maps.MapTypeId.ROADMAP
};
var map = new google.maps.Map(document.getElementById("map_canvas"),
    myOptions);

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

function initialize() {
  
<?php
$i = 0;
foreach($allpos as $point) {
$point['poslabel'] = str_replace('\'', '\\\'', $point['poslabel']);
$a = $point['poslabel'];
?>

markers['<?php echo $point['pos'] ?>'] = new google.maps.Marker({
    position: new google.maps.LatLng(<?php echo $point['lat'] ?>, <?php echo $point['long'] ?>), 
    title: '<?php echo $point['poslabel'] ?>',
    map: map,
    icon: '<?php echo $point['icon']!=""?$point['icon']:"resources/blackness.png" ?>',
    visible: false
});

infowindows['<?php echo $point['pos'] ?>'] = new google.maps.InfoWindow({
    content: '<div id="content">'+
    '<h2 id="title"><?php echo $point['poslabel'] ?></h2>'+
    '<div id="bodyContent">Loading...'+
    '</div>'+
    '</div>'
});

<?php
$i++;
}

foreach($allbus as $point) {
?>
markers['<?php echo $point['pos'] ?>'] = new google.maps.Marker({
    position: new google.maps.LatLng(<?php echo $point['lat'] ?>, <?php echo $point['long'] ?>), 
    title: '<?php echo $point['poslabel'] ?>',
    map: map,
    icon: 'http://google-maps-icons.googlecode.com/files/bus.png',
    visible: false
});

infowindows['<?php echo $point['pos'] ?>'] = new google.maps.InfoWindow({
    content: '<div id="content">'+
    '<h2 id="title"><?php echo $point['poslabel'] ?></h2>'+
    '<div id="bodyContent">Loading...'+
    '</div>'+
    '</div>'
});
<?php
$i++;
}
?>
//var clusterer = new MarkerClusterer(map, markers, mcOptions);
window.loadWindow = function(j) {
	var xmlhttp = new XMLHttpRequest();
	xmlhttp.open("GET","info.php?uri="+encodeURI(j),true);
	xmlhttp.send();
	xmlhttp.onreadystatechange=function()
	{
		if (xmlhttp.readyState==4 && xmlhttp.status==200)
		{
			//infowindows[j].setContent(infowindows[j].getContent()+'<br />'+xmlhttp.responseText);
			infowindows[j].setContent(xmlhttp.responseText);
		}
	}
}

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

initgeoloc();
inittoggle();
initcredits();
}

var inputBox = document.getElementById("inputbox");
var list = document.getElementById("list");
var oldString = null;

var xmlhttp = undefined;

var updateFunc = function() {
	var enabledCategories = getSelectedCategories();
     reset_search_icon();
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
			//alert(matches.length);
			for(var m in matches) {
				if(markers[matches[m]] !== undefined)
					markers[matches[m]].setVisible(true);
			}
			list.innerHTML = "";
			var re = new RegExp('('+inputBox.value+')',"gi");
			for(var m in labelmatches) {
				var dispStr = new String(labelmatches[m]).replace(re, "<b>$1</b>");
				//var dispStr = labelmatches[m];
				list.innerHTML += '<li onclick="inputBox.value = \'^'+labelmatches[m]+'$\'; updateFunc();">'+dispStr+'</li>';
			}
			cluster();
			
			if (jQuery("#spinner").is(":visible")) {
			  jQuery("#spinner").fadeOut();
			}
			// end of initialize 
			
		}
	}
}

inputBox.onkeyup = updateFunc;
updateFunc();
<?php
if(true || $_SERVER['REMOTE_ADDR'] == "188.222.196.170")
{
?>
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
/*
					if(markers[i].getIcon() == markers[firstAtPos[markers[i].getPosition().toString()]].getIcon())
					{
						clusterMarkers[markers[i].getPosition().toString()].setIcon(markers[i].getIcon());
					}
					else
					{
						clusterMarkers[markers[i].getPosition().toString()].setIcon('resources/cluster5.png');
					}
*/
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
<?php
}
else
{
?>
var cluster = function() {}
<?php
}
?>
