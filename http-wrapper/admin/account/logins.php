<?php
$reload = false;

require_once '../include/common.php';

require(ROOT_SITE.'include/message.php');
?>
	<div class="row">
		<div class="span12">
			<div class="widget">
			<div class="widget-header">
			    <h3><?php echo __tr("Logins") ?></h3>
			</div>
			<div class="widget-content">


<br style="clear:both"/>
<br />
<table class="table table-bordered table-striped span10">
	<thead>
	<tr>
		<th class="span3"><?php echo __tr('Username') ?></th>
		<th class="span3"><?php echo __tr('Logins') ?></th>
		<th class="span3"><?php echo __tr('Last IP address') ?></th>
	</tr>
	</thead>
<tbody>
<?php
$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
    die('Connexion impossible : ' . mysqli_error());
}

$counts = $ojnAPI->getApiMapped('accounts/GetUserLogins?'.$ojnAPI->getToken());
$logins = array();
$sql = "SELECT username, lastip FROM account";
$res = mysqli_query($link, $sql);
while($row = mysqli_fetch_assoc($res))
{
	$logins[utf8_encode($row['username'])] = array('lastip' => $row['lastip'], 'count' => $counts[$row['username']], 'user' => strtolower(utf8_encode($row['username'])));
}
mysqli_close($link);
function mysort($a, $b)
{
	if($a['count'] == $b['count'])
	{
		return $a['user'] >= $b['user'];
	}
	return $a['count'] < $b['count'];
}
uasort($logins, 'mysort');

foreach($logins as $user => $data)
{
?>
	<tr>
		<td><?php echo $user ?></td>
		<td><?php echo $data['count'] ?></td>
		<td><?php echo $data['lastip'] ?></td>
	</tr>
<?php
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
require_once '../include/append.php';
?>
