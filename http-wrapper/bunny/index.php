<?php
require_once "../include/common.php";
$ojnTemplate->setTitle(__tr('Bunnies'));
if(!isset($_SESSION['token']))
header('Location: /index.php');
if(isset($_SESSION['bunny']))
	define("BUNNY_API", "bunny/" . $_SESSION['bunny']);

//apcu_delete(APC_PREFIX.'ojn_plugins_infos_'.$Infos['language']);
if(!($plugins = apcu_fetch(APC_PREFIX.'ojn_plugins_infos_'.$Infos['language']))) {
	$xml = $ojnAPI->getApiRaw('plugins/getPlugins?lng='.$Infos['language']);
	$list = simplexml_load_string($xml);
	$plugins = array();
	foreach($list->plugins->plugin as $plugin)
	{
		$p = array();
		$p['name'] = (string)$plugin;
		$attrs = (array)$plugin->attributes();
		foreach($attrs['@attributes'] as $key => $value)
		{
			$p[$key] = trim($value);
		}
		$p['actif'] = false;

		if(!($p['required'] || $p['system']))
			$plugins[$p['id']] = $p;
	}
	//ksort($list);

	$plugins_db = array();
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
	    die('Connexion impossible : ' . mysqli_error());
	}

	$sql = "SELECT * FROM plugins;";
	$res = mysqli_query($link, $sql);
	while($row = mysqli_fetch_assoc($res))
	{
		$plugins_db[$row['name']] = $row;
	}

	foreach($plugins as $key => $plugin) {
		if(isset($plugins_db[$key])) {
			if($plugins_db[$key]['version'] != $plugin['version']) {
				$old = preg_split('/\./', $plugins_db[$key]['version']);
				$new = preg_split('/\./', $plugins_db[$key]['version']);
				if($old[0] + 0 != $new[0] + 0) {
					$changed = 3;
				} else if($old[1] + 0 != $new[1] + 0) {
					$changed = 2;
				} else {
					$changed = 1;
				}
				$sql = "UPDATE plugins SET changed='".$changed."', version='".$plugin['version']."', updated_at=NOW() WHERE name='".$plugin['id']."';";
				$res = mysqli_query($link, $sql);
				$plugins_db[$key]['version'] = $plugin['version'];
				$plugins_db[$key]['updated_at'] = date('Y-m-d H:i:s');
			}
		} else {
			$sql = "INSERT INTO plugins SET name='".$plugin['id']."', changed=1, version='".$plugin['version']."', premium='".$plugin['premium']."', installed_at=NOW(), updated_at=NOW();";
			$res = mysqli_query($link, $sql);
			$plugins_db[$key] = array('changed' => 1, 'version' => $plugin['version'], 'updated_at' => date('Y-m-d H:i:s'), 'installed_at' => date('Y-m-d H:i:s'), 'display' => 0);
		}
		$plugins[$key]['new'] = time() - strtotime($plugins_db[$key]['installed_at']) < 3600 * 24 * 15 ? true : false;
		$plugins[$key]['updated'] = time() - strtotime($plugins_db[$key]['updated_at']) < round(3600 * 24 * 3 * pow($plugins_db[$key]['changed'], 1.3)) ? true : false;
		$plugins[$key]['display'] = $plugins_db[$key]['display'];
	}

	mysqli_close($link);
	apcu_store(APC_PREFIX.'ojn_plugins_info_'.$Infos['language'], $plugins, 600);
}

if(isset($_GET['b']) && isset($_GET['silent']))
{
	if($_GET['b'] == $_GET['silent'])
	{
		Message::AddFromApi($ojnAPI->getApiString("bunny/" . $_GET['b'] . "/status/status?action=silent&".$ojnAPI->getToken()));
		header("Location: index.php");
		exit;
	}
}
if(isset($_GET['rawconf'])) {
	$_SESSION['tab'] = 'bunny_debug';
	$_SESSION['rawconf'] = 1;
	header("Location: /bunny/index.php");
	exit;
}
if(isset($_GET['b']) && isset($_GET['bSilent'])) {
	$_SESSION['tab'] = 'bunny_base';
	$silent = 1;
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
	    die('Connexion impossible : ' . mysqli_error());
	}
	$sql = "INSERT INTO silent SET active='".$silent."', mac='".$_GET['b']."' ON DUPLICATE KEY UPDATE active='".$silent."'";
	$res = mysqli_query($link, $sql);
	mysqli_close($link);
	if($silent == 1)
	{
		if(!file_exists('/home/prod/OpenJabNab/http-wrapper/ojn_local/bootcode/bootcode.'.$_GET['b'])) {
			symlink('/home/prod/OpenJabNab/http-wrapper/ojn_local/bootcode/bootcode.silent', '/home/prod/OpenJabNab/http-wrapper/ojn_local/bootcode/bootcode.'.$_GET['b']);
		}
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_GET['b']."/disconnect?reboot=1&".$ojnAPI->getToken()));
	}
	header("Location: index.php");
	exit;
}
$reload = false;
if(isset($_POST['migration_bunny'])) {
	$_SESSION['migration_bunny'] = $_POST['migration_bunny'];
	$_SESSION['tab'] = 'bunny_migrate';
	$reload = true;
}
if(isset($_GET['dev']))
{
	$_SESSION['tab'] = 'bunny_plugins';
	$id = Message::AddWarning("<b>".__tr("This plugin is currently not available")."</b>");
	$reload = true;
}
if(isset($_GET['premium']))
{
	$_SESSION['tab'] = 'bunny_plugins';
	$id = Message::AddWarning("<b>".__tr("This plugin is only available to Premium users, or VIP")."</b>");
	Message::AddWarning("&bull; ".__tr("Premium status will be available soon"), $id);
	Message::AddWarning("&bull; ".__tr("VIP status is for everyone who already made a donation"), $id);
	Message::AddWarning("&bull; ".__tr("You could also try all premium plugins for a limited time")." <a href='demo.php'>".__tr("Try plugins")."</a>", $id);
	$reload = true;
}
if(!empty($_GET['b'])) {
	$_SESSION['tab'] = 'bunny_base';
	if($_GET['b'] == "clear")
	{
		unset($_SESSION['bunny']);
		unset($_SESSION['bunny_name']);
	}
	else
	{
		$_SESSION['bunny'] = $_GET['b'];
		$bunnies = $ojnAPI->getListOfBunnies(false);
		if(isset($Infos['isAdmin']) && $Infos['isAdmin'])
			$bunnies = $ojnAPI->getApiMapped("bunnies/getListofAllBunnies?".$ojnAPI->getToken());
		$_SESSION['bunny_name'] = !empty($bunnies[$_GET['b']]) ? $bunnies[$_GET['b']] : '';
	}
	header("Location: /bunny/index.php");
	exit();
}

