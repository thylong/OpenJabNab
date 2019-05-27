<?php
$reload = false;
if(isset($_POST['debug']))
{
	Message::AddFromApi($ojnAPI->getApiString("plugin/foot/config?action=debug&set=".$_POST['debug']."&".$ojnAPI->getToken()));
	$reload = true;
}
if(isset($_POST['groups']))
{
	$groups = implode(',', array_keys($_POST['group']));
	Message::AddFromApi($ojnAPI->getApiString("plugin/foot/group?action=set&group=".$groups."&".$ojnAPI->getToken()));
	$reload = true;
}
if($reload)
{
	header("Location: server_plugin.php?p=foot");
	exit();
}
include('include/message.php');
$debug = $ojnAPI->getApiValue('plugin/foot/config?action=debug&'.$ojnAPI->getToken());
$groups = $ojnAPI->getApiMapped('plugin/foot/group?action=list&'.$ojnAPI->getToken());
$groups_display = array_keys($ojnAPI->getApiMapped('plugin/foot/group?action=get&'.$ojnAPI->getToken()));
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

<form method="post" class="form-horizontal">
<input type="hidden" name="groups" value="1">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Groups") ?></label>
            <div class="controls">
<?php foreach($groups as $code => $group): ?>
		<input type="checkbox" name="group[<?php echo $code ?>]" <?php if(in_array($code, $groups_display)): ?>checked="checked" <?php endif; ?>> <?php echo $group ?><br />
<?php endforeach; ?>
<?php ?>
<?php ?>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <button class="btn"><?php echo __tr("Cancel") ?></button>
          </div>
</form>
