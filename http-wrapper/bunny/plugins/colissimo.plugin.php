<?php
$Ztamps = $ojnAPI->GetListofZtamps(false);
$Ztamps = array_merge(array("" => __tr('None')), $Ztamps);
$reload = false;
if(!empty($_POST['addcode'])) {
	$_SESSION['subtab'] = 'colissimo_suivi';
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/colissimo/addcode?code=".strtoupper($_POST['addcode'])."&name=".urlencode($_POST['addname'])."&".$ojnAPI->getToken()));
	$reload = true;
}
if(!empty($_POST['webcastT'])) {
	$_SESSION['subtab'] = 'colissimo_settings';
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/colissimo/addwebcast?time=".$_POST['webcastT']."&".$ojnAPI->getToken()));
	$reload = true;
}
if(isset($_POST['frequency']) && $_POST['frequency'] != "") {
	$_SESSION['subtab'] = 'colissimo_settings';
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/colissimo/setfrequency?min=".$_POST['frequency']."&".$ojnAPI->getToken()));
	$reload = true;
}
if(isset($_POST['RFID'])) {
	$_SESSION['subtab'] = 'colissimo_settings';
	if($_POST['RFID'] == '')
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/colissimo/removerfid?tag=".$_POST['RFID']."&".$ojnAPI->getToken()));
	else
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/colissimo/addrfid?tag=".$_POST['RFID']."&".$ojnAPI->getToken()));
	$reload = true;
}
if(!empty($_GET['cp'])) {
	$_SESSION['subtab'] = 'colissimo_suivi';
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/colissimo/instantTrack?code=".urlencode($_GET['cp'])."&".$ojnAPI->getToken()));
	$reload = true;
}
if(!empty($_GET['rp'])) {
	$_SESSION['subtab'] = 'colissimo_suivi';
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/colissimo/removecode?code=".urlencode($_GET['rp'])."&".$ojnAPI->getToken()));
	$reload = true;
}
if(!empty($_GET['rw'])) {
	$_SESSION['subtab'] = 'colissimo_settings';
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/colissimo/removewebcast?time=".$_GET['rw']."&".$ojnAPI->getToken()));
	$reload = true;
}
if($reload)
{
	header("Location: bunny_plugin.php?p=colissimo");
	exit();
}
$frequency = $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/colissimo/getfrequency?".$ojnAPI->getToken());
$pList = $ojnAPI->getApiList("bunny/".$_SESSION['bunny']."/colissimo/getcodeslist?".$ojnAPI->getToken());
$psList = $ojnAPI->getApiList("bunny/".$_SESSION['bunny']."/colissimo/getstatuslist?".$ojnAPI->getToken());
$wList = $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/colissimo/getwebcastslist?".$ojnAPI->getToken());

$frequencies = array(
0 => __tr('No repeat'),
10 => __tr('%1 minutes', 10),
15 => __tr('%1 minutes', 15),
30 => __tr('%1 minutes', 30),
60 => __tr('%1 hour', 1),
90 => __tr('%1 hour %2 minutes', 1, 30),
120 => __tr('%1 hours', 2),
180 => __tr('%1 hours', 3),
240 => __tr('%1 hours', 4),
);

if(!isset($_SESSION['subtab']) || !preg_match("|^colissimo_|", $_SESSION['subtab'])) {
	$_SESSION['subtab'] = "colissimo_suivi";
}
?>

		<div class="tabbable">
		<ul class="nav nav-tabs">
		  <li<?php echo $_SESSION['subtab'] == 'colissimo_suivi' ? ' class="active"' : '' ?>><a href="#suivi" data-toggle="tab"><?php echo __tr('Package List') ?></a></li>
		  <li<?php echo $_SESSION['subtab'] == 'colissimo_settings' ? ' class="active"' : '' ?>><a href="#settings" data-toggle="tab"><?php echo __tr('Settings') ?></a></li>
		</ul>
		<br />
		
			<div class="tab-content">
				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'colissimo_suivi' ? ' active' : '' ?>" id="suivi">
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Add a package") ?></label>
            <div class="controls">
		<input type="text" name="addcode" class="input-xlarge span2"/>
		<p class="help-block"><?php echo __tr("Enter the 13 characters code") ?></p>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Name") ?></label>
            <div class="controls">
		<input type="text" name="addname" class="input-xlarge span2"/>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <button class="btn"><?php echo __tr("Cancel") ?></button>
          </div>
