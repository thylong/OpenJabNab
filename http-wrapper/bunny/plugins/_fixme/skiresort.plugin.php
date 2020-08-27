<?php
$reload = false;
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

$options = array(
1 => __tr('Snow at top'),
2 => __tr('Top altitude'),
4 => __tr('Snow at bottom'),
8 => __tr('Bottom altitude'),
);
if(isset($_POST['scheduleT']) && isset($_POST['scheduleR']) && isset($_POST['scheduleD']))
{
	$_SESSION['subtab'] = "skiresort_schedule";
	$resort = $_POST['scheduleR'];
	$option = 0;
	foreach($_POST['scheduleO'] as $k => $v)
	{
		if($v == 1)
			$option |= $k;
	}
	if($option == 0)
		$option = 5;
	if($_POST['scheduleD'] >= 0)
	{
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/skiresort/schedule?action=add&resort=".$resort."&option=".$option."&time=".$_POST['scheduleT']."&day=".$_POST['scheduleD']."&".$ojnAPI->getToken()));
	}
	else
	{
		if($_POST['scheduleD'] == -1)
		{
			$id = 0;
			for($d = 1; $d<=5; $d++)
			{
				Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/skiresort/schedule?action=add&resort=".$resort."&option=".$option."&time=".$_POST['scheduleT']."&day=".$d."&".$ojnAPI->getToken()));
			}
		}
		if($_POST['scheduleD'] == -2)
		{
			$id = 0;
			for($d = 6; $d<=7; $d++)
			{
				Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/skiresort/schedule?action=add&resort=".$resort."&option=".$option."&time=".$_POST['scheduleT']."&day=".$d."&".$ojnAPI->getToken()));
			}
		}
	}
	$reload = true;
}
else if(isset($_POST['defaultTh']) && isset($_POST['defaultZ']) )
{
	$_SESSION['subtab'] = "skiresort_config";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/skiresort/zodiac?action=set&zodiac=".$_POST['defaultZ']."&themes=".implode(",", $_POST['defaultTh'])."&".$ojnAPI->getToken()));
	$reload = true;
}
else if(isset($_GET['rt']) && isset($_GET['rd']))
{
	$_SESSION['subtab'] = "skiresort_schedule";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/skiresort/schedule?action=del&day=".$_GET['rd']."&time=".$_GET['rt']."&".$ojnAPI->getToken()));
	$reload = true;
}
if(isset($_GET['rtag'])) {
	$_SESSION['subtab'] = "skiresort_rfid";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/skiresort/rfid?action=del&tag=".$_GET['rtag']."&".$ojnAPI->getToken()));
	$reload = true;
}
if(isset($_POST['atag']) && isset($_POST['aresort'])) {
	$_SESSION['subtab'] = "skiresort_rfid";
	if($_POST['atag'] == "")
	{
		Message::AddError(__tr('You must choose a Ztamp'));
	}
	else
	{
		$option = 0;
		foreach($_POST['aoption'] as $k => $v)
		{
			if($v == 1)
				$option |= $k;
		}
		if($option == 0)
			$option = 5;
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/skiresort/rfid?action=add&tag=".$_POST['atag']."&resort=".$_POST['aresort']."&option=".$option."&".$ojnAPI->getToken()));
	}
	$reload = true;
}

if(!isset($_SESSION['subtab']) || !preg_match("|^skiresort_|", $_SESSION['subtab'])) {
	$_SESSION['subtab'] = "skiresort_config";
}
if($reload)
{
	header("Location: bunny_plugin.php?p=skiresort");
	exit();
}
$resorts = array();
$mountains = $ojnAPI->getApiMapped("plugin/skiresort/mountain?action=list&".$ojnAPI->getToken());
foreach($mountains as $key => $name)
{
	$_resorts = $ojnAPI->getApiMapped("plugin/skiresort/mountain?action=resorts&mountain=".$key."&".$ojnAPI->getToken());
	$mountains[$key] = array('name' => $name, 'resorts' => $_resorts);
	$resorts = array_merge($resorts, $_resorts);
}

$defaultR = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/skiresort/resort?action=get&".$ojnAPI->getToken());
$rList = $ojnAPI->getApiList("bunny/".$_SESSION['bunny']."/skiresort/resort?action=list&".$ojnAPI->getToken());
$wList = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/skiresort/schedule?action=list&".$ojnAPI->getToken());
$Assoc = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/skiresort/rfid?action=list&".$ojnAPI->getToken());

$drawDef = false;
if(!$drawDef && $_SESSION['subtab'] == 'skiresort_config') {
	$_SESSION['subtab'] = 'skiresort_schedule';
}
?>

		<div class="tabbable">
		<ul class="nav nav-tabs">
<?php if($drawDef): ?>
		  <li<?php echo $_SESSION['subtab'] == 'skiresort_config' ? ' class="active"' : '' ?>><a href="#config" data-toggle="tab"><?php echo __tr('Setup') ?></a></li>
