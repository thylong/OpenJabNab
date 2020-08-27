<?php
$reload = false;
$Ztamps = $ojnAPI->GetListofZtamps(false);

if(!empty($_POST)) {
	if(isset($_POST['koubachi_user'])) {
		$_SESSION['subtab'] = "koubachi_mac";
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/koubachi/config?action=set&user=".urlencode($_POST['koubachi_user'])."&key=".urlencode($_POST['koubachi_key'])."&".$ojnAPI->getToken()));
		$reload = true;
	}
	if(isset($_POST['koubachi_mac'])) {
		$_SESSION['subtab'] = "koubachi_mac";
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/koubachi/config?action=addmac&mac=".urlencode($_POST['koubachi_mac'])."&plant=".urlencode($_POST['koubachi_name'])."&".$ojnAPI->getToken()));
		$reload = true;
	}
	if(!empty($_POST['scheduleT']) && !empty($_POST['scheduleC'])) {
		$time = trim(preg_replace("|[\.h]|", ":", $_POST['scheduleT']));
		if(preg_match("|\d{1,2}:\d{2}|", $time)) {
			Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/koubachi/schedule?action=add&mac=".urlencode($_POST['scheduleC'])."&time=".$time."&".$ojnAPI->getToken()));
		} else {
			Message::AddError(__tr("Invalid time format"));
		}
		$_SESSION['subtab'] = "koubachi_schedule";
		$reload = true;
	}
	if(isset($_POST['amac']) && isset($_POST['atag'])) {
		$_SESSION['subtab'] = "koubachi_rfid";
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/koubachi/rfid?action=add&tag=".$_POST['atag']."&mac=".urlencode($_POST['amac'])."&".$ojnAPI->getToken()));
		$reload = true;
	}
	if(isset($_POST['rtag'])) {
		$_SESSION['subtab'] = "koubachi_rfid";
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/koubachi/rfid?action=del&tag=".$_POST['rtag']."&".$ojnAPI->getToken()));
		$reload = true;
	}
	if(isset($_POST['lang'])) {
		$_SESSION['subtab'] = "koubachi_mac";
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/koubachi/setlang?lg=".$_POST['lang']."&".$ojnAPI->getToken()));
		$reload = true;
	}
}
else if(!empty($_GET['rp']) || isset($_GET['rp'])) {
	$_SESSION['subtab'] = "koubachi_mac";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/koubachi/config?action=delmac&mac=".urlencode($_GET['rp'])."&".$ojnAPI->getToken()));
	$reload = true;
}
else if(!empty($_GET['d'])) {
	$_SESSION['subtab'] = "koubachi_mac";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/koubachi/config?action=setmac&mac=".urlencode($_GET['d'])."&".$ojnAPI->getToken()));
	$reload = true;
}
else if(!empty($_GET['rw'])) {
	$_SESSION['subtab'] = "koubachi_schedule";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/koubachi/schedule?action=del&time=".$_GET['rw']."&".$ojnAPI->getToken()));
	$reload = true;
}

