<?php 
if(isset($_POST['color'])) {
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/colorbreathing/setColor?name=".$_POST['color']."&".$ojnAPI->getToken()));
	header("Location: bunny_plugin.php?p=colorbreathing");
	exit;
}
$colors = $ojnAPI->getApiList("bunny/".$_SESSION['bunny']."/colorbreathing/getColorList?".$ojnAPI->getToken());
$current = $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/colorbreathing/getColor?".$ojnAPI->getToken());
$current = isset($current['ok']) ? $current['ok'] : 'violet';
?>
<form method="post">
  <div class="form-group row">
    <label class="col-sm-2 col-form-label" for="color"><?php echo __tr("Breathing color") ?></label>
    <div class="col-sm-1">    
      <select name="color" class="form-control">
        <?php foreach($colors as $color): ?>
          <option value="<?php echo $color; ?>"<?php echo $color==$current ? " selected='selected'" : ""; ?>><?php echo __tr($color); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-sm-4">
      <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
    </div>
  </div>
</form>
