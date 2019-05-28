<?php
require_once 'common.php';
if(0):
  $cities = array('Grenoble, FR','Paris, FR');
else:
  $cities = array();
  $ojnAPI = getAPI();
  $list = $ojnAPI->getApiList('plugin/weather/getCitiesList?'.$ojnAPI->getToken());
  foreach($list as $l)
  {
    if(!empty($l->key) && !empty($l->value))
      $cities[] = array('woeid'=>(int)$l->key,'location'=>(string)$l->value);
  }
endif;

$weather = array();

function buildBaseString($baseURI, $method, $params) {
    $r = array();
    ksort($params);
    foreach($params as $key => $value) {
        $r[] = "$key=" . rawurlencode($value);
    }
    return $method . "&" . rawurlencode($baseURI) . '&' . rawurlencode(implode('&', $r));
}

function buildAuthorizationHeader($oauth) {
    $r = 'Authorization: OAuth ';
    $values = array();
    foreach($oauth as $key=>$value) {
        $values[] = "$key=\"" . rawurlencode($value) . "\"";
    }
    $r .= implode(', ', $values);
    return $r;
}

function fetchData($city)
{
  $url = 'https://weather-ydn-yql.media.yahoo.com/forecastrss';
  $app_id = YWEATHER_APPID;
  $consumer_key = YWEATHER_KEY;
  $consumer_secret = YWEATHER_SECRET;
  if(is_array($city))
  {
    $query = array(
      'woeid' => $city['woeid'],
      'format' => 'json',
      'u' => 'c',
    );
  }
  else
  {
    $query = array(
      'location' => $city,
      'format' => 'json',
      'u' => 'c',
    );
  }
  $oauth = array(
    'oauth_consumer_key' => $consumer_key,
    'oauth_nonce' => uniqid(mt_rand(1, 1000)),
    'oauth_signature_method' => 'HMAC-SHA1',
    'oauth_timestamp' => time(),
    'oauth_version' => '1.0'
  );

  $base_info = buildBaseString($url, 'GET', array_merge($query, $oauth));
  $composite_key = rawurlencode($consumer_secret) . '&';
  $oauth_signature = base64_encode(hash_hmac('sha1', $base_info, $composite_key, true));
  $oauth['oauth_signature'] = $oauth_signature;

  $header = array(
    buildAuthorizationHeader($oauth),
    'X-Yahoo-App-Id: ' . $app_id
  );
  $options = array(
    CURLOPT_HTTPHEADER => $header,
    CURLOPT_HEADER => false,
    CURLOPT_URL => $url . '?' . http_build_query($query),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false
  );
  //var_dump($options);

  $ch = curl_init();
  curl_setopt_array($ch, $options);
  $response = curl_exec($ch);
  curl_close($ch);
  return $response;
}

$json_dbg = '{"location":{"woeid":593720,"city":"Grenoble","region":" Rhone-Alpes","country":"France","lat":45.18034,"long":5.72188,"timezone_id":"Europe/Paris"},"current_observation":{"wind":{"chill":14,"direction":225,"speed":7.0},"atmosphere":{"humidity":44,"visibility":16.1,"pressure":930.0,"rising":0},"astronomy":{"sunrise":"6:34 am","sunset":"8:36 pm"},"condition":{"text":"Showers","code":11,"temperature":14},"pubDate":1556218800},"forecasts":[{"day":"Thu","date":1556143200,"low":10,"high":21,"text":"Rain","code":12},{"day":"Fri","date":1556229600,"low":4,"high":12,"text":"Rain","code":12},{"day":"Sat","date":1556316000,"low":7,"high":13,"text":"Showers","code":11},{"day":"Sun","date":1556402400,"low":5,"high":10,"text":"Scattered Showers","code":39},{"day":"Mon","date":1556488800,"low":4,"high":14,"text":"Mostly Cloudy","code":28},{"day":"Tue","date":1556575200,"low":5,"high":17,"text":"Partly Cloudy","code":30},{"day":"Wed","date":1556661600,"low":6,"high":17,"text":"Partly Cloudy","code":30},{"day":"Thu","date":1556748000,"low":8,"high":17,"text":"Partly Cloudy","code":30},{"day":"Fri","date":1556834400,"low":8,"high":16,"text":"Showers","code":11},{"day":"Sat","date":1556920800,"low":8,"high":17,"text":"Scattered Showers","code":39}]}';

foreach($cities as $c)
{
    //$json = json_decode($json_dbg);
    $json = json_decode(fetchData($c));
    //var_dump($json);
    if(!isset($json->location) || !isset($json->current_observation))
    {
        echo 'Skipping city: '.$c.'. API Anwser was'.$json;
        continue;
    }

    $current = array('date' => $json->current_observation->pubDate,
                                //date("d/m/Y h:i:s",$json->current_observation->pubDate),
                      'code' => $json->current_observation->condition->code,
                      'temp' => $json->current_observation->condition->temperature,
                      'forecast' => NULL,
                      'wind' => $json->current_observation->wind->speed,
                     );
    //echo 'Current date:'.date("d/m/Y h:i:s",$json->current_observation->pubDate)."\n";
    $forecasts = array();
    foreach($json->forecasts as $f)
    {
        //echo "\t".'Forecast date:'.date("d/m/Y h:i:s",$f->date)."\n";
        $tmp = array('date' => $f->date,
                                //date("d/m/Y h:i:s",$f->date),
                      'code' => $f->code,
                      'min' => $f->low,
                      'max' => $f->high,
                     );
        if(date("YMD",$f->date) == date("YMD",$current['date']))
        {
            //echo "\t\t Today's forecast !\n";
            $current['forecast'] = $tmp;
        }
        else if($f->date > $current['date'])
        {
            //echo "\t\t Next forecast !\n";
            $forecasts[$f->date] = $tmp;
        }
        else if($f->date <= $current['date']) // equal date should be covered by YMD check
        {
            //echo "\t\t Previous forecast. Skip\n";
        }
    }
    //var_dump($forecasts);
    ksort($forecasts);

    $weather[$json->location->woeid] = array(
        'id'    => $json->location->woeid,
        'city'     => $json->location->city,
        'current'  => $current,
        'forecast' => array_shift($forecasts),
    );
}
//var_dump($weather);
file_put_contents(ROOT_LOCAL."/plugins/weather/weather.json",json_encode($weather));
?>
