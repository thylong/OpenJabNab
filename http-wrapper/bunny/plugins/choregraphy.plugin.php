<?php
$reload = false;

if(isset($_POST['chor']) && isset($_POST['sender'])) {
	$_SESSION['subtab'] = "choregraphy_setup";
	$sender = $_POST['sender'] == "apijsp" ? 'api.jsp' : $_POST['sender'];
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/choregraphy/config?action=add&chor=".$_POST['chor']."&sender=".urlencode($sender)."&".$ojnAPI->getToken()));
	header("Location: bunny_plugin.php?p=choregraphy");
}
if(isset($_GET['rmchor'])) {
	$_SESSION['subtab'] = "choregraphy_setup";
	$sender = $_GET['rmchor'] == "apijsp" ? 'api.jsp' : $_GET['rmchor'];
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/choregraphy/config?action=del&sender=".urlencode($sender)."&".$ojnAPI->getToken()));
	$reload = true;
}

if($reload) {
	header("Location: bunny_plugin.php?p=choregraphy");
	exit;
}
if(!isset($_SESSION['subtab']) || !preg_match("|^choregraphy_|", $_SESSION['subtab'])) {
	$_SESSION['subtab'] = "choregraphy_setup";
}
$Assoc = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/choregraphy/config?action=list&".$ojnAPI->getToken());

$plugins = $ojnAPI->getListOfPlugins(false, $ojnTemplate->getLanguage());
if(is_array($plugins)) {
	asort($plugins);
}
$plugins = array_merge(array('apijsp' => __tr('API Call')), $plugins);
$chors = array('random' => __tr('Random'));
for($i=0; $i<=7; $i++) {
	$chors[$i] = $i+1;
}
$bunnyPlugins = $ojnAPI->getListOfBunnyEnabledPlugins(false);
?>

		<div class="tabbable">
		<ul class="nav nav-tabs">
		  <li<?php echo $_SESSION['subtab'] == 'choregraphy_setup' ? ' class="active"' : '' ?>><a href="#setup" data-toggle="tab"><?php echo __tr('Setup') ?></a></li>
		</ul>
		<br />
		
			<div class="tab-content">
				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'choregraphy_setup' ? ' active' : '' ?>" id="setup">
<form method="post" class="form-horizontal">
          <div class="control-group">
            <div class="controls">
<select name="chor">
	<?php foreach($chors as $k => $v): ?>
	<option value="<?php echo $k; ?>"><?php echo $v; ?></option>
	<?php endforeach; ?>
</select>
&nbsp;
<select name="sender" class="span6">
	<?php foreach($plugins as $k=>$p): ?>
		<?php if($k != 'choregraphy'): ?>
	<option value="<?php echo $k; ?>"><?php echo $p; ?></option>
		<?php endif; ?>
	<?php endforeach; ?>
</select>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Add") ?></button>
          </div>
</form>

<?php if(count($Assoc)): ?>
<table class="table table-bordered table-striped span10">
<tr><th colspan="3"><?php echo __tr('Choregraphies') ?></th></tr>
<tr>
	<th colspan="2"><?php echo __tr('Choregraphy') ?></th>
	<th><?php echo __tr('Actions') ?></th>
</tr>
<?php foreach($Assoc as $k=>$v): ?>
<?php if($k == 'api.jsp') {$k = 'apijsp';} ?>
<tr>
	<td><?php echo $chors[$v] ?></td>
	<td><?php echo $plugins[$k]; ?></td>
	<td><a href="bunny_plugin.php?p=choregraphy&rmchor=<?php echo urlencode($k) ?>" class="btn btn-danger btn-small"><i class="icon-trash icon-large"></i> <?php echo __tr('Remove') ?></a></td>
</tr>
<?php endforeach; ?>

</table>
<?php endif; ?>

				</div>
			</div>
		</div>
