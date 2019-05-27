<?php
require_once "include/common.php";
if(!isset($_SESSION['token']) || !$Infos['isAdmin'])
	header('Location: index.php');

$bunnies = array();
$accounts = array();

/*
function macLike($mac, $i = 3)
{
	$like = array();
	$m = $mac;
	$correspondances = array('0' => 'd', 'd' => '0', 'b' => '8', '8' => 'b');
//	$correspondances = array('d' => '0', 'b' => '8');
//	$correspondances = array('d' => '0');
	foreach($correspondances as $a => $b) {
//		echo "$i, $a, $b <br />";
		$k = strpos($m, $a, $i);
		if($k > 0) {
			$m[$k] = $b;
			$like = array_unique(array_merge($like, array($m), macLike($mac, $k+1), macLike($m, $k+1)));
		}
	}
	return $like;
}
*/

function macLike($mac, $j = 2)
{
	$array = array($mac);
	$correspondances = array('0' => 'd', 'd' => '0', 'b' => '8', '8' => 'b');
	for($i=$j; $i<12; $i++) {
		$m = $mac;
		if(isset($correspondances[$mac[$i]])) {
			$m[$i] = $correspondances[$mac[$i]];
			$array = array_merge($array, macLike($m, $i+1));
			
		}
	}
	return array_unique($array);
}

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
    die('Connexion impossible : ' . mysqli_error());
}
$search = isset($_GET['search']) ? trim(strtolower($_GET['search'])): trim(strtolower($_POST['search']));

$title = "";

if(preg_match("/\d+\.\d+\.\d+\.\d+/", $search)) {
	$title = __tr('Search with IP : %1', $search);
	$list = $ojnAPI->getApiMapped("bunnies/getListOfBunniesByIP?ip=".$search."&".$ojnAPI->getToken());
	foreach($list as $mac => $name)
	{
		$bunnies[] = array('mac' => $mac);
	}
	$sql = 'SELECT * FROM account WHERE lastip ="'.$search.'" ORDER BY LOWER(username) ASC;';
	$res = mysqli_query($link, $sql);
	while($row = mysqli_fetch_assoc($res))
	{
		$accounts[] = $row;
	}
} else if($search != '') {
	$title = __tr('Search with keyword : %1', $search);
	if(preg_match('/like:([0-9a-fA-F]{12})/', $search, $match)) {
		$search = $match[1];
	} else {
		$sql = 'SELECT * FROM bunny WHERE LOWER(mac) LIKE "%'.$search.'%" ORDER BY mac ASC;';
		$res = mysqli_query($link, $sql);
		while($row = mysqli_fetch_assoc($res))
		{
			$bunnies[] = $row;
		}
		$sql = 'SELECT * FROM bunny WHERE LOWER(replace(settings, "\0", "")) LIKE "%OwnerAccount%'.strtolower($search).'%" ORDER BY mac ASC;';
		$res = mysqli_query($link, $sql);
		while($row = mysqli_fetch_assoc($res))
		{
			$bunnies[] = $row;
		}
		$sql = 'SELECT * FROM account WHERE LOWER(username) LIKE "%'.$search.'%" ORDER BY LOWER(username) ASC;';
		$res = mysqli_query($link, $sql);
		while($row = mysqli_fetch_assoc($res))
		{
			$accounts[] = $row;
		}
		//if(preg_match("/@/", $search))
		{
			$sql = 'SELECT * FROM account WHERE LOWER(REPLACE(settings, "\0", "")) LIKE "%'.strtolower($search).'%" ORDER BY LOWER(username) ASC;';
			$res = mysqli_query($link, $sql);
			while($row = mysqli_fetch_assoc($res))
			{
				$accounts[] = $row;
			}
		}
	}
} else {
	$title = __tr('Search inactive accounts and bunnies');
	$sql = 'SELECT * FROM bunny WHERE account_id IS NULL AND REPLACE(settings, "\0", "") NOT LIKE "%LastIP%" AND REPLACE(settings, "\0", "") NOT LIKE "%OwnerAccount%" AND REPLACE(settings, "\0", "") NOT LIKE "%LastLocateString%" ORDER BY mac ASC;';
	$res = mysqli_query($link, $sql);
	while($row = mysqli_fetch_assoc($res))
	{
		$bunnies[] = $row;
	}
/*
	$sql = 'SELECT * FROM account LEFT JOIN bunny ON account.id=account_id WHERE account_id IS NULL;';
	$res = mysqli_query($link, $sql);
	while($row = mysqli_fetch_assoc($res))
	{
		$accounts[] = $row;
	}
*/
}
mysqli_close($link);
?>

	      <div class="row">
	      	<div class="span12">      		
	      		<div class="widget ">
	      			<div class="widget-header">
	      				<i class="icon-cog"></i> <h3><?php echo $title ?></h3>
	  				</div> <!-- /widget-header -->
					<div class="widget-content">

