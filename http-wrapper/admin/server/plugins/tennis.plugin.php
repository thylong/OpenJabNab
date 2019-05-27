<?php
$reload = false;
if(isset($_POST['debug']))
{
	Message::AddFromApi($ojnAPI->getApiString("plugin/tennis/config?action=debug&set=".$_POST['debug']."&".$ojnAPI->getToken()));
	$reload = true;
}
if($reload)
{
	header("Location: server_plugin.php?p=tennis");
	exit();
}
include('include/message.php');
$debug = $ojnAPI->getApiValue('plugin/tennis/config?action=debug&'.$ojnAPI->getToken());
?>
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Debug setting") ?></label>
            <div class="controls">
		<input type="text" name="debug" value="<?php echo $debug ?>" class="dropdown-timepicker"/>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <button class="btn"><?php echo __tr("Cancel") ?></button>
          </div>
</form>
