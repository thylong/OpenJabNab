<?php

$content = base64_decode($_POST['file']);
file_put_contents("confs/conf_" . $_POST['mac'] . ".bin", $content);
header("Content-Type: application/force-download; name=\"conf_" . $_POST['mac'] . ".bin\"");
header("Content-Transfer-Encoding: binary");
header("Content-Length: ".strlen($content));
header("Content-Disposition: attachment; filename=\"conf_" . $_POST['mac'] . ".bin\"");
header("Expires: 0");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

echo $content;
?>
