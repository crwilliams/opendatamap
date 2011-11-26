<?
error_reporting(0);
include 'functions.inc.php';
outputHeader("Register an account", "", "GENERIC");
$post = false;
if($_SERVER['REQUEST_METHOD'] == 'POST')
{
	$post = true;
	$errors = array();
	if(trim($_POST['username']) == "")
	{
		$errors[] = 'Username not set';
		$bad['username'] = true;
	}
	if(trim($_POST['email']) == "")
	{
		$errors[] = 'Email address not set';
		$bad['email'] = true;
	}
	if(trim($_POST['password1']) == "")
	{
		$errors[] = 'Password not set';
		$bad['password1'] = true;
	}
	elseif($_POST['password1'] != $_POST['password2'])
	{
		$errors[] = 'Passwords do not match';
		$bad['password2'] = true;
	}
	if(count($errors) > 0)
	{
		echo '<div style="background-color:#FF9999; margin:10px; padding:10px; text-align:center">';
		foreach($errors as $error)
		{
			echo $error.'<br />';
		}
		echo '</div>';
	}
}
if(!$post || count($errors) > 0)
{
?>
<form action='register' method='post'>
	<table>
		<tr class='comp <?= $post && $bad['username'] ? "bad" : "" ?>'><td><label for='username'>Username:</label></td><td><input id='username' name='username' style='width:10em' value='<?= $_POST['username'] ?>' /></td></tr>
		<tr class='comp <?= $post && $bad['email'] ? "bad" : "" ?>'><td><label for='email'>Email Address:</label></td><td><input id='email' name='email' style='width:20em' value='<?= $_POST['email'] ?>' /></td></tr>
		<tr><td colspan='2'><hr /></td></tr>
		<tr class='comp <?= $post && $bad['password1'] ? "bad" : "" ?>'><td><label for='password1'>Password:</label></td><td><input type='password' id='password1' name='password1' style='width:10em' value='<?= $_POST['password1'] ?>' /></td></tr>
		<tr class='comp <?= $post && $bad['password2'] ? "bad" : "" ?>'><td><label for='password2'>Confirm Password:</label></td><td><input type='password' id='password2' name='password2' style='width:10em' value='<?= $_POST['password2'] ?>' /></td></tr>
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
	$params[] = "'".mysql_real_escape_string($_POST['username'])."'";
	$params[] = "'".mysql_real_escape_string($_POST['email'])."'";
	$params[] = "'".md5($_POST['password1'])."'";
	$q = "INSERT INTO users VALUES (".implode(',', $params).")";
	$res = mysql_query($q);
	if(!$res)
	{
		echo 'Failed to register user.';
	}
	else
	{
		$_SESSION['username'] = $_POST['username'];
		echo "You are now logged in as ".$_SESSION['username'].".  <a href='new'>Create a new map</a>.";
	}
}
outputFooter();
?>

