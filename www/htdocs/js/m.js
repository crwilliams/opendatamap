var selectGenericOffering = function(str) {
	$('#specific').get(0).value = "";
	$('#q').get(0).value = '^'+str+'$';
}

var selectSpecificOffering = function(str) {
	$('#generic').get(0).value = "";
	$('#q').get(0).value = '^'+str+'$';
}

var selectSite = function(uri) {
	$('#building-name').get(0).value = "";
	$('#building-number').get(0).value = "";
	$('#loc').get(0).value = 'uri='+uri;
}

var selectBuilding = function(uri) {
	$('#site').get(0).value = "";
	$('#building-name').get(0).value = uri;
	$('#building-number').get(0).value = uri;
	$('#loc').get(0).value = 'uri='+uri;
}

var geoloc = function() {
	_gaq.push(['_trackEvent', 'Geolocation', 'Request']);
	$('#geoinfo').get(0).innerHTML = 'Trying to obtain location...';
	navigator.geolocation.getCurrentPosition(
		function(position) {        
			if( position.coords.accuracy < 5000 ) {
				$('#loc').get(0).value = 'lat='+position.coords.latitude+'&long='+position.coords.longitude;
				$('#geoinfo').get(0).innerHTML = 'Location obtained';
				$('#building-name').get(0).value = "";
				$('#building-number').get(0).value = "";
				$('#site').get(0).value = "";
			} else {
				$('#geoinfo').get(0).innerHTML = 'Sorry, location wildly inaccurate ('+ position.coords.accuracy+' meters)'; 
			}
			_gaq.push(['_trackEvent', 'Geolocation', 'Response', null, position.coords.accuracy]);
		},        
		function(e) {
			$('#geoinfo').get(0).innerHTML = 'Sorry, geo location failed';
			_gaq.push(['_trackEvent', 'Geolocation', 'Failed']);
		}
	);                                                                  
}
