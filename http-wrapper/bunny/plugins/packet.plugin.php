<?php
$reload = false;
if(isset($_POST['type']) && $_POST['type'] != "" && trim($_POST['content']) != "")
{
	$_SESSION['packet_type'] = $_POST['type'];
	if($_POST['type'] == "pack")
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/packet/sendPacket?data=".urlencode($_POST['content'])."&".$ojnAPI->getToken()));
	elseif($_POST['type'] == "expert")
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/packet/sendExpert?msg=".urlencode($_POST['content'])."&".$ojnAPI->getToken()));
	elseif($_POST['type'] == "ambient") {
		$service = 17;
		$value = 0;
		if(preg_match("/ /", $_POST['content'])) {
			list($service, $value) = preg_split("/ /", $_POST['content']);
		}
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/packet/sendAmbient?service=".$service."&value=".$value."&".$ojnAPI->getToken()));
	}
	elseif($_POST['type'] == "msg")
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/packet/sendMessage?msg=".urlencode($_POST['content'])."&".$ojnAPI->getToken()));
	$reload = true;
}
else if(count($_POST))
{
	if(count($_POST) && $_POST['type'] == "")
	{
		Message::AddError("You must choose a type");
	}
	if(count($_POST) && $_POST['content'] == "")
	{
		Message::AddError("You must enter a content");
	}
	$reload = true;
}

if($reload)
{
	header("Location: bunny_plugin.php?p=packet");
	exit;
}
if(!isset($_SESSION['packet_type']))
	$_SESSION['packet_type'] = 'msg';

?>
<form method="post">
  <div class="form-group row">
    <label class="col-sm-1 col-form-label" for="type"><?php echo __tr("Packet type") ?></label>
    <div class="col-sm-2 input-group">
      <select name="type" class="form-control">
				<option value=""><?php echo __tr("Choose type of packet") ?></option>
				<option value="pack" <?php echo $_SESSION['packet_type'] == 'pack' ? 'selected="selected"' : '' ?>><?php echo __tr("Packet") ?></option>
				<option value="ambient" <?php echo $_SESSION['packet_type'] == 'ambient' ? 'selected="selected"' : '' ?>><?php echo __tr("Ambient") ?></option>
				<option value="msg" <?php echo $_SESSION['packet_type'] == 'msg' ? 'selected="selected"' : '' ?>><?php echo __tr('Message') ?></option>
				<option value="expert" <?php echo $_SESSION['packet_type'] == 'expert' ? 'selected="selected"' : '' ?>><?php echo __tr('Expert') ?></option>
			</select>
    </div>
	</div>
	<div class="form-group row">
    <label class="col-sm-1 col-form-label" for="content"><?php echo __tr("Content") ?></label>
    <div class="col-sm-4 input-group">
			<textarea name="content" class="form-control"></textarea>
    </div>
	</div>
	<div class="form-group row">
    <div class="col-sm-1 offset-sm-1">
      <button class="btn btn-primary" type="submit"><?php echo __tr("Send") ?></button>
    </div>
  </div>
</form>
