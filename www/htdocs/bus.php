<?
$uri = urldecode($_GET['uri']);
if(preg_match('/http:\/\/id\.southampton\.ac\.uk\/bus-stop\/(.*)/', $uri, $matches))
{
	echo "<style>table{width:100%; margin:3px} td{background-color:black; color:yellow; margin:3px; padding:3px}</style>";
        $data = json_decode(file_get_contents('http://data.southampton.ac.uk/bus-stop/'.$matches[1].'.json?max=6', 'r'));
	echo '<table>';
	foreach($data->stops as $stop)
	{
		echo '<tr><td>'.$stop->name.'</td><td>'.$stop->dest.'</td><td>'.$stop->time.'</td></tr>';
	}
	echo '</table>';
}
?>
