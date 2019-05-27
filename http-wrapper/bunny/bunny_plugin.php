<?php
require_once '../include/common.php';
$ojnTemplate->setTitle(__tr('Plugin setup for bunny'));
$_SESSION['tab'] = 'bunny_plugins';
if(!isset($_SESSION['token']))
	header('Location: index.php');

$plugins = $ojnAPI->getListOfPlugins(false, $ojnTemplate->getLanguage());
if(!isset($plugins[$_GET['p']]) || !isset($_SESSION['bunny'])) {
	header('Location: /bunny/index.php');
	exit();
}

require(ROOT_SITE.'include/message.php');
?>
	<div class="row">
		<div class="span12">
			<div class="widget">
			<div class="widget-header">
			    &nbsp;&nbsp;&nbsp;<a href="/bunny/index.php" class="btn btn-mini">&lt; <?php echo __tr('Back') ?></a>
			    <h3><?php echo __tr("Setup for plugin '%1' on bunny %2", __tr($plugins[$_GET['p']]), !empty($_SESSION['bunny_name']) ? $_SESSION['bunny_name'] :$_SESSION['bunny']) ?></h3>
			</div>
			<div class="widget-content">
<?php
$plugin = !empty($_GET['p']) ? $_GET['p'] : (isset($_POST['p']) ? $_POST['p'] : '');
if(file_exists('plugins/'.$plugin.'.plugin.php'))
	require_once('plugins/'.$plugin.'.plugin.php');
else {
	Message::AddError(__tr('No configuration for this plugin'));
	header('Location: /bunny/index.php');
}
?>
			</div>
			</div>
		</div>
	</div>
<?php
require_once '../include/append.php';
?>
