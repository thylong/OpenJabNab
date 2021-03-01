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
if(isset($_GET['rvoice'])) {
	$_SESSION['subtab'] = "callurl_voice";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/callurl/voice?action=del&command=".($_GET['rvoice'])."&".$ojnAPI->getToken()));
	$reload = true;
}
if(isset($_GET['rear'])) {
	$_SESSION['subtab'] = "callurl_ears";
	list($ear, $pos) = preg_split("|(\d+)|", $_GET['rear'], -1,  PREG_SPLIT_DELIM_CAPTURE |  PREG_SPLIT_NO_EMPTY  );
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
if(isset($_GET['rtag'])) {
	$_SESSION['subtab'] = "callurl_rfid";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/callurl/rfid?action=del&tag=".$_GET['rtag']."&".$ojnAPI->getToken()));
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

<ul class="nav nav-tabs">
	<li class="nav-item">
    <a class="nav-link <?php echo $_SESSION['subtab'] == 'callurl_url' ? ' active' : '' ?>" href="#url" data-toggle="tab"><?php echo __tr('URL List') ?></a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?php echo $_SESSION['subtab'] == 'callurl_schedule' ? ' active' : '' ?>" href="#schedule" data-toggle="tab"><?php echo __tr('Schedules') ?></a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?php echo $_SESSION['subtab'] == 'callurl_rfid' ? ' active' : '' ?>" href="#rfid" data-toggle="tab"><?php echo __tr('RFID') ?></a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?php echo $_SESSION['subtab'] == 'callurl_ears' ? ' active' : '' ?>" href="#ears" data-toggle="tab"><?php echo __tr('Ears') ?></a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?php echo $_SESSION['subtab'] == 'callurl_voice' ? ' active' : '' ?>" href="#voice" data-toggle="tab"><?php echo __tr('Voice command') ?></a>
  </li>
</ul>

<div class="tab-content pt-2">
	<div class="tab-pane<?php echo $_SESSION['subtab'] == 'callurl_url' ? ' active' : '' ?>" id="url">
		<div class="alert alert-success">
			<?php echo __tr("You can create dynamic url, with the following variables") ?>.
			<?php echo __tr("Those variables will be replaced by values (or empty when not avaliable)") ?>.
			<ul class="m-2">
				<li><?php echo __tr("BUNNYMAC will be replaced with MAC address (on all url)") ?></li>
				<li><?php echo __tr("CLICTYPE will be replaced with 1 for a single click, 2 for a double click") ?></li>
				<li><?php echo __tr("LEFTPOS will be replaced with left ear position (only when left ear is moved)") ?></li>
				<li><?php echo __tr("RIGHTPOS will be replaced with right ear position (only when right ear is moved)") ?></li>
				<li><?php echo __tr("ZTAMPSN will be replaced with ztamp serial number (only when using a ztamp)") ?></li>
				<li><?php echo __tr("VOICECMD will be replaced with the voice command you ask (only when using voice command, and only available to VIP users)") ?></li>
			</ul>
		</div>
		<form method="post">
      <div class="form-group row">
        <label class="col-sm-2 col-form-label" for="addurl"><?php echo __tr("Add an url") ?></label>
        <div class="col-sm-6 input-group">
          <input type="text" name="addurl" class="form-control"/>
        </div>
      </div>
      <div class="form-group row">
        <label class="col-sm-2 col-form-label" for="addname"><?php echo __tr("Name") ?> (<?php echo __tr('Optional') ?>)</label>
        <div class="col-sm-2 input-group">
          <input type="text" name="addname" class="form-control"/>
        </div>
      </div>
      <div class="form-group row">
        <div class="col-sm-1 offset-sm-2">
          <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
        </div>
      </div>
    </form>

		<?php if(!empty($pList)): ?>
		<hr />
		<h5><?php echo __tr('URL List') ?></h5>
		<table class="table table-bordered table-striped span11">
			<tr>
				<th class="col-sm-2"><?php echo __tr('Name') ?></th>
				<th><?php echo __tr('URL') ?></th>
				<th class="col-sm-3"><?php echo __tr('Actions') ?></th>
			</tr>
			<?php foreach($pList as $k => $item): ?>
			<tr>
				<td><?php echo urldecode($k) ?></td>
				<td><?php echo urldecode($item) ?></td>
				<td>
					<a class="btn btn-danger btn-sm" href="bunny_plugin.php?p=callurl&rp=<?php echo $k ?>"><i class="icon-trash icon-large"></i> <?php echo __tr("Remove") ?></a>
					<?php if($defaults['Click'] != $k): ?>
					<a class="btn btn-primary btn-sm" href="bunny_plugin.php?p=callurl&d=<?php echo $k ?>"><?php echo __tr("Set as default") ?></a>
					<?php else: ?>
					<span class="btn btn-sm btn-secondary"><?php echo __tr("Default url") ?></span>
					<?php endif; ?>
				</td>
			</tr>
			<?php endforeach; ?>
		</table>
		<?php endif; ?>
	</div>

	<div class="tab-pane<?php echo $_SESSION['subtab'] == 'callurl_schedule' ? ' active' : '' ?>" id="schedule">
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
        <label class="col-sm-3 col-form-label" for="scheduleN"><?php echo __tr("Url") ?></label>
        <div class="col-sm-2 input-group">
          <select name="scheduleN" class="form-control">
            <option value=""></option>
            <?php if(!empty($pList))
						foreach($pList as $key => $item): ?>
							<option value="<?php echo $key ?>"><?php echo $key; ?></option>
						<?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-group row">
        <div class="col-sm-1 offset-sm-3">
          <button class="btn btn-primary" type="submit"><?php echo __tr("Add") ?></button>
        </div>
      </div>
    </form>

    <?php if(!empty($wList)): ?>
    <h5><?php echo __tr('Schedules') ?></h5>
    <table class="table table-bordered table-striped">
      <tr>
        <th class="col-sm-2"><?php echo __tr('Day') ?></th>
        <th class="col-sm-1"><?php echo __tr('Time') ?></th>
        <th><?php echo __tr('Name') ?></th>
        <th class="col-sm-1"><?php echo __tr('Actions') ?></th>
      </tr>
      <?php foreach($wList as $when => $name):
          list($day, $time) = preg_split("/\|/", $when);
      ?>
      <tr>
        <td><?php echo $days[$day] ?></td>
        <td><?php echo $time ?></td>
        <td><?php echo preg_replace('/OJN_/', '', $name) ?></td>
        <td><a href="bunny_plugin.php?p=callurl&rwt=<?php echo $time ?>&rwd=<?php echo $day ?>" class="btn btn-sm btn-danger"><?php echo __tr('Remove') ?></a></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>

	<div class="tab-pane<?php echo $_SESSION['subtab'] == 'callurl_rfid' ? ' active' : '' ?>" id="rfid">
		<form method="post">
			<div class="form-group row">
				<label class="col-sm-1 col-form-label" for="allrfidurl"><?php echo __tr("Launch") ?></label>
				<div class="col-sm-2 input-group">
					<select name="allrfidurl" class="form-control">
						<option value=""></option>
						<?php foreach($pList as $k => $item): ?>
							<option <?php if($k == $defaults['RFID']): ?> selected="selected"<?php endif; ?>value="<?php echo urldecode($k) ?>"><?php echo urldecode($k); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="col-sm-2 input-group">
					<span class="col-form-label"><?php echo __tr("for all RFID") ?></span>
				</div>
				<div class="col-sm-2">
					<button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
				</div>
			</div>
		</form>
		<form method="post">
			<div class="form-group row">
				<label class="col-sm-1 col-form-label" for="aurl"><?php echo __tr("Launch") ?></label>
				<div class="col-sm-2 input-group">
					<select name="aurl" class="form-control">
						<option value=""></option>
						<?php foreach($pList as $k => $item): ?>
							<option value="<?php echo urldecode($k) ?>"><?php echo urldecode($k); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<label class="col-sm-2 col-form-label" for="atag"><?php echo __tr("on Ztamp") ?></label>
				<div class="col-sm-4 input-group">
					<select name="atag"  class="form-control">
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
				<th><?php echo __tr('Url') ?></th>
				<th class="col-sm-4"><?php echo __tr('Ztamp') ?></th>
				<th class="col-sm-1"><?php echo __tr('Actions') ?></th>
			</tr>
			<?php foreach($Assoc as $k=>$v): ?>
			<tr>
				<td><?php echo preg_replace('/OJN_/', '', $v); ?></td>
				<td><?php echo $Ztamps[$k] . " - " . $k; ?></td>
				<td><a href="bunny_plugin.php?p=callurl&rtag=<?php echo $k ?>" class="btn btn-danger btn-sm"><i class="icon-trash icon-large"></i> <?php echo __tr('Remove') ?></a></td>
			</tr>
			<?php endforeach; ?>
		</table>
		<?php endif; ?>
	</div>

	<div class="tab-pane<?php echo $_SESSION['subtab'] == 'callurl_ears' ? ' active' : '' ?>" id="ears">
		<form method="post">
			<div class="form-group row">
				<label class="col-sm-4 col-form-label" for="alllefturl"><?php echo __tr("Call url for all left ear positions") ?></label>
				<div class="col-sm-2 input-group">
					<select name="alllefturl" class="form-control">
						<option value=""></option>
						<?php foreach($pList as $k => $item): ?>
						<option <?php if($k == $defaults['LeftEar']): ?> selected="selected"<?php endif; ?>value="<?php echo urldecode($k) ?>"><?php echo urldecode($k); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-4 col-form-label" for="allrighturl"><?php echo __tr("Call url for all right ear positions") ?></label>
				<div class="col-sm-2 input-group">
					<select name="allrighturl" class="form-control">
						<option value=""></option>
						<?php foreach($pList as $k => $item): ?>
						<option <?php if($k == $defaults['LeftEar']): ?> selected="selected"<?php endif; ?>value="<?php echo urldecode($k) ?>"><?php echo urldecode($k); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<div class="form-group row">
				<div class="col-sm-2 offset-sm-4">
					<button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
				</div>
			</div>
		</form>
		<hr />
		<form method="post">
			<div class="form-group row">
				<label class="col-sm-1 col-form-label" for="aurl"><?php echo __tr("Launch") ?></label>
				<div class="col-sm-2 input-group">
					<select name="aurl" class="form-control">
						<option value=""></option>
						<?php foreach($pList as $k => $item): ?>
							<option value="<?php echo urldecode($k) ?>"><?php echo urldecode($k); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<label class="col-sm-2 col-form-label" for="aear"><?php echo __tr("on ear position") ?></label>
				<div class="col-sm-3 input-group">
					<select name="aear" class="form-control">
						<option value=""></option>
						<?php foreach($Ears as $k=>$v): ?>
						<option value="<?php echo $k; ?>"><?php echo $v; ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="col-sm-2">
					<button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
				</div>
			</div>
		</form>

		<?php if(array_unique(array_values($Assocear)) != array("")): ?>
		<h5><?php echo __tr('Ear positions associations') ?></h5>
		<table class="table table-bordered table-stripe">
			<tr>
				<th class="col-sm-2"><?php echo __tr('Url') ?></th>
				<th><?php echo __tr('Ear position') ?></th>
				<th class="col-sm-1"><?php echo __tr('Actions') ?></th>
			</tr>
			<?php foreach($Assocear as $k=>$v):
					if(!strlen(trim($v)))
						continue;
			?>
			<tr>
				<td><?php echo preg_replace('/OJN_/', '', $v); ?></td>
				<td><?php echo $Ears[$k]; ?></td>
				<td><a href="bunny_plugin.php?p=callurl&rear=<?php echo $k ?>" class="btn btn-danger btn-sm"><i class="icon-trash icon-large"></i> <?php echo __tr('Remove') ?></a></td>
			</tr>
			<?php endforeach; ?>
		</table>
		<?php endif; ?>
	</div>

	<div class="tab-pane<?php echo $_SESSION['subtab'] == 'callurl_voice' ? ' active' : '' ?>" id="voice">
		<div class="alert alert-warning"><?php echo __tr("Only available to VIP and premium users"); ?></div>

		<form method="post">
			<div class="form-group row">
				<label class="col-sm-1 col-form-label" for="allvoiceurl"><?php echo __tr("Launch") ?></label>
				<div class="col-sm-3 input-group">
					<select name="allvoiceurl" class="form-control">
						<option value=""></option>
						<?php  if(!empty($pList))
						foreach($pList as $k => $item): ?>
							<option <?php if($k == $defaults['Voice']): ?> selected="selected"<?php endif; ?>value="<?php echo urldecode($k) ?>"><?php echo urldecode($k); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="col-sm-2 input-group">
					<span class="col-form-label"><?php echo __tr("for all voice commands") ?></span>
				</div>
				<div class="col-sm-2">
					<button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
				</div>
			</div>
		</form>
		<form method="post">
			<div class="form-group row">
				<label class="col-sm-1 col-form-label" for="aurl"><?php echo __tr("Launch") ?></label>
				<div class="col-sm-3 input-group">
					<select name="aurl" class="form-control">
						<option value=""></option>
						<?php foreach($pList as $k => $item): ?>
							<option value="<?php echo urldecode($k) ?>"><?php echo urldecode($k); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<label class="col-sm-2 col-form-label" for="avoice"><?php echo __tr("on voice command"); ?></label>
				<div class="col-sm-2 input-group">
					<input type="text" name="avoice" class="form-control">
				</div>
				<div class="col-sm-2">
					<button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
				</div>
			</div>
		</form>

		<?php if(!empty($Assocvoice)): ?>
		<h5><?php echo __tr('Voice commands associations') ?></h5>
		<table class="table table-bordered table-stripe">
			<tr>
				<th><?php echo __tr('Url') ?></th>
				<th class="col-sm-2"><?php echo __tr('Command') ?></th>
				<th class="col-sm-1"><?php echo __tr('Actions') ?></th>
			</tr>
			<?php foreach($Assocvoice as $k=>$v): ?>
			<tr>
				<td><?php echo $v; ?></td>
				<td><?php echo $k; ?></td>
				<td><a href="bunny_plugin.php?p=callurl&rvoice=<?php echo $k ?>" class="btn btn-danger btn-sm"><i class="icon-trash icon-large"></i> <?php echo __tr('Remove') ?></a></td>
			</tr>
			<?php endforeach; ?>
		</table>
		<?php endif; ?>
	</div>
</div>
