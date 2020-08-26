<?php
if(isset($_POST['text']) && trim($_POST['text']) != "")
{
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/tts/say?text=".urlencode($_POST['text'])."&".$ojnAPI->getToken()));
	header("Location: bunny_plugin.php?p=tts");
	exit();
}
?>
<form method="post">
  <div class="form-group row">
    <label class="col-sm-2 col-form-label" for="text"><?php echo __tr("Text to send") ?></label>
    <div class="col-sm-8">    
      <input type="text" name="text"  class="form-control" />
    </div>
    <div class="col-sm-2">
      <button class="btn btn-primary" type="submit"><?php echo __tr("Submit") ?></button>
    </div>
  </div>
</form>
