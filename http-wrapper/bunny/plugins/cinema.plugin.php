<?php 
$reload = false;
$days = array("", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday");
if(!empty($_POST['cinematime']) && !empty($_POST['cinemaday']) ) {
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/cinema/addwebcast?time=".$_POST['cinematime']."&day=".$_POST['cinemaday']."&".$ojnAPI->getToken()));
	$reload = true;
}
if(!empty($_GET['rmwbc'])) {
	list($day, $time) = split("\|", $_GET['rmwbc']);
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/cinema/removewebcast?time=".$time."&day=".$day."&".$ojnAPI->getToken()));
	$reload = true;
} 
if($reload) {
	header("Location: bunny_plugin.php?p=cinema");
	exit;
}
?>
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Time for schedule") ?></label>
            <div class="controls">
		<input type="text" name="cinematime" class="dropdown-timepicker"/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Day for schedule") ?></label>
            <div class="controls">
<select name="cinemaday">
<?php foreach($days as $id => $day): ?>
<?php if($id): ?>
<option value="<?php echo $id ?>"><?php echo __tr($day) ?></option>
<?php endif; ?>
<?php endforeach; ?>
</select>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <button class="btn"><?php echo __tr("Cancel") ?></button>
          </div>
</form>

<?php
$webcasts = $ojnAPI->getApiList("bunny/".$_SESSION['bunny']."/cinema/getwebcastslist?".$ojnAPI->getToken());
if($webcasts){
?>
<center>
<table class="table table-bordered table-striped span11">
	<tr>
		<th colspan="3"><?php echo __tr('Schedule list') ?></th>
	</tr>
	<tr>
		<th><?php echo __tr('Time') ?></th>
		<th><?php echo __tr('Name') ?></th>
		<th><?php echo __tr('Actions') ?></th>
	</tr>
<?php
	$i = 0;
	foreach($webcasts as $item) {
	list($day, $time) = split("\|", $item);
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><?php echo $time ?></td>
		<td><?php echo __tr($days[$day]) ?></td>
		<td width="15%"><a href="bunny_plugin.php?p=cinema&rmwbc=<?php echo $item ?>" class="btn btn-danger btn-small"><i class="icon-trash icon-large"></i> <?php echo __tr('Remove') ?></a></td>
	</tr>
<?php  } ?>
</table>
<?php } ?>

</fieldset>
</form>