$Langs = array('fr'=>__tr('French'),'en'=>__tr('English'), 'es'=>__tr('Spanish'), 'de' => __tr('German'));
/*
if(!empty($_POST['a'])) {
	} else if($_POST['a'] == "rfidadd") {
	var_dump($_POST);
		if(!empty($_POST['RfPlant']) && !empty($_POST['Tag_Rfa']) && isset($Ztamps[$_POST['Tag_Rfa']])) {
			$retour = $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/koubachi/addrfid?tag=".$_POST['Tag_Rfa']."&mac=".urlencode($_POST['RfPlant'])."&".$ojnAPI->getToken());
			$_SESSION['message'] = isset($retour['ok']) ? $retour['ok'] : "Error : ".$retour['error'];
			header("Location: bunny_plugin.php?p=koubachi");
		}
	} else if($_POST['a'] == "rfidd") {
		if(!empty($_POST['Tag_Rf']) && isset($Ztamps[$_POST['Tag_Rf']])) {
			$retour = $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/koubachi/removerfid?tag=".$_POST['Tag_Rf']."&".$ojnAPI->getToken());
			$_SESSION['message'] = isset($retour['ok']) ? $retour['ok'] : "Error : ".$retour['error'];
			header("Location: bunny_plugin.php?p=koubachi");
		}
	} else if($_POST['a'] == "setlang") {
		if(!empty($_POST['Lang']) && isset($Langs[$_POST['Lang']])) {
			$retour = $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/koubachi/setlang?lg=".$_POST['Lang']."&".$ojnAPI->getToken());
			$_SESSION['message'] = isset($retour['ok']) ? $retour['ok'] : "Error : ".$retour['error'];
			header("Location: bunny_plugin.php?p=koubachi");
		}
	}
}
*/
$wList = $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/koubachi/schedule?action=list&".$ojnAPI->getToken());
$Assoc = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/koubachi/rfid?action=list&".$ojnAPI->getToken());
$conf = array();
$xml = $ojnAPI->getApiRaw("bunny/".$_SESSION['bunny']."/koubachi/config?action=get&".$ojnAPI->getToken());
$list = new SimpleXMLElement($xml);
$pList = array();
$conf['user'] = $list->config->user;
$conf['key'] = $list->config->key;
$default = $list->config->mac;
foreach($list->config->macs->mac as $mac)
{
	$p = array();
	$attrs = $mac->attributes();
	$attrs = (array)$attrs;

	$pList[$attrs['@attributes']['address']] = (string)$mac;
}
/*
$lang =  $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/koubachi/getlang?".$ojnAPI->getToken());
$lang = isset($lang['value']) ? (string)($lang['value']) : 'fr';
*/


if(!isset($_SESSION['subtab']) || !preg_match("|^koubachi_|", $_SESSION['subtab'])) {
	$_SESSION['subtab'] = "koubachi_mac";
}
if($reload)
{
	header("Location: bunny_plugin.php?p=koubachi");
	exit();
}
?>

		<div class="tabbable">
		<ul class="nav nav-tabs">
		  <li<?php echo $_SESSION['subtab'] == 'koubachi_mac' ? ' class="active"' : '' ?>><a href="#mac" data-toggle="tab"><?php echo __tr('Setup') ?></a></li>
		  <li<?php echo $_SESSION['subtab'] == 'koubachi_schedule' ? ' class="active"' : '' ?>><a href="#schedule" data-toggle="tab"><?php echo __tr('Schedules') ?></a></li>
		  <li<?php echo $_SESSION['subtab'] == 'koubachi_rfid' ? ' class="active"' : '' ?>><a href="#rfid" data-toggle="tab"><?php echo __tr('RFID') ?></a></li>
		</ul>
		<br />
		
			<div class="tab-content">
				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'koubachi_mac' ? ' active' : '' ?>" id="mac">
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Add a mac") ?></label>
            <div class="controls">
<input type="text" id="koubachi_mac" name="koubachi_mac" class="span2" value="">
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Name") ?></label>
            <div class="controls">
<input type="text" id="koubachi_name" name="koubachi_name" class="span2" value="">
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <button class="btn"><?php echo __tr("Cancel") ?></button>
          </div>
</form>
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("User") ?></label>
            <div class="controls">
<input type="text" id="koubachi_user" name="koubachi_user" class="span4" value="<?php echo $conf['user'] ?>">
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Key") ?></label>
            <div class="controls">
<input type="text" id="koubachi_key" name="koubachi_key" class="span4" value="<?php echo $conf['key'] ?>">
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <button class="btn"><?php echo __tr("Cancel") ?></button>
          </div>
</form>
<?php /*
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Language") ?></label>
            <div class="controls">
<select name="lang">
<?php foreach($Langs as $k => $v): ?>
<option value="<?php echo $k ?>"<?php if($lang == $k): ?> selected="selected"<?php endif; ?>><?php echo $v ?></option>
<?php endforeach; ?>
</select>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <button class="btn"><?php echo __tr("Cancel") ?></button>
          </div>
</form>
*/ ?>
				</div>

				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'koubachi_schedule' ? ' active' : '' ?>" id="schedule">
<form method="post" class="form-horizontal">
<?php echo __tr("Add a schedule at (hh:mm)") ?>
 <div class="input-append bootstrap-timepicker">
<input id="timepicker2" type="text" name="scheduleT" class="input-small"><span class="add-on">
<i class="icon-time"></i>
</span>
</div>
<?php $ojnTemplate->setJs('<script type="text/javascript">jQuery("#timepicker2").timepicker({minuteStep: 1,showSeconds: false,showMeridian: false});</script>'); ?>
<?php echo __tr("for mac") ?>
&nbsp;<select name="scheduleC">
	<option value=""></option>
	<?php if(!empty($pList))
	foreach($pList as $code => $item) { ?>
		<option value="<?php echo $code ?>"><?php echo $item; ?></option>
	<?php } ?>
