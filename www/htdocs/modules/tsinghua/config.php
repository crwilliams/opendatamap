<?
$config['Site title'] = "Tsinghua University Linked Open Data Map";
$config['Site keywords'] = "Tsinghua University,map,Tsinghua,building,campus,interactive";
$config['Site description'] = "Interactive Map of Tsinghua University, generated from Linked Open Data";
$config['default lat'] = 40;
$config['default long'] = 116.32;
$config['default zoom'] = 16;
$config['default map'] = "google.maps.MapTypeId.SATELLITE";
$config['categories'] = array();
$config['datasource'] = array('tsinghua', /*'oxford', 'cambridge'*/);

if($versionparts[1] == 'embed')
{
	$config['Site title'] = "embed";
	$config['enabled'] = array();
}
$config['hidden'] = true;
?>
