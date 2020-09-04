<?php
require_once '../include/common.php';

global $online;
$online = $ojnAPI->getListofAllConnectedBunnies(false);
$online = is_array($online) ? array_keys($online) : array();
$bad = $ojnAPI->getApiMapped('plugin/locate/server?action=list&'.$ojnAPI->getToken());
function connected($mac)
{
	global $online;
	return in_array($mac, $online);
}
function online($mac, $text)
{
	$color = 'danger';
	if(connected($mac))
		$color = 'success';
	return '<span class="badge badge-'.$color.'">'.$text.'</span>';
}
function color($code, $channel)
{
	$color = 'badge-danger';
	if($code == '') {
		$code = '?';
		$color = '';
	} else {
		$code += 0;
		if($code < -12)
			$color = 'badge-warning';
		if($code < -25)
			$color = 'badge-success';
	}
	if(strlen($channel)) {
		return '<span class="badge '.$color.'" alt="'.__tr('Channel').' '.$channel.'" title="'.__tr('Channel').' '.$channel.'">'.$code.'</span>';
	} else {
		return '<span class="badge '.$color.'">'.$code.'</span>';
	}
}

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
    die('Connexion impossible : ' . mysqli_error());
}
$params = " 1=1 ";
if(isset($_GET['recent'])) {
	$params .= " AND DATE_SUB(NOW(), INTERVAL 1 MONTH) < date ";
} else if(isset($_GET['old'])) {
	$params .= " AND DATE_SUB(NOW(), INTERVAL 1 MONTH) > date ";
}
if(isset($_GET['revision']) && strlen($_GET['revision'])) {
	$params .= " AND bytecode_revision LIKE '%".$_GET['revision']."%' ";
}
$order = "(date-boot) ASC";
if(isset($_GET['online']) && $_GET['online'] == 1) {
	$order = "(NOW()-boot) ASC";
}
if(isset($_GET['wifi'])) {
	$order = "CAST(rssi AS SIGNED INTEGER) DESC";
}
$sql = "SELECT *, FROM_UNIXTIME(date_boot) as boot FROM status_shortconfig WHERE $params ORDER BY $order;";
$res = mysqli_query($link, $sql);
mysqli_close($link);
$nbr = 0;
$time = 0;
$revs = array();
$bunnies = array();
while($row = mysqli_fetch_assoc($res))
{
	if((isset($_GET['online']) && $_GET['online'] == 1 && connected($row['mac'])) || (isset($_GET['online']) && $_GET['online'] == 0 && !connected($row['mac'])) || !isset($_GET['online']) || (isset($_GET['bad']) && isset($bad[$row['mac']])))
	{
		if((isset($_GET['bad']) && isset($bad[$row['mac']])) || !isset($_GET['bad']))
		{
			$nbr++;
			$time += (connected($row['mac']) ? time() : strtotime($row['date'])) - strtotime($row['boot']);
			if(!isset($_GET['revision'])) {
				$rev = $row['bytecode_revision'];
				$rev = preg_replace('/^(OJN\d+)\w*/', '$1', $rev);
				if(!isset($revs[$rev])) {
					$revs[$rev] = 0;
				}
				$revs[$rev]++;
			}
			$bunnies[] = $row;
		}
	}
}
$moyenne = $nbr ? (int)($time / $nbr) : 0;
?>
<div class="card">
  <h5 class="card-header">
    <i class="icon-cog"></i> <?php echo $nbr ?> <?php echo __tr("Bunnies") ?>
		<?php if(count($revs)): ?>
		<?php $rs = array(); ?>
		<?php foreach($revs as $rev => $c): ?><?php $rs[] = '<span class="badge badge-success">'.$rev.': '.$c.'</span>' ;?><?php endforeach; ?>
		<?php krsort($rs); ?>
		<?php $rs[] = '<span class="badge badge-info">Avg uptime: '.sectotime($moyenne, true).'</span>'; ?>
		<span class="float-right"><?php echo implode(' ', $rs); ?> &nbsp; </span>
		<?php endif; ?>
  </h5>
  <div class="card-body">
		<table class="table table-bordered table-striped">
			<thead>
				<tr>
					<th class="col-2"><?php echo __tr('Date') ?></th>
					<th class="col-1"><?php echo __tr('MAC') ?></th>
					<th class="col-auto"><?php echo __tr('Bootcode') ?></th>
					<th class="col-auto"><?php echo __tr('Wifi') ?></th>
					<th class="col-2"><?php echo __tr('Boot') ?></th>
					<!--th class="col-sm-1"><?php echo __tr('Server') ?></th-->
					<th class="col-auto"><?php echo __tr('Uptime') ?></th>
					<th class="col-5"><?php echo __tr('Actions') ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($bunnies as $row): ?>
					<tr>
					<td><?php echo date('d/m/Y H:i:s', strtotime($row['date'])); ?></td>
					<td><?php echo $row['mac']; ?></td>
					<td><?php echo $row['bytecode_revision']; ?></td>
					<td class="text-center"><?php echo color($row['rssi'], $row['channel']); ?></td>
					<td><?php echo strlen($row['boot']) ? date('d/m/Y H:i:s', strtotime($row['boot'])) : ''; ?></td>
					<!--td><?php echo isset($bad[$row['mac']]) ? $bad[$row['mac']] : '-' ?></td-->
					<td class="text-center"><?php echo online($row['mac'], strlen($row['boot']) ? sectotime((connected($row['mac']) ? time() : strtotime($row['date'])) - strtotime($row['boot'])) : ''); ?></td>
					<td>
						<button class="btn btn-sm btn-danger" onclick="$.get( 'bunny.ajax.php?reboot=1&b=<?php echo $row['mac'] ?>')"><i class="icon icon-off"></i> <?php echo __tr('Reboot') ?></button>
						<button class="btn btn-sm btn-warning" onclick="$.get( 'bunny.ajax.php?reconf&b=<?php echo $row['mac'] ?>')"><i class="icon icon-cog"></i> <?php echo __tr('Reconfigure') ?></button>
						<a target="_blank" class="btn btn-sm btn-primary" href="/bunny/index.php?b=<?php echo $row['mac'] ?>"><i class="icon icon-edit"></i> <?php echo __tr('Manage') ?></a>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
<?php
require_once '../../include/append.php';
?>
