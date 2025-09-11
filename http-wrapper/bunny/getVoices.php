<?php
require_once '../include/common.php';

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

// Add Google TTS voices from the C++ server
$googleVoices = array(
    'en' => array(
        array('name' => 'en-US-Standard-A', 'gender' => 'FEMALE'),
        array('name' => 'en-US-Standard-B', 'gender' => 'MALE'),
        array('name' => 'en-US-Standard-C', 'gender' => 'FEMALE'),
        array('name' => 'en-US-Standard-D', 'gender' => 'MALE'),
        array('name' => 'en-US-Standard-E', 'gender' => 'FEMALE'),
        array('name' => 'en-US-Standard-F', 'gender' => 'FEMALE'),
        array('name' => 'en-US-Standard-G', 'gender' => 'FEMALE'),
        array('name' => 'en-US-Standard-H', 'gender' => 'FEMALE'),
        array('name' => 'en-US-Standard-I', 'gender' => 'MALE'),
        array('name' => 'en-US-Standard-J', 'gender' => 'MALE'),
    ),
    'fr' => array(
        array('name' => 'fr-FR-Standard-A', 'gender' => 'FEMALE'),
        array('name' => 'fr-FR-Standard-B', 'gender' => 'MALE'),
        array('name' => 'fr-FR-Standard-C', 'gender' => 'FEMALE'),
        array('name' => 'fr-FR-Standard-D', 'gender' => 'MALE'),
    ),
    'de' => array(
        array('name' => 'de-DE-Standard-A', 'gender' => 'FEMALE'),
        array('name' => 'de-DE-Standard-B', 'gender' => 'MALE'),
        array('name' => 'de-DE-Standard-C', 'gender' => 'FEMALE'),
        array('name' => 'de-DE-Standard-D', 'gender' => 'MALE'),
    ),
    'es' => array(
        array('name' => 'es-ES-Standard-A', 'gender' => 'FEMALE'),
        array('name' => 'es-ES-Standard-B', 'gender' => 'MALE'),
    ),
    'it' => array(
        array('name' => 'it-IT-Standard-A', 'gender' => 'FEMALE'),
        array('name' => 'it-IT-Standard-B', 'gender' => 'MALE'),
        array('name' => 'it-IT-Standard-C', 'gender' => 'FEMALE'),
        array('name' => 'it-IT-Standard-D', 'gender' => 'FEMALE'),
    )
);

// Add Google voices if available for the language
if (isset($googleVoices[$language])) {
    foreach ($googleVoices[$language] as $voice) {
        $genderText = $voice['gender'] === 'FEMALE' ? 'Female' : 'Male';
        $voices[] = array(
            'value' => 'google/' . $voice['name'],
            'label' => $voice['name'] . ' (' . $genderText . ')',
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