// Base setup
if(!empty($_GET['lng'])) {
	$_SESSION['tab'] = 'bunny_language';
	Message::AddFromApi($ojnAPI->getApiString(BUNNY_API."/setlanguage?lng=".$_GET['lng']."&".$ojnAPI->getToken()));
	$reload = true;
}
if(!empty($_GET['bunny_name'])) {
	$_SESSION['tab'] = 'bunny_base';
	Message::AddFromApi($ojnAPI->getApiString(BUNNY_API."/setBunnyName?name=".urlencode($_GET['bunny_name'])."&".$ojnAPI->getToken()));
	$_SESSION['bunny_name'] = $_GET['bunny_name'];
	$reload = true;
}
if(isset($_GET['voice']) && $_GET['voice'] != "") {
	$_SESSION['tab'] = 'bunny_language';
	Message::AddFromApi($ojnAPI->getApiString(BUNNY_API."/voice?action=set&voice=".$_GET['voice']."&".$ojnAPI->getToken()));
	$reload = true;
}
if(isset($_GET['timezone'])) {
	$timezone = $_GET['timezone'];
	$_SESSION['tab'] = 'bunny_language';
	Message::AddFromApi($ojnAPI->getApiString(BUNNY_API."/setTimezone?name=".$timezone."&".$ojnAPI->getToken()));
	$reload = true;
}
if(!empty($_GET['aInsomniac'])) {
	$_SESSION['tab'] = 'bunny_base';
	$night = (int)$_GET['aInsomniac'] - 1;
	if($night == 0 || $night == 1)
		Message::AddFromApi($ojnAPI->getApiString(BUNNY_API."/setInsomniac?insomniac=".$night."&".$ojnAPI->getToken()));
	else
		Message::AddError(__tr("Bad parameters"));
	$reload = true;
}

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
    die('Connexion impossible : ' . mysqli_error());
}
$silent =2;
$asks = array('config','shortconfig','running','silent');
$StDbg = array();
if(isset($_SESSION['bunny']))
{
  foreach($asks as $ask)
  {
    $sql = 'SELECT * FROM status_'.$ask.' WHERE mac=\''.$_SESSION['bunny'].'\'';
  	$res = mysqli_query($link, $sql);
  	if($row = mysqli_fetch_assoc($res))
	    $StDbg[$ask] = $row;
  }
}
if(isset($_GET['aSilent'])) {
	$_SESSION['tab'] = 'bunny_base';
	$silent = (int)$_GET['aSilent'];
	if($silent == 0 || $silent == 1 || $silent == 2)
	{
		$sql = "INSERT INTO silent SET active='".$silent."', mac='".$_SESSION['bunny']."' ON DUPLICATE KEY UPDATE active='".$silent."'";
		$res = mysqli_query($link, $sql);
		if($silent == 1)
		{
			if(!file_exists('/home/prod/OpenJabNab/http-wrapper/ojn_local/bootcode/bootcode.'.$_SESSION['bunny'])) {
				symlink('/home/prod/OpenJabNab/http-wrapper/ojn_local/bootcode/bootcode.silent', '/home/prod/OpenJabNab/http-wrapper/ojn_local/bootcode/bootcode.'.$_SESSION['bunny']);
			}
			if($silent != $Silent)
				Message::AddFromApi($ojnAPI->getApiString(BUNNY_API."/disconnect?reboot=1&".$ojnAPI->getToken()));
		}
		else if($silent == 0)
		{
			unlink('/home/prod/OpenJabNab/http-wrapper/ojn_local/bootcode/bootcode.'.$_SESSION['bunny']);
			if($silent != $Silent)
				Message::AddFromApi($ojnAPI->getApiString(BUNNY_API."/disconnect?reboot=1&".$ojnAPI->getToken()));
		}
	}
	$reload = true;
}
mysqli_close($link);

$restricted = false;
/*
if(isset($_SESSION['bunny']))
{
	$lasts = $ojnAPI->getLasts($_SESSION['bunny']);
	if($lasts)
	{
		if(isset($lasts['LastIP']) && $lasts['LastIP'] != '')
		{
			$restricted = false;
		}
		else
		{
			Message::AddWarning(__tr('Your bunny has never connected to the server. You must connect it to access the complete setup.'));
		}
	}
}
*/
//echo $lasts['LastIP'];




// Expert
if(isset($_GET['reboot'])) {
	$_SESSION['tab'] = 'bunny_expert';
	Message::AddFromApi($ojnAPI->getApiString(BUNNY_API."/disconnect?reboot=1&".$ojnAPI->getToken()));
	$reload = true;
}

if(isset($_GET['disconnect'])) {
	$_SESSION['tab'] = 'bunny_expert';
	Message::AddFromApi($ojnAPI->getApiString(BUNNY_API."/disconnect?".$ojnAPI->getToken()));
	$reload = true;
}
if(isset($_GET['removeB'])) {
	$_SESSION['tab'] = 'bunny_expert';
	Message::AddFromApi($ojnAPI->getApiString(BUNNY_API."/disconnect?".$ojnAPI->getToken()));
	header("Location: server.php?removeB=".$_SESSION['bunny']);
	exit;
}

// Debug
foreach($asks as $ask)
{
  if(isset($_GET['ask'.$ask])) 
  {
	  $_SESSION['tab'] = 'bunny_debug';
  	Message::AddFromApi($ojnAPI->getApiString(BUNNY_API.'/status/status?action='.$ask.'&'.$ojnAPI->getToken()));
  	$reload = true;
  }
}
if(isset($_GET['resetpwd'])) {
	$_SESSION['tab'] = 'bunny_debug';
	Message::AddFromApi($ojnAPI->getApiString(BUNNY_API."/resetPassword?".$ojnAPI->getToken()));
	$reload = true;
}

if(isset($_GET['resetown'])) {
	$_SESSION['tab'] = 'bunny_debug';
	Message::AddFromApi($ojnAPI->getApiString(BUNNY_API."/resetOwner?".$ojnAPI->getToken()));
	$reload = true;
}

// Plugins
if(!empty($_GET['single']) && !empty($_GET['double'])) {
	$_SESSION['tab'] = 'bunny_plugins';
	$id = Message::AddFromApi($ojnAPI->getApiString(BUNNY_API."/setSingleClickPlugin?name=".$_GET['single']."&".$ojnAPI->getToken()));
	Message::AddFromApi($ojnAPI->getApiString(BUNNY_API."/setDoubleClickPlugin?name=".$_GET['double']."&".$ojnAPI->getToken()), $id);
	$reload = true;
}
if((!empty($_GET['plug']) && !empty($_GET['stat'])) || (!empty($_POST['plug']) && !empty($_POST['stat']))) {
	$plugin = !empty($_GET['stat']) ? $_GET['plug'] : $_POST['plug'];
	$_SESSION['tab'] = 'bunny_plugins';
	if(!($Infos['isAdmin'] || $Infos['status'] == 'VIP' || $Infos['status'] == 'Premium' || $Infos['status'] == 'Demo') && $plugins[$plugin]['premium'])
	{
		$id = Message::AddWarning("<b>".__tr("This plugin is only available to Premium users, or VIP")."</b>");
		Message::AddWarning("&bull; ".__tr("Premium status will be available soon"), $id);
		Message::AddWarning("&bull; ".__tr("VIP status is for everyone who already made a donation"), $id);
		Message::AddWarning("&bull; ".__tr("You could also try all premium plugins for a limited time")." <a href='demo.php'>".__tr("Try plugins")."</a>", $id);
	}
	else if(!($Infos['isAdmin'] || isTester($Infos['login'], $plugin)) && $plugins[$plugin]['dev'])
	{
		$id = Message::AddWarning("<b>".__tr("This plugin is currently not available")."</b>");
	}
	else
	{
		$serial = $_SESSION['bunny'];
		if(isset($_GET['migrate'])) {
			$serial = $_GET['migrate'];
			$_SESSION['tab'] = 'bunny_migrate';
		}
		$a = !empty($_GET['stat']) ? $_GET : $_POST;
		$function = $a['stat'] == 'register' ? 'register' : 'unregister';
		Message::AddFromApi($ojnAPI->getApiString('bunny/'.$serial.'/'.$function.'Plugin?name='.$a['plug'].'&'.$ojnAPI->getToken()));
	}
	$reload = true;
}
if(isset($_GET['migrate']) && isset($_GET['from'])) {
	Message::AddFromApi($ojnAPI->getApiString('bunnies/settingsForBunny?action=clone&plugin='.$_GET['migrate'].'&from='.$_GET['from'].'&to='.$_GET['to'].'&'.$ojnAPI->getToken()));
	$relaod = true;
	$_SESSION['tab'] = 'bunny_migrate';
}
// API
if(!empty($_GET['pVAPI'])) {
	$_SESSION['tab'] = 'bunny_api';
	$pub = (int)$_GET['pVAPI'] - 1;
	if($pub == 0 || $pub == 1)
		Message::AddFromApi($ojnAPI->getApiString(BUNNY_API."/setPublicVAPI?public=".$pub."&".$ojnAPI->getToken()));
	else
		Message::AddError(__tr("Bad parameters"));
	$reload = true;
}
if(!empty($_GET['aVAPI'])) {
	$_SESSION['tab'] = 'bunny_api';
	$st = (string)$_GET['aVAPI'];
	if($st == "enable" || $st == "disable")
		Message::AddFromApi($ojnAPI->getApiString(BUNNY_API."/".$st."VAPI?".$ojnAPI->getToken()));
	else
		Message::AddError(__tr("Bad parameters"));
	$reload = true;
}

