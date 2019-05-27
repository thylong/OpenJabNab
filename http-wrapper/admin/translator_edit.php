<?php
require_once "include/common.php";
$translates = getTranslates($_SESSION['login']);
if(!isset($_SESSION['token']) || (!$Infos['isAdmin'] && !in_array($_GET['lng'], $translates)))
	header('Location: index.php');

$reload = false;

if(isset($_GET['generate']) && $_GET['generate'] == 'tr') {
	require('translator.generate.php');
	apcu_delete(APC_PREFIX.'ojn_tr_' . $_GET['lng']);
	$reload = true;
	Message::AddSuccess(__tr("Translation cache generated", $new));
}
if(isset($_POST['sid']) && is_numeric($_POST['sid'])) {
	$reload = true;
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
	    Message::AddError(__tr('Connexion impossible : ') . mysqli_error());
		header('Location: translator_edit.php?lng=' . $_GET['lng']);
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

if($reload) {
	header('Location: translator_edit.php?lng=' . $_GET['lng']);
	exit;
}
include('include/message.php');
?>
	      <div class="row">
	      	<div class="span12">
	      		<div class="widget">
					<div class="widget-header">
						<i class="icon-th-large"></i>
						<h3><?php echo __tr("Online translation (%1)", $_GET['lng']) ?></h3>
					</div> <!-- /widget-header -->
					<div class="widget-content">
<a href="translator.php?lng=<?php echo $_GET['lng'] ?>" class="btn ">&lt; <?php echo __tr("Back") ?></a>
<a style="float:right" href="translator_edit.php?lng=<?php echo $_GET['lng'] ?>&generate=tr" class="btn btn-success"><?php echo __tr("Generate translations") ?></a>
<br /><br />
<?php
if(isset($_GET['eid']) && is_numeric($_GET['eid'])) {
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
	    die('Connexion impossible : ' . mysqli_error());
	}

	$sql = "SELECT translation.translation, translation.language, sentence.sentence, sentence.id, translation.note FROM sentence LEFT JOIN translation ON translation.sentence_id = sentence.id AND translation.language='".$_GET['lng']."' WHERE sentence.id=" . $_GET['eid'];
	$res = mysqli_query($link, $sql);
	if($row = mysqli_fetch_assoc($res))
	{
?>
								<form id="edit-profile" class="form-horizontal" method="post">
												<input type="hidden" name="sid" value="<?php echo $row['id'] ?>">
												<input type="hidden" name="language" value="<?php echo $_GET['lng'] ?>">
										<div class="control-group">	
											<label class="control-label" for="sentence"><?php echo __tr('Original sentence') ?></label>
											<div class="controls">
												<input type="text" class="input-xlarge disabled span8" id="sentence" value="<?php echo htmlentities($row['sentence']) ?>" disabled>
											</div> <!-- /controls -->				
										</div> <!-- /control-group -->
										
										<div class="control-group">											
											<label class="control-label" for="translate"><?php echo __tr('Translation') ?></label>
											<div class="controls">
												<input type="text" class="input-xlarge span8" name="translate" value="<?php echo htmlentities($row['translation']) ?>">
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
else {
?>
<table class="table table-bordered table-striped span11">
	<thead>
	<tr>
		<th class="span4"><?php echo __tr('Original sentence') ?></th>
		<th class="span5"><?php echo __tr('Translations') ?></th>
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

	$sql = "SELECT translation.translation, sentence.sentence, sentence.id, translation.note FROM sentence LEFT JOIN translation ON translation.language='".$_GET['lng']."' AND translation.sentence_id = sentence.id ORDER BY translation.note ASC, sentence ASC";
	$res = mysqli_query($link, $sql);
	while($row = mysqli_fetch_assoc($res))
	{
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><?php echo $row['sentence']; ?></td>
		<td><?php echo $row['translation']; ?></td>
		<td><a  href="translator_edit.php?lng=<?php echo $_GET['lng'] ?>&eid=<?php echo $row['id']; ?>" class="btn btn-primary"><?php echo __tr('Edit') ?></a></td>
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
require_once "include/append.php"
?>
