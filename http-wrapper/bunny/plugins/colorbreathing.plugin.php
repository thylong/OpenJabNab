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
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="color" class="control-label"><?php echo __tr("Breathing color") ?></label>
            <div class="controls">
<select name="color">
<?php foreach($colors as $color){ ?>
<option value="<?php echo $color; ?>"<?php echo $color==$current ? " selected='selected'" : ""; ?>><?php echo __tr($color); ?></option>
<?php } ?>
</select>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <button class="btn"><?php echo __tr("Cancel") ?></button>
          </div>
</form>
