<?
global $iconcats;
global $iconmap;
$iconpath = 'http://opendatamap.ecs.soton.ac.uk/img/icon/';
$file = fopen('catlist.csv', 'r');
while($row = fgetcsv($file))
{
	$iconcats[$row[0]] = $row[1];
	$iconmap[$row[0]] = $iconpath.$row[2];
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
