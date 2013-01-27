<?
$config['Site title'] = "University of Southampton Open Day Map";
$config['Site keywords'] = "University of Southampton,open day,map,Southampton,amenity,bus stop,building,site,campus,interactive";
$config['Site description'] = "Interactive Open Day Map of the University of Southampton";
$config['default lat'] = 50.9355;
$config['default long'] = -1.39595;
$config['default zoom'] = 13;
$config['datasource'] = array('openday', 'postcode', /*'oxford', 'cambridge'*/);
if($versionparts[1] == 'iframe')
{
	$config['enabled'] = array('sidebarhidden', 'bookmarks', '-title');
}
else
{
	$config['enabled'] = array('sidebar', 'bookmarks', '-title'/*, 'search'*/);
	$config['map style'] = 'left:300px;';
}
?>
