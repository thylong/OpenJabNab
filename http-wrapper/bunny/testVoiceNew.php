<?php
/**
 * Enhanced voice testing endpoint supporting both Pico and Google TTS
 */
require_once "../include/common.php";
require_once "../include/google-tts.php";

if (!isset($_SESSION['token']) || !isset($_SESSION['bunny'])) {
    ob_end_clean();
    echo '<div class="alert alert-danger">' . __tr('Error: Not authenticated') . '</div>';
    exit;
}

if (!isset($_GET['voice']) || !isset($_GET['sentence'])) {
    ob_end_clean();
    echo '<div class="alert alert-danger">' . __tr('Error: Missing parameters') . '</div>';
    exit;
}

$voice = $_GET['voice'];
$sentence = $_GET['sentence'];

// Sanitize inputs
$sentence = trim($sentence);
if (empty($sentence)) {
    $sentence = 'Hello, this is a voice test.';
}

if (strlen($sentence) > 500) {
    $sentence = substr($sentence, 0, 500); // Limit text length
}

ob_end_clean();

try {
    // Generate a cache key based on voice and sentence
    $cacheKey = md5($voice . '|' . $sentence);
    
    // Determine storage paths
    $engine = strpos($voice, '/') !== false ? substr($voice, 0, strpos($voice, '/')) : 'pico';
    
    if ($engine === 'google') {
        $audioDir = '/var/www/html/ojn_local/tts/google';
        $audioFile = $audioDir . '/' . $cacheKey . '.mp3';
        $webPath = '/ojn_local/tts/google/' . $cacheKey . '.mp3';
    } else {
        $audioDir = '/var/www/html/ojn_local/tts/pico';
        $audioFile = $audioDir . '/' . $cacheKey . '.mp3';
        $webPath = '/ojn_local/tts/pico/' . $cacheKey . '.mp3';
    }
    
    // Check if audio file already exists (caching)
    if (!file_exists($audioFile)) {
        // Generate new audio file
        $result = generateTTS($sentence, $voice, $audioFile);
        
        if (!$result['success']) {
            echo '<div class="alert alert-danger">' . __tr('TTS Error') . ': ' . htmlspecialchars($result['error']) . '</div>';
            exit;
        }
    }
    
    // Verify the file exists and is readable
    if (!file_exists($audioFile) || !is_readable($audioFile)) {
        echo '<div class="alert alert-danger">' . __tr('Audio file not accessible') . '</div>';
        exit;
    }
    
    // Get file info
    $fileSize = filesize($audioFile);
    $fileExt = pathinfo($audioFile, PATHINFO_EXTENSION);
    
    ?>
    <div class="alert alert-success">
        <strong><?php echo __tr('Voice test successful!'); ?></strong><br>
        <small>
            <?php echo __tr('Engine'); ?>: <?php echo ucfirst($engine); ?><br>
            <?php echo __tr('Voice'); ?>: <?php echo htmlspecialchars(str_replace($engine . '/', '', $voice)); ?><br>
            <?php echo __tr('File size'); ?>: <?php echo round($fileSize / 1024, 1); ?> KB<br>
            <?php echo __tr('Format'); ?>: <?php echo strtoupper($fileExt); ?>
        </small>
    </div>
    
    <!-- HTML5 Audio Player -->
    <div class="text-center mb-3">
        <audio controls style="width: 300px;">
            <source src="<?php echo htmlspecialchars($webPath); ?>" type="audio/<?php echo $fileExt; ?>">
            <?php echo __tr('Your browser does not support audio playback.'); ?>
        </audio>
    </div>
    
    <!-- Download link -->
    <div class="text-center">
        <a href="<?php echo htmlspecialchars($webPath); ?>" download="voice_test.<?php echo $fileExt; ?>" class="btn btn-sm btn-outline-primary">
            <i class="icon-download"></i> <?php echo __tr('Download'); ?>
        </a>
    </div>
    
    <?php
    
} catch (Exception $e) {
    echo '<div class="alert alert-danger">' . __tr('Unexpected error') . ': ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>