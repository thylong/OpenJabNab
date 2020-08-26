<?php
$reload = false;

require_once "../include/common.php";
if(!isset($_SESSION['token'])) {
	header('Location: index.php');
	exit;
}

if($Infos['status'] == "VIP")
	$quota = 100;
else if($Infos['status'] == "Premium")
	$quota = 250;
else if($Infos['status'] == "Admin")
	$quota = 1000;
else 
	$quota = QUOTA;

$user_dir = ROOT_LOCAL . "users/" . md5($_SESSION['login']) . "/";
$user_files = array();
$size = 0;
if (is_dir($user_dir)) 
{
    if ($dh = opendir($user_dir)) 
    {
        while (($file = readdir($dh)) !== false) 
        {
          if($file != '..' && $file != '.')
          {
            $f = array();
            $f['name'] = $file;
            $retour = "";
            $out = exec("/usr/bin/ffprobe -sexagesimal -show_streams -show_format " . $user_dir.escapeshellcmd($file), $retour);
            foreach($retour as $line)
            {
              $line = explode("=", $line);
              if(isset($line[0]) && isset($line[1])) {
                $f[$line[0]] = $line[1];
              }
            }
            $f['size'] = !empty($f['size']) ? $f['size'] / 1024 / 1024 : 0;
            $size += $f['size'] ;
            $user_files[$f['name']] = $f;
          }
        }
        closedir($dh);
    }
    ksort($user_files);
}
else
	mkdir($user_dir);


if(isset($_GET['cancel']))
{
	$_SESSION['tab'] = 'files_file';
	$reload = true;
}
if(!empty($_FILES['file'])) {
	if($size +( $_FILES['file']['size'] / 1024/1024) > $quota)
		Message::AddError(__tr("Not enought space for this file"));
	else
	{
// ffmpeg -i input.mp3 -ab 128k output.mp3
		$target_path = $user_dir . preg_replace("|[ '&]|", "_", basename( $_FILES['file']['name']));
		if(move_uploaded_file($_FILES['file']['tmp_name'], $target_path)) {
			Message::AddSuccess(__tr("The file %1 has been uploaded", basename( $_FILES['file']['name'])));
		} else{
			Message::AddError(__tr("There was an error uploading the file, please try again!"));
		}
	}
	$_SESSION['tab'] = 'files_file';
	$reload = true;
}

