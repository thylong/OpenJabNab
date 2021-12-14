<?php
require_once 'common.php';
if(empty($_GET['httpCron'])) echo '<pre>';
define('WEATHER_FAKE_CITY_LIST',false);
define('WEATHER_FAKE_JSON_DATA',false);

define('WEATHER_MAX_CONSECUTIVE_ERRORS', 10);

define('WEATHER_CACHE_WRITE',true);
define('WEATHER_CACHE_FILE', ROOT_LOCAL.'/plugins/weather/cache_weatherapi.json');
define('WEATHER_CACHE_VALIDITY_S', 24*60*60);

function getCitiesList($srv_name)
{
  if(WEATHER_FAKE_CITY_LIST):
    $cities = array('Grenoble, FR','Paris, FR');
  else:
    $cities = array();
    $ojnAPI = getAPI();
    $list = $ojnAPI->getApiList('plugin/weather/getCitiesList?'.$ojnAPI->getToken());
    //var_dump($list);
    foreach($list as $l)
    {
      /*if(!empty($l->key) && !empty($l->value))
      {
        $woeid = (int)$l->key;
        if(!isset($cities[$woeid]))
          $cities[$woeid] = array('woeid'=>$woeid,'location'=>(string)$l->value);
      }*/
      $cities[] = (string)$l->key;
    }
    //var_dump(count($cities));
  endif;
  return array_unique($cities);
}

$weather = array();
$cities = getCitiesList('WeatherAPI');

if(file_exists(WEATHER_CACHE_FILE) && !isset($_GET['skipCache']))
{
  var_dump(WEATHER_CACHE_FILE);
  $cache = json_decode(file_get_contents(WEATHER_CACHE_FILE));
}
else
  $cache = array();

$now = time();

$n_err = 0;
foreach($cities as $c)
{
    var_dump($c);

    // Check cache first
    if(!empty($cache->$c))
    {
      var_dump("  In Cache !!");
      //var_dump($cache->$c);
      if(($now - $cache->$c->current->date) <= WEATHER_CACHE_VALIDITY_S)
      {
        var_dump('  => Cache is still valid, skip');
        $weather[$c] = $cache->$c;
        continue;
      }
      var_dump('  => Cache is too old');
    } else
      var_dump('  Not in cache');
    var_dump('  => Refresh cache for '.$c);

    // Refresh cache !
    $url = 'http://api.weatherapi.com/v1/forecast.json?key='.WEATHERAPI_KEY.'&days=2&q='.urlencode($c).'&alerts=yes&aqi=yes';
    if(WEATHER_FAKE_JSON_DATA)
      $data = file_get_contents('weather_weatherapi.json');
    else
    {
      $data = file_get_contents($url,false, stream_context_create(['http' => ['ignore_errors' => true]]));
      foreach($http_response_header as $h)
      {
        if(strstr($h,'HTTP/1.1') && $h != 'HTTP/1.1 200 OK')
        {
          var_dump($url);
          var_dump($data);
          if(++$n_err > WEATHER_MAX_CONSECUTIVE_ERRORS)
            break;
          else
            continue;
        }
      }
    }

    $json = json_decode($data);
    //var_dump($json);
    $n_err = 0;
    if(!isset($json->location) || !isset($json->current))
    {
        echo '  => Skipping city: '.$c.'. API Anwser was'.$json."\n";
        continue;
    }
    $c_time = $json->location->localtime_epoch;

    $current['date'] = $json->current->last_updated_epoch; // as timestamp, use ->last_updated to get Y-M-D H:i
                                //date("d/m/Y h:i:s",$json->current_observation->pubDate),
    $current['code'] = normalizeWeatherCode($json->current->condition->code);
    $current['temp'] = $json->current->temp_c;
    $current['wind'] = $json->current->wind_kph;
    $forecasts = array();
    foreach($json->forecast as $f_array)
    {
      foreach($f_array as $f)
      {
        //echo "\t".'Forecast date:'.date("d/m/Y h:i:s",$f->date_epoch)."\n";
        //var_dump($f);
        $tmp = array('date' => $f->date_epoch,
                      'code' => normalizeWeatherCode($f->day->condition->code),
                      'wind' => $f->day->maxwind_kph,
                      'min' => $f->day->mintemp_c,
                      'max' => $f->day->maxtemp_c,
                     );
        $k = date("YMD",$f->date_epoch) == date('YMD',$c_time) ? "current" : "tomorrow";
        $forecasts[$k] = $tmp;
        //if(date("YMD",$f->date_epoch) == date('YMD',$c_time) || $f->date_epoch > $c_time)
        {/*
            //echo "\t\t Today's or next forecast !\n";
            $current['forecast'] = $tmp;
        }
        else if($f->date_epoch > $c_time)
        {*/
            //echo "\t\t Next forecast !\n";
        }
      }
    }
    ksort($forecasts);

    $weather[$c] = array(
        'city'     => $json->location->name,
        'lat'      => $json->location->lat,
        'lon'      => $json->location->lon,
        'current'  => $current,
        'forecast' => $forecasts,
    );
}

