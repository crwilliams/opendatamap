<?
error_reporting(E_ERROR);
include_once "inc/sparqllib.php";
include_once "inc/categories.php";


$endpoint = "http://sparql.data.southampton.ac.uk";
// Put needed parameter names from GetPOI request in an array called $keys. 
$keys = array( "layerName", "lat", "lon", "radius", "CHECKBOXLIST", "SEARCHBOX" );

// Initialize an empty associative array.
$value = array(); 
 
try {
  // Retrieve parameter values using $_GET and put them in $value array with parameter name as key. 
  foreach( $keys as $key ) {
  
    if ( isset($_GET[$key]) )
      $value[$key] = $_GET[$key]; 
    else 
      throw new Exception($key ." parameter is not passed in GetPOI request.");
  }//foreach
}//try
catch(Exception $e) {
  echo 'Message: ' .$e->getMessage();
}//catch

    // Create an empty array named response.
    $response = array();
    
    // Assign cooresponding values to mandatory JSON response keys.
    $response["layer"] = $value["layerName"];
    
    // Use Gethotspots() function to retrieve POIs with in the search range.  
    $response["hotspots"] = Gethotspots( $value );

    // if there is no POI found, return a custom error message.
    if ( empty( $response["hotspots"] ) ) {
        $response["errorCode"] = 20;
         $response["errorString"] = "No POI found. Please adjust the range.";
    }//if
    else {
          $response["errorCode"] = 0;
          $response["errorString"] = "ok";
    }//else


    // Put the JSON representation of $response into $jsonresponse.
    $jsonresponse = json_encode( $response );
    
    // Declare the correct content type in HTTP response header.
    header( "Content-type: application/json; charset=utf-8" );
    
    // Print out Json response.
    echo $jsonresponse;


function sortpoints($a, $b) {
	if($a['distance'] == $b['distance'])
		return 0;
	return ($a['distance'] < $b['distance']) ? -1 : 1;
}

