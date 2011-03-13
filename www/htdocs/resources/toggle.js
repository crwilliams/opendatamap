function toggle(category) {
	if(document.getElementById(category).className == "")
	{
		document.getElementById(category).className = "deselected";
		_gaq.push(['_trackEvent', 'Categories', 'Toggle', category, 0]);
	}
	else
	{
		document.getElementById(category).className = "";
		_gaq.push(['_trackEvent', 'Categories', 'Toggle', category, 1]);
	}
}

function getSelectedCategories() {
	var icons = document.getElementById('toggleicons').childNodes;
	var selected = "";
	for(var i in icons)
	{
		if(icons[i] !== null && icons[i].className == "")
			selected += icons[i].id + ",";
	}
	return selected;
}

function inittoggle() {
	var geobutton = document.getElementById('toggleicons');
	geobutton.style.display = 'block';
	geobutton.index = 1;
	map.controls[google.maps.ControlPosition.RIGHT_TOP].push(geobutton);
}
