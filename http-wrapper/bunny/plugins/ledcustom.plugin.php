<?php
$reload = false;
if(isset($_POST['addurl'])) {
  $_SESSION['subtab'] = "ledcustom_service";
  if(strlen(trim($_POST['addurl']))) {
    if(strlen(trim($_POST['adddelay']))) {
      Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/ledcustom/service?action=add&url=".urlencode($_POST['addurl'])."&service=".$_POST['addservice']."&interval=".$_POST['adddelay']."&".$ojnAPI->getToken()));
    } else {
      Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/ledcustom/service?action=add&url=".urlencode($_POST['addurl'])."&service=".$_POST['addservice']."&".$ojnAPI->getToken()));
    }
  } else {
    Message::AddError(__tr("URL can't be empty"));
  }
  $reload = true;
}
else if(isset($_GET['rp'])) {
  $_SESSION['subtab'] = "ledcustom_service";
  Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/ledcustom/service?action=remove&service=".urlencode($_GET['rp'])."&".$ojnAPI->getToken()));
  $reload = true;
}
if(isset($_POST['addchor'])) {
  $_SESSION['subtab'] = "ledcustom_chor";
  if(strlen(trim($_POST['addchor']))) {
    Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/ledcustom/chor?action=add&leds=".urlencode($_POST['addchor'])."&service=".$_POST['addservice']."&tempo=".$_POST['adddelay']."&value=".$_POST['addvalue']."&".$ojnAPI->getToken()));
  } else {
    Message::AddError(__tr("Chor can't be empty"));
  }
  $reload = true;
}
else if(isset($_GET['rc'])) {
  $_SESSION['subtab'] = "ledcustom_chor";
  Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/ledcustom/chor?action=del&service=".urlencode($_GET['rc'])."&value=".urlencode($_GET['rv'])."&".$ojnAPI->getToken()));
  $reload = true;
}
$chors = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/ledcustom/chor?action=list&".$ojnAPI->getToken());
$services = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/ledcustom/service?action=list&".$ojnAPI->getToken());

if(!isset($_SESSION['subtab']) || !preg_match("|^ledcustom_|", $_SESSION['subtab'])) {
  $_SESSION['subtab'] = "ledcustom_service";
}
if($reload)
{
  header("Location: bunny_plugin.php?p=ledcustom");
  exit();
}
require_once dirname(realpath(__FILE__)).'/ledcustom.inc.php';
getLedScript();
?>
<ul class="nav nav-tabs">
  <li class="nav-item">
    <a class="nav-link<?php echo $_SESSION['subtab'] == 'ledcustom_service' ? ' active' : '' ?>" href="#url" data-toggle="tab"><?php echo __tr('URL List') ?></a>
  </li>
  <li class="nav-item">
    <a class="nav-link<?php echo $_SESSION['subtab'] == 'ledcustom_chor' ? ' active' : '' ?>" href="#chor" data-toggle="tab"><?php echo __tr('Choregraphies') ?></a>
  </li>
  <?php if(!empty($Infos['isAdmin'])): ?>
  <li class="nav-item">
    <a class="nav-link<?php echo $_SESSION['subtab'] == 'ledcustom_chors' ? ' active' : '' ?> bg-danger text-light" href="#chors" data-toggle="tab"><?php echo __tr('All choregraphies') ?></a>
  </li>
  <?php endif; ?>
</ul>

