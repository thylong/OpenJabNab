<?php
require_once 'common.php';

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
    die('Connexion impossible : ' . mysqli_error());
}

$url = ROOT_WWW_API.'plugin/stats/getbunniestimezone';
$content = file_get_contents($url);
$timezones = array();
if(preg_match_all("|<key>([0-9a-f]+)</key><value>([a-zA-Z]*/[a-zA-Z]*)</value>|", $content, $match, PREG_SET_ORDER))
{
	foreach($match as $id => $bunny)
	{
		$timezones[$bunny[1]] = $bunny[2];
	}
	$url = ROOT_WWW_API.'plugin/stats/getbunniesip';
	$content = file_get_contents($url);
	if(preg_match_all("|<key>([0-9a-f]+)</key><value>(\d+\.\d+\.\d+\.\d+)</value>|", $content, $match, PREG_SET_ORDER))
	{
		foreach($match as $id => $bunny)
		{
			$url = 'http://api.ipstack.com/'.$bunny[2].'?access_key=2b7156acc7344f1d1d1e79cee157cde6'; // 10000 req/month limit
			$content = file_get_contents($url);
			//$content = '{"ip":"90.57.234.194","type":"ipv4","continent_code":"EU","continent_name":"Europe","country_code":"FR","country_name":"France","region_code":"OCC","region_name":"Occitanie","city":"Deaux","zip":"30360","latitude":44.067,"longitude":4.1492,"location":{"geoname_id":3021667,"capital":"Paris","languages":[{"code":"fr","name":"French","native":"Fran\u00e7ais"}],"country_flag":"http:\/\/assets.ipstack.com\/flags\/fr.svg","country_flag_emoji":"\ud83c\uddeb\ud83c\uddf7","country_flag_emoji_unicode":"U+1F1EB U+1F1F7","calling_code":"33","is_eu":true}}';
			$jdata = json_decode($content);
			$long = $jdata->latitude;
			$lat = $jdata->longitude;
			$lang = $jdata->country_code.'/'.$jdata->location->languages[0]->code;
			if(!empty($lat) && !empty($long) && !empty($lang))
			{
				$t = isset($timezones[$bunny[1]]) ? $timezones[$bunny[1]] : "Unknow";
				$data = "ip='".$bunny[2]."', language='".$lang."', timezone='".$t."', longitude='".$long."', latitude='".$lat."', date=NOW()";
				$sql = "INSERT INTO geo SET mac='".$bunny[1]."', ".$data." ON DUPLICATE KEY UPDATE ".$data;
				mysqli_query($link, $sql);

			}
			usleep(10000);
		}
	}
}
mysqli_close($link);

?>
