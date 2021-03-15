<?php
define('DEBUG_DECODE',false);
define('DECODE_STR_CHECK',false);

function deobfuscate($pkt,$p,$len)
{
  $out = '';
  // Deobfuscating algorithm by Sache
  $c = 35;
  ++$p; // Skip first char
  for($i=0;$i<$len;$i++)
  {
    $v = ord($pkt[$p]); ++$p;
    $c = (($v-47)*(1+2*$c))%256;
    $out .= chr($c);
  }
  return $out;
}

function decodePktMessage($pkt, $p, $len)
{
  return array("Message"=>deobfuscate($pkt,$p,$len));
}

function decodePktChorConfig($pkt, $p, $len)
{
  return array("ChorConfig"=>deobfuscate($pkt,$p,$len));
}

function decodePktServiceConfig($pkt, $p, $len)
{
  return array("ServiceConfig"=>deobfuscate($pkt,$p,$len));
}

function decodePktAmbient($pkt,$p,$len)
{
  $out = array();

  $ServicesNames = array(
    0x00 => 'Disable_Service', 
    0x01 => 'Service_Weather', 
    0x02 => 'Service_StockMarket', 
    0x03 => 'Service_Periph', 
    0x04 => 'MoveLeftEar', 
    0x05 => 'MoveRightEar', 
    0x06 => 'Service_EMail', 
    0x07 => 'Service_AirQuality', 
    0x08 => 'Service_Nose', 
    0x09 => 'Service_Custom1', 
    0x0A => 'Service_Custom2', 
    0x0B => 'Service_Custom3', 
    0x0C => 'Service_Custom4', 
    0x0D => 'Service_Custom5', 
    0x0E => 'Service_Custom6', 
    0x0F => 'Service_Custom7', 
    0x10 => 'Service_Custom8', 
    0x11 => 'Service_Custom9', 
    0x12 => 'Service_Custom10', 
    0x21 => 'Service_BottomLed',
    0x22 => 'Service_SoundVol',
    0x23 => 'Service_TaiChi',
    0x25 => 'Service_Debug',
    0x26 => 'Service_Listen',
    0x27 => 'Service_DisableRfid',
  );

  if($len < 6 || ($len%2) != 0)
    die('Bad AmbientPacket size: '.$len);
  $p += 4;  // Ignore 7fff at the beginning
  for($i=4;$i+2<=$len;$i+=2)
  {
    $k = ord($pkt[$p]); ++$p; 
    $v = ord($pkt[$p]); ++$p;
    //var_dump('0x'.dechex($k).' => '.$v);
    if($k != 0x00)
      if(isset($ServicesNames[$k]))
        $out[$ServicesNames[$k]] = $v;
      else
        $out['Unknown0x'.dechex($k)] = $v;
      //$out[$k] = $v;
  }
  return array("Ambient" => $out);
}

function decodePktConfig($pkt,$p,$len)
{
  if($len != 1)
    die('Bad ConfigPacket size: '.$len);
  $v = ord($pkt[$p]);
  if($v < 0 || $v > 1)
    die('Bad ConfigPacket value: '.$v);
  return array("Sleep" => $v);
}

function decodePktSleep($pkt,$p,$len)
{
  if($len != 1)
    die('Bad SleepPacket size: '.$len);
  $v = ord($pkt[$p]);
  if($v < 0 || $v > 1)
    die('Bad SleepPacket value: '.$v);
  return array("Sleep" => $v);
}

define('pktAmbient',      0x04);
define('pktServiceconfig',0x06);
define('pktChorconfig',   0x07);
define('pktConfig',       0x08);
define('pktReboot',       0x09);
define('pktMessage',      0x0A);
define('pktSleep',        0x0B);

