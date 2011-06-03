<html>
<head>
	<link rel="stylesheet" href="../css/reset.css" type="text/css">
	<link rel="stylesheet" href="../css/index.css" type="text/css">
	<link rel="stylesheet" href="../css/credits.css" type="text/css">
	<title>opendatamap iconset | listing</title>
</head>
<body>
<? include_once '../googleanalytics.php'; ?>
<h1>opendatamap iconset</h1>
<h2>Listing</h2>
<p>Below is a listing of all icons in the <a href='.'>opendatamap iconset</a>.</p>
<?

$handle = opendir('../img/icon/');

while (false !== ($file = readdir($handle))) {
	if(substr($file, -4, 4) != '.png')
		continue;
	$filename = substr($file, 0, -4);
	echo "<img src='../img/icon/$file' alt='$filename icon' title='$filename' />";
}
?>
</body>
</html>
