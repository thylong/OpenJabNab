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
else if(!empty($_GET['ztamp_name'])) {
	$_SESSION['message'] = $ojnAPI->getApiString("ztamp/".$_SESSION['ztamp']."/setZtampName?name=".urlencode($_GET['ztamp_name'])."&".$ojnAPI->getToken());
	$_SESSION['ztamp_name'] = $_GET['ztamp_name'];
	header('Location: /account/ztamp.php');
	exit();
}
require_once('../include/message.php');
?>
	      <div class="row">
	      	<div class="span12">
	      		<div class="widget">
					<div class="widget-header">
						<i class="icon-th-large"></i>
<?php
if(empty($_SESSION['ztamp'])) {
?>
						<h3><?php echo __tr("Choose your Ztamp") ?></h3>
					</div> <!-- /widget-header -->
					<div class="widget-content">
						<div class="object-list object-6">
<?php
	$ztamps = $ojnAPI->getListOfZtamps(false);
    if(!empty($ztamps))
	foreach($ztamps as $ztamp => $nom)	{
?>
			    <div class="obj-container">
				<div class="object">
					<div class="obj-header">
					    <div class="obj-name2"><?php echo $nom; ?></div>
					    <div class="obj-info"><?php echo $ztamp; ?></div>
					</div>
					<div class="obj-actions">
					    <a class="btn" href="/account/ztamp.php?z=<?php echo $ztamp; ?>"><?php echo __tr("Setup") ?></a>
					</div>
				</div> <!-- /object -->
			    </div> <!-- /obj-container -->
<?php
	}
?>
</div>
<br style="clear:both"/>
<br />
<?php
	$bunnies = $ojnAPI->getListOfBunnies(false);
    if(!empty($bunnies))
	foreach($bunnies as $mac => $nom)	{
		$lastZ = $ojnAPI->getApiString("bunny/$mac/rfid/getLastBunnyTag?".$ojnAPI->getToken());
		if(!empty($lastZ['value'])) {
?>
Dernier Ztamp utilis&eacute;  par <?php echo $nom; ?> (<?php echo $mac; ?>): <?php echo (!empty($ztamps) && isset($ztamps[$lastZ['value']])) ? $ztamps[$lastZ['value']]: ''; ?> (<a href="/account/ztamp.php?z=<?php echo $lastZ['value']; ?>"><?php echo $lastZ['value']; ?></a>)<br />
<?php
}
}
} else {
$ownersList = array();
$q = $ojnAPI->getAPIList('ztamp/'.$_SESSION['ztamp'].'/owner?action=list&'.$ojnAPI->getToken());
foreach($q as $it)
  if(!empty($it))
    $ownersList[] = (string)($it);

$reload=true;
if(!empty($_GET['add_owner']))
  Message::AddFromApi($ojnAPI->getApiString('ztamp/'.$_SESSION['ztamp'].'/owner?action=add&login='.$_GET['add_owner'].'&'.$ojnAPI->getToken()));
else if(!empty($_GET['rm_owner']))
  Message::AddFromApi($ojnAPI->getApiString('ztamp/'.$_SESSION['ztamp'].'/owner?action=del&login='.$_GET['rm_owner'].'&'.$ojnAPI->getToken()));
else
  $reload=false;

if($reload)
{
  header('Location: /account/ztamp.php');    
  exit;
}
?>
						<h3><?php echo __tr("Setup of ztamp '%1'", !empty($_SESSION['ztamp_name']) ? $_SESSION['ztamp_name'] : $_SESSION['ztamp']) ?></h3>
					</div> <!-- /widget-header -->


<div class="widget-content">
  <form>
    <fieldset>
      <legend>Configuration</legend>
<?php
$plugins = $ojnAPI->getListOfPlugins(false, $ojnTemplate->getLanguage());
?>
      Nom : <input type="text" name="ztamp_name" value="<?php echo $_SESSION['ztamp_name']; ?>"> <input type="submit" value="Enregistrer">
    </fieldset>
  </form>
<?php if($Infos['isAdmin']): ?>
  <div class="card alert alert-light">
    <h4 class="card-header alert alert-danger">Administration</h4>
    <div class="card-body">
      Ztamp ID <?php echo $_SESSION['ztamp']; ?><br />
      Owner(s):
      <ul>
        <?php foreach($ownersList as $login): ?>
        <li class="text-primary"><?php echo $login; ?> <a class="text-danger" href="?rm_owner=<?php echo $login; ?>">Supprimer</a></li>
        <?php endforeach; ?>
      </ul>
      <form class="form-inline" method="get">
        <label for="add_owner">Add owner</label>
        <input type="text" name="add_owner" placeholder="Login" />
        <input type="submit" value="Valider" class="btn btn-primary" />
      </form>
      <a class="btn btn-warning" href="?resetown">Liberer le ztamp de ce compte</a>
      <a class="btn btn-danger" href="?delete">Supprimer ce Ztamp</a>
    </div>
  </div>
<?php endif; ?>
<?php }
?>

					</div> <!-- /widget-content -->
				</div> <!-- /widget -->
		    </div> <!-- /span12 -->
	      </div> <!-- /row -->
<?php
require_once '../include/append.php';
?>
