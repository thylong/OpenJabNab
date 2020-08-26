<?php
if(isset($_POST['voix'])) {
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/clock/voice?action=set&name=".$_POST['voix']."&".$ojnAPI->getToken()));
	header("Location: bunny_plugin.php?p=clock");
	exit;
}
$voices = $ojnAPI->getApiList("bunny/".$_SESSION['bunny']."/clock/voice?action=list&".$ojnAPI->getToken());
$voice = $ojnAPI->getApiValue("bunny/".$_SESSION['bunny']."/clock/voice?action=get&".$ojnAPI->getToken());
?>
<form method="post">
  <div class="form-group row">
    <label class="col-sm-2 col-form-label" for="voix"><?php echo __tr("Voice to use") ?></label>
    <div class="col-sm-1">    
      <select name="voix" class="form-control">
        <?php foreach($voices as $voix): ?>
        <option value="<?php echo $voix ?>"<?php echo $voix == $voice ? ' selected="selected"' : ''; ?>><?php echo $voix; ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-sm-4">
      <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
    </div>
  </div>
</form>
