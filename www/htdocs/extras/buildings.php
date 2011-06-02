<?php
/**
 *
 * This file generates a set of polygons to display as buildings.
 * Its output should be a KML document, which can be rendered by google maps.
 *
 */

error_reporting(0);
$pathtoroot = "../";
include_once $pathtoroot."inc/sparqllib.php";

$endpoint = "http://sparql.data.southampton.ac.uk";

$sites = sparql_get($endpoint, "
SELECT DISTINCT ?name ?outline ?hfeature ?lfeature ?number WHERE {
  ?url a <http://vocab.deri.ie/rooms#Building> .
  ?url <http://purl.org/dc/terms/spatial> ?outline .
  ?url <http://www.w3.org/2000/01/rdf-schema#label> ?name .
  OPTIONAL { ?url <http://purl.org/openorg/hasFeature> ?hfeature . 
           ?hfeature <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://id.southampton.ac.uk/ns/PlaceFeature-ResidentialUse> }
  OPTIONAL { ?url <http://purl.org/openorg/lacksFeature> ?lfeature . 
           ?lfeature <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://id.southampton.ac.uk/ns/PlaceFeature-ResidentialUse> }
  OPTIONAL { ?url <http://www.w3.org/2004/02/skos/core#notation> ?number . }
} 
");
?>
<?php echo '<?xml version="1.0"?>' ?>
<kml xmlns="http://earth.google.com/kml/2.2">
	<Document>
		<name>University of Southampton Buildings</name>
		<PolyStyle id="residentialStyle">
			<color>ffcc0000</color>
			<colorMode>normal</colorMode>
		</PolyStyle>
		<PolyStyle id="nonResidentialStyle">
			<color>ff0000cc</color>
			<colorMode>normal</colorMode>
		</PolyStyle>
<?php foreach($sites as $site) { ?>
		<Placemark>
			<name><?php echo htmlspecialchars($site['name']) ?> (<?php echo $site['number'] ?>)</name>
<?php if($site['hfeature'] != "") { ?>
			<styleUrl>#residentialStyle</styleUrl>
<?php } else if($site['lfeature'] != "") { ?>
			<styleUrl>#nonResidentialStyle</styleUrl>
<?php } ?>
			<description/>  
			<Polygon>
				<outerBoundaryIs>
					<LinearRing>
						<tessellate>1</tessellate>
						<coordinates>
<?php
$site['outline'] = explode(",", str_replace(array("POLYGON((", "))"), "", $site['outline']));
foreach($site['outline'] as $p)
{
	echo str_replace(' ', ',', $p).',0.000000'."\n";
}
?>

						</coordinates>
					</LinearRing>
				</outerBoundaryIs>
			</Polygon>
		</Placemark>
<?php } ?>
	</Document>
</kml>