if(isset($_GET['r'])) {
	$_SESSION['tab'] = 'files_file';
	if(file_exists($user_dir.$_GET['r'])) {
		if(unlink($user_dir . $_GET['r'])) {
			Message::AddSuccess(__tr("File successfuly removed"));
		} else {
			Message::AddError(__tr("Error while removing file"));
		}
	} else {
		Message::AddError(__tr("File %1 doesn't exist", $_GET['r']));
	}
	$reload = true;
}
if(isset($_GET['vup'])) {
	$_SESSION['tab'] = 'files_file';
	if(file_exists($user_dir.$_GET['vup'])) {
		exec("mv ".$user_dir.$_GET['vup']." ".$user_dir.$_GET['vup'].".tmp && ffmpeg -ar 44100 -ac 1 -y -i ".$user_dir.$_GET['vup'].".tmp -ab 96k -vol 512 ".$user_dir.$_GET['vup']." && rm ".$user_dir.$_GET['vup'].".tmp", $a, $ret);
		//exec("sox ".$user_dir.$_GET['c']." ".$user_dir.$_GET['c'].".wav remix 1,2 && ffmpeg -ar 44100 -ac 1 -y -i ".$user_dir.$_GET['c'].".wav -ab 64k ".$user_dir.$_GET['c']." && rm ".$user_dir.$_GET['c'].".wav", $a, $ret);
		if($ret == 0) {
			Message::AddSuccess(__tr("File successfuly converted"));
		} else {
			Message::AddError(__tr("Error while converting file"));
		}
	} else {
		Message::AddError(__tr("File %1 doesn't exist", $_GET['c']));
	}
	$reload = true;
}
if(isset($_GET['c'])) {
	$_SESSION['tab'] = 'files_file';
	if(file_exists($user_dir.$_GET['c'])) {
		//exec("mv ".$user_dir.$_GET['c']." ".$user_dir.$_GET['c'].".tmp && ffmpeg -ar 44100 -ac 1 -y -i ".$user_dir.$_GET['c'].".tmp -ab 96k ".$user_dir.$_GET['c']." && rm ".$user_dir.$_GET['c'].".tmp", $a, $ret);
		exec("sox ".$user_dir.$_GET['c']." ".$user_dir.$_GET['c'].".wav remix 1,2 && ffmpeg -ac 1 -y -i ".$user_dir.$_GET['c'].".wav -ab 64k -ar 44100 ".$user_dir.$_GET['c']." && rm ".$user_dir.$_GET['c'].".wav", $a, $ret);
		if($ret == 0) {
			Message::AddSuccess(__tr("File successfuly converted"));
		} else {
			Message::AddError(__tr("Error while converting file"));
		}
	} else {
		Message::AddError(__tr("File %1 doesn't exist", $_GET['c']));
	}
	$reload = true;
}
if(isset($_POST['ngroup'])) {
	$name = trim($_POST['ngroup']);
	if($name == "")
	{
		Message::AddError(__tr("Name can't be empty"));
	}
	else if(preg_replace("|\W|", "", $name) != $name)
	{
		Message::AddError(__tr("Name is invalid, use only alphanumeric characters"));
	}
	else
	{
		Message::AddFromApi($ojnAPI->getApiString('accounts/editgroup?login='.urlencode($_SESSION['login']).'&group='.$name.'&create='.$name.'&'.$ojnAPI->getToken()));
	}
	$_SESSION['tab'] = 'files_group';
	$reload = true;
}
if(isset($_POST['egroup'])) {
	$name = trim($_POST['egroup']);
	if($name == "")
	{
		Message::AddError(__tr("Name can't be empty"));
	}
	else if(preg_replace("|\W|", "", $name) != $name)
	{
		Message::AddError(__tr("Name is invalid, use only alphanumeric characters"));
	}
	else
	{
		Message::AddFromApi($ojnAPI->getApiString('accounts/editgroup?login='.urlencode($_SESSION['login']).'&group='.$name.'&rename='.$name.'&'.$ojnAPI->getToken()));
	}
	$_SESSION['tab'] = 'files_group';
	$reload = true;
}
if(isset($_GET['rs'])) {
	$file = base64_decode($_GET['rs']);
	Message::AddFromApi($ojnAPI->getApiString('accounts/removesound?login='.urlencode($_SESSION['login']).'&group='.$_GET['edit'].'&sound='.$file.'&'.$ojnAPI->getToken()));
	apcu_delete(APC_PREFIX.'ojn_group_'.$_SESSION['login']."_".$_GET['edit']);
	$_SESSION['tab'] = 'files_group';
	$reload = true;
}
if(isset($_POST['addfile'])) {
	$file = base64_decode($_POST['addfile']);
	if(!isset($user_files[$file]))
	{
		Message::AddError(__tr("File doesn't exist"));
	}
	else
	{
		Message::AddFromApi($ojnAPI->getApiString('accounts/addsound?login='.urlencode($_SESSION['login']).'&group='.$_GET['edit'].'&sound='.$file.'&'.$ojnAPI->getToken()));
		apcu_delete(APC_PREFIX.'ojn_group_'.$_SESSION['login']."_".$_GET['edit']);
	}
	$_SESSION['tab'] = 'files_group';
	$reload = true;
}
if(isset($_GET['rg']))
{
	$name = trim($_GET['rg']);
	Message::AddFromApi($ojnAPI->getApiString('accounts/delgroup?login='.urlencode($_SESSION['login']).'&group='.$name.'&'.$ojnAPI->getToken()));
	$_SESSION['tab'] = 'files_group';
	$reload = true;
}

if(!isset($_SESSION['tab']) || !preg_match("|^files_|", $_SESSION['tab']))
  $_SESSION['tab'] = 'files_file';
  
if(isset($_GET['edit']))
	$_SESSION['tab'] = 'files_group';

if($reload) {
	header("Location: files.php");
	exit;
}

