<?php
$days = array(
0 => __tr("Every day"),
1 => __tr("Monday"),
2 => __tr("Tuesday"),
3 => __tr("Wednesday"),
4 => __tr("Thursday"),
5 => __tr("Friday"),
6 => __tr("Saturday"),
7 => __tr("Sunday")
);
$reload = false;
if(isset($_POST['scheduleT']) && isset($_POST['scheduleV']) && isset($_POST['scheduleD'])) {
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/volume/schedule?action=add&vol=".urlencode($_POST['scheduleV'])."&time=".$_POST['scheduleT']."&day=".$_POST['scheduleD']."&".$ojnAPI->getToken()));
	$reload = true;
}
if(isset($_GET['rt']) && isset($_GET['rd'])) {
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/volume/schedule?action=del&day=".$_GET['rd']."&time=".$_GET['rt']."&".$ojnAPI->getToken()));
	$reload = true;
}

if(isset($_POST['sound'])) {
	if(isset($_POST['save'])) {
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/volume/sound?action=set&vol=".$_POST['sound']."&".$ojnAPI->getToken()));
	} elseif(isset($_POST['test'])) {
		$_SESSION['sound'] = $_POST['sound'];
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/volume/sound?action=test&volume=".$_POST['sound']."&".$ojnAPI->getToken()));
	}
	$reload = true;
}

if($reload)
{
	header("Location: ?p=volume");
	exit();
}
$current = $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/volume/sound?action=read&".$ojnAPI->getToken());
$current = isset($current['ok']) ? $current['ok'] : __tr("Not available");
$set = $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/volume/sound?action=get&".$ojnAPI->getToken());
$set = isset($_SESSION['sound']) ? $_SESSION['sound'] : (isset($set['ok']) ? $set['ok'] : '0');
unset($_SESSION['sound']);
$wList = $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/volume/schedule?action=list&".$ojnAPI->getToken());
?>
<form method="post" class="form-horizontal">
  <div class="form-group row">
    <label class="col-sm-3 col-form-label" for="currentV"><?php echo __tr("Current sound volume") ?></label>
    <div class="col-sm-2 input-group">
      <input type="text" class="form-control disabled" id="currentV" disabled value="<?php echo $current ?>"/>
    </div>
  </div>
  <div class="form-group row">
    <label class="col-sm-3 col-form-label" for="sound"><?php echo __tr("Set volume to") ?></label>
    <div class="col-sm-2 input-group">
      <input type="text" class="form-control" name="sound" value="<?php echo $set ?>"/>
    </div>
    <div class="col-sm-2 input-group">
      <button name="test" class="btn btn-sm btn-success" type="submit"><?php echo __tr("Test") ?></button>
    </div>
  </div>
  <div class="form-group row">
    <div class="col-sm-4 offset-sm-3 input-group">
      <ul>
        <li><?php echo __tr("%1 : volume set with button", 0) ?></li>
        <li><?php echo __tr("%1 : maximum volume", 1) ?></li>
        <li><?php echo __tr("%1 : minimum volume", 255) ?></li>
      </ul>
    </div>
  </div>
  <div class="form-group row">
    <div class="col-sm-1 offset-sm-3">
      <button name="save" class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
    </div>
  </div>
</form>
    <hr />
    <form method="post" class="form-horizontal">
      <div class="form-group row">
        <label class="col-sm-3 col-form-label" for="scheduleT"><?php echo __tr("Automatic change at (hh:mm)") ?></label>
        <div class="col-sm-2 input-group">
          <div class="input-group clockpicker" data-autoclose="true">
            <input type="text" name="scheduleT" class="form-control" value="<?php echo date('H:i'); ?>">
            <div class="input-group-text input-group-addon">
              <i class="icon-time"></i>
            </div>
          </div>
          <script type="text/javascript">
            $('.clockpicker').clockpicker({'default': 'now'});
          </script>
        </div>
      </div>
      <div class="form-group row">
        <label class="col-sm-3 col-form-label" for="scheduleD"><?php echo __tr("Day") ?></label>
        <div class="col-sm-2 input-group">
          <select name="scheduleD" class="form-control">
            <?php foreach($days as $d => $day): ?>
            <option value="<?php echo $d ?>"><?php echo $day ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-group row">
        <label class="col-sm-3 col-form-label" for="scheduleV"><?php echo __tr("Sound volume") ?></label>
        <div class="col-sm-2 input-group">
          <input class="form-control" type="text" name="scheduleV">
        </div>
      </div>
      <div class="form-group row">
        <div class="col-sm-1 offset-sm-3">
          <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
        </div>
      </div>
    </form>

<?php
if(isset($wList['list']->item)){
?>
<hr />
<center>
<table class="table table-bordered table-striped span11">
	<tr>
		<th colspan="4"><?php echo __tr('Schedules') ?></th>
	</tr>
	<tr>
		<th><?php echo __tr('Day') ?></th>
		<th><?php echo __tr('Time') ?></th>
		<th><?php echo __tr('New volume') ?></th>
		<th><?php echo __tr('Actions') ?></th>
	</tr>
<?php
	$i = 0;
	foreach($wList['list']->item as $item) {
	list($day, $time) = explode("|", $item->key);
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><?php echo $days[$day] ?></td>
		<td><?php echo $time ?></td>
		<td><?php echo $item->value.($item->value == 0 ? ' ('.__tr('Sound controlled by cursor').')' : '') ?></td>
		<td width="15%"><a class="btn btn-danger btn-small" href="bunny_plugin.php?p=sound&rd=<?php echo $day ?>&rt=<?php echo $time ?>"><i class="icon-trash icon-large"></i> <?php echo __tr('Remove') ?></a></td>
	</tr>
<?php  } ?>
</table>
<?php } ?>
</form>
<script>
function updateSound() {
$.get('<?php echo ROOT_WWW_EXTAPI."bunny/".$_SESSION['bunny']."/volume/sound?action=poll&".$ojnAPI->getToken() ?>', function(data) {
});
$.get('<?php echo ROOT_WWW_EXTAPI."bunny/".$_SESSION['bunny']."/volume/sound?action=read&".$ojnAPI->getToken() ?>', function(data) {
  $('#current').val(data.getElementsByTagName("ok")[0].childNodes[0].nodeValue);
});
}
refreshIntervalId = setInterval("updateSound()", 1000<?php if(!$Infos['isAdmin']): ?>0<?php endif; ?>);

</script>
