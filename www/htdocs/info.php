<?php
include_once "config.php";

// This script should return, for a given marker ID (passed in as $_GET['uri']), an html fragment that can be displayed in that icon's infowindow.
// It is called via ajax when the infowindow is opened.

$uri = urldecode($_GET['uri']);

foreach($config['datasource'] as $ds)
{
	$dsclass = ucwords($ds).'DataSource';
	if(call_user_func(array($dsclass, 'processURI'), $uri))
	{
?>
		<div class='sharing'>
		<a href="http://www.facebook.com/sharer.php?u=<?= urlencode('http://opendatamap.ecs.soton.ac.uk/'.$_GET['v'].'?uri='.urlencode($uri)) ?>">Share this place on Facebook</a>
		| <a href="<?= 'http://opendatamap.ecs.soton.ac.uk/'.$_GET['v'].'?uri='.urlencode($uri) ?>">Link to this place</a>
		</div>
<?php
		die();
	}
}
echo "[$uri]";
?>





