<form target="paypal" action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_cart">
<input type="hidden" name="business" value="F4HA9BXXGFB42">
<input type="hidden" name="lc" value="FR">
<input type="hidden" name="item_name" value="Code cadeau premium openJabNab">
<input type="hidden" name="item_number" value="ojn_gift">
<input type="hidden" name="button_subtype" value="products">
<input type="hidden" name="currency_code" value="EUR">
<input type="hidden" name="add" value="1">
<input type="hidden" name="bn" value="PP-ShopCartBF:btn_cart_LG.gif:NonHostedGuest">
<table>
<tr><td><input type="hidden" name="on0" value="Durée">Durée</td></tr><tr><td><select name="os0">
	<option value="3 mois">3 mois €3,00 EUR</option>
	<option value="6 mois">6 mois €5,00 EUR</option>
	<option value="1 an">1 an €10,00 EUR</option>
	<option value="2 ans">2 ans €20,00 EUR</option>
</select> </td></tr>
</table>
<input type="hidden" name="currency_code" value="EUR">
<input type="hidden" name="option_select0" value="3 mois">
<input type="hidden" name="option_amount0" value="3.00">
<input type="hidden" name="option_select1" value="6 mois">
<input type="hidden" name="option_amount1" value="5.00">
<input type="hidden" name="option_select2" value="1 an">
<input type="hidden" name="option_amount2" value="10.00">
<input type="hidden" name="option_select3" value="2 ans">
<input type="hidden" name="option_amount3" value="20.00">
<input type="hidden" name="option_index" value="0">
<input type="image" src="https://www.paypalobjects.com/fr_FR/FR/i/btn/btn_cart_LG.gif" border="0" name="submit" alt="PayPal - la solution de paiement en ligne la plus simple et la plus sécurisée !">
<img alt="" border="0" src="https://www.paypalobjects.com/fr_FR/i/scr/pixel.gif" width="1" height="1">
</form>

<form target="paypal" action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_cart">
<input type="hidden" name="business" value="F4HA9BXXGFB42">
<input type="hidden" name="display" value="1">
<input type="image" src="https://www.paypalobjects.com/fr_FR/FR/i/btn/btn_cart_LG.gif" border="0" name="submit" alt="PayPal - la solution de paiement en ligne la plus simple et la plus sécurisée !">
<img alt="" border="0" src="https://www.paypalobjects.com/fr_FR/i/scr/pixel.gif" width="1" height="1">
</form>


<form action="https://www.paypal.com/cgi-bin/webscr" method="post"> 
<input type="hidden" name="return" value="http://openjabnab.fr/ojn_admin/thanks.php">
<input type="hidden" name="cmd" value="_s-xclick"> 
<?php if(isset($_SESSION['login'])): ?>
<input type="hidden" name="on0" value="<?php echo __tr('Username') ?>">
<input type="hidden" name="os0" value="<?php echo $_SESSION['login'] ?>">
<?php 
$bunnies = $ojnAPI->getListOfBunnies(false);
if(is_array($bunnies)):
?>
<input type="hidden" name="on1" value="<?php echo __tr('Bunnies') ?>">
<input type="hidden" name="os1" value="<?php echo implode(', ', array_keys($bunnies)) ?>">
<?php endif; ?>
<input type="hidden" name="item_name" value="openJabNab (<?php echo __tr('Username') ?> : <?php echo $_SESSION['login'] ?>)">
<?php else:?>
<input type="hidden" name="item_name" value="openJabNab">
<?php endif;?>
<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7----- 
"> 
<?php
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
<input type="image" src="<?php echo $image ?>" border="0" name="submit" alt="PayPal - la solution de paiement en ligne la plus simple et la plus sécurisée !"> 
<img alt="" border="0" src="https://www.paypalobjects.com/fr_FR/i/scr/pixel.gif" width="1" height="1"> 
</form>
