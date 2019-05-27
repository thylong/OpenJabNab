<?php
require_once 'include/common.php';
if(!isset($_SESSION['token']) || !$Infos['isAdmin'])
	header('Location: index.php');

function slugify($text) {
	$text = preg_replace('#[^\\pL\d]+#u', '-', $text);
	//$text = preg_replace('/\pM*/u','',normalizer_normalize( $text, Normalizer::FORM_D));
	$text = trim($text, '-');

	if (function_exists('iconv'))
	{
	    setlocale(LC_COLLATE, 'fr_FR.utf8');
	    setlocale(LC_CTYPE, 'fr_FR.utf8');
	    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
	}

	$text = strtolower($text);

	$text = preg_replace('#[^-\w]+#', '', $text);

	if (empty($text))
	{
	    return 'n-a';
	}

	return $text;
}

$reload = false;
if(isset($_POST['name']) && count($_FILES)) {
	$reload = true;
	$name = $_POST['name'];
	$res = move_uploaded_file($_FILES['file']['tmp_name'], ROOT_SITE . 'faqs/' . $name);
}
if(isset($_GET['did']) && is_numeric($_GET['did'])) {
	$reload = true;
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
	    Message::AddError(__tr('Connexion impossible : ') . mysqli_error());
		header('Location: /admin/faq.php');
		exit;
	}

	$sql = "DELETE FROM faq WHERE id='".$_GET['did']."';";
	$res = mysqli_query($link, $sql);
	if($res)
		Message::AddSuccess(__tr('Successfully remove the question'));
	else
		Message::AddError(__tr('Error removing the question'));
	mysqli_close($link);
}
if(isset($_POST['aid']) && is_numeric($_POST['aid'])) {
	$reload = true;
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
	    Message::AddError(__tr('Connexion impossible : ') . mysqli_error());
		header('Location: /admin/faq.php');
		exit;
	}

	$i = '';
	if($_POST['aid'] != -1) {
		$i = 'id='.$_POST['aid'].', ';
	}
	$keyword = ','.preg_replace('/ /', ',', $_POST['keyword']).',';
	$keyword = preg_replace('/,+/', ',', $keyword);
	$slug = slugify($_POST['question']);
	$sql = "INSERT INTO faq SET $i viewed=0, annotation='".addslashes($_POST['annotation'])."', slug='".addslashes($slug)."', question='".addslashes($_POST['question'])."', keyword='".addslashes($keyword)."', answer='".addslashes($_POST['answer'])."', language='".$_POST['language']."'  ON DUPLICATE KEY UPDATE question='".addslashes($_POST['question'])."', answer='".addslashes($_POST['answer'])."', language='".$_POST['language']."', slug='".addslashes($slug)."', keyword='".addslashes($keyword)."', annotation='".addslashes($_POST['annotation'])."'";
	$res = mysqli_query($link, $sql);
	if($res)
		Message::AddSuccess(__tr('Successfully save the question'));
	else
		Message::AddError(__tr('Error saving the question'));

	mysqli_close($link);
}
if($reload) {
	header('Location: /admin/faq.php');
	exit;
}
include(ROOT_SITE.'include/message.php');
?>
	      <div class="row">
	      	<div class="span12">
	      		<div class="widget">
					<div class="widget-header">
						<i class="icon-th-large"></i>
						<h3><?php echo __tr("Manage FAQs") ?></h3>
					</div> <!-- /widget-header -->
					<div class="widget-content">