<?php endif; ?>
		  <li<?php echo $_SESSION['subtab'] == 'skiresort_schedule' ? ' class="active"' : '' ?>><a href="#schedule" data-toggle="tab"><?php echo __tr('Schedules') ?></a></li>
		  <li<?php echo $_SESSION['subtab'] == 'skiresort_rfid' ? ' class="active"' : '' ?>><a href="#rfid" data-toggle="tab"><?php echo __tr('RFID') ?></a></li>
		</ul>
		<br />
		
			<div class="tab-content">
<?php if($drawDef): ?>
				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'skiresort_config' ? ' active' : '' ?>" id="config">
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Resort") ?></label>
            <div class="controls">
<select name="addresort" id="resorts" style="width: 250px">
	<option value=""></option>
	<?php 
	foreach($mountains as $key => $mountain) { ?> 
		<optgroup label="<?php echo utf8_decode($mountain['name']) ?>">
	<?php foreach($mountain['resorts'] as $id => $resort) { ?>
		<option value="<?php echo $id ?>"><?php echo utf8_decode($resort); ?></option>
	<?php } ?>
		</optgroup>
	<?php } ?>
</select>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Add resort") ?></button>
            <a href="bunny_plugin.php?p=skiresort" class="btn"><?php echo __tr("Cancel") ?></a>
          </div>
</form>
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Default options") ?></label>
            <div class="controls">
		<input type="checkbox" name="skidefault[1]" value="1"<?php echo $defaultO | 1 ? ' selected="selected"' : '' ?>><?php echo __tr("Snow condition at top of resort") ?><br />
		<?php echo __tr('Also say altitude') ?> :
		<input type="radio" name="skidefault[2]" value="1"<?php echo $defaultO | 2 ? ' checked="checked"' : '' ?>> <?php echo __tr("Yes") ?> &nbsp;
		<input type="radio" name="skidefault[2]" value="0"<?php echo $defaultO | 2 ? ' checked="checked"' : '' ?>> <?php echo __tr("No") ?> &nbsp;<br />
		<input type="checkbox" name="skidefault[4]" value="1"<?php echo $defaultO | 4 ? ' selected="selected"' : '' ?>><?php echo __tr("Snow condition at bottom of resort") ?><br />
		<?php echo __tr('Also say altitude') ?> :
		<input type="radio" name="skidefault[8]" value="1"<?php echo $defaultO | 2 ? ' checked="checked"' : '' ?>> <?php echo __tr("Yes") ?> &nbsp;
		<input type="radio" name="skidefault[8]" value="0"<?php echo $defaultO | 2 ? ' checked="checked"' : '' ?>> <?php echo __tr("No") ?> &nbsp;<br />
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <a href="bunny_plugin.php?p=skiresort" class="btn"><?php echo __tr("Cancel") ?></a>
          </div>
</form>

<?php
if(count($rList)){
?>
<hr />
<center>
<table class="table table-bordered table-striped span11">
	<tr>
		<th colspan="4"><?php echo __tr('Resorts') ?></th>
	</tr>
	<tr>
		<th><?php echo __Tr('Name') ?></th>
		<th><?php echo __tr('Actions') ?></th>
	</tr>
<?php
	$i = 0;
	foreach($rList as $resort)
	{
?>
	<tr>
		<td><?php echo $resorts[$resort] ?></td>
		<td width="15%"><a class="btn btn-danger" href="bunny_plugin.php?p=skiresort&rd=<?php echo $day ?>&rt=<?php echo $time ?>"><?php echo __tr('Remove') ?></a></td>
	</tr>
<?php  } ?>
</table>
<?php } ?>

				</div>
<?php endif; ?>

				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'skiresort_schedule' ? ' active' : '' ?>" id="schedule">
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
            <label for="input01" class="control-label"><?php echo __tr("Resort") ?></label>
            <div class="controls">
<select name="scheduleR" id="resorts" style="width: 250px">
	<option value=""></option>
	<?php 
	foreach($mountains as $key => $mountain) { ?> 
		<optgroup label="<?php echo utf8_decode($mountain['name']) ?>">
	<?php foreach($mountain['resorts'] as $id => $resort) { ?>
		<option value="<?php echo $id ?>"><?php echo utf8_decode($resort); ?></option>
	<?php } ?>
		</optgroup>
	<?php } ?>
</select>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Default options") ?></label>
            <div class="controls">
		<input type="checkbox" name="scheduleO[1]" value="1"><?php echo __tr("Snow condition at top of resort") ?><br />
		<?php echo __tr('Also say altitude') ?> :
		<input type="radio" name="scheduleO[2]" value="1"> <?php echo __tr("Yes") ?> &nbsp;
		<input type="radio" name="scheduleO[2]" value="0"> <?php echo __tr("No") ?> &nbsp;<br />
		<input type="checkbox" name="scheduleO[4]" value="1"><?php echo __tr("Snow condition at bottom of resort") ?><br />
		<?php echo __tr('Also say altitude') ?> :
		<input type="radio" name="scheduleO[8]" value="1"> <?php echo __tr("Yes") ?> &nbsp;
		<input type="radio" name="scheduleO[8]" value="0"> <?php echo __tr("No") ?> &nbsp;<br />
            </div>
          </div>

          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Add the schedule") ?></button>
            <a href="bunny_plugin.php?p=skiresort" class="btn"><?php echo __tr("Cancel") ?></a>
          </div>
