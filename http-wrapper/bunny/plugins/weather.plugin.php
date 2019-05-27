<?php
$reload = false;
$Ztamps = $ojnAPI->GetListofZtamps(false);

if(!empty($_POST)) {
	if(isset($_POST['weather_city'])) {
		$_SESSION['subtab'] = "weather_city";
		list($code, $name) = preg_split("/\|/", $_POST['weather_city']);
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/weather/addcity?city=".urlencode($code)."&name=".urlencode($name)."&".$ojnAPI->getToken()));
		$reload = true;
	}
	if(!empty($_POST['webcastT']) && !empty($_POST['webcastC'])) {
		$time = trim(preg_replace("|[\.h]|", ":", $_POST['webcastT']));
		if(preg_match("|\d{1,2}:\d{2}|", $time)) {
			Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/weather/addwebcast?city=".urlencode($_POST['webcastC'])."&time=".$time."&".$ojnAPI->getToken()));
		} else {
			Message::AddError(__tr("Invalid time format"));
		}
		$_SESSION['subtab'] = "weather_webcast";
		$reload = true;
	}
	if(isset($_POST['acity']) && isset($_POST['atag'])) {
		$_SESSION['subtab'] = "weather_rfid";
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/weather/addrfid?tag=".$_POST['atag']."&city=".urlencode($_POST['acity'])."&".$ojnAPI->getToken()));
		$reload = true;
	}
	if(isset($_POST['rtag'])) {
		$_SESSION['subtab'] = "weather_rfid";
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/weather/removerfid?tag=".$_POST['rtag']."&".$ojnAPI->getToken()));
		$reload = true;
	}
	if(isset($_POST['lang'])) {
		$_SESSION['subtab'] = "weather_city";
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/weather/setlang?lg=".$_POST['lang']."&".$ojnAPI->getToken()));
		$reload = true;
	}
}
else if(!empty($_GET['rp'])) {
	$_SESSION['subtab'] = "weather_city";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/weather/removecity?city=".urlencode($_GET['rp'])."&".$ojnAPI->getToken()));
	$reload = true;
}
else if(!empty($_GET['d'])) {
	$_SESSION['subtab'] = "weather_city";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/weather/setdefaultcity?city=".urlencode($_GET['d'])."&".$ojnAPI->getToken()));
	$reload = true;
}
else if(!empty($_GET['rw'])) {
	$_SESSION['subtab'] = "weather_webcast";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/weather/removewebcast?time=".$_GET['rw']."&".$ojnAPI->getToken()));
	$reload = true;
}

$Langs = array('fr'=>__tr('French'),'en'=>__tr('English'), 'es'=>__tr('Spanish'), 'de' => __tr('German'));
/*
if(!empty($_POST['a'])) {
	} else if($_POST['a'] == "rfidadd") {
	var_dump($_POST);
		if(!empty($_POST['RfCity']) && !empty($_POST['Tag_Rfa']) && isset($Ztamps[$_POST['Tag_Rfa']])) {
			$retour = $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/weather/addrfid?tag=".$_POST['Tag_Rfa']."&city=".urlencode($_POST['RfCity'])."&".$ojnAPI->getToken());
			$_SESSION['message'] = isset($retour['ok']) ? $retour['ok'] : "Error : ".$retour['error'];
			header("Location: bunny_plugin.php?p=weather");
		}
	} else if($_POST['a'] == "rfidd") {
		if(!empty($_POST['Tag_Rf']) && isset($Ztamps[$_POST['Tag_Rf']])) {
			$retour = $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/weather/removerfid?tag=".$_POST['Tag_Rf']."&".$ojnAPI->getToken());
			$_SESSION['message'] = isset($retour['ok']) ? $retour['ok'] : "Error : ".$retour['error'];
			header("Location: bunny_plugin.php?p=weather");
		}
	} else if($_POST['a'] == "setlang") {
		if(!empty($_POST['Lang']) && isset($Langs[$_POST['Lang']])) {
			$retour = $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/weather/setlang?lg=".$_POST['Lang']."&".$ojnAPI->getToken());
			$_SESSION['message'] = isset($retour['ok']) ? $retour['ok'] : "Error : ".$retour['error'];
			header("Location: bunny_plugin.php?p=weather");
		}
	}
}
*/
$default = $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/weather/getdefaultcity?".$ojnAPI->getToken());
$default = isset($default['value']) ? (string)($default['value']) : '';
$pList = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/weather/getcitieslist?".$ojnAPI->getToken());
$wList = $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/weather/getwebcastslist?".$ojnAPI->getToken());
$Assoc = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/weather/listrfid?".$ojnAPI->getToken());
$lang =  $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/weather/getlang?".$ojnAPI->getToken());
$lang = isset($lang['value']) ? (string)($lang['value']) : 'fr';


