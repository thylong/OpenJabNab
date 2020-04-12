<?php
function Slugify($text)
{
	if (function_exists('iconv'))
	    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
	return $text;
}

$url = 'http://www.journee-mondiale.com/les-journees-mondiales.htm';
//<li><a href="https://www.journee-mondiale.com/5/journee-mondiale-de-la-paix.htm"><time datetime="1er janvier">1er janvier</time> : Journée Mondiale de la Paix</a></li>

$content = file_get_contents($url);

echo "inline void PluginDayof::InitData()\n{\n";
$slugified = array();
if(preg_match_all('`<li><a href="https?:\/\/www.journee-mondiale.com\/\d+\/[^\"]+"><time datetime="[^\"]+">([^<]*)<\/time> : ([^<]*)<\/a><\/li>`', $content, $match, PREG_SET_ORDER))
{
  foreach($match as $line)
  {
    if(count($line) != 3)
    {
      echo 'erreur en '.$line;
      die();
    }
    $day   = $line[1];
    $month = explode(' ', $day);
    $day = (int)(preg_replace('~\D~', '', $month[0]));
    $month = Slugify($month[1]);
    $slugified[$month][$day][] = $line[2];
  }
}
$mois = array('','janvier', 'fevrier', 'mars', 'avril', 'mai', 'juin', 'juillet', 'aout', 'septembre', 'octobre', 'novembre', 'decembre');
foreach($slugified as $month => $days)
{
  $k = array_search($month, $mois);
  if($k == false)
  {
    echo 'erreur en '.$month;
    die();
  }
  echo '  QMultiMap<int,QString> data_'.$k.";\n";
  foreach($days as $d => $vs)
    foreach($vs as $v)
      echo '    '.'data_'.$k.'.insert('.$d.',"'.addslashes($v).'");'."\n";
  echo '  data.insert('.$k.', data_'.$k.");\n";
}
echo "}\n";
