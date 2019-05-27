<?php
require_once 'common.php';

$ojnAPI = getAPI();
$vips_server = $ojnAPI->getApiList('accounts/GetListOfVips?'.$ojnAPI->getToken());

include('../donate/update_status.inc.php');

$vips_bdd = array_keys($vip);
$remove = array_diff($vips_server, $vips_bdd);
$need = array_diff($vips_bdd, $vips_server);

foreach($need as $user)
{
	echo('User '.$user.' wins VIP status'."\n");
	$ret = $ojnAPI->getApiString('accounts/setvip?user='.$user.'&vip=true&'.$ojnAPI->getToken());
}
foreach($remove as $user)
{
	echo('User '.$user.' looses VIP status'."\n");
	$ret = $ojnAPI->getApiString('accounts/setvip?user='.$user.'&vip=false&'.$ojnAPI->getToken());
}

/*
$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
    die('Connexion impossible : ' . mysqli_error());
}

$sql = 'UPDATE account SET account.status="User" WHERE ( account.status = "VIP" OR account.status = "Demo" ) AND account.username != "Pixel3";';
$res = mysqli_query($link, $sql);
$sql = 'UPDATE account, demo SET account.status="Demo" WHERE account.username=demo.username AND date >= DATE_SUB(curdate(), INTERVAL 7 DAY);';
$res = mysqli_query($link, $sql);

$sql = 'SELECT * FROM `don` WHERE `username`!="" ORDER BY `username`, `date` ASC';
$res = mysqli_query($link, $sql);
$vip = array();
while($row = mysqli_fetch_assoc($res))
{
	if($row['username'] != '')
	{
		$days = floor(365 * $row['value'] / 10);
		if(!isset($vip[$row['username']]))
		{
			$date = date_create($row['date']);
			date_add($date, date_interval_create_from_date_string(($days).' day'));
			$vip[$row['username']] = date_format($date, 'Y-m-d');
		}
		else
		{
			$d = date_create($vip[$row['username']]);
			$date = date_create($row['date']);
			if($d > $date)
				$date = $d;
			date_add($date, date_interval_create_from_date_string(($days).' day'));
			$vip[$row['username']] = date_format($date, 'Y-m-d');
		}
	}
}

$sql = 'SELECT * FROM `gift` WHERE genuine=1 AND `username`!="" ORDER BY `username`, `used` ASC';
$res = mysqli_query($link, $sql);
while($row = mysqli_fetch_assoc($res))
{
	if($row['username'] != '')
	{
		$days = $row['days'];
		if(!isset($vip[$row['username']]))
		{
			$date = date_create($row['used']);
			date_add($date, date_interval_create_from_date_string(($days).' day'));
			$vip[$row['username']] = date_format($date, 'Y-m-d');
		}
		else
		{
			$d = date_create($vip[$row['username']]);
			$date = date_create($row['used']);
			if($d > $date)
				$date = $d;
			date_add($date, date_interval_create_from_date_string(($days).' day'));
			$vip[$row['username']] = date_format($date, 'Y-m-d');
		}
	}
}

$vips_bdd = array_keys($vip);
$remove = array_diff($vips_server, $vips_bdd);
$need = array_diff($vips_bdd, $vips_server);

$now = new DateTime("now");
foreach($vip as $user => $date)
{
	$d = date_create($date);
	if($d > $now)
	{
		$sql = 'UPDATE account SET account.status="VIP" WHERE account.username="'.$user.'";';
		mysqli_query($link, $sql);
	}
}
foreach($need as $user)
{
	$ret = $ojnAPI->getApiString('accounts/setvip?user='.$user.'&vip=true&'.$ojnAPI->getToken());
}
foreach($remove as $user)
{
	$ret = $ojnAPI->getApiString('accounts/setvip?user='.$user.'&vip=false&'.$ojnAPI->getToken());
}

mysqli_close($link);
*/
?>
