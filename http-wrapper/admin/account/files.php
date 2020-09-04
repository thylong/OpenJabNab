<?php
$reload = false;

require_once "include/common.php";
if(!isset($_SESSION['token']) || !$Infos['isAdmin'])
	header('Location: index.php');

$Users = $ojnAPI->getApiMapped("accounts/GetUserlist?".$ojnAPI->getToken());
$md5 = array();
foreach($Users as $u => $n)
	$md5[md5($u)] = $u;
	

$user_dir = ROOT_LOCAL . "users/";
$user_files = array();
$size = 0;
if (is_dir($user_dir)) {
    if ($dhd = opendir($user_dir)) {
        while (($dir = readdir($dhd)) !== false) {
	    if($dir != '..' && $dir != '.')
	    {

		if (is_dir($user_dir.$dir)) {
		    if ($dh = opendir($user_dir.$dir)) {
			while (($file = readdir($dh)) !== false) {
			    if($file != '..' && $file != '.')
			    {
				    $f = array();
				$f['name'] = (isset($md5[$dir])?$md5[$dir]:'UNKNOWN_FIXME')."/".$file;
				$retour = "";
				$out = exec("/usr/bin/ffprobe -sexagesimal -show_streams -show_format \"" . $user_dir.$dir."/".$file."\"", $retour);
				foreach($retour as $line)
				{
					$line = explode("=", $line);
					if(isset($line[0]) && isset($line[1])) {
						$f[$line[0]] = $line[1];
					}
				}
				$f['size'] = $f['size'] / 1024 / 1024;
				$size += $f['size'] ;
				$user_files[] = $f;
			    }
			}
			closedir($dh);
		    }
		}


	    }
        }
        closedir($dhd);
    }
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
if(isset($_GET['c'])) {
	if(file_exists($user_dir.$_GET['c'])) {
		exec("mv ".$user_dir.$_GET['c']." ".$user_dir.$_GET['c'].".tmp && ffmpeg -ar 44100 -ac 1 -y -i ".$user_dir.$_GET['c'].".tmp -ab 96k ".$user_dir.$_GET['c']." && rm ".$user_dir.$_GET['c'].".tmp", $a, $ret);
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
	header("Location: files.php");
	exit;
}

require('include/message.php');
?>
	<div class="row">
		<div class="span12">
			<div class="widget">
			<div class="widget-header">
			    <h3><?php echo __tr("File manager") ?></h3>
			</div>
			<div class="widget-content">

		
<?php echo __tr("You can upload files, and use them later with plugins"); ?>.
<br style="clear:both"/>
<br />
<table class="table table-bordered table-striped span10">
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
	$i = 0;
	foreach($user_files as $file){
?>
	<tr>
		<td><?php echo $file['name']; ?></td>
		<td><?php echo __tr("%1 Mb", round($file['size'], 3)); ?></td>
		<td><?php echo round($file['bit_rate']/1000, 0); ?> kbps, <?php echo round($file['sample_rate']/1000,1); ?> kHz, <?php echo $file['channels'] == 2 ? "Stéréo" : "Mono"; ?></td>
		<td><a href="files.php?r=<?php echo $file['name']; ?>&execute" class="btn btn-small btn-danger"><i class="icon-trash icon-large"></i> <?php echo __tr('Remove') ?></a><?php if($file['bit_rate']/1000 > 97 || $file['channels'] == 2 || $file['sample_rate']/1000 > 45): ?>&nbsp;<a href="files.php?c=<?php echo $file['name']; ?>&execute" class="btn btn-small btn-primary"><i class="icon-trash icon-large"></i> <?php echo __tr('Convert to bunny format') ?></a><?php endif; ?></td>
	</tr>
<?php } ?>
</tbody>
</table>
<br style="clear:both"/>
<div class="span5">
<?php echo __tr('Total') ?> : <?php echo __tr('%1 Mb', round($size,2)) ?>
</div>
<br style="clear:both"/>

</div>
<?php /*
<div class="span5">
<?php echo __tr('Quota') ?> : <?php echo __tr('%1 Mb', round($size,2)." / ".QUOTA) ?><div style="width: 300px; height: 20px; border: 1px solid black;">
<div style="width: <?php echo $size*300/QUOTA ?>px; background-color: <?php echo $size <= QUOTA*0.7 ? "green" : ($size > QUOTA*0.9 ? "red" : "yellow") ?>; height: 20px;">&nbsp;</div>
</div>
*/ ?>
</div>
			</div> 			
			</div> 			
		</div> 
	</div> 
<?php
require_once "include/append.php";
?>

