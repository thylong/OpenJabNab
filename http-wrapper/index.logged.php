<?php
$ojnTemplate->setTitle(__tr('Dashboard'));
if(!($user_storage = apcu_fetch(APC_PREFIX.'ojn_userstorage'))) {
	$cmd = 'du '.ROOT_LOCAL.'/users/ --max-depth=0 | cut -d"/" -f1';
	$user_storage = round((trim(exec($cmd)) + 0) / 1024, 1);
	apcu_store(APC_PREFIX.'ojn_userstorage', $user_storage, 3600);
}

if(!($max = apcu_fetch(APC_PREFIX.'ojn_stats_maxbunnies_'.$Infos['language']))) {
$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
    die('Connexion impossible : ' . mysqli_error());
}

$sql = "SELECT date, (sleep + awake) as max FROM stats_sleep ORDER BY max DESC, date DESC LIMIT 0,1";
$res = mysqli_query($link, $sql);
$max = __tr('No data available for statistics');
if($row = mysqli_fetch_assoc($res))
{
	$max = __tr("%1 bunnies on %2", $row['max'], date("d/m/Y", strtotime($row['date'])));
}
mysqli_close($link);
apcu_store(APC_PREFIX.'ojn_stats_maxbunnies_'.$Infos['language'], $max, 3600);
}
if(!($min = apcu_fetch(APC_PREFIX.'ojn_stats_minbunnies_'.$Infos['language']))) {
$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
    die('Connexion impossible : ' . mysqli_error());
}

$sql = "SELECT date, (sleep + awake) as min FROM stats_sleep ORDER BY min ASC, date DESC LIMIT 0,1";
$res = mysqli_query($link, $sql);
$min = __tr('No data available for statistics');
if($row = mysqli_fetch_assoc($res))
{
	if($row['min'] == null)
		$min = __tr("No bunny on %1", date("d/m/Y", strtotime($row['date'])));
	else
		$min = __tr("%1 bunnies on %2", $row['min'], date("d/m/Y", strtotime($row['date'])));
}
mysqli_close($link);
apcu_store(APC_PREFIX.'ojn_stats_minbunnies_'.$Infos['language'], $min, 3600);
}

if(isset($_GET['addmac'])) {
	if(strlen($_GET['addmac']) == 12 && ctype_xdigit($_GET['addmac'])) {
		apcu_delete(APC_PREFIX.'ojn_bunnies_'.$ojnAPI->getToken());
		$output = $ojnAPI->getApiString('accounts/addBunny?login='.urlencode($_SESSION['login']).'&bunnyid='.$_GET['addmac'].'&'.$ojnAPI->getToken());
		$id = Message::AddFromApi($output);
		if($output['ok']) {
			header('Location: /bunny/index.php?b='.$_GET['addmac']);
		} else {
			Message::AddFromApi($ojnAPI->getApiString("plugin/reset/reset?action=free&bunny=".$_GET['addmac']."&".$ojnAPI->getToken()), $id);
			header('Location: index.php');
		}
		exit;
	}
}
?>
	 <div class="row">
	      	<div class="span6">
	      		<div class="widget">

					<div class="widget-header">
						<i class="icon-bookmark"></i>
						<h3><?php echo __tr('Shortcuts') ?></h3>
					</div> <!-- /widget-header -->

					<div class="widget-content">

						<div class="shortcuts">
							<a href="/help/plugins.php" class="shortcut">
								<i class="shortcut-icon icon-list-alt"></i>
								<span class="shortcut-label"><?php echo __tr('Plugins') ?></span>
							</a>

							<a href="/stats.php" class="shortcut">
								<i class="shortcut-icon icon-bar-chart"></i>
								<span class="shortcut-label"><?php echo __tr('Statistics') ?></span>
							</a>

							<a href="/account/index.php" class="shortcut">
								<i class="shortcut-icon icon-user"></i>
								<span class="shortcut-label"><?php echo __tr('My profile') ?></span>
							</a>

							<a href="/bunny/index.php?b=clear" class="shortcut">
								<img src="media/img/NabzGrey32.png" style="margin-top: 4px; margin-bottom: 3px;">
								<span class="shortcut-label"><?php echo __tr('My bunnies') ?></span>
							</a>

							<a href="/account/ztamp.php?z=clear" class="shortcut">
								<i class="shortcut-icon icon-barcode"></i>
								<span class="shortcut-label"><?php echo __tr('My Ztamps') ?></span>
							</a>

							<a href="/account/files.php" class="shortcut">
								<i class="shortcut-icon icon-folder-open"></i>
								<span class="shortcut-label"><?php echo __tr('File manager') ?></span>
							</a>
							<a href="/account/clock.php" class="shortcut">
								<i class="shortcut-icon icon-time"></i>
								<span class="shortcut-label"><?php echo __tr('Clock plugin') ?></span>
							</a>
						</div> <!-- /shortcuts -->
					</div>
				</div>
	      			<div class="widget">

					<div class="widget-header">
						<i class="icon-bookmark"></i>
						<h3><?php echo __tr('Informations !') ?></h3>
					</div> <!-- /widget-header -->

					<div class="widget-content">
