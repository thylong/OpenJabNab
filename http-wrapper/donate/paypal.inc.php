<?php
    #define('PAYPAL_CLIENT_ID','AZF0ncJIS14QSALr7pTLFk4ai9w_YPLTQ8-2orW8MR0Ng43enM0eiNqs2vThR7VgpQltBkzboT4K9Tc-');
    #define('PAYPAL_CLIENT_ID','ATeLORFqpeBW4FZPgQiZUI7brj3-YQmZARNKQNC5FHo766eqOXnBvcd_2NuXWfL4rDJCGHKiF0p1AQ_M');
    #define('PAYPAL_CURRENCY','EUR');

    $donation_type = 'premium';
    $donation_title = 'OJN Premium';
    $donation_user = 'redox';

    $image = "https://www.paypalobjects.com/fr_FR/FR/i/btn/btn_donateCC_LG.gif";
if($Infos['language'] == "es") {
	$image = "https://www.paypalobjects.com/es_ES/ES/i/btn/btn_donateCC_LG.gif";
}
if($Infos['language'] == "en") {
	$image = "https://www.paypalobjects.com/en_US/GB/i/btn/btn_donateCC_LG.gif";
}
if($Infos['language'] == "de") {
	$image = "https://www.paypalobjects.com/de_DE/DE/i/btn/btn_donateCC_LG.gif";
}
if($Infos['language'] == "it") {
	$image = "https://www.paypalobjects.com/it_IT/IT/i/btn/btn_donateCC_LG.gif";
}
if($Infos['language'] == "ru") {
	$image = "https://www.paypalobjects.com/ru_RU/RU/i/btn/btn_donateCC_LG.gif";
}
?>

<form action="https://www.<?php if(defined('PAYPAL_BTN_SANDBOX')): ?>sandbox.<?php endif; ?>paypal.com/cgi-bin/webscr" method="post" target="_top">
<input type="hidden" name="cmd" value="_s-xclick" />
<input type="hidden" name="custom" value="<?php echo $donation_type; ?>/<?php echo $donation_user; ?>" />
<input type="hidden" name="item_name" value="<?php echo $donation_title; ?>" />
<input type="hidden" name="hosted_button_id" value="<?php echo defined('PAYPAL_BTN_SANDBOX') ? PAYPAL_BTN_SANDBOX : PAYPAL_BTN_LIVE; ?>" />
<input type="image" src="<?php echo $image; ?>" border="0" name="submit" title="PayPal - The safer, easier way to pay online!" alt="Bouton Faites un don avec PayPal" />
<img alt="" border="0" src="https://www.<?php if(defined('PAYPAL_BTN_SANDBOX')): ?>sandbox.<?php endif; ?>paypal.com/fr_FR/i/scr/pixel.gif" width="1" height="1" />
</form>
