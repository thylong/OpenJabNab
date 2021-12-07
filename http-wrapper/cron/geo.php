<?php
require_once 'common.php';

$link = getSQL();
$ojnAPI = getAPI();
$tzs = $ojnAPI->getApiMapped('plugin/stats/getbunniestimezone?'.$ojnAPI->getToken());
$ips = $ojnAPI->getApiMapped('plugin/stats/getbunniesip?'.$ojnAPI->getToken());
$timezones = array();

$n_err = 0;
foreach($ips as $bunny => $ip)
{
	$ip = str_replace('::ffff:','',$ip); // Remove IPv6 > IPv4 mapping
	$url = 'https://api.ipgeolocation.io/ipgeo?apiKey='.IPGEOLOCATION_APIKEY.'&ip='.$ip.'&fields=latitude,longitude,country_code2,languages'; // 1000 req/day limit
	//var_dump($url);
	if(strstr($ip,'ffff'))
	{
		echo 'Invalid IP for bunny '.$bunny.' : '.$ip."\n";
	}

	$data = file_get_contents($url,false, stream_context_create(['http' => ['ignore_errors' => true]]));
	foreach($http_response_header as $h)
	{
		if(strstr($h,'HTTP/1.1') && $h != 'HTTP/1.1 200 OK')
		{
			var_dump($url);
			var_dump($data);
			if(++$n_err > GEO_MAX_CONSECUTIVE_ERRORS)
				break;
			else
				continue;
		}
	}

	$jdata = json_decode($data);
	//var_dump($jdata);
	$n_err = 0;
	$long = $jdata->latitude;
	$lat = $jdata->longitude;
	$langs = explode(',',$jdata->languages);
	$lang = $jdata->country_code2.'/'.(!empty($langs) ? $langs[0] :'UNK');
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
