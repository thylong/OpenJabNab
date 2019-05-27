<?php
require_once "include/common.php";
?>
<div class="row">
    <div class="span7">
        <div class="widget widget">
            <div class="widget-header">
                <i class="icon-list-alt"></i><h3><?php echo __tr("Data provider") ?></h3>
            </div> 
            <div class="widget-content">
		<p><?php echo __tr('Here is a list of links to website who help us providing some data') ?></p>
		<ul>
		<li><?php echo __tr('%1 data are provided by %2', __tr('Ephemeris'), "<a href='http://fetedujour.fr/' target='_blank'>fetedujour.fr</a>") ?>.</li>
		<li><?php echo __tr('%1 data are provided by %2', __tr('Weather'), "<a href='http://yahoo.fr/' target='_blank'>yahoo.fr</a>") ?>.</li>
		</ul>
		<p><?php echo __tr('Here is a list of companies that helped me') ?></p>
		<ul>
		<li><a href='http://www.koubachi.com/' target='_blank'>Koubachi</a>, <?php echo __tr('Discount on a plant sensor') ?></li>
		</ul>
            </div> 
        </div>
    </div>
    <div class="span5">
        <div class="widget widget">
            <div class="widget-header">
                <i class="icon-list-alt"></i><h3><?php echo __tr("Donate") ?></h3>
            </div> 
            <div class="widget-content">
		<p><?php echo __tr('Many thanks to all donators') ?>.</p>
<center>
<?php include_once('include/paypal.inc.php') ?>
</center>
            </div> 
        </div>
    </div>
</div>
<?php
require_once "include/append.php";
?>
