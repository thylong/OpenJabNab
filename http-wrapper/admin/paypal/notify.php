<?php
include('../include/tools.inc.php');

$post = file_get_contents('php://input');
if(!empty($_GET['fake']))
{
  echo '<pre>';
  switch($_GET['fake'])
  {
    case 'premium':
      $post = 'mc_gross=2.00&protection_eligibility=Eligible&address_status=confirmed&item_number1=&payer_id=8LRDCJBPGQMEC&address_street=Av.+de+la+Pelouse&payment_date=09%3A35%3A20+Sep+01%2C+2020+PDT&option_name2_1=User&option_selection1_1=1+month&payment_status=Completed&option_selection3_1=Premium&charset=windows-1252&address_zip=75002&first_name=John&mc_fee=0.32&address_country_code=FR&address_name=John+Doe&notify_version=3.9&custom=premium/redox&payer_status=verified&business=sb-1zfzm208771%40business.example.com&address_country=France&num_cart_items=1&mc_handling1=0.00&address_city=Paris&verify_sign=ADAc8BikALuel0ntCgZ-FvCWQ6UjAh-o9CfMavqn76cAiim5xXMTnnCA&payer_email=sb-gwlfv207075%40personal.example.com&option_name1_1=Duration&txn_id=7C593194VE323751D&payment_type=instant&option_name3_1=Type&option_selection2_1=redox&last_name=Doe&address_state=Alsace&item_name1=OpenJabNab&receiver_email=sb-1zfzm208771%40business.example.com&payment_fee=&shipping_discount=0.00&quantity1=1&insurance_amount=0.00&receiver_id=P63XNLWK2A8CG&txn_type=cart&discount=0.00&mc_gross_1=2.00&mc_currency=EUR&residence_country=FR&test_ipn=1&shipping_method=Default&transaction_subject=&payment_gross=&ipn_track_id=c83e6ab6b23e0';
      break;
    case 'premium_gift':
      $post = 'mc_gross=4.00&protection_eligibility=Eligible&address_status=confirmed&item_number1=&item_number2=&payer_id=8LRDCJBPGQMEC&address_street=Av.+de+la+Pelouse&payment_date=06%3A55%3A47+Sep+01%2C+2020+PDT&option_name2_1=User&option_name2_2=User&option_selection1_1=1+month&payment_status=Completed&option_selection1_2=1+month&option_selection3_1=Gift+code&option_selection3_2=Premium&charset=windows-1252&address_zip=75002&first_name=John&mc_fee=0.39&address_country_code=FR&address_name=John+Doe&notify_version=3.9&custom=premium/redox&payer_status=verified&business=sb-1zfzm208771%40business.example.com&address_country=France&num_cart_items=2&mc_handling1=0.00&mc_handling2=0.00&address_city=Paris&verify_sign=AeZrFWQF36YmdFtxcNrfZeGLpIVaAtIFL-fE6yVdy7S87B0nwHpBtynm&payer_email=sb-gwlfv207075%40personal.example.com&option_name1_1=Duration&option_name1_2=Duration&txn_id=58623865W67120506&payment_type=instant&option_name3_1=Type&option_name3_2=Type&option_selection2_1=redox&last_name=Doe&address_state=Alsace&option_selection2_2=redox&item_name1=OpenJabNab&receiver_email=sb-1zfzm208771%40business.example.com&item_name2=OpenJabNab&payment_fee=&shipping_discount=0.00&quantity1=1&insurance_amount=0.00&quantity2=1&receiver_id=P63XNLWK2A8CG&txn_type=cart&discount=0.00&mc_gross_1=2.00&mc_currency=EUR&mc_gross_2=2.00&residence_country=FR&test_ipn=1&shipping_method=Default&transaction_subject=&payment_gross=&ipn_track_id=6400c0ff4c275';
      break;
    case 'donation':
      $post = 'mc_gross=5.00&protection_eligibility=Eligible&payer_id=8LRDCJBPGQMEC&payment_date=06%3A27%3A27+Aug+28%2C+2020+PDT&payment_status=Completed&charset=windows-1252&first_name=John&mc_fee=0.42&notify_version=3.9&custom=guest&payer_status=verified&business=sb-1zfzm208771%40business.example.com&quantity=1&verify_sign=AGdDhbqLhOGuxL72HCkHfEcIDjyXAqnrwE8KvwJvT58TPx2vRFuKLbnc&payer_email=sb-gwlfv207075%40personal.example.com&memo=Helllloooo+%21&txn_id=68S219186F231551W&payment_type=instant&last_name=Doe&receiver_email=sb-1zfzm208771%40business.example.com&payment_fee=&shipping_discount=0.00&receiver_id=P63XNLWK2A8CG&insurance_amount=0.00&txn_type=web_accept&item_name=DEV+OpenJabNab+donations+DEV&discount=0.00&mc_currency=EUR&item_number=&residence_country=FR&test_ipn=1&shipping_method=Default&transaction_subject=guest&payment_gross=&ipn_track_id=9ee312a63d8f2';
      break;
    case 'donation2':
      $post = 'mc_gross=5.00&protection_eligibility=Eligible&payer_id=8LRDCJBPGQMEC&payment_date=10%3A03%3A28+Sep+01%2C+2020+PDT&payment_status=Completed&charset=windows-1252&first_name=John&mc_fee=0.42&notify_version=3.9&custom=donation/redox&payer_status=verified&business=sb-1zfzm208771%40business.example.com&quantity=1&verify_sign=AeuoCIASNPq7VbNrPe2EKC3fpdemAlMKYKrfM4TFTRSb4msIAr1xTYhu&payer_email=sb-gwlfv207075%40personal.example.com&txn_id=6L470442SM039453B&payment_type=instant&last_name=Doe&receiver_email=sb-1zfzm208771%40business.example.com&payment_fee=&shipping_discount=0.00&receiver_id=P63XNLWK2A8CG&insurance_amount=0.00&txn_type=web_accept&item_name=DEV+OpenJabNab+donations+DEV&discount=0.00&mc_currency=EUR&item_number=&residence_country=FR&test_ipn=1&shipping_method=Default&transaction_subject=donation/redox&payment_gross=&ipn_track_id=18aabc543db53';
      break;
    case 'gift':
      $post = 'mc_gross=10.00&protection_eligibility=Eligible&item_number1=&payer_id=8LRDCJBPGQMEC&payment_date=13%3A36%3A21+Sep+02%2C+2020+PDT&option_name2_1=Type&option_selection1_1=6+months&payment_status=Completed&option_selection3_1=redox&charset=windows-1252&first_name=John&mc_fee=0.59&notify_version=3.9&custom=premium/redox&payer_status=verified&business=sb-1zfzm208771%40business.example.com&num_cart_items=1&mc_handling1=0.00&verify_sign=AEsmu0l-0hGZo0Pxvzk5AWMRKN4sA-Lt2a1Lz6h.h-KesKk551VCzQNJ&payer_email=sb-gwlfv207075%40personal.example.com&btn_id1=4145726&option_name1_1=Duration&txn_id=6TE66557FP623004K&payment_type=instant&option_name3_1=User&option_selection2_1=Gift+code&last_name=Doe&item_name1=DEV+OpenJabNab+Premium+PROD&receiver_email=sb-1zfzm208771%40business.example.com&payment_fee=&shipping_discount=0.00&quantity1=2&insurance_amount=0.00&receiver_id=P63XNLWK2A8CG&txn_type=cart&discount=0.00&mc_gross_1=10.00&mc_currency=EUR&residence_country=FR&test_ipn=1&shipping_method=Default&transaction_subject=&payment_gross=&ipn_track_id=dbbf47ab1c4d8';
      break;
    default:
      $post = '';
  }
}
if(empty($post))
  die('No input');

