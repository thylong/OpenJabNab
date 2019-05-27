<?php
$reload = false;
if(isset($_POST['addurl'])) {
	$_SESSION['subtab'] = "ledcustom_service";
	if(strlen(trim($_POST['addurl']))) {
		if(strlen(trim($_POST['adddelay']))) {
			Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/ledcustom/service?action=add&url=".urlencode($_POST['addurl'])."&service=".$_POST['addservice']."&interval=".$_POST['adddelay']."&".$ojnAPI->getToken()));
		} else {
			Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/ledcustom/service?action=add&url=".urlencode($_POST['addurl'])."&service=".$_POST['addservice']."&".$ojnAPI->getToken()));
		}
	} else {
		Message::AddError(__tr("URL can't be empty"));
	}
	$reload = true;
}
else if(isset($_GET['rp'])) {
	$_SESSION['subtab'] = "ledcustom_service";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/ledcustom/service?action=remove&service=".urlencode($_GET['rp'])."&".$ojnAPI->getToken()));
	$reload = true;
}
if(isset($_POST['addchor'])) {
	$_SESSION['subtab'] = "ledcustom_chor";
	if(strlen(trim($_POST['addchor']))) {
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/ledcustom/chor?action=add&leds=".urlencode($_POST['addchor'])."&service=".$_POST['addservice']."&tempo=".$_POST['adddelay']."&value=".$_POST['addvalue']."&".$ojnAPI->getToken()));
	} else {
		Message::AddError(__tr("Chor can't be empty"));
	}
	$reload = true;
}
else if(isset($_GET['rc'])) {
	$_SESSION['subtab'] = "ledcustom_chor";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/ledcustom/chor?action=del&service=".urlencode($_GET['rc'])."&value=".urlencode($_GET['rv'])."&".$ojnAPI->getToken()));
	$reload = true;
}
$chors = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/ledcustom/chor?action=list&".$ojnAPI->getToken());
$services = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/ledcustom/service?action=list&".$ojnAPI->getToken());

if(!isset($_SESSION['subtab']) || !preg_match("|^ledcustom_|", $_SESSION['subtab'])) {
	$_SESSION['subtab'] = "ledcustom_service";
}
if($reload)
{
	header("Location: bunny_plugin.php?p=ledcustom");
	exit();
}
require_once dirname(realpath(__FILE__)).'/led.php';
getLedScript();
?>

		<div class="tabbable">
		<ul class="nav nav-tabs">
		  <li<?php echo $_SESSION['subtab'] == 'ledcustom_service' ? ' class="active"' : '' ?>><a href="#url" data-toggle="tab"><?php echo __tr('URL List') ?></a></li>
		  <li<?php echo $_SESSION['subtab'] == 'ledcustom_chor' ? ' class="active"' : '' ?>><a href="#chor" data-toggle="tab"><?php echo __tr('Choregraphies') ?></a></li>
<?php if($Infos['isAdmin']): ?>
		  <li<?php echo $_SESSION['subtab'] == 'ledcustom_chors' ? ' class="active"' : '' ?>><a href="#chors" data-toggle="tab"><?php echo __tr('All choregraphies') ?></a></li>
<?php endif; ?>
		</ul>
		<br />
		
			<div class="tab-content">
				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'ledcustom_service' ? ' active' : '' ?>" id="url">
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Add an url") ?></label>
            <div class="controls">
		<input type="text" name="addurl" class="input-xlarge span6"/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Service") ?></label>
            <div class="controls">
		<input type="text" name="addservice" class="input-xlarge span6"/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Delay") ?> (sec)</label>
            <div class="controls">
		<input type="text" name="adddelay" class="input-xlarge span6"/>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <button class="btn"><?php echo __tr("Cancel") ?></button>
          </div>
</form>
				</div>


				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'ledcustom_chor' ? ' active' : '' ?>" id="chor">
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Service") ?></label>
            <div class="controls">
		<input type="text" name="addservice" class="input-xlarge span6"/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Value") ?></label>
            <div class="controls">
		<input type="text" name="addvalue" class="input-xlarge span6"/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Leds") ?></label>
            <div class="controls">
		<input type="text" name="addchor" id="addchor" class="input-xlarge span6" onkeyup="updateChor()"/>
