<?php
$mois = array('janvier', 'fevrier', 'mars', 'avril', 'mai', 'juin', 'juillet', 'aout', 'septembre', 'octobre', 'novembre', 'decembre');

foreach($mois as $k => $m) {
	$url = 'http://www.dicocitations.com/dicton-mois-'.$m.'.php';
	$content = utf8_encode(file_get_contents($url));
	$content = preg_replace('|<h5><font color=black>|', '', $content);
	if(preg_match_all('|<blockquote><div class="citation"><p class="citationContenu">([^<]*)</p>.*\[ Dicton du jour : *(\d+) [^]]*\].*</blockquote>|isU', $content, $match, PREG_SET_ORDER)) {
		echo 'QMap<int, QString> data_'.$m.';'."\n";
		foreach($match as $line) {
			$j = $line[2];
			$dicton = preg_replace('|^\[.*\]|isU', '', $line[1]);
			echo 'data_'.$m.'.insert('.$j . ',"' . $dicton . '");'."\n";
		}
		echo 'data.insert('.($k+1).', data_'.$m.');'."\n";
	} else {
		die('erreur en '. $m . "\n" . $content);
	}
}