</select><br />
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <button class="btn"><?php echo __tr("Cancel") ?></button>
          </div>
</form>
				</div>



				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'koubachi_rfid' ? ' active' : '' ?>" id="rfid">
<form method="post" class="form-horizontal">
<?php echo __tr("Use") ?> <select name="amac" class="span4">
	<option value=""></option>
	<?php  if(!empty($pList))
	foreach($pList as $code => $item) { ?>
		<option value="<?php echo $code ?>"><?php echo urldecode($item); ?></option>
	<?php } ?>
</select> <?php echo __tr("on Ztamp") ?> <select name="atag">
    <option value=""></option>
	<?php foreach($Ztamps as $k=>$v): ?>
	<option value="<?php echo $k; ?>"><?php echo $v; ?> (<?php echo $k; ?>)</option>
	<?php endforeach; ?>
	</select><br />
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
          </div>
</form>
<?php if(count($Assoc)): ?>
<form method="post" class="form-horizontal">
<?php echo __tr("Delete Ztamp association") ?>
&nbsp;<select name="rtag">
    <option value=""><?php echo __tr('Choose an association') ?></option>
	<?php foreach($Assoc as $k=>$v): ?>
	<option value="<?php echo $k; ?>"><?php echo $v; ?> (<?php echo $Ztamps[$k] . " - " . $k; ?>)</option>
	<?php endforeach; ?>
	</select>


          <div class="form-actions">
            <button class="btn btn-danger" type="submit"><?php echo __tr("Remove") ?></button>
          </div>
<?php endif; ?>
</form>
				</div>
			</div>
		</div>

<?php /*
<input type="radio" name="a" value="setlang" /> Default Language (for koubachi infos only for now) <select name="Lang">
	<option value=""></option>
	<?php  if(!empty($Langs))
	foreach($Langs as $k=>$v) { ?>
		<option value="<?php echo urldecode($k) ?>" <?php echo ($lang == $k) ? 'selected="true"': ''; ?>><?php echo urldecode($v); ?></option>
	<?php } ?>
</select><br />
*/ ?>

<?php
if(!empty($pList)) {
?>
<hr />
<center>
<table style="width: 80%">
	<tr>
		<th colspan="4"><?php echo __tr("Plants") ?></th>
	</tr>
	<tr>
		<th><?php echo __tr("Plant") ?></th>
		<th><?php echo __tr("Code") ?></th>
		<th colspan="2"><?php echo __tr("Actions") ?></th>
	</tr>
<?php
	$i = 0;
	foreach($pList as $code => $item) {
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><?php echo urldecode($item) ?></td>
		<td><i><?php echo $code ?></i></td>
		<td width="15%"><a class="btn btn-small btn-danger" href="bunny_plugin.php?p=koubachi&rp=<?php echo $code ?>"><?php echo __tr("Remove") ?></a></td>
		<td width="15%"><?php if($default != $code) { ?><a href="bunny_plugin.php?p=koubachi&d=<?php echo $code ?>" class="btn btn-small btn-primary"><?php echo __tr("Set as default") ?></a><?php } else { echo __tr("Default mac"); } ?></td>
	</tr>
<?php } ?>
</table>
<?php
}
if(isset($wList['list']->item)){
?>
<hr />
<center>
<table style="width: 80%">
	<tr>
		<th colspan="3"><?php echo __tr("Webcasts") ?></th>
	</tr>
	<tr>
		<th><?php echo __tr("Time") ?></th>
		<th><?php echo __tr("Plant") ?></th>
		<th><?php echo __tr("Actions") ?></th>
	</tr>
<?php
	$i = 0;
	foreach($wList['list']->item as $item) {
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><?php echo urldecode($item->key) ?></td>
		<td><?php echo $pList[(string)$item->value] ?></td>
		<td width="15%"><a href="bunny_plugin.php?p=koubachi&rw=<?php echo $item->key ?>" class="btn btn-small btn-danger"><?php echo __tr("Remove") ?></a></td>
	</tr>
<?php  } ?>
</table>
<?php } ?>