if(!empty($_POST) && count($_POST) == 6) {
	$_SESSION['tab'] = 'bunny_expert';
	$id = Message::AddFromApi($ojnAPI->getApiString(BUNNY_API."/locate/setcustomlocate?param=PingServer&value=".$_POST['pingserver']."&".$ojnAPI->getToken()));
	Message::AddFromApi($ojnAPI->getApiString(BUNNY_API."/locate/setcustomlocate?param=BroadServer&value=".$_POST['broadserver']."&".$ojnAPI->getToken()), $id);
	Message::AddFromApi($ojnAPI->getApiString(BUNNY_API."/locate/setcustomlocate?param=XmppServer&value=".$_POST['xmppserver']."&".$ojnAPI->getToken()), $id);
	Message::AddFromApi($ojnAPI->getApiString(BUNNY_API."/locate/setcustomlocate?param=ListeningXmppPort&value=".$_POST['xmppport']."&".$ojnAPI->getToken()), $id);
	Message::AddFromApi($ojnAPI->getApiString(BUNNY_API."/locate/setcustomlocate?param=ListeningXmppAltPort&value=".$_POST['xmppaltport']."&".$ojnAPI->getToken()), $id);
	Message::AddFromApi($ojnAPI->getApiString(BUNNY_API."/locate/setcustomlocate?param=XmppTcpIdleTime&value=".$_POST['xmpptimeout']."&".$ojnAPI->getToken()), $id);
	$reload = true;
}
foreach(array("wifi_ssid", "wifi_auth", "wifi_crypt", "wifi_key", "server_url", "dhcp", "ip", "mask", "gateway", "dns_server") as $conf) {
	$changed = false;
	if(isset($_POST['conf_' . $conf])) {
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/locate/config?action=set&config=$conf&value=".$_POST['conf_' . $conf]."&".$ojnAPI->getToken()));
		$changed = true;
	}
	if($changed) {
		$_SESSION['tab'] = 'bunny_expert';
		header('Location: /bunny/index.php');
		exit;
	}
}
if(isset($_POST['update_conf'])) {
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/locate/config?action=update&".$ojnAPI->getToken()));
	$_SESSION['tab'] = 'bunny_expert';
	header('Location: /bunny/index.php');
	exit;
}

if(!isset($_SESSION['tab']) || !preg_match("|^bunny_|", $_SESSION['tab']))
	$_SESSION['tab'] = 'bunny_base';

if($reload) {
	header('Location: /bunny/index.php');
	exit;
}

$online = $ojnAPI->getListOfConnectedBunnies(false);
$online = is_array($online) ? array_keys($online) : array();
if(isset($Infos['isAdmin']) && $Infos['isAdmin']) {
	$online = array_keys($ojnAPI->getListOfAllConnectedBunnies(true));
}

require_once(ROOT_SITE.'include/message.php');
?>
	      <div class="row">
	      	<div class="span12">
	      		<div class="widget">
					<div class="widget-header">
						<i class="icon-th-large"></i>
