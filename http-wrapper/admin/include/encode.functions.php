<?php
function echoInt($i)
{
	$hex = str_split(str_pad(dechex($i), 8, "0", STR_PAD_LEFT), 2);
	return chr(hexdec($hex[0])).chr(hexdec($hex[1])).chr(hexdec($hex[2])).chr(hexdec($hex[3]));
}

function echoBool($i)
{
	return chr(hexdec($i));
}

function echoStr($str)
{
	$str = str_split($str);
	$tmp = "";
	foreach($str as $s)
		$tmp .= $s;
	return echoInt(strlen($tmp)).$tmp;
}

function echoString($str)
{
	if(strlen($str))
	{
		$str = str_split($str);
		$tmp = "";
		foreach($str as $s)
			$tmp .= chr(0).$s;
		return echoInt(strlen($tmp)).$tmp;
	}
	return chr(255).chr(255).chr(255).chr(255);
}

function echoBytearray($str)
{
	$str = str_split($str, 2);
	$tmp = "";
	foreach($str as $s)
		$tmp .= chr(hexdec($s));
	return echoInt(strlen($tmp)).$tmp;
}
function echoArrayInt($array)
{
	$tmp = echoInt(count($array));
	foreach($array as $i)
		$tmp .= echoInt($i);
	return $tmp;
}
function echoArrayStr($array)
{
	$tmp = echoInt(count($array));
	foreach($array as $i)
		$tmp .= echoStr($i);
	return $tmp;
}