<?php if($Infos['status'] == "User"): ?>
<span class="label label-info"><?php echo __tr('New !') ?></span> <?php echo __tr('Try premium plugins for a limited time') ?>. <a href="/donate/demo.php"><?php echo __tr('Try plugins') ?></a>
<br />
<br />
<?php endif; ?>
<?php echo __tr('If you have missing bunnies or ztamps since the new version, click on the link below, and fill all fields') ?>.<br /><br /><center><a class="btn btn-primary" href="/help/index.php?pb=5"><?php echo __tr('I have some missing bunnies or ztamps') ?></a></center>
<br />
		<p><?php echo __tr('openJabNab exists thanks to volunteers, who are giving a lot of time, and even money, for this project and servers') ?>. <?php echo __tr('You can contribute to the project with a donations, that is going to pay a part of the server rental, or that will motivate developers') ?>.</p>
<center>
<?php include_once('donate/paypal.inc.php') ?>
</center>
					</div>
				</div>
		</div>
	      	<div class="span6">
				<div class="widget">
					<div class="widget-header"><i class="icon-file"></i><h3><?php echo __tr('Bunnies status') ?></h3></div>
					<div class="widget-content">
<?php
$ip = false;
if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
	$ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        if(preg_match('/,/', $ip)) {
	        $ips = preg_split('/,/', $ip);
                $ip = trim($ips[0]);
        }
} else {
	$headers = apache_request_headers();
	if(isset($headers["X-Forwarded-For"])) {
		$ip = $headers["X-Forwarded-For"];
	}
}
$bunnies = $ojnAPI->getListOfBunnies(false);
$online = $ojnAPI->getListOfConnectedBunnies(false);
$diff = array();
if($ip) {
	if(!($byip = apcu_fetch(APC_PREFIX.'ojn_ip_bunnies_'.$ojnAPI->getToken())))
	{
		apcu_store(APC_PREFIX.'ojn_ip_bunnies_'.$ojnAPI->getToken(), $ojnAPI->getApiMapped("bunnies/getListOfBunniesByIP?ip=".$ip."&".$ojnAPI->getToken()), 15);
		$byip = apcu_fetch(APC_PREFIX.'ojn_ip_bunnies_'.$ojnAPI->getToken());
	}
	$diff = is_array($byip) && is_array($bunnies) ? array_diff_key($byip, $bunnies) : array();
}
$silence_survey = false;
?>
						<?php if(count($diff)): ?>
						<?php foreach($diff as $mac => $name): ?>
							<div class="alert alert-info">
							<?php echo __tr("The bunny with MAC address '%1' use your IP for his last connection", $mac) ?><br />
							<span><a href="index.php?addmac=<?php echo $mac ?>"><?php echo __tr('Add this bunny to your account') ?></a></span>
							</div>
						<?php endforeach; ?>
						<?php endif; ?>

						<?php if($silence_survey): ?>
							<div class="alert alert-info">
							<?php echo __tr("If you think your bunny is silent, please click the blue button with a speaker.") ?><br />
							<?php echo __tr("Warning : a silent bunny is not a bunny in idle state. It's a bunny with its nose blinking red/orange with no sound after, when it should say something.") ?><br />
							<?php // echo __tr("You have to click after each sound that is not played. Thanks !") ?><br />
							</div>
						<?php endif; ?>

							<table class="table table-bordered table-striped">

								<thead><tr>
									<th><?php echo __tr('MAC') ?></th>
									<th><?php echo __tr('Bunny') ?></th>
									<th><?php echo __tr('Status') ?></th>
									<th>&nbsp;</th>
	<?php	if($silence_survey): ?>
									<th><?php echo __tr('Silent') ?></th>
	<?php endif; ?>
								</tr></thead>
							<tbody>
						<?php if(is_array($bunnies) && count($bunnies)): ?>
						<?php foreach($bunnies as $mac => $name): ?>
							<tr>
								<td class="description"><?php echo $mac?> </td>
								<td class="description"><?php echo $name?> </td>
								<td class="value"><span><?php echo isset($online[$mac]) ? __tr('Connected') : __tr('Disconnected') ?></span></td>
								<td class="span1"><a class="btn btn-small" href="/bunny/index.php?b=<?php echo $mac ?>"><i class="icon-cog"></i></a></td>
	<?php	if($silence_survey): ?>
								<td class="span1"><a class="btn btn-primary" href="/bunny/index.php?bSilent=1&b=<?php echo $mac ?>"><i class="icon-volume-off"></i></a></td>
	<?php endif; ?>
							</tr>
						<?php endforeach; ?>
						<?php else: ?>
							<tr>
								<td colspan="2"><?php echo __tr('You have no bunny in your account') ?></td>
							</tr>
						<?php endif; ?>
						</tbody>
