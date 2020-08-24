<?php
$Ztamps = $ojnAPI->GetListofZtamps(false);
$days = array(
0 => __tr('Every day'),
-1 => __tr('During the week'),
-2 => __tr('During the week-end'),
1 => __tr("Monday"),
2 => __tr("Tuesday"),
3 => __tr("Wednesday"),
4 => __tr("Thursday"),
5 => __tr("Friday"),
6 => __tr("Saturday"),
7 => __tr("Sunday")
);
$Ears = array(
"Left0" => __tr("Left ear, position %1", 0),
"Left1" => __tr("Left ear, position %1", 1),
"Left2" => __tr("Left ear, position %1", 2),
"Left3" => __tr("Left ear, position %1", 3),
"Left4" => __tr("Left ear, position %1", 4),
"Left5" => __tr("Left ear, position %1", 5),
"Left6" => __tr("Left ear, position %1", 6),
"Left7" => __tr("Left ear, position %1", 7),
"Left8" => __tr("Left ear, position %1", 8),
"Left9" => __tr("Left ear, position %1", 9),
"Left10" => __tr("Left ear, position %1", 10),
"Left11" => __tr("Left ear, position %1", 11),
"Left12" => __tr("Left ear, position %1", 12),
"Left13" => __tr("Left ear, position %1", 13),
"Left14" => __tr("Left ear, position %1", 14),
"Left15" => __tr("Left ear, position %1", 15),
"Left16" => __tr("Left ear, position %1", 16),
"Right0" => __tr("Right ear, position %1", 0),
"Right1" => __tr("Right ear, position %1", 1),
"Right2" => __tr("Right ear, position %1", 2),
"Right3" => __tr("Right ear, position %1", 3),
"Right4" => __tr("Right ear, position %1", 4),
"Right5" => __tr("Right ear, position %1", 5),
"Right6" => __tr("Right ear, position %1", 6),
"Right7" => __tr("Right ear, position %1", 7),
"Right8" => __tr("Right ear, position %1", 8),
"Right9" => __tr("Right ear, position %1", 9),
"Right10" => __tr("Right ear, position %1", 10),
"Right11" => __tr("Right ear, position %1", 11),
"Right12" => __tr("Right ear, position %1", 12),
"Right13" => __tr("Right ear, position %1", 13),
"Right14" => __tr("Right ear, position %1", 14),
"Right15" => __tr("Right ear, position %1", 15),
"Right16" => __tr("Right ear, position %1", 16),
);
$reload = false;
if(isset($_POST['addurl'])) {
	$_SESSION['subtab'] = "callurl_url";
	if(strlen(trim($_POST['addurl']))) {
		if(strlen(trim($_POST['addname']))) {
			Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/callurl/url?action=add&name=".urlencode($_POST['addname'])."&url=".urlencode($_POST['addurl'])."&".$ojnAPI->getToken()));
		} else {
			Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/callurl/url?action=add&url=".urlencode($_POST['addurl'])."&".$ojnAPI->getToken()));
		}
	} else {
		Message::AddError(__tr("URL can't be empty"));
	}
	$reload = true;
}
if(isset($_POST['scheduleT']) && isset($_POST['scheduleN']) && isset($_POST['scheduleD'])) {
	$_SESSION['subtab'] = "callurl_schedule";

	if($_POST['scheduleD'] >= 0)
	{
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/callurl/schedule?action=add&name=".$_POST['scheduleN']."&time=".$_POST['scheduleT']."&day=".$_POST['scheduleD']."&".$ojnAPI->getToken()));
	}
	else
	{
		if($_POST['scheduleD'] == -1)
		{
			$id = 0;
			for($d = 1; $d<=5; $d++)
			{
				Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/callurl/schedule?action=add&name=".$_POST['scheduleN']."&time=".$_POST['scheduleT']."&day=".$d."&".$ojnAPI->getToken()));
			}
		}
		if($_POST['scheduleD'] == -2)
		{
			$id = 0;
			for($d = 6; $d<=7; $d++)
			{
				Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/callurl/schedule?action=add&name=".$_POST['scheduleN']."&time=".$_POST['scheduleT']."&day=".$d."&".$ojnAPI->getToken()));
			}
		}
	}
	$reload = true;
}
if(isset($_POST['aurl']) && isset($_POST['aear'])) {
	$_SESSION['subtab'] = "callurl_ears";
	list($ear, $pos) = preg_split("|(\d+)|", $_POST['aear'], -1,  PREG_SPLIT_DELIM_CAPTURE |  PREG_SPLIT_NO_EMPTY  );
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/callurl/ear?action=add&ear=".$ear."&pos=".$pos."&url=".urlencode($_POST['aurl'])."&".$ojnAPI->getToken()));
	$reload = true;
}
if(isset($_POST['aurl']) && isset($_POST['avoice']) && strlen(trim(($_POST['avoice'])))) {
	$_SESSION['subtab'] = "callurl_voice";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/callurl/voice?action=add&command=".urlencode($_POST['avoice'])."&name=".urlencode(($_POST['aurl']))."&".$ojnAPI->getToken()));
	$reload = true;
}
if(isset($_POST['rvoice'])) {
	$_SESSION['subtab'] = "callurl_voice";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/callurl/voice?action=del&command=".($_POST['rvoice'])."&".$ojnAPI->getToken()));
	$reload = true;
}
if(isset($_POST['rear'])) {
	$_SESSION['subtab'] = "callurl_ears";
	list($ear, $pos) = preg_split("|(\d+)|", $_POST['rear'], -1,  PREG_SPLIT_DELIM_CAPTURE |  PREG_SPLIT_NO_EMPTY  );
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/callurl/ear?action=del&ear=".$ear."&pos=".$pos."&".$ojnAPI->getToken()));
	$reload = true;
}
if(isset($_POST['aurl']) && isset($_POST['atag']) && strlen($_POST['aurl'])) {
	$_SESSION['subtab'] = "callurl_rfid";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/callurl/rfid?action=add&tag=".$_POST['atag']."&name=".urlencode($_POST['aurl'])."&".$ojnAPI->getToken()));
	$reload = true;
}
if(isset($_POST['allvoiceurl'])) {
	$_SESSION['subtab'] = "callurl_voice";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/callurl/url?action=set&type=Voice&name=".urlencode($_POST['allvoiceurl'])."&".$ojnAPI->getToken()));
	$reload = true;
}
if(isset($_POST['alllefturl'])) {
	$_SESSION['subtab'] = "callurl_ears";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/callurl/url?action=set&type=LeftEar&name=".urlencode($_POST['alllefturl'])."&".$ojnAPI->getToken()));
	$reload = true;
}
if(isset($_POST['allrighturl'])) {
	$_SESSION['subtab'] = "callurl_ears";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/callurl/url?action=set&type=RightEar&name=".urlencode($_POST['allrighturl'])."&".$ojnAPI->getToken()));
	$reload = true;
}
if(isset($_POST['allrfidurl'])) {
	$_SESSION['subtab'] = "callurl_rfid";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/callurl/url?action=set&type=RFID&name=".urlencode($_POST['allrfidurl'])."&".$ojnAPI->getToken()));
	$reload = true;
}
if(isset($_POST['rtag'])) {
	$_SESSION['subtab'] = "callurl_rfid";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/callurl/rfid?action=del&tag=".$_POST['rtag']."&".$ojnAPI->getToken()));
	$reload = true;
}
else if(isset($_GET['rp'])) {
	$_SESSION['subtab'] = "callurl_url";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/callurl/url?action=del&name=".urlencode($_GET['rp'])."&".$ojnAPI->getToken()));
//echo urlencode(base64_decode($_GET['rp']));
	$reload = true;
}
else if(!empty($_GET['d'])) {
	$_SESSION['subtab'] = "callurl_url";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/callurl/url?action=set&type=Click&name=".urlencode($_GET['d'])."&".$ojnAPI->getToken()));
	$reload = true;
}
else if(isset($_GET['rwd']) && isset($_GET['rwt'])) {
	$_SESSION['subtab'] = "callurl_url";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/callurl/schedule?action=del&time=".$_GET['rwt']."&day=".$_GET['rwd']."&".$ojnAPI->getToken()));
	$reload = true;
}
$pList = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/callurl/url?action=list&".$ojnAPI->getToken());
foreach($pList as $k => $v) {
	$pList[$k] = htmlentities($v);
}
$wList = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/callurl/schedule?action=list&".$ojnAPI->getToken());
$Assoc = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/callurl/rfid?action=list&".$ojnAPI->getToken());
$Assocear = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/callurl/ear?action=list&".$ojnAPI->getToken());
$Assocvoice = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/callurl/voice?action=list&".$ojnAPI->getToken());
$defaults = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/callurl/url?action=get&".$ojnAPI->getToken());

