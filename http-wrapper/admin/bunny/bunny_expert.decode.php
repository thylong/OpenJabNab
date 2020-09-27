<?php
require_once "../include/common.php";
if(!isset($_SESSION['token']) || !$Infos['isAdmin'] || !isset($bunny))
	header('Location: index.php');

// GlobalSettings << PluginsSettings << listOfPlugins << knownRFIDTags
function getSize($str)
{
	if($str == chr(255).chr(255).chr(255).chr(255))
		return 0;
	$s = "";
	for($i=0; $i<strlen($str); $i++)
	{
		$s .= str_pad(dechex(ord($str[$i])), 2, "0", STR_PAD_LEFT);
	}
	return hexdec($s);
}
function clean($str)
{
	return preg_replace("|".'\0'."|isU", "", $str);
}
$globalsettings = array();
$settings = $bunny['settings'];
$Sets = $settings;
$length = getSize(substr($settings, 0, 4));
$settings = substr($settings, 4);

for($i = 0; $i < $length; $i++)
{
	$_length = getSize(substr($settings, 0, 4));
	$_name = clean(substr($settings, 4, $_length));
	$settings = substr($settings, 4 + $_length);
	if($_name == "Last JabberDisconnection")
	{
		$l = 10;
		$c = "To be decoded";
		//$c = getSize(substr($settings, 4, $l));
		//$c = date("d/m/Y H:i:s", $c);
		$settings = substr($settings, 4 + $l);
	}
	else if($_name == "BunnyPassword")
	{
		$settings = substr($settings, 5);
		$l = getSize(substr($settings, 0, 4));
		$c = clean(substr($settings, 4, $l));
		$settings = substr($settings, 4 + $l);
	}
	else
	{
		$l = getSize(substr($settings, 0, 4));
		$c = clean(substr($settings, 4, $l));
		$settings = substr($settings, 4 + $l);
	}
	$globalsettings[$_name] = $c;
}
//var_dump($globalsettings);

