<?php
// http://es.horoscopofree.com/rss/horoscopofree-es.rss
// http://horoscopo.cibeles.net/
// http://it.horoscopofree.com/rss/horoscopofree-it.rss
// http://de.horoscopofree.com/rss/horoscopofree-de.rss

global $datas;
$datas = array();
$zodiacs = array('aries', 'taurus', 'gemini', 'cancer', 'leo', 'virgo', 'libra', 'scorpio', 'sagittarius', 'capricorn', 'aquarius', 'pisces');
function addToDatas($lng, $theme, $d) {
	global $datas;
	if(!isset($datas[$lng])) { $datas[$lng] = array(); } 
	if(!isset($datas[$lng][$theme])) { $datas[$lng][$theme] = array(); } 
	$datas[$lng][$theme] = $d;
}
function mytrim($s) {
	return trim(preg_replace('/  +/is', ' ', $s));
}

$prod = false;

// Daily ES
if(true) {
	$d = array();
	$url = 'http://es.horoscopofree.com/rss/horoscopofree-es.rss';
	$xml = simplexml_load_string(file_get_contents($url));
	$channel = (array)($xml->channel);
	foreach($channel['item'] as $i => $item) {
		$desc = preg_replace('/\s*<a href.*$/', '', (string)$item->description);
		$d[$zodiacs[$i]] = mytrim($desc);
	}
	addToDatas('es', 'daily', $d);
}
// Daily IT
if(true) {
	$d = array();
	$url = 'http://it.horoscopofree.com/rss/horoscopofree-it.rss';
	$xml = simplexml_load_string(file_get_contents($url));
	$channel = (array)($xml->channel);
	foreach($channel['item'] as $i => $item) {
		$desc = preg_replace('/\s*<a href.*$/', '', (string)$item->description);
		$d[$zodiacs[$i]] = mytrim($desc);
	}
	addToDatas('it', 'daily', $d);
}
// Daily DE
if(true) {
	$d = array();
	$url = 'http://de.horoscopofree.com/rss/horoscopofree-de.rss';
	$xml = simplexml_load_string(file_get_contents($url));
	$channel = (array)($xml->channel);
	foreach($channel['item'] as $i => $item) {
		$desc = preg_replace('/\s*<a href.*$/', '', (string)$item->description);
		$d[$zodiacs[$i]] = mytrim($desc);
	}
	addToDatas('de', 'daily', $d);
}
// Daily FR
if($prod) {
	$d = array();
	$url = 'http://www.astrocenter.fr/fr/feeds/rss-horoscope-jour.aspx?Af=0';
	$xml = simplexml_load_string(file_get_contents($url));
	$channel = (array)($xml->channel);
	foreach($channel['item'] as $i => $item) {
		$desc = preg_replace('/\s*<br.*$/', '', (string)$item->description);
		$desc = preg_replace('/\(e\)/', '', $desc);
		$d[$zodiacs[$i]] = mytrim($desc);
	}
	addToDatas('fr', 'daily', $d);
}
// Family FR
if($prod) {
	$d = array();
	$url = 'http://www.astrocenter.fr/fr/feeds/rss-horoscope-famille.aspx?Af=0';
	$xml = simplexml_load_string(file_get_contents($url));
	$channel = (array)($xml->channel);
	foreach($channel['item'] as $i => $item) {
		$desc = preg_replace('/\s*<br.*$/', '', (string)$item->description);
		$desc = preg_replace('/\(e\)/', '', $desc);
		$d[$zodiacs[$i]] = mytrim($desc);
	}
	addToDatas('fr', 'family', $d);
}
// Love FR
if($prod) {
	$d = array();
	$url = 'http://www.astrocenter.fr/fr/feeds/rss-horoscope-amour.aspx?Af=0';
	$xml = simplexml_load_string(file_get_contents($url));
	$channel = (array)($xml->channel);
	foreach($channel['item'] as $i => $item) {
		$desc = preg_replace('/\s*<br.*$/', '', (string)$item->description);
		$desc = preg_replace('/\(e\)/', '', $desc);
		$d[$zodiacs[$i]] = mytrim($desc);
	}
	addToDatas('fr', 'love', $d);
}
// Work FR
if($prod) {
	$d = array();
	$url = 'http://www.astrocenter.fr/fr/feeds/rss-horoscope-travail.aspx?Af=0';
	$xml = simplexml_load_string(file_get_contents($url));
	$channel = (array)($xml->channel);
	foreach($channel['item'] as $i => $item) {
		$desc = preg_replace('/\s*<br.*$/', '', (string)$item->description);
		$desc = preg_replace('/\(e\)/', '', $desc);
		$d[$zodiacs[$i]] = mytrim($desc);
	}
	addToDatas('fr', 'work', $d);
}
// Daily EN
if($prod) {
	$d = array();
	$url = 'http://www.astrology.com/horoscopes/daily-horoscope.rss';
	$xml = simplexml_load_string(file_get_contents($url));
	$channel = (array)($xml->channel);
	foreach($channel['item'] as $i => $item) {
		$desc = preg_replace('/<p>(.*)<\/p>.*$/isU', '$1', (string)$item->description);
		$desc = preg_replace('/ --/', '', $desc);
		$d[$zodiacs[$i]] = mytrim($desc);
	}
	addToDatas('en', 'daily', $d);
}
// Work EN
if($prod) {
	$d = array();
	$url = 'http://www.astrology.com/horoscopes/daily-work.rss';
	$xml = simplexml_load_string(file_get_contents($url));
	$channel = (array)($xml->channel);
	foreach($channel['item'] as $i => $item) {
		$desc = preg_replace('/<p>(.*)<\/p>.*$/isU', '$1', (string)$item->description);
		$desc = preg_replace('/ --/', '', $desc);
		$d[$zodiacs[$i]] = mytrim($desc);
	}
	addToDatas('en', 'work', $d);
}
// Love-singles EN
if($prod) {
	$d = array();
	$url = 'http://www.astrology.com/horoscopes/daily-singles.rss';
	$xml = simplexml_load_string(file_get_contents($url));
	$channel = (array)($xml->channel);
	foreach($channel['item'] as $i => $item) {
		$desc = preg_replace('/<p>(.*)<\/p>.*$/isU', '$1', (string)$item->description);
		$desc = preg_replace('/ --/', '', $desc);
		$d[$zodiacs[$i]] = mytrim($desc);
	}
	addToDatas('en', 'love_single', $d);
}
// Love-couples EN
if($prod) {
	$d = array();
	$url = 'http://www.astrology.com/horoscopes/daily-couples.rss';
	$xml = simplexml_load_string(file_get_contents($url));
	$channel = (array)($xml->channel);
	foreach($channel['item'] as $i => $item) {
		$desc = preg_replace('/<p>(.*)<\/p>.*$/isU', '$1', (string)$item->description);
		$desc = preg_replace('/ --/', '', $desc);
		$d[$zodiacs[$i]] = mytrim($desc);
	}
	addToDatas('en', 'love_couple', $d);
}

$data = '<xml>';
foreach($datas as $lng => $themes) {
	$data .= '<language name="'.$lng.'">';
	foreach($themes as $theme => $items) {
		$data .= '<theme name="'.$theme.'">';
		foreach($items as $i => $v) {
			$data .= '<item name="'.$i.'"><![CDATA['.$v.']]></item>';
		}
		$data .= '</theme>';
	}
	$data .= '</language>';
}
$data .= '</xml>';
echo $data;
?>
