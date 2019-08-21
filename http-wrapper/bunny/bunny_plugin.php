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
  <div class="row">
    <div class="span12">
      <div class="widget">
        <div class="widget-header">
          <a href="/bunny/index.php" class="btn btn-mini">&lt; <?php echo __tr('Back') ?></a>
          <h3><?php echo __tr("Setup for plugin '%1' on bunny %2", __tr($plugins[$_GET['p']]), !empty($_SESSION['bunny_name']) ? $_SESSION['bunny_name'] :$_SESSION['bunny']) ?></h3>
        </div>
<?php

if(!file_exists('plugins/'.$plugin.'.plugin.php')):
  Message::AddError(__tr('No configuration for this plugin'));
	header('Location: /bunny/index.php');
else: ?>
        <div class="widget-content">
<?php require_once('plugins/'.$plugin.'.plugin.php'); ?>
        <hr />
          <a href="?p=<?php echo $plugin	; ?>&action=deleteConfig" class="btn btn-mini btn-danger"><?php echo __tr('Delete configuration') ?></a>
        </div>
      <div>
    </div>
<?php endif; ?>
  </div>
<?php require_once '../include/append.php'; ?>
