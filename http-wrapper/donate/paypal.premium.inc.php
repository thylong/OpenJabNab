<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="return" value="http://openjabnab.fr/ojn_admin/premium_done.php">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="on0" value="<?php echo __tr('Username') ?>">
<input type="hidden" name="os0" value="<?php echo $_SESSION['login'] ?>">
<table>
<tr><td><input type="hidden" name="on0" value="Durée">Durée</td></tr><tr><td><select name="os0">
	<option value="3 mois">3 mois €3,00 EUR</option>
	<option value="6 mois">6 mois €5,00 EUR</option>
	<option value="1 an">1 an €10,00 EUR</option>
	<option value="2 ans">2 ans €20,00 EUR</option>
</select> </td></tr>
</table>
<input type="hidden" name="currency_code" value="EUR">
<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----MIIIIQYJKoZIhvcNAQcEoIIIEjCCCA4CAQExggEwMIIBLAIBADCBlDCBjjELMAkGA1UEBhMCVVMxCzAJBgNVBAgTAkNBMRYwFAYDVQQHEw1Nb3VudGFpbiBWaWV3MRQwEgYDVQQKEwtQYXlQYWwgSW5jLjETMBEGA1UECxQKbGl2ZV9jZXJ0czERMA8GA1UEAxQIbGl2ZV9hcGkxHDAaBgkqhkiG9w0BCQEWDXJlQHBheXBhbC5jb20CAQAwDQYJKoZIhvcNAQEBBQAEgYA3XT0DgQ/P49ssZd2x8K0KlR5qqGEgZgyrEBY9of1fWMyWMinQ+/6mQmMRdeO01NzBcnEltX/2OJoSbmvNvpx9amww9h7TU/K4ftTBMdmGoa6xb2N/Y4D4pgsjZgHLwhmFQzGQ4fHfZbFHqx0tV4WA6Y03W8fskX4hho/UrjNABTELMAkGBSsOAwIaBQAwggGdBgkqhkiG9w0BBwEwFAYIKoZIhvcNAwcECA4vye9LeU0QgIIBeNOJCNFqeOfOpDMEG6gzfuJmEdihs6z5W02ssbYzJUR8FW35NbInYMDwUyaM/PahBOt9O/Xg4CATFE3lIlUWHScK6Hz97Gdpumm0QqRNMDjKw247Sy0bQ2emEiB3D4qic4POWQPiWFgQP/dr3uy55xUAQOS1j2nef3rErn1kuZ8f59vHe0fH6gPOwOm+DDmIInDfSx3ckKuY7ANMtPw9V8SFZl+50Aw1GxztQtaVXz7CLHRTESR75P7U3/ZWLw4lyN7OEt/+KQXyCZZTqCcFCHO9BJ4KaREvWhlKyG6SjGiqj0tDcvetSmQD4DG8WoQqZRwQUp6BUnlCmeEw19nwnkzvP/CH2dChQZd+lN0kdx7Fm1bPzaFMkw2S0tNK+zA5FMKZlzgd0DyF+4GCooC9oAK34wvfEOJrxJbfZdiJCUSp8++Qy/bUPsElY8WFHImVnXe0HMZOQBTAh7FrWCUEZKTMnWV9PKbgzLf1hOXWJhw/ezOf7o7AvVmgggOHMIIDgzCCAuygAwIBAgIBADANBgkqhkiG9w0BAQUFADCBjjELMAkGA1UEBhMCVVMxCzAJBgNVBAgTAkNBMRYwFAYDVQQHEw1Nb3VudGFpbiBWaWV3MRQwEgYDVQQKEwtQYXlQYWwgSW5jLjETMBEGA1UECxQKbGl2ZV9jZXJ0czERMA8GA1UEAxQIbGl2ZV9hcGkxHDAaBgkqhkiG9w0BCQEWDXJlQHBheXBhbC5jb20wHhcNMDQwMjEzMTAxMzE1WhcNMzUwMjEzMTAxMzE1WjCBjjELMAkGA1UEBhMCVVMxCzAJBgNVBAgTAkNBMRYwFAYDVQQHEw1Nb3VudGFpbiBWaWV3MRQwEgYDVQQKEwtQYXlQYWwgSW5jLjETMBEGA1UECxQKbGl2ZV9jZXJ0czERMA8GA1UEAxQIbGl2ZV9hcGkxHDAaBgkqhkiG9w0BCQEWDXJlQHBheXBhbC5jb20wgZ8wDQYJKoZIhvcNAQEBBQADgY0AMIGJAoGBAMFHTt38RMxLXJyO2SmS+Ndl72T7oKJ4u4uw+6awntALWh03PewmIJuzbALScsTS4sZoS1fKciBGoh11gIfHzylvkdNe/hJl66/RGqrj5rFb08sAABNTzDTiqqNpJeBsYs/c2aiGozptX2RlnBktH+SUNpAajW724Nv2Wvhif6sFAgMBAAGjge4wgeswHQYDVR0OBBYEFJaffLvGbxe9WT9S1wob7BDWZJRrMIG7BgNVHSMEgbMwgbCAFJaffLvGbxe9WT9S1wob7BDWZJRroYGUpIGRMIGOMQswCQYDVQQGEwJVUzELMAkGA1UECBMCQ0ExFjAUBgNVBAcTDU1vdW50YWluIFZpZXcxFDASBgNVBAoTC1BheVBhbCBJbmMuMRMwEQYDVQQLFApsaXZlX2NlcnRzMREwDwYDVQQDFAhsaXZlX2FwaTEcMBoGCSqGSIb3DQEJARYNcmVAcGF5cGFsLmNvbYIBADAMBgNVHRMEBTADAQH/MA0GCSqGSIb3DQEBBQUAA4GBAIFfOlaagFrl71+jq6OKidbWFSE+Q4FqROvdgIONth+8kSK//Y/4ihuE4Ymvzn5ceE3S/iBSQQMjyvb+s2TWbQYDwcp129OPIbD9epdr4tJOUNiSojw7BHwYRiPh58S1xGlFgHFXwrEBb3dgNbMUa+u4qectsMAXpVHnD9wIyfmHMYIBmjCCAZYCAQEwgZQwgY4xCzAJBgNVBAYTAlVTMQswCQYDVQQIEwJDQTEWMBQGA1UEBxMNTW91bnRhaW4gVmlldzEUMBIGA1UEChMLUGF5UGFsIEluYy4xEzARBgNVBAsUCmxpdmVfY2VydHMxETAPBgNVBAMUCGxpdmVfYXBpMRwwGgYJKoZIhvcNAQkBFg1yZUBwYXlwYWwuY29tAgEAMAkGBSsOAwIaBQCgXTAYBgkqhkiG9w0BCQMxCwYJKoZIhvcNAQcBMBwGCSqGSIb3DQEJBTEPFw0xMzAzMTcxMzQ1MDZaMCMGCSqGSIb3DQEJBDEWBBSnfgi20nRilnLpJBGm1EFqVe4LZTANBgkqhkiG9w0BAQEFAASBgCvCdlryX7PZodOFyh4OJ4C4lDCRnPEt5O87obEztVbRyBjgYEVUsekRTaVl9m+/Vd2qWdvAs9VvfqSECQr29FhRo8vYzv4dkuj+JuehtT9iuuWAHkNgzwHfpEz0nf1WrWn/U7ocH7ttQmoHGIkmq1UHbj7A+T+2EfjpcQJTef8c-----END PKCS7-----
">
<?php
$image = "https://www.paypalobjects.com/fr_FR/FR/i/btn/btn_buynowCC_LG.gif";
if($Infos['language'] == "es") {
	$image = "https://www.paypalobjects.com/es_ES/ES/i/btn/btn_buynowCC_LG.gif";
}
if($Infos['language'] == "en") {
	$image = "https://www.paypalobjects.com/en_US/GB/i/btn/btn_buynowCC_LG.gif";
}
if($Infos['language'] == "de") {
	$image = "https://www.paypalobjects.com/de_DE/DE/i/btn/btn_buynowCC_LG.gif";
}
if($Infos['language'] == "it") {
	$image = "https://www.paypalobjects.com/it_IT/IT/i/btn/btn_buynowCC_LG.gif";
}
if($Infos['language'] == "ru") {
	$image = "https://www.paypalobjects.com/ru_RU/RU/i/btn/btn_buynowCC_LG.gif";
}
?>
<input type="image" src="<?php echo $image ?>" border="0" name="submit" alt="PayPal - la solution de paiement en ligne la plus simple et la plus sécurisée !"> 
<img alt="" border="0" src="https://www.paypalobjects.com/fr_FR/i/scr/pixel.gif" width="1" height="1"> 
</form>
