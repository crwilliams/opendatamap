<?
$uri = urldecode($_GET['uri']);
if(preg_match('/http:\/\/id\.southampton\.ac\.uk\/point-of-service\/parking-([0-9]{4})/', $uri, $matches))
{
	echo "<style>table{width:100%; margin:3px} td{background-color:black; color:yellow; margin:3px; padding:3px}</style>";
	$data = @file_get_contents("http://dor.ky/api/parking/Southampton/C0".$matches[1]."");
	if($data != "")
	{
		$data = ltrim($data, "{");
		$data = rtrim($data, "}");
		$data = explode(",", $data);
		foreach($data as $dataline)
		{
			list($k, $v) = explode(":", $dataline);
			$k = trim($k, '"');
			$v = trim($v, '"');
			$d[$k] = $v;
		}
		echo '<table>';
		echo '<tr><td>State</td><td>'.$d['state'].'</td></tr>';
		echo '<tr><td>Capacity</td><td>'.$d['capacity'].'</td></tr>';
		echo '<tr><td>Used</td><td>'.$d['used'].'</td></tr>';
		echo '</table>';
		echo 'updated at '.date('H:i:s d/m/y', $d['updated']);
		echo '<br/><small>This information is provided by <a href="http://dor.ky/code/apis/parking">Scott Wilcox&apos;s Parking Data API</a></small>';
	}
	else
	{
		echo "Live parking data is currently unavailable.";
	}
}
?>