<?php
if(empty($_SESSION['bunny'])) {
	?>
						<h3><?php echo __tr("Choose your bunny") ?></h3>
					</div> <!-- /widget-header -->
					<div class="widget-content">
						<div class="object-list object-4">

<?php
$bunnies = $ojnAPI->getListOfBunnies(false);
if(!empty($bunnies)) {
	foreach($bunnies as $bunny => $nom) {
?>
				<div class="obj-container">
				<div class="object">
					<div class="obj-header">
					    <div class="obj-name"><?php echo $nom; ?></div>
					    <div class="obj-info"><?php echo $bunny; ?></div>
					</div>

						<div class="obj-actions">
						<p style="text-align: center">
						<?php if(in_array($bunny, $online)): ?>
						<?php echo __tr('Connected') ?>
						<?php else: ?>
						<i><?php echo __tr('Disconnected') ?></i>
						<?php endif; ?>
						</p>
						 <a class="btn" href="/bunny/index.php?b=<?php echo $bunny; ?>"><li class="icon-cog"></li>&nbsp;<?php echo __tr("Setup") ?></a>
						</div> <!-- /obj-actions -->

					</div> <!-- /object -->
			    </div> <!-- /obj-container -->
<?php
	}
}
/*
?>
			<div class="obj-container">
			<div class="object">
				<div class="obj-header">
				    <div class="obj-name"><?php echo __tr('Add a bunny'); ?></div>
				    <div class="obj-info"><?php  ?></div>
				</div>

					<div class="obj-actions">
					<p style="text-align: center">
					 <a class="btn" href="/bunny/index.php?b=<?php echo $bunny; ?>"><li class="icon-cog"></li>&nbsp;<?php echo __tr("Setup") ?></a>
					</div> <!-- /obj-actions -->

				</div> <!-- /object -->
			    </div> <!-- /obj-container -->
<?php */ ?>
</div>
<?php
} else {
$ojnTemplate->setTitle(__tr('Bunny setup'));
if(defined(BUNNY_API))
	define("BUNNY_API", "bunny/" . $_SESSION['bunny']);
$Token = $ojnAPI->getApiString(BUNNY_API."/getVAPIToken?".$ojnAPI->getToken());
$Token = isset($Token['value']) ? $Token['value'] : '';
/* Status */
$Status = $ojnAPI->getApiString(BUNNY_API."/getVAPIStatus?".$ojnAPI->getToken());
$Status= (!empty($Status['value']) && $Status['value'] == 'enabled') ? true : false;
/* Public */
$Public = $ojnAPI->getApiString(BUNNY_API."/getPublicVAPI?".$ojnAPI->getToken());
$Public= (!empty($Public['value']) && $Public['value'] == "public") ? true : false;
/* Night */
$Insomniac = $ojnAPI->getApiString(BUNNY_API."/getInsomniac?".$ojnAPI->getToken());
$Insomniac = (!empty($Insomniac['value']) && $Insomniac['value'] == "insomniac") ? true : false;

?>
					<h3>
						<?php echo __tr("Setup of bunny '%1'", !empty($_SESSION['bunny_name']) ? $_SESSION['bunny_name'] : $_SESSION['bunny']) ?>
						(
						<?php if(in_array($_SESSION['bunny'], $online)): ?>
						<?php echo __tr('Connected') ?>
						<?php else: ?>
						<i><?php echo __tr('Disconnected') ?></i>
						<?php endif; ?>
						)
					</h3>
					</div> <!-- /widget-header -->


					<div class="widget-content">

						<div class="tabbable">
						<ul class="nav nav-tabs">
						  <li<?php echo $_SESSION['tab'] == 'bunny_base' ? ' class="active"' : '' ?>><a href="#base" data-toggle="tab"><?php echo __tr('Base setup') ?></a></li>
						  <li<?php echo $_SESSION['tab'] == 'bunny_language' ? ' class="active"' : '' ?>><a href="#language" data-toggle="tab"><?php echo __tr('Language') ?></a></li>
						  <li<?php echo $_SESSION['tab'] == 'bunny_api' ? ' class="active"' : '' ?>><a href="#api" data-toggle="tab"><?php echo __tr('API') ?></a></li>
						  <li<?php echo $_SESSION['tab'] == 'bunny_plugins' ? ' class="active"' : '' ?>><a href="#plugins" data-toggle="tab"><?php echo __tr('Plugins') ?></a></li>
						  <li<?php echo $_SESSION['tab'] == 'bunny_expert' ? ' class="active"' : '' ?>><a href="#expert" data-toggle="tab"><?php echo __tr('Expert') ?></a></li>
<?php if(isset($Infos['isAdmin']) && $Infos['isAdmin']): ?>
						  <li<?php echo $_SESSION['tab'] == 'bunny_debug' ? ' class="active"' : '' ?>><a href="#debug" data-toggle="tab"><?php echo __tr('Debug') ?></a></li>
						  <li<?php echo $_SESSION['tab'] == 'bunny_admin' ? ' class="active"' : '' ?>><a href="#admin" data-toggle="tab"><?php echo __tr('Admin') ?></a></li>
						  <li<?php echo $_SESSION['tab'] == 'bunny_migrate' ? ' class="active"' : '' ?>><a href="#migrate" data-toggle="tab"><?php echo __tr('Migrate') ?></a></li>
<?php endif; ?>

						</ul>
						<br />

							<div class="tab-content">
								<div class="tab-pane<?php echo $_SESSION['tab'] == 'bunny_base' ? ' active' : '' ?>" id="base">

<?php

global $plugins;
//$plugins = $ojnAPI->getListOfPlugins(false, $ojnTemplate->getLanguage());
$bunnyPlugins = $ojnAPI->getListOfBunnyEnabledPlugins(false);

$actifs = $ojnAPI->bunnyListOfPlugins($_SESSION['bunny'],false);
foreach($actifs as $actif) {
	$plugins[$actif]['actif'] = true;
}
$clicks = $ojnAPI->getApiList(BUNNY_API."/getClickPlugins?".$ojnAPI->getToken());
$t = $ojnAPI->getApiValue(BUNNY_API."/getTimezone?".$ojnAPI->getToken());
$tzs = $ojnAPI->getApiMapped("translate/listTimezones?".$ojnAPI->getToken());
?>
      <form class="form-horizontal">
        <fieldset>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Name") ?></label>
            <div class="controls">
              <input type="text" name="bunny_name" value="<?php echo $_SESSION['bunny_name']; ?>" class="input-xlarge">
              <p class="help-block"><?php echo __tr("Name of your bunny, choose what you want") ?></p>
            </div>
          </div>
<?php /*
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Silent bunny") ?></label>
            <div class="controls">
              <label class="checkbox">
		<input type="radio" name="aSilent" value="2" <?php echo $Silent == 2 ? 'checked="checked"' : ''; ?> /> <?php echo __tr("Sorry, my bunny isn't silent") ?>
              </label>
              <label class="checkbox">
		<input type="radio" name="aSilent" value="1" <?php echo $Silent == 1 ? 'checked="checked"' : ''; ?>/> <?php echo __tr("My bunny is silent, and I want to help discover why") ?><br />
              </label>
              <label class="checkbox">
		<input type="radio" name="aSilent" value="0" <?php echo $Silent == 0 ? 'checked="checked"' : ''; ?> /> <?php echo __tr("My bunny is silent, but I don't wan't him to be part of experimentations") ?>
              </label>
            </div>
          </div>
          <div class="control-group">
            <label for="optionsCheckbox" class="control-label"><?php echo __tr("How is the night ?") ?></label>
            <div class="controls">
              <label class="checkbox">
		<input type="radio" name="aInsomniac" value="1" <?php echo !$Insomniac ? 'checked="checked"' : ''; ?>/> <?php echo __tr("I'm sleeping") ?><br />
              </label>
              <label class="checkbox">
		<input type="radio" name="aInsomniac" value="2" <?php echo $Insomniac ? 'checked="checked"' : ''; ?> /> <?php echo __tr("I'm insomniac") ?>
              </label>
            </div>
          </div>
          <?php */ ?>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <button class="btn"><?php echo __tr("Cancel") ?></button>
          </div>
        </fieldset>
      </form>
								</div>

								<div class="tab-pane<?php echo $_SESSION['tab'] == 'bunny_language' ? ' active' : '' ?>" id="language">

      <form class="form-horizontal">
        <fieldset>
          <div class="control-group">
            <label for="select01" class="control-label"><?php echo __tr("Language") ?></label>
            <div class="controls">

<select name="lng" onchange="$('#voiceList').val('');">
<?php
	$Lng = $ojnAPI->getApiString(BUNNY_API."/getlanguage?".$ojnAPI->getToken());
	$Lng = isset($Lng['value']) ? $Lng['value'] : 'en';
	$tr = getTranslates(isset($_SESSION['login']) ? $_SESSION['login'] : '');
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
	    die('Connexion impossible : ' . mysqli_error());
	}

	$sql = "SELECT * FROM language";
	if(!isset($Infos['isAdmin']) || !$Infos['isAdmin'])
		$sql .= " WHERE public=1";
	// Exceptions de dev :
	if(count($tr))
		foreach($tr as $t)
			$sql .= " OR code='".$t."'";

	$res = mysqli_query($link, $sql);
	while($row = mysqli_fetch_assoc($res))
	{
?>
<option value="<?php echo $row['code'] ?>"<?php if($Lng == $row['code']) { ?> selected="selected"<?php } ?>><?php echo $row['language'] ?></option>
<?php
	}
	mysqli_close($link);
?>
</select>

            </div>
          </div>
          <div class="control-group">
            <label for="select01" class="control-label"><?php echo __tr("Timezone") ?></label>
            <div class="controls">
              <select name="timezone">
                <?php foreach($tzs as $tz=>$time) { ?>
                <option value="<?php echo $tz; ?>" <?php echo ($t == $tz ? ' selected="selected"' : '') ?>><?php echo $tz.' ('.$time.')'; ?></option>
                <?php } ?>
              </select>
            </div>
          </div>
