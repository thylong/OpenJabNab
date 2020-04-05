<?php
require_once "include/common.php";
$translates = getTranslates($_SESSION['login']);
if(!isset($_SESSION['token']) || (!$Infos['isAdmin'] && !in_array($_GET['lng'], $translates)))
	header('Location: index.php');

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
    Message::AddError(__tr('Connexion impossible') . ' : '. mysqli_error());
	header('Location: translation.php');
	exit;
}
$sql = "SELECT translation.translation, sentence.sentence FROM sentence LEFT JOIN translation ON translation.sentence_id = sentence.id WHERE translation.language='".$_GET['lng']."' AND ( web='1' OR web='2' ) ORDER BY translation.note ASC, sentence ASC";
$res = mysqli_query($link, $sql);
$t = array();
while($row = mysqli_fetch_assoc($res))
{
	$t[$row['sentence']] = $row['translation'];
}
mysqli_close($link);
$content = "<?php\nglobal \$translations;\n\$translations = ".var_export($t, true).";\n";
file_put_contents("cache/translations.".$_GET['lng'].".php", $content);
?>
