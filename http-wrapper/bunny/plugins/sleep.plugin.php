<?php
$days = array(
  1 => __tr('Monday'),
  2 => __tr('Tuesday'),
  3 => __tr('Wednesday'),
  4 => __tr('Thursday'),
  5 => __tr('Friday'),
  6 => __tr('Saturday'),
  7 => __tr('Sunday'),
);
$reload = false;
if(!empty($_POST['s1']) && !empty($_POST['s2']) && !empty($_POST['w1']) && !empty($_POST['w2']))
{
  $s1 = $_POST['s1']; $s2 = $_POST['s2'];
  $w1 = $_POST['w1']; $w2 = $_POST['w2'];
  $ret = $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/sleep/config?action=add&wakeOn=".$w2."&wakeAt=".$w1."&sleepOn=".$s2."&sleepAt=".$s1."&".$ojnAPI->getToken());
  Message::AddFromApi($ret);
  if(isset($ret['ok']))
    $_SESSION['sleep'] = array('s1'=>$s1, 's2'=>$s2, 'w1'=>$w1, 'w2'=>$w2);
  $reload = true;
}
if(isset($_GET['wake']) && $_GET['wake']=="true")
{
  Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/sleep/sleep?action=wakeup&".$ojnAPI->getToken()));
  $reload = true;
}
if(isset($_GET['rm']))
{
  Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/sleep/config?action=del&id=".$_GET['rm']."&".$ojnAPI->getToken()));
  $reload = true;
}
if(isset($_GET['sleep']) && $_GET['sleep']=="true")
{
  Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/sleep/sleep?action=sleep&".$ojnAPI->getToken()));
  $reload = true;
}
if($reload) {
  header("Location: bunny_plugin.php?p=sleep");
  exit;
}
$lists = $ojnAPI->getApiList("bunny/".$_SESSION['bunny']."/sleep/config?action=list&".$ojnAPI->getToken());

$s1 = '22:00'; $select_s2 = 1;
$w1 = '09:00'; $select_w2 = 2;
if(!empty($_SESSION['sleep']['s1']) && !empty($_SESSION['sleep']['s2']) &&
   !empty($_SESSION['sleep']['w1']) && !empty($_SESSION['sleep']['w2'])
  )
{
  $s1 = $_SESSION['sleep']['s1'];
  $select_s2 = ((int)$_SESSION['sleep']['s2'])%7 + 1;
  $w1 = $_SESSION['sleep']['w1'];
  $select_w2 = ((int)$_SESSION['sleep']['w2'])%7 + 1;
  unset($_SESSION['sleep']);
}
?>
<form method="post" class="form-horizontal">
  <fieldset  class="border p-3">
    <legend><h6><?php echo __tr('Add a new sleep time'); ?></h6></legend>
    <div class="form-group row">
      <label class="col-sm-1 col-form-label" for="s2"><?php echo __tr('Go to sleep') ?></label>
      <div class="col-sm-4">
        <select name="s2" class="form-control">
          <?php foreach($days as $d => $day): ?>
          <option value="<?php echo $d; ?>"<?php echo $d == $select_s2 ? ' selected="selected"' :''; ?>><?php echo $day; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-sm-2 input-group">
        <div class="input-group-preprend">
          <div class="input-group-text"><i class="icon-time"></i></div>
        </div>
        <input type="text" name="s1" value="<?php echo $s1; ?>" class="timepicker form-control  text-center">
      </div>
    </div>
    <div class="form-group row">
      <label class="col-sm-1 col-form-label" for="w2"><?php echo __tr('Wake up') ?></label>
      <div class="col-sm-4">
        <select name="w2" class="form-control">
          <?php foreach($days as $d => $day): ?>
          <option value="<?php echo $d; ?>"<?php echo $d == $select_w2 ? ' selected="selected"' :''; ?>><?php echo $day; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-sm-2 input-group">
        <div class="input-group-preprend">
          <div class="input-group-text"><i class="icon-time"></i></div>
        </div>
        <input type="text" name="w1" value="<?php echo $w1; ?>" class="timepicker form-control  text-center">
      </div>
    </div>

    <div class="form-group row">
      <div class="col-sm-7 input-group text-center">
        <button class="btn btn-primary" type="submit"><?php echo __tr("Add") ?></button>
      </div>
    </div>
  </fieldset>
  <div class="form-group row mt-2">
    <label class="col-sm-1 col-form-label" ><?php echo __tr('Actions'); ?></label>
    <div class="col-sm-6">
      <a href="bunny_plugin.php?p=sleep&wake=true" class="btn btn-info"><?php echo __tr("Wake up") ?></a>
      <a href="bunny_plugin.php?p=sleep&sleep=true" class="btn btn-info"><?php echo __tr("Go to sleep") ?></a>
    </div>
  </div>
