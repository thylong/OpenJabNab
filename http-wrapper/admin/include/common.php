<?php
require_once realpath(dirname(__FILE__)).'/../../include/common.php';

if(!isset($_SESSION['token']) || !$Infos['isAdmin'])
    header('Location: /index.php');
?>
<div class="row">
  <div class="span12">
   <div class="widget">
      <div class="widget-header">
        <i class="icon-cog"></i> <h3><?php echo __tr('Server settings') ?></h3>
      </div>
      <div class="widget-content alert">
        Be careful messing around :)
      </div>
    </div>
  </div>
</div>
