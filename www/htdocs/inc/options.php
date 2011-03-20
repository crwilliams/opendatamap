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

function getSites() {
	global $endpoint;
	$sites = sparql_get($endpoint, "
		SELECT DISTINCT ?uri ?name WHERE {
		  ?uri a <http://www.w3.org/ns/org#Site> .
		  ?uri <http://www.w3.org/2000/01/rdf-schema#label> ?name .
		} ORDER BY ?name
	");
	echo '<option value="">Select site</option>';
	foreach($sites as $site) {
		echo '<option value="'.$site['uri'].'">'.$site['name'].'</option>';
	}
}
?>
