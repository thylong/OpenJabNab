<?php

function getSize($str)
{
  if($str == chr(255).chr(255).chr(255).chr(255))
    return 0;
  $s = "";
  for($i=0; $i<strlen($str); $i++)
    $s .= str_pad(dechex(ord($str[$i])), 2, "0", STR_PAD_LEFT);
  return hexdec($s);
}

function decodeBool($settings, $p)
{
  $out = ord(substr($settings, $p, 1)); $p+=1;            // Value
  return array($p,$out);
}

function decodeInt($settings, $p)
{
  $out = getSize(substr($settings, $p, 4)); $p += 4;     // Value
  return array($p,$out);
}

function decodeByteArray($settings, $p)
{
  $len  = getSize(substr($settings, $p, 4)); $p += 4;     // Get Key length
  $out  = substr($settings, $p, $len); $p += $len;         // Get Key
  return array($p, $len ? $out : NULL);
}

function decodeDateTime($settings, $p)
{
  $date = getSize(substr($settings, $p, 4));  $p += 4;  // Date
  $time = getSize(substr($settings, $p, 4));  $p += 4;  // Time
  $p += 1;                                              // Format ?
  $out = (($date - 2440587.5) * 86400 - (86400 / 2) - 3600) + (int)($time / 1000);
  return array($p,$out);
}

function decodeStr($settings, $p)
{
  $len  = getSize(substr($settings, $p, 4)); $p += 4;     // Get Key length
  $out = substr($settings, $p, $len); $p += $len;         // Get Key
  $out = str_replace("\0",'', $out);                      // QtChar are on two bytes
  if($len != 2*strlen($out))
    die('String length ('.$len.') doesn\'t match decoded string length ('.(strlen($out)*2).')');
  return array($p, $len ? $out : NULL);
}

function decodeList($settings, $p, $cb)
{
  $out = array();
  $n = getSize(substr($settings, $p, 4));  $p += 4;     // Count
  for($j=0;$j<$n;$j++)
  {
    list($p,$v) = $cb($settings, $p);
    if($v !== NULL)
      $out[] = $v;
  }
  return array($p,$out);
}

function decodeVal($settings,$p)
{
  $type  = getSize(substr($settings, $p, 4)); $p += 4;      // Get Value type
  $p += 1;                                                  // ???
  //var_dump('Type: 0x'.dechex($type));
  switch($type)
  {
    case 0x00000001:  // BOOL
      //var_dump("BOOL");
      list($p,$v) = decodeBool($settings,$p);
      break;
    case 0x00000002:  // INT
      //var_dump("INT");
      list($p,$v) = decodeInt($settings,$p);
      break;
    case 0x00000008:  // MAP
      //var_dump('MAP for key '.$key);
      //var_dump('  Recurse for: '.$key.' at '.dechex($p));
      list($p,$v) = decodeSettings($settings,$p,false);
      //var_dump('  End Recurse: '.dechex($p));
      break;
    case 0x0000000A:  // STRING
      //var_dump("STRING");
      list($p,$v) = decodeStr($settings,$p);
      break;
    case 0x0000000B:
      //var_dump("STRLIST");
      list($p,$v) = decodeList($settings,$p,'decodeStr');
      break;
    case 0x0000000C:  // BYTEARRAY
      //var_dump("BYTEARRAY");
      list($p,$v) = decodeByteArray($settings,$p);
      break;
    case 0x00000010:  // DATETIME
      //var_dump("DATETIME");
      list($p,$v) = decodeDateTime($settings,$p);
      //$v = date("d/m/Y H:i:s", $v);
      break;
    default:
      var_dump('UNKNOWN TYPE '.$type.' for key '.$key.' at '.$p);
      die;
  };
  return array($p, $v);
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
    list($p,$key) = decodeStr($settings,$p);
    //var_dump('Key: '.$key);
    $v = NULL;
    if($recurse)
    {
      //var_dump('Recurse for: '.$key);
      list($p,$v) = decodeSettings($settings, $p, false);
      //var_dump('End Recurse: '.dechex($p));
    }
    else
      list($p,$v) = decodeVal($settings, $p);
    if($v !== NULL)
    {
      //var_dump($v);
      $out[$key] = $v;
    }
  }
  return array($p, $out);
}

function decodeBunnySettings($settings)
{
  // From bunny.cpp
  // GlobalSettings << PluginsSettings << listOfPlugins << knownRFIDTags
  $p = 0;
  $out = array();
  list($p, $out['GlobalSettings'])  = decodeSettings($settings, $p);
  list($p, $out['PluginsSettings']) = decodeSettings($settings, $p, true);
  list($p, $out['listOfPlugins'])   = decodeList($settings, $p,'decodeStr');
  list($p, $out['knownRFIDTags'])   = decodeList($settings, $p,'decodeByteArray');
  return $out;
}

function decodeAccountSettings($settings)
{
  // From account.cpp
  // v1: >> login >> username >> passwordHash                      >> isAdmin                                                                            >> UserAccess >> listOfBunnies >> listOfZtamps;
  // v2: >> login >> username >> passwordHash >> language >> email >> isAdmin                                                                            >> UserAccess >> listOfBunnies >> listOfZtamps;
  // v3: >> login >> username >> passwordHash >> language >> email >> isAdmin >> isPremium >> isVip >> loginCount >> lastLogin                           >> UserAccess >> listOfBunnies >> listOfZtamps;
  // v4: >> login >> username >> passwordHash >> language >> email >> isAdmin >> isPremium >> isVip >> loginCount >> lastLogin >> abuseCount >> startBan >> UserAccess >> listOfBunnies >> listOfZtamps;
  $p = 0;
  $out = array();
  list($p,$out['version'])  = decodeInt($settings,$p);
  list($p,$out['login'])    = decodeStr($settings,$p);
  list($p,$out['username']) = decodeStr($settings,$p);
  list($p,$out['pwd_hash']) = decodeByteArray($settings,$p); $out['pwd_hash'] = bin2hex($out['pwd_hash']);
  if($out['version'] >= 2)
  {
    list($p,$out['language']) = decodeStr($settings,$p);
    list($p,$out['email'])    = decodeStr($settings,$p);
  }
  list($p,$out['isAdmin']) = decodeBool($settings,$p);
  if($out['version'] >= 3)
  {
    list($p,$out['isPremium'])  = decodeBool($settings,$p);
    list($p,$out['isVip'])      = decodeBool($settings,$p);
    list($p,$out['loginCount']) = decodeInt($settings,$p);
    list($p,$out['lastLogin'])  = decodeDateTime($settings,$p); //$out['lastLogin'] = date("d/m/Y H:i:s", $out['lastLogin']);
    if($out['version'] >= 4)
    {
      list($p,$out['abuseCount']) = decodeInt($settings,$p);
      list($p,$out['startBan'])   = decodeDateTime($settings,$p); //$out['startBan'] = date("d/m/Y H:i:s", $out['startBan']);
    }
  }
  list($p,$out['UserAccess'])     = decodeList($settings, $p,'decodeInt');
  list($p,$out['listOfBunnies'])  = decodeList($settings, $p,'decodeByteArray');
  list($p,$out['listOfZtamps'])   = decodeList($settings, $p,'decodeByteArray');
  return $out;
}

if (http_response_code()===false) 
{
  if(count($argv) != 3) die('Incorrect args');
  $data = file_get_contents($argv[2]);
  switch($argv[1])
  {
    case 'bunny':
      var_dump(decodeBunnySettings($data));
      break;
    case 'account':
      var_dump(decodeAccountSettings($data));
      break;
    default:
      die('Unknown settings');
  };
}

?>
