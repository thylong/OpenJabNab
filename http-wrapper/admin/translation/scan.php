<?php
require_once "../include/common.php";

$new = 0;
$dir = dirname(__FILE__);
function scan($dir, $files = array())
{
	if (is_dir($dir)) {
	    if ($dh = opendir($dir)) {
		while (($file = readdir($dh)) !== false) {
			if(is_dir($dir ."/".$file) === true) {
				if($file != "." && $file != ".." ) {
					$files = scan($dir."/".$file, $files);
				}
			} else {
				$files[] = $dir."/".$file;
			}
		}
	    }
	}
	return $files;
}
$tr = array();
$files = scan($dir);
foreach($files as $file) {
	$content = file_get_contents($file);
	if(preg_match_all("|__tr\((['\"])(.*)\\1[\),]|sU", $content, $match))
	{
		foreach($match[2] as $s) {
			$s = trim(stripslashes($s));
			$tr[$s] = "";
		}
	}
}
$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
    die('Connexion impossible : ' . mysqli_error());
}
foreach($tr as $s => $t)
{
	$sql = "SELECT * FROM sentence WHERE sentence = \"".addslashes($s)."\"";
	$res = mysqli_query($link, $sql);
	if($res)
	{
		if(mysqli_num_rows($res) == 0)
		{
			$new++;
			$sql = "INSERT INTO sentence SET sentence = \"".addslashes($s)."\", web=1";
			$res = mysqli_query($link, $sql);
		}
		else
		{
			if($row = mysqli_fetch_assoc($res))
			{
				$id = $row['id'];
				if($row['web'] == 0)
				{
					mysqli_query($link, "UPDATE sentence SET web=2 WHERE id='$id';");
				}
				if($row['web'] == 3)
				{
					mysqli_query($link, "UPDATE sentence SET web=1 WHERE id='$id';");
				}
			}
		}
	}
	if(!$res)
	{
		Message::AddError(__tr("Can't add sentence : %1", $s));
	}
}
mysqli_close($link);
?>
