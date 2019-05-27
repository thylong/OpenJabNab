<?php
require_once '../../config.php';
if(!empty($_SERVER['DOCUMENT_ROOT']))
	die('HTTP use is forbidden');

global $datas;
global $mois;
$context = stream_context_create(array(
'http' => array(
    'method' => 'GET',
    'timeout' => 10,
    'user_agent' => 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:34.0) Gecko/20100101 Firefox/34.0',
)));

$datas = array();
function mytrim($s) {
	return trim(preg_replace('/  +/is', ' ', $s));
}
$mois = array("janvier" => 1, "février" => 2, "mars" => 3, "avril" => 4, "mai" => 5, "juin" => 6, "juillet" => 7, "août" => 8, "septembre" => 9, "octobre" => 10, "novembre" => 11, "décembre" => 12);

$base_url = 'https://particulier.edf.fr/fr/accueil/contrat-et-conso/options/';

// Tempo
if(true) {
	$d = array();
	$url = $base_url.'/tempo.html';
	$content = file_get_contents($url, false, $context);
	if(preg_match_all('|<div class="TempoDay">.*<h4>(.*)</h4>.*<ul class="tempoColor">(.*)</ul>|sU', $content, $match, PREG_SET_ORDER)) {
		foreach($match as $line) {
			$today = preg_match('|Aujourd\'hui|', $line[1]) ? true : false;
			$date = preg_replace_callback('|/(.+)/|', function ($matches) { global $mois; return '/'.$mois[$matches[1]].'/'; }, preg_replace('| +|', '/', preg_replace('|^[^0-9]*|', '', $line[1])));
			$date = date('Y-m-d', strtotime(preg_replace('|^([0-9]+)/([0-9]+)/([0-9]+)|', '$3-$2-$1', $date)));
			$color = trim(preg_replace('|<li class="[^"]+"></li>|', '', $line[2]));
			if(strlen($color)) {
				$color = preg_replace('|^.*"([^"]+)".*$|', '$1', $color);
			} else {
				$color = '';
			}
			$datas[$date] = array('today' => $today, 'color' => $color);
		}
	}
}
// EJP
if(true) {
	$d = array();
	$url = $base_url.'/ejp.html';
	$content = file_get_contents($url, false, $context);
	if(preg_match_all('|<div class="EJPDay">.*<h4>(.*)</h4>.*<caption>(.*)</caption>.*<td class="first" id="tabEJP\d_1-l1">EJP</td>(.*)</tr>|sU', $content, $match, PREG_SET_ORDER)) {
		foreach($match as $line) {
			$today = preg_match('|aujourd\'hui|', $line[1]) ? true : false;
			$date = preg_replace_callback('|/(.+)/|', function ($matches) { global $mois; return '/'.$mois[$matches[1]].'/'; }, preg_replace('| +|', '/', preg_replace('|^[^0-9]*|', '', $line[2])));
			$date = date('Y-m-d', strtotime(preg_replace('|^([0-9]+)/([0-9]+)/([0-9]+)|', '$3-$2-$1', $date)));
			if(preg_match_all('|/FRONT/NetExpress/img/ejp_([^\.]+).png|', $line[3], $zones)) {
				$datas[$date]['ejp_nord'] = $zones[1][0];
				$datas[$date]['ejp_paca'] = $zones[1][1];
				$datas[$date]['ejp_ouest'] = $zones[1][2];
				$datas[$date]['ejp_sud'] = $zones[1][3];
			}
		}
	}
}

$data = '<xml>';
foreach($datas as $date => $infos) {
	if($infos['today']) {
		$data .= '<today date="'.$date.'">';
		$data .= '<color><![CDATA['.$infos['color'].']]></color>';
		foreach($infos as $key => $value) {
			if(preg_match('|^ejp_(.*)$|', $key, $match)) {
				$data .= '<ejp name="'.$match[1].'"><![CDATA['.$value.']]></ejp>';
			}
		}
		$data .= '</today>';
	} else {
		$data .= '<tomorrow date="'.$date.'">';
		$data .= '<color><![CDATA['.$infos['color'].']]></color>';
		foreach($infos as $key => $value) {
			if(preg_match('|^ejp_(.*)$|', $key, $match)) {
				$data .= '<ejp name="'.$match[1].'"><![CDATA['.$value.']]></ejp>';
			}
		}
		$data .= '</tomorrow>';
	}
}
$data .= '</xml>';
file_put_contents(ROOT_DATA.'/edftempo.xml', $data);
?>
