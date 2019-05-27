<?php
if(!file_exists("include/common.php"))
	header('Location: install.php');
require_once "include/common.php";

require_once('include/message.php');
?>
	<div class="row">
		<div class="span12">
			<div class="widget error-container">
			<div class="widget-content">
				<h2><?php echo __tr('403 Forbidden') ?></h2>
				<div class="error-details">
					<?php echo __tr('Sorry, you are not allowed to see this page') ?>
				</div> 
				
				<div class="error-actions">
					<a href="/ojn_admin/index.php" class="btn btn-large btn-primary"><i class="icon-chevron-left"></i> <?php echo __tr('Back to index') ?></a>
					<a href="/ojn_admin/help.php" class="btn btn-large"><i class="icon-envelope"></i> <?php echo __tr('Contact Support') ?>
					</a>
				</div> 
			</div> 			
			</div> 			
		</div> 
	</div> 