if(!isset($_SESSION['subtab']) || !preg_match("|^weather_|", $_SESSION['subtab'])) {
	$_SESSION['subtab'] = "weather_city";
}
if($reload)
{
	header("Location: bunny_plugin.php?p=weather");
	exit();
}
?>

		<div class="tabbable">
		<ul class="nav nav-tabs">
		  <li<?php echo $_SESSION['subtab'] == 'weather_city' ? ' class="active"' : '' ?>><a href="#city" data-toggle="tab"><?php echo __tr('Cities List') ?></a></li>
		  <li<?php echo $_SESSION['subtab'] == 'weather_webcast' ? ' class="active"' : '' ?>><a href="#webcast" data-toggle="tab"><?php echo __tr('Webcasts') ?></a></li>
		  <li<?php echo $_SESSION['subtab'] == 'weather_rfid' ? ' class="active"' : '' ?>><a href="#rfid" data-toggle="tab"><?php echo __tr('RFID') ?></a></li>
		</ul>
		<br />
		
			<div class="tab-content">
				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'weather_city' ? ' active' : '' ?>" id="city">
<p>
<?php echo __tr('To use this plugin, you need to retrieve the code associated to your city') ?>
<ol>
<li><?php echo __tr('Enter the name of your city in the first text field') ?></li>
<li><?php echo __tr('Click on the "%1" button', __tr('Search the location code')) ?></li>
<li><?php echo __tr('Choose the best matching city in the list, and click on "%1"', __tr("Use this city")) ?>"</li>
<li><?php echo __tr('Save') ?></li>
</ol>
</p>
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Add a city") ?></label>
            <div class="controls">
<div class="input-append"><input type="text" id="weather_search" class="span2" value=""><a onclick="launchSearch()" class="btn"><?php echo __tr('Search the location code') ?></a></div>
<div id="weather_results">&nbsp;</div>
            </div>
            <div class="controls">
<div class="input-append"><input type="text" id="weather_city_code" class="span2" value=""><span class="add-on" id="weather_city_name"></span></div>
<input name="weather_city" type="hidden" id="weather_city" value="">
<p class="help-block"><?php echo __tr('Location ID used by Yahoo weather.') ?></p>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <button class="btn"><?php echo __tr("Cancel") ?></button>
          </div>
</form>
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Language") ?></label>
            <div class="controls">
<select name="lang">
<?php foreach($Langs as $k => $v): ?>
<option value="<?php echo $k ?>"<?php if($lang == $k): ?> selected="selected"<?php endif; ?>><?php echo $v ?></option>
<?php endforeach; ?>
</select>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <button class="btn"><?php echo __tr("Cancel") ?></button>
          </div>
</form>
				</div>

				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'weather_webcast' ? ' active' : '' ?>" id="webcast">
<form method="post" class="form-horizontal">
<?php echo __tr("Add a webcast at (hh:mm)") ?>
 <div class="input-append bootstrap-timepicker">
<input id="timepicker2" type="text" name="webcastT" class="input-small"><span class="add-on">
<i class="icon-time"></i>
</span>
</div>
<?php $ojnTemplate->setJs('<script type="text/javascript">jQuery("#timepicker2").timepicker({minuteStep: 1,showSeconds: false,showMeridian: false});</script>'); ?>
<?php echo __tr("for city") ?>
&nbsp;<select name="webcastC">
	<option value=""></option>
	<?php if(!empty($pList))
	foreach($pList as $code => $item) { ?>
		<option value="<?php echo $code ?>"><?php echo $item; ?></option>
	<?php } ?>
</select><br />
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <button class="btn"><?php echo __tr("Cancel") ?></button>
          </div>
