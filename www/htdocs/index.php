<!DOCTYPE html>
<?php
error_reporting(0);
?>
<html>
<head>
<title>University of Southampton Open Linked Data Map</title>
<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
<script src="http://www.google.com/jsapi"></script>
<script type="text/javascript" src="resources/jquery-1.5.min.js"></script>
<script type="text/javascript" src="resources/geoloc.js"></script>
<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false">
<script src="markerclusterer.js" type="text/javascript"></script>
<link href='http://fonts.googleapis.com/css?family=Ubuntu' rel='stylesheet' type='text/css'>
<link rel="stylesheet" href="css/reset.css" type="text/css">
<link rel="stylesheet" href="css/index.css" type="text/css">
<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-20609696-4']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();
</script>
</head>
<body onload="initialize()">
<div id="spinner"><img src="resources/ajax-loader.gif"></div>
<div id="map_canvas"></div>
<img id="geobutton" src='resources/geoloc.png' onclick="geoloc()" alt="Geo-locate me!" title="Geo-locate me!" />
<form action="" onsubmit="return false">
   <input id="inputbox" style='width:200px' value='<?php echo $_GET['q'] ?>'>
   <img id="clear" src='http://www.picol.org/images/icons/files/png/16/search_16.png' onclick="document.getElementById('inputbox').value=''; updateFunc();" alt="Clear search" title="Clear search">
   </input>
<ul id="list"></ul>
</form>
<div id="credits"><?php include 'credits.php' ?></div>
</body>
<script type="text/javascript" src="alldata.php?lat=<?php echo $_GET['lat'] ?>&long=<?php echo $_GET['long'] ?>&zoom=<?php echo $_GET['zoom'] ?>">
</script>
</html>
