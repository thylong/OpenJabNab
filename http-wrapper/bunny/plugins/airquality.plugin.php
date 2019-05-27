<?php 
$reload = false;
if(!empty($_POST['airqualitytime']) && !empty($_POST['airqualitycity']) ) {
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/airquality/schedule?action=add&time=".$_POST['airqualitytime']."&city=".urlencode($_POST['airqualitycity'])."&".$ojnAPI->getToken()));
	$reload = true;
}
else if(!empty($_GET['rmwbc'])) {
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/airquality/schedule?action=del&time=".$_GET['rmwbc']."&".$ojnAPI->getToken()));
	$reload = true;
} 
if($reload) {
	header("Location: bunny_plugin.php?p=airquality");
	exit;
}
?>
<br />
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Time for schedule") ?></label>
            <div class="controls">
		<input type="text" name="airqualitytime" class="dropdown-timepicker"/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("City") ?></label>
            <div class="controls">
<select name="airqualitycity">
<?php
if(!($cities = apcu_fetch(APC_PREFIX.'ojn_plugin_airquality_cities'))) {
	$cities = array();
	$citiesTemp = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/airquality/city?action=list&".$ojnAPI->getToken());
	foreach($citiesTemp as $country => $list) {
		if(!isset($cities[$country])) {
			$cities[$country] = array();
		}
		foreach(preg_split('/,/', $list) as $city) {
			$cities[$country][$country . '/' . $city] = $city;
		}
	}
	apcu_store(APC_PREFIX.'ojn_plugin_airquality_cities', $cities, 86400);
}
foreach($cities as $country => $list)
{
?>
<optgroup label="<?php echo $country ?>">
<?php
foreach($list as $key => $city)
{
?>
<option value="<?php echo $key ?>"><?php echo $city ?></option>
<?php
}
?>
</optgroup>
<?php
}
?>
</select>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <button class="btn"><?php echo __tr("Cancel") ?></button>
          </div>
</form>

<?php
$webcasts = $ojnAPI->getApiList("bunny/".$_SESSION['bunny']."/airquality/schedule?action=list&".$ojnAPI->getToken());
if($webcasts){
?>
<center>
<table class="table table-bordered table-striped span11">
	<tr>
		<th colspan="3"><?php echo __tr('Schedule list') ?></th>
	</tr>
	<tr>
		<th><?php echo __tr('Time') ?></th>
		<th><?php echo __tr('Name') ?></th>
		<th><?php echo __tr('Actions') ?></th>
	</tr>
<?php
	$i = 0;
	foreach($webcasts as $item) {
	$key = $item->key;
	$city = $item->value;
	if(preg_match('|/|', $city)) {
		$s = preg_split('|/|', $city);
		$city = $s[1] . ' ('.$s[0].')';
	}
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><?php echo urldecode($key) ?></td>
		<td><?php echo $city ?></td>
		<td width="15%"><a href="bunny_plugin.php?p=airquality&rmwbc=<?php echo $key ?>" class="btn btn-danger btn-small"><i class="icon-trash icon-large"></i> <?php echo __tr('Remove') ?></a></td>
	</tr>
<?php  } ?>
</table>
<?php } ?>
<?php
/*
$ojnTemplate->setJS("	<script>$('.dropdown-timepicker').timepicker({
		defaultTime: 'current',
		minuteStep: 15,
		disableFocus: true,
		template: 'dropdown'
	});</script>");
*/
?>
