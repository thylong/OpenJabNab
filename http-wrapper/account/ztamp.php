<?php
require_once '../include/common.php';
$ojnTemplate->setTitle(__tr('Ztamps'));
if(!isset($_SESSION['token']))
	header('Location: /index.php');

if(isset($_GET['z']) && (empty($_GET['z']) || $_GET['z'] == 'clear')) {
	unset($_SESSION['ztamp']);
	unset($_SESSION['ztamp_name']);
	header("Location: /account/ztamp.php");
	exit();
}elseif(!empty($_GET['z'])) {
		$_SESSION['ztamp'] = $_GET['z'];
		$ztamps = $ojnAPI->getListOfZtamps(false);
		$_SESSION['ztamp_name'] = !empty($ztamps[$_GET['z']]) ? $ztamps[$_GET['z']] : '';
		header("Location: ztamp.php");
	exit();
}elseif((!empty($_GET['plug']) && !empty($_GET['stat'])) || (!empty($_POST['plug']) && !empty($_POST['stat']))) {
	$a = !empty($_GET['stat']) ? $_GET : $_POST;
	$function = $a['stat'] == 'register' ? 'register' : 'unregister';
	$_SESSION['message'] = $ojnAPI->getApiString('ztamp/'.$_SESSION['ztamp'].'/'.$function.'Plugin?name='.$a['plug'].'&'.$ojnAPI->getToken());
	header('Location: /account/ztamp.php');
	exit();
} elseif(isset($_GET['resetown'])) {
	$_SESSION['message'] = $ojnAPI->getApiString("ztamp/".$_SESSION['ztamp']."/resetOwner?".$ojnAPI->getToken());
	header('Location: /account/ztamp.php');
	exit();
}
elseif(isset($_GET['delete'])) {
	Message::AddFromApi($ojnAPI->getApiString("ztamps/removeZtamp?serial=".$_SESSION['ztamp']."&".$ojnAPI->getToken()));
	header('Location: /account/ztamp.php');
	exit();
}
else if(!empty($_POST['ztamp_name'])) {
	$_SESSION['message'] = $ojnAPI->getApiString("ztamp/".$_SESSION['ztamp']."/setZtampName?name=".urlencode($_POST['ztamp_name'])."&".$ojnAPI->getToken());
	$_SESSION['ztamp_name'] = $_POST['ztamp_name'];
	header('Location: /account/ztamp.php');
	exit();
}
require_once('../include/message.php');