// Log
if(PAYPAL_LOG_NOTIFY)
  file_put_contents('pay.txt',file_get_contents('pay.txt')."\n".date('Y/m/d H:i:s').' '.$post);

$req = $post.'&cmd=_notify-validate';
$ch = curl_init('https://ipnpb.'.(USE_PAYPAL_SANDBOX ? 'sandbox.' :'').'paypal.com/cgi-bin/webscr');
curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
// In wamp-like environments that do not come bundled with root authority certificates,
// please download 'cacert.pem' from "https://curl.haxx.se/docs/caextract.html" and set
// the directory path of the certificate as shown below:
// curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . '/cacert.pem');
if ( !($res = curl_exec($ch)) ) {
  // error_log("Got " . curl_error($ch) . " when processing IPN data");
  die('cURL error: '. curl_error($ch));
  curl_close($ch);
}
curl_close($ch);

// IPN invalid, log for manual investigation
if (strcmp ($res, "VERIFIED") != 0)
  die('Invalid IPN');

// Start parsing !
function getKey($a,$k,$v,$ch) { return isset($a[$k]) ? mb_convert_encoding($a[$k],'utf-8',$ch) : $v; }
function cleanKey($link,$a,$k,$v,$ch) { return mysqli_real_escape_string($link,getKey($a,$k,$v,$ch)); }

