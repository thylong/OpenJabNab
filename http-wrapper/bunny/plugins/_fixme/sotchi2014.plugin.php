<?php
$reload = false;
if(!empty($_POST['sotchi2014time']) && !empty($_POST['sotchi2014country'])) {
	$_SESSION['subtab'] = "sotchi2014_webcast";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/sotchi2014/addwebcast?country=".$_POST['sotchi2014country']."&time=".$_POST['sotchi2014time']."&".$ojnAPI->getToken()));
	$reload = true;
}
if(!empty($_GET['rw'])) {
	$_SESSION['subtab'] = "sotchi2014_webcast";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/sotchi2014/removewebcast?time=".$_GET['rw']."&".$ojnAPI->getToken()));
	$reload = true;
}
if(!empty($_POST['sotchi2014delay']) && !empty($_POST['sotchi2014country'])) {
	$_SESSION['subtab'] = "sotchi2014_recursive";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/sotchi2014/addrecursive?country=".$_POST['sotchi2014country']."&delay=".$_POST['sotchi2014delay']."&".$ojnAPI->getToken()));
	$reload = true;
}
if(!empty($_GET['rr'])) {
	$_SESSION['subtab'] = "sotchi2014_recursive";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/sotchi2014/removerecursive?delay=".$_GET['rr']."&".$ojnAPI->getToken()));
	$reload = true;
}
$wList = $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/sotchi2014/listwebcast?".$ojnAPI->getToken());
$rList = $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/sotchi2014/listrecursive?".$ojnAPI->getToken());

if(!isset($_SESSION['subtab']) || !preg_match("|^sotchi2014_|", $_SESSION['subtab'])) {
	$_SESSION['subtab'] = "sotchi2014_webcast";
}
if($reload) {
	header("Location: bunny_plugin.php?p=sotchi2014");
	exit;
}
?>

		<div class="tabbable">
		<ul class="nav nav-tabs">
		  <li<?php echo $_SESSION['subtab'] == 'sotchi2014_webcast' ? ' class="active"' : '' ?>><a href="#url" data-toggle="tab"><?php echo __tr('Add a webcast') ?></a></li>
		  <li<?php echo $_SESSION['subtab'] == 'sotchi2014_recursive' ? ' class="active"' : '' ?>><a href="#rfid" data-toggle="tab"><?php echo __tr('Add a recursive task') ?></a></li>
		</ul>
		<br />
		
			<div class="tab-content">
				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'sotchi2014_webcast' ? ' active' : '' ?>" id="url">
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Time for schedule") ?></label>
            <div class="controls">
		<input type="text" name="sotchi2014time" class="dropdown-timepicker"/>
		<p class="note"><?php echo __tr('Use hh:mm format') ?></p>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Country") ?></label>
            <div class="controls">
<select name="sotchi2014country">
<?php
//apcu_delete(APC_PREFIX.'ojn_plugin_sotchi2014_countries_'.$Infos['language']);
if(!($countries = apcu_fetch(APC_PREFIX.'ojn_plugin_sotchi2014_countries_'.$Infos['language']))) {
	$countries = $ojnAPI->getApiMapped("plugin/sotchi2014/listcountries?".$ojnAPI->getToken());
	asort($countries);
	$countries = array_merge(array('RANK' => __tr('Overall ranking')), $countries);
	apcu_store(APC_PREFIX.'ojn_plugin_sotchi2014_countries_'.$Infos['language'], $countries, 86400);
}
foreach($countries as $code => $country)
{
?>
<option value="<?php echo $code ?>"><?php echo $country ?></option>
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
				</div>

				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'sotchi2014_recursive' ? ' active' : '' ?>" id="rfid">
<p><?php echo __tr('Annoucements will be all the day, between 10am and 10pm') ?></p>
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Delay between announcements") ?></label>
            <div class="controls">
		<select name="sotchi2014delay">
		<option value="5"><?php echo __tr('%1 minutes', 5) ?></option>
		<option value="10"><?php echo __tr('%1 minutes', 10) ?></option>
		<option value="15"><?php echo __tr('%1 minutes', 15) ?></option>
		<option value="30"><?php echo __tr('%1 minutes', 30) ?></option>
		<option value="60"><?php echo __tr('%1 hour', 1) ?></option>
		<option value="120"><?php echo __tr('%1 hours', 2) ?></option>
		<option value="180"><?php echo __tr('%1 hours', 3) ?></option>
		<option value="240"><?php echo __tr('%1 hours', 4) ?></option>
		<option value="360"><?php echo __tr('%1 hours', 6) ?></option>
		</select>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Country") ?></label>
            <div class="controls">
<select name="sotchi2014country">
<?php
foreach($countries as $code => $country)
{
?>
<option value="<?php echo $code ?>"><?php echo $country ?></option>
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
				</div>
			</div>
		</div>

<?php
if(isset($wList['list']->item)){
?>
<center>
<table class="table table-bordered table-striped span11">
	<tr>
		<th colspan="3"><?php echo __tr('Webcasts') ?></th>
	</tr>
	<tr>
		<th><?php echo __tr('Time') ?></th>
		<th><?php echo __tr('Name') ?></th>
		<th><?php echo __tr('Actions') ?></th>
	</tr>
<?php
	$i = 0;
	foreach($wList['list']->item as $item) {
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><?php echo urldecode($item->key) ?></td>
		<td><?php echo $countries[(string)$item->value] ?></td>
		<td width="15%"><a class="btn btn-danger" href="bunny_plugin.php?p=sotchi2014&rw=<?php echo $item->key ?>"><?php echo __tr('Remove') ?></a></td>
	</tr>
<?php  } ?>
</table>
<?php 
}
if(isset($rList['list']->item)){
?>
<center>
<table class="table table-bordered table-striped span11">
	<tr>
		<th colspan="3"><?php echo __tr('Recursive webcasts') ?></th>
	</tr>
	<tr>
		<th><?php echo __tr('Delay (minutes)') ?></th>
		<th><?php echo __tr('Name') ?></th>
		<th><?php echo __tr('Actions') ?></th>
	</tr>
<?php
	$i = 0;
	foreach($rList['list']->item as $item) {
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><?php echo urldecode($item->key) ?></td>
		<td><?php echo $countries[(string)$item->value] ?></td>
		<td width="15%"><a class="btn btn-danger" href="bunny_plugin.php?p=sotchi2014&rr=<?php echo $item->key ?>"><?php echo __tr('Remove') ?></a></td>
	</tr>
<?php  } ?>
</table>
<?php } ?>
