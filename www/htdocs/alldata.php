var initmarkers = function() {
<?php
error_reporting(0);
include_once "sparqllib.php";

$endpoint = "http://sparql.data.southampton.ac.uk";

$allpos = sparql_get($endpoint, "
PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
PREFIX org: <http://www.w3.org/ns/org#>
PREFIX gr: <http://purl.org/goodrelations/v1#>

SELECT DISTINCT ?pos ?lat ?long ?poslabel ?icon WHERE {
  ?offering a gr:Offering .
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
} ORDER BY ?poslabel
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
} ORDER BY ?poslabel
");

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
    '<h2 id="title"><img style="width:20px;" src="<?php echo $point['icon']!=""?$point['icon']:"resources/blackness.png" ?>" /><?php echo $point['poslabel'] ?></h2>'+
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
    '<h2 id="title"><img style="width:20px;" src="http://google-maps-icons.googlecode.com/files/bus.png" /><?php echo $point['poslabel'] ?></h2>'+
    '<div id="bodyContent">Loading...'+
    '</div>'+
    '</div>'
});
<?php
$i++;
}
?>
}
