<?php
require_once "include/common.php";
ob_end_clean();
if(!isset($_SESSION['token']))
header('Location: index.php');
if(isset($_GET['b']))
	define("BUNNY_API", "bunny/" . $_GET['b']);
else
header('Location: index.php');

// Expert
if(isset($_GET['reboot'])) {
	$_SESSION['tab'] = 'bunny_expert';
	Message::AddFromApi($ojnAPI->getApiString(BUNNY_API."/disconnect?reboot=1&".$ojnAPI->getToken()));
}

if(isset($_GET['disconnect'])) {
	$_SESSION['tab'] = 'bunny_expert';
	Message::AddFromApi($ojnAPI->getApiString(BUNNY_API."/disconnect?".$ojnAPI->getToken()));
}

if(isset($_GET['reconf'])) {
	$_SESSION['tab'] = 'bunny_expert';
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_GET['b']."/locate/server?action=change&".$ojnAPI->getToken()));
}

include('include/message.php');
