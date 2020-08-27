<?php
$Ztamps = $ojnAPI->GetListofZtamps(false);
$reload = false;
if(isset($_POST['addmac']))
{
  $_SESSION['subtab'] = "nab2nab_receiver";
  Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/nab2nab/addfriend?name=".urlencode($_POST['addname'])."&sn=".urlencode($_POST['addmac'])."&".$ojnAPI->getToken()));
  $reload = true;
}
if(isset($_POST['afriend']) && isset($_POST['atag']))
{
  $_SESSION['subtab'] = "nab2nab_rfid";
  Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/nab2nab/setreceiverontag?tag=".$_POST['atag']."&sn=".urlencode($_POST['afriend'])."&".$ojnAPI->getToken()));
  $reload = true;
}
if(isset($_GET['rtag']))
{
  $_SESSION['subtab'] = "nab2nab_rfid";
  Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/nab2nab/removereceiverontag?tag=".$_GET['rtag']."&".$ojnAPI->getToken()));
  $reload = true;
}
else if(!empty($_GET['rm']))
{
  $_SESSION['subtab'] = "nab2nab_receiver";
  Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/nab2nab/removefriend?sn=".urlencode($_GET['rm'])."&".$ojnAPI->getToken()));
  $reload = true;
}
$pList = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/nab2nab/getfriends?".$ojnAPI->getToken());
$Assoc = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/nab2nab/getreceiversontags?".$ojnAPI->getToken());

if(!isset($_SESSION['subtab']) || !preg_match("|^nab2nab_|", $_SESSION['subtab'])) {
  $_SESSION['subtab'] = "nab2nab_receiver";
}
if($reload)
{
  header("Location: bunny_plugin.php?p=nab2nab");
  exit();
}
?>

<div class="alert alert-info p-4">
  <ul class="m-0">
    <li><?php echo __tr("To send a message to another bunny, just use your Ztamp, and then you have 1 minute to record your message") ?>.</li>
    <li><?php echo __tr("To record a message, long click on the button, wait for the red nose, and keep pushing the button while speaking. Release the button to send the message") ?>.</li>
  </ul>
</div>

<ul class="nav nav-tabs">
  <li class="nav-item">
    <a class="nav-link <?php echo $_SESSION['subtab'] == 'nab2nab_receiver' ? ' active' : '' ?>" href="#receiver" data-toggle="tab"><?php echo __tr('Friends List') ?></a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?php echo $_SESSION['subtab'] == 'nab2nab_rfid' ? ' active' : '' ?>" href="#rfid" data-toggle="tab"><?php echo __tr('RFID') ?></a>
  </li>
</ul>

<div class="tab-content pt-3">
  <div class="tab-pane<?php echo $_SESSION['subtab'] == 'nab2nab_receiver' ? ' active' : '' ?>" id="receiver">
    <form method="post">
      <div class="form-group row">
        <label class="col-sm-2 col-form-label" for="addmac"><?php echo __tr("Enter MAC address") ?></label>
        <div class="col-sm-2 input-group">
          <input type="text" name="addmac" class="form-control" placeholder="00xxxxxxxxxx" />
        </div>
      </div>
      <div class="form-group row">
        <label class="col-sm-2 col-form-label" for="addname"><?php echo __tr("Enter bunny name") ?></label>
        <div class="col-sm-2 input-group">
          <input type="text" name="addname" class="form-control" />
        </div>
      </div>
      <div class="form-group row">
        <div class="col-sm-1 offset-sm-2">
          <button class="btn btn-primary" type="submit"><?php echo __tr("Add this bunny as friend") ?></button>
        </div>
      </div>
    </form>

    <?php if(!empty($pList)):?>
    <h5><?php echo __tr('Bunnies') ?></h5>
    <table class="table table-bordered table-striped">
      <tr>
        <th><?php echo __tr('Name') ?></th>
        <th><?php echo __tr('MAC') ?></th>
        <th class="col-sm-1"><?php echo __tr('Actions') ?></th>
      </tr>
      <?php	foreach($pList as $mac => $name): ?>
      <tr>
        <td><?php echo urldecode($name) ?></td>
        <td><?php echo urldecode($mac) ?></td>
        <td><a class="btn btn-sm btn-danger" href="bunny_plugin.php?p=nab2nab&rm=<?php echo urlencode($mac) ?>"><?php echo __tr("Remove") ?></a></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>

  <div class="tab-pane<?php echo $_SESSION['subtab'] == 'nab2nab_rfid' ? ' active' : '' ?>" id="rfid">
    <form method="post">
      <div class="form-group row">
        <label class="col-sm-2 col-form-label" for="afriend"><?php echo __tr("Send message to") ?></label>
        <div class="col-sm-2 input-group">
          <select name="afriend" class="form-control">
            <option value=""></option>
            <?php  if(!empty($pList))
            foreach($pList as $mac => $item): ?>
              <option value="<?php echo urldecode($mac) ?>"><?php echo urldecode($item); ?></option>
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
        <th class="col-sm-2"><?php echo __tr('Friend') ?></th>
        <th class="col-sm-4"><?php echo __tr('Ztamp') ?></th>
        <th class="col-sm-1"><?php echo __tr('Actions') ?></th>
      </tr>
      <?php foreach($Assoc as $k=>$v): ?>
      <tr>
        <td><?php echo $pList[$v]; ?> - <?php echo $v; ?></td>
        <td><?php echo $Ztamps[$k] . " - " . $k; ?></td>
        <td><a href="bunny_plugin.php?p=nab2nab&rtag=<?php echo $k ?>" class="btn btn-danger btn-sm"><i class="icon-trash icon-large"></i> <?php echo __tr('Remove') ?></a></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>
</div>