<?php
$voices = $ojnAPI->getApiMapped(BUNNY_API."/voice?action=list&".$ojnAPI->getToken());
$Voice = $ojnAPI->getApiValue(BUNNY_API."/voice?action=get&".$ojnAPI->getToken());
?>
          <div class="control-group">
            <label for="select01" class="control-label"><?php echo __tr("Voice") ?></label>
            <div class="controls">
	      <select name="voice" id="voiceList">
		<option value=""></option>
	      <?php if(is_array($voices)): ?>
	      <?php foreach($voices as $tts => $vlist): ?>
		<?php if(preg_match('|^(.*)/(.*)$|', $tts, $match)): ?>
	      <option value="<?php echo $tts; ?>" <?php echo ($Voice == $tts ? ' selected="selected"' : '') ?>><?php echo $vlist." (".$match[0].")"; ?></option>
		<?php else: ?>
		<?php foreach(preg_split("/,/", $vlist) as $voice): ?>
	      <option value="<?php echo $tts."/".$voice; ?>" <?php echo ($Voice == $tts."/".$voice ? ' selected="selected"' : '') ?>><?php echo $voice." (".$tts.")"; ?></option>
	        <?php endforeach; ?>
		<?php endif; ?>
	      <?php endforeach; ?>
	      <?php endif; ?>
	      </select>
<div class="input-append"><input type="text" id="testvoice" class="span3" value="<?php echo __tr('Test sentence : hello world') ?>"><a onclick="testVoice()" class="btn btn-success"><?php echo __tr('Test this voice') ?></a></div>
<span id="testvoice_results" style="display: inline-block; margin-top: -6px; top: 6px; position: relative; margin-left: 20px;"></span>
<script>
function testVoice()
{
$.get('testVoice.php?voice=' + $("#voiceList").val() + '&sentence=' + $("#testvoice").val(), function(data) {
  $('#testvoice_results').html(data);
});
}

</script>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <button class="btn"><?php echo __tr("Cancel") ?></button>
          </div>
        </fieldset>
      </form>
								</div>
								<div class="tab-pane<?php echo $_SESSION['tab'] == 'bunny_api' ? ' active' : '' ?>" id="api">

      <form class="form-horizontal">
        <fieldset>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("MAC address") ?></label>
            <div class="controls"><?php echo $_SESSION['bunny'] ; ?></div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Violet API Token") ?></label>
            <div class="controls"><?php echo $Token ; ?></div>
          </div>
          <div class="control-group">
            <label for="optionsCheckbox" class="control-label"><?php echo __tr("Violet API") ?></label>
            <div class="controls">
              <label class="checkbox">
		<input type="radio" name="aVAPI" value="enable" <?php echo $Status ? 'checked="checked"' : ''; ?>/> <?php echo __tr('Enabled') ?><br />
              </label>
              <label class="checkbox">
		<input type="radio" name="aVAPI" value="disable" <?php echo !$Status ? 'checked="checked"' : ''; ?> /> <?php echo __tr('Disabled') ?>
              </label>
            </div>
          </div>
          <div class="control-group">
            <label for="optionsCheckbox" class="control-label"><?php echo __tr("Public") ?></label>
            <div class="controls">
              <label class="checkbox">
		<input type="radio" name="pVAPI" value="2" <?php echo $Public ? 'checked="checked"' : ''; ?>/> <?php echo __tr('Public') ?><br />
              </label>
              <label class="checkbox">
		<input type="radio" name="pVAPI" value="1" <?php echo !$Public ? 'checked="checked"' : ''; ?> /> <?php echo __tr('Private') ?>
              </label>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <button class="btn"><?php echo __tr("Cancel") ?></button>
          </div>
	</form>
								</div>
								<div class="tab-pane<?php echo $_SESSION['tab'] == 'bunny_plugins' ? ' active' : '' ?>" id="plugins">

      <form class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Single click plugin") ?></label>
            <div class="controls">
<?php
$single = $ojnAPI->getClickPlugins();
$double = $ojnAPI->getClickPlugins("double");

function lngSort($a, $b)
{
	global $plugins;
	$_a = $plugins[$a['id']];
	$_b = $plugins[$b['id']];
	if($_a == $_b) return 0;
	return $_a < $_b ? -1 : 1;
}

uasort($single, 'lngSort');
uasort($double, 'lngSort');

function lngSort2($a, $b)
{
	$active1 = $a['actif'] + 0;
	$active2 = $b['actif'] + 0;
	$name1 = $a['name'];
	$name2 = $b['name'];
	$new1 = $a['new'] + 0;
	$new2 = $b['new'] + 0;

	$s1 = 8;
	$s2 = 8;
	if($active1 == 1) $s1 = 5;
	if($active2 == 1) $s2 = 5;

	if($active1 == 0 && $new1 == 1) $s1 = 2;
	if($active2 == 0 && $new2 == 1) $s2 = 2;
	$p1 = $s1 . '_' . $name1;
	$p2 = $s2 . '_' . $name2;

	if($p1 == $p2) return 0;
	return $p1 < $p2 ? -1 : 1;

}

uasort($plugins, 'lngSort2');
function filterPlugins($plugins, $version = 2)
{
	$ret = array();
	//var_dump($plugins);
	foreach($plugins as $name => $infos) {
		if($infos['v' . $version] == 1) {
			$ret[$name] = $infos;
		}
	}
	return $ret;
}
$plugins = filterPlugins($plugins, bunnyVersion($_SESSION['bunny']));
?>
<select name="single" class="span4">
<option value="none"><?php echo __tr('None') ?></option>
<?php foreach($single as $plugin => $info) { ?>
	<?php if($info['enabled'] == "1" && isset($plugins[$plugin]) && $plugins[$plugin]['actif']): ?>
<option value="<?php echo $plugin; ?>" <?php echo ($plugin == $clicks[0] ? ' selected="selected"' : '') ?>><?php echo __tr($plugins[$plugin]['name']); ?></option>
	<?php endif; ?>
<?php } ?>
</select>
	    </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Double click plugin") ?></label>
            <div class="controls">
<select name="double" class="span4">
<option value="none"><?php echo __tr('None') ?></option>
<?php foreach($double as $plugin => $info) { ?>
	<?php if($info['enabled'] == "1" && isset($plugins[$plugin]) && $plugins[$plugin]['actif']): ?>
<option value="<?php echo $plugin; ?>" <?php echo ($plugin == $clicks[1] ? ' selected="selected"' : '') ?>><?php echo __tr($plugins[$plugin]['name']); ?></option>
	<?php endif; ?>
<?php } ?>
</select>
	    </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <button class="btn"><?php echo __tr("Cancel") ?></button>
          </div>
	</form>
<center>
<table class="table table-bordered table-striped span10">
	<tr>
		<th><?php echo __tr('Name of plugin') ?></th>
		<th colspan="2"><?php echo __tr('Actions') ?></th>
	</tr>
