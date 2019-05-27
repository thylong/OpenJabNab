<?php
require_once "include/common.php";
$ojnTemplate->setTitle(__tr('Crons'));
if(!isset($_SESSION['token']))
header('Location: index.php');

if($reload) {
	header('Location: /bunny/index.php');
	exit;
}

require_once('include/message.php');
$xml = $ojnAPI->getApiRaw("cron/cron?action=list&plugin=fairytales&".$ojnAPI->getToken());
$crons = simplexml_load_string($xml);
?>
	      <div class="row">
	      	<div class="span12">
	      		<div class="widget">
					<div class="widget-header">
						<i class="icon-th-large"></i>
						<h3><?php echo __tr("Choose your bunny") ?></h3>
					</div> <!-- /widget-header -->
					<div class="widget-content">

<?php foreach($crons->crons->cron as $cron): ?>
<?php echo $cron->plugin ?>-&gt;<?php echo strlen($cron->callback) ? $cron->callback : 'OnCron' ?>(<?php echo strlen($cron->data_string) ? '"' . $cron->data_string . '"' : ( strlen($cron->data_int) ? $cron->data_int : '') ?>) @ <?php echo date('H:i d/m/Y', $cron->next_run + 0) ?> <?php echo strlen($cron->bunny) ? ' for '.$cron->bunny: '' ?><br /> 
<?php endforeach; ?>
					</div> <!-- /widget-content -->
				</div> <!-- /widget -->					
		    </div> <!-- /span12 -->     	
	      </div> <!-- /row -->
<?php
require_once "include/append.php";
?>
