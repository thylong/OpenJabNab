<?php

if(isset($_SESSION['login']))
{
	if(isset($Infos['email']) && trim($Infos['email']) == '')
	{
		Message::AddError(__tr("Your profile doesn't have an email. Please complete your personal informations.") . " &nbsp; <a href='account.php'>".__tr("Your account")."</a>");
		$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		if (!$link) {
		    die('Connexion impossible : ' . mysqli_error());
		}
//		var_dump($_SERVER);
		$sql = "INSERT INTO `no_mail` SET date=NOW(), account=\"".addslashes($_SESSION['login'])."\", url=\"".$_SERVER['PHP_SELF']."\";";
		$res = mysqli_query($link, $sql);
		mysqli_close($link);
	}
	else if(isset($Infos['email']) && !preg_match("|^.+@.+\.\w{1,4}|", trim($Infos['email'])))
	{
		Message::AddWarning(__tr("Your email in your profile seems to be invalid. Please correct it."));
		$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		if (!$link) {
		    die('Connexion impossible : ' . mysqli_error());
		}
		$sql = "INSERT INTO `bad_mail` SET date=NOW(), account=\"".addslashes($_SESSION['login'])."\", url=\"".$_SERVER['PHP_SELF']."\";";
		$res = mysqli_query($link, $sql);
		mysqli_close($link);
	}
}
if(isset($_SESSION['Message']) ) {
	foreach($_SESSION['Message'] as $type => $array)
	{
		if(strtolower($type) == 'error')
			$type = 'danger';

		asort($array);
		foreach($array as $id => $msg)
		{
?>
<div class="alert alert-<?php echo strtolower($type) ?>">
  <a class="close" data-dismiss="alert" href="#">×</a>
  <?php echo __tr($msg); ?>
</div>
<?php
		}
	}
	Message::Clear();
}
