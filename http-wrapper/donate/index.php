<?php
require_once "../include/common.php";
?>
<div class="row">
  <div class="col-md-12">
    <div class="card">
      <h5 class="card-header">
        <i class="icon-list-alt"></i> <?php echo __tr("Donate") ?>
      </h5>
      <div class="card-body">
        <p><?php echo __tr('openJabNab exists thanks to volunteers, who are giving a lot of time, and even money, for this project and servers') ?>.</p>
        <p><?php echo __tr('You can contribute to the project with a donations, that is going to pay a part of the server rental, or that will motivate developers') ?>.</p>
        <?php if(ENABLE_DONATE): ?>
        <div class="text-center">
          <?php include_once('form.inc.php') ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<div class="row">
  <div class="col-md-6">
    <div class="card">
      <h5 class="card-header">
        <i class="icon-list-alt"></i> <?php echo __tr("Development") ?>
      </h5>
      <div class="card-body">
        <p><?php echo __tr('If you are a Qt / C++ developper, a web developer ( PHP / html / Javascript, ... ), you could help us to improve openJabNab') ?>.</p>
        <p><?php echo __tr('If you are a mobile (Android, IOS, blackberry, ...) developper, you could help creating mobile apps') ?>.</p>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card">
      <h5 class="card-header">
        <i class="icon-list-alt"></i> <?php echo __tr("Ideas") ?>
      </h5>
      <div class="card-body">
        <p><?php echo __tr('You could also contribute by sending us ideas for new features or improvements') ?>.</p>
      </div>
    </div>
  </div>
</div>
<div class="row">
  <div class="col-md-6">
    <div class="card">
      <h5 class="card-header">
        <i class="icon-list-alt"></i> <?php echo __tr("Data provider") ?>
      </h5>
      <div class="card-body">
        <p><?php echo __tr('Here is a list of links to website who help us providing some data') ?></p>
        <ul>
          <li><?php echo __tr('%1 data are provided by %2', __tr('Ephemeris'), "<a href='http://fetedujour.fr/' target='_blank'>fetedujour.fr</a>") ?>.</li>
          <li><?php echo __tr('%1 data are provided by %2', __tr('Weather'), "<a href='http://yahoo.fr/' target='_blank'>yahoo.fr</a>") ?>.</li>
        </ul>
        <?php /* <p><?php echo __tr('Here is a list of companies that helped me') ?></p>
        <ul>
          <li><a href='http://www.koubachi.com/' target='_blank'>Koubachi</a>, <?php echo __tr('Discount on a plant sensor') ?></li>
        </ul>
        */ ?>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card">
      <h5 class="card-header">
        <i class="icon-list-alt"></i> <?php echo __tr("How to help the project ?") ?>
      </h5>
      <div class="card-body">
      </div>
    </div>
  </div>
</div>
<?php
require_once "../include/append.php";
?>
