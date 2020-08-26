<?php
$reload = false;

require_once "../include/common.php";
if(!isset($_SESSION['token'])) {
	header('Location: index.php');
	exit;
}

$user_dir = ROOT_LOCAL . "clock/" . md5($_SESSION['login']) . "/";
$user_files = array();
$size = 0;
if (is_dir($user_dir)) {
    if ($dh = opendir($user_dir)) {
        while (($file = readdir($dh)) !== false) {
	    if($file != '..' && $file != '.')
	    {
		$f = array();
		$f['name'] = $file;
		$retour = "";
		$out = exec("/usr/bin/ffprobe -sexagesimal -show_streams -show_format " . $user_dir.$file, $retour);
		foreach($retour as $line)
		{
			$line = explode("=", $line);
			if(isset($line[0]) && isset($line[1])) {
				$f[$line[0]] = $line[1];
			}
		}
		$f['size'] = $f['size'] / 1024 / 1024;
		$size += $f['size'] ;
		$user_files[$f['name']] = $f;
	    }
        }
        closedir($dh);
    }
    ksort($user_files);
}
else
{
	mkdir($user_dir);
}
if(!empty($_FILES['file'])) {
// ffmpeg -i input.mp3 -ab 128k output.mp3
	if(!(isset($_POST['group']) && strlen($_POST['group']))) {
		Message::AddError(__tr("You need to choose a group name"));
	}
	if(!(isset($_POST['hour']) && strlen($_POST['hour']))) {
		Message::AddError(__tr("You need to choose the time for this file"));
	}
	if(!(isset($_POST['language']) && strlen($_POST['language']))) {
		Message::AddError(__tr("You need to choose the language for this file"));
	}
	$target_path = $user_dir . preg_replace("|[ '&]|", "", $_POST['language'] . "_" . $_POST['group'] . "_" . $_POST['hour'] . "_" .basename( $_FILES['file']['name']));
	if(move_uploaded_file($_FILES['file']['tmp_name'], $target_path)) {
		Message::AddSuccess(__tr("The file %1 has been uploaded", basename( $_FILES['file']['name'])));
	} else{
		Message::AddError(__tr("There was an error uploading the file, please try again!"));
	}
	$reload = true;
}

if(isset($_GET['r'])) {
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
	if(file_exists($user_dir.$_GET['vup'])) {
		exec("mv ".$user_dir.$_GET['vup']." ".$user_dir.$_GET['vup'].".tmp && ffmpeg -ar 44100 -ac 1 -y -i ".$user_dir.$_GET['vup'].".tmp -ab 96k -vol 512 ".$user_dir.$_GET['vup']." && rm ".$user_dir.$_GET['vup'].".tmp", $a, $ret);
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
	if(file_exists($user_dir.$_GET['c'])) {
		//exec("mv ".$user_dir.$_GET['c']." ".$user_dir.$_GET['c'].".tmp && ffmpeg -ar 44100 -ac 1 -y -i ".$user_dir.$_GET['c'].".tmp -ab 96k ".$user_dir.$_GET['c']." && rm ".$user_dir.$_GET['c'].".tmp", $a, $ret);
		exec("sox ".$user_dir.$_GET['c']." ".$user_dir.$_GET['c'].".wav remix 1,2 && ffmpeg -ar 44100 -ac 1 -y -i ".$user_dir.$_GET['c'].".wav -ab 64k ".$user_dir.$_GET['c']." && rm ".$user_dir.$_GET['c'].".wav", $a, $ret);
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

if($reload) {
	header("Location: /account/clock.php");
	exit;
}

$bunnies = array();//$ojnAPI->getListOfConnectedBunnies(false);
require(ROOT_SITE.'include/message.php');
?>
<div class="card ">
  <h5 class="card-header">
    <i class="icon-time"></i> <?php echo __tr("File manager for clock plugin") ?>
  </h5>
  <div class="card-body">
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
        <?php
          foreach($user_files as $file):
        ?>
        <tr>
          <td><?php echo $file['name']; ?></td>
          <td><?php echo __tr("%1 Mb", round($file['size'], 3)); ?></td>
          <td><?php echo round($file['bit_rate']/1000, 0); ?> kbps, <?php echo round($file['sample_rate']/1000,1); ?> kHz, <?php echo $file['channels'] == 2 ? "Stéréo" : "Mono"; ?></td>
          <td><a href="clock.php?r=<?php echo $file['name']; ?>&execute" class="btn btn-small btn-danger"><i class="icon-trash icon-large"></i> <?php echo __tr('Remove') ?></a><?php if($file['bit_rate']/1000 > 97 || $file['channels'] == 2 || $file['sample_rate']/1000 > 45): ?>&nbsp;<a href="clock.php?c=<?php echo $file['name']; ?>&execute" class="btn btn-small btn-primary"><i class="icon-trash icon-large"></i> <?php echo __tr('Convert to bunny format') ?></a><?php endif; ?>&nbsp;<a href="clock.php?vup=<?php echo $file['name']; ?>&execute" class="btn btn-small btn-success"><i class="icon-volume-up icon-large"></i> <?php echo __tr('Volume up') ?></a><?php if(count($bunnies)) { ?>&nbsp; <a class="btn btn-small btn-warning" onclick="testSound('<?php echo $file['name']; ?>')"><?php echo __tr("Test") ?></a><?php } ?></td>
        </tr>
          <?php endforeach; ?>
      </tbody>
    </table>
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
          <label class="col-sm-1 col-form-label" for="group"><?php echo __tr("Group") ?></label>
          <div class="col-sm-2">
            <input type="text" class="form-control" name="group" >
          </div>
        </div>
        <div class="form-group row">
          <label class="col-sm-1 col-form-label" for="language"><?php echo __tr("Language") ?></label>
          <div class="col-sm-2">
            <select name="language" class="form-control" >
              <?php
                $link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
                if (!$link) {
                    die('Connexion impossible : ' . mysqli_error());
                }

                $sql = "SELECT * FROM language";
                if(!$Infos['isAdmin'])
                  $sql .= " WHERE public=1";

                $res = mysqli_query($link, $sql);
                while($row = mysqli_fetch_assoc($res)):
              ?>
              <option value="<?php echo $row['code'] ?>"><?php echo $row['language'] ?></option>
                <?php endwhile;
                mysqli_close($link);
              ?>
            </select>
          </div>
        </div>
        <div class="form-group row">
          <label class="col-sm-1 col-form-label" for="hour"><?php echo __tr("Hour") ?></label>
          <div class="col-sm-1">
            <select name="hour" class="form-control" >
              <?php for($i=0; $i<24; $i++): ?>
	            <option value="<?php echo $i ?>"><?php echo $i ?></option>
              <?php endfor; ?>
						</select>
          </div>
        </div>

        <div class="form-group row">
          <div class="col-sm-12 text-left">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Add file") ?></button>
          </div>
        </div>
      </fieldset>
    </form>
	</div>
</div>
<?php
require_once "../include/append.php";
?>