</form>

<?php
if(count($wList)){
?>
<hr />
<center>
<table class="table table-bordered table-striped span11">
	<tr>
		<th colspan="5"><?php echo __tr('Schedules') ?></th>
	</tr>
	<tr>
		<th><?php echo __Tr('Day') ?></th>
		<th><?php echo __Tr('Time') ?></th>
		<th><?php echo __tr('Resort') ?></th>
		<th><?php echo __tr('Options') ?></th>
		<th><?php echo __tr('Actions') ?></th>
	</tr>
<?php
	$i = 0;
	foreach($wList as $when => $option)
	{
		list($day, $time) = explode("|", $when);
		list($resort, $option) = explode("|", $option);
		$list = array();
		foreach($options as $k => $v)
		{
			if((int)$option & (int)$k)
				$list[] = $v;
		}
?>
	<tr>
		<td><?php echo $days[$day] ?></td>
		<td><?php echo $time ?></td>
		<td><?php echo $resorts[$resort] ?></td>
		<td><?php echo implode(', ', $list) ?></td>
		<td width="15%"><a class="btn btn-danger" href="bunny_plugin.php?p=skiresort&rd=<?php echo $day ?>&rt=<?php echo $time ?>"><?php echo __tr('Remove') ?></a></td>
	</tr>
<?php  } ?>
</table>
<?php } ?>

				</div>

				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'skiresort_rfid' ? ' active' : '' ?>" id="rfid">

<form method="post" class="form-horizontal">

          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Resort") ?></label>
            <div class="controls">
<select name="aresort" id="resorts" style="width: 250px">
	<option value=""></option>
	<?php 
	foreach($mountains as $key => $mountain) { ?> 
		<optgroup label="<?php echo utf8_decode($mountain['name']) ?>">
	<?php foreach($mountain['resorts'] as $id => $resort) { ?>
		<option value="<?php echo $id ?>"><?php echo utf8_decode($resort); ?></option>
	<?php } ?>
		</optgroup>
	<?php } ?>
</select>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Default options") ?></label>
            <div class="controls">
		<input type="checkbox" name="aoption[1]" value="1"><?php echo __tr("Snow condition at top of resort") ?><br />
		<?php echo __tr('Also say altitude') ?> :
		<input type="radio" name="aoption[2]" value="1"> <?php echo __tr("Yes") ?> &nbsp;
		<input type="radio" name="aoption[2]" value="0"> <?php echo __tr("No") ?> &nbsp;<br />
		<input type="checkbox" name="aoption[4]" value="1"><?php echo __tr("Snow condition at bottom of resort") ?><br />
		<?php echo __tr('Also say altitude') ?> :
		<input type="radio" name="aoption[8]" value="1"> <?php echo __tr("Yes") ?> &nbsp;
		<input type="radio" name="aoption[8]" value="0"> <?php echo __tr("No") ?> &nbsp;<br />
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
            <a href="bunny_plugin.php?p=skiresort" class="btn"><?php echo __tr("Cancel") ?></a>
          </div>


</form>

<?php if(count($Assoc)): ?>
<table class="table table-bordered table-striped span10">
<tr><th colspan="3"><?php echo __tr('Associations') ?></th></tr>
<tr>
	<th><?php echo __tr('Ztamp') ?></th>
	<th><?php echo __tr('Resort') ?></th>
	<th><?php echo __tr('Options') ?></th>
	<th><?php echo __tr('Actions') ?></th>
</tr>
<?php foreach($Assoc as $k=>$v):
	list($resort, $option) = explode("|", $v);
	$list = array();
	foreach($options as $key => $v)
	{
		if((int)$option & (int)$key)
			$list[] = $v;
	}

?>
<tr>
	<td><?php echo $Ztamps[$k] . " - " . $k; ?></td>
	<td><?php echo $resorts[$resort] ?></td>
	<td><?php echo implode(', ', $list) ?></td>
	<td><a href="bunny_plugin.php?p=skiresort&rtag=<?php echo $k ?>" class="btn btn-danger btn-small"><i class="icon-trash icon-large"></i> <?php echo __tr('Remove') ?></a></td>
</tr>
<?php endforeach; ?>

</table>
<?php endif; ?>
</form>



				</div>

			</div>
		</div>
<?php
$js = '<link href="js/select2.css" rel="stylesheet"/><script src="js/select2.js"></script> <script>function format(resort) {if (!resort.id) return resort.text; return resort.text; }; $(document).ready(function() { $("#resorts").select2({formatResult: format,formatSelection: format}); });</script>';
$ojnTemplate->setJS($js);
?>
