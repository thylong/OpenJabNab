<?php
require_once "include/common.php";
if(!isset($_SESSION['token']) || !$Infos['isAdmin'])
	header('Location: index.php');

$bunnies = array();
$accounts = array();

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
if (!$link)
    die('Connexion impossible : ' . mysqli_error());
$search = !empty($_GET['search']) ? $_GET['search']: !empty($_POST['search']) ? $_POST['search'] : '';
$search = trim(strtolower(str_replace(':','',$search)));

$title = "";

if(preg_match("/\d+\.\d+\.\d+\.\d+/", $search)) {
	$title = __tr('Search with IP : %1', $search);
	$list = $ojnAPI->getApiMapped("bunnies/getListOfBunniesByIP?ip=".$search."&".$ojnAPI->getToken());
	foreach($list as $mac => $name)
		$bunnies[] = array('mac' => $mac);
	$sql = 'SELECT * FROM account WHERE lastip ="'.$search.'" ORDER BY LOWER(username) ASC;';
	$res = mysqli_query($link, $sql);
	while($row = mysqli_fetch_assoc($res))
		$accounts[] = $row;
} else if($search != '') {
	$title = __tr('Search with keyword : %1', $search);
	if(preg_match('/like:([0-9a-fA-F]{12})/', $search, $match)) {
		$search = $match[1];
	} else {
		$sql = 'SELECT mac
						FROM bunny
						WHERE LOWER(mac) LIKE \'%'.$search.'%\'
							OR  LOWER(replace(settings, \'\0\', \'\')) LIKE \'%OwnerAccount%'.$search.'%\'
						ORDER BY mac ASC;';
		$res = mysqli_query($link, $sql);
		while($row = mysqli_fetch_assoc($res))
			$bunnies[] = $row;

		$sql = 'SELECT id, username
						FROM account
						WHERE LOWER(username) LIKE \'%'.$search.'%\'
							 OR LOWER(REPLACE(settings, \'\0\', \'\')) LIKE \'%'.$search.'%\'
						ORDER BY LOWER(username) ASC;';
		$res = mysqli_query($link, $sql);
		while($row = mysqli_fetch_assoc($res))
			$accounts[] = $row;
	}
} else {
	$title = __tr('Search inactive accounts and bunnies');
	$sql = 'SELECT mac
				  FROM bunny
					WHERE (account_id IS NULL
								 AND REPLACE(settings, "\0", "") NOT LIKE "%LastIP%"
								 AND REPLACE(settings, "\0", "") NOT LIKE "%OwnerAccount%"
								 AND REPLACE(settings, "\0", "") NOT LIKE "%LastLocateString%"
								)
						 OR lastlocate IS NULL
					ORDER BY mac ASC;';
	$res = mysqli_query($link, $sql);
	while($row = mysqli_fetch_assoc($res))
		$bunnies[] = $row;

	$sql = 'SELECT id, username
					FROM account
					WHERE lastip IS NULL
						 OR lastlogin IS NULL
					ORDER BY LOWER(username) ASC;';
	$res = mysqli_query($link, $sql);
	while($row = mysqli_fetch_assoc($res))
		$accounts[] = $row;
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

$is_try = '';
if(empty($bunnies))
{
	$is_try = __tr('Try with').' ';
	$macs = array();
	if(strlen($search) == 12)
	{
		$mac = $search;
		$macs = macLike($mac);
		$k = array_search($mac, $macs);
		unset($macs[$k]);
		sort($macs);
		foreach($macs as $m)
			$bunnies[] = array('mac' => $m);
	}
}

?>
<div class="card">
  <h5 class="card-header">
    <i class="icon-cog"></i> <?php echo $title ?>
  </h5>
  <div class="card-body">
	<?php if(count($bunnies)): ?>
	<h5><?php echo __tr('List of bunnies') ?> (<?php echo count($bunnies); ?>)</h5>
		<table class="table table-bordered table-striped">
			<thead>
				<tr>
					<th class="col-sm-2"><?php echo __tr('MAC') ?></th>
					<th class="col-sm-2"><?php echo __tr('Status') ?></th>
					<th class="col-sm-8"><?php echo __tr('Actions') ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
					$r = $ojnAPI->getListofAllConnectedBunnies(false);
					$online = array_keys(!empty($r) ? $r : array());
					foreach($bunnies as $bunny):
						$is_online = in_array($bunny['mac'], $online);
				?>
				<tr>
					<td><?php echo $is_try.$bunny['mac'] ?></td>
					<td><h5><span class="badge badge-<?php echo $is_online ? 'success' : 'secondary'; ?> disabled"><?php echo $is_online ? __tr('Online') : __tr('Offline') ?></span></h5></td>
					<td>
						<a target="_blank" class="btn btn-sm btn-warning" href="bunny/bunny_expert.php?mac=<?php echo $bunny['mac'] ?>"><i class="icon-large icon-search"></i> <?php echo __tr('Expert view') ?></a>
						<a target="_blank" class="btn btn-sm btn-primary" href="/bunny/index.php?b=<?php echo $bunny['mac'] ?>"><i class="icon-large icon-cog"></i> <?php echo __tr('Manage bunny') ?></a>
						<a target="_blank" class="btn btn-sm btn-danger" href="server/index.php?removeB=<?php echo $bunny['mac'] ?>"><i class="icon-large icon-trash"></i> <?php echo __tr('Remove bunny') ?></a>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>
		<?php if(count($accounts)): ?>
		<h5><?php echo __tr('List of accounts') ?> (<?php echo count($accounts); ?>)</h5>
		<table class="table table-bordered table-striped span10">
			<thead>
				<tr>
					<th class="col-sm-4"><?php echo __tr('Username') ?></th>
					<th class="col-sm-8"><?php echo __tr('Actions') ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($accounts as $account): ?>
				<tr>
					<td><?php echo $account['username'] ?></td>
					<td>
						<a target="_blank" class="btn btn-sm btn-warning" href="account/account_expert.php?accid=<?php echo $account['id'] ?>"><i class="icon-large icon-search"></i> <?php echo __tr('Expert view') ?></a>
						<a target="_blank" class="btn btn-sm btn-primary" href="/account/index.php?a=<?php echo $account['id'] ?>"><i class="icon-large icon-cog"></i> <?php echo __tr('Manage account') ?></a>
						<a target="_blank" class="btn btn-sm btn-success" href="/index.php?logid=<?php echo $account['id'] ?>"><i class="icon-large icon-user"></i> <?php echo __tr('Connect') ?></a>
						<a target="_blank" class="btn btn-sm btn-danger" href="server/index.php?removeA=<?php echo urlencode($account['username']) ?>"><i class="icon-large icon-trash"></i> <?php echo __tr('Remove account') ?></a>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>
	</div>
</div>
<?php
require_once 'include/append.php'
?>
