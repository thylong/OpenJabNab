<?php
require_once 'config.php';


$imap = imap_open(IMAP_SERVER, IMAP_USER, IMAP_PASS)
      or die("Connexion impossible : " . imap_last_error());

function generate()
{
        $codes = array();
        for($i=0; $i<4; $i++)
        {
                $codes[] = strtoupper(substr(base_convert(rand() % 9999999999, 10, 36), 0, 5));
        }
        $code = implode("-", $codes);
        $link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if (!$link) {
            die('Connexion impossible : ' . mysqli_error());
        }

        $sql = 'SELECT * FROM gift WHERE code="'.addslashes($code).'";';
        $res = mysqli_query($link, $sql);
        $num = mysqli_num_rows($res);
        mysqli_close($link);
        if($num)
        {
                $code = generate();
        }
        return $code;
}

if( $imap )
{
	$changes = 0;
	$some = imap_search($imap, 'SUBJECT "un don" UNSEEN');
	if($some)
	{
		foreach($some as $id)
		{
			$header = (array)imap_header($imap, $id);
			$content = base64_decode(imap_fetchbody($imap, $id, 1));

			$date = strtotime($header['MailDate']);
			$email = (array)$header['from'][0];
			$email = $email['mailbox'] . "@" . $email['host'];
			if($email != "webmaster@openjabnab.fr")
			{
				if(preg_match("/Vous avez re.*u un don de .*([\d,]+) EUR .* partir de .* \((.*@.*)\)/U", $content, $match)) {
					$value = preg_replace("|,|", ".", $match[1]) + 0;
					$email = trim($match[2]);
				} else {
					if(preg_match("|Montant total : .*([\d,]+) EUR|isU", $content, $match))
					{
						$value = preg_replace("|,|", ".", $match[1]) + 0;
					}
				}
				if(preg_match("|openJabNab \(.*: ([^\)]+)\)|isU", $content, $match))
				{
					$user = $match[1];
				}
				if(isset($value) && isset($user))
				{
					$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
					if (!$link) {
					    die('Connexion impossible : ' . mysqli_error());
					}
					$sql = 'SELECT * FROM don WHERE date="'.date('Y-m-d H:i:s', $date).'" AND value="'.$value.'" AND username="'.$user.'" AND email="'.$email.'"';
					$res = mysqli_query($link, $sql);
					if($row = mysqli_fetch_assoc($res))
					{
						sendMail(("Le don de ".date('d/m H:i', $date)." n'a pas été pris en compte car il existe déjà."), utf8_decode("Un don existait déjà"), $to = "alexis.mellone@gadz.org", $from = "webmaster@openjabnab.fr");
					}
					else
					{
						sendMail(("Le don de ".$value."€ de la part de $user à ".date('d/m H:i', $date)." a été pris en compte"), utf8_decode("Un don a été intégré"), $to = "alexis.mellone@gadz.org", $from = "webmaster@openjabnab.fr");
						$changes++;
						$remain = $value - (0.25 + $value * 0.034);
						$sql = 'INSERT INTO don SET date="'.date('Y-m-d H:i:s', $date).'", value="'.$value.'", username="'.$user.'", email="'.$email.'", type="donation", remain="'.$remain.'"';
						mysqli_query($link, $sql);
					}
					mysqli_close($link);
				}
				else
				{
					sendMail(("Le don de ".date('d/m H:i', $date)." n'a pas été pris en compte"), utf8_decode("Un don n'a pas été pris en compte"), $to = "alexis.mellone@gadz.org", $from = "webmaster@openjabnab.fr");
				}
			}
		}
	}

	$some1 = imap_search($imap, 'SUBJECT "ception d\'un paiement" UNSEEN');
	$some2 = imap_search($imap, 'SUBJECT "Paiement re" UNSEEN');
	//$some = imap_search($imap, 'SUBJECT "ception d\'un paiement"');
	$some = false;
	if($some1) {
		$some = $some1;
	}
	if($some2) {
		if(!$some) {
			$some = array();
		}
		$some = array_merge($some, $some2);
	}
	if($some)
	{
		foreach($some as $id)
		{
			$header = (array)imap_header($imap, $id);
			$content = base64_decode(imap_fetchbody($imap, $id, 1));

			$date = strtotime($header['MailDate']);
			$email = (array)$header['from'][0];
			$email = $email['mailbox'] . "@" . $email['host'];
			if($email != "webmaster@openjabnab.fr")
			{
				$mail = $header['reply_toaddress'];

				$lines = false;
				if(preg_match_all("|Description.*de l'objet.*(ojn_[^>]*)</span>.*Duration: (.*)<br.*User: (.*)</span>.*&euro;(\d+,\d\d) EUR.*</tr>.*Paiement|isU", $content, $match, PREG_SET_ORDER))
				{
					var_dump($match);
				//}
				//if(preg_match_all("|Description(.*)Q(.*)Montant(.*) EUR|isU", $content, $match, PREG_SET_ORDER))
				//{
					foreach($match as $obj)
					{
						//$lines = preg_split("/[\r\n]/", $obj[0]);

						$user = trim($obj[3]);
						$duration = trim($obj[2]);
						$type = trim($obj[1]);
						$qty = 1;
						$value = trim(preg_replace('/,/', '.', $obj[3]));

						$_duration = $duration;
						if(preg_match('/(\d+) (year|month)s?/', $duration, $dur)) {
							$duration = $dur[1] * ($dur[2] == 'year' ? 12 : 1) ;
						} else {
							$duration = false;
						}
						if($qty && $user && $duration && $type && $value) {
							$code = generate();
							$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
							if (!$link) {
							    die('Connexion impossible : ' . mysqli_error());
							}
							if($type == 'ojn_premium') {
								$changes++;
								$remain = $value - (0.25 + $value * 0.034);
								$sql = 'INSERT INTO premium SET date="'.date('Y-m-d H:i:s', $date).'", value="'.$value.'", username="'.$user.'", email="'.$mail.'", duration="'.$duration.'", remain="'.$remain.'"';
								mysqli_query($link, $sql);
								if(mysqli_insert_id($link)) {
									sendMail(("L'achat d'un compte premium de ".__tr($_duration)." par $user à ".date('d/m H:i', $date)." a été pris en compte"), utf8_decode("Un achat a été intégré"), $to = "alexis.mellone@gadz.org", $from = "webmaster@openjabnab.fr");
								} else {
									sendMail(("L'achat d'un compte premium de ".date('d/m H:i', $date)." n'a pas été pris en compte"), utf8_decode("Un achat n'a pas été pris en compte"), $to = "alexis.mellone@gadz.org", $from = "webmaster@openjabnab.fr");
								}
							}
							else if($type == 'ojn_giftcode') {
								$changes++;
								$remain = $value - (0.25 + $value * 0.034);

								$duration *= 31;
								$sql = 'INSERT INTO gift SET code="'.$code.'", days="'.$duration.'", genuine=1, buyer="'.addslashes($user).'" ON DUPLICATE KEY UPDATE code="'.$code.'", days="'.$duration.'", genuine=1, buyer="'.addslashes($user).'";';
								$res = mysqli_query($link, $sql);
								if(mysqli_insert_id($link)) {
									sendMail(("L'achat d'un code cadeau (".$code.") de ".__tr($_duration)." par $user à ".date('d/m H:i', $date)." a été pris en compte"), utf8_decode("Un achat a été intégré"), $to = "alexis.mellone@gadz.org", $from = "webmaster@openjabnab.fr");
									sendMail(("Voici le code cadeau pour OpenJabNab qui correspond à votre achat : ".$code."\n\n Merci."), utf8_decode("Votre code cadeau openJabNab"), $to = $mail, $from = "webmaster@openjabnab.fr");
								} else {
									sendMail(("L'achat d'un code cadeau ".date('d/m H:i', $date)." n'a pas été pris en compte"), utf8_decode("Un achat n'a pas été pris en compte"), $to = "alexis.mellone@gadz.org", $from = "webmaster@openjabnab.fr");
								}
							} else {
/*
								$changes++;
								$remain = $value - (0.25 + $value * 0.034);
								$sql = 'INSERT INTO premium SET date="'.date('Y-m-d H:i:s', $date).'", value="'.$value.'", username="'.$user.'", email="'.$mail.'", duration="'.$duration.'", remain="'.$remain.'"';
								mysqli_query($link, $sql);
								mysqli_close($link);
*/
								//sendMail(("L'achat d'un code cadeau de ".__tr($_duration)." par $user à ".date('d/m H:i', $date)." a été pris en compte"), utf8_decode("Un achat a été intégré"), $to = "alexis.mellone@gadz.org", $from = "webmaster@openjabnab.fr");
								sendMail(("L'achat d'un code cadeau de ".__tr($_duration)." par $user à ".date('d/m H:i', $date)." n'a pas été pris en compte"), utf8_decode("Un achat n'a pas été intégré"), $to = "alexis.mellone@gadz.org", $from = "webmaster@openjabnab.fr");
							}
							mysqli_close($link);
						}
						else
						{
							sendMail(("L'achat de ".date('d/m H:i', $date)." n'a pas été pris en compte"), utf8_decode("Un achat n'a pas été pris en compte"), $to = "alexis.mellone@gadz.org", $from = "webmaster@openjabnab.fr");
						}
					}
				}
				/*
				if(isset($value) && isset($user))
				{
					$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
					if (!$link) {
					    die('Connexion impossible : ' . mysqli_error());
					}
					$sql = 'SELECT * FROM don WHERE date="'.date('Y-m-d H:i:s', $date).'" AND value="'.$value.'" AND username="'.$user.'" AND email="'.$email.'"';
					$res = mysqli_query($link, $sql);
					if($row = mysqli_fetch_assoc($res))
					{
						sendMail(("Le don de ".date('d/m H:i', $date)." n'a pas été pris en compte car il existe déjà."), utf8_decode("Un don existait déjà"), $to = "alexis.mellone@gad.org", $from = "webmaster@openjabnab.fr");
					}
					else
					{
						sendMail(("Le don de ".$value."€ de la part de $user à ".date('d/m H:i', $date)." a été pris en compte"), utf8_decode("Un don a été intégré"), $to = "alexis.mellone@gadz.org", $from = "webmaster@openjabnab.fr");
						$changes++;
						$remain = $value - (0.25 + $value * 0.034);
						$sql = 'INSERT INTO don SET date="'.date('Y-m-d H:i:s', $date).'", value="'.$value.'", username="'.$user.'", email="'.$email.'", type="donation", remain="'.$remain.'"';
						mysqli_query($link, $sql);
					}
					mysqli_close($link);
				}
				*/
			}
		}
	}

	imap_close($imap);
	if($changes)
	{
		include './vip_status.php';
	}
}

?>
