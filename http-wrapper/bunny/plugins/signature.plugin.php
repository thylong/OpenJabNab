<?php
$reload = false;

$quand = array('before' => __tr('Before'), 'after' => __tr('After'));

if($Infos['status'] == "VIP")
  $quota = 100;
else if($Infos['status'] == "Premium")
  $quota = 250;
else if($Infos['status'] == "Admin")
  $quota = 1000;
else
  $quota = QUOTA;

$Ztamps = $ojnAPI->GetListofZtamps(false);
$Files = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/signature/sound?action=list&".$ojnAPI->getToken());
$pluginFiles = $ojnAPI->getApiMapped("plugin/signature/sound?action=list&".$ojnAPI->getToken());
$user_dir = ROOT_LOCAL . "users/" . md5($_SESSION['login']) . "/";
$user_files = "";
$user_files_not = "";
$size = 0;
if (is_dir($user_dir))
{
  if ($dh = opendir($user_dir))
  {
    while (($file = readdir($dh)) !== false)
    {
      if($file != '..' && $file != '.')
      {
        $size += filesize($user_dir.$file)/1024/1024;
        $user_files .="<option value='$file'>$file</option>\n";
        if(!in_array($file, $Files))
          $user_files_not .="<option value='$file'>$file</option>\n";
      }
    }
    closedir($dh);
  }
}
if(isset($_POST['sfile']) && isset($_POST['when']) && isset($_POST['sender'])) {
	$_SESSION['subtab'] = "signature_setup";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/signature/config?action=add&type=".$_POST['when']."&name=".urlencode($_POST['sfile'])."&sender=".urlencode($_POST['sender'])."&".$ojnAPI->getToken()));
	header("Location: bunny_plugin.php?p=signature");
}
if(isset($_POST['name']) && isset($_POST['nurl'])) {
	$_SESSION['subtab'] = "signature_files";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/signature/sound?action=add&name=".urlencode($_POST['name'])."&url=".urlencode($_POST['nurl'])."&".$ojnAPI->getToken()));
	header("Location: bunny_plugin.php?p=signature");
}
if(isset($_POST['name']) && isset($_POST['nfile'])) {
	$_SESSION['subtab'] = "signature_files";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/signature/sound?action=add&name=".urlencode($_POST['name'])."&file=".$_POST['nfile']."&".$ojnAPI->getToken()));
	header("Location: bunny_plugin.php?p=signature");
}
if(isset($_GET['rmsign'])) {
	$_SESSION['subtab'] = "signature_setup";
	list($type, $sender) = explode('_', $_GET['rmsign']);
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/signature/config?action=del&sender=".$sender."&type=".$type."&".$ojnAPI->getToken()));
	$reload = true;
}
if(isset($_GET['rmfile'])) {
	$_SESSION['subtab'] = "signature_files";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/signature/sound?action=del&name=".urlencode($_GET['rmfile'])."&".$ojnAPI->getToken()));
	$reload = true;
}
if(!empty($_POST['f']) && !empty($_POST['rf']) && $_POST['f'] == 'remove') {
	$_SESSION['subtab'] = "signature_manager";
	if(unlink($user_dir . $_POST['rf'])) {
		Message::AddSuccess(__tr('File successfuly removed'));
	} else {
		Message::AddError(__tr('Error while removing file'));
	}
	$reload = true;
}
elseif(!empty($_FILES['file'])) {
	$_SESSION['subtab'] = "signature_manager";
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
	header("Location: bunny_plugin.php?p=signature");
	exit;
}
if(!isset($_SESSION['subtab']) || !preg_match("|^signature_|", $_SESSION['subtab'])) {
	$_SESSION['subtab'] = "signature_setup";
}
$Assoc = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/signature/config?action=list&".$ojnAPI->getToken());

