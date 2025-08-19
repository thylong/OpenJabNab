<?php
if(!file_exists("include/common.php"))
  header('Location: install.php');
require_once "include/common.php";
$ojnTemplate->setTitle(__tr('Statistics'));

if(!($online = apcu_fetch(APC_PREFIX.'ojn_stats_connected')) || !($sleep = apcu_fetch(APC_PREFIX.'ojn_stats_sleep'))) {
  $link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
  if (!$link) {
      die('Connexion impossible : ' . mysqli_error());
  }

  $sql = "SELECT * FROM stats_sleep ORDER BY date DESC LIMIT 0,100";
  $res = mysqli_query($link, $sql);
  $online = array();
  $sleep = array();
  $seconds = date_offset_get(new DateTime);
  while($row = mysqli_fetch_assoc($res))
  {
    $online[(strtotime($row['date']) + $seconds)* 1000] = $row['sleep'] + $row['awake'];
    $sleep[(strtotime($row['date']) + $seconds)* 1000] = array('awake' => $row['awake'], 'sleep' => $row['sleep']);
  }
  $online = array_reverse($online, true);
  $sleep = array_reverse($sleep, true);
  mysqli_close($link);
  apcu_store(APC_PREFIX.'ojn_stats_connected', $online, 300);
  apcu_store(APC_PREFIX.'ojn_stats_sleep', $sleep, 300);
}

if(!($colors = apcu_fetch(APC_PREFIX.'ojn_stats_colors'))) {
  $link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
  if (!$link) {
      die('Connexion impossible : ' . mysqli_error());
  }

  $sql = "SELECT * FROM stats_color ORDER BY date DESC LIMIT 0,100";
  $res = mysqli_query($link, $sql);
  $colors = array();
  $seconds = date_offset_get(new DateTime);
  while($row = mysqli_fetch_assoc($res))
  {
    foreach($row as $k => $v)
    {
      if($k != "date")
        $colors[(strtotime($row['date']) + $seconds)* 1000][$k] = $v + 0;
    }
  }
  $colors = array_reverse($colors, true);
  mysqli_close($link);
  apcu_store(APC_PREFIX.'ojn_stats_colors', $colors, 300);
}

if(!($actions = apcu_fetch(APC_PREFIX.'ojn_stats_actions'))) {
  $link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
  if (!$link) {
      die('Connexion impossible : ' . mysqli_error());
  }

  $sql = "SELECT * FROM stats_actions ORDER BY date DESC LIMIT 0,100";
  $res = mysqli_query($link, $sql);
  $actions = array();
  $seconds = date_offset_get(new DateTime);
  $max = 0;
  while($row = mysqli_fetch_assoc($res))
  {
    foreach($row as $k => $v)
    {
      if($k != "date")
      {
        $actions[(strtotime($row['date']) + $seconds)* 1000][$k] = $v + 0;
        $max = max($max, $v + 0);
      }

    }
  }
  $actions = array_reverse($actions, true);
  mysqli_close($link);
  apcu_store(APC_PREFIX.'ojn_stats_actions', $actions, 300);
}


if(!($maxis = apcu_fetch(APC_PREFIX.'ojn_stats_maxis'))) {
  $link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
  if (!$link) {
      die('Connexion impossible : ' . mysqli_error());
  }

  $sql = "SELECT date, MAX(count) AS count FROM (SELECT DATE_FORMAT(date, '%Y-%m-%d 12:00:00') AS date, awake+sleep AS count FROM stats_sleep) AS t GROUP BY date ORDER BY date DESC LIMIT 730;";
  $res = mysqli_query($link, $sql);
  $maxis = array();
  $seconds = date_offset_get(new DateTime);
  while($row = mysqli_fetch_assoc($res))
  {
    $maxis[(strtotime($row['date']) + $seconds)* 1000] = $row['count'];
  }

  $maxis = array_reverse($maxis, true);
  mysqli_close($link);
  apcu_store(APC_PREFIX.'ojn_stats_maxis', $maxis, 3600);
}

