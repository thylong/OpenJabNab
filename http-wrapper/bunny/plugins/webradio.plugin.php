<?php

if(!isset($_SESSION['bunny']))
{
  header("Location: /bunny/index.php");
  exit();
}
$reload = false;
$Ztamps = $ojnAPI->GetListofZtamps(false);
$days = array(
0 => __tr('Every day'),
-1 => __tr('During the week'),
-2 => __tr('During the week-end'),
1 => __tr("Monday"),
2 => __tr("Tuesday"),
3 => __tr("Wednesday"),
4 => __tr("Thursday"),
5 => __tr("Friday"),
6 => __tr("Saturday"),
7 => __tr("Sunday")
);
if(!empty($_POST['scheduleT']) && !empty($_POST['scheduleP'])) {
  $_SESSION['subtab'] = "webradio_schedule";
  $time = $_POST['scheduleT'];
  $name = urlencode(preg_replace('/OJN_/', '', $_POST['scheduleP']));
  $d = $_POST['scheduleD'];
  if($_POST['scheduleD'] >= 0)
  {
    Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/webradio/schedule?action=add&day=".$d."&time=".$time."&name=".$name."&".$ojnAPI->getToken()));
  }
  else
  {
    if($_POST['scheduleD'] == -1)
    {
      $id = 0;
      for($d = 1; $d<=5; $d++)
      {
        Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/webradio/schedule?action=add&day=".$d."&time=".$time."&name=".$name."&".$ojnAPI->getToken()), $id);
      }
    }
    if($_POST['scheduleD'] == -2)
    {
      $id = 0;
      for($d = 6; $d<=7; $d++)
      {
        Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/webradio/schedule?action=add&day=".$d."&time=".$time."&name=".$name."&".$ojnAPI->getToken()), $id);
      }
    }
  }
  $reload = true;
}
if(isset($_GET['rtag'])) {
  $_SESSION['subtab'] = "webradio_rfid";
  Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/webradio/rfid?action=del&tag=".$_GET['rtag']."&".$ojnAPI->getToken()));
  $reload = true;
}
if(isset($_POST['addname']) && isset($_POST['addurl'])) {
  $_SESSION['subtab'] = "webradio_preset";


  if($_POST['addname'] == "")
  {
    Message::AddError(__tr('You must choose a name'));
  }
  else if($_POST['addurl'] == "")
  {
    Message::AddError(__tr('You must choose a preset'));
  }
  else
  {
    if(preg_match("/\.m3u$/", $_POST['addurl']))
    {
      $playlist = file_get_contents($_POST['addurl']);
      if($list = preg_split("/\n/", $playlist, -1, PREG_SPLIT_NO_EMPTY))
      {
        $id = Message::AddWarning(__tr('You enter the url of a playlist, here is the content. Please choose the link you want to add :'));
        Message::AddWarning('<ul>', $id);
        foreach($list as $radio)
        {
          $form = '<form style="margin: 0px; display: inline-block" method="post"><input type="hidden" name="addurl" value="'.$radio.'"><input type="hidden" name="addname" value="'.$_POST['addname'].'"><input type="submit" class="btn btn-mini btn-warning" value="'.__tr('Use this radio').'"></form>';
          Message::AddWarning('<li>'.$radio.'&nbsp; &nbsp; &nbsp;'.$form.'</li>', $id);
        }
        Message::AddWarning('</ul>', $id);
      }
      else
      {
        Message::AddError(__tr('You enter the url of a playlist, but I can\'t find any available radio'));
      }
    }
    else if(preg_match("/\.pls$/", $_POST['addurl']))
    {
      $playlist = file_get_contents($_POST['addurl']);
      if(preg_match_all("/File\d+=(.*)\n/isU", $playlist, $list, PREG_SET_ORDER))
      {
        $id = Message::AddWarning(__tr('You enter the url of a playlist, here is the content. Please choose the link you want to add :'));
        Message::AddWarning('<ul>', $id);
        foreach($list as $radio)
        {
          $radio = $radio[1];
          $form = '<form style="margin: 0px; display: inline-block" method="post"><input type="hidden" name="addurl" value="'.$radio.'"><input type="hidden" name="addname" value="'.$_POST['addname'].'"><input type="submit" class="btn btn-mini btn-warning" value="'.__tr('Use this radio').'"></form>';
          Message::AddWarning('<li>'.$radio.'&nbsp; &nbsp; &nbsp;'.$form.'</li>', $id);
        }
        Message::AddWarning('</ul>', $id);
      }
      else
      {
        Message::AddError(__tr('You enter the url of a playlist, but I can\'t find any available radio'));
      }
    }
    else
    {
      Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/webradio/preset?action=add&name=".urlencode($_POST['addname'])."&url=".urlencode($_POST['addurl'])."&".$ojnAPI->getToken()));
    }
  }
  $reload = true;
}
if(isset($_POST['atag']) && isset($_POST['aurl'])) {
  $_SESSION['subtab'] = "webradio_rfid";
  if($_POST['atag'] == "")
  {
    Message::AddError(__tr('You must choose a Ztamp'));
  }
  else if($_POST['aurl'] == "")
  {
    Message::AddError(__tr('You must choose a preset'));
  }
  else
  {
    Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/webradio/rfid?action=add&tag=".$_POST['atag']."&preset=".urlencode(preg_replace('/OJN_/', '', $_POST['aurl']))."&".$ojnAPI->getToken()));
  }
  $reload = true;
}
if(isset($_GET['rp'])) {
  $_SESSION['subtab'] = "webradio_preset";
  Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/webradio/preset?action=del&name=".urlencode($_GET['rp'])."&".$ojnAPI->getToken()));
  $reload = true;
}
else if(isset($_GET['d'])) {
  $_SESSION['subtab'] = "webradio_preset";
  Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/webradio/preset?action=setdefault&name=".urlencode(preg_replace('/OJN_/', '', $_GET['d']))."&".$ojnAPI->getToken()));
  $reload = true;
}
else if(isset($_GET['play'])) {
  $_SESSION['subtab'] = "webradio_preset";
  Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/webradio/preset?action=play&name=".urlencode(preg_replace('/OJN_/', '', $_GET['play']))."&".$ojnAPI->getToken()));
  $reload = true;
}
else if(isset($_GET['rw'])) {
  $_SESSION['subtab'] = "webradio_schedule";
  Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/webradio/schedule?action=del&day=".$_GET['rwd']."&time=".$_GET['rw']."&".$ojnAPI->getToken()));
  $reload = true;
}
$default = $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/webradio/preset?action=getdefault&".$ojnAPI->getToken());
$default = isset($default['value']) ? (string)($default['value']) : '';
$pList = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/webradio/preset?action=list&".$ojnAPI->getToken());
$wList = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/webradio/schedule?action=list&".$ojnAPI->getToken());
$Assoc = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/webradio/rfid?action=list&".$ojnAPI->getToken());



