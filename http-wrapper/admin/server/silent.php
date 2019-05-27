<?php
$reload = false;

require_once "../include/common.php";
if(!isset($_SESSION['token']) || !$Infos['isAdmin'])
	header('Location: index.php');

?>
<table class="table table-bordered table-striped span16">
	<thead>
	<tr>
		<th class="span2"><?php echo __tr('Date') ?></th>
		<th class="span2"><?php echo __tr('MAC') ?></th>
		<th class="span12"><?php echo __tr('Status') ?></th>
	</tr>
	</thead>
<tbody>
<?php
$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
    die('Connexion impossible : ' . mysqli_error());
}
$sql = "SELECT * FROM status_silent ORDER BY date DESC;";
$res = mysqli_query($link, $sql);
while($row = mysqli_fetch_assoc($res))
{
	$data = $row;
	$status = array();
	unset($data['id']);
	unset($data['date']);
	unset($data['mac']);
	foreach($data as $key => $value)
		$status[] = $key . '=' . $value;
?>
	<tr>
		<td><?php echo date('d/m/Y H:i:s', strtotime($row['date'])); ?></td>
		<td><?php echo $row['mac']; ?></td>
		<td><?php echo implode(', ', $status) ?></td>
	</tr>
<?php
}
mysqli_close($link);
?>
</tbody>
</table>
<?php
/*
$statuses = array(
0 => __tr('Not silent'),
1 => __tr('Silent bunny, and using debug bootcode'),
2 => __tr('Silent bunny, but with normal bootcode'),
);

if(isset($_GET['insert']) && count($_POST))
{
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
	    die('Connexion impossible : ' . mysqli_error());
	}

	$sql = 'INSERT INTO silent SET mac="'.$_POST['mac'].'", active="'.$_POST['status'].'" ON DUPLICATE KEY UPDATE active="'.$_POST['status'].'";';
	$res = mysqli_query($link, $sql);
	mysqli_close($link);
	$reload = true;
} elseif(isset($_GET['status']) && count($_POST)) {
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
	    die('Connexion impossible : ' . mysqli_error());
	}

	$sql = 'INSERT INTO silent SET mac="'.$_GET['status'].'", active="'.$_POST['status'].'" ON DUPLICATE KEY UPDATE active="'.$_POST['status'].'";';
	$res = mysqli_query($link, $sql);
	mysqli_close($link);
	$reload = true;
}
if(isset($_GET['reboot']))
{
	if($_GET['reboot'] == 'all')
	{
		$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		if (!$link) {
		    die('Connexion impossible : ' . mysqli_error());
		}
		$sql = "SELECT * FROM silent WHERE active='1';";
		$res = mysqli_query($link, $sql);
		while($row = mysqli_fetch_assoc($res))
		{
			Message::AddFromApi($ojnAPI->getApiString("bunny/" . $row['mac'] . "/disconnect?reboot=1&".$ojnAPI->getToken()));
		}
		mysqli_close($link);
	}
	else
	{
		Message::AddFromApi($ojnAPI->getApiString("bunny/" . $_GET['reboot'] . "/disconnect?reboot=1&".$ojnAPI->getToken()));
	}
	$reload = true;
}
if($reload)
{
	header('Location: server_silent.php');
	exit();
}
require('include/message.php');
?>
	<div class="row">
		<div class="span12">
			<div class="widget">
			<div class="widget-header">
			    <h3><?php echo __tr("Silent bunnies") ?></h3>
			</div>
			<div class="widget-content">
<?php
if(isset($_GET['insert']) && $_GET['insert'] == 'bunny') {
?>
<form method="post" class="form-horizontal" action="server_silent.php?insert=bunny">
	<input type="hidden" name="id" value="<?php echo $_GET['edit'] ?>">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("MAC") ?></label>
            <div class="controls">
		<input type="text"  class=" x-large" name="mac" value=""/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Status") ?></label>
            <div class="controls">
		<select name="status">
<?php foreach($statuses as $k => $v) { ?>
			<option value="<?php echo $k ?>"><?php echo $v ?></option>
<?php } ?>
		</select>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <a href="donation.php" class="btn"><?php echo __tr("Cancel") ?></a>
          </div>
</form>
<?php
} elseif(isset($_GET['status'])) {
$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
    die('Connexion impossible : ' . mysqli_error());
}
$sql = "SELECT * FROM silent WHERE mac='".$_GET['status']."';";
$res = mysqli_query($link, $sql);
$status = 2;
if($row = mysqli_fetch_assoc($res))
{
	$status = $row['active'];
}
mysqli_close($link);
?>
<form method="post" class="form-horizontal" action="server_silent.php?status=<?php echo $_GET['status'] ?>">
	<input type="hidden" name="id" value="<?php echo $_GET['edit'] ?>">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("MAC") ?></label>
            <div class="controls">
		<input type="text" disabled class="disabled x-large" value="<?php echo $_GET['status'] ?>"/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Status") ?></label>
            <div class="controls">
		<select name="status">
<?php foreach($statuses as $k => $v) { ?>
			<option value="<?php echo $k ?>"<?php echo $k==$status ? ' selected="selected"' : '' ?>><?php echo $v ?></option>
<?php } ?>
		</select>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <a href="donation.php" class="btn"><?php echo __tr("Cancel") ?></a>
          </div>
</form>
<?php
} else {
?>
<table class="table table-bordered table-striped span10">
	<thead>
	<tr>
		<th class="span2"><?php echo __tr('MAC') ?></th>
		<th class="span4"><?php echo __tr('Status') ?></th>
		<th class="span2"><?php echo __tr('Report') ?></th>
		<th class="span4"><?php echo __tr('Actions') ?></th>
	</tr>
	</thead>
<tbody>
<?php
$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
    die('Connexion impossible : ' . mysqli_error());
}
$sql = "SELECT * FROM silent ORDER BY active ASC, mac ASC;";
$res = mysqli_query($link, $sql);
while($row = mysqli_fetch_assoc($res))
{
	$sql2 = "SELECT COUNT(mac) AS count, sound, mac FROM silent_report WHERE mac='".$row['mac']."' AND type IN ('MU', 'ST') GROUP BY sound ORDER BY sound;";
	$res2 = mysqli_query($sql2, $link);
	$counts = array(''=>0, 0=>0, 1=>0, 2=>0);
	while($row2 = mysqli_fetch_assoc($res2))
	{
		$counts[$row2['sound']] = $row2['count'];
	}
?>
	<tr>
		<td><?php echo $row['mac']; ?></td>
		<td><?php echo $statuses[$row['active']]; ?></td>
		<td>
		<?php echo preg_replace("| |", "&nbsp;",
			str_pad($counts[''], 2, ' ', STR_PAD_LEFT) . ' / ' .
			str_pad($counts[0], 2, ' ', STR_PAD_LEFT) . ' / ' .
			str_pad($counts[1], 2, ' ', STR_PAD_LEFT) . ' / ' .
			str_pad($counts[2], 2, ' ', STR_PAD_LEFT)
			)  ?>
		</td>
		<td>
			<a class="btn btn-success" href="server_silent.php?status=<?php echo $row['mac'] ?>"><?php echo __tr('Change status') ?></a>&nbsp;
			<a class="btn btn-primary" href="server_silent.php?reboot=<?php echo $row['mac'] ?>"><?php echo __tr('Reboot bunny') ?></a>
		</td>
	</tr>
<?php
}
mysqli_close($link);
?>
</tbody>
</table>
<br />
<br style="clear:both"/>
<form method="get" class="form-horizontal">
          <div class="form-actions">
            <button class="btn btn-primary" type="submit" name="reboot" value="all"><?php echo __tr("Reboot all bunnies with debug bootcode") ?></button>
            <button class="btn btn-primary" type="submit" name="insert" value="bunny"><?php echo __tr("Add a bunny") ?></button>
          </div>
</form>
<?php
}
?>

</div>
</div>
			</div>
			</div>
		</div>
	</div>
<?php
*/
require_once "../include/append.php";
?>

