<?php

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

function decodeList($settings, $p)
{
  $out = array();
  $n = getSize(substr($settings, $p, 4));  $p += 4;     // Count
  for($j=0;$j<$n;$j++)
  {
    $l = getSize(substr($settings, $p, 4));  $p += 4;   // Length
    $out[] = clean(substr($settings, $p, $l)); $p += $l;  // Content
  }
  return array($p,$out);
}

function decodeSettings($settings, $p, $recurse=false)
{
  $out = array();
  $nb = getSize(substr($settings, $p, 4)); $p += 4;           // Get config length
  //var_dump('Config items:'.$nb);
  for($i=0; $i<$nb; $i++)
  {
    //echo '<hr />';
    //var_dump('Pos 0x'.strtoupper(dechex($p)));
    $klen  = getSize(substr($settings, $p, 4)); $p += 4;      // Get Key length
    $key = clean(substr($settings, $p, $klen)); $p += $klen;  // Get Key
    //var_dump($key);
    if($klen != 2*strlen($key))
      die("Key length doesn't match string length");
    $v = NULL;
    if($recurse)
    {
      //var_dump('Recurse for: '.$key);
      list($p,$v) = decodeSettings($settings, $p, false);
      //var_dump('End Recurse: '.dechex($p));
    }
    else
    {
      $type  = getSize(substr($settings, $p, 4)); $p += 4;      // Get Value type
      $p += 1;                                                  // ???
      //var_dump($klen);
      //var_dump($key);
      //var_dump($type);
      switch($type)
      {
        case 0x00000001:  // BOOL
          //var_dump("BOOL");
          $v = ord(substr($settings, $p, 1)); $p+=1;            // Value
          break;
        case 0x00000002:  // INT
          //var_dump("INT");
          $v  = getSize(substr($settings, $p, 4)); $p += 4;     // Value
          break;
        case 0x00000008:  // MAP
          //var_dump('MAP for key '.$key);
          //var_dump('  Recurse for: '.$key.' at '.dechex($p));
          list($p,$v) = decodeSettings($settings,$p,false);
          //var_dump('  End Recurse: '.dechex($p));
          break;
        case 0x0000000A:  // STRING
          //var_dump("STRING");
          $l  = getSize(substr($settings, $p, 4));  $p += 4;    // Length
          $v = clean(substr($settings, $p, $l));    $p += $l;   // Content
          break;
        case 0x0000000B:
          //var_dump("STRLIST");
          list($p,$v) = decodeList($settings,$p);
          break;
        case 0x0000000C:  // BYTEARRAY
          //var_dump("BYTEARRAY");
          $l  = getSize(substr($settings, $p, 4));  $p += 4;    // Length
          $v = clean(substr($settings, $p, $l));    $p += $l;   // Content
          break;
        case 0x00000010:  // DATETIME
          //var_dump("DATETIME");
          $date = getSize(substr($settings, $p, 4));  $p += 4;  // Date
          $time = getSize(substr($settings, $p, 4));  $p += 4;  // Time
          $p += 1;                                              // Format ?
          $v =(($date - 2440587.5) * 86400 - (86400 / 2) - 3600) + (int)($time / 1000);
          $v = date("d/m/Y H:i:s", $v);
          break;
        default:
          var_dump('UNKNOWN TYPE '.$type.' for key '.$key.' at '.$p);
          die;
      };
    }
    if($v !== NULL)
    {
      //var_dump($v);
      $out[$key] = $v;
    }
  }
  return array($p, $out);
}
?>
