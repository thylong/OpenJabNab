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

<ul class="nav nav-tabs">
  <li class="nav-item">
    <a class="nav-link <?php echo $_SESSION['subtab'] == 'dayof_schedule' ? ' active' : '' ?>" href="#schedule" data-toggle="tab"><?php echo __tr('Schedules') ?></a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?php echo $_SESSION['subtab'] == 'dayof_rfid' ? ' active' : '' ?>" href="#rfid" data-toggle="tab"><?php echo __tr('RFID') ?></a>
  </li>
</ul>

<div class="tab-content pt-3">
	<div class="tab-pane<?php echo $_SESSION['subtab'] == 'dayof_schedule' ? ' active' : '' ?>" id="schedule">
		<form method="post">
		<div class="form-group row">
        <label class="col-sm-3 col-form-label" for="scheduleT"><?php echo __tr("Add a schedule at (hh:mm)") ?></label>
        <div class="col-sm-2 input-group">
          <div class="input-group clockpicker" data-autoclose="true">
            <input type="text" name="etime" class="form-control" value="<?php echo date('H:i'); ?>">
            <div class="input-group-text input-group-addon">
              <i class="icon-time"></i>
            </div>
          </div>
          <script type="text/javascript">
            $('.clockpicker').clockpicker({'default': 'now'});
          </script>
        </div>
				<div class="col-sm-1">
					<button class="btn btn-primary" type="submit"><?php echo __tr("Add") ?></button>
				</div>
			</div>
		</form>
		<?php
			$webcasts = $ojnAPI->getApiList("bunny/".$_SESSION['bunny']."/dayof/schedule?action=list&".$ojnAPI->getToken());
			if(!empty($webcasts)):
		?>
		<h5><?php echo __tr('Schedule list') ?></h5>
		<table class="table table-bordered table-striped">
			<tr>
				<th><?php echo __tr('Time') ?></th>
				<th class="col-sm-1"><?php echo __tr('Actions') ?></th>
			</tr>
			<?php foreach($webcasts as $time): ?>
			<tr>
				<td><?php echo $time ?></td>
				<td width="15%"><a href="bunny_plugin.php?p=dayof&rmwbc=<?php echo $time ?>" class="btn btn-danger btn-sm"><i class="icon-trash icon-large"></i> <?php echo __tr('Remove') ?></a></td>
			</tr>
			<?php endforeach; ?>
		</table>
		<?php endif; ?>
	</div>

	<div class="tab-pane<?php echo $_SESSION['subtab'] == 'dayof_rfid' ? ' active' : '' ?>" id="rfid">
		<form method="post">
      <div class="form-group row">
        <label class="col-sm-2 col-form-label" for="atag"><?php echo __tr("on Ztamp") ?></label>
        <div class="col-sm-4 input-group">
          <select name="atag"  class="form-control">
            <option value=""></option>
            <?php foreach($Ztamps as $k=>$v): ?>
            <option value="<?php echo $k; ?>"><?php echo $v; ?> (<?php echo $k; ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-sm-2">
          <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
        </div>
      </div>
    </form>

		<?php
		$Assoc = $ojnAPI->getApiList("bunny/".$_SESSION['bunny']."/dayof/rfid?action=list&".$ojnAPI->getToken());
		if(!empty($Assoc)):
		?>
		<h5><?php echo __tr('Associations') ?></h5>
		<table class="table table-bordered table-striped">
		<tr>
			<th><?php echo __tr('Ztamp') ?></th>
			<th class="col-sm-1"><?php echo __tr('Actions') ?></th>
		</tr>
		<?php foreach($Assoc as $k): ?>
		<tr>
			<td><?php echo $Ztamps[$k] . " - " . $k; ?></td>
			<td><a href="bunny_plugin.php?p=dayof&rtag=<?php echo $k ?>" class="btn btn-danger btn-sm"><i class="icon-trash icon-large"></i> <?php echo __tr('Remove') ?></a></td>
		</tr>
		<?php endforeach; ?>

		</table>
		<?php endif; ?>
	</div>

</div>
