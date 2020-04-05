<?php
require_once '../include/common.php';

$reload = false;
if(isset($_GET['elanguage'])) {
	$_SESSION['elanguage'] = $_GET['elanguage'];
	Message::AddSuccess(__tr("Successfully change edit language to '%1'", $_SESSION['elanguage']));
	$reload = true;
}
if(isset($_GET['clear']) && $_GET['clear'] == 'cache') {
	apcu_delete(APC_PREFIX.'ojn_tr_' . $_SESSION['elanguage']);
	$reload = true;
	Message::AddSuccess(__tr("Cache successfully cleared for '%1'", $_SESSION['elanguage']));
}
if(isset($_GET['scan']) && $_GET['scan'] == 'server') {
	require('server.php');
	$reload = true;
	Message::AddSuccess(__tr("%1 new translations in database", $new));
}
if(isset($_GET['scan']) && $_GET['scan'] == 'files') {
	require('scan.php');
	$reload = true;
	Message::AddSuccess(__tr("%1 new translations in database", $new));
}
if(isset($_GET['auto']) && $_GET['auto'] == 'google') {
	require('auto.php');
	$reload = true;
}
if(isset($_GET['generate']) && $_GET['generate'] == 'tr') {
	require('generate.php');
	$reload = true;
	Message::AddSuccess(__tr("Translation cache generated", $new));
}
if(isset($_GET['did']) && is_numeric($_GET['did'])) {
	$reload = true;
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
	    Message::AddError(__tr('Connexion impossible : ') . mysqli_error());
		header('Location: index.php');
		exit;
	}

	$sql = "DELETE FROM sentence WHERE id='".$_GET['did']."';";
	$res = mysqli_query($link, $sql);
	if($res)
		Message::AddSuccess(__tr('Successfully remove the sentence'));
	else
		Message::AddError(__tr('Error removing the sentence'));
	$sql = "DELETE FROM translation WHERE sentence_id='".$_GET['did']."';";
	$res = mysqli_query($link, $sql);
	if($res)
		Message::AddSuccess(__tr('Successfully remove the translations'));
	else
		Message::AddError(__tr('Error removing the translations'));
	mysqli_close($link);
}
if(isset($_POST['sid']) && is_numeric($_POST['sid'])) {
	$reload = true;
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
	    Message::AddError(__tr('Connexion impossible : ') . mysqli_error());
		header('Location: index.php');
		exit;
	}

	$sql = "INSERT INTO translation SET sentence_id='".$_POST['sid']."', translation='".addslashes($_POST['translate'])."', language='".$_POST['language']."', note=0 ON DUPLICATE KEY UPDATE translation='".addslashes($_POST['translate'])."', language='".$_POST['language']."', note=0";
	//echo $sql;
	$res = mysqli_query($link, $sql);
	if($res)
		Message::AddSuccess(__tr('Successfully save the translation'));
	else
		Message::AddError(__tr('Error saving the translation'));
	mysqli_close($link);
}
if(isset($_POST['aid']) && is_numeric($_POST['aid']) && $_POST['aid'] == -1) {
	$reload = true;
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
	    Message::AddError(__tr('Connexion impossible : ') . mysqli_error());
		header('Location: index.php');
		exit;
	}

	$sql = "INSERT INTO sentence SET sentence='".addslashes($_POST['sentence'])."'";
	$res = mysqli_query($link, $sql);
	if($res) {
		Message::AddSuccess(__tr('Successfully add the sentence'));
		$id = mysqli_insert_id($link);
		$sql = "INSERT INTO translation SET sentence_id='".$id."', translation='".addslashes($_POST['translate'])."', language='".$_POST['language']."', note=0 ON DUPLICATE KEY UPDATE translation='".addslashes($_POST['translate'])."', language='".$_POST['language']."', note=0";
		$res = mysqli_query($link, $sql);
		if($res)
			Message::AddSuccess(__tr('Successfully save the translation'));
		else
			Message::AddError(__tr('Error saving the translation'));
		}
	else {
		Message::AddError(__tr('Error inserting the sentence'));
	}
	mysqli_close($link);
}
if(!isset($_SESSION['elanguage'])) {
	$_SESSION['elanguage'] = 'fr';
}
if($reload) {
	header('Location: index.php');
	exit;
}
include(ROOT_SITE.'include/message.php');
?>
	      <div class="row">
	      	<div class="span12">
	      		<div class="widget">
					<div class="widget-header">
						<i class="icon-th-large"></i>
						<h3><?php echo __tr("Translate openJabNab") ?></h3>
					</div> <!-- /widget-header -->
					<div class="widget-content">
<a href="?clear=cache" class="btn btn-primary"><?php echo __tr("Clear translation cache") ?></a>
<a href="?scan=files" class="btn btn-primary"><?php echo __tr("Scan files") ?></a>
<a href="?scan=server" class="btn btn-primary"><?php echo __tr("Scan server files") ?></a>
<a href="?auto=google" class="btn btn-primary"><?php echo __tr("Auto translate") ?></a>
<a href="?manual=add" class="btn btn-primary"><?php echo __tr("Manually add a sentence") ?></a>
<a style="float:right" href="?generate=tr" class="btn btn-success"><?php echo __tr("Generate translations") ?></a>
<br /><br />
	<form id="edit-profile" method="get" class="well form-inline">
				<label class="control-label" for="sentence"><?php echo __tr('Language to edit') ?> : </label>
				<select name="elanguage">
