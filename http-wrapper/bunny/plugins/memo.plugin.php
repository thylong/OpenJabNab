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

if(isset($_GET['et']) && isset($_GET['ed']))
	$edit = $wList[$_GET['ed'] . "|" . $_GET['et']];

?>

<form method="post">
	<div class="form-group row">
		<label class="col-sm-3 col-form-label" for="scheduleT"><?php echo __tr("Add a schedule at (hh:mm)") ?></label>
		<div class="col-sm-2 input-group">
			<div class="input-group-preprend">
				<div class="input-group-text"><i class="icon-time"></i></div>
			</div>
			<input type="text" name="scheduleT" class="timepicker form-control text-center" value="<?php echo isset($_GET['et']) ? $_GET['et'] : '' ?>">
		</div>
		<script type="text/javascript">
			$(".timepicker").timepicker({minuteStep: 1,showMeridian: false});
		</script>
	</div>
	<div class="form-group row">
		<label class="col-sm-3 col-form-label" for="scheduleD"><?php echo __tr("Day") ?></label>
		<div class="col-sm-2 input-group">
			<select name="scheduleD" class="form-control">
				<?php foreach($days as $d => $day): ?>
				<option value="<?php echo $d ?>"<?php echo isset($_GET['ed']) && $_GET['ed'] == $d ? ' selected="selected"' : '' ?>><?php echo $day ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>
	<div class="form-group row">
		<label class="col-sm-3 col-form-label" for="scheduleM"><?php echo __tr("Text to say") ?></label>
		<div class="col-sm-6 input-group">
			<textarea name="scheduleM" rows="6" class="form-control"><?php echo isset($edit) ? $edit : '' ?></textarea>
		</div>
	</div>
	<div class="form-group row">
		<div class="col-sm-1 offset-sm-3">
			<?php if(isset($edit)): ?>
			<input type="hidden" name="scheduleE" value="1"/>
			<button class="btn btn-primary" type="submit"><?php echo __tr("Edit the schedule") ?></button>
			<?php else: ?>
			<button class="btn btn-primary" type="submit"><?php echo __tr("Add the schedule") ?></button>
			<?php endif; ?>
		</div>
	</div>
</form>

<?php if(count($wList)): ?>
<h5><?php echo __tr('Schedules') ?></h5>
<table class="table table-bordered table-striped">
	<tr>
		<th class="col-sm-2"><?php echo __Tr('Day') ?></th>
		<th class="col-sm-1"><?php echo __Tr('Time') ?></th>
		<th><?php echo __tr('Memo') ?></th>
		<th class="col-sm-3"><?php echo __tr('Actions') ?></th>
	</tr>
	<?php foreach($wList as $key => $item):
		list($day, $time) = explode("|", $key);
	?>
	<tr>
		<td><?php echo $days[$day] ?></td>
		<td><?php echo $time ?></td>
		<td><?php echo $item ?></td>
		<td>
			<a class="btn btn-sm btn-primary" href="bunny_plugin.php?p=memo&ed=<?php echo $day ?>&et=<?php echo $time ?>"><i class="icon-edit icon-large"></i> <?php echo __tr('Edit') ?></a>
			<a class="btn btn-sm btn-danger" href="bunny_plugin.php?p=memo&rd=<?php echo $day ?>&rt=<?php echo $time ?>"><i class="icon-trash icon-large"></i> <?php echo __tr('Remove') ?></a>&nbsp;
		</td>
	</tr>
	<?php endforeach; ?>
</table>
	<?php endif; ?>
