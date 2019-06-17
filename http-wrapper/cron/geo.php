<?php
require_once 'common.php';

$link = getSQL();
$ojnAPI = getAPI();
$tzs = $ojnAPI->getApiMapped('plugin/stats/getbunniestimezone?'.$ojnAPI->getToken());
$ips = $ojnAPI->getApiMapped('plugin/stats/getbunniesip?'.$ojnAPI->getToken());
$timezones = array();

foreach($ips as $bunny => $ip)
{
	$url = 'http://api.ipstack.com/'.$ip.'?access_key='.IPSTACK_APIKEY; // 10000 req/month limit
	$jdata = json_decode(file_get_contents($url));
	$long = $jdata->latitude;
	$lat = $jdata->longitude;
	$lang = $jdata->country_code.'/'.$jdata->location->languages[0]->code;
	if(!empty($lat) && !empty($long) && !empty($lang))
	{
		$t = isset($tzs[$bunny]) ? $tzs[$bunny] : "Unknow";
		$data = "ip='".$ip."', language='".$lang."', timezone='".$t."', longitude='".$long."', latitude='".$lat."', date=NOW()";
		$sql = "INSERT INTO geo SET mac='".$bunny."', ".$data." ON DUPLICATE KEY UPDATE ".$data;
    //var_dump($sql);
		mysqli_query($link, $sql);
	}
	usleep(10000);
}
mysqli_close($link);

?>
