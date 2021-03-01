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
if(!empty($_POST['lng'])) {
	$_SESSION['tab'] = 'bunny_language';
	Message::AddFromApi($ojnAPI->getApiString(BUNNY_API."/setlanguage?lng=".$_POST['lng']."&".$ojnAPI->getToken()));
	$reload = true;
}
if(!empty($_POST['bunny_name'])) {
	$_SESSION['tab'] = 'bunny_base';
	Message::AddFromApi($ojnAPI->getApiString(BUNNY_API."/setBunnyName?name=".urlencode($_POST['bunny_name'])."&".$ojnAPI->getToken()));
	$_SESSION['bunny_name'] = $_POST['bunny_name'];
	$reload = true;
}
if(!empty($_POST['voice'])) {
	$_SESSION['tab'] = 'bunny_language';
	Message::AddFromApi($ojnAPI->getApiString(BUNNY_API."/voice?action=set&voice=".$_POST['voice']."&".$ojnAPI->getToken()));
	$reload = true;
}
if(!empty($_POST['timezone'])) {
	$timezone = $_POST['timezone'];
	$_SESSION['tab'] = 'bunny_language';
	Message::AddFromApi($ojnAPI->getApiString(BUNNY_API."/setTimezone?name=".$timezone."&".$ojnAPI->getToken()));
	$reload = true;
}
if(!empty($_POST['aInsomniac'])) {
	$_SESSION['tab'] = 'bunny_base';
	$night = (int)$_POST['aInsomniac'] - 1;
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
if(!empty($_POST['single']) && !empty($_POST['double'])) {
	$_SESSION['tab'] = 'bunny_plugins';
	$id = Message::AddFromApi($ojnAPI->getApiString(BUNNY_API."/setSingleClickPlugin?name=".$_POST['single']."&".$ojnAPI->getToken()));
	Message::AddFromApi($ojnAPI->getApiString(BUNNY_API."/setDoubleClickPlugin?name=".$_POST['double']."&".$ojnAPI->getToken()), $id);
	$reload = true;
}
if((!empty($_GET['plug']) && !empty($_GET['stat'])) || (!empty($_POST['plug']) && !empty($_POST['stat']))) {
	$plugin = !empty($_GET['stat']) ? $_GET['plug'] : $_POST['plug'];
  $_SESSION['tab'] = 'bunny_plugins';

  $beta = isBeta($_SESSION['bunny'], $plugin) || isTester($Infos['login'], $plugin);

  if($plugins[$plugin]['premium'] &&
      !(   $Infos['isAdmin']
        || $Infos['status'] == 'VIP'
        || $Infos['status'] == 'Premium'
        || $Infos['status'] == 'Demo'
        || $beta
       )
    )
	{
		$id = Message::AddWarning("<b>".__tr("This plugin is only available to Premium users, or VIP")."</b>");
		Message::AddWarning("&bull; ".__tr("Premium status will be available soon"), $id);
		Message::AddWarning("&bull; ".__tr("VIP status is for everyone who already made a donation"), $id);
		Message::AddWarning("&bull; ".__tr("You could also try all premium plugins for a limited time")." <a href='demo.php'>".__tr("Try plugins")."</a>", $id);
	}
  else if($plugins[$plugin]['dev'] &&
          !(   $Infos['isAdmin']
            || $beta
           )
         )
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
if(!empty($_POST['pVAPI'])) {
	$_SESSION['tab'] = 'bunny_api';
	$pub = (int)$_POST['pVAPI'] - 1;
	if($pub == 0 || $pub == 1)
		Message::AddFromApi($ojnAPI->getApiString(BUNNY_API."/setPublicVAPI?public=".$pub."&".$ojnAPI->getToken()));
	else
		Message::AddError(__tr("Bad parameters"));
	$reload = true;
}
if(!empty($_POST['aVAPI'])) {
	$_SESSION['tab'] = 'bunny_api';
	$st = (string)$_POST['aVAPI'];
	if($st == "enable" || $st == "disable")
		Message::AddFromApi($ojnAPI->getApiString(BUNNY_API."/".$st."VAPI?".$ojnAPI->getToken()));
	else
		Message::AddError(__tr("Bad parameters"));
	$reload = true;
}

if(!empty($_POST['pingserver']) && !empty($_POST['broadserver']) && !empty($_POST['xmppserver']) &&
   !empty($_POST['xmppport']) && !empty($_POST['xmppaltport']) && !empty($_POST['xmpptimeout'])
  )
{
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

$title = empty($_SESSION['bunny']) ?  __tr("Choose your bunny") :
                                      __tr("Setup of bunny '%1'", !empty($_SESSION['bunny_name']) ? $_SESSION['bunny_name'] : $_SESSION['bunny']).
                                        ' ('.__tr(in_array($_SESSION['bunny'], $online) ? 'Connected' : 'Disconnected').')' ;
?>

<div class="card ">
  <h5 class="card-header">
    <i class="icon-th-large"></i> <?php echo $title; ?>
  </h5>
  <div class="card-body">
    <?php if(empty($_SESSION['bunny'])): ?>
    <div class="object-list object-4">
      <?php
      $bunnies = $ojnAPI->getListOfBunnies(false);
      if(!empty($bunnies))
        foreach($bunnies as $bunny => $nom):
      ?>
      <div class="obj-container">
        <div class="object">
          <div class="obj-header">
            <div class="obj-name"><?php echo $nom; ?></div>
            <div class="obj-info"><?php echo $bunny; ?></div>
          </div>
          <div class="obj-actions">
            <p class="text-center">
              <?php echo __tr(in_array($bunny, $online) ? 'Connected' : 'Disconnected') ?>
            </p>
            <a class="btn" href="/bunny/index.php?b=<?php echo $bunny; ?>"><i class="icon-cog"></i> <?php echo __tr("Setup") ?></a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else:
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
      <ul class="nav nav-tabs">
      <li class="nav-item">
        <a class="nav-link <?php echo $_SESSION['tab'] == 'bunny_base' ? 'active' : '' ?>"  href="#base" data-toggle="tab" role="tab" aria-controls="base" aria-selected="true"><?php echo __tr('Base setup') ?></a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?php echo $_SESSION['tab'] == 'bunny_language' ? 'active' : '' ?>"  href="#language" data-toggle="tab" role="tab" aria-controls="language" aria-selected="false"><?php echo __tr('Language') ?></a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?php echo $_SESSION['tab'] == 'bunny_api' ? 'active' : '' ?>"  href="#api" data-toggle="tab" role="tab" aria-controls="api" aria-selected="false"><?php echo __tr('API') ?></a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?php echo $_SESSION['tab'] == 'bunny_plugins' ? 'active' : '' ?>"  href="#plugins" data-toggle="tab" role="tab" aria-controls="plugins" aria-selected="false"><?php echo __tr('Plugins') ?></a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?php echo $_SESSION['tab'] == 'bunny_expert' ? 'active' : '' ?> bg-warning text-secondary" href="#expert" data-toggle="tab" role="tab" aria-controls="expert" aria-selected="false"><?php echo __tr('Expert') ?></a>
      </li>
      <?php if(!empty($Infos['isAdmin'])): ?>
      <li class="nav-item">
        <a class="nav-link <?php echo $_SESSION['tab'] == 'bunny_debug' ? 'active' : '' ?> bg-danger text-light" href="#debug" data-toggle="tab" role="tab" aria-controls="debug" aria-selected="false"><?php echo __tr('Debug') ?></a>
      </li>
      <li class="nav-item">
        <a class="nav-link bg-danger text-light<?php echo $_SESSION['tab'] == 'bunny_admin' ? 'active' : '' ?>" href="#admin" data-toggle="tab" role="tab" aria-controls="admin" aria-selected="false"><?php echo __tr('Admin') ?></a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?php echo $_SESSION['tab'] == 'bunny_migrate' ? 'active' : '' ?> bg-danger text-light" href="#migrate" data-toggle="tab" role="tab" aria-controls="migrate" aria-selected="false"><?php echo __tr('Migrate') ?></a>
      </li>
      <?php endif; ?>
    </ul>
    <div class="tab-content">
      <br />
			<div class="tab-pane<?php echo $_SESSION['tab'] == 'bunny_base' ? ' active' : '' ?>" id="base">
        <?php
        global $plugins;
        //$plugins = $ojnAPI->getListOfPlugins(false, $ojnTemplate->getLanguage());
        $bunnyPlugins = $ojnAPI->getListOfBunnyEnabledPlugins(false);

        $actifs = $ojnAPI->bunnyListOfPlugins($_SESSION['bunny'],false);
        foreach($actifs as $actif)
          $plugins[$actif]['actif'] = true;
        $clicks = $ojnAPI->getApiList(BUNNY_API."/getClickPlugins?".$ojnAPI->getToken());
        $Tz = $ojnAPI->getApiValue(BUNNY_API."/getTimezone?".$ojnAPI->getToken());
        $tzs = $ojnAPI->getApiMapped("translate/listTimezones?".$ojnAPI->getToken());
        ?>
        <form method="post">
          <div class="form-group row">
            <label class="col-sm-2 col-form-label" for="bunny_name"><?php echo __tr("Name") ?></label>
            <div class="col-sm-2">
              <input type="text" class="form-control" name="bunny_name" value="<?php echo $_SESSION['bunny_name']; ?>">
            </div>
            <div class="col-sm-8">
              <p class="help-block"><?php echo __tr("Name of your bunny, choose what you want") ?></p>
            </div>
          </div>
          <div class="form-group row">
            <div class="col-sm-12 text-left">
              <button type="submit" class="btn btn-primary"><?php echo __tr('Save') ?></button>
            </div>
          </div>
        </form>
      </div>

      <div class="tab-pane<?php echo $_SESSION['tab'] == 'bunny_language' ? ' active' : '' ?>" id="language">
        <form method="post">
          <div class="form-group row">
            <label class="col-sm-2 col-form-label" for="lng"><?php echo __tr("Language") ?></label>
            <div class="col-sm-2">
              <select name="lng"  class="form-control" onchange="$('#voiceList').val('');">
                <?php
                  $Lng = $ojnAPI->getApiString(BUNNY_API."/getlanguage?".$ojnAPI->getToken());
                  $Lng = isset($Lng['value']) ? $Lng['value'] : 'en';
                  $tr = getTranslates(isset($_SESSION['login']) ? $_SESSION['login'] : '');
                  $link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
                  if (!$link) {
                      die('Connexion impossible : ' . mysqli_error());
                  }

                  $sql = "SELECT * FROM language";
                  if(!empty($Infos['isAdmin']))
                    $sql .= " WHERE public=1";
                  // Exceptions de dev :
                  if(count($tr))
                    foreach($tr as $t)
                      $sql .= " OR code='".$t."'";

                  $res = mysqli_query($link, $sql);
                  while($res && $row = mysqli_fetch_assoc($res)):
                ?>
                <option value="<?php echo $row['code'] ?>"<?php if($Lng == $row['code']) { ?> selected="selected"<?php } ?>>
                  <?php echo $row['language'] ?>
                </option>
                <?php endwhile;
                  mysqli_close($link);
                ?>
              </select>
            </div>
          </div>
          <div class="form-group row">
            <label class="col-sm-2 col-form-label" for="timezone"><?php echo __tr("Timezone") ?></label>
            <div class="col-sm-4">
              <select name="timezone" class="form-control">
                <?php foreach($tzs as $tz=>$time): ?>
                  <option value="<?php echo $tz; ?>" <?php echo ($Tz == $tz ? ' selected="selected"' : '') ?>>
                    <?php echo $tz.' ('.$time.')'; ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <?php
          $voices = $ojnAPI->getApiMapped(BUNNY_API."/voice?action=list&".$ojnAPI->getToken());
          $Voice = $ojnAPI->getApiValue(BUNNY_API."/voice?action=get&".$ojnAPI->getToken());
          ?>
          <div class="form-group row">
            <label class="col-sm-2 col-form-label" for="voice"><?php echo __tr("Voice") ?></label>
            <div class="col-sm-3">
              <select name="voice" id="voiceList" class="form-control">
                <?php
                if(is_array($voices)):
                  foreach($voices as $tts => $vlist):
                    if(preg_match('|^(.*)/(.*)$|', $tts, $match)): ?>
                <option value="<?php echo $tts; ?>" <?php echo ($Voice == $tts ? ' selected="selected"' : '') ?>><?php echo $vlist." (".$match[0].")"; ?></option>
                <?php
                    else:
                      foreach(preg_split("/,/", $vlist) as $voice):
                ?>
                <option value="<?php echo $tts."/".$voice; ?>" <?php echo ($Voice == $tts."/".$voice ? ' selected="selected"' : '') ?>><?php echo $voice." (".$tts.")"; ?></option>
                <?php
                      endforeach;
                    endif;
                  endforeach;
                endif; ?>
              </select>
            </div>
            <div class="col-sm-7">
              <div class="row">
                <div class="col-sm-8">
                  <input type="text" id="testvoice" class="form-control" value="<?php echo __tr('Test sentence : hello world') ?>">
                </div>
                <div class="col-sm-4">
                  <a onclick="testVoice()" class="btn btn-sm btn-light"><?php echo __tr('Test this voice') ?></a>
                </div>
              </div>
              <div class="row">
                <div class="col-md-12 text-center mt-1" id="testvoice_results"></div>
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
          </div>
          <div class="form-group row">
            <div class="col-sm-12 text-left">
              <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            </div>
          </div>
        </form>
      </div>

		  <div class="tab-pane<?php echo $_SESSION['tab'] == 'bunny_api' ? ' active' : '' ?>" id="api">
        <form method="post">
          <div class="form-group row">
            <label class="col-sm-2 col-form-label"><?php echo __tr("MAC address") ?></label>
            <div class="col-sm-2">
              <?php echo $_SESSION['bunny'] ; ?>
            </div>
          </div>
          <div class="form-group row">
            <label class="col-sm-2 col-form-label"><?php echo __tr("Violet API Token") ?></label>
            <div class="col-sm-4">
              <?php echo $Token ; ?>
            </div>
          </div>
          <div class="form-group row">
            <label class="col-sm-2 col-form-label" for="aVAPI"><?php echo __tr("Violet API") ?></label>
            <div class="col-sm-4">
		          <input type="radio" name="aVAPI" value="enable" <?php echo $Status ? 'checked="checked"' : ''; ?>/> <?php echo __tr('Enabled') ?><br />
		          <input type="radio" name="aVAPI" value="disable" <?php echo !$Status ? 'checked="checked"' : ''; ?> /> <?php echo __tr('Disabled') ?>
            </div>
          </div>
          <div class="form-group row">
            <label class="col-sm-2 col-form-label" for="pVAPI"><?php echo __tr("Public") ?></label>
            <div class="col-sm-4">
		          <input type="radio" name="pVAPI" value="2" <?php echo $Public ? 'checked="checked"' : ''; ?>/> <?php echo __tr('Public') ?><br />
              <input type="radio" name="pVAPI" value="1" <?php echo !$Public ? 'checked="checked"' : ''; ?> /> <?php echo __tr('Private') ?>
            </div>
          </div>
          <div class="form-group row">
            <div class="col-sm-12 text-left">
              <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            </div>
          </div>
	      </form>
      </div>

      <div class="tab-pane<?php echo $_SESSION['tab'] == 'bunny_plugins' ? ' active' : '' ?>" id="plugins">
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
          global $bunnyPlugins;
          $ret = array();
          foreach($plugins as $name => $infos)
          {
            if(in_array($name, $bunnyPlugins) &&
              $infos['v' . $version] == 1
              )
              $ret[$name] = $infos;
          }
          return $ret;
        }
        $plugins = filterPlugins($plugins, bunnyVersion($_SESSION['bunny']));
        ?>
        <form method="post">
          <fieldset class="border p-3">
            <legend><h6><?php echo __tr('Clic(s) plugins configuration'); ?></h6></legend>
            <div class="form-group row">
              <label class="col-sm-2 col-form-label" for="single"><?php echo __tr("Single click plugin") ?></label>
              <div class="col-sm-6">
                <select name="single" class="form-control">
                  <option value="none"><?php echo __tr('None') ?></option>
                  <?php foreach($single as $plugin => $info):
                    if($info['enabled'] == "1" && isset($plugins[$plugin]) && $plugins[$plugin]['actif']):
                  ?>
                  <option value="<?php echo $plugin; ?>" <?php echo ($plugin == $clicks[0] ? ' selected="selected"' : '') ?>><?php echo __tr($plugins[$plugin]['name']); ?></option>
                  <?php
                    endif;
                  endforeach; ?>
                </select>
              </div>
            </div>
            <div class="form-group row">
              <label class="col-sm-2 col-form-label" for="double"><?php echo __tr("Double click plugin") ?></label>
              <div class="col-sm-6">
                <select name="double" class="form-control">
                  <option value="none"><?php echo __tr('None') ?></option>
                  <?php foreach($double as $plugin => $info):
                    if($info['enabled'] == "1" && isset($plugins[$plugin]) && $plugins[$plugin]['actif']):
                  ?>
                  <option value="<?php echo $plugin; ?>" <?php echo ($plugin == $clicks[1] ? ' selected="selected"' : '') ?>><?php echo __tr($plugins[$plugin]['name']); ?></option>
                  <?php
                    endif;
                  endforeach; ?>
                </select>
	            </div>
            </div>
            <div class="form-group row">
              <div class="col-sm-12 text-left">
                <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
              </div>
            </div>
          </fieldset>
	      </form>
        <table class="table table-bordered table-striped mt-2">
	        <tr>
		        <th class="col-md-8"><?php echo __tr('Name of plugin') ?></th>
		        <th class="col-md-4"><?php echo __tr('Actions') ?></th>
	        </tr>
          <?php
          foreach($plugins as $id => $plugin):
            $beta = isBeta($_SESSION['bunny'], $id) || isTester($Infos['login'], $id);
            if(!($Infos['isAdmin'] || $plugin['display'] || $beta))
              continue;
          ?>
          <tr>
            <td>
              <?php echo $plugin['name'] ?>
              <span class="float-right">
                <?php if($plugin['new']): ?><span class="badge badge-primary"><?php echo __tr('New plugin') ?></span> <?php endif; ?>
                <?php if($plugin['updated']): ?><span class="badge badge-success" alt="<?php echo $plugin['version'] ?>" title="<?php echo $plugin['version'] ?>"><?php echo __tr('New version') ?></span> <?php endif; ?>
                <?php if($plugin['premium']): ?><span class="badge badge-warning"><?php echo __tr("Premium") ?></span> <?php endif; ?>
                <?php if($plugin['dev']): ?><span class="badge badge-info"><?php echo __tr("WIP") ?></span><?php endif; ?>
                <?php if(!$plugin['display']): ?><span class="badge badge-secondary"><?php echo __tr("Hidden") ?></span><?php endif; ?>
                <?php if($beta): ?><span class="badge badge-danger"><?php echo __tr("Beta") ?></span><?php endif; ?>
                &nbsp;
              </span>
            </td>
            <td>
              <?php if($plugin['premium'] && !($Infos['isAdmin'] || $Infos['status'] == 'VIP' || $Infos['status'] == 'Premium' || $Infos['status'] == 'Demo')): ?>
              <a class="btn btn-sm btn-warning" href="/bunny/index.php?premium"><?php echo __tr("Premium") ?></a>
              <?php elseif($plugin['dev'] && !($Infos['isAdmin'] || $beta)): ?>
              <span class="badge badge-info"><?php echo __tr("WIP") ?></span>
              <?php else: ?>
                <a class="btn btn-sm btn-<?php echo $plugin['actif'] ? "danger" : "success";?>" href="?stat=<?php echo $plugin['actif'] ? "unregister" : "register"; ?>&plug=<?php echo $id ?>"><?php echo $plugin['actif'] ? __tr('Disable plugin') : __tr('Enable plugin') ?></a>
                <?php if($plugin['actif'] && file_exists("plugins/".$id.".plugin.php")): ?><a href="bunny_plugin.php?p=<?php echo $id; ?>" class="btn btn-sm btn-primary"><i class="icon-cog icon-large"></i> <?php echo __tr('Setup / Use') ?></a> <?php endif; ?>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </table>
      </div>

      <div class="tab-pane<?php echo $_SESSION['tab'] == 'bunny_expert' ? ' active' : '' ?>" id="expert">
        <?php if(bunnyVersion($_SESSION['bunny']) == 2): ?>
        <form method="post">
          <fieldset class="border p-3">
            <legend><h6><?php echo __tr('Actions'); ?></h6></legend>
            <div class="form-group row">
              <div class="col-sm-6">
                <a class="btn btn-primary" href="?disconnect"><?php echo __tr("Disconnect the bunny") ?></a>
                <a class="btn btn-primary" href="?reboot"><?php echo __tr("Reboot the bunny") ?></a>
              <div>
            </div>
          </fieldset>
	      </form>

        <?php
        $pingserver = $ojnAPI->getApiValue(BUNNY_API."/locate/getcustomlocate?param=PingServer&".$ojnAPI->getToken());
        $broadserver = $ojnAPI->getApiValue(BUNNY_API."/locate/getcustomlocate?param=BroadServer&".$ojnAPI->getToken());
        $xmppserver = $ojnAPI->getApiValue(BUNNY_API."/locate/getcustomlocate?param=XmppServer&".$ojnAPI->getToken());
        $xmppport = $ojnAPI->getApiValue(BUNNY_API."/locate/getcustomlocate?param=ListeningXmppPort&".$ojnAPI->getToken());
        $xmppaltport = $ojnAPI->getApiValue(BUNNY_API."/locate/getcustomlocate?param=ListeningXmppAltPort&".$ojnAPI->getToken());
        $xmpptimeout = $ojnAPI->getApiValue(BUNNY_API."/locate/getcustomlocate?param=XmppTcpIdleTime&".$ojnAPI->getToken());
        ?>
        <form method="post" class="mt-2">
          <fieldset class="border p-3">
            <legend><h6><?php echo __tr('Use another openJabNab server without modifying bunny setup') ?></h6></legend>
            <div class="alert alert-danger">
              <?php echo __tr('Use at your own risk, bunny may loose connection if parameters are bad') ?>
            </div>
            <div class="form-group row">
              <label class="col-sm-2 col-form-label" for="pingserver"><?php echo __tr('Ping Server') ?></label>
              <div class="col-sm-3">
                <input type="text" class="form-control" name="pingserver" value="<?php echo $pingserver ?>">
              </div>
            </div>
            <div class="form-group row">
              <label class="col-sm-2 col-form-label" for="pingserver"><?php echo __tr('Broad server') ?></label>
              <div class="col-sm-3">
                <input type="text" class="form-control" name="pingserver" value="<?php echo $pingserver ?>">
              </div>
            </div>
            <div class="form-group row">
              <label class="col-sm-2 col-form-label" for="xmppserver"><?php echo __tr('Xmpp server') ?></label>
              <div class="col-sm-3">
                <input type="text" class="form-control" name="xmppserver" value="<?php echo $xmppserver ?>">
              </div>
            </div>
            <div class="form-group row">
              <label class="col-sm-2 col-form-label" for="xmppport"><?php echo __tr('Xmpp port') ?></label>
              <div class="col-sm-1">
                <input type="text" class="form-control" name="xmppport" value="<?php echo $xmppport ?>">
              </div>
            </div>
            <div class="form-group row">
              <label class="col-sm-2 col-form-label" for="xmppaltport"><?php echo __tr("Xmpp port") ?> (<?php echo __tr("Alternative") ?>)</label>
              <div class="col-sm-1">
                <input type="text" class="form-control" name="xmppaltport" value="<?php echo $xmppaltport ?>">
              </div>
            </div>
            <div class="form-group row">
              <label class="col-sm-2 col-form-label" for="xmpptimeout"><?php echo __tr('Xmpp timeout') ?></label>
              <div class="col-sm-1">
                <input type="text" class="form-control" name="xmpptimeout" value="<?php echo $xmpptimeout ?>">
              </div>
            </div>
            <div class="form-group row">
              <div class="col-sm-1 offset-sm-2 text-left">
                <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
              </div>
            </div>
          </fieldset>
	      </form>

        <?php if($Infos['isAdmin']): ?>
        <div class="card">
          <h6 class="card-header bg-danger text-light">Administration</h6>
          <div class="card-body">
            <?php
            $tips = array("wifi_crypt" => "0 : Aucun, 1 : WEP, 2 : WPA", "wifi_auth" => "0 : OpenSystem, 1 : SharedKey");
            foreach(array("wifi_ssid", "wifi_crypt", "wifi_auth", "wifi_key", "server_url", "dhcp", "ip", "mask", "gateway", "dns_server") as $conf): ?>
            <?php
            $value = $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/locate/config?action=get&config=$conf&".$ojnAPI->getToken());
            $value = $value['value'];
            ?>
            <form cclass="form-inline" method="post">
              <div class="form-group row">
                <label class="col-sm-2 col-form-label" for="conf_<?php echo $conf; ?>"><?php echo ucfirst($conf); ?></label>
                <div class="col-sm-2">
                  <input type="text" class="form-control" name="conf_<?php echo $conf; ?>" value="<?php echo $value; ?>">
                </div>
                <div class="col-sm-2">
                  <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
                </div>
                <?php if(!empty($tips[$conf])): ?>
                <div class="col-sm-3">
                  <?php echo $tips[$conf]; ?>
                </div>
                <?php endif; ?>
              </div>
            </form>
            <?php endforeach; ?>
            <hr />
            <form class="form" method="post">
              <div class="form-group row">
                <label class="col-sm-2 col-form-label" for="update_conf"><?php echo __tr('Update configuration'); ?></label>
                <input type="hidden" name="update_conf" value="1">
                <div class="col-sm-2">
                  <button class="btn btn-primary" type="submit"><?php echo __tr("Update") ?></button>
                </div>
              </div>
            </form>
          </div>
        </div>
        <?php endif; /* Not Admin */ ?>
        <?php else: /* Not V2 */ ?>
        Aucune option n'est disponible pour votre lapin.
        <?php endif; ?>
      </div>

      <?php if(!empty($Infos['isAdmin'])):
        $lasts = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/getlasts?".$ojnAPI->getToken());
        $xml = $ojnAPI->getApiRaw("bunny/".$_SESSION['bunny']."/getallcrons?".$ojnAPI->getToken());
        $crons = simplexml_load_string($xml);
        $owner = $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/getOwner?".$ojnAPI->getToken());
      ?>
      <div class="tab-pane<?php echo $_SESSION['tab'] == 'bunny_debug' ? ' active' : '' ?>" id="debug">
        <form>
          <div class="form-group row">
            <label class="col-sm-3 col-form-label"><?php echo __tr("Last IP address") ?></label>
            <div class="col-sm-9">
              <input disabled class="form-control disabled" type="text" value="<?php echo isset($lasts['LastIP']) && $lasts['LastIP'] != "" ? $lasts['LastIP'] : __tr('Unknow') ?>">
	          </div>
          </div>
          <?php if(bunnyVersion($_SESSION['bunny']) == 1 && isset($lasts['Last PingConnection']) && $lasts['Last PingConnection'] != ""): ?>
          <div class="form-group row">
            <label class="col-sm-3 col-form-label"><?php echo __tr("Last PingConnection") ?></label>
            <div class="col-sm-9">
              <input disabled class="form-control disabled" type="text" value="<?php echo isset($lasts['Last PingConnection']) && $lasts['Last PingConnection'] != "" ? date("d/m/Y H:i:s", strtotime($lasts['Last PingConnection'])) : __tr('Unknow') ?>">
	          </div>
          </div>
          <div class="form-group row">
            <label class="col-sm-3 col-form-label"><?php echo __tr("Last Ping") ?></label>
            <div class="col-sm-9">
              <input disabled class="form-control disabled" type="text" value="<?php echo isset($lasts['Last Ping']) && $lasts['Last Ping'] != "" ? date("d/m/Y H:i:s", strtotime($lasts['Last Ping'])) : __tr('Unknow') ?>">
	          </div>
          </div>
          <?php else: ?>
          <div class="form-group row">
            <label class="col-sm-3 col-form-label"><?php echo __tr("Last Jabber Connection") ?></label>
            <div class="col-sm-9">
              <input disabled class="form-control disabled" type="text" value="<?php echo isset($lasts['Last JabberConnection']) && $lasts['Last JabberConnection'] != "" ? date("d/m/Y H:i:s", strtotime($lasts['Last JabberConnection'])) : __tr('Unknow') ?>">
	          </div>
          </div>
          <div class="form-group row">
            <label class="col-sm-3 col-form-label"><?php echo __tr("Last Jabber Disconnection") ?></label>
            <div class="col-sm-9">
              <input disabled class="form-control disabled" type="text" value="<?php echo isset($lasts['Last JabberDisconnection']) && $lasts['Last JabberDisconnection'] != "" ? date("d/m/Y H:i:s", strtotime($lasts['Last JabberDisconnection'])) : __tr('Unknow') ?>">
	          </div>
          </div>
          <div class="form-group row">
            <label class="col-sm-3 col-form-label"><?php echo __tr("Last Record") ?></label>
            <div class="col-sm-9">
              <input disabled class="form-control disabled" type="text" value="<?php echo isset($lasts['LastRecord']) && $lasts['LastRecord'] != "" ? $lasts['LastRecord'] : __tr('Unknow') ?>">
	          </div>
          </div>
          <div class="form-group row">
            <label class="col-sm-3 col-form-label"><?php echo __tr("Last Locate") ?></label>
            <div class="col-sm-9">
              <input disabled class="form-control disabled" type="text" value="<?php echo isset($lasts['LastLocate']) && $lasts['LastLocate'] != "" ? date("d/m/Y H:i:s", strtotime($lasts['LastLocate'])) : __tr('Unknow') ?>">
	          </div>
          </div>
          <div class="form-group row">
            <label class="col-sm-3 col-form-label"><?php echo __tr("Last Locate string") ?></label>
            <div class="col-sm-9">
              <textarea disabled class="form-control disabled" rows="6"><?php echo isset($lasts['LastLocateString']) && $lasts['LastLocateString'] != "" ? trim($lasts['LastLocateString']) : __tr('Unknow') ?></textarea>
	          </div>
          </div>
          <?php endif; ?>
          <div class="form-group row">
            <label class="col-sm-3 col-form-label"><?php echo __tr("Last Cron") ?></label>
            <div class="col-sm-9">
              <input disabled class="form-control disabled" type="text" value="<?php echo isset($lasts['LastCron']) && $lasts['LastCron'] != "" ? $lasts['LastCron'] : __tr('Unknow') ?>">
	          </div>
          </div>
          <div class="form-group row">
            <label class="col-sm-3 col-form-label"><?php echo __tr("Crons") ?></label>
            <div class="col-sm-9">
              <textarea disabled class="form-control disabled" rows="<?php echo max(10, count($crons->crons->cron)); ?>"><?php
                foreach($crons->crons->cron as $cron):
                echo $cron->plugin ?>-&gt;<?php echo strlen($cron->callback) ? $cron->callback : 'OnCron' ?>(<?php echo strlen($cron->data_string) ? '"' . $cron->data_string . '"' : ( strlen($cron->data_int) ? $cron->data_int : '') ?>) @ <?php echo date('H:i d/m/Y', $cron->next_run + 0)."\n";
                endforeach; ?>
		          </textarea>
	          </div>
          </div>
          <div class="form-group row">
            <label class="col-sm-3 col-form-label"><?php echo __tr("Owner") ?></label>
            <div class="col-sm-3">
		          <input disabled class="form-control disabled" type="text" value="<?php echo !empty($owner['value']) ? $owner['value'] : __tr('Unknow') ?>"> &nbsp;
            <?php if(!empty($owner['value'])): ?>
            </div>
            <div class="col-sm-">
			        <a target="_blank" class="btn btn-sm btn-primary" href="/admin/account/account_expert.php?acc=<?php echo $owner['value'] ?>"><?php echo __tr('Expert view') ?></a>
              <input class="btn btn-primary btn-sm btn-danger" name="resetown" type="submit" value="<?php echo __tr('Reset owner') ?>">
            <?php endif; ?>
	          </div>
          </div>
          <div class="form-group row">
            <label class="col-sm-2 col-form-label"><?php echo __tr("Raw configuration") ?></label>
            <div class="col-sm-1">
              <input class="btn btn-primary" name="rawconf" type="submit" value="<?php echo __tr('RAW Conf') ?>">
            </div>
            <div class="col-sm-9">
              <textarea disabled class="form-control disabled" rows="10"><?php if(isset($_GET['rawconf']) || isset($_SESSION['rawconf'])) :
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
                endif; ?></textarea>
            </div>
          </div>
          <div class="form-group row">
            <label class="col-sm-3 col-form-label"><?php echo __tr('Actions'); ?></label>
            <div class="col-sm-9">
              <input class="btn btn-primary" name="resetpwd" type="submit" value="<?php echo __tr('Reset password') ?>">
		          <input class="btn btn-primary btn-danger" name="removeB" type="submit" value="<?php echo __tr('Remove bunny') ?>">
            </div>
          </div>
        </form>

        <?php
        $StTitles = array(
          'config' => 'Configuration',
          'shortconfig' => 'ShortConfiguration',
          'running' => 'Running',
          'silent' => 'Silent'
        ); ?>
        <form class="mt-3" method="get">
          <div class="form-group row">
            <label class="col-sm-3 col-form-label"><?php echo __tr('Dumps'); ?></label>
            <div class="col-sm-9">
              <?php foreach($asks as $ask): ?>
              <input class="btn btn-primary" name="ask<?php echo $ask; ?>" type="submit" value="<?php echo __tr('Ask for '.$ask) ?>">
              <?php endforeach ?>
            </div>
          </div>
        </form>
        <?php foreach($asks as $ask):
          if(isset($StDbg[$ask])):
        ?>
        <div class="form-group row">
          <table class="table table-bordered table-striped span10">
            <tr>
              <th colspan="2"><?php echo __tr($StTitles[$ask]); ?> <i>('<?php echo $ask; ?>')</i></th>
            </tr>
            <?php foreach($StDbg[$ask] as $key => $value): ?>
            <tr>
              <th class="span3"><?php echo $key ?></th>
              <td><?php echo $value ?></td>
            </tr>
            <?php endforeach; ?>
          </table>
        </div>
        <?php endif;
        endforeach; ?>
      </div>

      <div class="tab-pane<?php echo $_SESSION['tab'] == 'bunny_admin' ? ' active' : '' ?>" id="admin">
        <h5><?php echo __tr('Recorded messages') ?></h5>
        <table class="table table-bordered table-striped span11">
	        <tr>
            <th><?php echo __Tr('Date') ?></th>
            <th><?php echo __Tr('File') ?></th>
            <th><?php echo __tr('Actions') ?></th>
          </tr>
          <?php
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
          foreach($files as $file => $date):
            $date = date('d/m/Y H:i:s', $date);
            $url = "http://openjabnab.fr/ojn_local/plugins/record/" . $file;
            $path = "../ojn_local/plugins/record/" . $file;
          ?>
          <tr>
            <td><?php echo $date ?></td>
            <td><a target="_blank" href="<?php echo $url ?>"><?php echo $file ?></a></td>
            <td>
              <?php /* <audio id="audio1" src="<?php echo $path ?>" controls preload="auto" autobuffer></audio> */ ?>
              <embed src="<?php echo $path ?>" autostart=false loop=false style="height: 16px" >
              <a class="btn btn-sm btn-primary" href="#"><i class="icon-play"></i></a>
              <a class="btn btn-sm btn-danger" href="#"><i class="icon-trash"></i></a>
            </td>
          </tr>
          <?php endforeach; ?>
        </table>
      </div>

      <div class="tab-pane<?php echo $_SESSION['tab'] == 'bunny_migrate' ? ' active' : '' ?>" id="migrate">
        <h6>Copy plugins settings from/to another bunny</h6>
        <form class="form-inline mt-3" method="get">
            <div class="form-group row">
              <label class="col-sm-5 col-form-label"><?php echo __tr("Other bunny") ?></label>
              <div class="col-sm-5">
                <input type="text" placeholder="00xxxxxxxxxx" name="migration_bunny" class="form-control" value="<?php echo isset($_SESSION['migration_bunny']) ? $_SESSION['migration_bunny'] : '' ?>">
              </div>
              <div class="col-sm-1">
                <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
              </div>
            </div>
        </form>
        <hr />

        <?php if(isset($_SESSION['migration_bunny']) && strlen($_SESSION['migration_bunny']) == 12):
          $bunnies = $ojnAPI->getApiMapped("bunnies/getListofAllBunnies?".$ojnAPI->getToken());
          $actifs_migration = $ojnAPI->bunnyListOfPlugins($_SESSION['migration_bunny'], true);
        ?>
        <table class="table table-bordered table-striped span11">
          <tr>
            <th><?php echo isset($bunnies[$_SESSION['bunny']]) ? $bunnies[$_SESSION['bunny']] : $_SESSION['bunny'] ?></th>
            <th><?php echo __tr('Plugin') ?></th>
            <th><?php echo isset($bunnies[$_SESSION['migration_bunny']]) ? $bunnies[$_SESSION['migration_bunny']] : $_SESSION['migration_bunny'] ?></th>
          </tr>
          <?php foreach($plugins as $id => $plugin): ?>
            <tr>
              <td>
                <a href="/bunny/index.php?migrate=<?php echo $id ?>&from=<?php echo $_SESSION['migration_bunny'] ?>&to=<?php echo $_SESSION['bunny'] ?>" class="btn">&lt;&lt; <?php echo __tr("Migrate") ?></a>
                <a class="btn btn-sm btn-<?php echo $plugin['actif'] ? "danger" : "success";?>" href="?migrate=<?php echo $_SESSION['bunny'] ?>&stat=<?php echo $plugin['actif'] ? "unregister" : "register"; ?>&plug=<?php echo $id ?>"><?php echo $plugin['actif'] ? __tr('Disable plugin') : __tr('Enable plugin') ?></a>
                </td>
              <td class="text-center">
                <?php echo $plugin['name'] ?>
              </td>
              <td class="text-right">
                <a class="btn btn-sm btn-<?php echo in_array($id, $actifs_migration) ? "danger" : "success";?>" href="?migrate=<?php echo $_SESSION['migration_bunny']?>&stat=<?php echo in_array($id, $actifs_migration) ? "unregister" : "register"; ?>&plug=<?php echo $id ?>"><?php echo in_array($id, $actifs_migration) ? __tr('Disable plugin') : __tr('Enable plugin') ?></a>
                <a href="/bunny/index.php?migrate=<?php echo $id ?>&to=<?php echo $_SESSION['migration_bunny'] ?>&from=<?php echo $_SESSION['bunny'] ?>" class="btn"><?php echo __tr("Migrate") ?> &gt;&gt;</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>
        <?php endif; ?>
      </div>

      <?php endif; // is Admin ?>

    </div>
    <?php endif; // Bunny selected ?>
  </div>
</div>
<?php
require_once "../include/append.php";
?>
