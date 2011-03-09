<?
$uri = urldecode($_GET['uri']);
if(preg_match('/http:\/\/id\.southampton\.ac\.uk\/bus-stop\/(.*)/', $uri, $matches))
{
	echo "<style>table{width:100%; margin:3px} td{background-color:black; color:yellow; margin:3px; padding:3px}</style>";
	fpassthru(fopen("http://data.southampton.ac.uk/bus-stop/".$matches[1].".html?view=embed", 'r'));
}
?>
