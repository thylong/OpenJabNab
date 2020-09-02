<?php
require_once "../include/common.php";
?>
<?php
if(!empty($_GET['tx']))
{
  $_SESSION['paypal'] = $_GET;
  header('Location: /premium/done.php');
  die;
}
if(empty($_SESSION['paypal']))
{
  header('Location: /premium/');
  die;
}
//echo'<pre>'; var_dump($_SESSION['paypal']); echo '</pre>';
?>
<div class="card">
  <h5 class="card-header">
    <i class="icon-list-alt"></i> <?php echo __tr("Many thanks") ?>
  </h5>
  <div class="card-body">
    <div class="widget-content">
		  <p><?php echo __tr('Thank you very much for your payment') ?> (<?php echo __tr('Transaction <b>#%1</b>',$_SESSION['paypal']['tx']); ?>).</p>
		  <p><?php echo __tr('You will receive your premium status and/or gift codes in a few minutes. Otherwise, please contact an administrator so they can look into it.') ?></p>
      <p><?php echo __tr('Your contribution really help us stay motivated and keep the server running, so thank you for your help.') ?></p>
    </div>
  </div>
</div>
<?php
unset($_SESSION['paypal']); // Clean !

require_once "../include/append.php";
?>
