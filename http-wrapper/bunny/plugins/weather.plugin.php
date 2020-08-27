<?php
$reload = false;
$Ztamps = $ojnAPI->GetListofZtamps(false);

if(!empty($_GET['rtag'])) {
	$_SESSION['subtab'] = "weather_rfid";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/weather/removerfid?tag=".$_GET['rtag']."&".$ojnAPI->getToken()));
	$reload = true;
}

if(!empty($_POST)) {
	if(isset($_POST['weather_city'])) {
		$_SESSION['subtab'] = "weather_cities";
		list($code, $name) = preg_split("/\|/", $_POST['weather_city']);
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/weather/addcity?city=".urlencode($code)."&name=".urlencode($name)."&".$ojnAPI->getToken()));
		$reload = true;
	}
	if(!empty($_POST['scheduleT']) && !empty($_POST['scheduleC'])) {
		$time = trim(preg_replace("|[\.h]|", ":", $_POST['scheduleT']));
		if(preg_match("|\d{1,2}:\d{2}|", $time)) {
			Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/weather/addwebcast?city=".urlencode($_POST['scheduleC'])."&time=".$time."&".$ojnAPI->getToken()));
		} else {
			Message::AddError(__tr("Invalid time format"));
		}
		$_SESSION['subtab'] = "weather_schedule";
		$reload = true;
	}
	if(isset($_POST['acity']) && isset($_POST['atag'])) {
		$_SESSION['subtab'] = "weather_rfid";
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/weather/addrfid?tag=".$_POST['atag']."&city=".urlencode($_POST['acity'])."&".$ojnAPI->getToken()));
		$reload = true;
	}
	if(isset($_POST['lang'])) {
		$_SESSION['subtab'] = "weather_cities";
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/weather/setlang?lg=".$_POST['lang']."&".$ojnAPI->getToken()));
		$reload = true;
	}
}
else if(!empty($_GET['rp'])) {
	$_SESSION['subtab'] = "weather_cities";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/weather/removecity?city=".urlencode($_GET['rp'])."&".$ojnAPI->getToken()));
	$reload = true;
}
else if(!empty($_GET['d'])) {
	$_SESSION['subtab'] = "weather_cities";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/weather/setdefaultcity?city=".urlencode($_GET['d'])."&".$ojnAPI->getToken()));
	$reload = true;
}
else if(!empty($_GET['rw'])) {
	$_SESSION['subtab'] = "weather_schedule";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/weather/removewebcast?time=".$_GET['rw']."&".$ojnAPI->getToken()));
	$reload = true;
}

$Langs = array('fr'=>__tr('French'),'en'=>__tr('English'), 'es'=>__tr('Spanish'), 'de' => __tr('German'));
/*
if(!empty($_POST['a'])) {
	} else if($_POST['a'] == "rfidadd") {
	var_dump($_POST);
		if(!empty($_POST['RfCity']) && !empty($_POST['Tag_Rfa']) && isset($Ztamps[$_POST['Tag_Rfa']])) {
			$retour = $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/weather/addrfid?tag=".$_POST['Tag_Rfa']."&city=".urlencode($_POST['RfCity'])."&".$ojnAPI->getToken());
			$_SESSION['message'] = isset($retour['ok']) ? $retour['ok'] : "Error : ".$retour['error'];
			header("Location: bunny_plugin.php?p=weather");
		}
	} else if($_POST['a'] == "rfidd") {
		if(!empty($_POST['Tag_Rf']) && isset($Ztamps[$_POST['Tag_Rf']])) {
			$retour = $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/weather/removerfid?tag=".$_POST['Tag_Rf']."&".$ojnAPI->getToken());
			$_SESSION['message'] = isset($retour['ok']) ? $retour['ok'] : "Error : ".$retour['error'];
			header("Location: bunny_plugin.php?p=weather");
		}
	} else if($_POST['a'] == "setlang") {
		if(!empty($_POST['Lang']) && isset($Langs[$_POST['Lang']])) {
			$retour = $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/weather/setlang?lg=".$_POST['Lang']."&".$ojnAPI->getToken());
			$_SESSION['message'] = isset($retour['ok']) ? $retour['ok'] : "Error : ".$retour['error'];
			header("Location: bunny_plugin.php?p=weather");
		}
	}
}
*/
$default = $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/weather/getdefaultcity?".$ojnAPI->getToken());
$default = isset($default['value']) ? (string)($default['value']) : '';
$pList = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/weather/getcitieslist?".$ojnAPI->getToken());
$wList = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/weather/getwebcastslist?".$ojnAPI->getToken());
$Assoc = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/weather/listrfid?".$ojnAPI->getToken());
$lang =  $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/weather/getlang?".$ojnAPI->getToken());
$lang = isset($lang['value']) ? (string)($lang['value']) : 'fr';


