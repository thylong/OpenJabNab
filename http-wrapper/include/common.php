<?php
global $tstart;
$mtime = microtime();
$mtime = explode(" ",$mtime);
$mtime = $mtime[1] + $mtime[0];
$tstart = $mtime;

require_once realpath(dirname(__FILE__)).'/config.php';

/*
$roles = array(
0 => __tr("User"),
1 => __tr("Administrator"),
2 => __tr("Premium"),
3 => __tr("VIP"),
4 => __tr("Demo"),
);
*/

if (!function_exists('apache_request_headers')) {
        function apache_request_headers() {
            foreach($_SERVER as $key=>$value) {
                if (substr($key,0,5)=="HTTP_") {
                    $key=str_replace(" ","-",ucwords(strtolower(str_replace("_"," ",substr($key,5)))));
                    $out[$key]=$value;
                }else{
                    $out[$key]=$value;
        }
            }
            return $out;
        }
}

function sectotime($up, $all = false)
{
	$ret = "";
	if($up >= 60)
	{
		$min = (int)($up / 60);
		$sec = $up - 60 * $min;
		if($min >= 60)
		{
			$hour = (int)($min / 60);
			$min = $min - 60 * $hour;
			if($hour >= 24)
			{
				$day = (int)($hour / 24);
				$hour = $hour - 24 * $day;
				$ret = $day."day ".str_pad($hour, 2, "0", STR_PAD_LEFT)."h ".str_pad($min, 2, "0", STR_PAD_LEFT)."min";
			}
			else
			{
				$ret = $hour."h ".str_pad($min, 2, "0", STR_PAD_LEFT)."min";
			}
		}
		else
		{
			$hour = 0;
			$ret = $min."min";
		}
		if($all) {
			$ret .= " " . str_pad($sec, 2, "0", STR_PAD_LEFT)."s";
		}
	}
	else
	{
		$ret = str_pad($up, 2, "0", STR_PAD_LEFT)."s";
	}
	return $ret;
}

function correctTime($str)
{
	$str = trim(preg_replace("|[\.;h]|", ":", $str));
	if(preg_match("^\d:\d{2}$", $str)) $str = "0".$str;
	$str = preg_replace("|(\d{2})(\d{2})|", "$1:$2", $str);
	return $str;
}
//ini_set("SMTP","192.168.0.111" );
//ini_set('sendmail_from', ADMIN_EMAIL);
global $Infos;

//session_start('openJabNab');
session_start();
require_once(ROOT_SITE.'include/class/api.class.php');
require_once(ROOT_SITE.'include/class/message.class.php');
require_once(ROOT_SITE.'include/class/template.class.php');
$ojnAPI = new ojnApi();
$ojnTemplate = new ojnTemplate($ojnAPI);
if(date("m") == 10 && date('d') >= 28) {
	$ojnTemplate->setCSS('ojn.halloween.css');
}
$Infos = array('token' => '','login'=>'guest','usename'=>'Guest','isAdmin'=>false,'isValid'=>true);
if(isset($_SESSION['token']) && !strpos($_SERVER['REQUEST_URI'],"logout")) {
    if(isset($_SESSION['token']) && isset($_SESSION['login'])) {
	if(!apcu_fetch(APC_PREFIX.'ojn_user_'.$_SESSION['login'])) {
        	$Infos = (array)$ojnAPI->getApiMapped('accounts/infos?user='.$_SESSION['login'].'&'.$ojnAPI->getToken());
		/* Fix to convert 'true' to true, etc */
		$bFix = array('isAdmin','isValid','isVip');
		foreach($bFix as $k)
			if(isset($Infos[$k]))
			$Infos[$k] = $Infos[$k] != 'false' ? true : false;
		
		$iFix = array('lastBanStart','lastBanEnd','abuseCount','loginCount');
		foreach($iFix as $k)
			if(empty($Infos[$k]))
			$Infos[$k] = 0;

//		echo '<pre>'; var_dump($Infos);

		$Infos['status'] = "User";
		$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		if (!$link) {
		    die('Connexion impossible : ' . mysqli_error());
		}

		$sql = "SELECT status FROM account WHERE username=\"".addslashes($_SESSION['login'])."\";";
		$res = mysqli_query($link, $sql);
		if($row = mysqli_fetch_assoc($res))
		{
			$Infos['status'] = $row['status'];
		}

		//var_dump($Infos);
		mysqli_close($link);
		apcu_store(APC_PREFIX.'ojn_user_'.$_SESSION['login'], $Infos, 60);
	}
	else
		$Infos = apcu_fetch(APC_PREFIX.'ojn_user_'.$_SESSION['login']);
	}

	if(!isset($Infos['isValid']) || ( !$Infos['isValid']))
		header('Location: /index.php?logout');
}
//var_dump($_SERVER);



