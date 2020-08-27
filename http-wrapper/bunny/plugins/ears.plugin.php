<?php
if(isset($_POST['friend'])) {
  Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/ears/setFriend?id=".$_POST['friend']."&".$ojnAPI->getToken()));
  header("Location: bunny_plugin.php?p=ears");
  exit;
}
$friend = $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/ears/getFriend?".$ojnAPI->getToken());
$friend = isset($friend['value']) ? $friend['value'] : '';
if(strlen($friend))
{
  $f = $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/ears/checkFriend?id=".$friend."&".$ojnAPI->getToken());
  $f = isset($f['value']) ? $f['value'] : 'Not friend';
}
?>
<?php if(isset($f) && preg_match("|^Not|", $f)): ?>
<div class="alert alert-error"><?php echo __tr("The bunny '%1' doesn't setup your bunny as his friend", $friend); ?></div>
<?php endif; ?>
<form method="post">
  <div class="form-group row">
    <label class="col-sm-2 col-form-label" for="friend"><?php echo __tr('Friend\'s bunny ID') ?></label>
    <div class="col-sm-2 input-group">
      <input type="text" class="input-medium" id="friend" name="friend" value="<?php echo $friend ?>" placeholder="00xxxxxxxxxx">
    </div>
    <div class="col-sm-6">
      <p class="help-block"><?php echo __tr('You have to enter the MAC address of the bunny, and your friend need to to the same') ?></p>
    </div>
  </div>
	<div class="form-group row">
    <div class="col-sm-1 offset-sm-2">
      <button type="submit" class="btn btn-primary"><?php echo __tr('Save') ?></button>
    </div>
  </div>
</form>
