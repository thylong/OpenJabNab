<?php 
$reload = false;
$Ztamps = $ojnAPI->GetListofZtamps(false);
if(isset($_GET['rtag'])) {
	$_SESSION['subtab'] = "mailer_rfid";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/mailer/rfid?action=del&tag=".$_GET['rtag']."&".$ojnAPI->getToken()));
	$reload = true;
}
if(isset($_POST['addname'])) {
	$_SESSION['subtab'] = "mailer_mail";


	if($_POST['addname'] == "")
	{
		Message::AddError(__tr('You must choose a name'));
	}
	else if($_POST['addfrommail'] == "")
	{
		Message::AddError(__tr('You must enter a sender mail'));
	}
	else if($_POST['addtomail'] == "")
	{
		Message::AddError(__tr('You must enter a recipient mail'));
	}
	else if($_POST['addtitle'] == "")
	{
		Message::AddError(__tr('You must enter a title'));
	}
	else if($_POST['addcontent'] == "")
	{
		Message::AddError(__tr('You must enter a content'));
	}
	else
	{
		$mail  = "&name=".urlencode($_POST['addname']);
		$mail .= "&fromMail=".urlencode($_POST['addfrommail']);
		$mail .= "&toMail=".urlencode($_POST['addtomail']);
		$mail .= "&subject=".urlencode($_POST['addtitle']);
		$mail .= "&content=".urlencode($_POST['addcontent']);

		if(strlen($_POST['addfromname']))
			$mail .= "&fromName=".urlencode($_POST['addfromname']);
		if(strlen($_POST['addtoname']))
			$mail .= "&toName=".urlencode($_POST['addtoname']);
		if(strlen($_POST['addreport']))
			$mail .= "&report=".urlencode($_POST['addreport']);

		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/mailer/mail?action=add".$mail."&".$ojnAPI->getToken()));
	}
	$reload = true;
}
if(isset($_POST['atag']) && isset($_POST['amail'])) {
	$_SESSION['subtab'] = "mailer_rfid";
	if($_POST['atag'] == "")
	{
		Message::AddError(__tr('You must choose a Ztamp'));
	}
	else if($_POST['amail'] == "")
	{
		Message::AddError(__tr('You must choose a mail'));
	}
	else
	{
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/mailer/rfid?action=add&tag=".$_POST['atag']."&name=".urlencode($_POST['amail'])."&".$ojnAPI->getToken()));
	}
	$reload = true;
}
if(isset($_GET['rp'])) {
	$_SESSION['subtab'] = "mailer_mail";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/mailer/mail?action=del&name=".urlencode($_GET['rp'])."&".$ojnAPI->getToken()));
	$reload = true;
}
else if(isset($_GET['d'])) {
	$_SESSION['subtab'] = "mailer_mail";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/mailer/mail?action=set&name=".urlencode($_GET['d'])."&".$ojnAPI->getToken()));
	$reload = true;
}
$default = $ojnAPI->getApiValue("bunny/".$_SESSION['bunny']."/mailer/mail?action=get&".$ojnAPI->getToken());
$pList = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/mailer/mail?action=list&".$ojnAPI->getToken());
$Assoc = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/mailer/rfid?action=list&".$ojnAPI->getToken());
$user = $ojnAPI->getApiValue("plugin/mailer/config?action=limit&status=User&".$ojnAPI->getToken());
$premium = $ojnAPI->getApiValue("plugin/mailer/config?action=limit&status=Premium&".$ojnAPI->getToken());

if(!isset($_SESSION['subtab']) || !preg_match("|^mailer_|", $_SESSION['subtab'])) {
	$_SESSION['subtab'] = "mailer_mail";
}
if($reload)
{
	header("Location: bunny_plugin.php?p=mailer");
	exit();
}

?>

		<div class="tabbable">
		<div class="alert alert-warning"><a class="close" data-dismiss="alert" href="#">×</a>
			<?php echo __tr('This plugin has a daily limitation, to avoid spam') ?> :
			<ul>
			<li><?php echo __tr('%1 mail sent each day, for %2', $user, __tr('classic users')) ?></li>
			<li><?php echo __tr('%1 mail sent each day, for %2', $premium, __tr('premium users')) ?></li>
			</ul>
		</div>
		<ul class="nav nav-tabs">
		  <li<?php echo $_SESSION['subtab'] == 'mailer_mail' ? ' class="active"' : '' ?>><a href="#mail" data-toggle="tab"><?php echo __tr('Mails') ?></a></li>
		  <li<?php echo $_SESSION['subtab'] == 'mailer_rfid' ? ' class="active"' : '' ?>><a href="#rfid" data-toggle="tab"><?php echo __tr('RFID') ?></a></li>
		</ul>
		<br />
		
			<div class="tab-content">
				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'mailer_mail' ? ' active' : '' ?>" id="mail">
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Name") ?></label>
            <div class="controls">
		<input type="text" name="addname" class="input-xlarge span4"/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Sender mail") ?></label>
            <div class="controls">
		<input type="text" name="addfrommail" class="input-xlarge span4"/>
