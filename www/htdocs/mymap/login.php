<?
error_reporting(0);
session_start();
if($_SERVER['REQUEST_METHOD'] == 'POST')
{
	require_once('/home/opendatamap/mysql.inc.php');
	$params[] = mysql_real_escape_string($_POST['username']);
	$params[] = md5($_POST['password']);
	$q = 'SELECT * FROM users WHERE username = \''.$params[0].'\' AND password = \''.$params[1].'\'';
	$res = mysql_query($q);
	$row = mysql_fetch_assoc($res);
	if($row)
	{
		$_SESSION['username'] = $row['username'];
		if(isset($_SESSION['referer']))
			header('Location: '.$_SESSION['referer']);
		else
			header('Location: /admin');
	}
	else
	{
		echo 'Incorrect credentials, please try again';
	}
}
if(isset($_SESSION['username']))
{
	if(isset($_SESSION['referer']))
		header('Location: '.$_SESSION['referer']);
	else
		header('Location: /admin');
	die();
}
include 'functions.inc.php';
outputHeader("Log in", "", "GENERIC", true, false);
?>
<form action='login' method='post'>
	<table>
		<tr class='comp'><td><label for='username'>Username:</label></td><td><input id='username' name='username' style='width:10em' /></td></tr>
		<tr class='comp'><td><label for='password'>Password:</label></td><td><input type='password' id='password' name='password' style='width:10em' /></td></tr>
		<tr><td /><td><input type='submit' /></td></tr>
	</table>
</form>
<a href='register'>Register for an account</a>
<?
outputFooter();
?>