<?php
	$i = 0;
	if(is_array($plugins)) {
	foreach($plugins as $id => $plugin)
	{
		if(in_array($id, $bunnyPlugins))
		{
			if($Infos['isAdmin'] || $plugin['display'] || isBeta($_SESSION['bunny'], $id))
			{
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td>
<?php if(isBeta($_SESSION['bunny'], $id)): ?><span class="label label-info"><?php echo __tr('Beta-test') ?></span> <?php endif; ?>
<?php if($plugin['new']): ?><span class="label label-info"><?php echo __tr('New plugin') ?></span> <?php endif; ?>
<?php if($plugin['updated']): ?><span class="label label-success" alt="<?php echo $plugin['version'] ?>" title="<?php echo $plugin['version'] ?>"><?php echo __tr('New version') ?></span> <?php endif; ?>
<?php echo $plugin['name'] ?>
		</td>
		<?php if(!($Infos['isAdmin'] || $Infos['status'] == 'VIP' || $Infos['status'] == 'Premium' || $Infos['status'] == 'Demo') && $plugin['premium']): ?>
		<td colspan="2"><a class="btn btn-small btn-warning" href="/bunny/index.php?premium"><?php echo __tr("Premium plugin") ?></a></td>
		<?php elseif(!($Infos['isAdmin'] || isTester($Infos['login'], $id)) && $plugin['dev']): ?>
		<td colspan="2"><a class="btn btn-small btn-warning" href="/bunny/index.php?wip"><?php echo __tr("WIP") ?></a></td>
		<?php else: ?>
		<td class="span2"><a class="btn btn-small btn-<?php echo $plugin['actif'] ? "danger" : "success";?>" href="?stat=<?php echo $plugin['actif'] ? "unregister" : "register"; ?>&plug=<?php echo $id ?>"><?php echo $plugin['actif'] ? __tr('Disable plugin') : __tr('Enable plugin') ?></a></td>
		<td class="span3"><?php if($plugin['actif'] && file_exists("plugins/".$id.".plugin.php")) { ?><a href="bunny_plugin.php?p=<?php echo $id; ?>" class="btn btn-small btn-primary"><i class="icon-cog icon-large"></i> <?php echo __tr('Setup / Use') ?></a><?php } else { ?>&nbsp;<?php } ?></td>
		<?php endif; ?>
	</tr>
<?php
			}
		}
	}
	}
?>
</table>
</center>
								</div>
								<div class="tab-pane<?php echo $_SESSION['tab'] == 'bunny_expert' ? ' active' : '' ?>" id="expert">
<?php if(bunnyVersion($_SESSION['bunny']) == 2): ?>
      <form class="form-horizontal">
          <div class="form-actions">
            <input class="btn btn-primary" name="disconnect" type="submit" value="<?php echo __tr("Disconnect the bunny") ?>">
            <input class="btn btn-primary" name="reboot" type="submit" value="<?php echo __tr("Reboot the bunny") ?>">
          </div>
	</form>

<h4><?php echo __tr('Use another openJabNab server without modifying bunny setup') ?></h4>
<span style="color: red; font-weight: bold"><?php echo __tr('Use at your own risk, bunny may loose connection if parameters are bad') ?></span>
<?php
$pingserver = $ojnAPI->getApiValue(BUNNY_API."/locate/getcustomlocate?param=PingServer&".$ojnAPI->getToken());
$broadserver = $ojnAPI->getApiValue(BUNNY_API."/locate/getcustomlocate?param=BroadServer&".$ojnAPI->getToken());
$xmppserver = $ojnAPI->getApiValue(BUNNY_API."/locate/getcustomlocate?param=XmppServer&".$ojnAPI->getToken());
$xmppport = $ojnAPI->getApiValue(BUNNY_API."/locate/getcustomlocate?param=ListeningXmppPort&".$ojnAPI->getToken());
$xmppaltport = $ojnAPI->getApiValue(BUNNY_API."/locate/getcustomlocate?param=ListeningXmppAltPort&".$ojnAPI->getToken());
$xmpptimeout = $ojnAPI->getApiValue(BUNNY_API."/locate/getcustomlocate?param=XmppTcpIdleTime&".$ojnAPI->getToken());

?>

      <form class="form-horizontal" method="post">
          <div class="control-group">
            <label for="pingserver" class="control-label"><?php echo __tr("Ping Server") ?></label>
            <div class="controls">
		<input type="text" name="pingserver" value="<?php echo $pingserver ?>">
	    </div>
          </div>
          <div class="control-group">
            <label for="broadserver" class="control-label"><?php echo __tr("Broad Server") ?></label>
            <div class="controls">
		<input type="text" name="broadserver" value="<?php echo $broadserver ?>">
	    </div>
          </div>
          <div class="control-group">
            <label for="xmppserver" class="control-label"><?php echo __tr("Xmpp Server") ?></label>
            <div class="controls">
		<input type="text" name="xmppserver" value="<?php echo $xmppserver ?>">
	    </div>
          </div>
          <div class="control-group">
            <label for="xmppport" class="control-label"><?php echo __tr("Xmpp Port") ?></label>
            <div class="controls">
		<input type="text" name="xmppport" value="<?php echo $xmppport ?>">
	    </div>
          </div>
          <div class="control-group">
            <label for="xmppaltport" class="control-label"><?php echo __tr("Xmpp Port") ?> (<?php echo __tr("Alternative") ?>)</label>
            <div class="controls">
		<input type="text" name="xmppaltport" value="<?php echo $xmppaltport ?>">
	    </div>
          </div>
          <div class="control-group">
            <label for="xmpptimeout" class="control-label"><?php echo __tr("Xmpp timeout") ?></label>
            <div class="controls">
		<input type="text" name="xmpptimeout" value="<?php echo $xmpptimeout ?>">
	    </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <button class="btn"><?php echo __tr("Cancel") ?></button>
          </div>
	</form>

<?php if(isset($Infos['isAdmin']) && $Infos['isAdmin']): ?>
<?php
$tips = array("wifi_crypt" => "0 : Aucun, 1 : WEP, 2 : WPA", "wifi_auth" => "0 : OpenSystem, 1 : SharedKey");
foreach(array("wifi_ssid", "wifi_crypt", "wifi_auth", "wifi_key", "server_url", "dhcp", "ip", "mask", "gateway", "dns_server") as $conf): ?>
<?php
$value = $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/locate/config?action=get&config=$conf&".$ojnAPI->getToken());
$value = $value['value'];
?>
      <form class="form-horizontal" method="post">
          <div class="control-group">
            <label for="xmpptimeout" class="control-label"><?php echo $conf ?></label>
            <div class="controls">
		<input type="text" name="conf_<?php echo $conf ?>" value="<?php echo $value ?>">
                <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
<?php if(isset($tips[$conf])) { echo $tips[$conf]; } ?>
	    </div>
          </div>
	</form>
<?php endforeach; ?>
<?php endif; ?>
      <form class="form-horizontal" method="post">
	<input type="hidden" name="update_conf" value="1">
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Update") ?></button>
          </div>
	</form>

<?php else: ?>
Aucune option n'est disponible pour votre lapin.
<?php endif; ?>
								</div>
<?php if(isset($Infos['isAdmin']) && $Infos['isAdmin']):
$lasts = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/getlasts?".$ojnAPI->getToken());
$xml = $ojnAPI->getApiRaw("bunny/".$_SESSION['bunny']."/getallcrons?".$ojnAPI->getToken());
$crons = simplexml_load_string($xml);
$owner = $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/getOwner?".$ojnAPI->getToken());
?>
								<div class="tab-pane<?php echo $_SESSION['tab'] == 'bunny_debug' ? ' active' : '' ?>" id="debug">

      <form class="form-horizontal" method="get">
          <div class="control-group">
            <label class="control-label"><?php echo __tr("Last IP address") ?></label>
            <div class="controls">
		<input disabled class="input-xlarge span8 disabled" type="text" value="<?php echo isset($lasts['LastIP']) && $lasts['LastIP'] != "" ? $lasts['LastIP'] : __tr('Unknow') ?>">
	    </div>
          </div>
