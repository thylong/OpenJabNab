<?php
require_once "../include/common.php";

$reload = false;

if(isset($_GET['generate']) && $_GET['generate'] == 'tr') {
	require('generate.php');
	apcu_delete(APC_PREFIX.'ojn_tr_' . $_GET['lng']);
	$reload = true;
	Message::AddSuccess(__tr("Translation cache generated", $new));
}
if(isset($_POST['sid']) && is_numeric($_POST['sid']))
{
	$reload = true;
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
	    Message::AddError(__tr('Connexion impossible : ') . mysqli_error());
		header('Location: ?lng=' . $_GET['lng']);
		exit;
	}

	$sql = "INSERT INTO translation SET sentence_id='".$_POST['sid']."', translation='".addslashes($_POST['translate'])."', language='".$_POST['language']."', note=0 ON DUPLICATE KEY UPDATE translation='".addslashes($_POST['translate'])."', language='".$_POST['language']."', note=0";
	//echo $sql;
	$res = mysqli_query($link, $sql) or die(mysqli_error($link));
	if($res)
		Message::AddSuccess(__tr('Successfully save the translation'));
	else
		Message::AddError(__tr('Error saving the translation'));
	mysqli_close($link);
}
$row = NULL;
if(isset($_GET['eid']) && is_numeric($_GET['eid']))
{
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link)
			die('Connexion impossible : ' . mysqli_error());

	$sql = 'SELECT translation.translation,
								translation.language,
								translation.note,
								sentence.id,
								sentence.sentence
						FROM sentence
						LEFT JOIN translation
							ON translation.sentence_id = sentence.id
							AND translation.language=\''.$_GET['lng'].'\'
						WHERE sentence.id=' . $_GET['eid'];
	$res = mysqli_query($link, $sql);
	$row = mysqli_fetch_assoc($res);
	if(!$row)
		$reload=true;
}
if($reload) {
	header('Location: ?lng=' . $_GET['lng']);
	exit;
}
include(ROOT_SITE.'include/message.php');
?>
<div class="card">
	<h5 class="card-header">
		<?php /*<a href="?lng=<?php echo $_GET['lng'] ?>" class="btn btn-sm btn-secondary">&lt; <?php echo __tr("Back") ?></a>*/?>
		<i class="icon icon-edit"></i> <?php echo __tr("Online translation (%1)", $_GET['lng']) ?>
		<a href="?lng=<?php echo $_GET['lng'] ?>&generate=tr" class="btn btn-sm btn-success float-right "><?php echo __tr("Generate translations") ?></a>
	</h5>
	<div class="card-body">
		<?php	if(!empty($row)):	?>
		<form method="post">
			<input type="hidden" name="sid" value="<?php echo $_GET['eid']; ?>"  />
			<input type="hidden" name="language" value="<?php echo $_GET['lng']; ?>"  />
			<div class="form-group row">
				<label class="col-sm-2 col-form-label" for="sentence"><?php echo __tr('Original sentence') ?></label>
				<div class="col-sm-10">
						<input type="text" class="form-control disabled" name="sentence" value="<?php echo htmlentities($row['sentence']) ?>" disabled>
				</div>
			</div>
			<div class="form-group row">
				<label class="col-sm-2 col-form-label" for="translate"><?php echo __tr('Original sentence') ?></label>
				<div class="col-sm-10">
						<input type="text" class="form-control" name="translate" value="<?php echo htmlentities($row['translation']) ?>">
				</div>
			</div>
			<div class="form-group row">
				<div class="col-sm-10 offset-sm-2">
					<button type="submit" class="btn btn-primary"><?php echo __tr('Save') ?></button>
					<a href="?lng=<?php echo $_GET['lng']; ?>" class="btn btn-warning"><?php echo __tr('Cancel') ?></a>
				</div>
			</div>
		</form>
		<?php	else: ?>
		<table class="table table-bordered table-striped span11">
			<thead>
				<tr>
					<th class="col-sm-5"><?php echo __tr('Original sentence') ?></th>
					<th class="col-sm-5"><?php echo __tr('Translations') ?></th>
					<th class="col-sm-1"><?php echo __tr('Actions') ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
					$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
					if (!$link)
							die('Connexion impossible : ' . mysqli_error());

					$sql = 'SELECT 	translation.translation,
												 	translation.note,
													sentence.id,
													sentence.sentence
										FROM sentence
								LEFT JOIN translation
											ON translation.language=\''.$_GET['lng'].'\'
										AND translation.sentence_id = sentence.id
								ORDER BY translation.note ASC,
													sentence ASC';
					$res = mysqli_query($link, $sql);
					mysqli_close($link);
					while($row = mysqli_fetch_assoc($res)):
				?>
				<tr>
					<td><?php echo $row['sentence']; ?></td>
					<td><?php echo $row['translation']; ?></td>
					<td>
						<a href="?lng=<?php echo $_GET['lng'] ?>&eid=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary"><i class="icon icon-edit"></i> <?php echo __tr('Edit') ?></a></td>
				</tr>
				<?php endwhile; ?>
			</tbody>
		</table>
		<?php endif; ?>
	</div>
</div>
<?php
require_once "../include/append.php"
?>
