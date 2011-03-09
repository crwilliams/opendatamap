function geoloc()
{
//    document.getElementById('workarea').innerHTML = '<div style="text-decoration:blink;font-size:200%">Geo-locating</div><div> this might take a little while...</div>';
    document.getElementById('geobutton').style.display = 'none';
    navigator.geolocation.getCurrentPosition(
       function(position) {        
             if( position.coords.accuracy < 5000 )
             {
                 window.location = "?lat=" + position.coords.latitude + "&long=" + position.coords.longitude+"&acc=" + position.coords.accuracy;
	     }
             else
	     {
//                document.getElementById('workarea').innerHTML = 'Geo location wildly inaccurate ('+ position.coords.accuracy+" meters)"; 
             }
             document.getElementById('geobutton').style.display = 'block';
       },        
       function(e) {
//             document.getElementById('workarea').innerHTML = 'Geo location failed'; 
             document.getElementById('geobutton').style.display = 'block';
       }
    );                                                                  
}
if (navigator.geolocation) {
	document.getElementById('geobutton').style.display = 'block';
	//document.write( "<div id='geobutton' class='option'><a style='font-size:200%;' href='javascript:geoloc()'>Get my location</a></div>" );
}       
