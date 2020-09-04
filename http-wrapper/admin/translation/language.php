<?php
require_once '../include/common.php';

$reload = false;
if(isset($_GET['ixid'])) {
	if(count($_POST) && isset($_POST['format'])) {
		ob_end_clean();
		$filename = 'openjabnab-'.$_GET['ixid'].'.'.$_POST['format'];
		$buffer = fopen('php://temp', 'r+');

		if($_POST['format'] == 'csv') {
			fputcsv($buffer, array('id', 'origine', 'translation'), ';');
		}
		if($_POST['format'] == 'xml') {
			fwrite($buffer, '<?xml version="1.0" encoding="utf-8"?>'."\n");
			fwrite($buffer, '<!DOCTYPE TS>'."\n");
			fwrite($buffer, '<TS version="2.0" language="'.$_GET['ixid'].'" sourcelanguage="en">'."\n");
			fwrite($buffer, '<context>'."\n");
			fwrite($buffer, '    <name>openJabNab</name>'."\n");
		}
		$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		if (!$link) {
		    die('Connexion impossible : ' . mysqli_error());
		}

		$sql = "SELECT sentence.id, sentence.sentence, translation.translation FROM sentence LEFT JOIN translation ON translation.language='".$_GET['ixid']."' AND translation.sentence_id = sentence.id ORDER BY sentence ASC";
		$res = mysqli_query($link, $sql);
		while($row = mysqli_fetch_assoc($res))
		{
			if($_POST['format'] == 'csv') {
				fputcsv($buffer, $row, ';');
			}
			if($_POST['format'] == 'xml') {
				fwrite($buffer, '        <message id="'.$row['id'].'">'."\n");
				fwrite($buffer, '            <source>'.htmlentities($row['sentence']).'</source>'."\n");
				fwrite($buffer, '            <translation type="unfinished">'.htmlentities($row['translation']).'</translation>'."\n");
				fwrite($buffer, '        </message>'."\n");
			}
		}
		if($_POST['format'] == 'xml') {
			fwrite($buffer, '</context>'."\n");
			fwrite($buffer, '</TS>'."\n");
		}
		mysqli_close($link);
		header("Cache-Control: public");
		header("Content-Description: File Transfer");
		//header("Content-Length: ". count($content).";");
		header("Content-Disposition: attachment; filename=$filename");
		header("Content-Type: application/octet-stream; ");
		//header("Content-Transfer-Encoding: binary");

		rewind($buffer);
		while (!feof($buffer)) {
			echo fread($buffer, 8192);
		}
		fclose($buffer);
		exit;
	}
}
if(isset($_GET['did'])) {
	$reload = true;
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
	    Message::AddError(__tr('Connexion impossible : ') . mysqli_error());
		header('Location: language.php');
		exit;
	}

	$sql = "DELETE FROM language WHERE code='".$_GET['did']."';";
	$res = mysqli_query($link, $sql);
	if($res)
		Message::AddSuccess(__tr('Successfully remove the language'));
	else
		Message::AddError(__tr('Error removing the language'));
	mysqli_close($link);
}
if(isset($_POST['sid'])) {
	$reload = true;
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
	    Message::AddError(__tr('Connexion impossible : ') . mysqli_error());
		header('Location: language.php');
		exit;
	}

	$sql = "INSERT INTO language SET code='".$_POST['sid']."', language='".addslashes($_POST['language'])."', public='".$_POST['public']."' ON DUPLICATE KEY UPDATE language='".$_POST['language']."', public='".$_POST['public']."'";
	$res = mysqli_query($link, $sql);
	if($res)
		Message::AddSuccess(__tr('Successfully save the language'));
	else
		Message::AddError(__tr('Error saving the language'));
	mysqli_close($link);
}
if($reload) {
	header('Location: language.php');
	exit;
}
include(ROOT_SITE.'include/message.php');
?>
<div class="card">
	<h5 class="card-header">
		<i class="icon icon-th"></i> <?php echo __tr("Languages available") ?>
		<a href="?manual=add" class="btn btn-sm btn-primary float-right"><?php echo __tr("Add a language") ?></a>
	</h5>
	<div class="card-body">
		<?php
		if(isset($_GET['eid'])) {
			$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
			if (!$link) {
					die('Connexion impossible : ' . mysqli_error());
			}

			$sql = "SELECT * FROM language WHERE code='".$_GET['eid']."'";
			$res = mysqli_query($link, $sql);
			if($row = mysqli_fetch_assoc($res))
			{
		?>
								<form id="edit-profile" class="form-horizontal" method="post">
												<input type="hidden" name="sid" value="<?php echo $row['code'] ?>">
										<div class="control-group">
											<label class="control-label" for="sentence"><?php echo __tr('Code') ?></label>
											<div class="controls">
												<input type="text" class="input-xlarge disabled span8" id="sentence" value="<?php echo $row['code'] ?>" disabled>
											</div> <!-- /controls -->
										</div> <!-- /control-group -->

										<div class="control-group">
											<label class="control-label" for="translate"><?php echo __tr('Language') ?></label>
											<div class="controls">
												<input type="text" class="input-xlarge span8" name="language" value="<?php echo $row['language'] ?>">
											</div> <!-- /controls -->
										</div> <!-- /control-group -->
										<div class="control-group">
											<label class="control-label" for="translate"><?php echo __tr('Public') ?></label>
											<div class="controls">
              <label class="checkbox">
		<input type="radio" name="public" value="0" <?php echo $row['public'] == 0 ? 'checked="checked"' : ''; ?>/> <?php echo __tr("In development") ?><br />
              </label>
              <label class="checkbox">
		<input type="radio" name="public" value="1" <?php echo $row['public'] ? 'checked="checked"' : ''; ?> /> <?php echo __tr("Public") ?>
              </label>
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
if(isset($_GET['manual']) && $_GET['manual'] == 'add') {
?>
								<form id="edit-profile" class="form-horizontal" method="post">
										<div class="control-group">
											<label class="control-label" for="sentence"><?php echo __tr('Code') ?></label>
											<div class="controls">
												<input type="text" class="input-xlarge span8" name="sid" value="">
											</div> <!-- /controls -->
										</div> <!-- /control-group -->

										<div class="control-group">
											<label class="control-label" for="translate"><?php echo __tr('Language') ?></label>
											<div class="controls">
												<input type="text" class="input-xlarge span8" name="language" value="">
											</div> <!-- /controls -->
										</div> <!-- /control-group -->
										<div class="control-group">
											<label class="control-label" for="translate"><?php echo __tr('Public') ?></label>
											<div class="controls">
              <label class="checkbox">
		<input type="radio" name="public" value="0" checked="checked"/> <?php echo __tr("In development") ?><br />
              </label>
              <label class="checkbox">
		<input type="radio" name="public" value="1" /> <?php echo __tr("Public") ?>
              </label>
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
else if(isset($_GET['ixid'])) {
?>
								<form id="edit-profile" class="form-horizontal" method="post">
										<div class="control-group">
											<label class="control-label" for="sentence"><?php echo __tr('Import') ?></label>
											<div class="controls">
												<input type="file" class="input-xlarge span8" name="sid" value="">
											</div> <!-- /controls -->
										</div> <!-- /control-group -->

										<div class="form-actions">
											<button type="submit" class="btn btn-primary"><?php echo __tr('Load file') ?></button>
											<a href="language.php" class="btn"><?php echo __tr('Back') ?></a>
										</div> <!-- /form-actions -->
								</form>
	<br />

								<form id="edit-profile" class="form-horizontal" method="post">
										<div class="control-group">
											<label class="control-label" for="translate"><?php echo __tr('Format') ?></label>
											<div class="controls">
              <label class="checkbox">
		<input type="radio" name="format" value="csv" checked="checked"/> <?php echo __tr("CSV File") ?><br />
              </label>
              <label class="checkbox">
		<input type="radio" name="format" value="xml" /> <?php echo __tr("XML") ?>
              </label>
											</div> <!-- /controls -->
										</div> <!-- /control-group -->

										<div class="form-actions">
											<button type="submit" class="btn btn-primary"><?php echo __tr('Generate file') ?></button>
											<a href="language.php" class="btn"><?php echo __tr('Back') ?></a>
										</div> <!-- /form-actions -->
								</form>
<?php
}
else {
?>
		<table class="table table-bordered table-striped span11">
			<thead>
				<tr>
					<th class="col-md-1"><?php echo __tr('Code') ?></th>
					<th class="col-md-2"><?php echo __tr('Language') ?></th>
					<th class="col-md-1"><?php echo __tr('Public') ?></th>
					<th class="col-md-1"><?php echo __tr('Completion') ?></th>
					<th class="col-md-7"><?php echo __tr('Actions') ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
					$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
					if (!$link) {
							die('Connexion impossible : ' . mysqli_error());
					}

					$sql = "SELECT *, (SELECT COUNT(*) FROM sentence) as total, (SELECT COUNT(*) FROM translation WHERE translation.language=language.code) as translated FROM language;";
					$res = mysqli_query($link, $sql);
					while($row = mysqli_fetch_assoc($res))
					{
						$percent = round(($row['code'] == 'en' ? $row['total'] : $row['translated']) / $row['total'] * 100, 1);
				?>
				<tr>
					<td><?php echo $row['code']; ?></td>
					<td><?php echo $row['language']; ?></td>
					<td><?php echo $row['public']; ?></td>
					<td><?php echo __tr("%1 %", $percent); ?></td>
					<td>
						<a href="?eid=<?php echo $row['code']; ?>" class="btn btn-sm btn-primary"><i class="icon icon-edit"></i> <?php echo __tr('Edit') ?></a>
						<a href="?ixid=<?php echo $row['code']; ?>" class="btn btn-sm btn-success"><i class="icon icon-file"></i> <?php echo __tr('Import / Export') ?></a>
						<a href="translation.php?elanguage=<?php echo $row['code']; ?>" class="btn btn-sm btn-warning"><i class="icon icon-cog"></i> <?php echo __tr('Translate') ?></a>
						<a href="?did=<?php echo $row['code']; ?>" class="btn btn-sm btn-danger"><i class="icon icon-trash"></i> <?php echo __tr('Remove') ?></a>
					</td>
				</tr>
				<?php
					}
					mysqli_close($link);
				?>
			</tbody>
		</table>
		<?php } ?>
	</div>
</div>
<?php
require_once '../include/append.php';
?>
