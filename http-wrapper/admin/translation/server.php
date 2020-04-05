<?php
require_once "../include/common.php";
if(!isset($_SESSION['token']) || !$Infos['isAdmin'])
	header('Location: /index.php');

$new = 0;
$dir = dirname(__FILE__);
$languages = array('fr');
$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
    die('Connexion impossible : ' . mysqli_error());
}

foreach($languages as $lng)
{
	$file = ROOT_SERVER . 'openjabnab_' . $lng . '.ts';
	if(file_exists($file))
	{
		$xml = simplexml_load_string(file_get_contents($file));
		foreach($xml->context as $messages)
		{
			foreach($messages as $message)
			{
				if(isset($message->source))
				{
					$source = (string)($message->source);
					$translation = "";
					if(isset($message->translation) && $message->translation != "")
					{
						$translation = (string)($message->translation);
					}
					$sql = "SELECT * FROM sentence WHERE sentence = \"".addslashes($source)."\"";
					$res = mysqli_query($link, $sql);
					$id = 0;
					if($res)
					{
						if(mysqli_num_rows($res) == 0)
						{
							$new++;
							$sql = "INSERT INTO sentence SET sentence = \"".addslashes($source)."\", web='0'";
							$res = mysqli_query($link, $sql);
							$id = mysqli_insert_id($link);
						}
						else
						{
							if($row = mysqli_fetch_assoc($res))
							{
								$id = $row['id'];
								$web = $row['web'];
								if($web == 1)
								{
									mysqli_query($link, "UPDATE sentence set web=2 WHERE id='".$id."'");
								}
								if($web == 3)
								{
									mysqli_query($link, "UPDATE sentence set web=0 WHERE id='".$id."'");
								}
							}
						}

						if($id && $translation != "")
						{
							$sql = "INSERT INTO translation SET sentence_id='".$id."', translation='".addslashes($translation)."', language='".$lng."', note=0 ON DUPLICATE KEY UPDATE note=0";
							$res = mysqli_query($link, $sql);
						}
					}
					else
					{
						Message::AddError(__tr("Can't add sentence : %1", $source));
					}
				}
			}
		}
	}
}

mysqli_close($link);
?>
