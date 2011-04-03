<?
$file = fopen('catlist.csv', 'r');
while($row = fgetcsv($file))
{
	$iconcats[$row[0]] = $row[1];
}
fclose($file);

function in_cat($iconcats, $icon, $cats)
{
	if($icon == 'img/blackness.png')
		return true;
	return in_array($iconcats[$icon], $cats);
}
?>