if(!isset($_SESSION['subtab']) || !preg_match("|^callurl_|", $_SESSION['subtab'])) {
	$_SESSION['subtab'] = "callurl_url";
}
if($reload)
{
	header("Location: bunny_plugin.php?p=callurl");
	exit();
}
?>

		<div class="tabbable">
		<ul class="nav nav-tabs">
		  <li<?php echo $_SESSION['subtab'] == 'callurl_url' ? ' class="active"' : '' ?>><a href="#url" data-toggle="tab"><?php echo __tr('URL List') ?></a></li>
		  <li<?php echo $_SESSION['subtab'] == 'callurl_schedule' ? ' class="active"' : '' ?>><a href="#schedule" data-toggle="tab"><?php echo __tr('Schedules') ?></a></li>
		  <li<?php echo $_SESSION['subtab'] == 'callurl_rfid' ? ' class="active"' : '' ?>><a href="#rfid" data-toggle="tab"><?php echo __tr('RFID') ?></a></li>
		  <li<?php echo $_SESSION['subtab'] == 'callurl_ears' ? ' class="active"' : '' ?>><a href="#ears" data-toggle="tab"><?php echo __tr('Ears') ?></a></li>
		  <li<?php echo $_SESSION['subtab'] == 'callurl_voice' ? ' class="active"' : '' ?>><a href="#voice" data-toggle="tab"><?php echo __tr('Voice command') ?></a></li>
		</ul>
		<br />
		
			<div class="tab-content">
				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'callurl_url' ? ' active' : '' ?>" id="url">
