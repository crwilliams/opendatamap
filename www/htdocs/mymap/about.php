<?
error_reporting(0);
include 'functions.inc.php';
outputHeader("About this tool", "", "GENERIC", true, true);
?>
<p>
This tool was created by <?= mklink('http://id.ecs.soton.ac.uk/person/23977', 'Colin R. Williams') ?> in order to assist in the creation of open geographic data.
</p>
<p>
The problem with existing map creation tools (such as <?= mklink('http://maps.google.com', 'Google Maps') ?> and <?= mklink('http://openspace.ordnancesurvey.co.uk/openspace/', 'OS OpenSpace') ?>) is that their licenses place tight restrictions on what can be done with data created using those tools.  (For more information, see <?= mklink('http://blogs.ecs.soton.ac.uk/data/2011/06/23/generating-open-geographic-data/', 'my blog post', 'blog post on generating open geographic data') ?> on the matter.)  In contrast, this tool makes use of the <?= mklink('http://www.ordnancesurvey.co.uk/oswebsite/products/os-streetview/index.html', 'OS Street View') ?> maps which are provided as part of the <?= mklink('http://www.ordnancesurvey.co.uk/oswebsite/products/os-opendata.html', 'OS OpenData') ?> service, under the <?= mklink('http://www.ordnancesurvey.co.uk/opendata/licence', 'OS OpenData Licence') ?>.  As such, data produced using this tool is considered to be derived data, which is free to be used with attribution to the Ordnance Survey.
</p>
<p>
This tool makes use of a number of openly licensed resources, as follows:
<ul>
	<li><?= mklink('http://openlayers.org/', 'OpenLayers') ?> (available under a <?= mklink('https://raw.github.com/openlayers/openlayers/master/license.txt', 'FreeBSD license') ?>)</li>
	<li><?= mklink('http://jquery.org/', 'jQuery') ?> (available under a <?= mklink('http://github.com/jquery/jquery/blob/master/MIT-LICENSE.txt', 'MIT license') ?>)</li>
	<li><?= mklink('http://jqueryui.com/', 'jQuery UI') ?> (available under a <?= mklink('http://github.com/jquery/jquery/blob/master/MIT-LICENSE.txt', 'MIT license') ?>)</li>
	<li><?= mklink('http://www.famfamfam.com/lab/icons/silk/', 'famfamfam silk icons') ?> (available under the <?= mklink('http://creativecommons.org/licenses/by/3.0/', 'Creative Commons Attribution 3.0 License') ?>)</li>
</ul>
</p>
<?
outputFooter();
?>