</form>
				</div>

				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'colissimo_settings' ? ' active' : '' ?>" id="settings">
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Frequency") ?></label>
            <div class="controls">
<select name="frequency">
	<?php foreach($frequencies as $k=>$v): ?>
	<option value="<?php echo $k; ?>" <?php echo (int)$frequency['value'] == $k ? ' selected="selected"' : '' ?>><?php echo $v; ?></option>
	<?php endforeach; ?>
	</select>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <button class="btn"><?php echo __tr("Cancel") ?></button>
          </div>
</form>
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Add a webcast") ?></label>
            <div class="controls">
		<input type="text" name="webcastT" maxlength="5" style="width:50px" />
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <button class="btn"><?php echo __tr("Cancel") ?></button>
          </div>
</form>
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Select RFID to use") ?></label>
            <div class="controls">
<select name="RFID">
	<?php foreach($Ztamps as $k=>$v): ?>
	<option value="<?php echo $k; ?>"><?php echo $v.($k != "" ? "($k)" : ""); ?></option>
	<?php endforeach; ?>
	</select>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <button class="btn"><?php echo __tr("Cancel") ?></button>
          </div>
</form>
				</div>

			</div>
		</div>
<?php
if(!empty($pList)) {
?>
<hr />
<center>
<table class="table table-bordered table-striped span11">
	<tr>
		<th colspan="5"><?php echo __tr('Package List') ?></th>
	</tr>
	<tr>
		<th class="span2"><?php echo __tr('Code') ?></th>
		<th class="span3"><?php echo __tr('Name') ?></th>
		<th class="span3"><?php echo __tr('Status') ?></th>
		<th class="span1"><?php echo __tr('Date') ?></th>
		<th class="span3"><?php echo __tr('Actions') ?></th>
	</tr>
<?php
	$i = 0;
	foreach($pList as $id => $colis) {
		$status = $psList[$id];
		list($status, $date) = preg_split("|[\|]|", (string)$status->value );
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><?php echo $colis->key ?></td>
		<td><?php echo $colis->value ?></td>
		<td><?php echo $status ?></td>
		<td><?php echo $date ?></td>
		<td><a href="bunny_plugin.php?p=colissimo&rp=<?php echo $colis->key ?>" class="btn btn-danger"><?php echo __tr('Remove') ?></a>&nbsp;<a href="bunny_plugin.php?p=colissimo&cp=<?php echo $colis->key ?>" class="btn btn-success"><?php echo __tr('Check package') ?></a></td>
	</tr>
<?php } ?>
</table>
<?php
}
if(isset($wList['list']->item)){
?>
<hr />
<center>
<table class="table table-bordered table-striped span11">
	<tr>
		<th colspan="3"><?php echo __tr('Webcasts') ?></th>
	</tr>
	<tr>
		<th><?php echo __tr('Time') ?></th>
		<th><?php echo __tr('Name') ?></th>
		<th><?php echo __tr('Actions') ?></th>
	</tr>
<?php
	$i = 0;
	foreach($wList['list']->item as $item) {
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><?php echo urldecode($item->key) ?></td>
		<td><?php echo $item->value ?></td>
		<td width="15%"><a href="bunny_plugin.php?p=colissimo&rw=<?php echo $item->key ?>" class="btn btn-danger"><?php echo __tr('Remove') ?></a></td>
	</tr>
<?php  } ?>
</table>
<?php } ?>
</fieldset>
