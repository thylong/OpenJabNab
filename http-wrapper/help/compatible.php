<?php
require_once '../include/common.php';
?>
<div class="card">
  <h5 class="card-header">
    <i class="icon-question-sign"></i> <?php echo __tr("Is my bunny compatible with openJabNab ?") ?>
  </h5>
  <div class="card-body">
    <div class="object-list object-3">
      <div class="obj-container">
        <div class="object blue">
          <div class="obj-header">
            <div class="obj-name">Nabaztag<br />V1</div>
          </div>
          <div class="obj-actions text-center">
            <img src="/media/img/Nabaztag.png" />
            <div><?php echo __tr('Yes') ?></div>
          </div>
        </div>
      </div>
      <div class="obj-container">
        <div class="object blue">
          <div class="obj-header">
            <div class="obj-name">Nabaztag:tag<br />V2</div>
          </div>
          <div class="obj-actions text-center">
            <img src="/media/img/NabaztagTag.png" />
            <div><?php echo __tr('Yes') ?></div>
          </div>
        </div>
      </div>
      <div class="obj-container">
        <div class="object">
          <div class="obj-header">
            <div class="obj-name">Karotz<br />V3</div>
          </div>
          <div class="obj-actions text-center">
            <img src="/media/img/Karotz.png" />
            <div><?php echo __tr('No') ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php
require_once '../include/append.php';
?>
