<?php
require_once "include/common.php";
if(!isset($_SESSION['token']) || !$Infos['isAdmin'])
	header('Location: index.php');
ob_end_clean();
$max = isset($_GET['max']) ? min(100, $_GET['max']) : 25;
if(isset($_GET['bsearch']) || isset($_GET['bpage'])) {
	$page = isset($_GET['bpage']) ? $_GET['bpage'] + 0 : 0;
	$cbunnies = $ojnAPI->getListOfAllConnectedBunnies(false);
	$bunnies = $ojnAPI->getListOfAllBunnies(false);
	$b = array();
	foreach($bunnies as $mac => $name)
	{
		if(!isset($_GET['bsearch']) || preg_match("|".$_GET['bsearch']."|i", $name) || preg_match("|".$_GET['bsearch']."|i", $mac))
			$b[$mac] = array('name' => $name, 'connected' => isset($cbunnies[$mac]) ? true : false);
	}
	$ret = array('page' => $page, 'pages' => (int)(count($b) / $max) + 1 , 'total' => count($b), 'max' => $max);
	if(count($b) > $max)
		$b = array_slice($b, $max * $page, $max);
	else
		$ret['page'] = 0;
	$ret['data'] = $b;
	echo json_encode($ret);
}
if(isset($_GET['zsearch']) || isset($_GET['zpage'])) {
	$page = isset($_GET['zpage']) ? $_GET['zpage'] + 0 : 0;
	$ztamps = $ojnAPI->getListOfAllZtamps(false);
	if(isset($_GET['zsearch']))
	{
		$z = array();
		foreach($ztamps as $mac => $name)
			if(preg_match("|".$_GET['zsearch']."|i", $name) || preg_match("|".$_GET['zsearch']."|i", $mac))
				$z[$mac] = $name;
	}
	else
	{
		$z = $ztamps;
	}
	$ret = array('page' => $page, 'pages' => (int)(count($z) / $max) + 1 , 'total' => count($z), 'max' => $max);
	if(count($z) > $max)
		$z = array_slice($z, $max * $page, $max);
	else
		$ret['page'] = 0;
	$ret['data'] = $z;
	echo json_encode($ret);
}
if(isset($_GET['asearch']) || isset($_GET['apage'])) {
	$page = isset($_GET['apage']) ? $_GET['apage'] + 0 : 0;
	$accounts = $ojnAPI->getListOfAllAccounts(false);
	$online = $ojnAPI->getListOfAllConnectedAccounts(false);
	$admins = $ojnAPI->getApiList("accounts/GetListOfAdmins?".$ojnAPI->getToken());
	$a = array();
	foreach($accounts as $login => $name)
	{
		if(!isset($_GET['asearch']) || preg_match("|".$_GET['asearch']."|i", $name) || preg_match("|".$_GET['asearch']."|i", $login))
			$a[$login] = array('name' => $name, 'connected' => isset($online[$login]) ? true : false, 'admin' => in_array($login, $admins) ? true : false);
	}
	$ret = array('page' => $page, 'pages' => (int)(count($a) / $max) + 1 , 'total' => count($a), 'max' => $max);
	if(count($a) > $max)
		$a = array_slice($a, $max * $page, $max);
	else
		$ret['page'] = 0;
	$ret['data'] = $a;
	echo json_encode($ret);
}