</form>
				</div>



				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'weather_rfid' ? ' active' : '' ?>" id="rfid">
<form method="post" class="form-horizontal">
<?php echo __tr("Use") ?> <select name="acity" class="span4">
	<option value=""></option>
	<?php  if(!empty($pList))
	foreach($pList as $code => $item) { ?>
		<option value="<?php echo $code ?>"><?php echo urldecode($item); ?></option>
	<?php } ?>
</select> <?php echo __tr("on Ztamp") ?> <select name="atag">
    <option value=""></option>
	<?php foreach($Ztamps as $k=>$v): ?>
	<option value="<?php echo $k; ?>"><?php echo $v; ?> (<?php echo $k; ?>)</option>
	<?php endforeach; ?>
	</select><br />
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
          </div>
</form>
<?php if(count($Assoc)): ?>
<form method="post" class="form-horizontal">
<?php echo __tr("Delete Ztamp association") ?>
&nbsp;<select name="rtag">
    <option value=""><?php echo __tr('Choose an association') ?></option>
	<?php foreach($Assoc as $k=>$v): ?>
	<option value="<?php echo $k; ?>"><?php echo $v; ?> (<?php echo $Ztamps[$k] . " - " . $k; ?>)</option>
	<?php endforeach; ?>
	</select>


          <div class="form-actions">
            <button class="btn btn-danger" type="submit"><?php echo __tr("Remove") ?></button>
          </div>
<?php endif; ?>
</form>
				</div>
			</div>
		</div>

<?php /*
<input type="radio" name="a" value="setlang" /> Default Language (for weather infos only for now) <select name="Lang">
	<option value=""></option>
	<?php  if(!empty($Langs))
	foreach($Langs as $k=>$v) { ?>
		<option value="<?php echo urldecode($k) ?>" <?php echo ($lang == $k) ? 'selected="true"': ''; ?>><?php echo urldecode($v); ?></option>
	<?php } ?>
</select><br />
*/ ?>

<?php
if(!empty($pList)) {
?>
<hr />
<center>
<table style="width: 80%">
	<tr>
		<th colspan="4"><?php echo __tr("Cities") ?></th>
	</tr>
	<tr>
		<th><?php echo __tr("City") ?></th>
		<th><?php echo __tr("Code") ?></th>
		<th colspan="2"><?php echo __tr("Actions") ?></th>
	</tr>
<?php
	$i = 0;
	foreach($pList as $code => $item) {
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><?php echo urldecode($item) ?></td>
		<td><i><?php echo $code ?></i></td>
		<td width="15%"><a class="btn btn-small btn-danger" href="bunny_plugin.php?p=weather&rp=<?php echo $code ?>"><?php echo __tr("Remove") ?></a></td>
		<td width="15%"><?php if($default != $code) { ?><a href="bunny_plugin.php?p=weather&d=<?php echo $code ?>" class="btn btn-small btn-primary"><?php echo __tr("Set as default") ?></a><?php } else { echo __tr("Default city"); } ?></td>
	</tr>
<?php } ?>
</table>
<?php
}
if(isset($wList['list']->item)){
?>
<hr />
<center>
<table style="width: 80%">
	<tr>
		<th colspan="3"><?php echo __tr("Webcasts") ?></th>
	</tr>
	<tr>
		<th><?php echo __tr("Time") ?></th>
		<th><?php echo __tr("City") ?></th>
		<th><?php echo __tr("Actions") ?></th>
	</tr>
<?php
	$i = 0;
	foreach($wList['list']->item as $item) {
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><?php echo urldecode($item->key) ?></td>
		<td><?php echo $pList[(string)$item->value] ?></td>
		<td width="15%"><a href="bunny_plugin.php?p=weather&rw=<?php echo $item->key ?>" class="btn btn-small btn-danger"><?php echo __tr("Remove") ?></a></td>
	</tr>
<?php  } ?>
</table>
<?php } ?>
<script>
function launchSearch()
{
$.get('searchCity.php?city=' + $("#weather_search").val(), function(data) {
  $('#weather_results').html(data);
});
}
function useCity(id, name)
{
$('#weather_city_code').val(id);
$('#weather_city_name').html(name);
$('#weather_city').val(id + "|" + name);
}
</script>