<?php
if(isset($_GET['upload'])) {
?>
					<form id="edit-profile" class="form-horizontal" method="post" enctype="multipart/form-data">
						<div class="control-group">
							<label class="control-label" for="name"><?php echo __tr('Name') ?></label>
							<div class="controls">
								<input type="text" class="input-xlarge span8" name="name" id="name" value="">
							</div> <!-- /controls -->
						</div> <!-- /control-group -->
						<div class="control-group">
							<label class="control-label" for="sentence"><?php echo __tr('Import') ?></label>
							<div class="controls">
								<input type="file" class="input-xlarge span8" name="file" value="">
							</div> <!-- /controls -->
						</div> <!-- /control-group -->

						<div class="form-actions">
							<button type="submit" class="btn btn-primary"><?php echo __tr('Load file') ?></button>
# 							<a href="/admin/faq.php" class="btn"><?php echo __tr('Back') ?></a>
						</div> <!-- /form-actions -->
					</form>
<?php
} else if(isset($_GET['eid']) && is_numeric($_GET['eid'])) {
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
	    die('Connexion impossible : ' . mysqli_error());
	}

	$sql = "SELECT * FROM faq WHERE id=" . $_GET['eid'];
	$res = mysqli_query($link, $sql);
	if($row = mysqli_fetch_assoc($res))
	{
?>
					<form id="edit-profile" class="form-horizontal" method="post">
									<input type="hidden" name="aid" value="<?php echo $row['id'] ?>">
							<div class="control-group">
								<label class="control-label" for="language"><?php echo __tr('Language') ?></label>
								<div class="controls">
				<select name="language">
<?php
	$sql2 = "SELECT * FROM language";

	$res2 = mysqli_query($link, $sql2);
	while($row2 = mysqli_fetch_assoc($res2))
	{
?>
<option value="<?php echo $row2['code'] ?>"<?php if($row['language'] == $row2['code']) { ?> selected="selected"<?php } ?>><?php echo $row2['language'] ?></option>
<?php
	}
?>
				</select>
								</div> <!-- /controls -->
							</div> <!-- /control-group -->
							<div class="control-group">
								<label class="control-label" for="question"><?php echo __tr('Question') ?></label>
								<div class="controls">
									<input type="text" class="input-xlarge span8" name="question" id="question" value="<?php echo htmlentities(($row['question'])) ?>">
								</div> <!-- /controls -->
							</div> <!-- /control-group -->

							<div class="control-group">
								<label class="control-label" for="answer"><?php echo __tr('Answer') ?></label>
								<div class="controls">
									<textarea class="input-xlarge span8" style="height: 200px" name="answer"><?php echo htmlentities(($row['answer'])) ?></textarea>
								</div> <!-- /controls -->
							</div> <!-- /control-group -->
							<div class="control-group">
								<label class="control-label" for="keyword"><?php echo __tr('Keywords') ?></label>
								<div class="controls">
									<input type="text" class="input-xlarge span8" name="keyword" id="keyword" value="<?php echo htmlentities((preg_replace('/,/', ' ', $row['keyword']))) ?>">
								</div> <!-- /controls -->
							</div> <!-- /control-group -->
							<div class="control-group">
								<label class="control-label" for="annotation"><?php echo __tr('Annotation') ?></label>
								<div class="controls">
									<textarea class="input-xlarge span8" style="height: 200px" name="annotation"><?php echo htmlentities(($row['annotation'])) ?></textarea>
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
?>
					<form id="edit-profile" class="form-horizontal" method="post">
									<input type="hidden" name="aid" value="-1">
							<div class="control-group">
								<label class="control-label" for="language"><?php echo __tr('Language') ?></label>
								<div class="controls">
				<select name="language">
<?php
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
	    die('Connexion impossible : ' . mysqli_error());
	}
	$sql2 = "SELECT * FROM language";

	$res2 = mysqli_query($sql2, $link);
	while($row2 = mysqli_fetch_assoc($res2))
	{
?>
<option value="<?php echo $row2['code'] ?>"><?php echo $row2['language'] ?></option>
<?php
	}
	mysqli_close($link);
?>
				</select>
								</div> <!-- /controls -->
							</div> <!-- /control-group -->
							<div class="control-group">
								<label class="control-label" for="question"><?php echo __tr('Question') ?></label>
								<div class="controls">
									<input type="text" class="input-xlarge span8" name="question" id="question" value="">
								</div> <!-- /controls -->
							</div> <!-- /control-group -->

							<div class="control-group">
								<label class="control-label" for="answer"><?php echo __tr('Answer') ?></label>
								<div class="controls">
									<textarea class="input-xlarge span8" style="height: 200px" name="answer"></textarea>
								</div> <!-- /controls -->
							</div> <!-- /control-group -->
							<div class="control-group">
								<label class="control-label" for="keyword"><?php echo __tr('Keywords') ?></label>
								<div class="controls">
									<input type="text" class="input-xlarge span8" name="keyword" id="keyword" value="">
								</div> <!-- /controls -->
							</div> <!-- /control-group -->
							<div class="control-group">
								<label class="control-label" for="annotation"><?php echo __tr('Annotation') ?></label>
								<div class="controls">
									<textarea class="input-xlarge span8" style="height: 200px" name="annotation"></textarea>
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
}
else {
?>
<table class="table table-bordered table-striped span11">
	<thead>
	<tr>
		<th class="span7"><?php echo __tr('Question') ?></th>
		<th class="span1"><?php echo __tr('Language') ?></th>
		<th class="span1"><?php echo __tr('Viewed') ?></th>
		<th class="span3"><?php echo __tr('Actions') ?></th>
	</tr>
	</thead>
<tbody>
<?php
	$i = 0;
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
	    die('Connexion impossible : ' . mysqli_error());
	}

	$sql = "SELECT * FROM faq ORDER BY viewed DESC";
	$res = mysqli_query($link, $sql);
	while($row = mysqli_fetch_assoc($res))
	{
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td title="<?php echo addslashes(slugify($row['question'])) ?>"><?php echo $row['question']; ?></td>
		<td><?php echo $row['language']; ?></td>
		<td><?php echo $row['viewed']; ?></td>
		<td><a  href="/admin/faq.php?eid=<?php echo $row['id']; ?>" class="btn btn-primary"><?php echo __tr('Edit') ?></a> &nbsp;<a  href="/admin/faq.php?did=<?php echo $row['id']; ?>" class="btn btn-danger"><?php echo __tr('Remove') ?></a></td>
	</tr>
<?php
	}
	mysqli_close($link);
?>
</tbody>
</table>
<a href="/admin/faq.php?eid=-1" class="btn btn-primary"><?php echo __tr("Add a question") ?></a>
<a href="/admin/faq.php?upload" class="btn btn-primary"><?php echo __tr("Upload a file") ?></a>

<?php
}
?>
					</div> <!-- /widget-content -->
				</div> <!-- /widget -->
		    </div> <!-- /span12 -->
	      </div> <!-- /row -->
<script type="text/javascript" src="/media/js/nicEdit.js"></script>
<script type="text/javascript">
	bkLib.onDomLoaded(function() { nicEditors.allTextAreas() });
</script>
<?php
require_once 'include/append.php';
?>
