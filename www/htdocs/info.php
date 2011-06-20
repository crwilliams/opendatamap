<?php
error_reporting(0);
include_once "inc/sparqllib.php";

// This script should return, for a given marker ID (passed in as $_GET['uri']), an html fragment that can be displayed in that icon's infowindow.
// It is called via ajax when the infowindow is opened.

$uri = urldecode($_GET['uri']);

foreach($config['datasource'] as $ds)
{
	$dsclass = ucwords($ds).'DataSource';
	if(call_user_func(array($dsclass, 'processURI'), $uri))
		die();
}
?>





