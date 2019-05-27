<?php 

if(!isset($_SESSION['bunny']))
{
	header("Location: /bunny/index.php");
	exit();
}
$reload = false;
$Ztamps = $ojnAPI->GetListofZtamps(false);
$days = array(
0 => __tr('Every day'),
-1 => __tr('During the week'),
-2 => __tr('During the week-end'),
1 => __tr("Monday"),
2 => __tr("Tuesday"),
3 => __tr("Wednesday"),
4 => __tr("Thursday"),
5 => __tr("Friday"),
6 => __tr("Saturday"),
7 => __tr("Sunday")
);
if(isset($_POST['scheduleT']) && isset($_POST['scheduleP'])) {
	$_SESSION['subtab'] = "webradio_schedule";
	if($_POST['scheduleP'] != "")
	{
		if($_POST['scheduleD'] >= 0)
		{
			Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/webradio/schedule?action=add&day=".$_POST['scheduleD']."&time=".$_POST['scheduleT']."&name=".urlencode(preg_replace('/OJN_/', '', $_POST['scheduleP']))."&".$ojnAPI->getToken()));
		}
		else
		{
			if($_POST['scheduleD'] == -1)
			{
				$id = 0;
				for($d = 1; $d<=5; $d++)
				{
					Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/webradio/schedule?action=add&day=".$d."&time=".$_POST['scheduleT']."&name=".urlencode(preg_replace('/OJN_/', '', $_POST['scheduleP']))."&".$ojnAPI->getToken()), $id);
				}
			}
			if($_POST['scheduleD'] == -2)
			{
				$id = 0;
				for($d = 6; $d<=7; $d++)
				{
					Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/webradio/schedule?action=add&day=".$d."&time=".$_POST['scheduleT']."&name=".urlencode(preg_replace('/OJN_/', '', $_POST['scheduleP']))."&".$ojnAPI->getToken()), $id);
				}
			}
		}
	}
	else
	{
		Message::AddError(__tr('You must choose a preset'));
	}
	$reload = true;
}
if(isset($_GET['rtag'])) {
	$_SESSION['subtab'] = "webradio_rfid";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/webradio/rfid?action=del&tag=".$_GET['rtag']."&".$ojnAPI->getToken()));
	$reload = true;
}
if(isset($_POST['addname']) && isset($_POST['addurl'])) {
	$_SESSION['subtab'] = "webradio_preset";


	if($_POST['addname'] == "")
	{
		Message::AddError(__tr('You must choose a name'));
	}
	else if($_POST['addurl'] == "")
	{
		Message::AddError(__tr('You must choose a preset'));
	}
	else
	{
		if(preg_match("/\.m3u$/", $_POST['addurl']))
		{
			$playlist = file_get_contents($_POST['addurl']);
			if($list = preg_split("/\n/", $playlist, -1, PREG_SPLIT_NO_EMPTY))
			{
				$id = Message::AddWarning(__tr('You enter the url of a playlist, here is the content. Please choose the link you want to add :'));
				Message::AddWarning('<ul>', $id);
				foreach($list as $radio)
				{
					$form = '<form style="margin: 0px; display: inline-block" method="post"><input type="hidden" name="addurl" value="'.$radio.'"><input type="hidden" name="addname" value="'.$_POST['addname'].'"><input type="submit" class="btn btn-mini btn-warning" value="'.__tr('Use this radio').'"></form>';
					Message::AddWarning('<li>'.$radio.'&nbsp; &nbsp; &nbsp;'.$form.'</li>', $id);
				}
				Message::AddWarning('</ul>', $id);
			}
			else
			{
				Message::AddError(__tr('You enter the url of a playlist, but I can\'t find any available radio'));
			}
		}
		else if(preg_match("/\.pls$/", $_POST['addurl']))
		{
			$playlist = file_get_contents($_POST['addurl']);
			if(preg_match_all("/File\d+=(.*)\n/isU", $playlist, $list, PREG_SET_ORDER))
			{
				$id = Message::AddWarning(__tr('You enter the url of a playlist, here is the content. Please choose the link you want to add :'));
				Message::AddWarning('<ul>', $id);
				foreach($list as $radio)
				{
					$radio = $radio[1];
					$form = '<form style="margin: 0px; display: inline-block" method="post"><input type="hidden" name="addurl" value="'.$radio.'"><input type="hidden" name="addname" value="'.$_POST['addname'].'"><input type="submit" class="btn btn-mini btn-warning" value="'.__tr('Use this radio').'"></form>';
					Message::AddWarning('<li>'.$radio.'&nbsp; &nbsp; &nbsp;'.$form.'</li>', $id);
				}
				Message::AddWarning('</ul>', $id);
			}
			else
			{
				Message::AddError(__tr('You enter the url of a playlist, but I can\'t find any available radio'));
			}
		}
		else
		{
			Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/webradio/preset?action=add&name=".urlencode($_POST['addname'])."&url=".urlencode($_POST['addurl'])."&".$ojnAPI->getToken()));
		}
	}
	$reload = true;
}
if(isset($_POST['atag']) && isset($_POST['aurl'])) {
	$_SESSION['subtab'] = "webradio_rfid";
	if($_POST['atag'] == "")
	{
		Message::AddError(__tr('You must choose a Ztamp'));
	}
	else if($_POST['aurl'] == "")
	{
		Message::AddError(__tr('You must choose a preset'));
	}
	else
	{
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/webradio/rfid?action=add&tag=".$_POST['atag']."&preset=".urlencode(preg_replace('/OJN_/', '', $_POST['aurl']))."&".$ojnAPI->getToken()));
	}
	$reload = true;
}
if(isset($_GET['rp'])) {
	$_SESSION['subtab'] = "webradio_preset";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/webradio/preset?action=del&name=".urlencode($_GET['rp'])."&".$ojnAPI->getToken()));
	$reload = true;
}
else if(isset($_GET['d'])) {
	$_SESSION['subtab'] = "webradio_preset";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/webradio/preset?action=setdefault&name=".urlencode(preg_replace('/OJN_/', '', $_GET['d']))."&".$ojnAPI->getToken()));
	$reload = true;
}
else if(isset($_GET['play'])) {
	$_SESSION['subtab'] = "webradio_preset";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/webradio/preset?action=play&name=".urlencode(preg_replace('/OJN_/', '', $_GET['play']))."&".$ojnAPI->getToken()));
	$reload = true;
}
else if(isset($_GET['rw'])) {
	$_SESSION['subtab'] = "webradio_schedule";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/webradio/schedule?action=del&day=".$_GET['rwd']."&time=".$_GET['rw']."&".$ojnAPI->getToken()));
	$reload = true;
}
$default = $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/webradio/preset?action=getdefault&".$ojnAPI->getToken());
$default = isset($default['value']) ? (string)($default['value']) : '';
$pList = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/webradio/preset?action=list&".$ojnAPI->getToken());
$wList = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/webradio/schedule?action=list&".$ojnAPI->getToken());
$Assoc = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/webradio/rfid?action=list&".$ojnAPI->getToken());



