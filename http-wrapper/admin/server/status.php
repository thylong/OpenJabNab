<?php
require_once '../../include/common.php';
if(!isset($_SESSION['token']) || !$Infos['isAdmin'])
	header('Location: /index.php');

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
	$color = 'error';
	if(connected($mac))
		$color = 'success';
	return '<span class="badge badge-'.$color.'">'.$text.'</span>';
}
function color($code, $channel)
{
	$color = 'badge-error';
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
function my($buffer)
{
	return $buffer;
}
ob_start('my');
$nbr = 0;
$time = 0;
$revs = array();
while($row = mysqli_fetch_assoc($res))
{
	if((isset($_GET['online']) && $_GET['online'] == 1 && connected($row['mac'])) || (isset($_GET['online']) && $_GET['online'] == 0 && !connected($row['mac'])) || !isset($_GET['online']) || (isset($_GET['bad']) && isset($bad[$row['mac']])))
	{
		if((isset($_GET['bad']) && isset($bad[$row['mac']])) || !isset($_GET['bad'])) {
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
?>
	<tr>
		<td><?php echo date('d/m/Y H:i:s', strtotime($row['date'])); ?></td>
		<td><?php echo $row['mac']; ?></td>
		<td><?php echo $row['bytecode_revision']; ?></td>
		<td><?php echo color($row['rssi'], $row['channel']); ?></td>
		<td><?php echo strlen($row['boot']) ? date('d/m/Y H:i:s', strtotime($row['boot'])) : ''; ?></td>
		<td><?php echo isset($bad[$row['mac']]) ? $bad[$row['mac']] : '-' ?></td>
		<td><?php echo online($row['mac'], strlen($row['boot']) ? sectotime((connected($row['mac']) ? time() : strtotime($row['date'])) - strtotime($row['boot'])) : ''); ?></td>
		<td>
			<button class="btn btn-mini btn-danger" onclick="$.get( 'bunny.ajax.php?reboot=1&b=<?php echo $row['mac'] ?>')"><?php echo __tr('Reboot') ?></button>
			<button class="btn btn-mini btn-warning" onclick="$.get( 'bunny.ajax.php?reconf&b=<?php echo $row['mac'] ?>')"><?php echo __tr('Change setup') ?></button>
			<a target="_blank" class="btn btn-mini btn-primary" href="/bunny/index.php?b=<?php echo $row['mac'] ?>"><?php echo __tr('Manage bunny') ?></a>
		</td>
	</tr>
<?php
		}
	}
}
$moyenne = $nbr ? (int)($time / $nbr) : 0;
$content = ob_get_clean();
?>
<div class="row">
	<div class="span12">
		<div class="widget">
			<div class="widget-header">
				<i class="icon-th-large"></i>
				<h3><?php echo $nbr ?> <?php echo __tr("Bunnies") ?></h3>
				<?php if(count($revs)): ?>
				<?php $rs = array(); ?>
				<?php foreach($revs as $rev => $c): ?><?php $rs[] = '<span class="badge">'.$rev.':'.$c.'</span>' ;?><?php endforeach; ?>
				<?php krsort($rs); ?>
				<?php $rs[] = '<span class="badge badge-info">'.sectotime($moyenne, true).'</span>'; ?>
				<div class="pull-right"><?php echo implode(' ', $rs); ?> &nbsp; </div>
				<?php endif; ?>
			</div> <!-- /widget-header -->
			<div class="widget-content">
<table class="table table-bordered table-striped">
	<thead>
	<tr>
		<th><?php echo __tr('Date') ?></th>
		<th><?php echo __tr('MAC') ?></th>
		<th><?php echo __tr('Bootcode') ?></th>
		<th><?php echo __tr('Wifi') ?></th>
		<th><?php echo __tr('Boot') ?></th>
		<th><?php echo __tr('Server') ?></th>
		<th><?php echo __tr('Uptime') ?></th>
		<th><?php echo __tr('Actions') ?></th>
	</tr>
	</thead>
<tbody>
<?php
echo $content;
mysqli_close($link);
?>
</tbody>
</table>
			</div>
		</div>
	</div>
</div>
<?php
require_once '../../include/append.php';
?>

