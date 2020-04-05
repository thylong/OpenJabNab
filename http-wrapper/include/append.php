<?php
ob_end_flush();
if($Infos['isAdmin'])
{
	echo '<pre><hr />';
	$ojnAPI->getLog();
	echo "<hr />";
	var_dump($_SERVER);
	echo "<hr />";
	$headers = apache_request_headers();
	var_dump($headers);
	echo '</pre>';
}
?>