</form>
<script type="text/javascript">
  $(".timepicker").timepicker(
    {
      interval:30,
      timeFormat: 'HH:mm',
      /*defaultTime: 'now',*/
      dropdown: true,
      dynamic: false,
      minTime: '00:00',
      maxTime: '23:30',
      startTime: '00:00',
    });
</script>
<?php
global $size;
$size = 30;
$offset = 100;
class SleepTime {
  var $sleepAt;
  var $sleepOn;
  var $wakeAt;
  var $wakeOn;
}
function toSleepTime($str) {
  list($s1, $s2, $w1, $w2) = preg_split('/\|/', $str);
  $s = new sleepTime();
  $s->sleepAt = $s1;
  $s->sleepOn = $s2;
  $s->wakeAt = $s3;
  $s->wakeOn = $s4;
}
$sleeps = array(
  1 => array(),
  2 => array(),
  3 => array(),
  4 => array(),
  5 => array(),
  6 => array(),
  7 => array(),
);
function toMinute($s) {
  if(preg_match('/:/', $s)) {
    list($h, $m) = preg_split('/:/', $s);
    return $m + 60 * $h;
  }
  return $s;
}
function toSize($m) {
  global $size;
  return $m * (24*$size) / 1440;
}
foreach($lists as $s) {
  if(preg_match('/\|/', $s))
  {
    list($s1, $s2, $w1, $w2) = preg_split('/\|/', $s);
    $s1 = toMinute($s1);
    $w1 = toMinute($w1);

    if($w2 < $s2 || ($w2 == $s2 && $w1 < $s1)) {
      $w2 += 7;
    }

    if($w2 > $s2)
    {
      $sleeps[$s2][] = array(toSize($s1), toSize(1440) - toSize($s1));
      for($i=$s2+1; $i<$w2; $i++) $sleeps[($i-1)%7 + 1][] = array(0, toSize(1440));
      $sleeps[($w2-1)%7 + 1][] = array(0, toSize($w1));
    }
    else
    {
      $sleeps[$w2][] = array(toSize($s1), toSize($w1)-toSize($s1));
    }
  }
}
?>
<div>
  <h5><?php echo __tr('Schedule'); ?></h5>
  <canvas id="myCanvas" width="<?php echo $size*24 + 350?>" height="200"></canvas>
</div>
<script>
  var canvas = document.getElementById('myCanvas');
  var context = canvas.getContext('2d');

  <?php
  for($i=1; $i<=7; $i++): ?>
  context.font = "bold 12px sans-serif";
  context.fillStyle = 'black';
  context.fillText("<?php echo $days[$i] ?>", 10, <?php echo 25+($i-1)*20 ?>);

  context.beginPath();
  context.rect(<?php echo $offset ?>, <?php echo 10+($i-1)*20 ?>, <?php echo $size*24?>, 20);
  context.fillStyle = 'yellow';
  context.fill();
  context.lineWidth = 1;
  context.strokeStyle = 'black';
  context.stroke();
  context.closePath();
  <?php
  endfor;

  foreach($sleeps as $day => $list):
    foreach($list as $sleep): ?>
  context.beginPath();
  context.rect(<?php echo $offset+$sleep[0] ?>, <?php echo 10+($day-1)*20 ?>, <?php echo $sleep[1] ?>, 20);
  context.fillStyle = 'blue';
  context.fill();
  context.lineWidth = 1;
  context.strokeStyle = 'black';
  context.stroke();
  context.closePath();
  <?php
    endforeach;
  endforeach; ?>

  <?php for($i=0; $i<3; $i++): ?>
  context.beginPath();
  context.strokeStyle = 'black';
  context.setLineDash([1,5]);
  context.moveTo(<?php echo $offset+6*($i+1)*($size) ?>, 10);
  context.lineTo(<?php echo $offset+6*($i+1)*($size) ?>,150);
  context.stroke();
  context.closePath();
  <?php endfor; ?>

</script>
<table class="table table-bordered table-striped span11">
  <tr>
    <th><?php echo __tr('Go to sleep') ?></th>
    <th><?php echo __tr('Wake up') ?></th>
    <th><?php echo __tr('Actions') ?></th>
    </tr>
  <?php
  foreach($lists as $i => $s):
    if(empty($s))
      continue;
    list($s1, $s2, $w1, $w2) = preg_split('/\|/', $s);
  ?>
  <tr>
    <td><?php echo $s1 ?>, <?php echo $days[$s2] ?></td>
    <td><?php echo $w1 ?>, <?php echo $days[$w2] ?></td>
    <td><a href="bunny_plugin.php?p=sleep&rm=<?php echo $i ?>"><?php echo __tr('Remove') ?></a></td>
  </tr>
  <?php endforeach; ?>
</table>
