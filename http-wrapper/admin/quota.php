<?php
$reload = false;

require_once 'include/common.php';

require(ROOT_SITE.'include/message.php');
?>
	<div class="row">
		<div class="span12">
			<div class="widget">
			<div class="widget-header">
			    <h3><?php echo __tr("User quota") ?></h3>
			</div>
			<div class="widget-content">


<br style="clear:both"/>
<br />
<table class="table table-bordered table-striped span10">
	<thead>
	<tr>
		<th class="span3"><?php echo __tr('Username') ?></th>
		<th class="span3"><?php echo __tr('Directory') ?></th>
		<th class="span3"><?php echo __tr('Quota') ?></th>
		<th class="span3"><?php echo __tr('Actions') ?></th>
	</tr>
	</thead>
<tbody>
<?php
$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
    die('Connexion impossible : ' . mysqli_error());
}

$sql = "SELECT username, status FROM account ORDER BY MD5(username) ASC";
$res = mysqli_query($link, $sql);
$user_dir = ROOT_LOCAL . "users/";
$md5 = array();
while($row = mysqli_fetch_assoc($res))
{
	$quota = __tr('Unknow');
	$m5 = md5($row['username']);
	$md5[$m5] = $row['username'];
	$dir = $user_dir . $m5 . "/";
	if(file_exists($dir))
	{

		$quota = QUOTA;
		if($row['status'] == "VIP")
			$quota = 100;
		if($row['status'] == "Premium")
			$quota = 250;
		if($row['status'] == "Administrator")
			$quota = 1000;


		$size = exec("du -cb ".$dir." | grep total | cut  -f1");
		$size = round($size / ($quota * 1024 * 1024) * 100, 1);
		if($size)
		{
?>
	<tr>
		<td><?php echo $row['username']; ?> (<i><?php echo __tr($row['status']) ?></i>)</td>
		<td><?php echo md5($row['username']) ?></td>
		<td>
			<div class="progress progress-<?php echo $size >= 90 ? "danger" : ($size >= 70 ? "warning" : "success") ?>" title="<?php echo __tr("%1 %", $size) ?>" alt="<?php echo __tr("%1 %", $size)?>">
			<div class="bar" style="width: <?php echo $size; ?>%"></div>
		</td>
		<td>&nbsp;</td>
	</tr>
<?php
		}
	}
}

mysqli_close($link);


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
				$f['name'] = (!empty($md5[$dir]) ? $md5[$dir] : 'UNKNOWN')."/".$file;
				$f['size'] = filesize($user_dir.$dir."/".$file) / 1024 / 1024;
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
?>
</tbody>
</table>
<br style="clear:both"/>

</div>
</div>
			</div>
			</div>
		</div>
	</div>
<?php
require_once "include/append.php";
?>

