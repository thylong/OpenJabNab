<?php
// <a href="http://www.archive.org/download/FablesDeLaFontaine_450/STE-001.mp3">La cigale et la fourmi</a>

$url = 'http://www.audiocite.net/livres-audio-gratuits-poesies/jean-de-la-fontaine-les-fables.html';

$content = file_get_contents($url);
if(preg_match_all('|<a href="http://www.archive.org/download/FablesDeLaFontaine_450/(STE-\d+\.mp3)">([^<]*)</a>|', $content, $match, PREG_SET_ORDER)) {
	foreach($match as $line) {
		echo utf8_encode(html_entity_decode($line[2])) . ' => ' . $line[1] . "\n";
	}
}

?>
