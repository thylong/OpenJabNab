<?php
ob_end_flush();
if(!empty($Infos['isAdmin']))
{
	echo '<hr /><pre>';
	$ojnAPI->getLog();
	echo "</pre><hr /><pre>";
	var_dump($_SERVER);
	echo "</pre><hr /><pre>";
	$headers = apache_request_headers();
	var_dump($headers);
	echo '</pre>';
}
?>
