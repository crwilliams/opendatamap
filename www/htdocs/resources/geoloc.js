function geoloc()
{
	_gaq.push(['_trackEvent', 'Geolocation', 'Request']);
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
			_gaq.push(['_trackEvent', 'Geolocation', 'Response', null, position.coords.accuracy]);
		},        
		function(e) {
			alert('Sorry, geo location failed'); 
			_gaq.push(['_trackEvent', 'Geolocation', 'Failed']);
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
