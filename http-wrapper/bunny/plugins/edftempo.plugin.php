<?php
$reload = false;
$Ztamps = $ojnAPI->GetListofZtamps(false);
$whens = array(
0 => __tr('Today'),
1 => __tr('Tomorrow'),
2 => __tr('Today and tomorrow'),
);
$whats = array(
0 => __tr('Tempo color'),
1 => __tr('EJP status'),
2 => __tr('Tempo color and EJP status'),
);
$zones = array(
0 => '',
1 => __tr('Nord'),
2 => __tr('Provence, Alpes, Côte d\'Azur'),
3 => __tr('Ouest'),
4 => __tr('Sud'),
);
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
if(isset($_POST['scheduleT']) && isset($_POST['scheduleW']) && isset($_POST['scheduleD']))
{
	$_SESSION['subtab'] = "edftempo_schedule";
	if($_POST['scheduleD'] >= 0)
	{
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/edftempo/schedule?action=add&when=".$_POST['scheduleW']."&what=".$_POST['scheduleS']."&zone=".$_POST['scheduleZ']."&time=".$_POST['scheduleT']."&day=".$_POST['scheduleD']."&".$ojnAPI->getToken()));
	}
	else
	{
		if($_POST['scheduleD'] == -1)
		{
			$id = 0;
			for($d = 1; $d<=5; $d++)
			{
				Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/edftempo/schedule?action=add&when=".$_POST['scheduleW']."&what=".$_POST['scheduleS']."&zone=".$_POST['scheduleZ']."&time=".$_POST['scheduleT']."&day=".$d."&".$ojnAPI->getToken()));
			}
		}
		if($_POST['scheduleD'] == -2)
		{
			$id = 0;
			for($d = 6; $d<=7; $d++)
			{
				Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/edftempo/schedule?action=add&when=".$_POST['scheduleW']."&what=".$_POST['scheduleS']."&zone=".$_POST['scheduleZ']."&time=".$_POST['scheduleT']."&day=".$d."&".$ojnAPI->getToken()));
			}
		}
	}
	$reload = true;
}
else if(isset($_POST['automode']) && isset($_POST['defaultW']) )
{
	$_SESSION['subtab'] = "edftempo_config";
	if(strlen($_POST['automode'])) {
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/edftempo/option?action=cron&set=".$_POST['automode']."&".$ojnAPI->getToken()));
	}
	if(strlen($_POST['defaultW'])) {
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/edftempo/option?action=set&when=".$_POST['defaultW']."&".$ojnAPI->getToken()));
	}
	if(strlen($_POST['defaultS'])) {
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/edftempo/option?action=set&what=".$_POST['defaultS']."&".$ojnAPI->getToken()));
	}
	if(isset($_POST['defaultZ'])) {
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/edftempo/option?action=set&zone=".$_POST['defaultZ']."&".$ojnAPI->getToken()));
	}
	$reload = true;
}
else if(isset($_GET['rt']) && isset($_GET['rd']))
{
	$_SESSION['subtab'] = "edftempo_schedule";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/edftempo/schedule?action=del&day=".$_GET['rd']."&time=".$_GET['rt']."&".$ojnAPI->getToken()));
	$reload = true;
}
if(isset($_GET['rtag'])) {
	$_SESSION['subtab'] = "edftempo_rfid";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/edftempo/rfid?action=del&tag=".$_GET['rtag']."&".$ojnAPI->getToken()));
	$reload = true;
}
if(isset($_POST['atag']) && isset($_POST['aW'])) {
	$_SESSION['subtab'] = "edftempo_rfid";
	if($_POST['atag'] == "")
	{
		Message::AddError(__tr('You must choose a Ztamp'));
	}
	else
	{
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/edftempo/rfid?action=add&tag=".$_POST['atag']."&when=".$_POST['aW']."&what=".$_POST['aS']."&zone=".$_POST['aZ']."&".$ojnAPI->getToken()));
	}
	$reload = true;
}

