<?php
ob_end_flush();
if(isset($_SESSION['login']) && $_SESSION['login'] == 'Pixel')
{
	$ojnAPI->getLog();
	echo "<hr />";
	var_dump($_SERVER);
	echo "<hr />";
	$headers = apache_request_headers();
	var_dump($headers);
}
?>
