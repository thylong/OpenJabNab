<?php
$wips = array('rugby', 'velib', 'contes', 'tennis','horoscope','fdj', 'basket');
$premiums = array();
require_once "include/common.php";
if(!isset($_SESSION['token']))
	header('Location: index.php');
$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
    die('Connexion impossible : ' . mysqli_error());
}
$sql = "SELECT name FROM plugins WHERE premium=1";
$res = mysqli_query($link, $sql);
while($row = mysqli_fetch_assoc($res))
{
	$premiums[] = $row['name'];
}
$bunnies = array();
$sql = "SELECT bunny.mac, account.username, account.status FROM bunny LEFT JOIN account ON bunny.account_id=account.id";
$res = mysqli_query($link, $sql);
while($row = mysqli_fetch_assoc($res))
{
	$bunnies[$row['mac']] = array($row['username'], $row['status']);
}
?>

	<div class="row">
		<div class="span12">
			<div class="widget">
			<div class="widget-header">
			    <h3><?php echo __tr("Donations") ?></h3>
			</div>
			<div class="widget-content">
<table class="table table-bordered table-striped span10">
	<thead>
	<tr>
		<th class="span2"><?php echo __tr('MAC') ?></th>
		<th class="span2"><?php echo __tr('Username') ?></th>
		<th class="span2"><?php echo __tr('Status') ?></th>
		<th class="span5"><?php echo __tr('Plugins') ?></th>
		<th class="span2"><?php echo __tr('Actions') ?></th>
	</tr>
	</thead>
<tbody>
<?php
$plugins = simplexml_load_string($ojnAPI->getApiRaw("bunnies/getListOfPluginsForBunnies?" . $ojnAPI->getToken()));
$plugins = $plugins->list;
foreach($plugins->item as $bunny)
{
	$edit = false;
	$mac = (string)$bunny->key;
	$plugin = (string)$bunny->value;
	$plugin = preg_split('/,/', $plugin);
	if($bunnies[$mac][1] == 'User') {
		foreach($premiums as $premium) {
			if( in_array($premium, $plugin)) {
				foreach($plugin as $k => $v) {
					if($v == $premium) {
						$plugin[$k] = '<span style="color:red">' . $v . '</span>';
						$edit = true;
					}
				}
			}
		}
	}
	if($bunnies[$mac][1] == 'Demo') {
		foreach($premiums as $premium) {
			if( in_array($premium, $plugin)) {
				foreach($plugin as $k => $v) {
					if($v == $premium) {
						$plugin[$k] = '<span style="color:green">' . $v . '</span>';
					}
				}
			}
		}
	}
?>
<tr>
<td><?php echo $mac ?></td>
<td><?php echo $bunnies[$mac][0] ?></td>
<td><?php echo $bunnies[$mac][1] ?></td>
<td><?php echo implode(', ', $plugin) ?></td>
<td><?php if($edit) { ?><a class="btn btn-mini btn-danger" href="/bunny/index.php?b=<?php echo $mac ?>" target="_blank"><?php echo __tr('Need edition') ?></a><? } else { ?><a class="btn btn-mini btn-primary" href="bunny.php?b=<?php echo $mac ?>" target="_blank"><?php echo __tr('Edit') ?></a><?php } ?></td>
</tr>
<?php
}
mysqli_close($link);
?>
</tbody>
</table>
			</div>
			</div>
			</div>
			</div>
