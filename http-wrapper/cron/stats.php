<?php
require_once 'common.php';


$link = getSQL();
$options  = array('http' => array('user_agent' => 'OJN Cron'));
$context  = stream_context_create($options);

$content = file_get_contents(ROOT_WWW_API.'plugin/stats/getcolors', false, $context);
//var_dump($content);
$colors = simplexml_load_string($content);
$colors = $colors->list->item;
$sql = "INSERT INTO stats_color SET date=NOW()";
foreach($colors as $color)
{
	//var_dump($color);
	$sql .= ", ".preg_replace("|none|", "black", $color->key)."=".$color->value;
}
//var_dump($sql);
mysqli_query($link, $sql);
$sleep = simplexml_load_string(file_get_contents(ROOT_WWW_API.'plugin/stats/getbunniesstatus', false, $context));
$sleep = $sleep->list->item;
$sql = "INSERT INTO stats_sleep SET date=NOW()";
foreach($sleep as $sl)
{
	//var_dump($sl);
	$sql .= ", ".$sl->key."=".$sl->value;
}
//var_dump($sql);
mysqli_query($link, $sql);

mysqli_close($link);
?>
