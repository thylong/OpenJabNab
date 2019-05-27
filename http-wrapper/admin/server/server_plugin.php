<?php
require_once "../include/common.php";
$_SESSION['tab'] = 'server_plugins';

$plugins = $ojnAPI->getListOfPlugins(false, $ojnTemplate->getLanguage());
if(!isset($plugins[$_GET['p']]))
	header('Location: /admin/server/index.php');

require_once(ROOT_SITE.'include/message.php');
?>
	<div class="row">
		<div class="span12">
			<div class="widget">
			<div class="widget-header">
			    &nbsp;&nbsp;&nbsp;<a href="/admin/server/index.php" class="btn btn-mini">&lt; <?php echo __tr('Back') ?></a>
			    <h3><?php echo __tr("Setup for plugin '%1'", __tr($plugins[$_GET['p']])) ?></h3>
			</div>
			<div class="widget-content">
<?php
$plugin = !empty($_GET['p']) ? $_GET['p'] : (isset($_POST['p']) ? $_POST['p'] : '');
if(file_exists('plugins/'.$plugin.'.plugin.php'))
	include('plugins/'.$plugin.'.plugin.php');
else {
	Message::AddError(__tr('No configuration for this plugin'));
	header('Location: /admin/server/index.php');
}
?>
			</div>
			</div>
		</div>
	</div>
<?php
require_once "../include/append.php";
?>
