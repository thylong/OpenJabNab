<?php
require_once 'common.php';

$link = getSQL();
$ojnAPI = getAPI();

$options  = array('http' => array('user_agent' => 'OJN Cron'));
$context  = stream_context_create($options);

$colors = $ojnAPI->getApiMapped('plugin/stats/getcolors?'.$ojnAPI->getToken());
$sql = "INSERT INTO stats_color SET date=NOW()";
foreach($colors as $c => $nb)
	$sql .= ", ".preg_replace("|none|", "black", $c)."=".$nb;
//var_dump($sql);
mysqli_query($link, $sql);

$sleep = $ojnAPI->getApiMapped('plugin/stats/getbunniesstatus?'.$ojnAPI->getToken());
$sql = "INSERT INTO stats_sleep SET date=NOW()";
foreach($sleep as $st => $nb)
	$sql .= ", ".$st."=".$nb;

//var_dump($sql);
mysqli_query($link, $sql);
?>
