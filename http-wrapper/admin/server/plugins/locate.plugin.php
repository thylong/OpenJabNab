<?php
$bunnies = array();
$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
    die('Connexion impossible : ' . mysqli_error());
}
$reload = false;
$configs = array('server_url', 'dhcp', 'ip', 'mask', 'gateway', 'dns_server');
if(isset($_SESSION['bunny']))
	define("BUNNY_API", "bunny/" . $_SESSION['bunny']);
if(count($_POST)) {
	if(isset($_POST['clear'])) {
		$_SESSION['bunny'] = '';
		$reload = true;
	} else if(isset($_POST['bunny'])) {
		$_SESSION['bunny'] = $_POST['bunny'];
		$reload = true;
	}
	foreach($configs as $cfg) {
		if(isset($_POST[$cfg])) {
			Message::AddFromApi($ojnAPI->getApiString(BUNNY_API."/locate/config?action=set&config=".$cfg."&value=".($_POST[$cfg])."&".$ojnAPI->getToken()));
			$reload = true;
		}
	}
}
$bunny = isset($_SESSION['bunny']) ? $_SESSION['bunny'] : '';
if(strlen($bunny)) {
	if(isset($_POST['askrun'])) {
		Message::AddFromApi($ojnAPI->getApiString(BUNNY_API."/status/status?action=running&".$ojnAPI->getToken()));
		$reload = true;
	}
	if(isset($_POST['askconf'])) {
		Message::AddFromApi($ojnAPI->getApiString(BUNNY_API."/status/status?action=config&".$ojnAPI->getToken()));
		$reload = true;
	}
	if(isset($_POST['askreboot'])) {
		Message::AddFromApi($ojnAPI->getApiString(BUNNY_API."/disconnect?reboot=1&".$ojnAPI->getToken()));
		$reload = true;
	}
	if(isset($_POST['askreconf'])) {
		Message::AddFromApi($ojnAPI->getApiString(BUNNY_API."/locate/config?action=update&".$ojnAPI->getToken()));
		$reload = true;
	}
}
if($reload) {
	header("Location: server_plugin.php?p=locate");
	exit();
}
if(strlen($bunny)) {
	$lasts = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/getlasts?".$ojnAPI->getToken());
	$online = array_keys($ojnAPI->getListOfAllConnectedBunnies(true));
	$xml = $ojnAPI->getApiRaw('bunny/'.$bunny.'/locate/config?action=getconfig&'.$ojnAPI->getToken());
	$conf = simplexml_load_string($xml);

	$list = array();

	foreach($conf->configs->config as $cfg)
	{
		$attrs = (array)$cfg->attributes();
		$list[$attrs['@attributes']['name']] = (string)$cfg;
	}
}
include(ROOT_SITE.'include/message.php');
?>
<div class="span5">
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Bunny") ?></label>
            <div class="controls">
<?php if(strlen($bunny)): ?>
		<input type="text" name="bunny" class="input-large <?php echo in_array($bunny, $online) ? "btn-success" : "btn-danger" ?>" value="<?php echo $bunny ?>"/>
<?php else: ?>
		<input type="text" name="bunny" class="input-large" value="<?php echo $bunny ?>"/>
<?php endif; ?>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Use") ?></button>
            <button class="btn btn-danger" type="input" name="clear" value="1"><?php echo __tr("Clear") ?></button>
          </div>
</form>


<?php if(strlen($bunny)): ?>
<form method="post" class="form-horizontal">
<?php foreach($configs as $cfg): ?>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo $cfg ?></label>
            <div class="controls">
		<input type="text" name="<?php echo $cfg ?>" class="input-large" value="<?php echo $list[$cfg] ?>"/>
            </div>
          </div>
<?php endforeach; ?>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
          </div>
