<?php
// Handle form submissions
if(isset($_POST['question']) && trim($_POST['question']) != "")
{
    $question = trim($_POST['question']);
    $voice = isset($_POST['voice']) ? $_POST['voice'] : '';
    $language = isset($_POST['language']) ? $_POST['language'] : '';
    
    // Save language preference if changed
    if (!empty($language)) {
        $currentLang = $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/getlanguage?".$ojnAPI->getToken());
        $currentLang = isset($currentLang['value']) ? $currentLang['value'] : 'en';
        if ($language != $currentLang) {
            Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/setlanguage?lng=".$language."&".$ojnAPI->getToken()));
        }
    }
    
    // Save voice preference if changed
    if (!empty($voice)) {
        $currentVoice = $ojnAPI->getApiValue("bunny/".$_SESSION['bunny']."/voice?action=get&".$ojnAPI->getToken());
        if ($voice != $currentVoice) {
            Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/voice?action=set&voice=".$voice."&".$ojnAPI->getToken()));
        }
    }
    
    Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/chatgpt/ask?question=".urlencode($question)."&".$ojnAPI->getToken()));
    header("Location: bunny_plugin.php?p=chatgpt");
    exit();
}

// Get current bunny language and voice settings
$currentLang = $ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/getlanguage?".$ojnAPI->getToken());
$currentLang = isset($currentLang['value']) ? $currentLang['value'] : 'en';

$currentVoice = $ojnAPI->getApiValue("bunny/".$_SESSION['bunny']."/voice?action=get&".$ojnAPI->getToken());

// Get available languages
$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
    die('Database connection failed: ' . mysqli_error());
}
mysqli_set_charset($link, 'utf8mb4');

$sql = "SELECT * FROM language";
if(empty($Infos['isAdmin'])) {
    $sql .= " WHERE public=1";
}

// Add translations for development
if(function_exists('getTranslates')) {
    $tr = getTranslates(isset($_SESSION['login']) ? $_SESSION['login'] : '');
    if(count($tr)) {
        foreach($tr as $t) {
            $sql .= " OR code='".$t."'";
        }
    }
}

$res = mysqli_query($link, $sql);
$languages = array();
while($res && $row = mysqli_fetch_assoc($res)) {
    $languages[] = $row;
}
mysqli_close($link);
?>

<div class="card">
    <h5 class="card-header">
        <i class="icon-comment"></i> <?php echo __tr('ChatGPT Voice Assistant') ?>
    </h5>
    <div class="card-body">
        <form method="post">
            <div class="form-group row">
                <label class="col-sm-2 col-form-label" for="language"><?php echo __tr("Language") ?></label>
                <div class="col-sm-6">
                    <select name="language" id="language" class="form-control" onchange="updateVoiceList(this.value);">
                        <?php foreach($languages as $lang): ?>
                        <option value="<?php echo $lang['code'] ?>"<?php if($currentLang == $lang['code']) { ?> selected="selected"<?php } ?>>
                            <?php echo $lang['language'] ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-4">
                    <p class="help-block"><?php echo __tr("Select the language for ChatGPT response") ?></p>
                </div>
            </div>

            <div class="form-group row">
                <label class="col-sm-2 col-form-label" for="voice"><?php echo __tr("Voice") ?></label>
                <div class="col-sm-6">
                    <select name="voice" id="voiceList" class="form-control">
                        <option value="">Use default voice</option>
                        <option value="pico/en-US">English (Pico)</option>
                        <option value="pico/fr-FR">French (Pico)</option>
                        <option value="pico/de-DE">German (Pico)</option>
                        <option value="pico/es-ES">Spanish (Pico)</option>
                        <option value="pico/it-IT">Italian (Pico)</option>
                    </select>
                </div>
                <div class="col-sm-4">
                    <p class="help-block"><?php echo __tr("Select the voice for ChatGPT response") ?></p>
                </div>
            </div>

            <div class="form-group row">
                <label class="col-sm-2 col-form-label" for="question"><?php echo __tr("Question") ?></label>
                <div class="col-sm-6">    
                    <input type="text" name="question" class="form-control" placeholder="<?php echo __tr("What's the weather like?") ?>" />
                </div>
                <div class="col-sm-4">
                    <button class="btn btn-success" type="submit">
                        <i class="icon-comment"></i> <?php echo __tr("Ask ChatGPT") ?>
                    </button>
                    <p class="help-block mt-2">
                        <?php echo __tr("Ask any question and your bunny will speak the ChatGPT response") ?><br>
                        <small class="text-muted"><?php echo __tr("Voice and language preferences are automatically saved") ?></small>
                    </p>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function updateVoiceList(language) {
    console.log('Loading voices for language:', language);
    
    // Store current selection
    var currentValue = $('#voiceList').val();
    
    // Show loading state
    $('#voiceList').empty().append('<option value="">Loading...</option>');
    
    $.get('getVoices.php?language=' + language, function(data) {
        console.log('Voices loaded successfully:', data);
        // Always include default option at the top
        var html = '<option value="">Use default voice</option>' + data;
        $('#voiceList').html(html);
        
        // Restore selection if possible
        if (currentValue) {
            $('#voiceList').val(currentValue);
        }
    }).fail(function(xhr, status, error) {
        console.error('Failed to load voices:', status, error, xhr.responseText);
        // Fallback to basic Pico voices
        var fallbackOptions = [
            '<option value="">Use default voice</option>',
            '<option value="pico/en-US">English (Pico)</option>',
            '<option value="pico/fr-FR">French (Pico)</option>',
            '<option value="pico/de-DE">German (Pico)</option>',
            '<option value="pico/es-ES">Spanish (Pico)</option>',
            '<option value="pico/it-IT">Italian (Pico)</option>'
        ];
        $('#voiceList').html(fallbackOptions.join(''));
        
        // Restore selection if possible
        if (currentValue) {
            $('#voiceList').val(currentValue);
        }
    });
}

// Load voices for the current language on page load
$(document).ready(function() {
    var currentLanguage = $('#language').val();
    if (currentLanguage) {
        updateVoiceList(currentLanguage);
    }
});
</script>