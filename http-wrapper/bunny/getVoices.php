<?php
require_once '../include/common.php';

header('Content-Type: text/html; charset=utf-8');

if (!isset($_SESSION['bunny']) || !isset($_GET['language'])) {
    echo '<option value="">Error</option>';
    exit;
}

$language = $_GET['language'];

// Map language codes to language names that pico TTS supports
$picoLanguages = array(
    'fr' => 'fr-FR',
    'en' => 'en-US', 
    'de' => 'de-DE',
    'es' => 'es-ES',
    'it' => 'it-IT'
);

// Check if the language is supported by pico TTS
if (isset($picoLanguages[$language])) {
    $picoLang = $picoLanguages[$language];
    echo '<option value="pico/' . $picoLang . '">' . $picoLang . ' (pico)</option>' . "\n";
} else {
    // For unsupported languages, try to get voices from the current API
    // This might be empty but at least shows the language isn't supported
    echo '<option value="">No voices available for ' . htmlspecialchars($language) . '</option>';
}
?>