<canvas height="30" width="100" id="canvas_temp" style="float: right"></canvas>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Delay") ?> (sec)</label>
            <div class="controls">
		<input type="text" name="adddelay" id="adddelay" class="input-xlarge span6" onkeyup="updateChor()"/>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <button class="btn"><?php echo __tr("Cancel") ?></button>
          </div>
</form>
				</div>

				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'ledcustom_chors' ? ' active' : '' ?>" id="chors">
<div id="chors">
</div>
          <div class="form-actions">
			<button class="btn" onclick="requestChors()"><?php echo __tr('Request') ?></button>
          </div>
				</div>

			</div>
		</div>

<?php
if(!empty($services)) {
?>
<hr />
<center>
<table class="table table-bordered table-striped span11">
	<tr>
		<th colspan="4"><?php echo __tr('URL List') ?></th>
	</tr>
	<tr>
		<th class="span1"><?php echo __tr('Service') ?></th>
		<th class="span5"><?php echo __tr('URL') ?></th>
		<th class="span1"><?php echo __tr('Delay') ?></th>
		<th ><?php echo __tr('Actions') ?></th>
	</tr>
<?php
	$i = 0;
	foreach($services as $k => $item) {
		list($delay, $url) = preg_split('/\|SEPARATOR\|/', urldecode($item));
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><?php echo $k ?></td>
		<td><?php echo $url ?></td>
		<td><?php echo sectotime($delay, true) ?></td>
		<td class="span1"><a class="btn btn-danger" href="bunny_plugin.php?p=ledcustom&rp=<?php echo $k ?>"><?php echo __tr("Remove") ?></a></td>
	</tr>
<?php } ?>
</table>
<?php
}
if(!empty($chors)) {
?>
<hr />
<center>
<table class="table table-bordered table-striped span11">
	<tr>
		<th colspan="6"><?php echo __tr('Choregraphies List') ?></th>
	</tr>
	<tr>
		<th class="span1"><?php echo __tr('Service') ?></th>
		<th class="span1"><?php echo __tr('Value') ?></th>
		<th class="span1"><?php echo __tr('Delay') ?></th>
		<th class="span8" colspan="2"><?php echo __tr('LEDs') ?></th>
		<th ><?php echo __tr('Actions') ?></th>
	</tr>
<?php
	$i = 0;
	foreach($chors as $k => $item) {
		$choregraphies = preg_split('/\|/', urldecode($item));
		foreach($choregraphies as $id => $chor) {
		list($delay, $leds) = preg_split('/;/', $chor);
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><?php echo $k ?></td>
		<td><?php echo $id ?></td>
		<td><?php echo $delay ?></td>
		<td><?php echo $leds ?></td>
		<td><?php getLed($leds, $delay, $id, $k) ?></td>
		<td class="span1"><a class="btn btn-danger" href="bunny_plugin.php?p=ledcustom&rc=<?php echo $k ?>&rv=<?php echo $id ?>"><?php echo __tr("Remove") ?></a></td>
	</tr>
<?php } } ?>
</table>
<?php
}
?>
</fieldset>

<?php $ojnTemplate->setJs("<script>var chor_temp = new Leds('temp', $('#adddelay').val(), $('#addchor').val().split(','));chor_temp.play();function updateChor() { chor_temp.update($('#adddelay').val(), $('#addchor').val().split(',')); } function requestChors() { $.get('".(ROOT_WWW_EXTAPI."bunny/".$_SESSION['bunny']."/ledcustom/chor?action=request&".$ojnAPI->getToken())."'); setTimeout(\"fetchChors()\", 2000); } function fetchChors() { $.get('ledcustom.ajax.php?sn=".$_SESSION['bunny']."', function(data) {
  $('#chors').html(data);
})} </script>"); ?>