</form>
<form class="form-horizontal" method="post">
          <div class="form-actions">
		<input class="btn btn-primary" name="askconf" type="submit" value="<?php echo __tr('Ask configuration') ?>">
		<input class="btn btn-primary" name="askrun" type="submit" value="<?php echo __tr('Ask current') ?>">
		<br />
		<br />
		<input class="btn btn-success" name="askreconf" type="submit" value="<?php echo __tr('Reconfigure') ?>">
		<input class="btn btn-primary" name="askreboot" type="submit" value="<?php echo __tr('Reboot') ?>">
		<a href="/bunny/index.php?b=<?php echo $bunny ?>" class="btn btn-success"><?php echo __tr('View') ?></a>
          </div>
</form>
<?php endif; ?>
</div>
<div class="span5">
<?php
if(strlen($bunny)) {
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
	    die('Connexion impossible : ' . mysqli_error());
	}
	$Conf = array();
	$Run = array();
	$sql = "SELECT * FROM status_config WHERE mac='".$_SESSION['bunny']."'";
	$res = mysqli_query($link, $sql);
	if($row = mysqli_fetch_assoc($res))
	{
		$Conf = $row;
	}
	$sql = "SELECT * FROM status_running WHERE mac='".$_SESSION['bunny']."'";
	$res = mysqli_query($link, $sql);
	if($row = mysqli_fetch_assoc($res))
	{
		$Run = $row;
	}
	mysqli_close($link);
?>
<?php if(count($lasts)): ?>
          <div class="control-group">
<table class="table table-bordered table-striped span10">
<?php foreach($lasts as $key => $value): ?>
<?php if(!preg_match('/ping/i', $key)): ?>
			<tr>
				<th class="span3"><?php echo $key ?></th>
				<td><?php echo $key == 'LastLocateString' ? nl2br($value) : $value ?></td>
			</tr>
<?php endif; ?>
<?php endforeach; ?>
		</table>
          </div>
<?php endif; ?>
<?php if(count($list)): ?>
          <div class="control-group">
<table class="table table-bordered table-striped span10">
<?php foreach($list as $key => $value): ?>
			<tr>
				<th class="span3"><?php echo $key ?></th>
				<td><?php echo $value ?></td>
			</tr>
<?php endforeach; ?>
		</table>
          </div>
<?php endif; ?>
<?php if(count($Conf)): ?>
          <div class="control-group">
<table class="table table-bordered table-striped span10">
<?php foreach($Conf as $key => $value): ?>
			<tr>
				<th class="span3"><?php echo $key ?></th>
				<td><?php echo $value ?></td>
			</tr>
<?php endforeach; ?>
		</table>
          </div>
<?php endif; ?>
<?php if(count($Run)): ?>
          <div class="control-group">
<table class="table table-bordered table-striped span10">
<?php foreach($Run as $key => $value): ?>
			<tr>
				<th class="span3"><?php echo $key ?></th>
				<td><?php echo $value ?></td>
			</tr>
<?php endforeach; ?>
		</table>
          </div>
<?php endif; ?>
<?php
}
?>
</div>
<?php
/*
<table class="table table-bordered table-striped">
	<tr>
		<th colspan="5"><?php echo __tr("Bunnies") ?></th>
	</tr>
	<tr>
		<th>&nbsp;</th>
		<th><?php echo __tr("MAC") ?></th>
		<th><?php echo __tr("Account") ?></th>
		<th><?php echo __tr("Status") ?></th>
		<th><?php echo __tr("Actions") ?></th>
	</tr>
<?php
	foreach($bunnies as $mac => $data) {
?>
	<tr>
		<td><?php echo $data['voice'] ?></td>
		<td><?php echo $mac ?></td>
		<td><?php echo $data['username'] ?></td>
		<td><?php echo $data['status'] ?></td>
		<td width="15%"><a href="/bunny/index.php?b=<?php echo $mac ?>" class="btn btn-mini btn-primary"><?php echo __tr("Setup") ?></a></td>
	</tr>
<?php } ?>
</table>
<a href="server_plugin.php?p=voicecommand&update" class="btn btn-primary"><?php echo __tr("Update status") ?></a>
*/
?>