if(!isset($_SESSION['subtab']) || !preg_match("|^weather_|", $_SESSION['subtab'])) {
	$_SESSION['subtab'] = "weather_cities";
}
if($reload)
{
	header("Location: bunny_plugin.php?p=weather");
	exit();
}
?>
<ul class="nav nav-tabs">
  <li class="nav-item">
    <a class="nav-link <?php echo $_SESSION['subtab'] == 'weather_cities' ? ' active' : '' ?>" href="#cities" data-toggle="tab"><?php echo __tr('Cities lists') ?></a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?php echo $_SESSION['subtab'] == 'weather_schedule' ? ' active' : '' ?>" href="#schedule" data-toggle="tab"><?php echo __tr('Schedules') ?></a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?php echo $_SESSION['subtab'] == 'weather_rfid' ? ' active' : '' ?>" href="#rfid" data-toggle="tab"><?php echo __tr('RFID') ?></a>
  </li>
</ul>

<div class="tab-content pt-2">
	<div class="tab-pane<?php echo $_SESSION['subtab'] == 'weather_cities' ? ' active' : '' ?>" id="cities">
		<div class="alert alert-success">
			<?php echo __tr('To use this plugin, you need to retrieve the code associated to your city') ?>
			<ol>
				<li><?php echo __tr('Enter the name of your city in the first text field') ?></li>
				<li><?php echo __tr('Click on the "%1" button', __tr('Search the location code')) ?></li>
				<li><?php echo __tr('Choose the best matching city in the list, and click on "%1"', __tr("Use this city")) ?>"</li>
				<li><?php echo __tr('Save') ?></li>
			</ol>
		</div>

		<form method="post">
      <div class="form-group row">
        <label class="col-sm-2 col-form-label" for="weather_search"><?php echo __tr("Add a city") ?></label>
        <div class="col-sm-2 input-group">
					<input type="text" name="weather_search" id="weather_search" class="form-control" value="">
				</div>
				<div class="col-sm-2 input-group">
					<a onclick="launchSearch()" class="btn btn-sm btn-primary text-light"><?php echo __tr('Search the location code') ?></a>
				</div>
			</div>
      <div class="form-group row">
				<div class="col-sm-8 offset-sm-2 input-group" id="weather_results"></div>
				<script type="text/javascript">
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
			</div>

      <div class="form-group row">
				<input name="weather_city" type="hidden" id="weather_city" value="">
				<label class="col-sm-2 col-form-label" for="weather_search"><?php echo __tr("Location ID") ?></label>
				<div class="col-sm-2 input-group">
					<input type="text" id="weather_city_code" value=""><span class="add-on" id="weather_city_name"></span>
				</div>
				<div class="col-sm-4 input-group">
					<p class="col-form-label help-block"><?php echo __tr('Location ID used by Yahoo weather.') ?></p>
				</div>
			</div>
      <div class="form-group row">
        <div class="col-sm-1 offset-sm-2">
          <button class="btn btn-primary" type="submit"><?php echo __tr("Add") ?></button>
        </div>
      </div>
		</form>
		<hr />
		<form method="post">
      <div class="form-group row">
        <label class="col-sm-2 col-form-label" for="lang"><?php echo __tr("Language") ?></label>
        <div class="col-sm-2 input-group">
					<select name="lang" class="form-control">
						<?php foreach($Langs as $k => $v): ?>
							<option value="<?php echo $k ?>"<?php if($lang == $k): ?> selected="selected"<?php endif; ?>><?php echo $v ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="col-sm-1">
					<button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
				</div>
			</div>
		</form>

		<?php if(!empty($pList)): ?>
		<hr />
		<h5><?php echo __tr('Cities') ?></h5>
    <table class="table table-bordered table-striped span11">
      <tr>
        <th><?php echo __tr('City') ?></th>
        <th class="col-sm-1"><?php echo __tr('Code') ?></th>
        <th class="col-sm-3"><?php echo __tr('Actions') ?></th>
      </tr>
			<?php foreach($pList as $code => $item): ?>
			<tr>
				<td><?php echo urldecode($item) ?></td>
				<td><i><?php echo $code ?></i></td>
				<td>
					<a class="btn btn-sm btn-danger" href="bunny_plugin.php?p=weather&rp=<?php echo $code ?>"><i class="icon-trash icon-large"></i> <?php echo __tr("Remove") ?></a>
					<?php if($default != $code): ?>
					<a href="bunny_plugin.php?p=weather&d=<?php echo $code ?>" class="btn btn-sm btn-primary"><?php echo __tr("Set as default") ?></a>
					<?php else: ?>
					<span class="btn btn-sm btn-secondary"><?php echo __tr("Default city") ?></span>
					<?php endif; ?>
				</td>
			</tr>
			<?php endforeach; ?>
		</table>
		<?php endif; ?>
	</div>

	<div class="tab-pane<?php echo $_SESSION['subtab'] == 'weather_schedule' ? ' active' : '' ?>" id="schedule">
	<form method="post">
      <div class="form-group row">
        <label class="col-sm-3 col-form-label" for="scheduleT"><?php echo __tr("Add a schedule at (hh:mm)") ?></label>
        <div class="col-sm-2 input-group">
          <div class="input-group-preprend">
            <div class="input-group-text"><i class="icon-time"></i></div>
          </div>
          <input type="text" name="scheduleT" class="timepicker form-control text-center">
        </div>
        <script type="text/javascript">
          $(".timepicker").timepicker({minuteStep: 1,showMeridian: false});
        </script>
      </div>
      <?php /*div class="form-group row">
        <label class="col-sm-3 col-form-label" for="scheduleD"><?php echo __tr("Day") ?></label>
        <div class="col-sm-2 input-group">
          <select name="scheduleD" class="form-control">
            <?php foreach($days as $d => $day): ?>
            <option value="<?php echo $d ?>"><?php echo $day ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div */?>
      <div class="form-group row">
        <label class="col-sm-3 col-form-label" for="scheduleP"><?php echo __tr("City") ?></label>
        <div class="col-sm-4 input-group">
          <select name="scheduleC" class="form-control">
            <option value=""></option>
            <?php foreach($pList as $code => $item): ?>
							<option value="<?php echo $code ?>"><?php echo $item; ?></option>
						<?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-group row">
        <div class="col-sm-1 offset-sm-3">
          <button class="btn btn-primary" type="submit"><?php echo __tr("Add") ?></button>
        </div>
      </div>
    </form>

    <?php if(!empty($wList)): ?>
    <h5><?php echo __tr('Schedules') ?></h5>
    <table class="table table-bordered table-striped">
      <tr>
        <?php /*th class="col-sm-2"><?php echo __tr('Day') ?></th*/?>
        <th class="col-sm-1"><?php echo __tr('Time') ?></th>
        <th><?php echo __tr('City') ?></th>
        <th class="col-sm-1"><?php echo __tr('Actions') ?></th>
      </tr>
      <?php foreach($wList as $time => $city):
          //list($day, $time) = preg_split("/\|/", $when);
      ?>
      <tr>
        <?php /*td><?php echo $days[$day] ?></td*/?>
        <td><?php echo $time; ?></td>
        <td><?php echo preg_replace('/OJN_/', '', $pList[$city]) ?></td>
        <td><a href="bunny_plugin.php?p=weather&rwd=<?php echo $day ?>&rw=<?php echo $time ?>" class="btn btn-sm btn-danger"><i class="icon-trash icon-large"></i> <?php echo __tr('Remove') ?></a></td>
      </tr>
      <?php endforeach; ?>
    </table>
		<?php endif; ?>
  </div>

	<div class="tab-pane<?php echo $_SESSION['subtab'] == 'weather_rfid' ? ' active' : '' ?>" id="rfid">
		<form method="post">
      <div class="form-group row">
        <label class="col-sm-1 col-form-label" for="acity"><?php echo __tr("Launch") ?></label>
        <div class="col-sm-3 input-group">
          <select name="acity" class="form-control">
            <option value=""></option>
            <?php foreach($pList as $code => $item): ?>
						<option value="<?php echo $code ?>"><?php echo urldecode($item); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <label class="col-sm-2 col-form-label" for="atag"><?php echo __tr("on Ztamp") ?></label>
        <div class="col-sm-4 input-group">
          <select name="atag"  class="form-control">
            <option value=""></option>
            <?php foreach($Ztamps as $k=>$v): ?>
            <option value="<?php echo $k; ?>"><?php echo $v; ?> (<?php echo $k; ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-sm-2">
          <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
        </div>
      </div>
    </form>

    <?php if(!empty($Assoc)): ?>
    <h5><?php echo __tr('Associations') ?></h5>
    <table class="table table-bordered table-stripe">
      <tr>
        <th><?php echo __tr('City') ?></th>
        <th class="col-sm-4"><?php echo __tr('Ztamp') ?></th>
        <th class="col-sm-1"><?php echo __tr('Actions') ?></th>
      </tr>
      <?php foreach($Assoc as $k=>$v): ?>
      <tr>
        <td><?php echo $pList[$v]; ?></td>
        <td><?php echo $Ztamps[$k] . " - " . $k; ?></td>
        <td><a href="bunny_plugin.php?p=weather&rtag=<?php echo $k ?>" class="btn btn-danger btn-sm"><i class="icon-trash icon-large"></i> <?php echo __tr('Remove') ?></a></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>
</div>