function Gethotspots( $value ) {
global $endpoint;
global $iconcats;

$cats = explode(',', $value['CHECKBOXLIST']);
$q = trim($value['SEARCHBOX']);

if($q == '')
{
	$filter = "FILTER ( BOUND(?lon) && BOUND(?lat) )";
}
else
{
	$filter = "FILTER ( BOUND(?lon) && BOUND(?lat) && (REGEX( ?plabel, '$q', 'i') || REGEX( ?title, '$q', 'i')) )";
}
 // Iterator for the response array.
  $i = 0; 
  
$lat = $value['lat'];
$lon = $value['lon'];

$pois1 = sparql_get($endpoint, "
PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
PREFIX org: <http://www.w3.org/ns/org#>

SELECT DISTINCT ?id ?title ?lat ?lon ?icon ?page {
  ?rstop <http://id.southampton.ac.uk/ns/inBusRoute> ?route .
  ?rstop <http://id.southampton.ac.uk/ns/busStoppingAt> ?id .
  ?route <http://www.w3.org/2004/02/skos/core#notation> ?code .
  ?id rdfs:label ?title .
  ?id geo:lat ?lat .
  ?id geo:long ?lon .
  ?id <http://purl.org/openorg/mapIcon> ?icon .
  ?id <http://id.southampton.ac.uk/ns/mobilePage> ?page .
  FILTER ( ( REGEX( ?title, '$q', 'i') || REGEX( ?code, '$q', 'i')
  ) && REGEX( ?code, '^U', 'i') )
} ORDER BY (((?lat - $lat)*(?lat - $lat)*2.1) + ((?lon - $lon)*(?lon - $lon)))
");

$pois2 = sparql_get($endpoint, "
PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
PREFIX org: <http://www.w3.org/ns/org#>
PREFIX gr: <http://purl.org/goodrelations/v1#>
PREFIX foaf: <http://xmlns.com/foaf/0.1/>

SELECT DISTINCT ?id ?title ?lat ?lon ?icon ?www ?phone ?email ?bname ?sname ?page WHERE {
  ?id a gr:LocationOfSalesOrServiceProvisioning .
  ?id rdfs:label ?title .
  OPTIONAL { ?offering a gr:Offering .
             ?offering gr:availableAtOrFrom ?id .
             ?offering gr:includes ?ps .
             ?ps rdfs:label ?plabel .
           }
  OPTIONAL { ?id spacerel:within ?b .
             ?b geo:lat ?lat . 
             ?b geo:long ?lon .
             ?b a <http://vocab.deri.ie/rooms#Building> .
             ?b rdfs:label ?bname .
             OPTIONAL { ?b spacerel:within ?bs .
                        ?bs a org:Site .
                        ?bs rdfs:label ?sname .
                      }
           }
  OPTIONAL { ?id spacerel:within ?s .
             ?s geo:lat ?lat . 
             ?s geo:long ?lon .
             ?s a org:Site .
             ?s rdfs:label ?sname .
           }
  OPTIONAL { ?id geo:lat ?lat .
             ?id geo:long ?lon .
           }
  OPTIONAL { ?id <http://purl.org/openorg/mapIcon> ?icon . }
  OPTIONAL { ?id foaf:homepage ?www . }
  OPTIONAL { ?id foaf:phone ?phone . }
  OPTIONAL { ?id foaf:mailbox ?email . }
  OPTIONAL { ?id <http://id.southampton.ac.uk/ns/mobilePage> ?page . }
  $filter
} ORDER BY (((?lat - $lat)*(?lat - $lat)*2.1) + ((?lon - $lon)*(?lon - $lon)))
");

foreach(array_merge((array)$pois2, (array)$pois1) as $poi)
{
	$latdiff = ($poi['lat'] - $lat) * 111.24824;
	$londiff = ($poi['lon'] - $lon) * 70.19765;
	$poi['distance'] = sqrt(($latdiff*$latdiff) + ($londiff*$londiff));
	if(isset($pois[$poi['id']])) {
	} else {
		$pois[$poi['id']] = $poi;
	}
}

usort($pois, 'sortpoints');

  /* Process the $pois result */
  
  // if $pois array is empty, return empty array. 
  if ( empty($pois) ) {
      
      $response["hotspots"] = array ();
    
  }//if 
  else { 
      
      // Put each POI information into $response["hotspots"] array.
     foreach ( $pois as $poi ) {

	if($i >= 100)
		break;

	if($poi['distance'] > $value['radius']/1000)
		break;

	if(!in_cat($iconcats, $poi['icon'], $cats))
		continue;


        // If not used, return an empty actions array. 
        $poi["actions"] = array();
        
        // Store the integer value of "lat" and "lon" using predefined function ChangetoIntLoc.
        $poi["lat"] = ChangetoIntLoc( $poi["lat"] );
        $poi["lon"] = ChangetoIntLoc( $poi["lon"] );
    
         // Change to Int with function ChangetoInt.
        $poi["type"] = 0;//ChangetoInt( $poi["type"] );
        $poi["dimension"] = 2;//ChangetoInt( $poi["dimension"] );
    
        // Change to demical value with function ChangetoFloat
        $poi["distance"] = ChangetoFloat( $poi["distance"] );
    
        // Put the poi into the response array.
        $response["hotspots"][$i] = $poi;
        $i++;
      }//foreach
  
  }//else
  
  return $response["hotspots"];
}//Gethotspots

function json_encode($response)
{
	foreach($response["hotspots"] as $h)
	{
		$actions = array();
		$extralines = "";
		if($h["www"] != "")
			$actions[] = '{"label":"Visit webpage","uri":"'.$h["www"].'"}';
		if($h["email"] != "")
			$actions[] = '{"label":"Email","uri":"'.$h["email"].'"}';
		if($h["phone"] != "")
			$actions[] = '{"label":"Call","uri":"'.$h["phone"].'"}';
		if($h["page"] != "")
			$actions[] = '{"label":"View Live Times","uri":"'.$h["page"].'"}';
		if($h["bname"] != "")
			$extralines .= ',"line2":"'.$h["bname"].'"';
		if($h["sname"] != "")
			$extralines .= ',"line3":"'.$h["sname"].'"';
		$hotspots[] = '{'.
			'"distance":'.$h["distance"].','.
			'"dimension":'.$h["dimension"].','.
			'"transform":{"scale":10,"angle":0,"rel":true},'.
			'"title":"'.$h["title"].'",'.
			'"id":"'.$h["id"].'",'.
			'"lat":'.$h["lat"].','.
			'"lon":'.$h["lon"].','.
			'"type":'.$h["type"].','.
			'"imageURL":"'.str_replace('/', '\/', $h["icon"]).'",'.
			'"object":{"baseURL":"'.str_replace('/', '\/', $h["icon"]).'","icon":"","full":"","reduced":""},'.
			'"actions":['.implode(',', $actions).']'.$extralines.
		'}';
	}

	echo '{"hotspots": ['.implode(',', $hotspots).'],"layer":"'.$response['layer'].'","errorString":"'.$response['errorString'].'","errorCode":"'.$response['errorCode'].'"}';
}

function ChangetoIntLoc( $value_Dec ) {

  return $value_Dec * 1000000;
  
}

function ChangetoInt( $string ) {

  if ( strlen( trim( $string ) ) != 0 ) {
  
    return (int)$string;
  }
  else 
      return NULL;
}

function ChangetoFloat( $string ) {

  if ( strlen( trim( $string ) ) != 0 ) {
  
    return (float)$string;
  }
  else 
      return NULL;
}

?>
