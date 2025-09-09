<?php
require_once '../include/common.php';
require_once '../include/google-tts.php';

header('Content-Type: text/html; charset=utf-8');

if (!isset($_SESSION['bunny']) || !isset($_GET['language'])) {
    echo '<option value="">Error</option>';
    exit;
}

$language = $_GET['language'];
$voices = array();

// Map language codes to language names that pico TTS supports
$picoLanguages = array(
    'fr' => 'fr-FR',
    'en' => 'en-US', 
    'de' => 'de-DE',
    'es' => 'es-ES',
    'it' => 'it-IT'
);

// Add Pico voices if supported
if (isset($picoLanguages[$language])) {
    $picoLang = $picoLanguages[$language];
    $voices[] = array(
        'value' => 'pico/' . $picoLang,
        'label' => $picoLang . ' (Pico)',
        'type' => 'pico'
    );
}

// Add Google voices if API key is available
$googleApiKey = isset($_ENV['GOOGLE_SPEECH_API_KEY']) ? $_ENV['GOOGLE_SPEECH_API_KEY'] : 
                (isset($_SERVER['GOOGLE_SPEECH_API_KEY']) ? $_SERVER['GOOGLE_SPEECH_API_KEY'] : '');

if (!empty($googleApiKey)) {
    $googleVoices = getGoogleVoices($language, $googleApiKey);
    foreach ($googleVoices as $voice) {
        $voices[] = array(
            'value' => 'google/' . $voice['name'],
            'label' => formatGoogleVoice($voice),
            'type' => 'google'
        );
    }
}

// Sort voices: Pico first, then Google voices alphabetically
usort($voices, function($a, $b) {
    if ($a['type'] !== $b['type']) {
        return $a['type'] === 'pico' ? -1 : 1;
    }
    return strcmp($a['label'], $b['label']);
});

// Output voices
if (empty($voices)) {
    echo '<option value="">No voices available for ' . htmlspecialchars($language) . '</option>';
} else {
    foreach ($voices as $voice) {
        echo '<option value="' . htmlspecialchars($voice['value']) . '">' . 
             htmlspecialchars($voice['label']) . '</option>' . "\n";
    }
}
?>