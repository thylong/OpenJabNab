<?php
// Définition du content-type
header('Content-Type: image/png');
//ini_set('display_errors', true);


// Le texte à dessiner
$size = 9;
$angle = 90;
$text = $_GET['text'];
// Remplacez le chemin par votre propre chemin de police
$font = '/usr/share/fonts/truetype/msttcorefonts/Arial.ttf';
$bbox = imagettfbbox($size, $angle, $font, $text);
$x1 = min(array($bbox[0],$bbox[2],$bbox[4],$bbox[6]));
$x2 = max(array($bbox[0],$bbox[2],$bbox[4],$bbox[6]));
$y1 = min(array($bbox[1],$bbox[3],$bbox[5],$bbox[7]));
$y2 = max(array($bbox[1],$bbox[3],$bbox[5],$bbox[7]));

// Création de l'image
$im = imagecreatetruecolor(abs($x2 - $x1), abs($y2 - $y1));

// Création de quelques couleurs
$white = imagecolorallocate($im, 255, 255, 255);
$black = imagecolorallocate($im, 0x44, 0x44, 0x44);
imagecolortransparent ($im,$white);
imagefilledrectangle($im, 0, 0, abs($x2 - $x1) - 1, abs($y2 - $y1) - 1, $white);
// Ajout du texte
$c = 1;
if(preg_match_all("|\n|", $text, $match, PREG_SET_ORDER))
	$c = count($match) + 1;
imagettftext($im, $size, $angle, abs($x2 - $x1) -1 , abs($y2 - $y1) -1 , $black, $font, $text);

// Utiliser imagepng() donnera un texte plus claire,
// comparé à l'utilisation de la fonction imagejpeg()
imagepng($im);
imagedestroy($im);
