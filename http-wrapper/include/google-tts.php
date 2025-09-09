<?php
/**
 * Google Text-to-Speech API Helper Functions
 */

/**
 * Get available Google TTS voices for a specific language
 * @param string $language Language code (e.g., 'en', 'fr')
 * @param string $apiKey Google Speech API key
 * @return array Array of voice objects or empty array on failure
 */
function getGoogleVoices($language, $apiKey) {
    if (empty($apiKey)) {
        return array();
    }
    
    // Cache key for this language
    $cacheKey = 'google_voices_' . $language;
    $cacheTime = 3600; // Cache for 1 hour
    
    // Try to get from cache first
    $success = false;
    $cachedVoices = apcu_fetch($cacheKey, $success);
    if ($success && !empty($cachedVoices)) {
        return $cachedVoices;
    }
    
    // Google Cloud TTS API endpoint
    $url = 'https://texttospeech.googleapis.com/v1/voices?key=' . urlencode($apiKey);
    
    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'OpenJabNab/1.0');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($response === false || $httpCode !== 200) {
        error_log("Google TTS API error: HTTP $httpCode");
        return array();
    }
    
    $data = json_decode($response, true);
    if (!isset($data['voices']) || !is_array($data['voices'])) {
        error_log("Google TTS API: Invalid response format");
        return array();
    }
    
    // Filter voices by language
    $filteredVoices = array();
    $targetLanguages = array($language);
    
    // Add common language variations
    if ($language === 'en') {
        $targetLanguages = array('en', 'en-US', 'en-GB', 'en-AU', 'en-CA', 'en-IN');
    } elseif ($language === 'fr') {
        $targetLanguages = array('fr', 'fr-FR', 'fr-CA');
    } elseif ($language === 'de') {
        $targetLanguages = array('de', 'de-DE', 'de-AT', 'de-CH');
    } elseif ($language === 'es') {
        $targetLanguages = array('es', 'es-ES', 'es-US', 'es-MX');
    } elseif ($language === 'it') {
        $targetLanguages = array('it', 'it-IT');
    }
    
    foreach ($data['voices'] as $voice) {
        if (!isset($voice['languageCodes']) || !is_array($voice['languageCodes'])) {
            continue;
        }
        
        // Check if this voice supports any of our target languages
        foreach ($voice['languageCodes'] as $voiceLanguage) {
            foreach ($targetLanguages as $targetLang) {
                if (strpos($voiceLanguage, $targetLang) === 0) {
                    $filteredVoices[] = array(
                        'name' => $voice['name'],
                        'language' => $voiceLanguage,
                        'gender' => isset($voice['ssmlGender']) ? $voice['ssmlGender'] : 'NEUTRAL'
                    );
                    break 2; // Break out of both loops for this voice
                }
            }
        }
    }
    
    // Cache the results
    apcu_store($cacheKey, $filteredVoices, $cacheTime);
    
    return $filteredVoices;
}

/**
 * Format Google voice for display in dropdown
 * @param array $voice Voice data from Google API
 * @return string Formatted voice name
 */
function formatGoogleVoice($voice) {
    $name = $voice['name'];
    $language = $voice['language'];
    $gender = $voice['gender'];
    
    // Extract voice type (Standard, Wavenet, Neural2, etc.)
    $type = 'Standard';
    if (strpos($name, 'Wavenet') !== false) {
        $type = 'Wavenet';
    } elseif (strpos($name, 'Neural2') !== false) {
        $type = 'Neural2';
    } elseif (strpos($name, 'Studio') !== false) {
        $type = 'Studio';
    }
    
    // Extract voice identifier (A, B, C, etc.)
    preg_match('/[A-Z]$/', $name, $matches);
    $identifier = !empty($matches) ? $matches[0] : '';
    
    // Format: "en-US-Standard-A (Google, Female)"
    $genderLabel = ucfirst(strtolower($gender));
    if ($genderLabel === 'Neutral') {
        $genderLabel = '';
    } else {
        $genderLabel = ', ' . $genderLabel;
    }
    
    return $language . '-' . $type . '-' . $identifier . ' (Google' . $genderLabel . ')';
}

/**
 * Generate speech audio using Google TTS
 * @param string $text Text to synthesize
 * @param string $voiceName Google voice name (e.g., "en-US-Standard-A")  
 * @param string $apiKey Google Speech API key
 * @param string $outputPath Full path where to save the audio file
 * @return array Result with 'success' boolean and 'error' message if failed
 */