<?php
if(bunnyVersion($_SESSION['bunny']) == 1 && isset($lasts['Last PingConnection']) && $lasts['Last PingConnection'] != ""):
?>
          <div class="control-group">
            <label class="control-label"><?php echo __tr("Last PingConnection") ?></label>
            <div class="controls">
		<input disabled class="input-xlarge span8 disabled" type="text" value="<?php echo isset($lasts['Last PingConnection']) && $lasts['Last PingConnection'] != "" ? date("d/m/Y H:i:s", strtotime($lasts['Last PingConnection'])) : __tr('Unknow') ?>">
	    </div>
          </div>
          <div class="control-group">
            <label class="control-label"><?php echo __tr("Last Ping") ?></label>
            <div class="controls">
		<input disabled class="input-xlarge span8 disabled" type="text" value="<?php echo isset($lasts['Last Ping']) && $lasts['Last Ping'] != "" ? date("d/m/Y H:i:s", strtotime($lasts['Last Ping'])) : __tr('Unknow') ?>">
	    </div>
          </div>
<?php else: ?>
          <div class="control-group">
            <label class="control-label"><?php echo __tr("Last Jabber Connection") ?></label>
            <div class="controls">
		<input disabled class="input-xlarge span8 disabled" type="text" value="<?php echo isset($lasts['Last JabberConnection']) && $lasts['Last JabberConnection'] != "" ? date("d/m/Y H:i:s", strtotime($lasts['Last JabberConnection'])) : __tr('Unknow') ?>">
	    </div>
          </div>
          <div class="control-group">
            <label class="control-label"><?php echo __tr("Last Jabber Disconnection") ?></label>
            <div class="controls">
		<input disabled class="input-xlarge span8 disabled" type="text" value="<?php echo isset($lasts['Last JabberDisconnection']) && $lasts['Last JabberDisconnection'] != "" ? date("d/m/Y H:i:s", strtotime($lasts['Last JabberDisconnection'])) : __tr('Unknow') ?>">
	    </div>
          </div>
          <div class="control-group">
            <label class="control-label"><?php echo __tr("Last Record") ?></label>
            <div class="controls">
		<input disabled class="input-xlarge span8 disabled" type="text" value="<?php echo isset($lasts['LastRecord']) && $lasts['LastRecord'] != "" ? $lasts['LastRecord'] : __tr('Unknow') ?>">
	    </div>
          </div>
          <div class="control-group">
            <label class="control-label"><?php echo __tr("Last Locate") ?></label>
            <div class="controls">
		<input disabled class="input-xlarge span8 disabled" type="text" value="<?php echo isset($lasts['LastLocate']) && $lasts['LastLocate'] != "" ? date("d/m/Y H:i:s", strtotime($lasts['LastLocate'])) : __tr('Unknow') ?>">
	    </div>
          </div>
          <div class="control-group">
            <label class="control-label"><?php echo __tr("Last Locate string") ?></label>
            <div class="controls">
		<textarea disabled class="input-xlarge span8 disabled" style="height: 60px;"><?php echo isset($lasts['LastLocateString']) && $lasts['LastLocateString'] != "" ? $lasts['LastLocateString'] : __tr('Unknow') ?></textarea>
	    </div>
          </div>
<?php endif; ?>
          <div class="control-group">
            <label class="control-label"><?php echo __tr("Last Cron") ?></label>
            <div class="controls">
		<input disabled class="input-xlarge span8 disabled" type="text" value="<?php echo isset($lasts['LastCron']) && $lasts['LastCron'] != "" ? $lasts['LastCron'] : __tr('Unknow') ?>">
	    </div>
          </div>
          <div class="control-group">
            <label class="control-label"><?php echo __tr("Crons") ?></label>
            <div class="controls">
		<textarea disabled class="input-xlarge span8 disabled" style="height: <?php echo max(60, 19 * count($crons->crons->cron)) ?>px;">
<?php foreach($crons->crons->cron as $cron): ?>
<?php echo $cron->plugin ?>-&gt;<?php echo strlen($cron->callback) ? $cron->callback : 'OnCron' ?>(<?php echo strlen($cron->data_string) ? '"' . $cron->data_string . '"' : ( strlen($cron->data_int) ? $cron->data_int : '') ?>) @ <?php echo date('H:i d/m/Y', $cron->next_run + 0) ?>

<?php endforeach; ?>
		</textarea>
	    </div>
          </div>
<?php if(isset($owner['value']) && $owner['value'] != ""): ?>
          <div class="control-group">
            <label class="control-label"><?php echo __tr("Owner") ?></label>
            <div class="controls">
		<input disabled class="input-xlarge span7 disabled" type="text" value="<?php echo isset($owner['value']) && $owner['value'] != "" ? $owner['value'] : __tr('Unknow') ?>"> &nbsp;
			<a target="_blank" class="btn btn-small btn-primary" href="account_expert.php?acc=<?php echo $owner['value'] ?>"><?php echo __tr('Expert view') ?></a>
	    </div>
          </div>
<?php else: ?>
          <div class="control-group">
            <label class="control-label"><?php echo __tr("Owner") ?></label>
            <div class="controls">
		<input disabled class="input-xlarge span8 disabled" type="text" value="">
	    </div>
          </div>
<?php endif; ?>
<?php if(isset($_GET['rawconf']) || isset($_SESSION['rawconf'])) : ?>
          <div class="control-group">
            <label class="control-label"><?php echo __tr("RAW Conf") ?></label>
            <div class="controls">
		<textarea disabled class="input-xlarge span8 disabled" style="height: 320px;">
<?php
unset($_SESSION['rawconf']);
function getIp($s) {
	$ss = array();
	for($i=0; $i<strlen($s); $i++) {
		$ss[] = ord($s[$i]);
	}
	return implode('.', $ss);
}
function getString($s) {
	$ss = array();
	for($i=0; $i<strlen($s); $i++) {
		if(ord($s[$i]) == 0) {
			break;
		}
		$ss[] = $s[$i];
	}
	return implode('', $ss);
}
$str = $ojnAPI->getApiString("bunny/" . $_SESSION['bunny'] . "/locate/config?action=getraw&".$ojnAPI->getToken());
$str = (string)$str['value'];
if(strlen($str)) {
	$s = "";
	while(strlen($str)) {
		$s .= chr(hexdec(substr($str, 0, 2)));
		$str = substr($str, 2, strlen($str));
	}
	echo "Serveur : " . getString(substr($s, 0, 41)) . "\n";
	echo "DHCP    : " . ord(substr($s, 41, 1)) . "\n";
	echo "IP      : " . getIp(substr($s, 42, 4)) . "\n";
	echo "Mask    : " . getIp(substr($s, 46, 4)) . "\n";
	echo "Gateway : " . getIp(substr($s, 50, 4)) . "\n";
	echo "DNS     : " . getIp(substr($s, 54, 4)) . "\n";
	echo "Wifi    : " . getString(substr($s, 58, 32)) . "\n";
	echo "Auth    : " . getString(substr($s, 90, 1)) . "\n";
	echo "Crypt   : " . ord(substr($s, 91, 1)) . "\n";
	echo "Key     : " . getString(substr($s, 92, 64)) . "\n";
	echo "Proxy   : " . ord(substr($s, 156, 1)) . "\n";
	echo "IP      : " . getIp(substr($s, 157, 4)) . "\n";
	echo "Port    : " . (substr($s, 161, 2)) . "\n";
	echo "Login   : " . getString(substr($s, 163, 6)) . "\n";
	echo "Pwd     : " . getString(substr($s, 169, 6)) . "\n";
	echo "PMK     : " . getString(substr($s, 175, 32)) . "\n";
	echo "Magic   : " . ord(substr($s, 207, 1)) ;
}
?>
		</textarea>
	    </div>
          </div>
