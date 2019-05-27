<?php
$reload = false;

$quota = QUOTA;
if($Infos['status'] == "VIP")
	$quota = 100;
if($Infos['status'] == "Premium")
	$quota = 250;
if($Infos['status'] == "Admin")
	$quota = 1000;

$Ztamps = $ojnAPI->GetListofZtamps(false);
$Files = $ojnAPI->getApiList("bunny/".$_SESSION['bunny']."/music/file?action=list&".$ojnAPI->getToken());
$Groups = $ojnAPI->getApiList("bunny/".$_SESSION['bunny']."/music/file?action=listgroup&".$ojnAPI->getToken());
$user_dir = ROOT_LOCAL . "users/" . md5($_SESSION['login']) . "/";
$user_files = "";
$size = 0;
if (is_dir($user_dir)) {
    if ($dh = opendir($user_dir)) {
        while (($file = readdir($dh)) !== false) {
	    if($file != '..' && $file != '.')
	    {
		$size += filesize($user_dir.$file)/1024/1024;
            	$user_files .="<option value='$file'>$file</option>\n";
	    }
        }
        closedir($dh);
    }
}
//echo preg_replace('/OJN_GROUP_/', '', $_POST['mfile']);
//die();
if(!empty($_POST['a']) && !empty($_POST['tag'])) {
	$_SESSION['subtab'] = "music_action";
	if($_POST['a']=="addtag" && !empty($_POST['mfile']) && in_array($_POST['mfile'],$Files))
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/music/rfid?action=add&tag=".$_POST['tag']."&file=".$_POST['mfile']."&".$ojnAPI->getToken()));
	elseif($_POST['a']=="addtag" && !empty($_POST['mfile']) && $_POST['mfile']=='OJN_RANDOM')
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/music/rfid?action=add&tag=".$_POST['tag']."&file=".$_POST['mfile']."&".$ojnAPI->getToken()));
	elseif($_POST['a']=="addtag" && !empty($_POST['mfile']) && preg_match('/OJN_GROUP_/', $_POST['mfile']) && in_array(preg_replace('/OJN_GROUP_/', '', $_POST['mfile']),$Groups))
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/music/rfid?action=add&tag=".$_POST['tag']."&file=".$_POST['mfile']."&".$ojnAPI->getToken()));
//	elseif($_POST['a']=="rmtag")
//		$retour = $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/music/rfid?action=del&tag=".$_POST['tag']."&".$ojnAPI->getToken());
	else
		Message::AddError(__tr('Incorrect parameters'));
	header("Location: bunny_plugin.php?p=music");
}
if(isset($_GET['rmassoc'])) {
	$_SESSION['subtab'] = "music_action";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/music/rfid?action=del&tag=".$_GET['rmassoc']."&".$ojnAPI->getToken()));
	$reload = true;
}
if(!empty($_POST['f']) && !empty($_POST['rf']) && $_POST['f'] == 'remove') {
	$_SESSION['subtab'] = "music_manager";
	if(unlink($user_dir . $_POST['rf'])) {
		Message::AddSuccess(__tr('File successfuly removed'));
	} else {
		Message::AddError(__tr('Error while removing file'));
	}
	$reload = true;
}
elseif(!empty($_FILES['file'])) {
	$_SESSION['subtab'] = "music_manager";
	if($size +( $_FILES['file']['size'] / 1024/1024) > $quota)
		Message::AddError(__tr('Not enought space for this file'));
	else
	{
// ffmpeg -i input.mp3 -ab 128k output.mp3
		$target_path = $user_dir . preg_replace("| +|", "_", basename( $_FILES['file']['name'])); 
		if(move_uploaded_file($_FILES['file']['tmp_name'], $target_path)) {
			Message::AddSuccess(__tr('The file %1 has been uploaded',  basename( $_FILES['file']['name'])));
		} else{
			Message::AddError(__tr('There was an error uploading the file, please try again!'));
		}
	}
	$reload = true;
}
if($reload) {
	header("Location: bunny_plugin.php?p=music");
	exit;
}
if(!isset($_SESSION['subtab']) || !preg_match("|^music_|", $_SESSION['subtab'])) {
	$_SESSION['subtab'] = "music_action";
}
$Assoc = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/music/rfid?action=list&".$ojnAPI->getToken());

