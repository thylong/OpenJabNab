<?php
$Ztamps = $ojnAPI->GetListofZtamps(false);
$reload = false;
if(isset($_POST['addmac']))
{
	$_SESSION['subtab'] = "nab2nab_receiver";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/nab2nab/addfriend?name=".urlencode($_POST['addname'])."&sn=".urlencode($_POST['addmac'])."&".$ojnAPI->getToken()));
	$reload = true;
}
if(isset($_POST['afriend']) && isset($_POST['atag']))
{
	$_SESSION['subtab'] = "nab2nab_rfid";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/nab2nab/setreceiverontag?tag=".$_POST['atag']."&sn=".urlencode($_POST['afriend'])."&".$ojnAPI->getToken()));
	$reload = true;
}
if(isset($_POST['rtag']))
{
	$_SESSION['subtab'] = "nab2nab_rfid";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/nab2nab/removereceiverontag?tag=".$_POST['rtag']."&".$ojnAPI->getToken()));
	$reload = true;
}
else if(!empty($_GET['rm']))
{
	$_SESSION['subtab'] = "nab2nab_receiver";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/nab2nab/removefriend?sn=".urlencode($_GET['rm'])."&".$ojnAPI->getToken()));
	$reload = true;
}
$pList = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/nab2nab/getfriends?".$ojnAPI->getToken());
$Assoc = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/nab2nab/getreceiversontags?".$ojnAPI->getToken());

if(!isset($_SESSION['subtab']) || !preg_match("|^nab2nab_|", $_SESSION['subtab'])) {
	$_SESSION['subtab'] = "nab2nab_receiver";
}
if($reload)
{
	header("Location: bunny_plugin.php?p=nab2nab");
	exit();
}
?>

<div class="span11">
<?php echo __tr("To send a message to another bunny, just use your Ztamp, and then you have 1 minute to record your message") ?>.<br />
<?php echo __tr("To record a message, long click on the button, wait for the red nose, and keep pushing the button while speaking. Release the button to send the message") ?>.
</div>

		<div class="tabbable">
		<ul class="nav nav-tabs">
		  <li<?php echo $_SESSION['subtab'] == 'nab2nab_receiver' ? ' class="active"' : '' ?>><a href="#receiver" data-toggle="tab"><?php echo __tr('Friends List') ?></a></li>
		  <li<?php echo $_SESSION['subtab'] == 'nab2nab_rfid' ? ' class="active"' : '' ?>><a href="#rfid" data-toggle="tab"><?php echo __tr('RFID') ?></a></li>
		</ul>
		<br />
		
			<div class="tab-content">
				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'nab2nab_receiver' ? ' active' : '' ?>" id="receiver">
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Enter MAC address") ?></label>
            <div class="controls">
		<input type="text" name="addmac" class="input-xlarge span4"/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Enter bunny name") ?></label>
            <div class="controls">
		<input type="text" name="addname" class="input-xlarge span4"/>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Add this bunny as friend") ?></button>
            <button class="btn"><?php echo __tr("Cancel") ?></button>
          </div>
</form>
				</div>

				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'nab2nab_rfid' ? ' active' : '' ?>" id="rfid">
<form method="post" class="form-horizontal">
<?php echo __tr("Send message to") ?> <select name="afriend">
	<option value=""></option>
	<?php  if(!empty($pList))
	foreach($pList as $mac => $item) { ?>
		<option value="<?php echo urldecode($mac) ?>"><?php echo urldecode($item); ?></option>
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
	<option value="<?php echo $k; ?>"><?php echo $pList[$v]; ?> - <?php echo $v; ?> (<?php echo $Ztamps[$k] . " - " . $k; ?>)</option>
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
		<th colspan="3"><?php echo __tr('Bunnies') ?></th>
	</tr>
	<tr>
		<th><?php echo __tr('Name') ?></th>
		<th><?php echo __tr('MAC') ?></th>
		<th><?php echo __tr('Actions') ?></th>
	</tr>
<?php
	$i = 0;
	foreach($pList as $mac => $name) {
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><?php echo urldecode($name) ?></td>
		<td><?php echo urldecode($mac) ?></td>
		<td width="15%"><a class="btn btn-danger" href="bunny_plugin.php?p=nab2nab&rm=<?php echo urlencode($mac) ?>"><?php echo __tr("Remove") ?></a></td>
	</tr>
<?php } ?>
</table>
<?php
}
