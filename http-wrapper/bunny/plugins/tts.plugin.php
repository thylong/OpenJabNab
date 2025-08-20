<?php
if(isset($_POST['text']) && trim($_POST['text']) != "")
{
	// Use the API method now that authentication is working
	$text = trim($_POST['text']);
	
	// Use the API to send TTS via the C++ server
	$bunny_id = $_SESSION['bunny'];
	$result = $ojnAPI->getApiMapped('bunny/' . $bunny_id . '/tts/say?text=' . urlencode($text) . '&' . $ojnAPI->getToken());
	
	if ($result && isset($result['status']) && $result['status'] == 'ok') {
		Message::AddSuccess("Text-to-Speech sent successfully: " . $text);
	} elseif ($result && isset($result['error'])) {
		Message::AddError("TTS Error: " . (string)$result['error']);
	} else {
		// Try to get the raw response for better error diagnosis
		$raw_result = $ojnAPI->getApiRaw('bunny/' . $bunny_id . '/tts/say?text=' . urlencode($text) . '&' . $ojnAPI->getToken());
		if ($raw_result === NULL) {
			Message::AddError("Failed to send TTS command - unable to connect to OpenJabNab server. Check server logs for details.");
		} elseif (strpos($raw_result, '<error>') !== false) {
			// Extract error from XML
			if (preg_match('/<error>(.*?)<\/error>/', $raw_result, $matches)) {
				Message::AddError("TTS Error: " . $matches[1]);
			} else {
				Message::AddError("TTS Error: Server returned an error");
			}
		} elseif (strpos($raw_result, '<ok>') !== false) {
			// Success response format: <api><ok>Sending 'text' to bunny 'id'</ok></api>
			if (preg_match('/<ok>(.*?)<\/ok>/', $raw_result, $matches)) {
				Message::AddSuccess("Text-to-Speech sent successfully: " . $matches[1]);
			} else {
				Message::AddSuccess("Text-to-Speech sent successfully: " . $text);
			}
		} else {
			Message::AddError("Failed to send TTS command - unexpected server response: " . substr($raw_result, 0, 100));
		}
	}
	
	header("Location: bunny_plugin.php?p=tts");
	exit();
}
?>
<form method="post">
  <div class="form-group row">
    <label class="col-sm-2 col-form-label" for="text"><?php echo __tr("Text to send") ?></label>
    <div class="col-sm-8">    
      <input type="text" name="text"  class="form-control" />
    </div>
    <div class="col-sm-2">
      <button class="btn btn-primary" type="submit"><?php echo __tr("Submit") ?></button>
    </div>
  </div>
</form>