<div>
<?php echo __tr("You can create dynamic url, with the following variables") ?>.
<?php echo __tr("Those variables will be replaced by values (or empty when not avaliable)") ?>.
<ul>
<li><?php echo __tr("BUNNYMAC will be replaced with MAC address (on all url)") ?></li>
<li><?php echo __tr("CLICTYPE will be replaced with 1 for a single click, 2 for a double click") ?></li>
<li><?php echo __tr("LEFTPOS will be replaced with left ear position (only when left ear is moved)") ?></li>
<li><?php echo __tr("RIGHTPOS will be replaced with right ear position (only when right ear is moved)") ?></li>
<li><?php echo __tr("ZTAMPSN will be replaced with ztamp serial number (only when using a ztamp)") ?></li>
<li><?php echo __tr("VOICECMD will be replaced with the voice command you ask (only when using voice command, and only available to VIP users)") ?></li>
</ul>
</div>
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Add an url") ?></label>
            <div class="controls">
		<input type="text" name="addurl" class="input-xlarge span6"/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Name") ?> (<?php echo __tr('Optional') ?>)</label>
            <div class="controls">
		<input type="text" name="addname" class="input-xlarge span6"/>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <button class="btn"><?php echo __tr("Cancel") ?></button>
          </div>
</form>
				</div>

				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'callurl_schedule' ? ' active' : '' ?>" id="schedule">
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Add a schedule at (hh:mm)") ?></label>
            <div class="controls">
 <div class="input-append bootstrap-timepicker">
<input id="timepicker2" type="text" name="scheduleT" class="input-small"><span class="add-on">
<i class="icon-time"></i>
</span>
</div>
<?php $ojnTemplate->setJs('<script type="text/javascript">jQuery("#timepicker2").timepicker({minuteStep: 1,showSeconds: false,showMeridian: false});</script>'); ?>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Day") ?></label>
            <div class="controls">
		<select name="scheduleD">
		<?php foreach($days as $d => $day) { ?>
			<option value="<?php echo $d ?>"><?php echo $day ?></option>
		<?php } ?>
		</select>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Url") ?></label>
            <div class="controls">
<select name="scheduleN">
	<option value=""></option>
	<?php if(!empty($pList))
	foreach($pList as $key => $item) { ?>
		<option value="<?php echo $key ?>"><?php echo $item; ?></option>
	<?php } ?>
