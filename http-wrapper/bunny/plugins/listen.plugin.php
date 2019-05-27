<?php
$reload = false;
if(!empty($_POST['gain'])) {
	Message::addFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/listen/config?action=set&value=".$_POST['gain']."&".$ojnAPI->getToken()));
	$reload = true;
}
if($reload)
{
	header("Location: bunny_plugin.php?p=listen");
	exit();
}
$gains = array(
	10 => __tr("A few close and loud sounds"),
	50 => __tr("Some close sounds"),
	100 => __tr("All discussions in a 5meter perimeter"),
	250 => __tr("Nearly all sounds"),
	0 => __tr("No sounds"),
);
$gain = $ojnAPI->getApiValue("bunny/".$_SESSION['bunny']."/listen/config?action=get&".$ojnAPI->getToken());
?>

<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Which sounds to listen for ?") ?></label>
            <div class="controls">
<select name="gain"> 
<?php foreach($gains as $k => $v): ?>
<option value="<?php echo $k ?>" <?php if ($gain==$k) echo 'selected'; ?> ><?php echo $v ?></option>
<?php endforeach; ?>
</select>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <button class="btn"><?php echo __tr("Cancel") ?></button>
          </div>
</form>
