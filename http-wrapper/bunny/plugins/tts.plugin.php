<?php
if(isset($_POST['text']) && trim($_POST['text']) != "")
{
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/tts/say?text=".urlencode($_POST['text'])."&".$ojnAPI->getToken()));
	header("Location: bunny_plugin.php?p=tts");
	exit();
}
?>
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Text to send") ?></label>
            <div class="controls">
		<input type="text" name="text"/>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Send") ?></button>
            <button class="btn"><?php echo __tr("Cancel") ?></button>
          </div>
</form>
