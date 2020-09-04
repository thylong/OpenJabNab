<?php
ob_end_flush();
if(!empty($Infos['isAdmin']))
{
	echo '<pre><hr /><h4>API Calls</h4>';
	$ojnAPI->getLog();
	echo "<hr /><h4>GET</h4>";
	var_dump($_GET);
	echo "<hr /><h4>POST</h4>";
	var_dump($_POST);
	echo "<hr /><h4>Infos</h4>";
	var_dump($Infos);
	echo "<hr /><h4>SESSION</h4>";
	var_dump($_SESSION);
	echo "<hr /><h4>SERVER</h4>";
	var_dump($_SERVER);
	echo "<hr /><h4>Headers</h4>";
	$headers = apache_request_headers();
	var_dump($headers);
	echo '</pre>';
}
?>