if(!isset($_SESSION['subtab']) || !preg_match("|^webradio_|", $_SESSION['subtab'])) {
	$_SESSION['subtab'] = "webradio_preset";
}
if($reload)
{
	header("Location: bunny_plugin.php?p=webradio");
	exit();
}

?>

		<div class="tabbable">
		<ul class="nav nav-tabs">
		  <li<?php echo $_SESSION['subtab'] == 'webradio_preset' ? ' class="active"' : '' ?>><a href="#preset" data-toggle="tab"><?php echo __tr('Presets') ?></a></li>
		  <li<?php echo $_SESSION['subtab'] == 'webradio_schedule' ? ' class="active"' : '' ?>><a href="#schedule" data-toggle="tab"><?php echo __tr('Schedules') ?></a></li>
		  <li<?php echo $_SESSION['subtab'] == 'webradio_rfid' ? ' class="active"' : '' ?>><a href="#rfid" data-toggle="tab"><?php echo __tr('RFID') ?></a></li>
		</ul>
		<br />
		
			<div class="tab-content">
				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'webradio_preset' ? ' active' : '' ?>" id="preset">
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Name") ?></label>
            <div class="controls">
		<input type="text" name="addname" class="input-xlarge span6"/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Url") ?></label>
            <div class="controls">
		<input type="text" name="addurl" class="input-xlarge span6"/>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Add a preset") ?></button>
            <button class="btn"><?php echo __tr("Cancel") ?></button>
          </div>
</form>