var_dump($weather);
if(WEATHER_CACHE_WRITE)
  file_put_contents(WEATHER_CACHE_FILE,json_encode($weather));
else
  var_dump("Skip Caching values");

function normalizeWeatherCode($code)
{
  /* From plugin_weater.h
      #define PLUGIN_WEATHER_UNKNOW    0
      #define PLUGIN_WEATHER_SUNNY     1
      #define PLUGIN_WEATHER_RAIN      2
      #define PLUGIN_WEATHER_SNOW      3
      #define PLUGIN_WEATHER_STORM     4
      #define PLUGIN_WEATHER_RAINSNOW  5
      #define PLUGIN_WEATHER_FOG       6
      #define PLUGIN_WEATHER_WIND      7
      #define PLUGIN_WEATHER_CLOUD     8
  */

  $codes = array(
               // From https://www.weatherapi.com/docs/weather_conditions.json
               // code,day,night,icon
    1000 => 1, // 1000,Sunny,Clear,113
    1003 => 8, // 1003,"Partly cloudy","Partly cloudy",116
    1006 => 8, // 1006,Cloudy,Cloudy,119
    1009 => 8, // 1009,Overcast,Overcast,122
    1030 => 2, // 1030,Mist,Mist,143
    1063 => 2, // 1063,"Patchy rain possible","Patchy rain possible",176
    1066 => 3, // 1066,"Patchy snow possible","Patchy snow possible",179
    1069 => 5, // 1069,"Patchy sleet possible","Patchy sleet possible",182
    1072 => 5, // 1072,"Patchy freezing drizzle possible","Patchy freezing drizzle possible",185
    1087 => 4, // 1087,"Thundery outbreaks possible","Thundery outbreaks possible",200
    1114 => 3, // 1114,"Blowing snow","Blowing snow",227
    1117 => 3, // 1117,Blizzard,Blizzard,230
    1135 => 6, // 1135,Fog,Fog,248
    1147 => 6, // 1147,"Freezing fog","Freezing fog",260
    1150 => 2, // 1150,"Patchy light drizzle","Patchy light drizzle",263
    1153 => 2, // 1153,"Light drizzle","Light drizzle",266
    1168 => 2, // 1168,"Freezing drizzle","Freezing drizzle",281
    1171 => 2, // 1171,"Heavy freezing drizzle","Heavy freezing drizzle",284
    1180 => 2, // 1180,"Patchy light rain","Patchy light rain",293
    1183 => 2, // 1183,"Light rain","Light rain",296
    1186 => 2, // 1186,"Moderate rain at times","Moderate rain at times",299
    1189 => 2, // 1189,"Moderate rain","Moderate rain",302
    1192 => 2, // 1192,"Heavy rain at times","Heavy rain at times",305
    1195 => 2, // 1195,"Heavy rain","Heavy rain",308
    1198 => 2, // 1198,"Light freezing rain","Light freezing rain",311
    1201 => 2, // 1201,"Moderate or heavy freezing rain","Moderate or heavy freezing rain",314
    1204 => 3, // 1204,"Light sleet","Light sleet",317
    1207 => 3, // 1207,"Moderate or heavy sleet","Moderate or heavy sleet",320
    1210 => 3, // 1210,"Patchy light snow","Patchy light snow",323
    1213 => 3, // 1213,"Light snow","Light snow",326
    1216 => 3, // 1216,"Patchy moderate snow","Patchy moderate snow",329
    1219 => 3, // 1219,"Moderate snow","Moderate snow",332
    1222 => 3, // 1222,"Patchy heavy snow","Patchy heavy snow",335
    1225 => 3, // 1225,"Heavy snow","Heavy snow",338
    1237 => 3, // 1237,"Ice pellets","Ice pellets",350
    1240 => 2, // 1240,"Light rain shower","Light rain shower",353
    1243 => 2, // 1243,"Moderate or heavy rain shower","Moderate or heavy rain shower",356
    1246 => 2, // 1246,"Torrential rain shower","Torrential rain shower",359
    1249 => 5, // 1249,"Light sleet showers","Light sleet showers",362
    1252 => 5, // 1252,"Moderate or heavy sleet showers","Moderate or heavy sleet showers",365
    1255 => 5, // 1255,"Light snow showers","Light snow showers",368
    1258 => 3, // 1258,"Moderate or heavy snow showers","Moderate or heavy snow showers",371
    1261 => 3, // 1261,"Light showers of ice pellets","Light showers of ice pellets",374
    1264 => 3, // 1264,"Moderate or heavy showers of ice pellets","Moderate or heavy showers of ice pellets",377
    1273 => 4, // 1273,"Patchy light rain with thunder","Patchy light rain with thunder",386
    1276 => 4, // 1276,"Moderate or heavy rain with thunder","Moderate or heavy rain with thunder",389
    1279 => 3, // 1279,"Patchy light snow with thunder","Patchy light snow with thunder",392
    1282 => 3, // 1282,"Moderate or heavy snow with thunder","Moderate or heavy snow with thunder",395
  );
  return isset($codes[$code]) ? $codes[$code] : 0;
}

?>
