<?php //require_once '../include/config.php'; ?>
<form action="https://www.<?php echo USE_PAYPAL_SANDBOX ? 'sandbox.' :''; ?>paypal.com/cgi-bin/webscr" method="post" target="_top">
  <input type="hidden" name="cmd" value="_s-xclick" />
  <input type="hidden" name="hosted_button_id" value="<?php echo PAYPAL_DONATE_BTN_ID; ?>" />
  <input type="hidden" name="custom" value="donation/<?php echo !empty($_SESSION['login']) ? $_SESSION['login'] : 'guest'; ?>" />
  <input type="image" src="https://www.paypalobjects.com/en_US/FR/i/btn/btn_donateCC_LG.gif" border="0" name="submit" title="PayPal - The safer, easier way to pay online!" alt="Donate with PayPal button" />
  <img alt="" border="0" src="https://www.sandbox.paypal.com/en_FR/i/scr/pixel.gif" width="1" height="1" />
</form>