<?php endif; ?>
          <div class="form-actions">
		<input class="btn btn-primary" name="resetpwd" type="submit" value="<?php echo __tr('Reset password') ?>">
		<input class="btn btn-primary" name="resetown" type="submit" value="<?php echo __tr('Reset owner') ?>">
		<input class="btn btn-primary" name="removeB" type="submit" value="<?php echo __tr('Remove bunny') ?>">
		<input class="btn btn-primary" name="rawconf" type="submit" value="<?php echo __tr('RAW Conf') ?>">
          </div>
</form>
<form class="form-horizontal" method="get">
<?php 
$StTitles = array(
  'config' => 'Configuration',
  'shortconfig' => 'ShortConfiguration',
  'running' => 'Running',
  'silent' => 'Silent'
);
foreach($asks as $ask): ?>
<?php if(isset($StDbg[$ask])): ?>
  <div class="control-group">
    <table class="table table-bordered table-striped span10">
      <tr>
        <th colspan="2"><?php echo __tr($StTitles[$ask]); ?></th>
      </tr>
      <?php foreach($StDbg[$ask] as $key => $value): ?>
			<tr>
				<th class="span3"><?php echo $key ?></th>
				<td><?php echo $value ?></td>
			</tr>
      <?php endforeach; ?>
		</table>
  </div>
<?php endif; ?>
<?php endforeach; ?>
  <div class="form-actions">
		<input class="btn btn-primary" name="askconfig" type="submit" value="<?php echo __tr('Ask for config') ?>">
		<input class="btn btn-primary" name="askshortconfig" type="submit" value="<?php echo __tr('Ask for shortconfig') ?>">
		<input class="btn btn-primary" name="askrunning" type="submit" value="<?php echo __tr('Ask for running') ?>">
		<input class="btn btn-primary" name="asksilent" type="submit" value="<?php echo __tr('Ask for silent') ?>">
  </div>
</form>
								</div>
								<div class="tab-pane<?php echo $_SESSION['tab'] == 'bunny_admin' ? ' active' : '' ?>" id="admin">

<table class="table table-bordered table-striped span11">
	<tr>
		<th colspan="4"><?php echo __tr('Recorded messages') ?></th>
	</tr>
	<tr>
		<th><?php echo __Tr('Date') ?></th>
		<th><?php echo __Tr('File') ?></th>
		<th><?php echo __tr('Actions') ?></th>
	</tr>
<?php
	$i = 0;
$dir = "../ojn_local/plugins/record/";
$files = array();
// Ouvre un dossier bien connu, et liste tous les fichiers
if (is_dir($dir)) {
    if ($dh = opendir($dir)) {
        while (($file = readdir($dh)) !== false) {
		if(preg_match('/record_'.$_SESSION['bunny'].'_(\d{4})(\d{2})(\d{2})_(\d{2})(\d{2})(\d{2}).wav/', $file, $match)) {
			$files[$file] = strtotime($match[1]."-".$match[2]."-".$match[3]." ".$match[4].":".$match[5].":".$match[6]);
		}
	}
        closedir($dh);
   }
}
krsort($files);
foreach($files as $file => $date) {
	$date = date('d/m/Y H:i:s', $date);
	$url = "http://openjabnab.fr/ojn_local/plugins/record/" . $file;
	$path = "../ojn_local/plugins/record/" . $file;
/*
		$parts = explode("|", $message);
		$date = date('d/m/Y H:i:s', $parts[0]);
		$plugin = $parts[1];
		$files = array();
		for($i=2; $i<count($parts);$i++)
		{
			$files[] = preg_replace("|^broadcast/|", "http://openjabnab.fr/", $parts[$i]);
		}
*/

?>
	<tr>
		<td><?php echo $date ?></td>
		<td><?php echo $url ?></td>
		<td>
			<?php /* <audio id="audio1" src="<?php echo $path ?>" controls preload="auto" autobuffer></audio> */ ?>
			<embed src="<?php echo $path ?>" autostart=false loop=false style="height: 16px" >
		</td>
	</tr>
<?php

}
?>
</table>

								</div>

								<div class="tab-pane<?php echo $_SESSION['tab'] == 'bunny_migrate' ? ' active' : '' ?>" id="migrate">



<?php if(isset($_SESSION['migration_bunny']) && strlen($_SESSION['migration_bunny']) == 12): ?>
<?php
$bunnies = $ojnAPI->getApiMapped("bunnies/getListofAllBunnies?".$ojnAPI->getToken());
$actifs_migration = $ojnAPI->bunnyListOfPlugins($_SESSION['migration_bunny'], true);
?>
<table class="table table-bordered table-striped span11">
	<tr>
		<th><?php echo isset($bunnies[$_SESSION['bunny']]) ? $bunnies[$_SESSION['bunny']] : $_SESSION['bunny'] ?></th>
		<th><?php echo __Tr('Plugin') ?></th>
		<th><?php echo isset($bunnies[$_SESSION['migration_bunny']]) ? $bunnies[$_SESSION['migration_bunny']] : $_SESSION['migration_bunny'] ?></th>
	</tr>
<?php foreach($plugins as $id => $plugin): ?>
	<tr>
		<td><a href="/bunny/index.php?migrate=<?php echo $id ?>&from=<?php echo $_SESSION['migration_bunny'] ?>&to=<?php echo $_SESSION['bunny'] ?>" class="btn">&lt;&lt; <?php echo __tr("Migrate") ?></a></td>
		<td style="text-align: center">
    <div class="btn-toolbar">
    <div class="btn-group">
    <a class="btn span2 btn-<?php echo $plugin['actif'] ? "danger" : "success";?>" href="?migrate=<?php echo $_SESSION['bunny'] ?>&stat=<?php echo $plugin['actif'] ? "unregister" : "register"; ?>&plug=<?php echo $id ?>"><?php echo $plugin['actif'] ? __tr('Disable plugin') : __tr('Enable plugin') ?></a>
    <a class="btn span6 disabled"><?php echo $plugin['name'] ?></a>
    <a class="btn span2 btn-<?php echo in_array($id, $actifs_migration) ? "danger" : "success";?>" href="?migrate=<?php echo $_SESSION['migration_bunny']?>&stat=<?php echo in_array($id, $actifs_migration) ? "unregister" : "register"; ?>&plug=<?php echo $id ?>"><?php echo in_array($id, $actifs_migration) ? __tr('Disable plugin') : __tr('Enable plugin') ?></a>
    </div>
    </div>
		</td>
		<td style="text-align: right"><a href="/bunny/index.php?migrate=<?php echo $id ?>&to=<?php echo $_SESSION['migration_bunny'] ?>&from=<?php echo $_SESSION['bunny'] ?>" class="btn"><?php echo __tr("Migrate") ?> &gt;&gt;</a></td>
	</tr>
<?php endforeach; ?>
</table>
<br style="clear: both" />
<hr />
<?php endif; ?>

      <form method="post" class="form-horizontal">
        <fieldset>
          <div class="control-group">
            <label for="select01" class="control-label"><?php echo __tr("Bunny") ?></label>
            <div class="controls">
<input type="text" name="migration_bunny" class="span3" value="<?php echo isset($_SESSION['migration_bunny']) ? $_SESSION['migration_bunny'] : '' ?>">
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
          </div>
        </fieldset>
      </form>

								</div>
<?php endif; ?>
							</div>
</div>
</div>
</div>


<?php
}
?>
					</div> <!-- /widget-content -->
				</div> <!-- /widget -->
		    </div> <!-- /span12 -->
	      </div> <!-- /row -->
<?php
require_once "../include/append.php";
?>
