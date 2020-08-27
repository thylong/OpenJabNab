<?php 
$reload = false;
if(!empty($_POST)) {
	if(isset($_POST['weather']) && $_POST['weather'] == "on") {
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/led/setled?type=Weather&status=enabled&".$ojnAPI->getToken()));
		$reload = true;
	} else {
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/led/setled?type=Weather&status=disabled&".$ojnAPI->getToken()));
		$reload = true;
	}
	if(isset($_POST['weather_city'])) {
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/led/setweathercity?city=".urlencode($_POST['weather_city'])."&".$ojnAPI->getToken()));
		$reload = true;
	}
	if(isset($_POST['stock']) && $_POST['stock'] == "on") {
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/led/setled?type=Stock&status=enabled&".$ojnAPI->getToken()));
		$reload = true;
	} else {
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/led/setled?type=Stock&status=disabled&".$ojnAPI->getToken()));
		$reload = true;
	}
	if(isset($_POST['stock_quote'])) {
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/led/setstockquote?quote=".urlencode($_POST['stock_quote'])."&".$ojnAPI->getToken()));
		$reload = true;
	}
if($reload) {
	header("Location: bunny_plugin.php?p=led");
	exit;
}
}
$weather = $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/led/getweathercity?".$ojnAPI->getToken());
$weather = isset($weather['value']) ? (string)($weather['value']) : '';
$weather = explode("|", $weather);
$weatherLed =  $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/led/getled?type=Weather&".$ojnAPI->getToken());
$weatherLed = isset($weatherLed['value']) && $weatherLed['value'] == "enabled" ? 1 : 0;

$stock = $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/led/getstockquote?".$ojnAPI->getToken());
$stock = isset($stock['value']) ? (string)($stock['value']) : '';
$stockLed =  $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/led/getled?type=Stock&".$ojnAPI->getToken());
$stockLed = isset($stockLed['value']) && $stockLed['value'] == "enabled" ? 1 : 0;
?>
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="weather" class="control-label"> <input type="checkbox" name="weather" <?php if($weatherLed == 1) { echo 'checked="checked"'; } ?>/> <?php echo __tr("Weather for") ?></label>
            <div class="controls">
<div class="input-append"><input type="text" id="weather_search" class="span2" value="<?php echo  isset($weather[1]) ? $weather[1] : __tr("Unknow city") ?>"><a onclick="launchSearch()" class="btn"><?php echo __tr('Search the location code') ?></a></div>
<div id="weather_results">&nbsp;</div>
            </div>
            <div class="controls">
<div class="input-append"><input type="text" id="weather_city_code" class="span2" value="<?php echo $weather[0] ?>"><span class="add-on" id="weather_city_name"><?php echo isset($weather[1]) ? $weather[1] : __tr("Unknow city") ?></span></div>
<input name="weather_city" type="hidden" value="weather_city" id="weather_city" value="<?php echo implode("|", $weather) ?>">
<p class="help-block"><?php echo __tr('Location ID used by Yahoo weather.') ?></p>
            </div>
          </div>
          <div class="control-group">
            <label for="stock" class="control-label"> <input type="checkbox" name="stock" <?php if($stockLed == 1) { echo 'checked="checked"'; } ?>/> <?php echo __tr("Stock for") ?></label>
            <div class="controls">
<input type="text" name="stock_quote" value="<?php echo $stock ?>">
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <button class="btn"><?php echo __tr("Cancel") ?></button>
          </div>
</form>
<script>
function launchSearch()
{
$.get('searchCity.php?city=' + $("#weather_search").val(), function(data) {
  $('#weather_results').html(data);
});
}
function useCity(id, name)
{
$('#weather_city_code').val(id);
$('#weather_city_name').html(name);
$('#weather_city').val(id + "|" + name);
}
</script>