<?php
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
	    die('Connexion impossible : ' . mysqli_error());
	}

	$sql = "SELECT * FROM language";

	$res = mysqli_query($link, $sql);
	while($row = mysqli_fetch_assoc($res))
	{
?>
<option value="<?php echo $row['code'] ?>"<?php if($_SESSION['elanguage'] == $row['code']) { ?> selected="selected"<?php } ?>><?php echo $row['language'] ?></option>
<?php
	}
	mysqli_close($link);
?>
				</select>
				<button type="submit" class="btn btn-primary"><?php echo __tr('Apply') ?></button>
	</form>
<?php
if(isset($_GET['eid']) && is_numeric($_GET['eid'])) {
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
	    die('Connexion impossible : ' . mysqli_error());
	}

	$sql = "SELECT translation.translation, translation.language, sentence.sentence, sentence.id, translation.note FROM sentence LEFT JOIN translation ON translation.sentence_id = sentence.id AND translation.language='".$_SESSION['elanguage']."' WHERE sentence.id=" . $_GET['eid'];
	$res = mysqli_query($link, $sql);
	if($row = mysqli_fetch_assoc($res))
	{
?>
								<form id="edit-profile" class="form-horizontal" method="post">
												<input type="hidden" name="sid" value="<?php echo $row['id'] ?>">
												<input type="hidden" name="language" value="<?php echo strlen($row['language']) ? $row['language'] : $_SESSION['elanguage'] ?>">
										<div class="control-group">
											<label class="control-label" for="sentence"><?php echo __tr('Original sentence') ?></label>
											<div class="controls">
												<input type="text" class="input-xlarge disabled span8" id="sentence" value="<?php echo htmlentities(utf8_decode($row['sentence'])) ?>" disabled>
											</div> <!-- /controls -->
										</div> <!-- /control-group -->

										<div class="control-group">
											<label class="control-label" for="translate"><?php echo __tr('Translation') ?></label>
											<div class="controls">
												<input type="text" class="input-xlarge span8" name="translate" value="<?php echo htmlentities(utf8_decode($row['translation'])) ?>">
											</div> <!-- /controls -->
										</div> <!-- /control-group -->
										<br />

										<div class="form-actions">
											<button type="submit" class="btn btn-primary"><?php echo __tr('Save') ?></button>
											<button class="btn"><?php echo __tr('Cancel') ?></button>
										</div> <!-- /form-actions -->
								</form>
<?php
	mysqli_close($link);
	}
	else
	{
		echo __tr('Invalid ID');
	}
}
else if(isset($_GET['manual']) && $_GET['manual'] == 'add') {
?>
	<form id="edit-profile" class="form-horizontal" method="post">
			<input type="hidden" name="language" value="<?php echo $_SESSION['elanguage'] ?>">
			<input type="hidden" name="aid" value="-1">
			<div class="control-group">
				<label class="control-label" for="sentence"><?php echo __tr('New sentence') ?></label>
				<div class="controls">
					<input type="text" class="input-xlarge span8" name="sentence" value="<?php echo $row['sentence'] ?>">
				</div> <!-- /controls -->
			</div> <!-- /control-group -->

			<div class="control-group">
				<label class="control-label" for="translate"><?php echo __tr('Translation') ?></label>
				<div class="controls">
					<input type="text" class="input-xlarge span8" name="translate" value="<?php echo $row['translation'] ?>">
				</div> <!-- /controls -->
			</div> <!-- /control-group -->
			<br />

			<div class="form-actions">
				<button type="submit" class="btn btn-primary"><?php echo __tr('Save') ?></button>
				<button class="btn"><?php echo __tr('Cancel') ?></button>
			</div> <!-- /form-actions -->
	</form>
<?php
}
else {
?>
<table class="table table-bordered table-striped span11">
	<thead>
	<tr>
		<th class="span4"><?php echo __tr('Original sentence') ?></th>
		<th class="span4"><?php echo __tr('Translations') ?></th>
		<th class="span2"><?php echo __tr('Type') ?></th>
		<th class="span2"><?php echo __tr('Actions') ?></th>
	</tr>
	</thead>
<tbody>
<?php
	$i = 0;
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
	    die('Connexion impossible : ' . mysqli_error());
	}

	$sql = "SELECT translation.translation, sentence.sentence, sentence.id, translation.note, sentence.web FROM sentence LEFT JOIN translation ON translation.language='".$_SESSION['elanguage']."' AND translation.sentence_id = sentence.id ORDER BY translation.note ASC, sentence ASC";
	$res = mysqli_query($link, $sql);
	$type = array(__tr('Server'), __tr('Web admin'), __tr('Both'), __tr('Unknown'));
	while($row = mysqli_fetch_assoc($res))
	{
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><?php echo $row['sentence']; ?></td>
		<td><?php echo $row['translation']; ?></td>
		<td><?php echo $type[$row['web']]; ?></td>
		<td><a  href="?eid=<?php echo $row['id']; ?>" class="btn btn-primary"><?php echo __tr('Edit') ?></a> &nbsp;<a  href="?did=<?php echo $row['id']; ?>" class="btn btn-danger"><?php echo __tr('Remove') ?></a></td>
	</tr>
<?php
	}
	mysqli_close($link);
?>
</tbody>
</table>
<?php
}
?>
					</div> <!-- /widget-content -->
				</div> <!-- /widget -->
		    </div> <!-- /span12 -->
	      </div> <!-- /row -->
<?php
require_once '../include/append.php'
?>
