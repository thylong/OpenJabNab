<?php
require_once "include/common.php";
if(!isset($_SESSION['token']) || !$Infos['isAdmin'] || !isset($account))
	header('Location: index.php');

if(!function_exists('getSize')) {
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
}

$settings = $account['settings'];
$Sets = $settings;

$version = getSize(substr($settings, 0, 4));
$settings = substr($settings, 4);
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

$decal = false;
if($version == 2)
{
	if(substr($settings, 4, 1) != chr(8))
	{
		$settings = substr($settings, 8);
	}
}
else if($version == 3)
{
	if(substr($settings, 19, 1) != chr(8))
	{
		$decal = true;
	}
}
else if($version == 4)
{
	if(substr($settings, 19 + 13, 1) != chr(8))
	{
		$decal = true;
	}
}

$admin = ord(substr($settings, 0, 1));
$settings = substr($settings, 1);

$logincount = "n/a";
$abusecount = "n/a";

if($version == 3 || $version == 4)
{
	$premium = ord(substr($settings, 0, 1));
	$settings = substr($settings, 1);

	$vip = ord(substr($settings, 0, 1));
	$settings = substr($settings, 1);

	$logincount = getSize(substr($settings, 0, 4));
	$settings = substr($settings, 4);

	$lastlogindate = getSize(substr($settings, 0, 4));
	$settings = substr($settings, 4);
	$lastlogintime = getSize(substr($settings, 0, 4));

	$lastlogin = (($lastlogindate - 2440587.5) * 86400 - (86400 / 2) - 3600) + (int)($lastlogintime / 1000);
	$settings = substr($settings, 4);
	$lastloginformat = ord(substr($settings, 0, 1));
	$settings = substr($settings, 1);
	
	if($version == 4)
	{
		$abusecount = getSize(substr($settings, 0, 4));
		$settings = substr($settings, 4);

		$bandate = getSize(substr($settings, 0, 4));
		$settings = substr($settings, 4);
		$bantime = getSize(substr($settings, 0, 4));

		$ban = (($bandate - 2440587.5) * 86400 - (86400 / 2) - 3600) + (int)($bantime / 1000);
		$settings = substr($settings, 4);
		$banformat = ord(substr($settings, 0, 1));
		$settings = substr($settings, 1);
	}
	
	if($decal)
		$settings = substr($settings, 8);
}

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

?>
