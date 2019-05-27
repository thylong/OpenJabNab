<?php
$reload = false;
$Ztamps = $ojnAPI->GetListofZtamps(false);

$xml = $ojnAPI->getApiRaw('plugins/getPlugins?lng='.$Infos['language']);
$plugins = simplexml_load_string($xml);

$list = array();

foreach($plugins->plugins->plugin as $plugin)
{
	$p = array();
	$p['name'] = (string)$plugin;
	$attrs = (array)$plugin->attributes();
	foreach($attrs['@attributes'] as $key => $value)
	{
		$p[$key] = trim($value);
	}

	if($p['message'] && !($p['required'] || $p['system']))
		$list[$p['id']] = $p['name'];
}
asort($list);
$plugins = $list;

$actions = array(
	'one'   => __tr('Say one message'),
	'all'   => __tr('Say all messages'),
	'count' => __tr('Count messages'),
	'clear' => __tr('Clear messages'),
);
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
if(isset($_POST['scheduleT']) && isset($_POST['scheduleA']) && isset($_POST['scheduleD']))
{
	$_SESSION['subtab'] = "messages_schedule";
	if($_POST['scheduleD'] >= 0)
	{
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/messages/schedule?action=add&option=".$_POST['scheduleA']."&time=".$_POST['scheduleT']."&day=".$_POST['scheduleD']."&".$ojnAPI->getToken()));
	}
	else
	{
		if($_POST['scheduleD'] == -1)
		{
			$id = 0;
			for($d = 1; $d<=5; $d++)
			{
				Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/messages/schedule?action=add&option=".$_POST['scheduleA']."&time=".$_POST['scheduleT']."&day=".$d."&".$ojnAPI->getToken()));
			}
		}
		if($_POST['scheduleD'] == -2)
		{
			$id = 0;
			for($d = 6; $d<=7; $d++)
			{
				Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/messages/schedule?action=add&option=".$_POST['scheduleA']."&time=".$_POST['scheduleT']."&day=".$d."&".$ojnAPI->getToken()));
			}
		}
	}
	$reload = true;
}
else if(isset($_POST['keep']) && count($_POST['keep']) )
{
	$_SESSION['subtab'] = "messages_config";
	$id = 0;
	foreach($_POST['keep'] as $key => $value)
	{
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/messages/option?action=keep&set=".$value."&plugin=".$key."&".$ojnAPI->getToken()), $id);
	}
	$reload = true;
}
else if(isset($_GET['rt']) && isset($_GET['rd']))
{
	$_SESSION['subtab'] = "messages_schedule";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/messages/schedule?action=del&day=".$_GET['rd']."&time=".$_GET['rt']."&".$ojnAPI->getToken()));
	$reload = true;
}
if(isset($_GET['rmid'])) {
	$_SESSION['subtab'] = "messages_status";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/messages/message?action=del&id=".$_GET['rmid']."&".$ojnAPI->getToken()));
	$reload = true;
}
if(isset($_GET['rtag'])) {
	$_SESSION['subtab'] = "messages_rfid";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/messages/rfid?action=del&tag=".$_GET['rtag']."&".$ojnAPI->getToken()));
	$reload = true;
}
if(isset($_POST['atag']) && isset($_POST['aA'])) {
	$_SESSION['subtab'] = "messages_rfid";
	if($_POST['atag'] == "")
	{
		Message::AddError(__tr('You must choose a Ztamp'));
	}
	else
	{
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/messages/rfid?action=add&tag=".$_POST['atag']."&option=".$_POST['aA']."&".$ojnAPI->getToken()));
	}
	$reload = true;
}

if(!isset($_SESSION['subtab']) || !preg_match("|^messages_|", $_SESSION['subtab'])) {
	$_SESSION['subtab'] = "messages_status";
}
if($reload)
{
	header("Location: bunny_plugin.php?p=messages");
	exit();
}
$default = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/messages/option?action=get&".$ojnAPI->getToken());
$cron = (string)$ojnAPI->getApiValue("bunny/".$_SESSION['bunny']."/messages/option?action=cron&".$ojnAPI->getToken());
$wList = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/messages/schedule?action=list&".$ojnAPI->getToken());
$Assoc = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/messages/rfid?action=list&".$ojnAPI->getToken());

