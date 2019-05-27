<?php
$reload = false;

if(!empty($_POST)) {
	if(isset($_POST['id']) && isset($_POST['group'])) {
		Message::AddFromApi($ojnAPI->getApiString("plugin/weather/setgroup?id=".$_POST['id']."&name=".urlencode($_POST['group'])."&".$ojnAPI->getToken()));
		$reload = true;
	}
	if(isset($_POST['condition']) && isset($_POST['group'])) {
		Message::AddFromApi($ojnAPI->getApiString("plugin/weather/setcondition?id=".$_POST['condition']."&group=".$_POST['group']."&".$ojnAPI->getToken()));
		$reload = true;
	}
	if(isset($_POST['condition']) && isset($_POST['t'])  && isset($_POST['when'])  && isset($_POST['lng'])) {
		Message::AddFromApi($ojnAPI->getApiString("plugin/weather/settranslation?id=".$_POST['t']."&when=".$_POST['when']."&lng=".$_POST['lng']."&tr=".urlencode($_POST['condition'])."&".$ojnAPI->getToken()));
		$reload = true;
	}
}
if($reload)
{
	header("Location: server_plugin.php?p=weather");
	exit();
}
$groupList = $ojnAPI->getApiMapped("plugin/weather/getgroup?".$ojnAPI->getToken());
ksort($groupList, SORT_NUMERIC);
$conditionsList = $ojnAPI->getApiMapped("plugin/weather/getconditions?".$ojnAPI->getToken());
ksort($conditionsList, SORT_NUMERIC);
$windList = $ojnAPI->getApiMapped("plugin/weather/getgroup?wind=&".$ojnAPI->getToken());
ksort($windList, SORT_NUMERIC);
$speedList = $ojnAPI->getApiMapped("plugin/weather/getconditions?wind=&".$ojnAPI->getToken());
ksort($speedList, SORT_NUMERIC);

if(isset($_GET['new']) && isset($_GET['e']) && $_GET['e'] == "group" && isset($_GET['g']))
{
	$groupList[$_GET['g']] = __tr("New group");
}
include(ROOT_SITE.'include/message.php');