if(!isset($_SESSION['subtab']) || !preg_match("|^webradio_|", $_SESSION['subtab'])) {
  $_SESSION['subtab'] = "webradio_preset";
}
if($reload)
{
  header("Location: bunny_plugin.php?p=webradio");
  exit();
}

?>
<ul class="nav nav-tabs">
  <li class="nav-item">
    <a class="nav-link <?php echo $_SESSION['subtab'] == 'webradio_preset' ? ' active' : '' ?>" href="#preset" data-toggle="tab"><?php echo __tr('Presets') ?></a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?php echo $_SESSION['subtab'] == 'webradio_schedule' ? ' active' : '' ?>" href="#schedule" data-toggle="tab"><?php echo __tr('Schedules') ?></a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?php echo $_SESSION['subtab'] == 'webradio_rfid' ? ' active' : '' ?>" href="#rfid" data-toggle="tab"><?php echo __tr('RFID') ?></a>
  </li>
</ul>

<div class="tab-content pt-2">
  <div class="tab-pane<?php echo $_SESSION['subtab'] == 'webradio_preset' ? ' active' : '' ?>" id="preset">
    <form method="post">
      <div class="form-group row">
        <label class="col-sm-1 col-form-label" for="addname"><?php echo __tr("Name") ?></label>
        <div class="col-sm-2 input-group">
          <input type="text" name="addname" class="form-control"/>
        </div>
      </div>
      <div class="form-group row">
        <label class="col-sm-1 col-form-label" for="addurl"><?php echo __tr("Url") ?></label>
        <div class="col-sm-6 input-group">
          <input type="text" name="addurl" class="form-control"/>
        </div>
      </div>
      <div class="form-group row">
        <div class="col-sm-1 offset-sm-1">
          <button class="btn btn-primary" type="submit"><?php echo __tr("Add a preset") ?></button>
        </div>
      </div>
    </form>

    <?php if(!empty($pList)): ?>
    <h5><?php echo __tr('URL List') ?></h5>
    <table class="table table-bordered table-striped span11">
      <tr>
        <th class="col-sm-1"><?php echo __tr('Name') ?></th>
        <th><?php echo __tr('URL') ?></th>
        <th class="col-sm-4"><?php echo __tr('Actions') ?></th>
      </tr>
      <?php foreach($pList as $key => $item): ?>
      <tr>
        <td><?php echo preg_replace('/OJN_/', '', $key) ?></td>
        <td><?php echo urldecode($item) ?></td>
        <td>
          <a class="btn btn-sm btn-success" href="bunny_plugin.php?p=webradio&play=<?php echo $key ?>"><i class="icon-play icon-large"></i> <?php echo __tr("Play") ?></a>
          <?php if(!preg_match('/^OJN_/', $key)): ?>
          <a class="btn btn-sm btn-danger" href="bunny_plugin.php?p=webradio&rp=<?php echo $key ?>"><i class="icon-trash icon-large"></i> <?php echo __tr("Remove") ?></a>
          <?php endif; ?>
          <?php if($default != $key): ?>
          <a class="btn btn-sm btn-primary" href="bunny_plugin.php?p=webradio&d=<?php echo $key ?>"><?php echo __tr("Set as default") ?></a>
          <?php else: ?>
          <span class="btn btn-sm btn-secondary"><?php echo __tr("Default webradio") ?></span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>

  <div class="tab-pane<?php echo $_SESSION['subtab'] == 'webradio_schedule' ? ' active' : '' ?>" id="schedule">
    <form method="post">
      <div class="form-group row">
        <label class="col-sm-3 col-form-label" for="scheduleT"><?php echo __tr("Add a schedule at (hh:mm)") ?></label>
        <div class="col-sm-2 input-group">
          <div class="input-group clockpicker" data-autoclose="true">
            <input type="text" name="scheduleT" class="form-control" value="<?php echo date('H:i'); ?>">
            <div class="input-group-text input-group-addon">
              <i class="icon-time"></i>
            </div>
          </div>
          <script type="text/javascript">
            $('.clockpicker').clockpicker({'default': 'now'});
          </script>
        </div>
      </div>
      <div class="form-group row">
        <label class="col-sm-3 col-form-label" for="scheduleD"><?php echo __tr("Day") ?></label>
        <div class="col-sm-2 input-group">
          <select name="scheduleD" class="form-control">
            <?php foreach($days as $d => $day): ?>
            <option value="<?php echo $d ?>"><?php echo $day ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-group row">
        <label class="col-sm-3 col-form-label" for="scheduleP"><?php echo __tr("Preset") ?></label>
        <div class="col-sm-2 input-group">
          <select name="scheduleP" class="form-control">
            <option value=""></option>
            <?php foreach($pList as $key => $item): ?>
              <option value="<?php echo $key ?>"><?php echo preg_replace("/OJN_/", "", $key); ?></option>
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
        <th class="col-sm-2"><?php echo __tr('Day') ?></th>
        <th class="col-sm-1"><?php echo __tr('Time') ?></th>
        <th><?php echo __tr('Name') ?></th>
        <th class="col-sm-1"><?php echo __tr('Actions') ?></th>
      </tr>
      <?php foreach($wList as $when => $name):
          list($day, $time) = preg_split("/\|/", $when);
      ?>
      <tr>
        <td><?php echo $days[$day] ?></td>
        <td><?php echo $time ?></td>
        <td><?php echo preg_replace('/OJN_/', '', $name) ?></td>
        <td><a href="bunny_plugin.php?p=webradio&rwd=<?php echo $day ?>&rw=<?php echo $time ?>" class="btn btn-sm btn-danger"><i class="icon-trash icon-large"></i> <?php echo __tr('Remove') ?></a></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>

  <div class="tab-pane<?php echo $_SESSION['subtab'] == 'webradio_rfid' ? ' active' : '' ?>" id="rfid">
    <form method="post">
      <div class="form-group row">
        <label class="col-sm-1 col-form-label" for="aurl"><?php echo __tr("Launch") ?></label>
        <div class="col-sm-2 input-group">
          <select name="aurl" class="form-control">
            <option value=""></option>
            <?php foreach($pList as $key => $item): ?>
            <option value="<?php echo $key ?>"><?php echo preg_match("/RANDOM/", $key) ? $item : preg_replace('/OJN_/', '', $key) ?></option>
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
        <th><?php echo __tr('Url') ?></th>
        <th class="col-sm-4"><?php echo __tr('Ztamp') ?></th>
        <th class="col-sm-1"><?php echo __tr('Actions') ?></th>
      </tr>
      <?php foreach($Assoc as $k=>$v): ?>
      <tr>
        <td><?php echo preg_replace('/OJN_/', '', $v); ?></td>
        <td><?php echo $Ztamps[$k] . " - " . $k; ?></td>
        <td><a href="bunny_plugin.php?p=webradio&rtag=<?php echo $k ?>" class="btn btn-danger btn-sm"><i class="icon-trash icon-large"></i> <?php echo __tr('Remove') ?></a></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>
</div>
