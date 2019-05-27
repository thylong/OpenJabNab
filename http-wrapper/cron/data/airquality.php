<?php
require_once '../../config.php';
if(!empty($_SERVER['DOCUMENT_ROOT']))
	die('HTTP use is forbidden');

global $datas;
$datas = array();
function addToDatas($country, $city, $when, $d) {
	global $datas;
	if(!isset($datas[$country])) { $datas[$country] = array(); } 
	if(!isset($datas[$country][$city])) { $datas[$country][$city] = array(); } 
	//if(!isset($datas[$country][$city][$type])) { $datas[$country][$city][$type] = array(); } 
	if(!isset($datas[$country][$city][$when]) || $d != 0)
	{
		$datas[$country][$city][$when] = $d;
	}
}

function mytrim($s) {
	return trim(preg_replace('/  +/is', ' ', $s));
}

// France
if(true) {
	$d = array();
	$when = array(date('Ymd', time() - 24*3600) => date('Y-m-d'), date('Ymd') => date('Y-m-d'), date('Ymd', time() + 24*3600) => date('Y-m-d', time() + 24*3600), date('Ymd', time() + 2*24*3600) => date('Y-m-d', time() + 2*24*3600));
	foreach($when as $date => $dateShow) {
		$url = 'http://www2.prevair.org/ineris-web-services.php?url=atmo&date='.$date;
		$content = file_get_contents($url);
		// "Date","Code insee","Longitude","Latitude","Commune","Departement","Region","Incide","Sous-indice SO2","Sous-indice NO2","Sous-indice O3","Sous-indice pm10","Commentaire","Nom Aasqa"
		if(preg_match_all('|\["(\d\d\d\d-\d\d-\d\d)","(.*)","(.*)","(.*)","(.*)","(.*)","(.*)","(.*)","(.*)","(.*)","(.*)","(.*)","(.*)","(.*)"\]|isU', $content, $match, PREG_SET_ORDER)) {
			foreach($match as $line) {
                                $city = $line[5];
                                $city = preg_replace('|^\(([^\)]*)\)|i', '$1 ', $city);
				$city = ucfirst(strtolower($city)) . ' ('. $line[6] . ')';
				$indice = $line[8];
				$date = $line[1];
				addToDatas('France', $city, $dateShow, $indice);
			}
		}
	}
}

$data = '<xml>';
foreach($datas as $country => $cities) {
	$data .= '<country name="'.$country.'">';
	foreach($cities as $city => $items) {
		$data .= '<city name="'.$city.'">';
		foreach($items as $i => $v) {
			$data .= '<value name="'.$i.'"><![CDATA['.$v.']]></value>';
		}
		$data .= '</city>';
	}
	$data .= '</country>';
}
$data .= '</xml>';
var_dump(ROOT_DATA);
file_put_contents(ROOT_DATA.'/airquality.xml', $data);
?>