?>
<?php
require_once('include/message.php');
?>
        <div class="row">
          <div class="col-md-6">
            <div class="card">
              <h5 class="card-header">
                <i class="icon-bar-chart"></i> <?php echo __tr('Connected bunnies') ?>
              </h5>
              <div class="card-body">
                <div id="connected-chart" class="chart-holder"></div>
              </div>
            </div>
            <div class="card">
              <h5 class="card-header">
                <i class="icon-bar-chart"></i> <?php echo __tr('Actions') ?>
              </h5>
              <div class="card-body">
                <div id="actions-chart" class="chart-holder"></div>
              </div>
            </div>
            <div class="card">
              <h5 class="card-header">
                <i class="icon-bar-chart"></i> <?php echo __tr('API') ?>
              </h5>
              <div class="card-body">
                <div id="api-chart" class="chart-holder"></div>
              </div>
            </div>
          </div>
          <div class="col-md-6">
          <div class="card">

            <h5 class="card-header">
              <i class="icon-bar-chart"></i> <?php echo __tr('Sleep') ?>
            </h5>

            <div class="card-body">
              <div id="sleep-chart" class="chart-holder"></div>

            </div>

          </div>
          <div class="card">

            <h5 class="card-header">
              <i class="icon-bar-chart"></i> <?php echo __tr('Breathing colors of bunnies') ?>
            </h5>

            <div class="card-body">
              <div id="color-chart" class="chart-holder"></div>

            </div>

          </div>
          <div class="card">

            <h5 class="card-header">
              <i class="icon-bar-chart"></i> <?php echo __tr('Maximum number of bunnies') ?>
            </h5>

            <div class="card-body">
              <div id="max-chart" class="chart-holder"></div>

            </div>

          </div>
    </div>
    </div>
