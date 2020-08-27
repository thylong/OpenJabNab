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

<form method="post">
  <div class="form-group row">
    <label class="col-sm-1 col-form-label" for="frequency"><?php echo __tr("Frequency") ?></label>
    <div class="col-sm-2 input-group">
      <select name="frequency" class="form-control">
        <?php foreach($frequencies as $k => $v): ?>
        <option value="<?php echo $k ?>" <?php if ($frequency==$k) echo 'selected'; ?> ><?php echo $v ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-sm-1">
      <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
    </div>
  </div>
</form>

<form method="post">
  <div class="form-group row">
    <label class="col-sm-3 col-form-label" for="RFID"><?php echo __tr("Select RFID to use") ?></label>
    <div class="col-sm-4 input-group">
      <select name="RFID" class="form-control">
        <?php foreach($Ztamps as $k=>$v): ?>
        <option value="<?php echo $k; ?>"><?php echo $v.($k != "" ? "($k)" : ""); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-sm-1">
      <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
    </div>
  </div>
</form>