</select>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <button class="btn"><?php echo __tr("Cancel") ?></button>
          </div>
</form>

				</div>

				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'callurl_rfid' ? ' active' : '' ?>" id="rfid">
<form method="post" class="form-horizontal">
          <div class="control-group">
		<label for="input01" class="control-label"><?php echo __tr("Call url for all RFID") ?></label>
            <div class="controls">
	<select name="allrfidurl" class="span6">
	<option value=""></option>
	<?php  if(!empty($pList))
	foreach($pList as $k => $item) { ?>
		<option <?php if($k == $defaults['RFID']): ?> selected="selected"<?php endif; ?>value="<?php echo urldecode($k) ?>"><?php echo urldecode($k); ?></option>
	<?php } ?>
</select>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
          </div>
</form>
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Launch") ?></label>
            <div class="controls">
	<select name="aurl" class="span5">
	<option value=""></option>
	<?php  if(!empty($pList))
	foreach($pList as $k => $item) { ?>
		<option value="<?php echo urldecode($item) ?>"><?php echo $k; ?></option>
	<?php } ?>
</select> <?php echo __tr("on Ztamp") ?> <select name="atag" class="select2" style="width: 300px">
    <option value=""></option>
	<?php foreach($Ztamps as $k=>$v): ?>
	<option value="<?php echo $k; ?>"><?php echo $v; ?> (<?php echo $k; ?>)</option>
	<?php endforeach; ?>
	</select><br />
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
          </div>
</form>
<?php if(count($Assoc)): ?>
<form method="post" class="form-horizontal">
<?php echo __tr("Delete Ztamp association") ?>
&nbsp;<select name="rtag" class="span8">
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

				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'callurl_ears' ? ' active' : '' ?>" id="ears">

<form method="post" class="form-horizontal">
          <div class="control-group">
		<label for="input01" class="control-label"><?php echo __tr("Call url for all left ear positions") ?></label>
            <div class="controls">
	<select name="alllefturl" class="span6">
	<option <?php if("" == $defaults['LeftEar']): ?> selected="selected"<?php endif; ?> value=""></option>
	<?php  if(!empty($pList))
	foreach($pList as $k => $item) { ?>
		<option <?php if($k == $defaults['LeftEar']): ?> selected="selected"<?php endif; ?>value="<?php echo urldecode($k) ?>"><?php echo urldecode($item); ?></option>
	<?php } ?>
</select>
            </div>
          </div>
          <div class="control-group">
		<label for="input01" class="control-label"><?php echo __tr("Call url for all right ear positions") ?></label>
            <div class="controls">
	<select name="allrighturl" class="span6">
	<option <?php if("" == $defaults['RightEar']): ?> selected="selected"<?php endif; ?> value=""></option>
	<?php  if(!empty($pList))
	foreach($pList as $k => $item) { ?>
		<option <?php if($k == $defaults['RightEar']): ?> selected="selected"<?php endif; ?> value="<?php echo urldecode($k) ?>"><?php echo urldecode($item); ?></option>
	<?php } ?>
</select>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
          </div>
</form>

<form method="post" class="form-horizontal">
<?php echo __tr("Launch") ?> <select name="aurl" class="span7">
	<option value=""></option>
	<?php  if(!empty($pList))
	foreach($pList as $k => $item) { ?>
		<option value="<?php echo urldecode($k) ?>"><?php echo urldecode($item); ?></option>
	<?php } ?>
</select> <?php echo __tr("on ear position") ?> <select name="aear">
    <option value=""></option>
	<?php foreach($Ears as $k=>$v): ?>
	<option value="<?php echo $k; ?>"><?php echo $v; ?></option>
	<?php endforeach; ?>
	</select><br />
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
          </div>
</form>
<?php if(array_unique(array_values($Assocear)) != array("")): ?>
<form method="post" class="form-horizontal">
<?php echo __tr("Delete ear position association") ?>
&nbsp;<select name="rear" class="span8">
    <option value=""><?php echo __tr('Choose an association') ?></option>
	<?php foreach($Assocear as $k=>$v): ?>
	<?php if(strlen(trim($v))): ?>
	<option value="<?php echo $k; ?>"><?php echo $v; ?> (<?php echo $Ears[$k]; ?>)</option>
	<?php endif; ?>
	<?php endforeach; ?>
	</select>


          <div class="form-actions">
            <button class="btn btn-danger" type="submit"><?php echo __tr("Remove") ?></button>
          </div>
