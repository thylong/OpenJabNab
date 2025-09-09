<?php
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
    // Check if this is a Google TTS voice (format: google/voice-name)
    if (strpos($voice, 'google/') === 0) {
        // Use new Google TTS system
        $cacheKey = md5($voice . '|' . $sentence);
        $audioDir = '../ojn_local/tts/google';
        $audioFile = $audioDir . '/' . $cacheKey . '.mp3';
        $webPath = '/ojn_local/tts/google/' . $cacheKey . '.mp3';

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
                <?php echo __tr('Engine'); ?>: Google<br>
                <?php echo __tr('Voice'); ?>: <?php echo htmlspecialchars(str_replace('google/', '', $voice)); ?><br>
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

    } else {
        // Use legacy Pico TTS system via API
        define("BUNNY_API", "bunny/" . $_SESSION['bunny']);
        $str = $ojnAPI->getApiValue(BUNNY_API."/voice?action=test&sentence=".urlencode($sentence)."&voice=".$voice."&".$ojnAPI->getToken());
        if($str)
        {
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $baseUrl = $protocol . '://' . $host . '/';
            $audioUrl = $baseUrl . preg_replace("|^broadcast/|", "", $str);
?>
            <div class="alert alert-success">
                <strong><?php echo __tr('Voice test successful!'); ?></strong><br>
                <small>
                    <?php echo __tr('Engine'); ?>: Pico TTS<br>
                    <?php echo __tr('Voice'); ?>: <?php echo htmlspecialchars($voice); ?><br>
                    <?php echo __tr('Path'); ?>: <?php echo htmlspecialchars($str); ?>
                </small>
            </div>

            <!-- HTML5 Audio Player -->
            <div class="text-center mb-3">
                <audio controls autoplay style="width: 300px;">
                    <source src="<?php echo htmlspecialchars($audioUrl); ?>" type="audio/mpeg">
                    <?php echo __tr('Your browser does not support audio playback.'); ?>
                </audio>
            </div>

            <!-- Download link -->
            <div class="text-center">
                <a href="<?php echo htmlspecialchars($audioUrl); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="icon-external-link"></i> <?php echo __tr('Open in new tab'); ?>
                </a>
            </div>
<?php
        } else {
            echo '<div class="alert alert-danger">' . __tr('Voice generation failed') . '</div>';
        }
    }

} catch (Exception $e) {
    echo '<div class="alert alert-danger">' . __tr('Unexpected error') . ': ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>