require(ROOT_SITE.'include/message.php');
?>
<div class="card ">
  <h5 class="card-header">
    <i class="icon-user"></i> <?php echo __tr("File manager") ?>
  </h5>
  <div class="card-body">
    <div class="tabbable">
    <ul class="nav nav-tabs">
      <li class="nav-item <?php echo $_SESSION['tab'] == 'files_file' ? 'active' : '' ?>">
        <a class="nav-link" href="#file" data-toggle="tab" role="tab" aria-controls="profile" aria-selected="true"><?php echo __tr('Files') ?></a>
      </li>
			<li class="nav-item <?php echo $_SESSION['tab'] == 'files_group' ? 'active' : '' ?>">
        <a class="nav-link" href="#group" data-toggle="tab" role="tab" aria-controls="profile" aria-selected="false"><?php echo __tr('Groups') ?></a>
      </li>
    </ul>

		<div class="tab-content">
      <br />
		  <div class="tab-pane<?php echo $_SESSION['tab'] == 'files_file' ? ' active' : '' ?>" id="file">
        <?php echo __tr("You can upload files, and use them later with plugins"); ?>.
        <br />
        <br />
        <table class="table table-bordered table-striped">
	        <thead>
            <tr>
              <th class="span4"><?php echo __tr('Filename') ?></th>
              <th class="span1"><?php echo __tr('Size') ?></th>
              <th class="span2"><?php echo __tr('File format') ?></th>
              <th class="span3"><?php echo __tr('Actions') ?></th>
            </tr>
	        </thead>
          <tbody>
            <?php foreach($user_files as $file): ?>
            <tr>
              <td><?php echo $file['name']; ?></td>
              <td><?php echo __tr("%1 Mb", round($file['size'], 3)); ?></td>
              <td><?php echo round($file['bit_rate']/1000, 0); ?> kbps, <?php echo round($file['sample_rate']/1000,1); ?> kHz, <?php echo $file['channels'] == 2 ? "Stéréo" : "Mono"; ?></td>
              <td>
                <a href="files.php?r=<?php echo $file['name']; ?>&execute" class="btn btn-small btn-danger"><i class="icon-trash icon-large"></i> <?php echo __tr('Remove') ?></a>&nbsp;
                <?php if($file['bit_rate']/1000 > 97 || $file['channels'] == 2 || $file['sample_rate']/1000 > 45): ?><a href="files.php?c=<?php echo $file['name']; ?>&execute" class="btn btn-small btn-primary"><i class="icon-trash icon-large"></i> <?php echo __tr('Convert to bunny format') ?></a>&nbsp;<?php endif; ?>
                <a href="files.php?vup=<?php echo $file['name']; ?>&execute" class="btn btn-small btn-success"><i class="icon-volume-up icon-large"></i> <?php echo __tr('Volume up') ?></a>
              </td>
            </tr>
            <?php endforeach ?>
          </tbody>
        </table>
        <div class="well">
          <?php echo __tr('Quota') ?> : <?php echo __tr('%1 Mb', round($size,2)." / ".$quota) ?>
          <div class="progress progress-<?php echo $size >= 0.9*$quota ? "danger" : ($size >= 0.7*$quota ? "warning" : "success") ?>">
            <div class="bar" style="width: <?php echo round($size*100/$quota, 0); ?>%"></div>
          </div>
        </div>
        <br />
        <form method="post" class="form-horizontal" enctype="multipart/form-data">
          <fieldset class="border p-3">
            <legend><h6><?php echo __tr("Upload a file"); ?></h6></legend>
            <input type="hidden" name="f" value="upload">
            <div class="form-group row">
              <label class="col-sm-1 col-form-label" for="file"><?php echo __tr("File") ?></label>
              <div class="col-sm-4">
                <input type="file" class="form-control" name="file" maxlength="6000000" accept="audio/mpeg"/>
              </div>
            </div>
            <div class="form-group row">
              <div class="col-sm-12 text-left">
                <button class="btn btn-primary" type="submit"><?php echo __tr("Add file") ?></button>
              </div>
            </div>
        </form>
      </div>

    	<div class="tab-pane<?php echo $_SESSION['tab'] == 'files_group' ? ' active' : '' ?>" id="group">
        <?php
          $groups = $ojnAPI->getApiList("accounts/listgroup?login=".$_SESSION['login']."&".$ojnAPI->getToken());
        ?>
        <?php if(isset($_GET['edit']) && in_array($_GET['edit'], $groups)): ?>
        <?php $group = $_GET['edit']; ?>
        <table class="table table-bordered table-striped">
          <thead>
            <tr>
              <th class="span5"><?php echo __tr('File') ?></th>
              <th class="span3"><?php echo __tr('Actions') ?></th>
            </tr>
          </thead>
          <tbody>
            <?php
            apcu_delete(APC_PREFIX.'ojn_group_'.$_SESSION['login']."_".$group);
            if(!($gsounds = apcu_fetch(APC_PREFIX.'ojn_group_'.$_SESSION['login']."_".$group))) {
              $gsounds = $ojnAPI->getApiList("accounts/listsound?login=".$_SESSION['login']."&group=".$group."&".$ojnAPI->getToken());
              apcu_store(APC_PREFIX.'ojn_group_'.$_SESSION['login']."_".$group, $gsounds, 86400);
            }
            foreach($gsounds as $sound): ?>
            <tr>
              <td><?php echo $sound; ?></td>
              <td><a href="files.php?edit=<?php echo $group ?>&rs=<?php echo base64_encode($sound); ?>" class="btn btn-small btn-danger"><i class="icon-trash icon-large"></i> <?php echo __tr('Remove') ?></a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        
        <form method="post" class="form-horizontal">
          <fieldset class="border p-3">
            <legend><h6><?php echo __tr("Add a new file to the group") ?></h6></legend>
            <div class="form-group row">
              <label class="col-sm-2 col-form-label" for="addfile"><?php echo __tr("File to add") ?></label>
              <div class="col-sm-4">
                <select name="addfile" class="form-control" >
                  <?php	foreach($user_files as $file): ?>
                  <option value="<?php echo base64_encode($file['name']); ?>"><?php echo $file['name']; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="form-group row">
              <div class="col-sm-12 text-left">
                <button class="btn btn-primary" type="submit"><?php echo __tr("Add file") ?></button>
                <button class="btn btn-danger" type="button" onclick="document.location.href='files.php?cancel'"><?php echo __tr("Cancel") ?></button>
              </div>
            </div>
        </form>
        <?php else: ?>
        <table class="table table-bordered table-striped">
          <thead>
            <tr>
              <th class="span4"><?php echo __tr('Group name') ?></th>
              <th class="span5"><?php echo __tr('Files in group') ?></th>
              <th class="span3"><?php echo __tr('Actions') ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($groups as $group):
              if(!($gsounds = apcu_fetch(APC_PREFIX.'ojn_group_'.$_SESSION['login']."_".$group))) {
                $gsounds = $ojnAPI->getApiList("accounts/listsound?login=".$_SESSION['login']."&group=".$group."&".$ojnAPI->getToken());
                apcu_store(APC_PREFIX.'ojn_group_'.$_SESSION['login']."_".$group, $gsounds, 86400);
              }
            ?>
            <tr>
              <td><?php echo $group; ?></td>
              <td>
                <ul>
                  <?php foreach($gsounds as $sound): ?>
                  <li><?php echo $sound; ?></li>
                  <?php endforeach; ?>
                </ul>
              </td>
              <td>
                <a href="files.php?rg=<?php echo $group; ?>" class="btn btn-small btn-danger"><i class="icon-trash icon-large"></i> <?php echo __tr('Remove') ?></a>&nbsp;
                <a href="files.php?edit=<?php echo $group; ?>" class="btn btn-small btn-primary"><i class="icon-trash icon-large"></i> <?php echo __tr('Edit') ?></a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <form method="post" class="form-horizontal">
          <fieldset class="border p-3">
            <legend><h6><?php echo __tr("New group") ?></h6></legend>
            <div class="form-group row">
              <label class="col-sm-1 col-form-label" for="ngroup"><?php echo __tr("Group") ?></label>
              <div class="col-sm-2">
                <input type="text" class="form-control" name="ngroup" >
              </div>
            </div>
            <div class="form-group row">
              <div class="col-sm-12 text-left">
                <button class="btn btn-primary" type="submit"><?php echo __tr("Create group") ?></button>
              </div>
            </div>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php
require_once "../include/append.php";
?>

