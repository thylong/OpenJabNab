<?php
$reload = false;
if(isset($_GET['delete'])) {
	$retour = $ojnAPI->getApiString("plugin/webradio/preset?action=del&name=".urlencode($_GET['delete'])."&".$ojnAPI->getToken());
	$_SESSION['message'] = isset($retour['ok']) ? $retour['ok'] : "Error : ".$retour['error'];
	$reload = true;
}
if(isset($_GET['test'])) {
	$_SESSION['test_bunny'] = $_GET['test'];
	$reload = true;
}
if(count($_POST) > 0) {
	unset($_SESSION['addname']);
	unset($_SESSION['addurl']);
	if(isset($_POST['add']) && isset($_POST['addname']) && isset($_POST['addurl']) && strlen(trim($_POST['addname'])) && strlen(trim($_POST['addurl']))) {
		$retour = $ojnAPI->getApiString("plugin/webradio/preset?action=add&name=".urlencode($_POST['addname'])."&url=".urlencode($_POST['addurl'])."&".$ojnAPI->getToken());
		$_SESSION['message'] = isset($retour['ok']) ? $retour['ok'] : "Error : ".$retour['error'];
		$reload = true;
		apcu_delete(APC_PREFIX.'ojn_plugins_webradio_header_'.md5($_POST['addurl']));
	} else if(isset($_POST['stop'])) {
		$_SESSION['addname'] = $_POST['addname'];
		$_SESSION['addurl'] = $_POST['addurl'];
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['test_bunny']."/packet/sendMessage?msg=ST+&".$ojnAPI->getToken()));
		$reload = true;
	} else if(isset($_POST['test'])) {
		$_SESSION['addname'] = $_POST['addname'];
		$_SESSION['addurl'] = $_POST['addurl'];
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['test_bunny']."/packet/sendMessage?msg=ST+".urlencode($_POST['addurl'])."&".$ojnAPI->getToken()));
		$reload = true;
	}
}
$presetList = $ojnAPI->getApiMapped("plugin/webradio/preset?action=list&".$ojnAPI->getToken());
//asort($presetList);

if($reload) {
	header("Location: server_plugin.php?p=webradio");
	exit;
}
$online = $ojnAPI->getListOfConnectedBunnies(false);
?>
<?php
if(!empty($presetList)) {
?>
      <form class="form-horizontal" method="post">
          <div class="control-group">
            <label for="addname" class="control-label"><?php echo __tr("Name") ?></label>
            <div class="controls">
		<input class="span8" type="text" name="addname" value="<?php echo $_SESSION['addname'] ?>">
	    </div>
          </div>
          <div class="control-group">
            <label for="addurl" class="control-label"><?php echo __tr("URL") ?></label>
            <div class="controls">
		<input class="span8" type="text" name="addurl" value="<?php echo $_SESSION['addurl'] ?>">
	    </div>
          </div>
          <div class="form-actions">
	<div class="btn-toolbar" style="margin: 0;">
		    <div class="btn-group">
            <button class="btn btn-primary" name="add" type="submit"><?php echo __tr("Add") ?></button>
		</div>
		    <div class="btn-group">
    <button class="btn btn-success" name="test"><?php echo __tr("Test") ?><?php echo isset($_SESSION['test_bunny']) && strlen($_SESSION['test_bunny']) == 12 ? ' (' . (isset($online[$_SESSION['test_bunny']]) ? $online[$_SESSION['test_bunny']] : $_SESSION['test_bunny']) . ')' : '' ?></button>
    <button class="btn btn-success dropdown-toggle" data-toggle="dropdown">
    <span class="caret"></span>
    </button>
    <ul class="dropdown-menu">
<?php foreach($online as $mac => $name): ?>
<li><a href="server_plugin.php?p=webradio&test=<?php echo $mac ?>"><?php echo $name ?></a></li>
<?php endforeach; ?>
    </ul>
    </div>
		    <div class="btn-group">
            <button class="btn btn-danger" name="stop"><?php echo __tr("Stop") ?></button>
		</div>
    </div>
          </div>
	</form>
<center>

<table class="table table-bordered table-striped">
	<tr>
		<th colspan="4">Presets</th>
	</tr>
	<tr>
		<th>Name</th>
		<th>Url</th>
		<th>Test</th>
		<th>Actions</th>
	</tr>
<?php
	$i = 0;
	foreach($presetList as $c => $v) {
$content = '';
if(!($content = apcu_fetch(APC_PREFIX.'ojn_plugins_webradio_header_'.md5($v)))) {
//echo "test $v<br />";
	$ch = curl_init();
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt ($ch, CURLOPT_URL, $v);
	curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 3);
	//curl_setopt ($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
	//curl_setopt($ch, CURLOPT_RANGE, '0-30');
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_setopt($ch, CURLOPT_HEADER,         1);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	//curl_setopt($ch, CURLOPT_FILETIME, true);
	curl_setopt($ch, CURLOPT_NOBODY, true);

	//curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);

	// Only calling the head

	$content = curl_exec ($ch);
	$content = curl_getinfo($ch);
	curl_close ($ch);
//var_dump($content);
	$content = isset($content['http_code']) && $content['http_code'] > 0 ? $content['http_code'] : -1;
	apcu_store(APC_PREFIX.'ojn_plugins_webradio_header_'.md5($v), $content, 24 * 3600);
}

?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><?php echo preg_replace('/OJN_/', '', $c) ?></td>
		<td><?php echo $v ?></td>
		<td><span class="badge badge-<?php echo $content > 0 ? ($content >= 200 && $content< 300 ? 'success' : 'warning') : 'error' ?>"><?php echo $content > 0 ? ($content >= 200 && $content< 300 ? 'OK' : 'Error : ' . $content) : 'Error : can\'t connect' ?></span></td>
		<td width="15%">
			<a class="btn btn-mini btn-danger" href="server_plugin.php?p=webradio&delete=<?php echo urlencode(preg_replace('/OJN_/', '', $c)) ?>"><?php echo __tr('Remove') ?></a>
		</td>
	</tr>
<?php } ?>
</table>
<?php
}
?>