$count = $ojnAPI->getApiValue("bunny/".$_SESSION['bunny']."/messages/message?action=count&".$ojnAPI->getToken());
$messages = $ojnAPI->getApiList("bunny/".$_SESSION['bunny']."/messages/message?action=list&".$ojnAPI->getToken());

?>
<style>
.form-horizontal .controls_setup {
    margin-left: 310px;
}
.form-horizontal .control_setup-label {
    width: 300px;
}
</style>

		<div class="tabbable">
		<ul class="nav nav-tabs">
		  <li<?php echo $_SESSION['subtab'] == 'messages_status' ? ' class="active"' : '' ?>><a href="#status" data-toggle="tab"><?php echo __tr('Status') ?></a></li>
		  <li<?php echo $_SESSION['subtab'] == 'messages_config' ? ' class="active"' : '' ?>><a href="#config" data-toggle="tab"><?php echo __tr('Setup') ?></a></li>
		  <li<?php echo $_SESSION['subtab'] == 'messages_schedule' ? ' class="active"' : '' ?>><a href="#schedule" data-toggle="tab"><?php echo __tr('Schedules') ?></a></li>
		  <li<?php echo $_SESSION['subtab'] == 'messages_rfid' ? ' class="active"' : '' ?>><a href="#rfid" data-toggle="tab"><?php echo __tr('RFID') ?></a></li>
		</ul>
		<br />
		
			<div class="tab-content">

				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'messages_status' ? ' active' : '' ?>" id="status">

<?php
if($count){
?>
<center>
<table class="table table-bordered table-striped span11">
	<tr>
		<th colspan="4"><?php echo __tr('Previous messages read by the bunny') ?></th>
	</tr>
	<tr>
		<th><?php echo __Tr('Date') ?></th>
		<th><?php echo __Tr('Plugin') ?></th>
		<th><?php echo __tr('Actions') ?></th>
	</tr>
<?php
	$i = 0;
	foreach($messages as $id => $message)
	{
		$parts = explode("|", $message);
		$date = date('d/m/Y H:i:s', $parts[0]);
		$plugin = $parts[1];
		$files = array();
		for($i=2; $i<count($parts);$i++)
		{
			$files[] = preg_replace("|^broadcast/|", "http://openjabnab.fr/", $parts[$i]);
		}
?>
	<tr>
		<td><?php echo $date ?></td>
		<td><?php echo $plugins[$plugin] ?></td>
		<td>
			<object type="application/x-shockwave-flash" data="player_mp3_multi.swf" width="200" height="20">
			     <param name="movie" value="player_mp3_multi.swf" />
			     <param name="FlashVars" value="loadingcolor=0074CC&slidercolor1=0088CC&slidercolor2=0055CC&sliderovercolor=0074CC&buttonovercolor=0074CC&showlist=0&mp3=<?php echo implode('|', $files) ?>" />
			</object>
			<a class="btn btn-danger" href="bunny_plugin.php?p=messages&rmid=<?php echo $id ?>"><?php echo __tr('Remove') ?></a>
		</td>
	</tr>
<?php  } ?>
</table>
<?php } else { ?>
<p><?php echo __tr("No previous message said by the bunny") ?></p>
<?php } ?>

				</div>



				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'messages_config' ? ' active' : '' ?>" id="config">
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control_setup-label control-label"><?php echo __tr('Default action') ?></label>
            <div class="controls_setup controls">
		<select name="defaultAction">
<?php foreach($actions as $key => $value): ?>
			<option value="<?php echo $key ?>"><?php echo $value ?></option>
<?php endforeach; ?> 
		</select>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <a href="bunny_plugin.php?p=messages" class="btn"><?php echo __tr("Cancel") ?></a>
          </div>
</form>
<form method="post" class="form-horizontal">
          <div class="control-group">
		<p><?php echo __tr('Enter the number of hours a message will be kept for each plugin') ?></p>
	  </div>
          <div class="control-group">
            <label for="input01" class="control_setup-label control-label"><?php echo __tr('Default') ?></label>
            <div class="controls_setup controls">
		<div class="input-append"><input type="text" name="keep[default]" class="input-xlarge span1" value="<?php echo $ojnAPI->getApiValue("bunny/".$_SESSION['bunny']."/messages/option?action=keep&".$ojnAPI->getToken()) ?>"/><span class="add-on"><?php echo __tr('hour(s)') ?></span></div>
            </div>
          </div>
