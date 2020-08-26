<?php
require_once '../include/common.php';
$ojnTemplate->setTitle(__tr('Plugin setup for bunny'));
$_SESSION['tab'] = 'bunny_plugins';
$reload = false;
$plugin = !empty($_GET['p']) ? $_GET['p'] : '';
$plugins = $ojnAPI->getListOfPlugins(false, $ojnTemplate->getLanguage());
if(empty($plugin) || empty($_SESSION['bunny']) || !isset($plugins[$plugin]))
{
	header('Location: /bunny/index.php');
	exit();
}

$action=!empty($_GET['action']) ? $_GET['action'] :'';
$reload = true;
switch($action)
{
  case 'deleteConfig':
    Message::AddFromApi($ojnAPI->getApiString('bunny/'.$_SESSION['bunny'].'/deletePluginSettings?plugin='.$plugin.'&'.$ojnAPI->getToken()));
    break;
  default:
    $reload = false;
}

if($reload)
{
	header('Location: /bunny/bunny_plugin.php?p='.$plugin);
	exit;
}

require(ROOT_SITE.'include/message.php');
?>
<div class="card ">
  <h5 class="card-header">
    <a href="/bunny/index.php" class="btn btn-warning btn-sm">&lt; <?php echo __tr('Back') ?></a>&nbsp;
    <i class="icon-cog"></i> <?php echo __tr("Setup for plugin '%1' on bunny %2", __tr($plugins[$_GET['p']]), !empty($_SESSION['bunny_name']) ? $_SESSION['bunny_name'] :$_SESSION['bunny']) ?>
  </h5>
  <div class="card-body">
    <?php
    if(!file_exists('plugins/'.$plugin.'.plugin.php')):
      Message::AddError(__tr('No configuration for this plugin'));
      header('Location: /bunny/index.php');
    else:
      require_once('plugins/'.$plugin.'.plugin.php'); 
    ?>
    <hr />
    <div class="card">
      <h6 class="card-header bg-warning text-secondary"><?php echo __tr('Advanced'); ?></h6>
      <div class="card-body">
        <a href="?p=<?php echo $plugin	; ?>&action=deleteConfig" class="btn btn-sm btn-danger"><?php echo __tr('Delete configuration') ?></a>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php require_once '../include/append.php'; ?>
