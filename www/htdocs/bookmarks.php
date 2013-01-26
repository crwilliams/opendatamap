<ul>
<?
	foreach(getAllBookmarks() as $bookmark)
	{
		if(isset($bookmark['area']))
		{
			echo "<li title='Jump to ".$bookmark['label']."' class='clickable' style='text-align:center' onclick='zoomTo(\"".$bookmark['area']."\", false); updateFunc();'>".$bookmark['label']."</li>";
		}
		elseif(isset($bookmark['img']))
		{
			echo "<li style='text-align:center'><img style='padding:10px;' src='".$bookmark['img']."'/></li>";
		}
	}
?>
</ul>
