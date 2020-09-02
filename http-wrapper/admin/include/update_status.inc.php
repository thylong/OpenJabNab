<?php
$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
    die('Connexion impossible : ' . mysqli_error());
}

$vip = array();
$status = array();

$sql = 'SELECT username, date, value
					FROM `don`
					WHERE `username` IS NOT NULL and username != \'guest\'
					ORDER BY `username` DESC,
										`date` ASC';
$res = mysqli_query($link, $sql);
while($row = mysqli_fetch_assoc($res))
{
	$status[$row['username']] = 'VIP';
	$v = isset($vip[$row['username']]) ? $vip[$row['username']] : 0;
	$vip[$row['username']] = date_add_days($row['date'],floor(365 * $row['value'] / 10), $v);
}


$sql = 'SELECT username, date, days
					FROM `premium`
					WHERE `username` IS NOT NULL
					ORDER BY `username` DESC,
										`date` ASC';
$res = mysqli_query($link, $sql);
while($row = mysqli_fetch_assoc($res))
{
	$status[$row['username']] = 'Premium';
	$v = isset($vip[$row['username']]) ? $vip[$row['username']] : 0;
	$vip[$row['username']] = date_add_days($row['date'], $row['days'], $v);
}

$sql = 'SELECT username, date
					FROM `demo`
					WHERE `username` IS NOT NULL
					ORDER BY `username` DESC,
										`date` ASC';
$res = mysqli_query($link, $sql);
while($row = mysqli_fetch_assoc($res))
{
	$status[$row['username']] = 'Demo';
	$v = isset($vip[$row['username']]) ? $vip[$row['username']] : 0;
	$vip[$row['username']] = date_add_days($row['date'], 7, $v);
}

$sql = 'SELECT username, start_date, days
					FROM `gift`
					WHERE `username` IS NOT NULL
					ORDER BY `username` DESC,
									 `start_date` ASC';
$res = mysqli_query($link, $sql);
while($row = mysqli_fetch_assoc($res))
{
	$status[$row['username']] = 'Premium';
	$v = isset($vip[$row['username']]) ? $vip[$row['username']] : 0;
	$vip[$row['username']] = date_add_days($row['start_date'], $row['days'], $v);
}

$now = new DateTime("now");
//echo '<pre>';
foreach($vip as $user => $date)
{
	$st = date_create($date) >= $now ? $status[$user] : 'User';
	//echo 'Set user '.$user.' status to '.$st.'. Status '.$status[$user].' expiration date: '.$date."\n";
	$sql = 'UPDATE account
						SET status="'.$st.'"
					WHERE username=\''.$user.'\'
						AND `status` != \'Admin\'';
	$res = mysqli_query($link, $sql) or die(mysqli_error($link));
	// FIXME: Log error !
}
//echo '</pre>';

mysqli_close($link);
?>
