<?php 
$reload = false;
$data = $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/halloween/halloween?action=get&".$ojnAPI->getToken());
list($min, $max) = preg_split('/;/', $data['ok'],  PREG_SPLIT_NO_EMPTY);
//echo "$min, $max";

if(isset($_POST['min']) && isset($_POST['max']))
{
	if(trim($_POST['min']) == "" || trim($_POST['max']) == "")
	{
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/halloween/halloween?action=del&".$ojnAPI->getToken()));
	} else {
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/halloween/halloween?action=set&min=".$_POST['min']."&max=".$_POST['max']."&".$ojnAPI->getToken()));
	}
	header("Location: bunny_plugin.php?p=halloween");
	exit();
}
?>
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Minimum delay") ?></label>
            <div class="controls">
		<input type="text" name="min"/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Maximum delay") ?></label>
            <div class="controls">
		<input type="text" name="max"/>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <button class="btn"><?php echo __tr("Cancel") ?></button>
          </div>
</form>