<table class="table table-bordered table-striped span10">
	<thead>
	<tr>
		<th colspan="3"><?php echo __tr('List of bunnies') ?></th>
	</tr>
	<tr>
		<th class="span3"><?php echo __tr('MAC') ?></th>
		<th class="span3"><?php echo __tr('Status') ?></th>
		<th><?php echo __tr('Actions') ?></th>
	</tr>
	</thead>
<tbody>
<?php
if(count($bunnies)) {
	$online = array_keys($ojnAPI->getListofAllConnectedBunnies(false));
	foreach($bunnies as $bunny) {
?>
	<tr>
		<td><?php echo $bunny['mac'] ?></td>
		<td><?php echo in_array($bunny['mac'], $online) ? __tr('Online') : __tr('Offline') ?></td>
		<td>
			<a target="_blank" class="btn btn-small btn-primary" href="bunny_expert.php?mac=<?php echo $bunny['mac'] ?>"><?php echo __tr('Expert view') ?></a> &nbsp;
			<a target="_blank" class="btn btn-small btn-primary" href="/bunny/index.php?b=<?php echo $bunny['mac'] ?>"><?php echo __tr('Manage bunny') ?></a> &nbsp;
			<a target="_blank" class="btn btn-small btn-danger" href="server.php?removeB=<?php echo $bunny['mac'] ?>"><?php echo __tr('Remove bunny') ?></a>
			<a target="_blank" class="btn btn-primary" href="/bunny/index.php?silent=<?php echo $bunny['mac'] ?>&b=<?php echo $bunny['mac'] ?>"><i class="icon-volume-off"></i></a>
		</td>
	</tr>
<?php 	}
} else {
	$macs = array();
	if(strlen($search) == 12) {
		$mac = $search;
		$macs = macLike($mac);
		$k = array_search($mac, $macs);
		unset($macs[$k]);
		sort($macs);
//		var_dump($macs);
		$online = array_keys($ojnAPI->getListofAllConnectedBunnies(false));
	}
	if(count($macs)) {
		foreach($macs as $m) {
?>
	<tr>
		<td><?php echo __tr('Try with %1', $m) ?></td>
		<td><?php echo in_array($m, $online) ? __tr('Online') : __tr('Offline') ?></td>
		<td>
			<a target="_blank" class="btn btn-small btn-primary" href="bunny_expert.php?mac=<?php echo $m ?>"><?php echo __tr('Expert view') ?></a> &nbsp;
			<a target="_blank" class="btn btn-small btn-primary" href="/bunny/index.php?b=<?php echo $m ?>"><?php echo __tr('Manage bunny') ?></a> &nbsp;
			<a target="_blank" class="btn btn-small btn-danger" href="server.php?removeB=<?php echo $m ?>"><?php echo __tr('Remove bunny') ?></a>
		</td>
	</tr>
<?php
	}
	} else {
?>
	<tr>
		<td colspan="3"><?php echo __tr('No bunnies') ?></td>
	</tr>
<?php	 } 
} ?>
</tbody>
</table>
<br />
<br />
<table class="table table-bordered table-striped span10">
	<thead>
	<tr>
		<th colspan="2"><?php echo __tr('List of accounts') ?></th>
	</tr>
	<tr>
		<th class="span3"><?php echo __tr('Username') ?></th>
		<th>actions</th>
	</tr>
	</thead>
<tbody>
<?php
if(count($accounts)) {
	foreach($accounts as $account) {
?>
	<tr>
		<td><?php echo $account['username'] ?></td>
		<td>
			<a target="_blank" class="btn btn-small btn-primary" href="account_expert.php?accid=<?php echo $account['id'] ?>"><?php echo __tr('Expert view') ?></a> &nbsp;
			<a target="_blank" class="btn btn-small btn-primary" href="account_view.php?accid=<?php echo $account['id'] ?>"><?php echo __tr('View') ?></a> &nbsp;
			<a target="_blank" class="btn btn-small btn-success" href="index.php?logid=<?php echo $account['id'] ?>"><?php echo __tr('Connect') ?></a> &nbsp;
			<a target="_blank" class="btn btn-small btn-danger" href="server.php?removeA=<?php echo urlencode($account['username']) ?>"><?php echo __tr('Remove account') ?></a>
		</td>
	</tr>
<?php 	}
} else {
?>
	<tr>
		<td colspan="3"><?php echo __tr('No account') ?></td>
	</tr>
<?php } ?>
</tbody>
</table>

						</div>
						</div>
						</div>
						</div>
						</div>
						</div>
<?php
require_once 'include/append.php'
?>
