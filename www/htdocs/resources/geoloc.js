function geoloc()
{
	navigator.geolocation.getCurrentPosition(
		function(position) {        
			if( position.coords.accuracy < 5000 )
			{
				map.setCenter(new google.maps.LatLng(position.coords.latitude, position.coords.longitude));
			}
			else
			{
				alert('Sorry, geo location wildly inaccurate ('+ position.coords.accuracy+" meters)"); 
			}
		},        
		function(e) {
			alert('Sorry, geo location failed'); 
		}
	);                                                                  
}
function initgeoloc()
{
	if (navigator.geolocation) {
		var geobutton = document.getElementById('geobutton');
		geobutton.style.display = 'block';
		geobutton.index = 1;
		map.controls[google.maps.ControlPosition.TOP_RIGHT].push(geobutton);
	}
}       