<div class="tab-content pt-2">
  <div class="tab-pane<?php echo $_SESSION['subtab'] == 'ledcustom_service' ? ' active' : '' ?>" id="url">
    <form method="post">
      <div class="form-group row">
        <label class="col-sm-3 col-form-label" for="addurl"><?php echo __tr("Add an url") ?></label>
        <div class="col-sm-6">
          <input type="text" name="addurl" class="form-control"/>
        </div>
      </div>
      <div class="form-group row">
        <label class="col-sm-3 col-form-label" for="addservice"><?php echo __tr("Service") ?></label>
        <div class="col-sm-3">
          <input type="text" name="addservice" class="form-control"/>
        </div>
      </div>
      <div class="form-group row">
        <label class="col-sm-3 col-form-label" for="adddelay"><?php echo __tr("Delay") ?> (sec)</label></label>
        <div class="col-sm-2">
          <input type="text" name="adddelay" class="form-control"/>
        </div>
      </div>
      <div class="form-group row">
        <div class="col-sm-2 offset-sm-3">
          <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
        </div>
      </div>
    </form>
  </div>

  <div class="tab-pane<?php echo $_SESSION['subtab'] == 'ledcustom_chor' ? ' active' : '' ?>" id="chor">
    <form method="post">
      <div class="form-group row">
        <label class="col-sm-2 col-form-label" for="addservice"><?php echo __tr("Service") ?></label>
        <div class="col-sm-3">
          <input type="text" name="addservice" class="form-control"/>
        </div>
      </div>
      <div class="form-group row">
        <label class="col-sm-2 col-form-label" for="addvalue"><?php echo __tr("Value") ?></label>
        <div class="col-sm-3">
          <input type="text" name="addvalue" class="form-control"/>
        </div>
      </div>
      <div class="form-group row">
        <label class="col-sm-2 col-form-label" for="addchor"><?php echo __tr("Leds") ?></label>
        <div class="col-sm-6">
          <input type="text" name="addchor" id="addchor" class="form-control" onkeyup="updateChor()"/>
        </div>
        <div class="col-sm-3">
          <canvas height="30" width="100" id="canvas_temp" style="float: right"></canvas>
        </div>
      </div>
      <div class="form-group row">
        <label class="col-sm-2 col-form-label" for="adddelay"><?php echo __tr("Delay") ?> (sec)</label>
        <div class="col-sm-2">
          <input type="text" name="adddelay" id="adddelay" class="form-control" onkeyup="updateChor()"/>
        </div>
      </div>
      <div class="form-group row">
        <div class="col-sm-2 offset-sm-2">
          <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
        </div>
      </div>
    </form>

    <script type="text/javascript">
      var chor_temp = new Leds('temp', $('#adddelay').val(), $('#addchor').val().split(','));
      chor_temp.play();
      function updateChor()
      {
        chor_temp.update($('#adddelay').val(), $('#addchor').val().split(','));
      }
    </script>
  </div>

  <div class="tab-pane<?php echo $_SESSION['subtab'] == 'ledcustom_chors' ? ' active' : '' ?>" id="chors">
    <div id="chors"></div>
    <div class="form-group">
      <button class="btn" onclick="requestChors()"><?php echo __tr('Request') ?></button>
    <script type="text/javascript">
      function requestChors()
      {
        $.get('<?php echo ROOT_WWW_EXTAPI; ?>bunny/<?php echo $_SESSION['bunny']; ?>/ledcustom/chor?action=request&<?php echo $ojnAPI->getToken(); ?>');
        setTimeout('fetchChors()', 2000);
      };
      function fetchChors()
      {
        $.get('/bunny/plugins/ledcustom.ajax.php?sn=<?php echo $_SESSION['bunny']; ?>',
        function(data)
        {
          $('#chors').html(data);
        });
      };
      </script>
    </div>
  </div>

<?php if(!empty($services)): ?>
<h5><?php echo __tr('URL List') ?></h5>
<table class="table table-bordered table-striped">
  <tr>
    <th><?php echo __tr('Service') ?></th>
    <th><?php echo __tr('URL') ?></th>
    <th><?php echo __tr('Delay') ?></th>
    <th><?php echo __tr('Actions') ?></th>
  </tr>
  <?php foreach($services as $k => $item):
    list($delay, $url) = preg_split('/\|SEPARATOR\|/', urldecode($item));
  ?>
  <tr>
    <td><?php echo $k ?></td>
    <td><?php echo $url ?></td>
    <td><?php echo sectotime($delay, true) ?></td>
    <td><a class="btn btn-sm btn-danger" href="bunny_plugin.php?p=ledcustom&rp=<?php echo $k ?>"><?php echo __tr("Remove") ?></a></td>
  </tr>
  <?php endforeach; ?>
</table>
<?php endif;
if(!empty($chors)): ?>
<h5><?php echo __tr('Choregraphies List') ?></h5>
<table class="table table-bordered table-striped">
  <tr>
    <th class="span1"><?php echo __tr('Service') ?></th>
    <th class="span1"><?php echo __tr('Value') ?></th>
    <th class="span1"><?php echo __tr('Delay') ?></th>
    <th class="span8" colspan="2"><?php echo __tr('LEDs') ?></th>
    <th ><?php echo __tr('Actions') ?></th>
  </tr>
  <?php
  foreach($chors as $k => $item):
    $choregraphies = preg_split('/\|/', urldecode($item));
    foreach($choregraphies as $id => $chor):
      list($delay, $leds) = preg_split('/;/', $chor);
  ?>
  <tr>
    <td><?php echo $k ?></td>
    <td><?php echo $id ?></td>
    <td><?php echo $delay ?></td>
    <td><?php echo $leds ?></td>
    <td><?php getLed($leds, $delay, $id, $k) ?></td>
    <td><a class="btn btn-sm btn-danger" href="bunny_plugin.php?p=ledcustom&rc=<?php echo $k ?>&rv=<?php echo $id ?>"><?php echo __tr("Remove") ?></a></td>
  </tr>
  <?php
    endforeach;
  endforeach;
  ?>
</table>
<?php endif; ?>
