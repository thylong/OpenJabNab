<?php
$reload = false;
if(isset($_POST['frequency']) && !empty($_POST['folder']) ) {
	if($_POST['frequency'] == 0)
	{
		Message::addFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/surprise/delSurprise?name=".urlencode($_POST['folder'])."&".$ojnAPI->getToken()));
	}
	else
	{
		Message::addFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/surprise/setSurprise?name=".urlencode($_POST['folder'])."&frequency=".urlencode($_POST['frequency'])."&".$ojnAPI->getToken()));
	}
	$reload = true;
}
else if(!empty($_GET['r'])) {
	Message::addFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/surprise/delSurprise?name=".$_GET['r']."&".$ojnAPI->getToken()));
	$reload = true;
}
if($reload) {
	header("Location: bunny_plugin.php?p=surprise");
	exit;
}
$folders = $ojnAPI->getApiList("bunny/".$_SESSION['bunny']."/surprise/getFolderList?".$ojnAPI->getToken());
/*
500	15	12	18
250	30	24	36
165	45	36	54
125	60	48	72
83	90	72	108
62	121	97	145
31	242	193	291
*/
$freqs = array(
	0 => __tr('Never'),
	50 => __tr('Just a little'),
	125 => __tr('Often'),
	250 => __tr('Very often'),
	1500 => __tr('Always'),
);
?>
<form method="post" class="mb-2">
  <div class="form-group row">
    <label class="col-sm-1 col-form-label" for="folder"><?php echo __tr("Surprise") ?></label>
    <div class="col-sm-3 input-group">
			<select name="folder" class="form-control">
				<option><?php echo __tr("Choose a surprise") ?></option>
				<?php foreach($folders as $folder): ?>
				<option value="<?php echo $folder ?>"><?php echo $folder ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="col-sm-5">
			<p class="help-block"><?php echo __tr('You can now use your file groups') ?>.. <a class="btn btn-sm btn-secondary" href="/account/files.php"><?php echo __tr('Create a group') ?></a></p>
		</div>
	</div>
	<div class="form-group row">
    <label class="col-sm-1 col-form-label" for="frequency"><?php echo __tr("Frequency") ?></label>
    <div class="col-sm-3 input-group">
			<select name="frequency" class="form-control">
				<option><?php echo __tr("Choose a frequency") ?></option>
				<?php foreach($freqs as $freq => $text) { ?>
				<option value="<?php echo $freq ?>"><?php echo $text ?></option>
				<?php } ?>
			</select>
		</div>
	</div>
	<div class="form-group row">
		<div class="col-sm-1 offset-sm-1">
			<button class="btn btn-primary" type="submit"><?php echo __tr("Add") ?></button>
		</div>
	</div>
</form>

<?php
$webcasts = $ojnAPI->getApiList("bunny/".$_SESSION['bunny']."/surprise/getSurprises?".$ojnAPI->getToken());
if($webcasts):
?>
<h5><?php echo __tr('Surprises list') ?></h5>
<table class="table table-bordered table-striped">
	<tr>
		<th><?php echo __tr('Name') ?></th>
		<th class="col-sm-2"><?php echo __tr('Frequency') ?></th>
		<th class="col-sm-1"><?php echo __tr('Actions') ?></th>
	</tr>
	<?php foreach($webcasts as $item): ?>
	<tr>
		<td><?php echo urldecode($item->key) ?></td>
		<td><?php echo $freqs[$item->value + 0] ?></td>
		<td><a href="bunny_plugin.php?p=surprise&r=<?php echo $item->key ?>" class="btn btn-danger btn-sm"><i class="icon-trash icon-large"></i> <?php echo __tr('Remove') ?></a></td>
	</tr>
	<?php endforeach; ?>
</table>
<?php endif; ?>
