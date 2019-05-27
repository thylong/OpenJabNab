<?php
global $mois;
$mois = array("janvier" => 1, "février" => 2, "mars" => 3, "avril" => 4, "mai" => 5, "juin" => 6, "juillet" => 7, "août" => 8, "septembre" => 9, "octobre" => 10, "novembre" => 11, "décembre" => 12);

$datas = array();
function mytrim($s) {
	return trim(preg_replace('/  +/is', ' ', $s));
}

// Euromillion
if(true) {
	$d = array();
	$url = 'https://www.fdj.fr/mobiles/resultats/euromillions';
	$content = file_get_contents($url);
	$datas['euromillion'] = array();
	if(preg_match('|<div id="tirage-date">Tirages du (.*)</div>|isU', $content, $match)) {
		$date = preg_replace_callback('|/(.+)/|', function ($matches) { global $mois; return '/'.$mois[$matches[1]].'/'; }, preg_replace('| +|', '/', preg_replace('|^[^0-9]*|', '', $match[1])));
		$date = date('Y-m-d', strtotime(preg_replace('|^([0-9]+)/([0-9]+)/([0-9]+)|', '$3-$2-$1', $date)));
		if(preg_match('|<div id="tirage-boules">(.*)</div>|isU', $content, $match)) {
			if(preg_match_all('|<span class ?="([^"]*)\d?_[^"]*">(\d+)</span>|isU', $match[1], $match, PREG_SET_ORDER)) {
				$d = array();
				foreach($match as $line) {
					if(!isset($d[$line[1]])) {
						$d[$line[1]] = array();
					}
					$d[$line[1]][] = $line[2];
				}
				$tirage = array();
				foreach($d as $type => $num) {
					$tirage[] = $type.':'.implode(',', $num);
				}
				$tirage = implode(';', $tirage);
				$datas['euromillion']['tirages'] = array($date => $tirage);
			}
		}
	}
	if(preg_match('|<div id="prochain-tirage">(.*)</div|isU', $content, $match)) {

		if(preg_match('|<p>(.*)<br />.*(\d*) millions &euro;|isU', $match[1], $match)) {
			$date = preg_replace_callback('|/(.+)/|', function ($matches) { global $mois; return '/'.$mois[$matches[1]].'/'; }, preg_replace('| +|', '/', preg_replace('|^[^0-9]*|', '', $match[1])));
			$date = date('Y-m-d', strtotime(preg_replace('|^([0-9]+)/([0-9]+)/([0-9]+)|', '$3-$2-$1', $date)));
			$datas['euromillion']['next'] = array($date => $match[2] . '000000');
		}
	}
	if(preg_match('|<p class="code">(.*)</p>|isU', $content, $match)) {
		$datas['euromillion']['bonus'] = array('mymillion' => $match[1]);
	}
	//var_dump($content);
}

// Loto
if(true) {
	$d = array();
	$url = 'https://www.fdj.fr/mobiles/resultats/loto';
	$content = file_get_contents($url);
	$datas['loto'] = array();
	if(preg_match('|<div id="tirage-date">Tirage du (.*)</div>|isU', $content, $match)) {
		$date = preg_replace_callback('|/(.+)/|', function ($matches) { global $mois; return '/'.$mois[$matches[1]].'/'; }, preg_replace('| +|', '/', preg_replace('|^[^0-9]*|', '', $match[1])));
		$date = date('Y-m-d', strtotime(preg_replace('|^([0-9]+)/([0-9]+)/([0-9]+)|', '$3-$2-$1', $date)));
		if(preg_match('|<div id="tirage-boules">(.*)</div>|isU', $content, $match)) {
//<span class="numero-boule" id="numero-0">31</span>
			if(preg_match_all('|<span class ?="[^"]*" id="[^"]*-([^"]*)">(\d+)</span>|isU', $match[1], $match, PREG_SET_ORDER)) {
				$d = array();
				foreach($match as $line) {
					if($line[1] != 'chance') {
						$line[1] = 'boule';
					}
					if(!isset($d[$line[1]])) {
						$d[$line[1]] = array();
					}
					$d[$line[1]][] = $line[2];
				}
				$tirage = array();
				foreach($d as $type => $num) {
					$tirage[] = $type.':'.implode(',', $num);
				}
				$tirage = implode(';', $tirage);
				$datas['loto']['tirages'] = array($date => $tirage);
			}
		}
	}
	if(preg_match('|<div id="prochain-tirage"(.*)</div|isU', $content, $match)) {
		if(preg_match('|<p>(.*)<br />.*(\d+[ 0-9]+)&euro;|isU', $match[1], $match)) {
			$date = preg_replace('| +|', '/', preg_replace('|^[^0-9]*|', '', $match[1]));
			$date = date('Y-m-d', strtotime(preg_replace('|^([0-9]+)/([0-9]+)/([0-9]+)|', '$3-$2-$1', $date)));
			$datas['loto']['next'] = array($date => preg_replace('| |', '', $match[2]));
		}
	}
	if(preg_match('|<span id="numero-joker">(.*)</span>|isU', $content, $match)) {
		$datas['loto']['bonus'] = array('joker+' => $match[1]);
	}
//	var_dump($content);
}

$data = '<xml>';
foreach($datas as $jeu => $infos) {
	$data .= '<game name="'.$jeu.'">';
	foreach($infos['tirages'] as $date => $tirage) {
		$data .= '<tirage date="'.$date.'"><![CDATA['.$tirage.']]></tirage>';
	}
	foreach($infos['next'] as $date => $tirage) {
		$data .= '<next date="'.$date.'"><![CDATA['.$tirage.']]></next>';
	}
	foreach($infos['bonus'] as $name => $code) {
		$data .= '<bonus name="'.$name.'"><![CDATA['.$code.']]></next>';
	}
	$data .= '</game>';
}
$data .= '</xml>';
file_put_contents('/home/prod/OpenJabNab/server/bin/data/fdj.xml', $data);
?>
