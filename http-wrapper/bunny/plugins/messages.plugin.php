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
<ul class="nav nav-tabs">
<li class="nav-item">
    <a class="nav-link <?php echo $_SESSION['subtab'] == 'messages_status' ? ' active' : '' ?>" href="#status" data-toggle="tab"><?php echo __tr('Status') ?></a>
  </li>
	<li class="nav-item">
    <a class="nav-link <?php echo $_SESSION['subtab'] == 'messages_config' ? ' active' : '' ?>" href="#config" data-toggle="tab"><?php echo __tr('Setup') ?></a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?php echo $_SESSION['subtab'] == 'messages_schedule' ? ' active' : '' ?>" href="#schedule" data-toggle="tab"><?php echo __tr('Schedules') ?></a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?php echo $_SESSION['subtab'] == 'messages_rfid' ? ' active' : '' ?>" href="#rfid" data-toggle="tab"><?php echo __tr('RFID') ?></a>
  </li>
</ul>

<div class="tab-content pt-4">
	<div class="tab-pane<?php echo $_SESSION['subtab'] == 'messages_status' ? ' active' : '' ?>" id="status">
		<?php if($count):?>
		<h5><?php echo __tr('Previous messages read by the bunny') ?></h5>
		<table class="table table-bordered table-striped span11">
			<tr>
				<th><?php echo __Tr('Date') ?></th>
				<th><?php echo __Tr('Plugin') ?></th>
				<th><?php echo __tr('Actions') ?></th>
			</tr>
			<?php
				foreach($messages as $id => $message):
					$parts = explode("|", $message);
					$date = date('d/m/Y H:i:s', $parts[0]);
					$plugin = $parts[1];
					$files = array();
					for($i=2; $i<count($parts);$i++)
						$files[] = preg_replace("|^broadcast/|", "http://openjabnab.fr/", $parts[$i]);
			?>
			<tr>
				<td><?php echo $date ?></td>
				<td><?php echo $plugins[$plugin] ?></td>
				<td>
					<?php 
					// Create a playlist from multiple audio files
					$audioFiles = explode('|', implode('|', $files));
					?>
					<div class="audio-playlist">
						<?php foreach($audioFiles as $index => $audioFile): ?>
						<audio controls style="width: 200px; display: block; margin-bottom: 5px;">
							<source src="<?php echo htmlspecialchars(trim($audioFile)); ?>" type="audio/mpeg">
							<?php echo __tr('Your browser does not support audio playback.'); ?>
						</audio>
						<?php endforeach; ?>
					</div>
					<a class="btn btn-sm btn-danger" href="bunny_plugin.php?p=messages&rmid=<?php echo $id ?>"><?php echo __tr('Remove') ?></a>
				</td>
			</tr>
			<?php endforeach; ?>
		</table>
		<?php else: ?>
		<div class="alert"><?php echo __tr("No previous message said by the bunny") ?></div>
		<?php endif; ?>
	</div>

	<div class="tab-pane<?php echo $_SESSION['subtab'] == 'messages_config' ? ' active' : '' ?>" id="config">
		<form method="post">
			<div class="form-group row">
        <label class="col-sm-2 col-form-label" for="defaultAction"><?php echo __tr('Default action') ?></label>
        <div class="col-sm-2 input-group">
					<select name="defaultAction" class="form-control">
						<?php foreach($actions as $key => $value): ?>
						<option value="<?php echo $key ?>"><?php echo $value ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="col-sm-1 input-group">
					<button class="btn btn-sm btn-primary" type="submit"><?php echo __tr("Save") ?></button>
				</div>
			</div>
		</form>
		<hr />
		<?php $plugins = array_merge(array('default' => __tr('Default')), $plugins); ?>
		<form method="post">
			<div class="alert"><?php echo __tr('Enter the number of hours a message will be kept for each plugin') ?></div>
			<?php foreach($plugins as $id => $name): ?>
			<div class="form-group row">
				<label class="col-sm-3 col-form-label" for="keep[<?php echo $id; ?>"><?php echo $name; ?></label>
				<div class="col-sm-2 input-group">
					<?php $val = $ojnAPI->getApiValue("bunny/".$_SESSION['bunny']."/messages/option?action=keep&plugin=".$id."&".$ojnAPI->getToken()); ?>
					<input type="text" name="keep[<?php echo $id ?>]" class="input-xlarge span1" value="<?php echo $val;?>" />
				</div>
				<div class="col-sm-1 input-group">
					<span class="col-form-label"><?php echo __tr('hour(s)') ?></span>
				</div>
			</div>
			<?php endforeach; ?>
			<div class="form-group row">
				<div class="col-sm-1 offset-sm-3 input-group">
					<button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
				</div>
			</div>
		</form>
	</div>

  <div class="tab-pane<?php echo $_SESSION['subtab'] == 'messages_schedule' ? ' active' : '' ?>" id="schedule">
    <form method="post">
      <div class="form-group row">
        <label class="col-sm-3 col-form-label" for="scheduleT"><?php echo __tr("Add a schedule at (hh:mm)") ?></label>
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
        <label class="col-sm-3 col-form-label" for="scheduleA"><?php echo __tr("Action") ?></label>
        <div class="col-sm-2 input-group">
					<select name="scheduleA" class="form-control">
						<?php foreach($actions as $key => $value): ?>
						<option value="<?php echo $key ?>"><?php echo $value ?></option>
						<?php endforeach; ?>
					</select>
        </div>
      </div>
      <div class="form-group row">
        <div class="col-sm-1 offset-sm-3">
          <button class="btn btn-primary" type="submit"><?php echo __tr("Add the schedule") ?></button>
        </div>
      </div>
    </form>

		<?php if(!empty($wList)): ?>
    <h5><?php echo __tr('Schedules') ?></h5>
    <table class="table table-bordered table-striped">
      <tr>
        <th class="col-sm-2"><?php echo __tr('Day') ?></th>
        <th class="col-sm-1"><?php echo __tr('Time') ?></th>
        <th><?php echo __tr('Option') ?></th>
        <th class="col-sm-1"><?php echo __tr('Actions') ?></th>
      </tr>
      <?php foreach($wList as $when => $option):
          list($day, $time) = preg_split("/\|/", $when);
      ?>
      <tr>
        <td><?php echo $days[$day] ?></td>
        <td><?php echo $time ?></td>
        <td><?php echo $actions[$option] ?></td>
				<td><a class="btn btn-sm btn-danger" href="bunny_plugin.php?p=messages&rd=<?php echo $day ?>&rt=<?php echo $time ?>"><i class="icon-trash icon-large"></i> <?php echo __tr('Remove') ?></a></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>

	<div class="tab-pane<?php echo $_SESSION['subtab'] == 'messages_rfid' ? ' active' : '' ?>" id="rfid">
	<form method="post">
      <div class="form-group row">
        <label class="col-sm-1 col-form-label" for="aA"><?php echo __tr("Execute") ?></label>
        <div class="col-sm-2 input-group">
					<select name="aA" class="form-control">
						<?php foreach($actions as $key => $value): ?>
						<option value="<?php echo $key ?>"><?php echo $value ?></option>
						<?php endforeach; ?>
					</select>
        </div>
        <label class="col-sm-2 col-form-label" for="atag"><?php echo __tr("on Ztamp") ?></label>
        <div class="col-sm-4 input-group">
          <select name="atag" class="form-control">
            <option value=""></option>
            <?php foreach($Ztamps as $k=>$v): ?>
            <option value="<?php echo $k; ?>"><?php echo $v; ?> (<?php echo $k; ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-sm-2">
          <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
        </div>
      </div>
    </form>

    <?php if(!empty($Assoc)): ?>
    <h5><?php echo __tr('Associations') ?></h5>
    <table class="table table-bordered table-stripe">
      <tr>
        <th class="col-sm-1"><?php echo __tr('Action') ?></th>
        <th class="col-sm-4"><?php echo __tr('Ztamp') ?></th>
        <th class="col-sm-1"><?php echo __tr('Actions') ?></th>
      </tr>
      <?php foreach($Assoc as $k=>$v): ?>
      <tr>
        <td><?php echo $actions[$v] ?></td>
        <td><?php echo $Ztamps[$k] . " - " . $k; ?></td>
        <td><a href="bunny_plugin.php?p=messages&rtag=<?php echo $k ?>" class="btn btn-danger btn-sm"><i class="icon-trash icon-large"></i> <?php echo __tr('Remove') ?></a></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>
</div>
