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
if(count($_POST) >= 4) {
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/sleep/config?action=add&wakeOn=".$_POST['w2']."&wakeAt=".$_POST['w1']."&sleepOn=".$_POST['s2']."&sleepAt=".$_POST['s1']."&".$ojnAPI->getToken()));
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
/*
$wakeup = "";
$sleep = "";
*/
$lists = $ojnAPI->getApiList("bunny/".$_SESSION['bunny']."/sleep/config?action=list&".$ojnAPI->getToken());
?>
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr('Go to sleep') ?></label>
            <div class="controls">
<select name="s2">
<?php foreach($days as $d => $day): ?>
<option value="<?php echo $d ?>"><?php echo $day ?></option>
<?php endforeach; ?>
</select>&nbsp;&nbsp;
<div class="input-append bootstrap-timepicker">
<input type="text" name="s1" value="" class="timepicker input-small"><span class="add-on">
<i class="icon-time"></i>
</span>
</div>
            </div>
          </div>

          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr('Wake up') ?></label>
            <div class="controls">
<select name="w2">
<?php foreach($days as $d => $day): ?>
<option value="<?php echo $d ?>"><?php echo $day ?></option>
<?php endforeach; ?>
</select>&nbsp;&nbsp;
<div class="input-append bootstrap-timepicker">
<input type="text" name="w1" value="" class="timepicker input-small"><span class="add-on">
<i class="icon-time"></i>
</span>
</div>
            </div>
          </div>

          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Add") ?></button>
            <a href="bunny_plugin.php?p=sleep&wake=true" class="btn btn-info"><?php echo __tr("Wake up") ?></a>
            <a href="bunny_plugin.php?p=sleep&sleep=true" class="btn btn-info"><?php echo __tr("Go to sleep") ?></a>
          </div>
</form>
<?php
$ojnTemplate->setJs('<script type="text/javascript">jQuery(".timepicker").timepicker({minuteStep: 1,showSeconds: false,showMeridian: false});</script>');
/*
?>
<form method="post" class="form-horizontal">
<?php
	foreach($days as $id => $day):
?>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo $day ?></label>
            <div class="controls">

 <div class="input-prepend input-append bootstrap-timepicker">
<span class="add-on"><?php echo __tr('Wake up') ?></span><input type="text" name="w[<?php echo $id-1; ?>]" value="<?php echo $lists[$id-1]; ?>" class="timepicker input-small"><span class="add-on">
<i class="icon-time"></i>
</span>
</div>
&nbsp;&nbsp;&nbsp;&nbsp;
 <div class="input-prepend input-append bootstrap-timepicker">
<span class="add-on"><?php echo __tr('Go to sleep') ?></span><input type="text" name="s[<?php echo $id-1; ?>]" value="<?php echo $lists[$id+6]; ?>" class="timepicker input-small"><span class="add-on">
<i class="icon-time"></i>
</span>
</div>
            </div>
          </div>
<?php
	endforeach;
?>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <a href="bunny_plugin.php?p=sleep&wake=true" class="btn btn-info"><?php echo __tr("Wake up") ?></a>
            <a href="bunny_plugin.php?p=sleep&sleep=true" class="btn btn-info"><?php echo __tr("Go to sleep") ?></a>
          </div>
</form>
<?php
*/
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
<center>
    <canvas id="myCanvas" width="<?php echo $size*24 + 350?>" height="200"></canvas>
</center>
    <script>
      var canvas = document.getElementById('myCanvas');
      var context = canvas.getContext('2d');

<?php for($i=1; $i<=7; $i++): ?>
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
<?php endfor; ?>
<?php foreach($sleeps as $day => $list): ?>
<?php foreach($list as $sleep): ?>
      context.beginPath();
      context.rect(<?php echo $offset+$sleep[0] ?>, <?php echo 10+($day-1)*20 ?>, <?php echo $sleep[1] ?>, 20);
      context.fillStyle = 'blue';
      context.fill();
      context.lineWidth = 1;
      context.strokeStyle = 'black';
      context.stroke();
      context.closePath();
<?php endforeach; ?>
<?php endforeach; ?>

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
foreach($lists as $i => $s) {
  if(empty($s))
    continue;
  list($s1, $s2, $w1, $w2) = preg_split('/\|/', $s);
?>
<tr>
<td><?php echo $s1 ?>, <?php echo $days[$s2] ?></td>
<td><?php echo $w1 ?>, <?php echo $days[$w2] ?></td>
<td><a href="bunny_plugin.php?p=sleep&rm=<?php echo $i ?>"><?php echo __tr('Remove') ?></a></td>
</tr>
<?php
}
?>
</table>
