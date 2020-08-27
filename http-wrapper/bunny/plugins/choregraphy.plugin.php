<?php
$reload = false;

if(isset($_POST['chor']) && isset($_POST['sender'])) {
  $_SESSION['subtab'] = "choregraphy_setup";
  $sender = $_POST['sender'] == "apijsp" ? 'api.jsp' : $_POST['sender'];
  Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/choregraphy/config?action=add&chor=".$_POST['chor']."&sender=".urlencode($sender)."&".$ojnAPI->getToken()));
  header("Location: bunny_plugin.php?p=choregraphy");
}
if(isset($_GET['rmchor'])) {
  $_SESSION['subtab'] = "choregraphy_setup";
  $sender = $_GET['rmchor'] == "apijsp" ? 'api.jsp' : $_GET['rmchor'];
  Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/choregraphy/config?action=del&sender=".urlencode($sender)."&".$ojnAPI->getToken()));
  $reload = true;
}

if($reload) {
  header("Location: bunny_plugin.php?p=choregraphy");
  exit;
}
if(!isset($_SESSION['subtab']) || !preg_match("|^choregraphy_|", $_SESSION['subtab'])) {
  $_SESSION['subtab'] = "choregraphy_setup";
}
$Assoc = $ojnAPI->getApiMapped("bunny/".$_SESSION['bunny']."/choregraphy/config?action=list&".$ojnAPI->getToken());

$plugins = $ojnAPI->getListOfPlugins(false, $ojnTemplate->getLanguage());
if(is_array($plugins)) {
  asort($plugins);
}
$plugins = array_merge(array('apijsp' => __tr('API Call')), $plugins);
$chors = array('random' => __tr('Random'));
for($i=0; $i<=7; $i++) {
  $chors[$i] = $i+1;
}
$bunnyPlugins = $ojnAPI->getListOfBunnyEnabledPlugins(false);
?>
<form method="post">
  <div class="form-group row">
    <label class="col-sm-2 col-form-label" for="chor"><?php echo __tr("Add a choregraphy") ?></label>
    <div class="col-sm-2 input-group">
      <select name="chor" class="form-control">
        <?php foreach($chors as $k => $v): ?>
        <option value="<?php echo $k; ?>"><?php echo $v; ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <label class="col-sm-1 col-form-label" for="sender"><?php echo __tr("on event") ?></label>
    <div class="col-sm-4 input-group">
      <select name="sender" class="form-control">
        <?php foreach($plugins as $k=>$p):
            if($k == 'choregraphy') continue;
        ?>
        <option value="<?php echo $k; ?>"><?php echo $p; ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-sm-1">
      <button class="btn btn-primary" type="submit"><?php echo __tr("Add") ?></button>
    </div>
  </div>
</form>

<?php if(!empty($Assoc)): ?>
<h5><?php echo __tr('Choregraphies') ?></h5>
<table class="table table-bordered table-striped">
  <tr>
    <th class="col-sm-1"><?php echo __tr('Choregraphy') ?></th>
    <th><?php echo __tr('Plugin'); ?></th>
    <th class="col-sm-1"><?php echo __tr('Actions') ?></th>
  </tr>
  <?php foreach($Assoc as $k=>$v):
    if($k == 'api.jsp') $k = 'apijsp';
  ?>
  <tr>
    <td><?php echo $chors[$v] ?></td>
    <td><?php echo $plugins[$k]; ?></td>
    <td><a href="bunny_plugin.php?p=choregraphy&rmchor=<?php echo urlencode($k) ?>" class="btn btn-danger btn-sm"><i class="icon-trash icon-large"></i> <?php echo __tr('Remove') ?></a></td>
  </tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>
