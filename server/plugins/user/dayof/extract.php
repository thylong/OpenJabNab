<?php
function Slugify($text) {
	setlocale(LC_ALL, 'en_GB');
	// replace non letter or digits by -

//	$text = preg_replace('#[^\\pL\d]+#u', '-', $text);
//	$text = preg_replace('/\pM*/u','',normalizer_normalize( $text, \Normalizer::FORM_D));

	// trim
//	$text = trim($text, '-');

	//$text = utf8_decode($text);
	// transliterate
	if (function_exists('iconv'))
	{
	    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
	}

	// lowercase
//	$text = strtolower($text);

	// remove unwanted characters
//	$text = preg_replace('#[^-\w]+#', '', $text);

	setlocale(LC_ALL, 'C');

	return $text;
}


$mois = array('janvier', 'fevrier', 'mars', 'avril', 'mai', 'juin', 'juillet', 'aout', 'septembre', 'octobre', 'novembre', 'decembre');
$url = 'http://www.journee-mondiale.com/les-journees-mondiales.htm';

//<li><a href="http://www.journee-mondiale.com/303/journee-du-domaine-public.htm"><time datetime="1er janvier">1er janvier</time> : Journée du domaine public</a></li>

$content = file_get_contents($url);
//$content = Slugify($content);
echo 'inline void InitData()'."\n";
echo '{'."\n";
echo '    QMap<int, QMultiMap<int, QString> > data;'."\n";
$slugified = array();
if(preg_match_all('|<li><a href="http://www.journee-mondiale.com/(\d+)/([^\"]*)"><time datetime="([^\"]*)">([^<]*)</time> : ([^<]*)</a></li>|isU', Slugify($content), $match, PREG_SET_ORDER)) {

	foreach($match as $line) {
		$d = $line[4];
		$day = $line[1];
		$slugified[$day] = $d;
	}
} else {
	die('erreur en '. $m . "\n" . $content);
}
if(preg_match_all('|<li><a href="http://www.journee-mondiale.com/(\d+)/([^\"]*)"><time datetime="([^\"]*)">([^<]*)</time> : ([^<]*)</a></li>|isU', $content, $match, PREG_SET_ORDER)) {
	foreach($mois as $j => $m) {
		echo '    QMultiMap<int, QString> data_'.$m.';'."\n";
	}

	foreach($match as $line) {
		$day = trim($line[5]);
		$d = $line[4];
		$d = Slugify($line[4]);
		$d = $slugified[$line[1]];
		if(preg_match('/^(\d+)(?:er)? ('.implode($mois, '|').')$/', $d, $date)) {
			$d = $date[1];
			$m = $date[2];
			echo '    data_'.$m.'.insert('.$d . ',"' . $day . '");'."\n";
		} else {
			echo $d."\n";
		}
	}
	foreach($mois as $k => $m) {
		echo '    data.insert('.($k+1).', data_'.$m.');'."\n";
	}
} else {
	die('erreur en '. $m . "\n" . $content);
}
echo '}'."\n";