if(isset($_GET['t']))
{
	if(!isset($groupList[$_GET['t']]))
	{
		Message::AddError("No such group");
		header("Location: server_plugin.php?p=weather");
		exit();
	}
	else
	{
		if(isset($_GET['new']))
		{
?>
<form method="POST">
<input type="hidden" name="t" value="<?php echo $_GET['t'] ?>"/>
<input type="hidden" name="lng" value="<?php echo $_GET['lng'] ?>"/>
<input type="hidden" name="when" value="<?php echo $_GET['when'] ?>"/>
<input class="span12" type="text" name="condition">
<input type="submit" value="Enregistrer" class="btn btn-mini btn-primary"/>
</form>
<?php
		}
		else
		{
?>
City, Code, temp
<?php
		foreach( array('current', 'forecast') as $when):
			foreach( array('fr', 'en', 'es') as $lng):
			$trs = $ojnAPI->getApiList("plugin/weather/gettranslation?id=".$_GET['t']."&lng=".$lng."&when=".$when."&".$ojnAPI->getToken());
?>


<form method="POST">
<table class="table table-bordered table-striped">
	<tr>
		<th colspan="4"><?php echo __tr("Sentences (%1, %2)", $when, $lng) ?><div class="pull-right"><a href="server_plugin.php?p=weather&t=<?php echo $_GET['t'] ?>&lng=<?php echo $lng ?>&when=<?php echo $when ?>&new" class="btn btn-mini btn-inverse">+</a></div></th>
	</tr>
	<tr>
		<th><?php echo __tr("Sentence") ?></th>
		<th><?php echo __tr("Actions") ?></th>
	</tr>
<?php
	$i = 0;
	foreach($trs as $v) {
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><?php echo $v ?></td>
		<td width="15%"><a href="server_plugin.php?p=weather&e=group&g=<?php echo $c ?>" class="btn btn-mini btn-primary"><?php echo __tr("Edit") ?></a>&nbsp;<a href="server_plugin.php?p=weather&t=<?php echo $c ?>" class="btn btn-mini btn-primary"><?php echo __tr("Text") ?></a></td>
	</tr>
<?php } ?>
</table>
</form>




<?php
			endforeach;
		endforeach;
		}
	}

}
else
{
?>



<fieldset>
<form method="POST">
<table class="table table-bordered table-striped">
	<tr>
		<th colspan="4"><?php echo __tr("Weather condition's groups") ?><div class="pull-right"><a href="server_plugin.php?p=weather&e=group&g=<?php echo !empty($groupList) ? max(array_keys($groupList))+1 : 0 ?>&new" class="btn btn-mini btn-inverse">+</a></div></th>
	</tr>
	<tr>
		<th><?php echo __tr("Group ID") ?></th>
		<th><?php echo __tr("Group name") ?></th>
		<th><?php echo __tr("Actions") ?></th>
	</tr>
<?php
	$i = 0;
	foreach($groupList as $c => $v) {
		if(isset($_GET['e']) && $_GET['e'] == "group" && isset($_GET['g']) && $_GET['g'] == $c)
		{
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><input type="hidden" name="id" value="<?php echo $c ?>" /><?php echo $c ?></td>
		<td><input type="text" name="group" value="<?php echo $v ?>"/></td>
		<td width="15%"><input type="submit" value="Enregistrer" class="btn btn-mini btn-primary"/></td>
	</tr>
<?php
		}
		else
		{
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><?php echo $c ?></td>
		<td><?php echo $v ?></td>
		<td width="15%"><a href="server_plugin.php?p=weather&e=group&g=<?php echo $c ?>" class="btn btn-mini btn-primary"><?php echo __tr("Edit") ?></a>&nbsp;<a href="server_plugin.php?p=weather&t=<?php echo $c ?>" class="btn btn-mini btn-primary"><?php echo __tr("Text") ?></a></td>
	</tr>
<?php 	} ?>
<?php } ?>
</table>
</form>
<form method="POST">
<table class="table table-bordered table-striped">
	<tr>
		<th colspan="4"><?php echo __tr("Wind groups") ?><div class="pull-right"><a href="server_plugin.php?p=weather&e=wind&g=<?php echo !empty($groupList) ? max(array_keys($groupList))+1 : 0 ?>&new" class="btn btn-mini btn-inverse">+</a></div></th>
	</tr>
	<tr>
		<th><?php echo __tr("Group ID") ?></th>
		<th><?php echo __tr("Group name") ?></th>
		<th><?php echo __tr("Actions") ?></th>
	</tr>
<?php
	$i = 0;
	foreach($windList as $c => $v) {
		if(isset($_GET['e']) && $_GET['e'] == "wind" && isset($_GET['g']) && $_GET['g'] == $c)
		{
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><input type="hidden" name="id" value="<?php echo $c ?>" /><?php echo $c ?></td>
		<td><input type="text" name="group" value="<?php echo $v ?>"/></td>
		<td width="15%"><input type="submit" value="Enregistrer" class="btn btn-mini btn-primary"/></td>
	</tr>
<?php
		}
		else
		{
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><?php echo $c ?></td>
		<td><?php echo $v ?></td>
		<td width="15%"><a href="server_plugin.php?p=weather&e=wind&g=<?php echo $c ?>" class="btn btn-mini btn-primary"><?php echo __tr("Edit") ?></a>&nbsp;<a href="server_plugin.php?p=weather&t=<?php echo $c ?>" class="btn btn-mini btn-primary"><?php echo __tr("Text") ?></a></td>
	</tr>
<?php 	} ?>
<?php } ?>
</table>
</form>
<form method="POST">
<table class="table table-bordered table-striped">
	<tr>
		<th colspan="4"><?php echo __tr("Weather conditions") ?><div class="pull-right"><a href="server_plugin.php?p=weather&e=conditions&new" class="btn btn-mini btn-inverse">+</a></div></th>
	</tr>
	<tr>
		<th><?php echo __tr("Condition ID") ?></th>
		<th><?php echo __tr("In group") ?></th>
		<th><?php echo __tr("Actions") ?></th>
	</tr>
<?php
	$i = 0;
	foreach($conditionsList as $c => $v) {
		if(isset($_GET['e']) && $_GET['e'] == "conditions" && isset($_GET['g']) && $_GET['g'] == $c)
		{
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><input type="hidden" name="condition" value="<?php echo $c ?>" /><?php echo $c ?></td>
		<td>
<select name="group">
<?php foreach($groupList as $group => $name): ?>
<option value="<?php echo $group ?>"<?php if($group==$c): ?> selected="selected"<?php endif; ?>><?php echo $name ?></option>
<?php endforeach; ?>
</select>
</td>
		<td width="15%"><input type="submit" value="Enregistrer" class="btn btn-mini btn-primary"/></td>
	</tr>
<?php
		}
		else
		{
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><?php echo $c ?></td>
		<td><?php echo $groupList[$v] ?></td>
		<td width="15%"><a href="server_plugin.php?p=weather&e=group&g=<?php echo $c ?>" class="btn btn-mini btn-primary"><?php echo __tr("Edit") ?></a></td>
	</tr>
<?php 	} ?>
<?php } ?>
	<?php	if(isset($_GET['e']) && $_GET['e'] == "conditions" && isset($_GET['new'])): ?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><input type="text" name="condition" value="" /></td>
		<td>
<select name="group">
<?php foreach($groupList as $group => $name): ?>
<option value="<?php echo $group ?>"><?php echo $name ?></option>
<?php endforeach; ?>
</select>
</td>
		<td width="15%"><input type="submit" value="Enregistrer" class="btn btn-mini btn-primary"/></td>
	</tr>
	<?php endif; ?>
</table>
</form>
</fieldset>
<?php
}
?>