if(!isset($_SESSION['subtab']) || !preg_match("|^edftempo_|", $_SESSION['subtab'])) {
	$_SESSION['subtab'] = "edftempo_config";
}
if($reload)
{
	header("Location: bunny_plugin.php?p=edftempo");
	exit();
}
$default = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/edftempo/option?action=get&".$ojnAPI->getToken());
$cron = (string)$ojnAPI->getApiValue("bunny/".$_SESSION['bunny']."/edftempo/option?action=cron&".$ojnAPI->getToken());
$wList = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/edftempo/schedule?action=list&".$ojnAPI->getToken());
$Assoc = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/edftempo/rfid?action=list&".$ojnAPI->getToken());

?>

		<div class="tabbable">
		<ul class="nav nav-tabs">
		  <li<?php echo $_SESSION['subtab'] == 'edftempo_config' ? ' class="active"' : '' ?>><a href="#config" data-toggle="tab"><?php echo __tr('Setup') ?></a></li>
		  <li<?php echo $_SESSION['subtab'] == 'edftempo_schedule' ? ' class="active"' : '' ?>><a href="#schedule" data-toggle="tab"><?php echo __tr('Schedules') ?></a></li>
		  <li<?php echo $_SESSION['subtab'] == 'edftempo_rfid' ? ' class="active"' : '' ?>><a href="#rfid" data-toggle="tab"><?php echo __tr('RFID') ?></a></li>
		</ul>
		<br />
		
			<div class="tab-content">
				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'edftempo_config' ? ' active' : '' ?>" id="config">
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Automatic mode") ?></label>
            <div class="controls">
		<select name="automode">
			<option value="0"<?php echo $cron == 0 ? ' selected="selected"' : '' ?>><?php echo __tr('No') ?></option>
			<option value="1"<?php echo $cron ? ' selected="selected"' : '' ?>><?php echo __tr('Yes') ?></option>
		</select>
		<p class="help-block"><?php echo __tr('Automatic mode will announce the Tempo color of tomorrow as soon as it\'s available') ?></a>.</p>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Default information") ?></label>
            <div class="controls">
		<select name="defaultW">
		<?php foreach($whens as $w => $when) { ?>
			<option value="<?php echo $w ?>"<?php echo $w === (int)$default['When'] ? ' selected="selected"' : '' ?>><?php echo $when ?></option>
		<?php } ?>
		</select>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Default service") ?></label>
            <div class="controls">
		<select name="defaultS">
		<?php foreach($whats as $w => $what) { ?>
			<option value="<?php echo $w ?>"<?php echo $w === (int)$default['What'] ? ' selected="selected"' : '' ?>><?php echo $what ?></option>
		<?php } ?>
		</select>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Default EJP Zone") ?></label>
            <div class="controls">
		<select name="defaultZ">
		<?php foreach($zones as $w => $zone) { ?>
			<option value="<?php echo $w ?>"<?php echo $w === (int)$default['Zone'] ? ' selected="selected"' : '' ?>><?php echo $zone ?></option>
		<?php } ?>
		</select>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <a href="bunny_plugin.php?p=edftempo" class="btn"><?php echo __tr("Cancel") ?></a>
          </div>
</form>


				</div>

				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'edftempo_schedule' ? ' active' : '' ?>" id="schedule">
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Add a schedule at (hh:mm)") ?></label>
            <div class="controls">
 <div class="input-append bootstrap-timepicker">
<input id="timepicker2" type="text" name="scheduleT" class="input-small"><span class="add-on">
<i class="icon-time"></i>
</span>
</div>
<?php $ojnTemplate->setJs('<script type="text/javascript">jQuery("#timepicker2").timepicker({minuteStep: 1,showSeconds: false,showMeridian: false});</script>'); ?>
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
            <label for="input01" class="control-label"><?php echo __tr("Information to say") ?></label>
            <div class="controls">
		<select name="scheduleW">
		<?php foreach($whens as $w => $when) { ?>
			<option value="<?php echo $w ?>"><?php echo $when ?></option>
		<?php } ?>
		</select>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Service") ?></label>
            <div class="controls">
		<select name="scheduleS">
		<?php foreach($whats as $w => $what) { ?>
			<option value="<?php echo $w ?>"><?php echo $what ?></option>
		<?php } ?>
		</select>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("EJP Zone") ?></label>
            <div class="controls">
		<select name="scheduleZ">
		<?php foreach($zones as $w => $zone) { ?>
			<option value="<?php echo $w ?>"><?php echo $zone ?></option>
		<?php } ?>
		</select>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Add the schedule") ?></button>
            <a href="bunny_plugin.php?p=edftempo" class="btn"><?php echo __tr("Cancel") ?></a>
          </div>
