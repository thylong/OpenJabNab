<?php
require_once realpath(dirname(__FILE__)).'/../../include/common.php';

$translates = getTranslates($_SESSION['login']);

if(!empty($_SESSION['token']) && ($Infos['isAdmin'] || (strpos($_SERVER['DOCUMENT_URI'],'/translation/') !== false && !empty($_GET['lng']) && in_array($_GET['lng'], $translates)) )):
?>
<div class="card">
  <h5 class="card-header bg-danger text-light">
    <i class="icon-cog"></i> <?php echo __tr('Server settings') ?>
  </h5>
  <div class="card-body bg-danger-light">
    Be careful messing around :)
  </div>
</div>
<?php else:
    header('Location: /index.php');
    die;
endif; ?>