<?php
if(isset($_POST['voix'])) {
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/clock/voice?action=set&name=".$_POST['voix']."&".$ojnAPI->getToken()));
	header("Location: bunny_plugin.php?p=clock");
	exit;
}
$voices = $ojnAPI->getApiList("bunny/".$_SESSION['bunny']."/clock/voice?action=list&".$ojnAPI->getToken());
$voice = $ojnAPI->getApiValue("bunny/".$_SESSION['bunny']."/clock/voice?action=get&".$ojnAPI->getToken());
?>
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="voix" class="control-label"><?php echo __tr("Voice to use") ?></label>
            <div class="controls">
<select name="voix">
<?php foreach($voices as $voix) { ?>
<option value="<?php echo $voix ?>"<?php echo $voix == $voice ? ' selected="selected"' : ''; ?>><?php echo $voix; ?></option>
<?php } ?>
</select>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <button class="btn"><?php echo __tr("Cancel") ?></button>
          </div>
</form>