<?php foreach($plugins as $id => $name): ?>
          <div class="control-group">
            <label for="input01" class="control-label control_setup-label"><?php echo $name ?></label>
            <div class="controls controls_setup">
		<div class="input-append"><input type="text" name="keep[<?php echo $id ?>]" class="input-xlarge span1" value="<?php echo $ojnAPI->getApiValue("bunny/".$_SESSION['bunny']."/messages/option?action=keep&plugin=".$id."&".$ojnAPI->getToken()) ?>"/><span class="add-on"><?php echo __tr('hour(s)') ?></span></div>
            </div>
          </div>
<?php endforeach; ?>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <a href="bunny_plugin.php?p=messages" class="btn"><?php echo __tr("Cancel") ?></a>
          </div>
</form>


				</div>

				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'messages_schedule' ? ' active' : '' ?>" id="schedule">
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Add a schedule at (hh:mm)") ?></label>
            <div class="controls">
		<input type="text" name="scheduleT" class="input-xlarge span6"/>
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
            <label for="input01" class="control-label"><?php echo __tr('Action') ?></label>
            <div class="controls">
		<select name="scheduleA">
<?php foreach($actions as $key => $value): ?>
			<option value="<?php echo $key ?>"><?php echo $value ?></option>
<?php endforeach; ?> 
		</select>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Add the schedule") ?></button>
            <a href="bunny_plugin.php?p=messages" class="btn"><?php echo __tr("Cancel") ?></a>
          </div>
</form>

<?php
if(count($wList)){
?>
<hr />
<center>
<table class="table table-bordered table-striped span11">
	<tr>
		<th colspan="4"><?php echo __tr('Schedules') ?></th>
	</tr>
	<tr>
		<th><?php echo __Tr('Day') ?></th>
		<th><?php echo __Tr('Time') ?></th>
		<th><?php echo __tr('Option') ?></th>
		<th><?php echo __tr('Actions') ?></th>
	</tr>
<?php
	$i = 0;
	foreach($wList as $when => $option)
	{
		list($day, $time) = explode("|", $when);
?>
	<tr>
		<td><?php echo $days[$day] ?></td>
		<td><?php echo $time ?></td>
		<td><?php echo $actions[$option] ?></td>
		<td width="15%"><a class="btn btn-danger" href="bunny_plugin.php?p=messages&rd=<?php echo $day ?>&rt=<?php echo $time ?>"><?php echo __tr('Remove') ?></a></td>
	</tr>
<?php  } ?>
</table>
<?php } ?>

				</div>

				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'messages_rfid' ? ' active' : '' ?>" id="rfid">

<form method="post" class="form-horizontal">

          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr('Execute') ?></label>
            <div class="controls">
		<select name="aA">
<?php foreach($actions as $key => $value): ?>
			<option value="<?php echo $key ?>"><?php echo $value ?></option>
<?php endforeach; ?> 
		</select>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Ztamp") ?></label>
            <div class="controls">
<select name="atag" class="span4"> 
    <option value=""></option>
	<?php foreach($Ztamps as $k=>$v): ?>
	<option value="<?php echo $k; ?>"><?php echo $v; ?> (<?php echo $k; ?>)</option>
	<?php endforeach; ?>
</select>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <a href="bunny_plugin.php?p=messages" class="btn"><?php echo __tr("Cancel") ?></a>
          </div>


</form>

<?php if(count($Assoc)): ?>
<table class="table table-bordered table-striped span10">
<tr><th colspan="3"><?php echo __tr('Associations') ?></th></tr>
<tr>
	<th><?php echo __tr('Ztamp') ?></th>
	<th><?php echo __tr('Option') ?></th>
	<th><?php echo __tr('Actions') ?></th>
</tr>
<?php foreach($Assoc as $k=>$v): ?>
<?php //list($when, $what, $zone) = preg_split("/;/", $v); ?>
<tr>
	<td><?php echo $Ztamps[$k] . " - " . $k; ?></td>
		<td><?php echo $actions[$v] ?></td>
	<td><a href="bunny_plugin.php?p=messages&rtag=<?php echo $k ?>" class="btn btn-danger btn-small"><i class="icon-trash icon-large"></i> <?php echo __tr('Remove') ?></a></td>
</tr>
<?php endforeach; ?>

</table>
<?php endif; ?>
</form>



				</div>

			</div>
		</div>
