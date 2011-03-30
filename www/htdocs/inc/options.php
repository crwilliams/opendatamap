<?php
error_reporting(0);
require_once "inc/sparqllib.php";

$endpoint = "http://sparql.data.southampton.ac.uk";

function getBuildingsName() {
	global $endpoint;
	$buildings = sparql_get($endpoint, "
		SELECT DISTINCT ?uri ?name ?number WHERE {
		  ?uri a <http://vocab.deri.ie/rooms#Building> .
		  ?uri <http://www.w3.org/2000/01/rdf-schema#label> ?name .
		  ?uri <http://www.w3.org/2004/02/skos/core#notation> ?number .
		} ORDER BY ?name
	");
	echo '<option value="">Select building by name</option>';
	foreach($buildings as $building) {
		echo '<option value="'.$building['uri'].'">'.$building['name'].': Building '.$building['number'].'</option>';
	}
}

function getBuildingsNumber() {
	global $endpoint;
	$buildings = sparql_get($endpoint, "
		SELECT DISTINCT ?uri ?name ?number WHERE {
		  ?uri a <http://vocab.deri.ie/rooms#Building> .
		  ?uri <http://www.w3.org/2000/01/rdf-schema#label> ?name .
		  ?uri <http://www.w3.org/2004/02/skos/core#notation> ?number .
		} ORDER BY ?number
	");
	echo '<option value="">Select building by number</option>';
	foreach($buildings as $building) {
		echo '<option value="'.$building['uri'].'">Building '.$building['number'].': '.$building['name'].'</option>';
	}
}

function getGenericOfferings() {
	global $endpoint;
	$offerings = sparql_get($endpoint, "
		PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
		PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
		PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
		PREFIX org: <http://www.w3.org/ns/org#>
		PREFIX gr: <http://purl.org/goodrelations/v1#>

		SELECT DISTINCT ?label WHERE {
		  ?ps a gr:ProductOrServicesSomeInstancesPlaceholder .
		  ?ps rdfs:label ?label .
		  ?offering gr:includes ?ps .
		  ?offering gr:availableAtOrFrom ?pos .
		} ORDER BY ?label
	");
	echo '<option value="">Select generic product/service (eg. caffeine)</option>';
	foreach($offerings as $offering) {
		echo '<option value="'.$offering['label'].'">'.$offering['label'].'</option>';
	}
}

function getSpecificOfferings() {
	global $endpoint;
	$offerings = sparql_get($endpoint, "
		PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
		PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
		PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
		PREFIX org: <http://www.w3.org/ns/org#>
		PREFIX gr: <http://purl.org/goodrelations/v1#>

		SELECT DISTINCT ?label WHERE {
		  ?ps a gr:ProductOrService .
		  ?ps rdfs:label ?label .
		  ?offering gr:includes ?ps .
		  ?offering gr:availableAtOrFrom ?pos .
		} ORDER BY ?label
	");
	echo '<option value="">Select specific product/service (eg. cappuccino (large))</option>';
	foreach($offerings as $offering) {
		echo '<option value="'.$offering['label'].'">'.$offering['label'].'</option>';
	}
}

function getSites($selected = null) {
	global $endpoint;
	$sites = sparql_get($endpoint, "
		SELECT DISTINCT ?uri ?name WHERE {
		  ?uri a <http://www.w3.org/ns/org#Site> .
		  ?uri <http://www.w3.org/2000/01/rdf-schema#label> ?name .
		} ORDER BY ?name
	");
	echo '<option value="">Select site</option>';
	foreach($sites as $site) {
		if($site['uri'] == $selected)
			echo '<option value="'.$site['uri'].'" selected="true">'.$site['name'].'</option>';
		else
			echo '<option value="'.$site['uri'].'">'.$site['name'].'</option>';
	}
}

function getPlaces($selected = null) {
	global $endpoint;
	$sites = sparql_get($endpoint, "
		PREFIX foaf: <http://xmlns.com/foaf/0.1/>
		
		SELECT DISTINCT ?uri ?name WHERE {
		  ?uri <http://www.w3.org/2000/01/rdf-schema#label> ?name .
		  ?stop foaf:based_near ?uri .
		} ORDER BY ?name
	");
	echo '<option value="">Select place</option>';
	foreach($sites as $site) {
		if($site['uri'] == $selected)
			echo '<option value="'.$site['uri'].'" selected="true">'.$site['name'].'</option>';
		else
			echo '<option value="'.$site['uri'].'">'.$site['name'].'</option>';
	}
}

function getRoutes($from, $to) {
	global $endpoint;
	$routes = sparql_get($endpoint, "
PREFIX soton: <http://id.southampton.ac.uk/ns/>
PREFIX foaf: <http://xmlns.com/foaf/0.1/>
PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX naptan: <http://transport.data.gov.uk/def/naptan/>

SELECT DISTINCT ?sstoplabel ?dstoplabel ?routecode ?routelabel ?sstoprouteseq ?dstoprouteseq WHERE {
  ?route skos:notation ?routecode .
  ?route rdfs:label ?routelabel .
  ?sstoproute soton:inBusRoute ?route .
  ?dstoproute soton:inBusRoute ?route .
  ?sstoproute soton:busRouteSequenceNumber ?sstoprouteseq .
  ?dstoproute soton:busRouteSequenceNumber ?dstoprouteseq .
  ?sstoproute soton:busStoppingAt ?sstop .
  ?dstoproute soton:busStoppingAt ?dstop .
  ?sstop a naptan:BusStop .
  ?sstop foaf:based_near <$from> .
  ?sstop rdfs:label ?sstoplabel .
  ?dstop a naptan:BusStop .
  ?dstop foaf:based_near <$to> .
  ?dstop rdfs:label ?dstoplabel .
  FILTER ( ?dstoprouteseq > ?sstoprouteseq )
} ORDER BY ?routecode (?dstoprouteseq - ?sstoprouteseq)
	");
	foreach($routes as $route)
	{
		$code = $route['routecode'];
		if($code != $oldcode) {
			$oldstopcount = 0;
			$oldcode = $code;
		}
		$stopcount = $route['dstoprouteseq'] - $route['sstoprouteseq'];
		if($oldstopcount == 0 || $oldstopcount + 1 == $stopcount) {
			$stops[$code]['from'][] = $route['sstoplabel'];
			$stops[$code]['to'][] = $route['dstoplabel'];
			if($oldstopcount == 0) {
				$shortest[$code] = $stopcount;
			}
			$oldstopcount = $stopcount;
		}

/*
		echo $route['sstoplabel'];
		echo $route['dstoplabel'];
		echo $route['routecode'];
		echo $route['routelabel'];
		echo $route['sstoprouteseq'];
		echo $route['dstoprouteseq'];
*/
	}

	asort($shortest);
	echo "<table>";
	echo "<tr><th>Bus Route</th><th>From</th><th>To</th></tr>";
	foreach(array_keys($shortest) as $code) {
		echo "<tr>";
		echo "<td>$code</td>";
		echo "<td><ul>";
		$stops[$code]['from'] = array_reverse(array_unique($stops[$code]['from']));
		foreach($stops[$code]['from'] as $from)
		{
			echo "<li>$from</li>";
		}
		echo "</ul></td>";
		echo "<td><ul>";
		$stops[$code]['to'] = array_unique($stops[$code]['to']);
		foreach($stops[$code]['to'] as $to)
		{
			echo "<li>$to</li>";
		}
		echo "</ul></td>";
		echo "</tr>";
	}
	echo "</table>";
}

?>