parse_str($post,$raw);
if(isset($_GET['verbose']))
  var_dump($raw);

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link)
  die('Connexion SQL impossible : ' . mysqli_error());
$charset = cleanKey($link,$raw,'charset','utf-8','utf-8');
$txn_id = cleanKey($link,$raw,'txn_id','',$charset);
$txn_type = cleanKey($link,$raw,'txn_type','',$charset);
$txn_date = cleanKey($link,$raw,'payment_date','',$charset);
$txn_gross = (float)cleanKey($link,$raw,'mc_gross',0.0,$charset);
$txn_fee = (float)cleanKey($link,$raw,'mc_fee',0.0,$charset);
$txn_currency = cleanKey($link,$raw,'mc_currency','',$charset);
$pay_email = cleanKey($link,$raw,'payer_email','',$charset);
$pay_id = cleanKey($link,$raw,'payer_id','',$charset);
$fname = cleanKey($link,$raw,'first_name','',$charset);
$lname = cleanKey($link,$raw,'last_name','Unknown',$charset);
$name = trim($fname.' '.$lname);
$t = explode('/',cleanKey($link,$raw,'custom','',$charset));
$type = cleanKey($link,$t,0,'donation',$charset);
$username = cleanKey($link,$t,1,'guest',$charset);
$note = cleanKey($link,$raw,'memo','',$charset);
$raw_str = mysqli_real_escape_string($link, $post);

$r = 'SELECT count(id) as cnt FROM paypal_txn WHERE txn_id=\''.$txn_id.'\'';
$rx = mysqli_query($link,$r);
$res = mysqli_fetch_assoc($rx);
if(!empty($res['cnt']) && !isset($_GET['nocheck']))
{
  // FIXME: Log error
  die('Paypal Transaction already registered in database');
}
$r = 'INSERT INTO paypal_txn(date,txn_id,txn_date,txn_gross,txn_fee,txn_currency,pay_email,pay_id,type,username,note,raw) VALUES(NOW(),'
.'"'.$txn_id.'","'.$txn_date.'",'.$txn_gross.','.$txn_fee.',"'.$txn_currency.'",'
.'"'.$pay_email.'","'.$pay_id.'","'.$type.'","'.$username.'","'.$note.'","'.$raw_str.'");';
if(isset($_GET['verbose'])) var_dump($r);
if(!isset($_GET['nosql']))
  mysqli_query($link,$r) or die('SQL Error'.mysqli_error($link));

$items = array();

if(!in_array($txn_type, array('web_accept','cart')))
  die('Unsupported Paypal transaction');

