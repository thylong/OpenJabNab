<?php 
$Ztamps = $ojnAPI->GetListofZtamps(false);
$reload = false;
if(isset($_POST['etime'])) {
	$_SESSION['subtab'] = "dayof_schedule";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/dayof/schedule?action=add&time=".$_POST['etime']."&".$ojnAPI->getToken()));
	$reload = true;
}
if(!empty($_GET['rmwbc'])) {
	$_SESSION['subtab'] = "dayof_schedule";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/dayof/schedule?action=del&time=".$_GET['rmwbc']."&".$ojnAPI->getToken()));
	$reload = true;
}
if(isset($_POST['atag'])) {
	$_SESSION['subtab'] = "dayof_rfid";
	if($_POST['atag'] == "")
	{
		Message::AddError(__tr('You must choose a Ztamp'));
	}
	else
	{
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/dayof/rfid?action=add&tag=".$_POST['atag']."&".$ojnAPI->getToken()));
	}
	$reload = true;
}
if(isset($_GET['rtag'])) {
	$_SESSION['subtab'] = "dayof_rfid";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/dayof/rfid?action=del&tag=".$_GET['rtag']."&".$ojnAPI->getToken()));
	$reload = true;
}
if(!isset($_SESSION['subtab']) || !preg_match("|^dayof_|", $_SESSION['subtab'])) {
	$_SESSION['subtab'] = "dayof_schedule";
}
if($reload) {
	header("Location: bunny_plugin.php?p=dayof");
	exit;
}
?>
		<div class="tabbable">
		<ul class="nav nav-tabs">
		  <li<?php echo $_SESSION['subtab'] == 'dayof_schedule' ? ' class="active"' : '' ?>><a href="#schedule" data-toggle="tab"><?php echo __tr('Schedules') ?></a></li>
		  <li<?php echo $_SESSION['subtab'] == 'dayof_rfid' ? ' class="active"' : '' ?>><a href="#rfid" data-toggle="tab"><?php echo __tr('RFID') ?></a></li>
		</ul>
		<br />
		
			<div class="tab-content">
				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'dayof_schedule' ? ' active' : '' ?>" id="schedule">
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="etime" class="control-label"><?php echo __tr("Time for schedule") ?></label>
            <div class="controls">
		<input type="text" name="etime" class="dropdown-timepicker"/>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <button class="btn"><?php echo __tr("Cancel") ?></button>
          </div>
</form>

<?php
$webcasts = $ojnAPI->getApiList("bunny/".$_SESSION['bunny']."/dayof/schedule?action=list&".$ojnAPI->getToken());
if($webcasts){
?>
<center>
<table class="table table-bordered table-striped span11">
	<tr>
		<th colspan="3"><?php echo __tr('Schedule list') ?></th>
	</tr>
	<tr>
		<th><?php echo __tr('Time') ?></th>
		<th><?php echo __tr('Actions') ?></th>
	</tr>
<?php
	$i = 0;
	foreach($webcasts as $time) {
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><?php echo $time ?></td>
		<td width="15%"><a href="bunny_plugin.php?p=dayof&rmwbc=<?php echo $time ?>" class="btn btn-danger btn-small"><i class="icon-trash icon-large"></i> <?php echo __tr('Remove') ?></a></td>
	</tr>
<?php  } ?>
</table>
<?php } ?>
				</div>

				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'dayof_rfid' ? ' active' : '' ?>" id="rfid">

<form method="post" class="form-horizontal">
          <div class="control-group">
            <div class="controls">
<?php echo __tr("on Ztamp") ?> <select name="atag" class="span4"> 
    <option value=""></option>
	<?php foreach($Ztamps as $k=>$v): ?>
	<option value="<?php echo $k; ?>"><?php echo $v; ?> (<?php echo $k; ?>)</option>
	<?php endforeach; ?>
	</select><br />
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
          </div>
</form>

<?php

$Assoc = $ojnAPI->getApiList("bunny/".$_SESSION['bunny']."/dayof/rfid?action=list&".$ojnAPI->getToken());
if(count($Assoc)):
?>
<table class="table table-bordered table-striped span10">
<tr><th colspan="3"><?php echo __tr('Associations') ?></th></tr>
<tr>
	<th><?php echo __tr('Ztamp') ?></th>
	<th><?php echo __tr('Actions') ?></th>
</tr>
<?php foreach($Assoc as $k): ?>
<tr>
	<td><?php echo $Ztamps[$k] . " - " . $k; ?></td>
	<td><a href="bunny_plugin.php?p=dayof&rtag=<?php echo $k ?>" class="btn btn-danger btn-small"><i class="icon-trash icon-large"></i> <?php echo __tr('Remove') ?></a></td>
</tr>
<?php endforeach; ?>

</table>
<?php endif; ?>
</form>



				</div>


			</div>
		</div>