function generateGoogleTTS($text, $voiceName, $apiKey, $outputPath) {
    if (empty($apiKey) || empty($text) || empty($voiceName)) {
        return array('success' => false, 'error' => 'Missing required parameters');
    }
    
    // Extract language code from voice name
    $languageCode = substr($voiceName, 0, strpos($voiceName, '-', 3)); // e.g., "en-US" from "en-US-Standard-A"
    if (empty($languageCode)) {
        $languageCode = 'en-US'; // Fallback
    }
    
    // Prepare the request data
    $requestData = array(
        'input' => array(
            'text' => $text
        ),
        'voice' => array(
            'languageCode' => $languageCode,
            'name' => $voiceName
        ),
        'audioConfig' => array(
            'audioEncoding' => 'MP3',
            'speakingRate' => 1.0,
            'pitch' => 0.0,
            'volumeGainDb' => 0.0
        )
    );
    
    $jsonData = json_encode($requestData);
    
    // Google Cloud TTS synthesize endpoint
    $url = 'https://texttospeech.googleapis.com/v1/text:synthesize?key=' . urlencode($apiKey);
    
    // Initialize cURL for the API request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData)
    ));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'OpenJabNab/1.0');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($response === false) {
        return array('success' => false, 'error' => 'Network error occurred');
    }
    
    if ($httpCode !== 200) {
        $errorMsg = 'HTTP ' . $httpCode;
        $responseData = json_decode($response, true);
        if (isset($responseData['error']['message'])) {
            $errorMsg .= ': ' . $responseData['error']['message'];
        }
        return array('success' => false, 'error' => $errorMsg);
    }
    
    $responseData = json_decode($response, true);
    if (!isset($responseData['audioContent'])) {
        return array('success' => false, 'error' => 'Invalid API response');
    }
    
    // Decode the base64 audio content
    $audioContent = base64_decode($responseData['audioContent']);
    
    // Create the directory if it doesn't exist
    $dir = dirname($outputPath);
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0777, true)) {
            return array('success' => false, 'error' => 'Could not create output directory: ' . $dir);
        }
        // Set permissions explicitly after creation
        chmod($dir, 0777);
    }
    
    // Ensure directory is writable
    if (!is_writable($dir)) {
        return array('success' => false, 'error' => 'Output directory is not writable: ' . $dir);
    }
    
    // Save the audio file
    if (file_put_contents($outputPath, $audioContent) === false) {
        return array('success' => false, 'error' => 'Could not save audio file');
    }
    
    return array('success' => true, 'path' => $outputPath);
}

/**
 * Generate TTS audio using the appropriate engine (Pico or Google)
 * @param string $text Text to synthesize
 * @param string $voice Voice specification in format "engine/voice" (e.g., "pico/en-US" or "google/en-US-Standard-A")
 * @param string $outputPath Full path where to save the audio file
 * @return array Result with 'success' boolean and 'error' message if failed
 */
function generateTTS($text, $voice, $outputPath) {
    // Parse the voice specification
    $parts = explode('/', $voice, 2);
    if (count($parts) !== 2) {
        return array('success' => false, 'error' => 'Invalid voice format');
    }
    
    $engine = $parts[0];
    $voiceSpec = $parts[1];
    
    switch ($engine) {
        case 'pico':
            return generatePicoTTS($text, $voiceSpec, $outputPath);
            
        case 'google':
            $googleApiKey = isset($_ENV['GOOGLE_SPEECH_API_KEY']) ? $_ENV['GOOGLE_SPEECH_API_KEY'] : 
                           (isset($_SERVER['GOOGLE_SPEECH_API_KEY']) ? $_SERVER['GOOGLE_SPEECH_API_KEY'] : '');
            
            if (empty($googleApiKey)) {
                return array('success' => false, 'error' => 'Google Speech API key not configured');
            }
            
            return generateGoogleTTS($text, $voiceSpec, $googleApiKey, $outputPath);
            
        default:
            return array('success' => false, 'error' => 'Unknown TTS engine: ' . $engine);
    }
}

/**
 * Generate speech audio using Pico TTS (existing functionality)
 * @param string $text Text to synthesize
 * @param string $language Pico language code (e.g., "en-US")
 * @param string $outputPath Full path where to save the audio file
 * @return array Result with 'success' boolean and 'error' message if failed
 */
function generatePicoTTS($text, $language, $outputPath) {
    // Create the directory if it doesn't exist
    $dir = dirname($outputPath);
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0777, true)) {
            return array('success' => false, 'error' => 'Could not create output directory: ' . $dir);
        }
        // Set permissions explicitly after creation
        chmod($dir, 0777);
    }
    
    // Ensure directory is writable
    if (!is_writable($dir)) {
        return array('success' => false, 'error' => 'Output directory is not writable: ' . $dir);
    }
    
    // Generate a temporary WAV file first
    $tempWav = $outputPath . '.tmp.wav';
    
    // Use pico2wave to generate speech
    $command = sprintf(
        'pico2wave -l %s -w %s %s 2>&1',
        escapeshellarg($language),
        escapeshellarg($tempWav),
        escapeshellarg($text)
    );
    
    exec($command, $output, $returnCode);
    
    if ($returnCode !== 0 || !file_exists($tempWav)) {
        // Clean up temp file if it exists
        if (file_exists($tempWav)) {
            unlink($tempWav);
        }
        return array('success' => false, 'error' => 'Pico TTS generation failed: ' . implode(' ', $output));
    }
    
    // Convert WAV to MP3 if ffmpeg is available, otherwise keep as WAV
    $finalPath = $outputPath;
    if (pathinfo($outputPath, PATHINFO_EXTENSION) === 'mp3') {
        // Try to convert to MP3
        $convertCommand = sprintf(
            'ffmpeg -i %s -codec:a libmp3lame -b:a 128k %s -y 2>/dev/null',
            escapeshellarg($tempWav),
            escapeshellarg($finalPath)
        );
        
        exec($convertCommand, $convertOutput, $convertResult);
        
        if ($convertResult === 0 && file_exists($finalPath)) {
            // Conversion successful, remove temp WAV
            unlink($tempWav);
        } else {
            // Conversion failed, use WAV instead
            $finalPath = str_replace('.mp3', '.wav', $outputPath);
            if (!rename($tempWav, $finalPath)) {
                unlink($tempWav);
                return array('success' => false, 'error' => 'Could not save audio file');
            }
        }
    } else {
        // Keep as WAV
        if (!rename($tempWav, $finalPath)) {
            unlink($tempWav);
            return array('success' => false, 'error' => 'Could not save audio file');
        }
    }
    
    return array('success' => true, 'path' => $finalPath);
}
?>