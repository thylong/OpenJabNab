<?php
$reload = false;
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
if(isset($_POST['scheduleT']) && isset($_POST['scheduleM']) && isset($_POST['scheduleD']))
{
	$function = isset($_POST['scheduleE']) ? 'edit' : 'add';
	if($_POST['scheduleD'] >= 0)
	{
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/memo/schedule?action=".$function."&message=".urlencode($_POST['scheduleM'])."&time=".$_POST['scheduleT']."&day=".$_POST['scheduleD']."&".$ojnAPI->getToken()));
	}
	else
	{
		if($_POST['scheduleD'] == -1)
		{
			$id = 0;
			for($d = 1; $d<=5; $d++)
			{
				Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/memo/schedule?action=".$function."&message=".urlencode($_POST['scheduleM'])."&time=".$_POST['scheduleT']."&day=".$d."&".$ojnAPI->getToken()));
			}
		}
		if($_POST['scheduleD'] == -2)
		{
			$id = 0;
			for($d = 6; $d<=7; $d++)
			{
				Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/memo/schedule?action=".$function."&message=".urlencode($_POST['scheduleM'])."&time=".$_POST['scheduleT']."&day=".$d."&".$ojnAPI->getToken()));
			}
		}
	}
	$reload = true;
}
else if(isset($_GET['rt']) && isset($_GET['rd'])) {
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/memo/schedule?action=del&day=".$_GET['rd']."&time=".$_GET['rt']."&".$ojnAPI->getToken()));
	$reload = true;
}
/*
else if(isset($_GET['et']) && isset($_GET['ed'])) {
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/memo/schedule?action=del&day=".$_GET['rd']."&time=".$_GET['rt']."&".$ojnAPI->getToken()));
	$reload = true;
}
*/
if($reload)
{
	header("Location: bunny_plugin.php?p=memo");
	exit();
}
$wList = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/memo/schedule?action=list&".$ojnAPI->getToken());

if(isset($_GET['et']) && isset($_GET['ed'])) {
	$edit = $wList[$_GET['ed'] . "|" . $_GET['et']];
}

?>

<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Add a schedule at (hh:mm)") ?></label>
            <div class="controls">
		<input type="text" name="scheduleT" class="input-xlarge span6" value="<?php echo isset($_GET['et']) ? $_GET['et'] : '' ?>"/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Day") ?></label>
            <div class="controls">
		<select name="scheduleD">
		<?php foreach($days as $d => $day) { ?>
			<option value="<?php echo $d ?>"<?php echo isset($_GET['ed']) && $_GET['ed'] == $d ? ' selected="selected"' : '' ?>><?php echo $day ?></option>
		<?php } ?>
		</select>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Text to say") ?></label>
            <div class="controls">
		<textarea name="scheduleM" class="input-xlarge span6"><?php echo isset($edit) ? $edit : '' ?></textarea>
            </div>
          </div>
          <div class="form-actions">
<?php if(isset($edit)): ?>
	<input type="hidden" name="scheduleE" value="1"/>
            <button class="btn btn-primary" type="submit"><?php echo __tr("Edit the schedule") ?></button>
<?php else: ?>
            <button class="btn btn-primary" type="submit"><?php echo __tr("Add the schedule") ?></button>
<?php endif; ?>
            <a href="bunny_plugin.php?p=memo" class="btn"><?php echo __tr("Cancel") ?></a>
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
		<th><?php echo __tr('Name') ?></th>
		<th><?php echo __tr('Actions') ?></th>
	</tr>
<?php
	$i = 0;
	foreach($wList as $key => $item) {
	list($day, $time) = explode("|", $key);
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><?php echo $days[$day] ?></td>
		<td><?php echo $time ?></td>
		<td><?php echo $item ?></td>
		<td width="25%">
			<a class="btn btn-danger" href="bunny_plugin.php?p=memo&rd=<?php echo $day ?>&rt=<?php echo $time ?>"><?php echo __tr('Remove') ?></a>&nbsp;
			<a class="btn btn-success" href="bunny_plugin.php?p=memo&ed=<?php echo $day ?>&et=<?php echo $time ?>"><?php echo __tr('Edit') ?></a>
		</td>
	</tr>
<?php  } ?>
</table>
<?php } ?>