$title = empty($_SESSION['ztamp']) ? __tr("Choose your Ztamp") : __tr("Setup of ztamp '%1'", !empty($_SESSION['ztamp_name']) ? $_SESSION['ztamp_name'] : $_SESSION['ztamp']);
?>
<div class="card ">
  <h5 class="card-header">
    <i class="icon-th-large"></i> <?php echo $title; ?>
  </h5>
  <div class="card-body">
	  <?php if(empty($_SESSION['ztamp'])): ?>
      <div class="row">
        <div class="col-md-12 object-list object-6">
          <?php
          $ztamps = $ojnAPI->getListOfZtamps(false);
          if(!empty($ztamps))
            foreach($ztamps as $ztamp => $nom):
          ?>
          <div class="obj-container">
            <div class="object">
              <div class="obj-header">
                <div class="obj-name"><?php echo $nom; ?></div>
                <div class="obj-info"><?php echo $ztamp; ?></div>
              </div>
              <div class="obj-actions">
                <a class="btn" href="/account/ztamp.php?z=<?php echo $ztamp; ?>"><?php echo __tr("Setup") ?></a>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <hr />
      <?php
	    $bunnies = $ojnAPI->getListOfBunnies(false);
      if(!empty($bunnies))
	    foreach($bunnies as $mac => $nom):
		    $lastZ = $ojnAPI->getApiString("bunny/$mac/rfid/getLastBunnyTag?".$ojnAPI->getToken());
		    if(!empty($lastZ['value'])):
          ?><?php echo __tr('Last Ztamp used by '); ?>
          <a href="/bunny/?b=<?php echo $mac; ?>"><?php echo $nom; ?></a>
          (<?php echo $mac; ?>):
          <?php echo (!empty($ztamps) && isset($ztamps[$lastZ['value']])) ? $ztamps[$lastZ['value']]: ''; ?>
          (<a href="/account/ztamp.php?z=<?php echo $lastZ['value']; ?>"><?php echo $lastZ['value']; ?></a>)
          <br />
      <?php endif;
      endforeach; ?>
    <?php else: ?>
    <?php
    $ownersList = array();
    $q = $ojnAPI->getAPIList('ztamp/'.$_SESSION['ztamp'].'/owner?action=list&'.$ojnAPI->getToken());
    foreach($q as $it)
      if(!empty($it))
        $ownersList[] = (string)($it);
    $assocList = array();
    $assocList = $ojnAPI->getAPIMapped('ztamp/'.$_SESSION['ztamp'].'/plugin?action=association&'.$ojnAPI->getToken());

    $reload=true;
    if(!empty($_GET['add_owner']))
      Message::AddFromApi($ojnAPI->getApiString('ztamp/'.$_SESSION['ztamp'].'/owner?action=add&login='.$_GET['add_owner'].'&'.$ojnAPI->getToken()));
    else if(!empty($_GET['rm_owner']))
      Message::AddFromApi($ojnAPI->getApiString('ztamp/'.$_SESSION['ztamp'].'/owner?action=del&login='.$_GET['rm_owner'].'&'.$ojnAPI->getToken()));
    else if(isset($_GET['rm_assoc']))
      Message::AddFromApi($ojnAPI->getApiString('ztamp/'.$_SESSION['ztamp'].'/plugin?action=deassociate&bunny='.$_GET['b'].'&'.$ojnAPI->getToken()));
    else
      $reload=false;

    if($reload)
    {
      header('Location: /account/ztamp.php');    
      exit;
    }
    ?>
    <form method="post">
      <fieldset>
        <legend>Configuration</legend>
        <div class="form-group row">
          <label class="col-sm-1 col-form-label" for="ztamp_name"><?php echo __tr('Name') ?></label>
          <div class="col-sm-3">
            <input type="text" class="form-control" name="ztamp_name" value="<?php echo $_SESSION['ztamp_name']; ?>">
          </div>
        </div>
        <div class="form-group row">
          <div class="col-sm-12 text-left">
            <button type="submit" class="btn btn-primary"><?php echo __tr('Save') ?></button>
            <button class="btn btn-warning"><?php echo __tr('Cancel') ?></button>
          </div>
        </div>
      </fieldset>
    </form>
    <?php if($Infos['isAdmin']): ?>
    <div class="card">
      <h6 class="card-header bg-danger text-light">Administration</h6>
      <div class="card-body">
        <form method="get">
          <div class="form-group row">
            <label class="col-sm-2 col-form-label">Ztamp ID</label>
            <div class="col-sm-7">
              <?php echo $_SESSION['ztamp']; ?>
            </div>
          </div>
          <div class="form-group row">
            <label class="col-sm-2 col-form-label">Owner(s):</label>
            <div class="col-sm-7">
            <ul>
              <?php foreach($ownersList as $login): ?>
              <li>
                <?php echo $login; ?>
                <a class="btn btn-sm btn-danger m-1" href="?rm_owner=<?php echo $login; ?>">Supprimer</a>
              </li>
              <?php endforeach; ?>
            </ul>
            </div>
          </div>
          <div class="form-group row">
            <label class="col-sm-2 col-form-label">Association(s):</label>
            <div class="col-sm-7">
            <ul>
              <?php foreach($assocList as $b => $p): ?>
              <li>
                <?php echo $b; ?> => <?php echo $p; ?>
                <a class="btn btn-sm btn-danger m-1" href="?rm_assoc&b=<?php echo $b; ?>">Supprimer</a>
              </li>
              <?php endforeach; ?>
            </ul>
            </div>
          </div>
          <div class="form-group row">
            <label class="col-sm-2 col-form-label" for="add_owner"><?php echo __tr('Add owner') ?></label>
            <div class="col-sm-3">
              <input type="text" class="form-control" name="add_owner" value="" placeholder="<?php echo __tr('Login'); ?>">
            </div>
          </div>
          <div class="form-group row">
            <div class="col-sm-12 text-left">
              <button type="submit" class="btn btn-primary"><?php echo __tr('Save') ?></button>
              <button class="btn btn-warning"><?php echo __tr('Cancel') ?></button>
            </div>
          </div>
        </form>
        <a class="btn btn-warning" href="?resetown">Liberer le ztamp de ce compte</a>
        <a class="btn btn-danger" href="?delete">Supprimer ce Ztamp</a>
      </div>
    </div><?php endif; ?>
    <?php endif; ?>
	</div>
</div>
<?php
require_once '../include/append.php';
?>
