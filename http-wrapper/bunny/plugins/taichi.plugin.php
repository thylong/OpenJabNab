<?php
$Ztamps = $ojnAPI->GetListofZtamps(false);
$Ztamps = array_merge(array("" => __tr('None')), $Ztamps);
$reload = false;
if(!empty($_POST['frequency'])) {
	Message::addFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/taichi/setFrequency?value=".$_POST['frequency']."&".$ojnAPI->getToken()));
	$reload = true;
}
if(isset($_POST['RFID'])) {
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/taichi/setRfid?tag=".$_POST['RFID']."&".$ojnAPI->getToken()));
	$reload = true;
}
if($reload)
{
	header("Location: bunny_plugin.php?p=taichi");
	exit();
}
$frequencies = array(
	10 => __tr("Ultra"),
	30 => __tr("A lot"),
	60 => __tr("Often"),
	120 => __tr("A little"),
	0 => __tr("No TaïChi"),
);
$frequency = $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/taichi/getFrequency?".$ojnAPI->getToken());
$frequency = isset($frequency['value']) ? $frequency['value'] : '';
?>

<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Frequency") ?></label>
            <div class="controls">
<select name="frequency"> 
<?php foreach($frequencies as $k => $v): ?>
<option value="<?php echo $k ?>" <?php if ($frequency==$k) echo 'selected'; ?> ><?php echo $v ?></option>
<?php endforeach; ?>
</select>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <button class="btn"><?php echo __tr("Cancel") ?></button>
          </div>
</form>
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Select RFID to use") ?></label>
            <div class="controls">
<select name="RFID">
	<?php foreach($Ztamps as $k=>$v): ?>
	<option value="<?php echo $k; ?>"><?php echo $v.($k != "" ? "($k)" : ""); ?></option>
	<?php endforeach; ?>
	</select>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <button class="btn"><?php echo __tr("Cancel") ?></button>
          </div>
</form>