$plugins = $ojnAPI->getListOfPlugins(false, $ojnTemplate->getLanguage());
if(is_array($plugins)) {
	asort($plugins);
}
$bunnyPlugins = $ojnAPI->getListOfBunnyEnabledPlugins(false);
?>
<div class="tabbable">
	<ul class="nav nav-tabs">
    <li class="nav-item <?php echo $_SESSION['subtab'] == 'signature_setup' ? ' active' : '' ?>">
      <a class="nav-link" href="#setup" data-toggle="tab"><?php echo __tr('Setup') ?></a>
    </li>
		<li class="nav-item <?php echo $_SESSION['subtab'] == 'signature_files' ? ' active' : '' ?>">
      <a class="nav-link" href="#files" data-toggle="tab"><?php echo __tr('Files') ?></a>
    </li>
		<li class="nav-item <?php echo $_SESSION['subtab'] == 'signature_manager' ? ' active' : '' ?>">
      <a class="nav-link" href="#manager" data-toggle="tab"><?php echo __tr('File manager') ?></a>
    </li>
	</ul>
	<div class="tab-content">
    <br />
    <div class="tab-pane<?php echo $_SESSION['subtab'] == 'signature_setup' ? ' active' : '' ?>" id="setup">
      <form method="post" class="form-inline">
        <div class="form-group row">
          <label class="col-sm-2 col-form-label" for="sfile"><?php echo __tr("Play file") ?></label>
          <div class="col-sm-2">
            <select name="sfile" class="form-control">
              <option value=""><?php echo __tr('Choose a file') ?></option>
              <optgroup label="<?php echo __tr('My files') ?>">
                <?php foreach($Files as $f => $file): ?>
                <option value="<?php echo $f; ?>"><?php echo $f ?></option>
                <?php endforeach ?>
              </optgroup>
              <optgroup label="<?php echo __tr('Plugin files') ?>">
                <?php foreach($pluginFiles as $f => $file) { ?>
                <option value="<?php echo $f; ?>"><?php echo $f ?></option>
                <?php } ?>
              </optgroup>
            </select>
          </div>
          <div class="col-sm-7">
            <select name="when" class="form-control">
              <?php foreach($quand as $k=>$v): ?>
                <option value="<?php echo $k; ?>"><?php echo $v; ?></option>
              <?php endforeach; ?>
            </select>
            &nbsp;
            <select name="sender" class="form-control">
              <?php foreach($plugins as $k=>$p):
                if($k != 'signature'):
              ?>
              <option value="<?php echo $k; ?>"><?php echo $p; ?></option>
              <?php
                endif;
              endforeach; ?>
            </select>
          </div>
          <div class="col-sm-1">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Add") ?></button>
          </div>
        </div>
      </form>
      <?php if(count($Assoc)): ?>
      <br />
      <h5><?php echo __tr('Signatures') ?></h5>
      <table class="table table-bordered table-striped mt-3">
        <tr>
          <th><?php echo __tr('When') ?></th>
          <th><?php echo __tr('File') ?></th>
          <th><?php echo __tr('Actions') ?></th>
        </tr>
        <?php foreach($Assoc as $k=>$v):
          list($when, $sender) = explode('_', $k);
        ?>
        <tr>
          <td><?php echo __tr(ucfirst($when)) . " \"" . $plugins[$sender] . "\""; ?></td>
          <td><?php echo $v; ?></td>
          <td><a href="bunny_plugin.php?p=signature&rmsign=<?php echo $k ?>" class="btn btn-danger btn-small"><i class="icon-trash icon-large"></i> <?php echo __tr('Remove') ?></a></td>
        </tr>
        <?php endforeach; ?>
      </table>
      <?php endif; ?>
		</div>

    <div class="tab-pane<?php echo $_SESSION['subtab'] == 'signature_files' ? ' active' : '' ?>" id="files">
      <form method="post">
        <div class="form-group row">
          <label class="col-sm-1 col-form-label" for="name"><?php echo __tr("Name") ?></label>
          <div class="col-sm-2">
            <input type="text" name="name"  class="form-control">
          </div>
          <label class="col-sm-1 col-form-label" for="nfile"><?php echo __tr("For file") ?></label>
          <div class="col-sm-4">
            <select name="nfile"  class="form-control">
              <?php echo $user_files_not ?>
            </select>
          </div>
          <div class="col-sm-4 text-right">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Add") ?></button>
          </div>
        </div>
      </form>
      <form method="post">
        <div class="form-group row">
          <label class="col-sm-1 col-form-label" for="name"><?php echo __tr("Name") ?></label>
          <div class="col-sm-2">
            <input type="text" name="name" class="form-control">
          </div>
          <label class="col-sm-1 col-form-label" for="nurl"><?php echo __tr("For url") ?></label>
          <div class="col-sm-6">
            <input type="text" name="nurl"  class="form-control">
          </div>
          <div class="col-sm-2 text-right">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Add") ?></button>
          </div>
        </div>
      </form>

      <?php if(count($Files)): ?>
      <h5><?php echo __tr('Files') ?></h5>
      <table class="table table-bordered table-striped span10">
        <tr>
          <th><?php echo __tr('Name') ?></th>
          <th><?php echo __tr('File') ?></th>
          <th><?php echo __tr('Actions') ?></th>
        </tr>
        <?php foreach($Files as $k=>$v): ?>
        <tr>
          <td><?php echo $k; ?></td>
          <td><?php echo $v; ?></td>
          <td><a href="bunny_plugin.php?p=signature&rmfile=<?php echo urlencode($k) ?>" class="btn btn-danger btn-small"><i class="icon-trash icon-large"></i> <?php echo __tr('Remove') ?></a></td>
        </tr>
        <?php endforeach; ?>
      </table>
      <?php endif; ?>
		</div>

    <div class="tab-pane<?php echo $_SESSION['subtab'] == 'signature_manager' ? ' active' : '' ?>" id="manager">
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="f" value="upload">
      <div class="form-group row">
        <label class="col-sm-2 col-form-label" for="file"><?php echo __tr("Upload a file") ?></label>
        <div class="col-sm-4 input-group">
          <input type="file" name="file" maxlength="3000000" accept="audio/mpeg" class="form-control" />
        </div>
        <div class="col-sm-1 input-group">
          <button class="btn btn-primary" type="submit"><?php echo __tr("Add file") ?></button>
        </div>
      </div>
    </form>

    <form method="post">
      <input type="hidden" name="f" value="remove" />
      <div class="form-group row">
        <label class="col-sm-2 col-form-label" for="rf"><?php echo __tr("Remove a file") ?></label>
        <div class="col-sm-4 input-group">
          <select name="rf" class="form-control">
            <?php echo $user_files ?>
          </select>
        </div>
        <div class="col-sm-1 input-group">
          <button class="btn btn-danger" type="submit"><?php echo __tr("Remove") ?></button>
        </div>
      </div>
    </form>
    <div class="row">
      <div class="col-md-5">
        <?php echo __tr('Quota') ?> : <?php echo __tr('%1 Mb', round($size,2)." / ".$quota) ?>
        <div style="width: 300px; height: 20px; border: 1px solid black;">
          <div style="width: <?php echo $size*300/$quota ?>px; background-color: <?php echo $size <= $quota*0.7 ? "green" : ($size > $quota*0.9 ? "red" : "yellow") ?>; height: 20px;">&nbsp;</div>
        </div>
      </div>
    </div>
  </div>
</div>
