<?php
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
if(isset($_POST['scheduleT']) && isset($_POST['scheduleZ']) && isset($_POST['scheduleD']))
{
	$_SESSION['subtab'] = "horoscope_schedule";
	$option = $_POST['scheduleZ'] . "|" . implode(';', $_POST['scheduleTh']);
	if($_POST['scheduleD'] >= 0)
	{
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/horoscope/schedule?action=add&option=".$option."&time=".$_POST['scheduleT']."&day=".$_POST['scheduleD']."&".$ojnAPI->getToken()));
	}
	else
	{
		if($_POST['scheduleD'] == -1)
		{
			$id = 0;
			for($d = 1; $d<=5; $d++)
			{
				Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/horoscope/schedule?action=add&option=".$option."&time=".$_POST['scheduleT']."&day=".$d."&".$ojnAPI->getToken()));
			}
		}
		if($_POST['scheduleD'] == -2)
		{
			$id = 0;
			for($d = 6; $d<=7; $d++)
			{
				Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/horoscope/schedule?action=add&option=".$option."&time=".$_POST['scheduleT']."&day=".$d."&".$ojnAPI->getToken()));
			}
		}
	}
	$reload = true;
}
else if(isset($_POST['defaultTh']) && isset($_POST['defaultZ']) )
{
	$_SESSION['subtab'] = "horoscope_config";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/horoscope/zodiac?action=set&zodiac=".$_POST['defaultZ']."&themes=".implode(",", $_POST['defaultTh'])."&".$ojnAPI->getToken()));
	$reload = true;
}
else if(isset($_GET['rt']) && isset($_GET['rd']))
{
	$_SESSION['subtab'] = "horoscope_schedule";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/horoscope/schedule?action=del&day=".$_GET['rd']."&time=".$_GET['rt']."&".$ojnAPI->getToken()));
	$reload = true;
}
if(isset($_GET['rtag'])) {
	$_SESSION['subtab'] = "horoscope_rfid";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/horoscope/rfid?action=del&tag=".$_GET['rtag']."&".$ojnAPI->getToken()));
	$reload = true;
}
if(isset($_POST['atag']) && isset($_POST['aZ'])) {
	$_SESSION['subtab'] = "horoscope_rfid";
	if($_POST['atag'] == "")
	{
		Message::AddError(__tr('You must choose a Ztamp'));
	}
	else
	{
		$option = $_POST['aZ'] . "|" . implode(';', $_POST['aTh']);
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/horoscope/rfid?action=add&tag=".$_POST['atag']."&option=".$option."&".$ojnAPI->getToken()));
	}
	$reload = true;
}

if(!isset($_SESSION['subtab']) || !preg_match("|^horoscope_|", $_SESSION['subtab'])) {
	$_SESSION['subtab'] = "horoscope_config";
}
if($reload)
{
	header("Location: bunny_plugin.php?p=horoscope");
	exit();
}
$default = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/horoscope/zodiac?action=get&".$ojnAPI->getToken());
$defaultZ = $default['Zodiac'];
$defaultTh = preg_split('/,/', $default['Themes']);
$wList = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/horoscope/schedule?action=list&".$ojnAPI->getToken());
$Assoc = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/horoscope/rfid?action=list&".$ojnAPI->getToken());

$zodiacs = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/horoscope/zodiac?action=list&".$ojnAPI->getToken());
$themes  = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/horoscope/zodiac?action=theme&".$ojnAPI->getToken());
foreach($themes as $id => $name) {
	if(!strlen(trim($name))) {
		$themes[$id] = $id;
	}
}

?>

		<div class="tabbable">
		<ul class="nav nav-tabs">
		  <li<?php echo $_SESSION['subtab'] == 'horoscope_config' ? ' class="active"' : '' ?>><a href="#config" data-toggle="tab"><?php echo __tr('Setup') ?></a></li>
		  <li<?php echo $_SESSION['subtab'] == 'horoscope_schedule' ? ' class="active"' : '' ?>><a href="#schedule" data-toggle="tab"><?php echo __tr('Schedules') ?></a></li>
		  <li<?php echo $_SESSION['subtab'] == 'horoscope_rfid' ? ' class="active"' : '' ?>><a href="#rfid" data-toggle="tab"><?php echo __tr('RFID') ?></a></li>
		</ul>
		<br />
		
			<div class="tab-content">
				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'horoscope_config' ? ' active' : '' ?>" id="config">
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Default Sign") ?></label>
            <div class="controls">
		<select name="defaultZ">
		<option value=""></option>
		<?php foreach($zodiacs as $z => $name) { ?>
			<option value="<?php echo $z ?>"<?php echo $z == $defaultZ ? ' selected="selected"' : '' ?>><?php echo $name ?></option>
		<?php } ?>
		</select>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Default themes") ?></label>
            <div class="controls">
		<?php foreach($themes as $t => $name) { ?>
			<input name="defaultTh[]" type="checkbox" value="<?php echo $t ?>"<?php echo in_array($t, $defaultTh) ? ' checked="checked"' : '' ?> id="theme_<?php echo $t ?>"><label style="display: inline-block" for="theme_<?php echo $t ?>">&nbsp;<?php echo $name ?></label><br />
		<?php } ?>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <a href="bunny_plugin.php?p=horoscope" class="btn"><?php echo __tr("Cancel") ?></a>
          </div>
