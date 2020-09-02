<?php
if(!file_exists("include/common.php"))
	header('Location: install.php');
require_once "include/common.php";

//apcu_clear_cache();

$uptime = $ojnAPI->getUptime();
if(!$uptime && !Message::IsSet())
{
	Message::AddError(__tr('OpenJabNab seems down... Please try again later.'));
	header('Location: index.php');
	exit();
}

if(isset($_GET['logid']) && isset($Infos['isAdmin'])) {
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
	    die('Connexion impossible : ' . mysqli_error());
	}
	$login = '';
	$res = mysqli_query($link, "SELECT username FROM account WHERE id=".$_GET['logid'].";");
	if($row = mysqli_fetch_assoc($res)) {
		$login = $row['username'];
	}
	mysqli_close($link);
	if($login) {
		$previous = $_SESSION['login'];
		$prev_tk  = $_SESSION['token'];
		$r = $ojnAPI->loginAsAccount($login, false);
		foreach(array('bunny', 'bunny_name', 'ztamp', 'ztamp_name', 'login', 'token', 'logged_from','token_from') as $key)
			if(isset($_SESSION[$key]))
				unset($_SESSION[$key]);

		if(preg_match("|[0-9a-f]{32}|", $r)) {
			apcu_delete(APC_PREFIX.'ojn_user_'.$previous);
			$_SESSION['login'] = $login;
			$_SESSION['logged_from'] = $previous;
			$_SESSION['token_from']  = $prev_tk;

			$ojnAPI->setToken($r);
			Message::AddSuccess(__tr('You are now connected as %1', $login));
		} else {
			Message::AddError(__tr('Authentification failed : %1', $r));
		}
		session_write_close();

	} else {
		Message::AddError(__tr('Can\'t find user with id : %1', $_GET['logid']));
	}
	header("Location: /index.php");
	exit;
}
if(isset($_GET['logout']) && !empty($_SESSION['login']))
 {
	 // Log out user
	apcu_delete(APC_PREFIX.'ojn_user_'.$_SESSION['login']);
	$ojnAPI->SetToken('');
	$clean = array('bunny', 'bunny_name', 'ztamp', 'ztamp_name', 'logged_from','token_from');
	if(!empty($_SESSION['logged_from']) && !empty($_SESSION['token_from']))
	{
		$_SESSION['login'] = $_SESSION['logged_from'];
		$_SESSION['token'] = $_SESSION['token_from'];
	}
	else
	{
		$clean[] = 'login';
		$clean[] = 'token';
	}
	foreach($clean as $key) {
		if(isset($_SESSION[$key])) { unset($_SESSION[$key]); }
	}
	//var_dump($_SESSION);
	header("Location: /index.php");
	exit();
}
if(isset($_POST['login']) && isset($_POST['password'])) {
	if(strlen(trim($_POST['login'])) && strlen(trim($_POST['password']))) {
	$r = $ojnAPI->loginAccount($_POST['login'], $_POST['password']);
	if($r === NULL)
	{
		Message::AddError(__tr('OpenJabNab seems down... Please try again later.'));;
		header('Location: /index.php');
		exit();
	}
// 0977d6dd0648fec2acbf1cec4f432d4a
	apcu_delete(APC_PREFIX.'ojn_user_'.$_POST['login']);
	if(preg_match("|[0-9a-f]{32}|", $r)) {
		$_SESSION['login'] = $_POST['login'];
    $real_client_ip = '-';
    if(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] != '127.0.0.1')
      $real_client_ip = $_SERVER['REMOTE_ADDR'];
		if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
			$real_client_ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
		} else {
			$headers = apache_request_headers();
			if(isset($headers["X-Forwarded-For"])) {
				$real_client_ip = $headers["X-Forwarded-For"];
			}
		}
		if(strlen($real_client_ip)) {
			$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
			if (!$link) {
			    die('Connexion impossible : ' . mysqli_error());
			}
			mysqli_query($link, "UPDATE account SET lastip='".$real_client_ip."' WHERE username=\"".addslashes($_SESSION['login'])."\";");
			mysqli_close($link);
			//Message::Addsuccess('IP saved : ' . $real_client_ip);
		} else {
			//Message::AddError('No IP');
		}

		$ojnAPI->setToken($r);
	} else {
		Message::AddError(__tr('Authentification failed : %1', $r));
	}
	session_write_close();
	}
	else
	{
		if(!strlen(trim($_POST['login']))) {
			Message::AddError(__tr('Login can\'t be empty'));;
		}
		if(!strlen(trim($_POST['password']))) {
			Message::AddError(__tr('Password can\'t be empty'));;
		}
	}
	header("Location: /index.php");
	exit;
}

if(isset($_SESSION['login']) && isset($_SESSION['logged_from'])) {
	Message::AddWarning(__tr('Your are connected as %1, from your admin account %2', $_SESSION['login'], $_SESSION['logged_from']).'.');
}
require_once('include/message.php');
if(isset($_SESSION['token'])) {
	require_once("index.logged.php");
} else {
	require_once("index.anonymous.php");
}
require_once("include/append.php");
?>
