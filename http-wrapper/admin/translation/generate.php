<?php
require_once "../include/common.php";
if(!isset($_SESSION['token']) || !$Infos['isAdmin'])
	header('Location: /index.php');

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
    Message::AddError(__tr('Connexion impossible') . ' : '. mysqli_error());
	header('Location: translation.php');
	exit;
}
$sql = "SELECT translation.translation, sentence.sentence FROM sentence LEFT JOIN translation ON translation.sentence_id = sentence.id WHERE translation.language='".$_SESSION['elanguage']."' AND ( web='1' OR web='2' ) ORDER BY translation.note ASC, sentence ASC";
$res = mysqli_query($link, $sql);
$t = array();
while($row = mysqli_fetch_assoc($res))
{
	$t[$row['sentence']] = $row['translation'];
}
mysqli_close($link);
$content = "<?php\nglobal \$translations;\n\$translations = ".var_export($t, true).";\n";
file_put_contents("cache/translations.".$_SESSION['elanguage'].".php", $content);

function encodehtml($str)
{
	return preg_replace(array("/'/isU", "/</isU", "/>/isU"), array("&apos;", "&lt;", "&gt;"), $str);
}



$buffer = fopen('/home/prod/OpenJabNab/server/openjabnab_'.$_SESSION['elanguage'].'.ts.php', 'w+');

fwrite($buffer, '<?xml version="1.0" encoding="utf-8"?>'."\n");
fwrite($buffer, '<!DOCTYPE TS>'."\n");
fwrite($buffer, '<TS version="2.0" language="'.$_SESSION['elanguage'].'" sourcelanguage="en">'."\n");
fwrite($buffer, '<context>'."\n");
fwrite($buffer, '    <name>Translator</name>'."\n");
$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
    die('Connexion impossible : ' . mysqli_error());
}

$sql = "SELECT sentence.web, sentence.id, sentence.sentence, translation.translation FROM sentence LEFT JOIN translation ON translation.language='".$_SESSION['elanguage']."' AND translation.sentence_id = sentence.id WHERE (web='0' OR web='2') ORDER BY sentence ASC";
$res = mysqli_query($link, $sql);
while($row = mysqli_fetch_assoc($res))
{
	if(strlen(trim($row['sentence'])))
	{
		fwrite($buffer, '        <message id="'.$row['id'].'">'."\n");
		fwrite($buffer, utf8_encode('            <source>'.encodehtml(utf8_decode($row['sentence'])).'</source>'."\n"));
		fwrite($buffer, utf8_encode('            <translation'.(strlen(trim($row['translation'])) ? '' : ' type="unfinished"').'>'.encodehtml(utf8_decode($row['translation'])).'</translation>'."\n"));
		fwrite($buffer, '        </message>'."\n");
	}
}
fwrite($buffer, '</context>'."\n");
fwrite($buffer, '</TS>'."\n");
mysqli_close($link);
fclose($buffer);
?>