<?php
$cutlimit = 2;
$js = '<script src="/media/js/jquery.flot.js"></script>';
$js .= '<script src="/media/js/jquery.flot.pie.js"></script>';
$js .= '<script src="/media/js/jquery.flot.orderBars.js"></script>';
$js .= '<script src="/media/js/jquery.flot.stack.js"></script>';
$js .= '<script>
$(function () {
    var online = [];';
    $previous = null;
    $step = 0;
    $onlines = array();
    foreach($online as $time => $nbr) {
      if($step++ > $cutlimit || $previous === null || abs($previous-$nbr) < 100) {
        $onlines[] = $nbr;
        $step = 0;
        $js .= 'online.push(['.$time.', '.$nbr.']);';
        $previous = $nbr;
      }
    }
    $js .= 'var options = {
    yaxis: { min: '.max(min($onlines) - 50, 0).', max: '.(max($onlines) + 50).' },
    xaxis: { mode: "time", timeformat:"%H:%M", minTickSize: [1, "hour"] },
    colors: ["#0088CC", "#222", "#666", "#BBB"],
    series: {
         lines: {
          lineWidth: 2,
          fill: true,
          fillColor: { colors: [ { opacity: 0.6 }, { opacity: 0.2 } ] },
          steps: false
        }
         }
  };
  var plot = $.plot($("#connected-chart"), [ online ], options);

    var sleep = []; var awake = [];';
    foreach($sleep as $time => $nbr) {
      if(isset($nbr["sleep"]) && isset($nbr["awake"])) {
        $js .= 'sleep.push(['.$time.', '.$nbr["sleep"].']); awake.push(['.$time.', '.$nbr["awake"].']);';
      }
    }
    $js .= 'var options2 = {
    yaxis: { min: 0, max: '.(max($online) + 10).' },
    xaxis: { mode: "time", timeformat:"%H:%M", minTickSize: [1, "hour"] },
    colors: ["#059", "#D90", "#666", "#BBB"],
    series: {
           lines: {
            lineWidth: 2,
            fill: true,
            fillColor: { colors: [ { opacity: 0.6 }, { opacity: 0.2 } ] },
            steps: false
          }
         }
  };
  var plot = $.plot($("#sleep-chart"), [ sleep, awake ], options2);


    var white = []; var none = []; var red = []; var yellow = []; var green = []; var cyan = []; var blue = []; var violet = [];';
    $previous = null;
    $step = 0;
    foreach($colors as $time => $color)
    {
      if(isset($color["violet"]) && ($step++ > $cutlimit || $previous === null || abs($previous-$color["violet"]) < 100)) {
        $step = 0;
        $js .= 'white.push(['.$time.', '.$color["white"].']); none.push(['.$time.', '.$color["black"].']);';
        $js .= 'red.push(['.$time.', '.$color["red"].']); yellow.push(['.$time.', '.$color["yellow"].']);';
        $js .= 'green.push(['.$time.', '.$color["green"].']); cyan.push(['.$time.', '.$color["cyan"].']);';
        $js .= 'blue.push(['.$time.', '.$color["blue"].']); violet.push(['.$time.', '.$color["violet"].']);';
        $previous = $color["violet"];
      }
    }
    $js .= 'var options3 = {
    xaxis: { mode: "time", timeformat:"%H:%M", minTickSize: [1, "hour"] },
    colors: ["#9F9F9F", "#111111", "#FF0000", "#FFFF00", "#00FF00", "#00FFFF", "#0000FF", "#952CB3"],
    series: {
           stack: true,
           lines: {
            lineWidth: 1,
            fill: true,
            fillColor: { colors: [ { opacity: 0.6 }, { opacity: 0.2 } ] },
            steps: false
          }
         }
  };
  var plot = $.plot($("#color-chart"), [ white, none, red, yellow, green, cyan, blue, violet ], options3);

    var api = []; var single = []; var double = []; var rfid = []; var ears = []; var voice = [];';
    $max = 0;
    $max_api = 0;
    foreach($actions as $time => $action)
    {
      $single = $action["single"] ?? 0;
      $double = $action["double"] ?? 0;
      $rfid = $action["rfid"] ?? 0;
      $ears = $action["ears"] ?? 0;
      $voice = $action["voice"] ?? 0;
      $api = $action["api"] ?? 0;
      
      $js .= 'single.push(['.$time.', '.$single.']); double.push(['.$time.', '.$double.']);';
      $js .= 'rfid.push(['.$time.', '.$rfid.']); ears.push(['.$time.', '.$ears.']);';
      $js .= 'voice.push(['.$time.', '.$voice.']);';
      $js .= 'api.push(['.$time.', '.$api.']);';
      $max = max($max, $single + 0);
      $max = max($max, $double + 0);
      $max = max($max, $rfid + 0);
      $max = max($max, $ears + 0);
      $max = max($max, $voice + 0);
      $max_api = max($max_api, $api + 0);
    }
    $max_api += 10;
    $js .= 'var options4 = {
    yaxis: { min: 0, max: '.($max + 5).' },
    xaxis: { mode: "time", timeformat:"%H:%M", minTickSize: [1, "hour"] },
    colors: ["#944", "#C83", "#D90", "#494", "#059"],
    series: {
           stack: true,
           bars: {
            order: 1,
            barWidth: '.(1.5 * 60 * 1000).',
            show: true,
          }
         }
  };
  var plot = $.plot($("#actions-chart"), [ { label: "'.__tr("Ztamps").'",  data: rfid}, { label: "'.__tr("Single click").'",  data: single}, { label: "'.__tr("Double click").'",  data: double}, { label: "'.__tr("Ears").'",  data: ears}, { label: "'.__tr("Voice").'",  data: voice} ], options4);';

    $js .= 'var options6 = {
    yaxis: { min: 0, max: '.$max_api.' },
    xaxis: { mode: "time", timeformat:"%H:%M", minTickSize: [1, "hour"] },
    colors: ["#0088CC", "#222", "#666", "#BBB"],
    series: {
         lines: {
          lineWidth: 2,
          fill: true,
          fillColor: { colors: [ { opacity: 0.6 }, { opacity: 0.2 } ] },
          steps: false
        }
         }
  };
  var plot = $.plot($("#api-chart"), [ api ], options6);

    var maxi = [];';
    $max = max($maxis);
    foreach($maxis as $time => $maxi) {
      $js .= 'maxi.push(['.$time.', '.$maxi.']);';
    }
    $js .= 'var options5 = {
    yaxis: { min: 0, max: '.($max + 5).' },
    xaxis: { mode: "time", timeformat:"%m/%y", minTickSize: [1, "month"] },
    colors: ["#0088CC", "#222", "#666", "#BBB"],
    series: {
         lines: {
          lineWidth: 2,
          fill: true,
          fillColor: { colors: [ { opacity: 0.6 }, { opacity: 0.2 } ] },
          steps: false
        }
         },
  };
  var plot = $.plot($("#max-chart"), [ maxi ], options5);

});
</script>';
$ojnTemplate->setJS($js);