<?php endif; ?>
</form>

				</div>




				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'callurl_voice' ? ' active' : '' ?>" id="voice">
<div class="alert alert-warning"><a class="close" data-dismiss="alert" href="#">×</a><?php echo __tr("Only available to VIP and premium users"); ?></div>

<form method="post" class="form-horizontal">
          <div class="control-group">
		<label for="input01" class="control-label"><?php echo __tr("Call url for all voice commands") ?></label>
            <div class="controls">
	<select name="allvoiceurl" class="span6">
	<option <?php if("" == $defaults['Voice']): ?> selected="selected"<?php endif; ?> value=""></option>
	<?php  if(!empty($pList))
	foreach($pList as $k => $item) { ?>
		<option <?php if($k == $defaults['Voice']): ?> selected="selected"<?php endif; ?>value="<?php echo urldecode($k) ?>"><?php echo urldecode($item); ?></option>
	<?php } ?>
</select>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
          </div>
</form>

<form method="post" class="form-horizontal">
<?php echo __tr("Launch") ?> <select name="aurl" class="span7">
	<option value=""></option>
	<?php  if(!empty($pList))
	foreach($pList as $k => $item) { ?>
		<option value="<?php echo $k ?>"><?php echo urldecode($item); ?></option>
	<?php } ?>
</select> <?php echo __tr("on voice command") ?> <input type="text" name="avoice">
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
          </div>
</form>
<form method="post" class="form-horizontal">
<?php if(count($Assocvoice)): ?>
<?php echo __tr("Delete a voice command association") ?>
&nbsp;<select name="rvoice" class="span8">
    <option value=""><?php echo __tr('Choose an association') ?></option>
	<?php foreach($Assocvoice as $k=>$v): ?>
	<option value="<?php echo $k; ?>"><?php echo $v." ".__tr('for command')." ".$k ?></option>
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

<?php
if(!empty($pList)) {
?>
<hr />
<center>
<table class="table table-bordered table-striped span11">
	<tr>
		<th colspan="4"><?php echo __tr('URL List') ?></th>
	</tr>
	<tr>
		<th class="span2"><?php echo __tr('Name') ?></th>
		<th class="span6"><?php echo __tr('URL') ?></th>
		<th colspan="2"><?php echo __tr('Actions') ?></th>
	</tr>
<?php
	$i = 0;
	foreach($pList as $k => $item) {
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><?php echo urldecode($k) ?></td>
		<td><?php echo urldecode($item) ?></td>
		<td class="span1"><a class="btn btn-danger" href="bunny_plugin.php?p=callurl&rp=<?php echo $k ?>"><?php echo __tr("Remove") ?></a></td>
		<td class="span2"><?php if($defaults['Click'] != $k) { ?><a class="btn btn-primary" href="bunny_plugin.php?p=callurl&d=<?php echo $k ?>"><?php echo __tr("Set as default") ?></a><?php } else { ?><?php echo __tr("Default url") ?><?php } ?></td>
	</tr>
<?php } ?>
</table>
<?php
}
if(!empty($wList)){
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
		<th><?php echo __tr('Name') ?></th>
		<th><?php echo __tr('Actions') ?></th>
	</tr>
<?php
	$i = 0;
	foreach($wList as $when => $item) {
		list($day, $time) = preg_split("/\|/", $when);
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><?php echo $days[$day] ?></td>
		<td><?php echo $time ?></td>
		<td><?php echo $pList[$item] ?></td>
		<td width="15%"><a href="bunny_plugin.php?p=callurl&rwt=<?php echo $time ?>&rwd=<?php echo $day ?>" class="btn btn-danger"><?php echo __tr('Remove') ?></a></td>
	</tr>
<?php  } ?>
</table>
<?php } ?>
</fieldset>
<?php
$js = '<link href="/media/js/select2.css" rel="stylesheet"/><script src="/media/js/select2.js"></script> <script>$(document).ready(function() { $(".select2").select2(); });</script>';
$ojnTemplate->setJS($js);
?>
