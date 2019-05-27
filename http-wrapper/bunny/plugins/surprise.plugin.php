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
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Voice") ?></label>
            <div class="controls">
<select name="folder">
<option><?php echo __tr("Choose a voice") ?></option>
<?php foreach($folders as $folder) { ?>
<option value="<?php echo $folder ?>"><?php echo $folder ?></option>
<?php } ?>
</select>
	<p class="help-block"><?php echo __tr('You can now use your file groups') ?>. <a href="files.php"><?php echo __tr('Create a group') ?></a></p>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Frequency") ?></label>
            <div class="controls">
<select name="frequency">
<option><?php echo __tr("Choose a frequency") ?></option>
<?php foreach($freqs as $freq => $text) { ?>
<option value="<?php echo $freq ?>"><?php echo $text ?></option>
<?php } ?>
</select>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <button class="btn"><?php echo __tr("Cancel") ?></button>
          </div>
</form>

<?php
$webcasts = $ojnAPI->getApiList("bunny/".$_SESSION['bunny']."/surprise/getSurprises?".$ojnAPI->getToken());
if($webcasts){
?>
<center>
<table class="table table-bordered table-striped span11">
	<tr>
		<th colspan="3"><?php echo __tr('Surprises list') ?></th>
	</tr>
	<tr>
		<th><?php echo __tr('Name') ?></th>
		<th><?php echo __tr('Frequency') ?></th>
		<th><?php echo __tr('Actions') ?></th>
	</tr>
<?php
	$i = 0;
	foreach($webcasts as $item) {
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><?php echo urldecode($item->key) ?></td>
		<td><?php echo $freqs[$item->value + 0] ?></td>
		<td width="15%"><a href="bunny_plugin.php?p=surprise&r=<?php echo $item->key ?>" class="btn btn-danger btn-small"><i class="icon-trash icon-large"></i> <?php echo __tr('Remove') ?></a></td>
	</tr>
<?php  } ?>
</table>
<?php } ?>
