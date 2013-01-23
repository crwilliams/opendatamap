<ul>
<li style='text-align:center'><img style='padding:10px;' src='http://users.ecs.soton.ac.uk/crw104/img/logo/uos.png'/></li>
<?
	foreach(OpendayDataSource::getBookmarks() as $bookmark)
	{
		echo "<li title='Jump to ".$bookmark['label']."' class='clickable' style='text-align:center' onclick='zoomTo(\"".$bookmark['area']."\", false); updateFunc();'>".$bookmark['label']."</li>";
	}
?>
</ul>