<div class="input-prepend"><span class="add-on"><?php echo __tr("Name (optional)") ?></span><input class="span4" name="addfromname" type="text"></div>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Recipient mail") ?></label>
            <div class="controls">
		<input type="text" name="addtomail" class="input-xlarge span4"/>
<div class="input-prepend"><span class="add-on"><?php echo __tr("Name (optional)") ?></span><input class="span4" name="addtoname" type="text"></div>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Subject") ?></label>
            <div class="controls">
		<input type="text" name="addtitle" class="input-xlarge span6"/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Content") ?></label>
            <div class="controls">
		<textarea type="text" name="addcontent" class="input-xlarge span6"></textarea>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Report mail sent with bunny") ?></label>
            <div class="controls">
              <label class="checkbox">
		<input type="radio" name="addreport" value="0" checked="checked"/> <?php echo __tr("Bunny won't say that mail is sent") ?>
              </label>
              <label class="checkbox">
		<input type="radio" name="addreport" value="1" /> <?php echo __tr("Bunny will say when mail is sent") ?><br />
              </label>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Add a mail") ?></button>
            <button class="btn"><?php echo __tr("Cancel") ?></button>
          </div>
</form>

<?php
if(!empty($pList)) {
?>
<hr />
<center>
<table class="table table-bordered table-striped span11">
	<tr>
		<th colspan="4"><?php echo __tr('Mail List') ?></th>
	</tr>
	<tr>
		<th class="span2"><?php echo __tr('Name') ?></th>
		<th class="span5"><?php echo __tr('Mail') ?></th>
		<th colspan="2"><?php echo __tr('Actions') ?></th>
	</tr>
<?php
	$i = 0;
	foreach($pList as $key => $item) {
		$mail = preg_split("/\|/", urldecode($item));
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><?php echo $key ?></td>
		<td>
			<b><?php echo __tr('From:') ?></b> <?php echo strlen($mail[1]) ? $mail[1].' &lt;'.$mail[0].'&gt;' : $mail[0] ?><br />
			<b><?php echo __tr('To:') ?></b> <?php echo strlen($mail[3]) ? $mail[3].' &lt;'.$mail[2].'&gt;' : $mail[2] ?><br />
			<b><?php echo __tr('Title:') ?></b> <?php echo $mail[4] ?><br />
			<b><?php echo __tr('Content:') ?></b> <?php echo htmlentities(utf8_decode($mail[5])) ?><br />
		</td>
		<td class="span2">
			<a class="btn btn-danger" href="bunny_plugin.php?p=mailer&rp=<?php echo $key ?>"><?php echo __tr("Remove") ?></a>
		</td>
		<td class="span2"><?php if($default != $key) { ?><a class="btn btn-primary" href="bunny_plugin.php?p=mailer&d=<?php echo $key ?>"><?php echo __tr("Set as default") ?></a><?php } else { ?><?php echo __tr("Default mail") ?><?php } ?></td>
	</tr>
<?php } ?>
</table>
<?php
}
?>
				</div>

				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'mailer_rfid' ? ' active' : '' ?>" id="rfid">

<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Send mail") ?></label>
            <div class="controls">
	<select name="amail" class="span4">
	<option value=""></option>
	<?php  if(!empty($pList))
	foreach($pList as $key => $item) { ?>
		<option value="<?php echo $key ?>"><?php echo $key; ?></option>
	<?php } ?>
</select> <?php echo __tr("on Ztamp") ?> <select name="atag" class="span4"> 
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
<table class="table table-bordered table-striped span10">
<tr><th colspan="3"><?php echo __tr('Associations') ?></th></tr>
<tr>
	<th><?php echo __tr('Mail') ?></th>
	<th><?php echo __tr('Ztamp') ?></th>
	<th><?php echo __tr('Actions') ?></th>
</tr>
<?php foreach($Assoc as $k=>$v): ?>
<tr>
	<td><?php echo $v; ?></td>
	<td><?php echo $Ztamps[$k] . " - " . $k; ?></td>
	<td><a href="bunny_plugin.php?p=mailer&rtag=<?php echo $k ?>" class="btn btn-danger btn-small"><i class="icon-trash icon-large"></i> <?php echo __tr('Remove') ?></a></td>
</tr>
<?php endforeach; ?>

</table>
<?php endif; ?>
</form>



				</div>


			</div>
		</div>
