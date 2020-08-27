<?php
require_once realpath(dirname(__FILE__)).'/../../include/common.php';

if(empty($_SESSION['token']) || (!$Infos['isAdmin'] &&
   (strpos($_SERVER['DOCUMENT_URI'],'translation') === false))
  )
    header('Location: /index.php');
?>
<div class="card">
  <h5 class="card-header bg-danger text-light">
    <i class="icon-cog"></i> <?php echo __tr('Server settings') ?>
  </h5>
  <div class="card-body bg-danger-light">
    Be careful messing around :)
  </div>
</div>
