<?php
$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
    die('Connexion impossible : ' . mysqli_error());
}

$sql = 'UPDATE account SET account.status="User" WHERE ( account.status = "VIP" OR account.status = "Demo" );';
$res = mysqli_query($link, $sql);
$sql = 'UPDATE account, demo SET account.status="Demo" WHERE account.username=demo.username AND date >= DATE_SUB(curdate(), INTERVAL 7 DAY);';
$res = mysqli_query($link, $sql);

$vip = array();
$status = array();

$sql = 'SELECT * FROM `don` WHERE `username`!="" ORDER BY `username`, `date` ASC';
$res = mysqli_query($link, $sql);
while($row = mysqli_fetch_assoc($res))
{
	if($row['username'] != '')
	{
		$status[$row['username']] = 'VIP';
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
			if($d > $date) {
				$date = $d;
			}
			date_add($date, date_interval_create_from_date_string(($days).' day'));
			$vip[$row['username']] = date_format($date, 'Y-m-d');
		}
	}
}

$sql = 'SELECT * FROM `premium` WHERE `username`!="" ORDER BY `username`, `date` ASC';
$res = mysqli_query($link, $sql);
while($row = mysqli_fetch_assoc($res))
{
	if($row['username'] != '')
	{
		$status[$row['username']] = 'Premium';
		$days = 31 * $row['duration'] ;
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
			if($d > $date) {
				$date = $d;
			}
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
		$status[$row['username']] = 'Premium';
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
			$date = date_create($row['date']);
			if($d > $date) {
				$date = $d;
			}
			date_add($date, date_interval_create_from_date_string(($days).' day'));
			$vip[$row['username']] = date_format($date, 'Y-m-d');
		}
	}
}
$now = new DateTime("now");
foreach($vip as $user => $date)
{
	$d = date_create($date);
	$st = $d > $now ? $status[$user] : 'User';
	echo('User '.$user.' status set to '.$st."\n");
	$sql = 'UPDATE account SET account.status="'.$st.'" WHERE account.username="'.$user.'";';
	mysqli_query($link, $sql);
}

mysqli_close($link);
?>