</form>

<?php
if(count($wList)){
?>
<hr />
<center>
<table class="table table-bordered table-striped span11">
	<tr>
		<th colspan="4"><?php echo __tr('Schedules') ?></th>
	</tr>
	<tr>
		<th><?php echo __Tr('Day') ?></th>
		<th><?php echo __Tr('Time') ?></th>
		<th><?php echo __tr('Option') ?></th>
		<th><?php echo __tr('Actions') ?></th>
	</tr>
<?php
	$i = 0;
	foreach($wList as $when => $option)
	{
		list($day, $time) = explode("|", $when);
		list($when, $what, $zone) = preg_split("/;/", $option);
?>
	<tr>
		<td><?php echo $days[$day] ?></td>
		<td><?php echo $time ?></td>
		<td><?php echo $whens[$when] . '<br />' .$whats[$what] . '<br />' . $zones[$zone] ?></td>
		<td width="15%"><a class="btn btn-danger" href="bunny_plugin.php?p=edftempo&rd=<?php echo $day ?>&rt=<?php echo $time ?>"><?php echo __tr('Remove') ?></a></td>
	</tr>
<?php  } ?>
</table>
<?php } ?>

				</div>

				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'edftempo_rfid' ? ' active' : '' ?>" id="rfid">

<form method="post" class="form-horizontal">

          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Information to say") ?></label>
            <div class="controls">
		<select name="aW">
		<?php foreach($whens as $w => $when) { ?>
			<option value="<?php echo $w ?>"><?php echo $when ?></option>
		<?php } ?>
		</select>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Service") ?></label>
            <div class="controls">
		<select name="aS">
		<?php foreach($whats as $w => $what) { ?>
			<option value="<?php echo $w ?>"><?php echo $what ?></option>
		<?php } ?>
		</select>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("EJP Zone") ?></label>
            <div class="controls">
		<select name="aZ">
		<?php foreach($zones as $w => $zone) { ?>
			<option value="<?php echo $w ?>"><?php echo $zone ?></option>
		<?php } ?>
		</select>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Ztamp") ?></label>
            <div class="controls">
<select name="atag" class="span4"> 
    <option value=""></option>
	<?php foreach($Ztamps as $k=>$v): ?>
	<option value="<?php echo $k; ?>"><?php echo $v; ?> (<?php echo $k; ?>)</option>
	<?php endforeach; ?>
</select>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <a href="bunny_plugin.php?p=edftempo" class="btn"><?php echo __tr("Cancel") ?></a>
          </div>


</form>

<?php if(count($Assoc)): ?>
<table class="table table-bordered table-striped span10">
<tr><th colspan="3"><?php echo __tr('Associations') ?></th></tr>
<tr>
	<th><?php echo __tr('Ztamp') ?></th>
	<th><?php echo __tr('Option') ?></th>
	<th><?php echo __tr('Actions') ?></th>
</tr>
<?php foreach($Assoc as $k=>$v): ?>
<?php list($when, $what, $zone) = preg_split("/;/", $v); ?>
<tr>
	<td><?php echo $Ztamps[$k] . " - " . $k; ?></td>
	<td><?php echo $whens[$when] . '<br />' .$whats[$what] . '<br />' . $zones[$zone] ?></td>
	<td><a href="bunny_plugin.php?p=edftempo&rtag=<?php echo $k ?>" class="btn btn-danger btn-small"><i class="icon-trash icon-large"></i> <?php echo __tr('Remove') ?></a></td>
</tr>
<?php endforeach; ?>

</table>
<?php endif; ?>
</form>



				</div>

			</div>
		</div>