/*

$length = getSize(substr($settings, 0, 4));
$login =  clean(substr($settings, 4, $length));

$settings = substr($settings, 4 + $length);
$length = getSize(substr($settings, 0, 4));
$username =  clean(substr($settings, 4, $length));
$settings = substr($settings, 4 + $length);
$length = getSize(substr($settings, 0, 4));
$passwordhash =  substr($settings, 4, $length);
$settings = substr($settings, 4 + $length);
$length = getSize(substr($settings, 0, 4));
$language =  clean(substr($settings, 4, $length));
$settings = substr($settings, 4 + $length);

$length = getSize(substr($settings, 0, 4));
$email =  clean(substr($settings, 4, $length));
$settings = substr($settings, 4 + $length);

if(substr($settings, 4, 1) != chr(8))
	$settings = substr($settings, 8);
$admin = ord(substr($settings, 0, 1));
$settings = substr($settings, 1);

$error = false;
$nbr = getSize(substr($settings, 0, 4));
if($nbr > $max)
{
	$error = true;
	$nbr = $max;
}
$rights = array();
$settings = substr($settings, 4);
for($i = 0; $i<$nbr; $i++)
	$rights[$i] = getsize(substr($settings, 4 * $i, 4));
$settings = substr($settings, 4 * $i);


$nbr = getSize(substr($settings, 0, 4));
if($nbr > $max)
{
	$error = true;
	$nbr = $max;
}
$bunny2 = array();
$settings = substr($settings, 4);
for($i = 0; $i<$nbr; $i++)
{
	$length = getSize(substr($settings, 0, 4));
	$bunny2[$i] = substr($settings, 4, $length);
	$settings = substr($settings, 4 + $length);
}


$nbr = getSize(substr($settings, 0, 4));
if($nbr > $max)
{
	$error = true;
	$nbr = $max;
}
$ztamp2 = array();
$settings = substr($settings, 4);
for($i = 0; $i<$nbr; $i++)
{
	$length = getSize(substr($settings, 0, 4));
	$ztamp2[$i] = substr($settings, 4, $length);
	$settings = substr($settings, 4 + $length);
}

$nbr = getSize(substr($settings, 0, 4));
if($nbr > $max)
{
	$error = true;
	$nbr = $max;
}
$bunny = array();
$settings = substr($settings, 4);
for($i = 0; $i<$nbr; $i++)
{
	$length = getSize(substr($settings, 0, 4));
	$bunny[$i] = substr($settings, 4, $length);
	$settings = substr($settings, 4 + $length);
}


$nbr = getSize(substr($settings, 0, 4));
if($nbr > $max)
{
	$error = true;
	$nbr = $max;
}
$ztamp = array();
$settings = substr($settings, 4);
for($i = 0; $i<$nbr; $i++)
{
	$length = getSize(substr($settings, 0, 4));
	$ztamp[$i] = substr($settings, 4, $length);
	$settings = substr($settings, 4 + $length);
}


if(count($rights) == 0 && count($bunny) == 0 && count($ztamp) == 0) {
$email = "";
$language = "fr";

	$settings = $Sets;
	$i = substr($settings, 0, 4);
	$settings = substr($settings, 4);
	$length = getSize(substr($settings, 0, 4));
	$login =  clean(substr($settings, 4, $length));
	$settings = substr($settings, 4 + $length);
	$length = getSize(substr($settings, 0, 4));
	$username =  clean(substr($settings, 4, $length));
	$settings = substr($settings, 4 + $length);


	//for($i = 0; $i<strlen($settings); $i++)
	//	echo str_pad(dechex(ord($settings[$i])), 2, "0", STR_PAD_LEFT) . " ";

	$length = getSize(substr($settings, 0, 4));
	$passwordhash =  substr($settings, 4, $length);
	$settings = substr($settings, 4 + $length);

	$settings = substr($settings, 8);
	$settings = substr($settings, 8);
	$admin = ord(substr($settings, 0, 1));
	$settings = substr($settings, 1);

	//for($i = 0; $i<strlen($settings); $i++)
	//	echo str_pad(dechex(ord($settings[$i])), 2, "0", STR_PAD_LEFT) . " ";

	$nbr = getSize(substr($settings, 0, 4));
if($nbr > $max)
{
	$error = true;
	$nbr = $max;
}
	$rights = array();
	$settings = substr($settings, 4);
	for($i = 0; $i<$nbr; $i++)
	$rights[$i] = getsize(substr($settings, 4 * $i, 4));
	$settings = substr($settings, 4 * $i);


	$nbr = getSize(substr($settings, 0, 4));
	if($nbr > $max)
	$nbr = $max;
	$bunny2 = array();
	$settings = substr($settings, 4);
	for($i = 0; $i<$nbr; $i++)
	{
	$length = getSize(substr($settings, 0, 4));
	$bunny2[$i] = substr($settings, 4, $length);
	$settings = substr($settings, 4 + $length);
	}


	$nbr = getSize(substr($settings, 0, 4));
if($nbr > $max)
{
	$error = true;
	$nbr = $max;
}
	$ztamp2 = array();
	$settings = substr($settings, 4);
	for($i = 0; $i<$nbr; $i++)
	{
	$length = getSize(substr($settings, 0, 4));
	$ztamp2[$i] = substr($settings, 4, $length);
	$settings = substr($settings, 4 + $length);
	}

	$nbr = getSize(substr($settings, 0, 4));
if($nbr > $max)
{
	$error = true;
	$nbr = $max;
}
	$bunny = array();
	$settings = substr($settings, 4);
	for($i = 0; $i<$nbr; $i++)
	{
	$length = getSize(substr($settings, 0, 4));
	$bunny[$i] = substr($settings, 4, $length);
	$settings = substr($settings, 4 + $length);
	}


	$nbr = getSize(substr($settings, 0, 4));
if($nbr > $max)
{
	$error = true;
	$nbr = $max;
}
	$ztamp = array();
	$settings = substr($settings, 4);
	for($i = 0; $i<$nbr; $i++)
	{
	$length = getSize(substr($settings, 0, 4));
	$ztamp[$i] = substr($settings, 4, $length);
	$settings = substr($settings, 4 + $length);
	}
}

$bunnies = array();
$lapins = array_unique(array_merge($bunny, $bunny2));
foreach($lapins as $i => $lapin)
{
	if(preg_match("|([0-9a-f]{2}):?([0-9a-f]{2}):?([0-9a-f]{2}):?([0-9a-f]{2}):?([0-9a-f]{2}):?([0-9a-f]{2})|isU", $lapin, $match))
		$bunnies[] = strtolower($match[1].$match[2].$match[3].$match[4].$match[5].$match[6]);
}
$bunnies = array_unique($bunnies);
$ztamps = array();
$ztps = array_unique(array_merge($ztamp, $ztamp2));
foreach($ztps as $i => $ztp)
{
	if(preg_match("|([0-9a-f]{2}):?([0-9a-f]{2}):?([0-9a-f]{2}):?([0-9a-f]{2}):?([0-9a-f]{2}):?([0-9a-f]{2}):?([0-9a-f]{2}):?([0-9a-f]{2})|isU", $ztp, $match))
		$ztamps[] = strtolower($match[1].$match[2].$match[3].$match[4].$match[5].$match[6].$match[7].$match[8]);
}
$ztamps = array_unique($ztamps);

if($language == '')
	$language = 'fr';
//echo $settings;
// http://medias.lequipe.fr/logo-football/1615/20
*/

?>
