<?php
include('../config.php');
if(empty($_POST))
{
    header('Location: /');
    die();
}
$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link)
    die('Connexion SQL impossible : ' . mysqli_error());

function cleanKey($link,$a,$k,$v)
{
    return isset($a[$k]) ? mysqli_real_escape_string($link,$a[$k]) : $v;
}

$raw = $_POST;
$txn_id = cleanKey($link,$raw,'txn_id','');
$txn_date = cleanKey($link,$raw,'payment_date','');
$txn_gross = (float)cleanKey($link,$raw,'mc_gross',0.0);
$txn_fee = (float)cleanKey($link,$raw,'mc_fee',0.0);
$txn_currency = cleanKey($link,$raw,'mc_currency','');
$pay_email = cleanKey($link,$raw,'payer_email','');
$pay_id = cleanKey($link,$raw,'payer_id','');
$t = explode('/',cleanKey($link,$raw,'custom',''));
$type = cleanKey($link,$t,0,'donation');
$username = cleanKey($link,$t,1,'Anonymous');
$note = cleanKey($link,$raw,'memo','');
$raw = mysqli_real_escape_string($link,print_r($raw,true));

$r = 'INSERT INTO donations(date,txn_id,txn_date,txn_gross,txn_fee,txn_currency,pay_email,pay_id,type,username,note,raw) VALUES(NOW(),'
.'"'.$txn_id.'","'.$txn_date.'",'.$txn_gross.','.$txn_fee.',"'.$txn_currency.'",'
.'"'.$pay_email.'","'.$pay_id.'","'.$type.'","'.$username.'","'.$note.'","'.$raw.'");';

mysqli_query($link,$r) or die('SQL Error'.mysqli_error($link));
?>
