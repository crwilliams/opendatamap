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
		<tr class='comp <?= $post && $bad['mapname'] ? "bad" : "" ?>'><td><label for='mapname'>Map short name:</label></td><td><input id='mapname' name='mapname' style='width:20em' value='<?= $_POST['mapname'] ?>' /></td></tr>
		<tr><td colspan='2'><hr /></td></tr>
		<tr class='comp <?= $post && $bad['title'] ? "bad" : "" ?>'><td><label for='title'>Map title:</label></td><td><input id='title' name='title' style='width:60em' value='<?= $_POST['title'] ?>' /></td></tr>
		<tr class='comp <?= $post && $bad['source'] ? "bad" : "" ?>'><td><label for='source'>Map source:</label></td><td><input id='source' name='source' style='width:60em' value='<?= $_POST['source'] ?>' /></td></tr>
		<tr class='comp <?= $post && $bad['base'] ? "bad" : "" ?>'><td><label for='base'>Base URI:</label></td><td><input id='base' name='base' style='width:60em' value='<?= $_POST['base'] ?>' /></td></tr>
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