function decodePkt($pkt,$pkt_len)
{
  if( ord($pkt[0]) != 0x7F || ord($pkt[$pkt_len-1]) != 0xFF)
    die("Invalid packet header/footer");
  $p = 1;
  $out = array();
  while($p+4 < $pkt_len-1)
  {
    //var_dump('Pos '.$p.'/'.$pkt_len.' 0x'.dechex($p).'/0x'.dechex($pkt_len));
    $type = ord($pkt[$p]); $p++;;
    $len = (ord($pkt[$p]) << 16) | (ord($pkt[$p+1]) << 8) | (ord($pkt[$p+2])); $p+= 3;
    //var_dump('Type: 0x'.dechex($type));
    //var_dump(' Len:   '.$len);
    if($p+$len > $pkt_len-1)
      die('Bad packet length : '.$len.'/'.$pkt_len);
    $v = NULL;
    switch($type)
    {
      case pktAmbient:
        //var_dump("AMBIENT");
        $v = decodePktAmbient($pkt,$p,$len);
        break;
      case pktServiceconfig:
        //var_dump("SERVICE_CONFIG");
        $v = decodePktServiceConfig($pkt,$p,$len);
        break;
      case pktChorconfig:
        //var_dump("CHOR_CONFIG");
        $v = decodePktChorConfig($pkt,$p,$len);
        break;
      case pktConfig:
        //var_dump("CONFIG");
        $v = decodePktConfig($pkt,$p,$len);
        break;
      case pktReboot:
        //var_dump("REBOOT");
        $v = decodePktReboot($pkt,$p,$len);
        break;
      case pktMessage:
        //var_dump("MESSAGE");
        $v = decodePktMessage($pkt,$p,$len);
        break;
      case pktSleep:
        //var_dump("SLEEP");
        $v = decodePktSleep($pkt,$p,$len);
        break;
      default:
        die('Unknown packet type 0x'.bin2hex($pkt[$p]));
    }
    if($v !== NULL)
      $out = array_merge($out,$v);
    $p += $len;
  }
  return $out;
}

function decodePktFromStr($str)
{
  $pkt = base64_decode($str);
  $pkt_len = strlen(bin2hex($pkt))/2;
  //var_dump(strlen($pkt));
  return decodePkt($pkt,$pkt_len);
}

if (http_response_code()===false) 
{
  // CallURL (broken)
  //$str = "fwoAAAcAOwiP1pBl/w==";
  // Ambient + Sleep
  //$str = 'fwQAABR////+BAAFAAgAIQEiACMKJgAnAAsAAAEA/w==';
  // Play Audio (TTS File)
  //$str = 'fwoAAF4A+v6PEfny7vv6xsCbhiAF5ZrDtizGU3Y7gyqo/bAoP36Bvl4hiNF65mCi3IlpIkko1qJTnNyJHsasHQw/oS2z9R8IcDex084pGe3Z08xo2ThFmo4/0YWAf8KViCRF/w==';
  // Stream (Webradio)
  //$str = 'fwoAADcAxLtPl2ODH6nCYOJ/wuNujNE+VKstNQTVPlK5hGnuSGJsnj/jp3iE6u4F8r80oqmAf8KViCRF/w==';
  // Reboot (Message (T_T") )
  //$str = 'fwoAAAQATckx/w==';
  // Call URL
  //$str = 'fwoAAFoAVHKPl2ODH6nCYEvs0VVqvzS5oYwlxYQlhc1psiAF5ZrDtizGU3bayAJFpqjqv1UkES0pnS096eZ/JRgC4j8wAIKhLjdkFdMcGJkkmlTi7ijEpry6+YB/wpX/';
  //$str = 'fwoAAKsA+v6PEfny7vv6xsCbhiAF5ZrDtizGU3Y7gyqobhDG/zTz+FSsY/iStbCFeb6Y3DJRdNEouhc0UelWoqJrzgsggKJoLXQ/hFvDzilHVqzRgH/ClYgkRYj+jxH58u77+sbAm4YgBeWaw7YsxlN2O4MqqG4Qxv808/hUrGP4krWwhXm+mLkfJQii4j/GjpcgFVO4PwjFn1/jgLXp+gZcC4GfoVZtxYB/wpWIJEX/';
  $str = 'fwoAABMAVHKPl2ODH6nCYIlpsL75zWlx/w==';
  //var_dump($str);
  var_dump(decodePktFromStr($str));
}