</form>


				</div>

				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'horoscope_schedule' ? ' active' : '' ?>" id="schedule">
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Add a schedule at (hh:mm)") ?></label>
            <div class="controls">
		<input type="text" name="scheduleT" class="input-xlarge span6"/>
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
            <label for="input01" class="control-label"><?php echo __tr("Sign") ?></label>
            <div class="controls">
		<select name="scheduleZ">
		<option value=""></option>
		<?php foreach($zodiacs as $z => $name) { ?>
			<option value="<?php echo $z ?>"<?php echo $z == $defaultZ ? ' selected="selected"' : '' ?>><?php echo $name ?></option>
		<?php } ?>
		</select>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Themes") ?></label>
            <div class="controls">
		<?php foreach($themes as $t => $name) { ?>
			<input name="scheduleTh[]" type="checkbox" value="<?php echo $t ?>"<?php echo in_array($t, $defaultTh) ? ' checked="checked"' : '' ?> id="theme_<?php echo $t ?>"><label style="display: inline-block" for="theme_<?php echo $t ?>">&nbsp;<?php echo $name ?></label><br />
		<?php } ?>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Add the schedule") ?></button>
            <a href="bunny_plugin.php?p=horoscope" class="btn"><?php echo __tr("Cancel") ?></a>
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
		<th><?php echo __tr('Sign') ?></th>
		<th><?php echo __tr('Themes') ?></th>
		<th><?php echo __tr('Actions') ?></th>
	</tr>
<?php
	$i = 0;
	foreach($wList as $when => $option)
	{
		list($day, $time) = explode("|", $when);
		list($zodiac, $ths) = preg_split("/\|/", $option);
		$list = array();
		foreach(preg_split('/;/', $ths) as $th)
			$list[] = $themes[$th];
?>
	<tr>
		<td><?php echo $days[$day] ?></td>
		<td><?php echo $time ?></td>
		<td><?php echo $zodiacs[$zodiac] ?></td>
		<td><?php echo implode(', ', $list) ?></td>
		<td width="15%"><a class="btn btn-danger" href="bunny_plugin.php?p=horoscope&rd=<?php echo $day ?>&rt=<?php echo $time ?>"><?php echo __tr('Remove') ?></a></td>
	</tr>
<?php  } ?>
</table>
<?php } ?>

				</div>

				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'horoscope_rfid' ? ' active' : '' ?>" id="rfid">

<form method="post" class="form-horizontal">

          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Sign") ?></label>
            <div class="controls">
		<select name="aZ">
		<option value=""></option>
		<?php foreach($zodiacs as $z => $name) { ?>
			<option value="<?php echo $z ?>"<?php echo $z == $defaultZ ? ' selected="selected"' : '' ?>><?php echo $name ?></option>
		<?php } ?>
		</select>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Themes") ?></label>
            <div class="controls">
		<?php foreach($themes as $t => $name) { ?>
			<input name="aTh[]" type="checkbox" value="<?php echo $t ?>"<?php echo in_array($t, $defaultTh) ? ' checked="checked"' : '' ?> id="theme_<?php echo $t ?>"><label style="display: inline-block" for="theme_<?php echo $t ?>">&nbsp;<?php echo $name ?></label><br />
		<?php } ?>
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
            <a href="bunny_plugin.php?p=horoscope" class="btn"><?php echo __tr("Cancel") ?></a>
          </div>


</form>

<?php if(count($Assoc)): ?>
<table class="table table-bordered table-striped span10">
<tr><th colspan="3"><?php echo __tr('Associations') ?></th></tr>
<tr>
	<th><?php echo __tr('Ztamp') ?></th>
	<th><?php echo __tr('Sign') ?></th>
	<th><?php echo __tr('Themes') ?></th>
	<th><?php echo __tr('Actions') ?></th>
</tr>
<?php foreach($Assoc as $k=>$v):
	list($zodiac, $ths) = preg_split("/\|/", $v);
	$list = array();
	foreach(preg_split('/;/', $ths) as $th)
		$list[] = $themes[$th];
?>
<tr>
	<td><?php echo $Ztamps[$k] . " - " . $k; ?></td>
	<td><?php echo $zodiacs[$zodiac] ?></td>
	<td><?php echo implode(', ', $list) ?></td>
	<td><a href="bunny_plugin.php?p=horoscope&rtag=<?php echo $k ?>" class="btn btn-danger btn-small"><i class="icon-trash icon-large"></i> <?php echo __tr('Remove') ?></a></td>
</tr>
<?php endforeach; ?>

</table>
<?php endif; ?>
</form>



				</div>

			</div>
		</div>
