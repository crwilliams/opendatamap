<?
global $iconcats;
global $iconmap;
$iconpath = 'http://data.southampton.ac.uk/map-icons/';
$file = fopen('catlist.csv', 'r');
while($row = fgetcsv($file))
{
	$iconcats[$iconpath.$row[2]] = $row[1];
}
fclose($file);

function in_cat($iconcats, $icon, $cats)
{
	if($icon == 'img/blackness.png')
		return true;
	if(in_array("Buildings", $cats) && substr($icon, 0, 51) == "http://google-maps-icons.googlecode.com/files/black")
		return true;
	return in_array($iconcats[$icon], $cats);
}
?>
