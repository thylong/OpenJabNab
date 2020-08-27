<?php
$reload = false;

if($Infos['status'] == "VIP")
  $quota = 100;
elseif($Infos['status'] == "Premium")
  $quota = 250;
elseif($Infos['status'] == "Admin")
  $quota = 1000;
else
  $quota = QUOTA;

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
<ul class="nav nav-tabs">
  <li class="nav-item">
    <a class="nav-link <?php echo $_SESSION['subtab'] == 'music_action' ? ' active' : '' ?>" href="#action" data-toggle="tab"><?php echo __tr('Actions') ?></a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?php echo $_SESSION['subtab'] == 'music_manager' ? ' active' : '' ?>" href="#manager" data-toggle="tab"><?php echo __tr('File manager') ?></a>
  </li>
</ul>

<div class="tab-content pt-2">
  <div class="tab-pane<?php echo $_SESSION['subtab'] == 'music_action' ? ' active' : '' ?>" id="action">
    <form method="post">
      <input type="hidden" name="a" value="addtag">
      <div class="form-group row">
        <label class="col-sm-2 col-form-label" for="tag"><?php echo __tr("Associate Ztamp") ?></label>
        <div class="col-sm-4 input-group">
          <select name="tag" class="form-control">
            <option value=""><?php echo __tr('Choose a Ztamp') ?></option>
          <?php foreach($Ztamps as $k=>$v): ?>
          <option value="<?php echo $k; ?>"><?php echo $v; ?> (<?php echo $k; ?>)</option>
          <?php endforeach; ?>
          </select>
        </div>
        <label class="col-sm-1 col-form-label" for="mfile"><?php echo __tr("To file") ?></label>
        <div class="col-sm-4 input-group">
          <select name="mfile" class="form-control">
            <option value=""><?php echo __tr('Choose a file') ?></option>
            <optgroup label="<?php echo __tr('Options'); ?>">
              <option value="OJN_RANDOM"><?php echo __tr('Random file') ?></option>
            </optgroup>
            <optgroup label="<?php echo __tr('Groups'); ?>">
              <?php foreach($Groups as $g): ?>
              <option value="OJN_GROUP_<?php echo $g; ?>"><?php echo $g; ?></option>
              <?php endforeach; ?>
            </optgroup>
            <optgroup label="<?php echo __tr('Files'); ?>">
              <?php foreach($Files as $f): ?>
              <option value="<?php echo $f; ?>"><?php echo ucfirst(substr($f,0,-4)); ?></option>
              <?php endforeach; ?>
            </optgroup>
          </select>
        </div>
        <div class="col-sm-1 input-group">
          <button class="btn btn-primary" type="submit"><?php echo __tr("Add") ?></button>
        </div>
      </div>
    </form>

    <?php if(count($Assoc)): ?>
    <h5><?php echo __tr('Associations') ?></h5>
    <table class="table table-bordered table-striped">
      <tr>
        <th><?php echo __tr('File') ?></th>
        <th><?php echo __tr('Ztamp') ?></th>
        <th class="col-sm-1"><?php echo __tr('Actions') ?></th>
      </tr>
      <?php foreach($Assoc as $k=>$v): ?>
      <tr>
        <td><?php echo $v == 'OJN_RANDOM' ? __tr('Random file') : (preg_match('/OJN_GROUP_/', $v) ? __tr('Random file').' ('.__tr("Group '%1'", preg_replace('/OJN_GROUP_/', '', $v)).')' : $v); ?></td>
        <td><?php echo $Ztamps[$k] . " - " . $k; ?></td>
        <td><a href="bunny_plugin.php?p=music&rmassoc=<?php echo $k ?>" class="btn btn-danger btn-sm"><i class="icon-trash icon-large"></i> <?php echo __tr('Remove') ?></a></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>

  <div class="tab-pane<?php echo $_SESSION['subtab'] == 'music_manager' ? ' active' : '' ?>" id="manager">
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