?>

		<div class="tabbable">
		<ul class="nav nav-tabs">
		  <li<?php echo $_SESSION['subtab'] == 'music_action' ? ' class="active"' : '' ?>><a href="#action" data-toggle="tab"><?php echo __tr('Actions') ?></a></li>
		  <li<?php echo $_SESSION['subtab'] == 'music_manager' ? ' class="active"' : '' ?>><a href="#manager" data-toggle="tab"><?php echo __tr('File manager') ?></a></li>
		</ul>
		<br />
		
			<div class="tab-content">
				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'music_action' ? ' active' : '' ?>" id="action">
<form method="post" class="form-horizontal">
<input type="hidden" name="a" value="addtag">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Associate Ztamp") ?></label>
            <div class="controls">
<select name="tag">
    <option value=""><?php echo __tr('Choose a Ztamp') ?></option>
	<?php foreach($Ztamps as $k=>$v): ?>
	<option value="<?php echo $k; ?>"><?php echo $v; ?> (<?php echo $k; ?>)</option>
	<?php endforeach; ?>
	</select>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("To file") ?></label>
            <div class="controls">
<select name="mfile">
<option value=""><?php echo __tr('Choose a file') ?></option>
<option value="OJN_RANDOM"><?php echo __tr('Random file') ?></option>
<?php foreach($Groups as $g) { ?>
<option value="OJN_GROUP_<?php echo $g; ?>"><?php echo __tr("Group '%1'", $g); ?></option>
<?php } ?>
<?php foreach($Files as $f) { ?>
<option value="<?php echo $f; ?>"><?php echo ucfirst(substr($f,0,-4)); ?></option>
<?php } ?>
</select>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Add") ?></button>
          </div>
</form>
<?php if(false && count($Assoc)): ?>
<form method="post" class="form-horizontal">
<input type="hidden" name="a" value="rmtag">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Remove association") ?></label>
            <div class="controls">
<select name="tag">
    <option value=""><?php echo __tr('Choose an association') ?></option>
	<?php foreach($Assoc as $k=>$v): ?>
	<option value="<?php echo $k; ?>"><?php echo $v; ?> (<?php echo $Ztamps[$k] . " - " . $k; ?>)</option>
	<?php endforeach; ?>
	</select>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-danger" type="submit"><?php echo __tr("Remove") ?></button>
          </div>
</form>
<?php endif; ?>

<?php if(count($Assoc)): ?>
<table class="table table-bordered table-striped span10">
<tr><th colspan="3"><?php echo __tr('Associations') ?></th></tr>
<tr>
	<th><?php echo __tr('File') ?></th>
	<th><?php echo __tr('Ztamp') ?></th>
	<th><?php echo __tr('Actions') ?></th>
</tr>
<?php foreach($Assoc as $k=>$v): ?>
<tr>
	<td><?php echo $v == 'OJN_RANDOM' ? __tr('Random file') : preg_match('/OJN_GROUP_/', $v) ? __tr('Random file').' ('.__tr("Group '%1'", preg_replace('/OJN_GROUP_/', '', $v)).')' : $v; ?></td>
	<td><?php echo $Ztamps[$k] . " - " . $k; ?></td>
	<td><a href="bunny_plugin.php?p=music&rmassoc=<?php echo $k ?>" class="btn btn-danger btn-small"><i class="icon-trash icon-large"></i> <?php echo __tr('Remove') ?></a></td>
</tr>
<?php endforeach; ?>

</table>
<?php endif; ?>

				</div>

				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'music_manager' ? ' active' : '' ?>" id="manager">
<div class="span6">
<form method="post" class="form-horizontal" enctype="multipart/form-data">
<input type="hidden" name="f" value="upload">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Upload a file") ?></label>
            <div class="controls">
<input type="file" name="file" maxlength="3000000" accept="audio/mpeg"/>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Add file") ?></button>
          </div>
</form>
<form method="post" class="form-horizontal">
<input type="hidden" name="f" value="remove" />
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Remove a file") ?></label>
            <div class="controls">
<select name="rf"><?php echo $user_files ?></select>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-danger" type="submit"><?php echo __tr("Remove") ?></button>
          </div>
</form>
</div>
<div class="span5">
<?php echo __tr('Quota') ?> : <?php echo __tr('%1 Mb', round($size,2)." / ".$quota) ?><div style="width: 300px; height: 20px; border: 1px solid black;">
<div style="width: <?php echo $size*300/$quota ?>px; background-color: <?php echo $size <= $quota*0.7 ? "green" : ($size > $quota*0.9 ? "red" : "yellow") ?>; height: 20px;">&nbsp;</div>
</div>
</div>
				</div>
			</div>
		</div>
