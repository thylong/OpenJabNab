<?php
if(empty($formType))
{
	echo 'Cart form type not defined, abort';
	die();
}
?>
<?php //require_once '../include/config.php'; ?>
<form target="paypal" action="https://www.<?php echo USE_PAYPAL_SANDBOX ? 'sandbox.' :''; ?>paypal.com/cgi-bin/webscr" method="post">
	<input type="hidden" name="cmd" value="_s-xclick">
	<input type="hidden" name="hosted_button_id" value="<?php echo PAYPAL_PREMIUM_BTN_ID; ?>">
	<input type="hidden" name="custom" value="premium/<?php echo !empty($_SESSION['login']) ? $_SESSION['login'] : 'guest'; ?>" />
	<input type="hidden" name="currency_code" value="EUR">
	<input type="hidden" name="on2" value="User">
	<input type="hidden" name="os2" value="<?php echo !empty($_SESSION['login']) ? $_SESSION['login'] : 'guest'; ?>">
	<input type="hidden" name="on1" value="Type">
	<input type="hidden" name="os1" value="<?php echo $formType ?>">
	<input type="hidden" name="on0" value="Duration">
	<div class="form-group row">
		<label class="col-sm-2 col-form-label" for="os0"><?php echo __tr("Duration") ?></label>
		<div class="col-sm-5">
			<select name="os0" class="form-control">
				<option value="1 month"><?php echo __tr('%1 month, %2', 1, '2.00') ?> &euro;</option>
				<option value="3 months"><?php echo __tr('%1 months, %2', 3, '3.00') ?> &euro;</option>
				<option value="6 months"><?php echo __tr('%1 months, %2', 6, '5.00') ?> &euro;</option>
				<option value="1 year"><?php echo __tr('%1 year, %2', 1, '10.00') ?> &euro;</option>
				<option value="2 years"><?php echo __tr('%1 years, %2', 2, '20.00') ?> &euro;</option>
			</select>
		</div>
		<div class="col-sm-3">
			<input type="submit" class="btn btn-primary" value="<?php echo __tr('Add to cart') ?>">
		</div>
	</div>
</form>
