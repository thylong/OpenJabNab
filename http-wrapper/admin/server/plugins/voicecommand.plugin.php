<?php
$bunnies = array();
$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
    die('Connexion impossible : ' . mysqli_error());
}

$sql = 'SELECT bunny.mac, account.username, account.status FROM bunny LEFT JOIN account ON bunny.account_id=account.id WHERE account.status != "User" AND account.status != "Demo";';
$res = mysqli_query($link, $sql);
while($row = mysqli_fetch_assoc($res))
{
	$bunnies[$row['mac']] = array_merge($row, array('voice' => 'needed'));
}
mysqli_close($link);

$bunniesList = $ojnAPI->getApiList("plugin/voicecommand/getbunnies?".$ojnAPI->getToken());
foreach($bunniesList as $mac)
{
	if(isset($bunnies[$mac]))
		$bunnies[$mac]['voice'] = 'ok';
	if(!isset($bunnies[$mac]))
	{
		$data = array('voice' => 'remove', 'mac' => $mac);
		$bunnies[$mac] = $data;
	}
}
if(isset($_GET['update']))
{
	foreach($bunnies as $mac => $data)
	{
		if($data['voice'] == 'needed')
		{
			Message::AddFromApi($ojnAPI->getApiString("plugin/voicecommand/addbunny?sn=".$mac."&".$ojnAPI->getToken()));
		}
		if($data['voice'] == 'remove')
		{
			Message::AddFromApi($ojnAPI->getApiString("plugin/voicecommand/rmbunny?sn=".$mac."&".$ojnAPI->getToken()));
		}
	}
	header("Location: server_plugin.php?p=voicecommand");
	exit();
}
include(ROOT_SITE.'include/message.php');
?>
<table class="table table-bordered table-striped">
	<tr>
		<th colspan="5"><?php echo __tr("Bunnies") ?></th>
	</tr>
	<tr>
		<th>&nbsp;</th>
		<th><?php echo __tr("MAC") ?></th>
		<th><?php echo __tr("Account") ?></th>
		<th><?php echo __tr("Status") ?></th>
		<th><?php echo __tr("Actions") ?></th>
	</tr>
<?php
	foreach($bunnies as $mac => $data) {
?>
	<tr>
		<td><?php echo $data['voice'] ?></td>
		<td><?php echo $mac ?></td>
		<td><?php echo $data['username'] ?></td>
		<td><?php echo $data['status'] ?></td>
		<td width="15%"><a href="/bunny/index.php?b=<?php echo $mac ?>" class="btn btn-mini btn-primary"><?php echo __tr("Setup") ?></a></td>
	</tr>
<?php } ?>
</table>
<a href="server_plugin.php?p=voicecommand&update" class="btn btn-primary"><?php echo __tr("Update status") ?></a>

