<?php
include '../../include/config.php';

function generateGiftCode()
{
	$codes = array();
	for($i=0; $i<4; $i++)
	{
		$codes[] = strtoupper(substr(base_convert(rand() % 9999999999, 10, 36), 0, 5));
	}
	$code = implode("-", $codes);
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
	    die('Connexion impossible : ' . mysqli_error());
	}

	$sql = 'SELECT count(code) as cnt FROM gift WHERE code="'.addslashes($code).'";';
	$res = mysqli_query($link, $sql);
	$num = mysqli_fetch_assoc($res);
	mysqli_close($link);
	if(!empty($num['cnt']))
		$code = generate();
	return $code;
}

function date_add_days($date, $days,$start=0)
{
  $date = date_create($date);
  if($start != 0)
  {
    $d = date_create($start);
    if($d > $date)
    $date = $d;
  }
  date_add($date, date_interval_create_from_date_string(($days).' day'));
  return date_format($date, 'Y-m-d');
}

?>