<?php
error_reporting(0);
include_once "sparqllib.php";

$endpoint = "http://sparql.data.southampton.ac.uk";

$sites = sparql_get($endpoint, "
SELECT DISTINCT ?name ?outline WHERE {
  ?url a <http://www.w3.org/ns/org#Site> .
  ?url <http://purl.org/dc/terms/spatial> ?outline .
  ?url <http://www.w3.org/2000/01/rdf-schema#label> ?name .
} 
");
?>
<?php echo '<?xml version="1.0"?>' ?>
<kml xmlns="http://earth.google.com/kml/2.2">
	<Document>
		<name>University of Southampton Sites</name>
<?php foreach($sites as $site) { ?>
		<Placemark>
			<name><?php echo $site['name'] ?></name>
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
