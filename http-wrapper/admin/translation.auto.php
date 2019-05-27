<?php
function getTr($text, $lng, $orig)
{
	$url = "http://translate.google.fr/translate_a/t?client=t&text=".urlencode($text)."&hl=".$lng."&sl=".$orig."&tl=".$lng."&ie=UTF-8&oe=UTF-8&multires=1&otf=2&ssel=3&tsel=6&sc=1";
	$content = file_get_contents($url);
	if($content && preg_match("|\[\[\[\"(.*)\",\"".$text."\",\"\",\"\"\]|isU", $content, $match))
	{
		return ucfirst($match[1]);
	}
	return "";
}

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
    Message::AddError(__tr('Connexion impossible') . ' : '. mysqli_error());
	header('Location: translation.php');
	exit;
}
$sql = "SELECT * FROM sentence WHERE id NOT IN (SELECT sentence_id FROM translation WHERE language='".$_SESSION['elanguage']."')";
$res = mysqli_query($link, $sql);
$t = array();
while($row = mysqli_fetch_assoc($res))
{
	$t[$row['id']] = $row['sentence'];
}
$count = 0;
$total = 0;
foreach($t as $id => $s)
{
	$total++;
	$t = getTr($s, $_SESSION['elanguage'], 'en');
	if(strlen(trim($t))) {
		$count ++;
		$sql = "INSERT INTO translation SET sentence_id='".$id."', translation='".addslashes($t)."', language='".$_SESSION['elanguage']."', note=0 ON DUPLICATE KEY UPDATE translation='".addslashes($t)."', language='".$_SESSION['elanguage']."', note=0";
		$res = mysqli_query($link, $sql);
	}
}
Message::AddSuccess(__tr("%1 out of %2 sentences were automatically translated", $count, $total));
mysqli_close($link);
?>