// Premium
$nb = (int)getKey($raw,'num_cart_items',0,$charset);
for($i=0;$i<$nb;$i++)
{
  echo 'Item '.$i."\n";
  $a = array();
  for($j=0;$j<3;$j++)
  {
    $name = (string)getKey($raw,'option_name'.($j+1).'_'.($i+1),'',$charset);
    $value = (string)getKey($raw,'option_selection'.($j+1).'_'.($i+1),'',$charset);
    $a[strtolower($name)] = $value;
    //echo '  Option: '.$name.': '.$value."\n";
  }
  $a['quantity'] = (int)getKey($raw,'quantity'.($i+1),0,$charset);
  if(empty($a['duration']) || empty($a['type']) || empty($a['user']) || empty($a['quantity']))
  {
    // FIXME: Log error
    continue;
  }
  // Validate duration
  switch($a['duration'])
  {
    case '1 month':
      $a['duration'] = 31;
      break;
    case '3 months':
      $a['duration'] = 92;
      break;
    case '6 months':
      $a['duration'] = 183;
      break;
    case '1 year':
      $a['duration'] = 366;
      break;
    case '2 years':
      $a['duration'] = 732;
      break;
    default:
      // FIXME: Log error
      continue 2;
  }
  // Validate Type
  switch($a['type'])
  {
    case 'Premium':
      unset($a['type']);
      $items['premium'][] = $a;
      break;
    case 'Gift code':
      unset($a['type']);
      $items['gift'][] = $a;
      break;
    // 20200901: Donation are not done through the same button, but it could be
    case 'Donation':
      unset($a['type']);
      $items['premium'][] = $a;
      break;
    default:
      // FIXME: Log error
      continue 2;
  }
}

// Donations
// 20200901: Handle everything not premium or gift codes as donations...
if(empty($items['donation']) && empty($items['premium']) && empty($items['gift']) )
{
  $items['donation'][] = array(
    'name' => $name,
    'email' => $pay_email,
    'user' => $username,
    'value' => $txn_gross,
    'quantity' => 1
  );
}
if(isset($_GET['verbose']))
  var_dump($items);

if(!empty($items['donation']))
{
  foreach($items['donation'] as $a)
  $sql = 'INSERT INTO don(id,date,name,email,username,value,txn_id)'."\n";
    for($i=0;$i<$a['quantity'];$i++)
      $sql .= '    VALUES(NULL,NOW(),\''.$a['name'].'\',\''.$a['email'].'\',\''.$a['user'].'\','.$a['value'].',\''.$txn_id.'\'),'."\n";
  $sql = rtrim(trim($sql),',');
  if(isset($_GET['verbose'])) var_dump($sql);
  if(!isset($_GET['nosql']))
    $res = mysqli_query($link, $sql) or die(mysqli_error($link));
  if(!$res)
  {
    // FIXME Log error
  }
}

if(!empty($items['gift']))
{
  $sql = 'INSERT INTO gift(id,date,code,start_date,days,username,buyer,txn_id)'."\n";
  foreach($items['gift'] as $a)
    for($i=0;$i<$a['quantity'];$i++)
      $sql .= '    VALUES(NULL,NOW(),\''.generateGiftCode().'\',NULL,'.$a['duration'].',NULL,\''.$a['user'].'\',\''.$txn_id.'\'),'."\n";
  $sql = rtrim(trim($sql),',');
  if(isset($_GET['verbose'])) var_dump($sql);
  if(!isset($_GET['nosql']))
    $res = mysqli_query($link, $sql) or die(mysqli_error($link));
  if(!$res)
  {
    // FIXME Log error
  }
}

if(!empty($items['premium']))
{
  foreach($items['premium'] as $a)
  $sql = 'INSERT INTO premium(id,date,username,days,txn_id)'."\n";
    for($i=0;$i<$a['quantity'];$i++)
      $sql .= '    VALUES(NULL,NOW(),\''.$a['user'].'\','.$a['duration'].',\''.$txn_id.'\'),'."\n";
  $sql = rtrim(trim($sql),',');
  if(isset($_GET['verbose'])) var_dump($sql);
  if(!isset($_GET['nosql']))
    $res = mysqli_query($link, $sql) or die(mysqli_error($link));
  if(!$res)
  {
    // FIXME Log error
  }
}

include('../include/update_status.inc.php');

mysqli_close($link);
?>