</table>
					</div>
				</div>
<?php
if(false) {

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
    die('Connexion impossible : ' . mysqli_error());
}
$sql = "SELECT DISTINCT(mac) FROM silent_report WHERE mac IN ('".implode("','", array_keys($bunnies))."') AND sound IS NULL AND type IN ('MU', 'ST')";
$res = mysqli_query($link, $sql);

if(mysqli_num_rows($res)) { ?>
				<div class="widget">
					<div class="widget-header"><i class="icon-file"></i><h3><?php echo __tr('Server status') ?></h3></div>
					<div class="widget-content">
			<span>
<?php while($row = mysqli_fetch_assoc($res)) { ?>
<center><?php echo __tr("Your bunny %1 is trying to help discover why some bunnies are silent.", $bunnies[$row['mac']]); ?><br />
<a class="btn btn-primary" href="/bunny/silent.php?b=<?php echo $row['mac'] ?>"><?php echo __tr("Fill the report to help. Thanks !"); ?></a></center><br />
<?php } ?>
			</span>

					</div>
				</div>
<?php }
mysqli_close($link);
}
$Stats = $ojnAPI->getStats(false);
?>
				<div class="widget">
					<div class="widget-header"><i class="icon-file"></i><h3><?php echo __tr('Server status') ?></h3></div>
					<div class="widget-content">

							<table class="table table-bordered table-striped">

							<tbody>
							<tr>
								<td class="description"><?php echo __tr('Server uptime') ?></td>
								<td class="value"><span><?php echo $ojnAPI->getUptime() ?></span></td>
							</tr>
							<tr>
								<td class="description"><?php echo __tr('Connected bunnies') ?> (V2)</td>
								<td class="value"><span><?php echo $Stats['connected_v2'] ?></span></td>
							</tr>
							<tr>
								<td class="description"><?php echo __tr('Connected bunnies') ?> (V1)</td>
								<td class="value"><span><?php echo $Stats['connected_v1'] ?></span></td>
							</tr>
							<tr>
								<td class="description"><?php echo __tr('Connected bunnies') ?> (Karotz)</td>
								<td class="value"><span><?php echo $Stats['connected_v3'] ?></span></td>
							</tr>
							<tr>
								<td class="description"><?php echo __tr('Maximum number of bunnies') ?></td>
								<td class="value"><span><?php echo $max ?></span></td>
							</tr>
							<tr>
								<td class="description"><?php echo __tr('Minimum number of bunnies') ?></td>
								<td class="value"><span><?php echo $min ?></span></td>
							</tr>
							<tr>
								<td class="description"><?php echo __tr('User storage') ?></td>
								<td class="value"><span><?php echo __tr('%1 Mb', $user_storage) ?></span></td>
							</tr>
						</tbody>
</table>
					</div>
				</div>
		    </div>
	      </div>
