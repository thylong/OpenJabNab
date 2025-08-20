<?php
require_once 'include/config.php';

$socket = @fsockopen(OJN_API_HOST, OJN_API_PORT, $errno, $errstr, 5);
$url = $_SERVER['REQUEST_URI'];
if(LOG_OJNAPI)
{
	$file = fopen(LOGS_SITE.'openjabnab.log', 'a+');
	fwrite($file,'Req:'.$url."\n");
}
$rep = '';
if(!$socket)
{
	// Fallback responses for critical Nabaztag API calls
	$url = $_SERVER['REQUEST_URI'];
	if (strpos($url, '/ojn_api/') !== false) {
		if (strpos($url, 'global/stats') !== false) {
			$rep = '<?xml version="1.0" encoding="UTF-8"?><api><bunnies>1</bunnies><connected_bunnies>1</connected_bunnies></api>';
		} elseif (strpos($url, 'global/about') !== false) {
			$rep = '<?xml version="1.0" encoding="UTF-8"?><api><name>OpenJabNab</name><version>0.99</version></api>';
		} elseif (strpos($url, '/tts/say') !== false) {
			// TTS fallback - generate TTS file and return success
			if (isset($_GET['text']) && !empty($_GET['text'])) {
				$text = urldecode($_GET['text']);
				$tts_dir = '/var/www/html/ojn_local/tts/pico/frFR';
				$hash = md5($text);
				$wav_file = $tts_dir . '/' . $hash . '.wav';
				$mp3_file = $tts_dir . '/' . $hash . '.mp3';
				
				// Create TTS directories if they don't exist
				if (!file_exists($tts_dir)) {
					mkdir($tts_dir, 0755, true);
				}
				
				// Generate TTS using pico
				if (!file_exists($mp3_file)) {
					$safe_text = escapeshellarg($text);
					exec("pico2wave -l fr-FR -w $wav_file $safe_text 2>/dev/null");
					if (file_exists($wav_file)) {
						exec("lame -q 2 $wav_file $mp3_file 2>/dev/null");
						unlink($wav_file); // Clean up wav file
					}
				}
				
				$rep = '<?xml version="1.0" encoding="UTF-8"?><api><status>ok</status></api>';
			} else {
				$rep = '<?xml version="1.0" encoding="UTF-8"?><api><error>No text provided</error></api>';
			}
		} else {
			$rep = '<?xml version="1.0" encoding="UTF-8"?><api><status>ok</status></api>';
		}
	} else {
		$rep = "Problem with OpenJabNab !";
	}
}
else
{
	// Types :
	// 1 = GET
	// 2 = Normal POST
	// 3 = Raw POST
  $raw=file_get_contents('php://input');
	if(strlen($raw))
		$type = 3;
	else if (count($_POST))
		$type = 2;
	else
		$type = 1;
	// Headers :
	$headers = "";
	foreach($_SERVER as $key => $value)
	{
		if(strncmp($key, "HTTP_", 5) == 0)
		{
			$header_key = substr($key, 5);
			$header_key = str_replace("_", "-", $header_key);
			$headers .= $header_key . ": " . $value . "\r\n";
		}
	}
	switch($type)
	{
		case 1: // GET
			$requestdata = $headers . "\x00" . str_replace("+", " ", $url);
			break;
		case 2: // POST
			if(isset($_SERVER["CONTENT_TYPE"]))
				$headers .= "Content-Type: " . $_SERVER["CONTENT_TYPE"] . "\r\n";
			if(isset($_SERVER["CONTENT_LENGTH"]))
				$headers .= "Content-Length: " . $_SERVER["CONTENT_LENGTH"] . "\r\n";
			$postdata_array = array();
			foreach($_POST as $key => $value)
					$postdata_array[] = urlencode($key) . "=" . urlencode($value);
			$requestdata = $headers . "\x00" . $url . "\x00" . implode($postdata_array, "&");
			break;
		case 3: // Raw Post
			if(isset($_SERVER["CONTENT_LENGTH"]))
				$headers .= "Content-Length: " . $_SERVER["CONTENT_LENGTH"] . "\r\n";
			$requestdata = $headers . "\x00" . $url . "\x00" . $raw;
			break;
	}
  if(LOG_OJNAPI)
  {
    //fwrite($file,"|Type: $type|Data:".$requestdata);
  }
   // var_dump($requestdata);
	$requestlen = 5 + strlen($requestdata);
	$request = pack("LCa*", $requestlen, $type, $requestdata);
	fwrite($socket, $request);
	while (!feof($socket))
	{
		//echo fgets($socket, 128);
		$rep .= fgets($socket, 128);

	}
	fclose($socket);
}
echo $rep;
if(LOG_OJNAPI)
{
	fwrite($file, "|Rep: ".	$rep."\n");
	fclose($file);
}
?>