$translations = array();
if(!isset($Infos['language']))
{
	if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
	{
		$lng = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		if(preg_match("|([a-z]{2})|", $lng, $match))
		{
			$Infos['language'] = $match[1];
		}
		else
			$Infos['language'] = 'fr';
	}
	else
		$Infos['language'] = 'fr';
}
//apcu_delete(APC_PREFIX.'ojn_tr_'.$Infos['language']);
if(!($translations = apcu_fetch(APC_PREFIX.'ojn_tr_'.$Infos['language']))) {
	if(file_exists(ROOT_SITE.'cache/translations.'.$Infos['language'].'.php')) {
		require_once(ROOT_SITE.'cache/translations.'.$Infos['language'].'.php');
		apcu_store(APC_PREFIX.'ojn_tr_'.$Infos['language'], $translations, 86400);
	}
}
$ojnTemplate->setUInfos($Infos);



require_once "Mail.php";
require_once "Mail/mime.php";
function sendMail($body, $subject, $to = MAIL_SENDER, $from = MAIL_SENDER) {
	$headers = array ('From' => $from,
	   'To' => $to,
	   'Date' => date("r"),
	   'Subject' => $subject);

	$mime = new Mail_mime();
	$mime->setTxtBody($body);
	$body = $mime->get(array('text_charset' => 'utf-8'));
	$headers = $mime->headers($headers);

	$smtp = Mail::factory('smtp', array ('host' => MAIL_SERVER, 'username'=> MAIL_USER, 'password'=> MAIL_PASS,'port'=>465,'auth'=>'PLAIN'));

	$mail = $smtp->send($to.', '.MAIL_SENDER, $headers, $body);
	
	//var_dump($mail->getMessage());

  return !PEAR::isError($mail);
}

function getTranslates($nom)
{
	$translates = array();
	$success = false;
	if(strlen($nom)) {
		$translates = apcu_fetch(APC_PREFIX.'ojn_translate_'.$nom, $success);
		if(!$success) {
			$translates = array();
			$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
			if (!$link) {
			    die('Connexion impossible : ' . mysqli_error());
			}

			$sql = "SELECT language FROM translator WHERE login='".addslashes($nom)."'";
			$res = mysqli_query($link, $sql);
			while($row = mysqli_fetch_assoc($res))
				$translates[] = $row['language'];

			mysqli_close($link);
			apcu_store(APC_PREFIX.'ojn_translate_'.$nom, $translates, 86400);
		}
	}
	return $translates;
}

function getBetas()
{
	//apcu_delete(APC_PREFIX.'ojn_beta');
	$success = false;
	$betas = apcu_fetch(APC_PREFIX.'ojn_betas', $success);
	if(!$success) {
		$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		if (!$link) {
		    die('Connexion impossible : ' . mysqli_error());
		}

		$betas = array();
		$sql = "SELECT * FROM beta;";
		$res = mysqli_query($link, $sql);
        if($res)
            while($row = mysqli_fetch_assoc($res))
            {
                if(!isset($betas[$row['plugin']]))
                    $betas[$row['plugin']] = array();
                $betas[$row['plugin']] = $row['mac'];
            }

		mysqli_close($link);
		apcu_store(APC_PREFIX.'ojn_betas', $betas, 7200);
	}
	return $betas;
}

function isBeta($mac, $plugin)
{
	$betas = getBetas();
	return isset($betas[$plugin]) && isset($betas[$plugin][$mac]);
}

function getTesters()
{
	//apcu_delete(APC_PREFIX.'ojn_testers');
	$success = false;
	$testers = apcu_fetch(APC_PREFIX.'ojn_testers', $success);
	if(!$success) {
		$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		if (!$link) {
		    die('Connexion impossible : ' . mysqli_error());
		}

		$testers = array();
		$sql = "SELECT * FROM tester;";
		$res = mysqli_query($link, $sql);
		while($row = mysqli_fetch_assoc($res))
		{
			if(!isset($testers[$row['plugin']]))
				$testers[$row['plugin']] = array();
			$testers[$row['plugin']] = $row['username'];
		}
		mysqli_close($link);
		apcu_store(APC_PREFIX.'ojn_testers', $testers, 7200);
	}
	return $testers;
}

function isTester($username, $plugin)
{
	$testers = getTesters();
	return isset($testers[$plugin]) && isset($testers[$plugin][$username]);
}

function bunnyVersion($mac)
{
	$v1 = array("00039D402", "00305400", "0060B32E", "00904B");
	foreach($v1 as $m) {
		if(preg_match('/^'.$m.'/i', $mac)) {
			return 1;
		}
	}
	return 2;
}

ob_start(array($ojnTemplate,'display'));
?>
