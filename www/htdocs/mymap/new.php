<?
error_reporting(0);
include 'functions.inc.php';
outputHeader("Create a new map", "", "GENERIC", true, true);
$post = false;
if($_SERVER['REQUEST_METHOD'] == 'POST')
{
	$post = true;
	$errors = array();
	if(trim($_POST['mapname']) == "")
	{
		$errors[] = 'Map short name not set';
		$bad['mapname'] = true;
	}
	if(trim($_POST['title']) == "")
	{
		$errors[] = 'Map title not set';
		$bad['title'] = true;
	}
	if(trim($_POST['source']) == "")
	{
		$errors[] = 'Map source not set';
		$bad['source'] = true;
	}
	foreach($errors as $error)
	{
		echo $error.'<br />';
	}
}
if(!$post || count($errors) > 0)
{
?>
<form action='new' method='post'>
	<table>
		<tr class='comp <?= $post && $bad['mapname'] ? "bad" : "" ?>'><td class='label'><label for='mapname'>Map URL:</label></td><td class='field'>http://<?= $_SERVER['SERVER_NAME'] ?>/mymap/<?= $_SESSION['username'] ?>/<input id='mapname' name='mapname' style='width:9em' value='<?= $_POST['mapname'] ?>' /></td><td class='desc'>This is the URL that the RDF for the map will become available at.</tr>
		<tr><td colspan='3'><hr /></td></tr>
		<tr class='comp <?= $post && $bad['title'] ? "bad" : "" ?>'><td class='label'><label for='title'>Map title:</label></td><td><input id='title' name='title' style='width:35em' value='<?= $_POST['title'] ?>' /></td><td class='desc'>This is the title of your map.</td></tr>
		<tr class='comp <?= $post && $bad['source'] ? "bad" : "" ?>'><td class='label'><label for='source'>Map source:</label></td><td><input id='source' name='source' style='width:35em' value='<?= $_POST['source'] ?>' /></td><td class='desc'>This is the location (URL) of the source CSV file for your map.  The CSV file should begin with a header row identifying the following columns (case insensitive):
			<dl>
				<dt>code</dt>
				<dd>Unique code for the location (will be appended to the <i>base URI</i> below to create the location's URI).</dd>
				<dt>name</dt>
				<dd>Name of the location.</dd>
				<dt>icon</dt>
				<dd>URL of the icon used to represent the location on the map.</dd>
				<dt>latitude</dt>
				<dd>(optional) The latitude of the location.</dd>
				<dt>longitude</dt>
				<dd>(optional) The longitude of the location.</dd>
			</dl>
			Rows which begin with the string '*COMMENT' will be treated as comment lines and not processed further.
		</td></tr>
		<tr class='comp <?= $post && $bad['base'] ? "bad" : "" ?>'><td class='label'><label for='base'>Base URI:</label></td><td><input id='base' name='base' style='width:35em' value='<?= $_POST['base'] ?>' /></td><td class='desc'>This is the base URI that is used as a prefix to the unique codes in the source file in order to generate a full URI for each location.</td></tr>
		<tr><td /><td><input type='submit' /></td></tr>
	</table>
</form>
<?
}
else
{
	session_start();
	require_once('/home/opendatamap/mysql.inc.php');
	$params = array();
	$params[] = "'".mysql_real_escape_string($_SESSION['username'])."'";
	$params[] = "'".mysql_real_escape_string($_POST['mapname'])."'";
	$params[] = "'".mysql_real_escape_string($_POST['title'])."'";
	$params[] = "'".mysql_real_escape_string($_POST['source'])."'";
	$params[] = "'".mysql_real_escape_string($_POST['base'])."'";
	$q = "INSERT INTO maps VALUES (".implode(',', $params).")";
	$res = mysql_query($q);
	if(!$res)
	{
		echo 'Failed to create map.';
	}
	else
	{
	//	$_SESSION['username'] = $_POST['username'];
		echo "You have successfully created a new map.  <a href='".$_SESSION['username']."/".$_POST['mapname']."/edit'>Edit map</a>.";
	}
}
outputFooter();
?>