<?php
if(!empty($pList)) {
?>
<hr />
<center>
<table class="table table-bordered table-striped span11">
	<tr>
		<th colspan="4"><?php echo __tr('URL List') ?></th>
	</tr>
	<tr>
		<th class="span2"><?php echo __tr('Name') ?></th>
		<th class="span5"><?php echo __tr('URL') ?></th>
		<th colspan="2"><?php echo __tr('Actions') ?></th>
	</tr>
<?php
	$i = 0;
	foreach($pList as $key => $item) {
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><?php echo preg_replace('/OJN_/', '', $key) ?></td>
		<td><?php echo urldecode($item) ?></td>
		<td class="span2">
<?php if(!preg_match('/^OJN_/', $key)): ?>
			<a class="btn btn-danger" href="bunny_plugin.php?p=webradio&rp=<?php echo $key ?>"><?php echo __tr("Remove") ?></a>
<?php endif; ?>
			<a class="btn btn-success" href="bunny_plugin.php?p=webradio&play=<?php echo $key ?>"><?php echo __tr("Play") ?></a>
		</td>
		<td class="span2"><?php if($default != $key) { ?><a class="btn btn-primary" href="bunny_plugin.php?p=webradio&d=<?php echo $key ?>"><?php echo __tr("Set as default") ?></a><?php } else { ?><?php echo __tr("Default url") ?><?php } ?></td>
	</tr>
<?php } ?>
</table>
<?php
}
?>
				</div>

				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'webradio_schedule' ? ' active' : '' ?>" id="schedule">
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Add a schedule at (hh:mm)") ?></label>
            <div class="controls">
		<input type="text" name="scheduleT" maxlength="5" style="width:50px" />
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Day") ?></label>
            <div class="controls">
		<select name="scheduleD">
		<?php foreach($days as $d => $day) { ?>
			<option value="<?php echo $d ?>"><?php echo $day ?></option>
		<?php } ?>
		</select>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Preset") ?></label>
            <div class="controls">
<select name="scheduleP">
	<option value=""></option>
	<?php if(!empty($pList))
	foreach($pList as $key => $item) { ?>
		<option value="<?php echo $key ?>"><?php echo preg_replace("/OJN_/", "", $key); ?></option>
	<?php } ?>
</select>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <button class="btn"><?php echo __tr("Cancel") ?></button>
          </div>
</form>

<?php
if(!empty($wList)){
?>
<hr />
<center>
<table class="table table-bordered table-striped span11">
	<tr>
		<th colspan="4"><?php echo __tr('Schedules') ?></th>
	</tr>
	<tr>
		<th><?php echo __tr('Day') ?></th>
		<th><?php echo __tr('Time') ?></th>
		<th><?php echo __tr('Name') ?></th>
		<th><?php echo __tr('Actions') ?></th>
	</tr>
<?php
	$i = 0;
	foreach($wList as $when => $name) {
		list($day, $time) = preg_split("/\|/", $when);
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><?php echo $days[$day] ?></td>
		<td><?php echo $time ?></td>
		<td><?php echo preg_replace('/OJN_/', '', $name) ?></td>
		<td width="15%"><a href="bunny_plugin.php?p=webradio&rwd=<?php echo $day ?>&rw=<?php echo $time ?>" class="btn btn-danger"><?php echo __tr('Remove') ?></a></td>
	</tr>
<?php  } ?>
</table>
<?php } ?>
				</div>

				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'webradio_rfid' ? ' active' : '' ?>" id="rfid">

<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Launch") ?></label>
            <div class="controls">
	<select name="aurl" class="span4">
	<option value=""></option>
	<?php  if(!empty($pList))
	foreach($pList as $key => $item) { ?>
		<option value="<?php echo $key ?>"><?php echo preg_replace("/OJN_/", "", $key); ?></option>
	<?php } ?>
</select> <?php echo __tr("on Ztamp") ?> <select name="atag" class="span4"> 
    <option value=""></option>
	<?php if(count($Ztamps)): ?>
	<?php foreach($Ztamps as $k=>$v): ?>
	<option value="<?php echo $k; ?>"><?php echo $v; ?> (<?php echo $k; ?>)</option>
	<?php endforeach; ?>
	<?php endif; ?>
	</select><br />
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
          </div>
</form>

<?php if(count($Assoc)): ?>
<table class="table table-bordered table-striped span10">
<tr><th colspan="3"><?php echo __tr('Associations') ?></th></tr>
<tr>
	<th><?php echo __tr('Url') ?></th>
	<th><?php echo __tr('Ztamp') ?></th>
	<th><?php echo __tr('Actions') ?></th>
</tr>
<?php foreach($Assoc as $k=>$v): ?>
<tr>
	<td><?php echo preg_replace('/OJN_/', '', $v); ?></td>
	<td><?php echo $Ztamps[$k] . " - " . $k; ?></td>
	<td><a href="bunny_plugin.php?p=webradio&rtag=<?php echo $k ?>" class="btn btn-danger btn-small"><i class="icon-trash icon-large"></i> <?php echo __tr('Remove') ?></a></td>
</tr>
<?php endforeach; ?>

</table>
<?php endif; ?>
</form>



				</div>


			</div>
		</div>
