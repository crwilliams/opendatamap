function initcredits()
{
	var geobutton;
	geobutton = document.getElementById('credits');
	geobutton.index = 1;
	map.controls[google.maps.ControlPosition.RIGHT_BOTTOM].push(geobutton);
	geobutton = document.getElementById('credits-small');
	geobutton.index = 1;
	map.controls[google.maps.ControlPosition.RIGHT_BOTTOM].push(geobutton);
}       
