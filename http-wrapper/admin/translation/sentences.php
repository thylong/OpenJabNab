<?php
require_once '../include/common.php';
$ojnTemplate->setTitle(__tr('Server setup'));

$reload = false;

if(isset($_POST['filter'])/* && strlen(trim($_POST['filter']))*/) {
	$filter = trim($_POST['filter']);
	$_SESSION['filter_sentence'] = $filter;
	$_SESSION['missing'] = $_POST['missing'];
	$reload = true;
}
$filter = !empty($_SESSION['filter_sentence']) ? $_SESSION['filter_sentence'] : '';
$missing = !empty($_SESSION['missing']) ? $_SESSION['missing'] : '';
$_type = !empty($_SESSION['type']) ? $_SESSION['type'] : '';
$_language = !empty($_SESSION['language']) ? $_SESSION['language'] : '';
$_sentence_language = !empty($_SESSION['sentence_language']) ? $_SESSION['sentence_language'] : '';

if(count($_FILES))
{
	$s = 0;
	$file = $_FILES['uploadedfile']['tmp_name'];
	$row = 1;
	if (($handle = fopen($file, "r")) !== FALSE) {
	    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
	        $num = count($data);
		if($num == 3 || $num == 4) {
			$s++;
			$remove = false;
			if($num == 4 && $data[3] == 'remove')
			{
				$remove = true;
			}
			$type = $data[0];
			$language = $data[1];
			$sentence = $data[2];
			if($remove)
			{
				Message::AddFromApi($ojnAPI->getApiString("sentences/sentence?action=remove&type=".$type."&language=".$language."&sentence=".urlencode(trim($sentence))."&".$ojnAPI->getToken()));
			}
			else
			{
				Message::AddFromApi($ojnAPI->getApiString("sentences/sentence?action=add&type=".$type."&language=".$language."&sentence=".urlencode(trim($sentence))."&".$ojnAPI->getToken()));
			}
			apcu_delete(APC_PREFIX.'ojn_sentences_'.$type.'_'.$language);
			usleep(50);
		}
	    }
	    fclose($handle);
	}
	if($s == 0) {
		if (($handle = fopen($file, "r")) !== FALSE) {
		    while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
			$num = count($data);
			if($num == 3 || $num == 4) {
				$s++;
				$remove = false;
				if($num == 4 && $data[3] == 'remove')
				{
					$remove = true;
				}
				$type = $data[0];
				$language = $data[1];
				$sentence = $data[2];
				if($remove)
				{
					Message::AddFromApi($ojnAPI->getApiString("sentences/sentence?action=remove&type=".$type."&language=".$language."&sentence=".urlencode(trim($sentence))."&".$ojnAPI->getToken()));
				}
				else
				{
					Message::AddFromApi($ojnAPI->getApiString("sentences/sentence?action=add&type=".$type."&language=".$language."&sentence=".urlencode(trim($sentence))."&".$ojnAPI->getToken()));
				}
				apcu_delete(APC_PREFIX.'ojn_sentences_'.$_POST['type'].'_'.$_POST['language']);
				usleep(50);
			}
		    }
		    fclose($handle);
		}
	}
	if($s > 0) {
		Message::AddFromApi($ojnAPI->getApiString("sentences/sentence?action=save&type=&language=&".$ojnAPI->getToken()));
	}
	$reload = true;
}
if(!empty($_POST)) {
	if(isset($_POST['type']) && isset($_POST['language'])  && isset($_POST['sentence'])) {
		Message::AddFromApi($ojnAPI->getApiString("sentences/sentence?action=add&type=".$_POST['type']."&language=".$_POST['language']."&sentence=".urlencode(trim($_POST['sentence']))."&".$ojnAPI->getToken()));
		apcu_delete(APC_PREFIX.'ojn_sentences_'.$_POST['type'].'_'.$_POST['language']);
		$_SESSION['sentence_type'] = $_POST['type'];
		$_SESSION['sentence_language'] = $_POST['language'];

		$reload = true;
	}
}
if(isset($_GET['save'])) {
	Message::AddFromApi($ojnAPI->getApiString("sentences/sentence?action=save&type=&language=&".$ojnAPI->getToken()));
	$reload = true;
}
if(isset($_GET['delete'])) {
	Message::AddFromApi($ojnAPI->getApiString("sentences/sentence?action=remove&type=".$_GET['type']."&language=".$_GET['language']."&sentence=".urlencode(trim($_GET['delete']))."&".$ojnAPI->getToken()));
	apcu_delete(APC_PREFIX.'ojn_sentences_'.$_GET['type'].'_'.$_GET['language']);
	$reload = true;
}
if(isset($_GET['export'])) {
	Message::AddFromApi($ojnAPI->getApiString("sentences/sentence?action=save&type=&language=&".$ojnAPI->getToken()));
}
if($reload)
{
	header("Location: sentences.php");
	exit();
}

$types = $ojnAPI->getApiList("sentences/type?action=list&".$ojnAPI->getToken());
sort($types);

foreach($types as $k => $type) {
	if(strlen($filter) && !preg_match('/'.$filter.'/', $type)) {
		unset($types[$k]);
	}
}
$languages = $ojnAPI->getApiList("sentences/language?action=list&".$ojnAPI->getToken());
sort($languages);
$sentences = array();
foreach($types as $type)
{
	if(!isset($sentences[$type]) || !is_array($sentences[$type])) {
		$sentences[$type] = array();
	}
	foreach($languages as $language)
	{
		$list = apcu_fetch(APC_PREFIX.'ojn_sentences_'.$type.'_'.$language);
		if($list === false) {
			$list = $ojnAPI->getApiList("sentences/sentence?action=list&type=".$type."&language=".$language."&".$ojnAPI->getToken());
			foreach($list as $i => $v)
				$list[$i] = !empty($v) ? $v : '';
			apcu_store(APC_PREFIX.'ojn_sentences_'.$type.'_'.$language, $list, 48 * 3600);
		}
		$sentences[$type][$language] = $list;
	}
}
if(isset($_GET['export'])) {
	ob_end_clean();
	$file = "/tmp/sentences.csv";
	$fp = fopen($file, 'w+');
	foreach( $sentences as $type => $languages):
		foreach( $languages as $language => $list):
			foreach( $list as $sentence):
				fputcsv($fp, array($type, $language, $sentence));
			endforeach;
		endforeach;
	endforeach;
	fclose($fp);

	$size = filesize($file);
	header("Content-Type: application/force-download; name=\"" . basename($file) . "\"");
	header("Content-Transfer-Encoding: binary");
	header("Content-Length: $size");
	header("Content-Disposition: attachment; filename=\"" . basename($file) . "\"");
	header("Expires: 0");
	header("Cache-Control: no-cache, must-revalidate");
	header("Pragma: no-cache");
	readfile($file);
	exit();
}

include(ROOT_SITE.'include/message.php');


?>
<div class="card">
	<h5 class="card-header">
		<i class="icon icon-plus"></i> <?php echo __tr("Insert new sentence") ?>
		<a href="?save" class="btn btn-sm btn-primary float-right"><?php echo __tr('Save') ?></a>
	</h5>
	<div class="card-body">
		<ul class="nav nav-tabs">
			<li class="nav-item"><a class="nav-link active" href="#preset" data-toggle="tab"><?php echo __tr('Preset') ?></a></li>
			<li class="nav-item"><a class="nav-link" href="#new" data-toggle="tab"><?php echo __tr('New type or language') ?></a></li>
			<li class="nav-item"><a class="nav-link" href="#files" data-toggle="tab"><?php echo __tr('Files') ?></a></li>
		</ul>

		<div class="tab-content pt-2">

			<div class="tab-pane active" id="preset">
				<form method="post">
					<div class="form-group row">
						<label class="col-md-1 col-form-label" for="type"><?php echo __tr('Type'); ?></label>
						<div class="col-md-4">
							<select name="type" class="form-control">
								<?php foreach($types as $type):?>
									<option <?php if($type == $_type): ?>selected="selected" <?php endif; ?>value="<?php echo $type ?>"><?php echo $type ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<label class="col-md-1 col-form-label" for="type"><?php echo __tr('Language'); ?></label>
						<div class="col-md-1">
							<select name="language" class="form-control">
								<?php foreach($languages as $language):?>
								<option <?php if($language == $_sentence_language): ?>selected="selected" <?php endif; ?>value="<?php echo $language ?>"><?php echo $language ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
					<div class="form-group row">
						<label class="col-md-1 col-form-label" for="type"><?php echo __tr('Sentence'); ?></label>
						<div class="col-md-10">
							<input class="form-control" type="text" name="sentence">
						</div>
					</div>
					<div class="form-group row">
						<div class="col-md-10 offset-md-1">
							<input type="submit" value="Enregistrer" class="btn btn-sm btn-primary"/>
						</div>
					</div>
				</form>
			</div>

			<div class="tab-pane" id="new">
				<form method="post">
					<div class="form-group row">
						<label class="col-md-1 col-form-label" for="type"><?php echo __tr('Type'); ?></label>
						<div class="col-md-4">
							<input type="text" name="type" class="form-control" value="<?php echo $_type; ?>"/>
						</div>
						<label class="col-md-1 col-form-label" for="type"><?php echo __tr('Language'); ?></label>
						<div class="col-md-1">
							<input type="text" name="language" class="form-control" value="<?php echo $_language; ?>"/>
						</div>
					</div>
					<div class="form-group row">
						<label class="col-md-1 col-form-label" for="type"><?php echo __tr('Sentence'); ?></label>
						<div class="col-md-10">
							<input class="form-control" type="text" name="sentence">
						</div>
					</div>
					<div class="form-group row">
						<div class="col-md-10 offset-md-1">
							<input type="submit" value="Enregistrer" class="btn btn-sm btn-primary"/>
						</div>
					</div>
				</form>
			</div>

			<div class="tab-pane" id="files">
				<form enctype="multipart/form-data" method="post">
					<div class="form-group row">
						<label class="col-md-2 col-form-label" for="uploadedfile"><?php echo __tr('Choose a file to upload'); ?></label>
						<div class="col-md-6">
							<input class="form-control" name="uploadedfile" type="file" />
						</div>
						<div class="col-md-4">
							<input type="submit" class="btn btn-sm btn-primary" value="<?php echo __tr('Upload file') ?>" />
						</div>
					</div>
				</form>
				<div class="row">
					<div class="col">
						<a class="btn btn-sm btn-success" href="?export"><?php echo __tr('Export') ?></a>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="card">
	<div class="card-header">
		<div class="row">
		<h5 class="col-md-6 my-auto">
			<i class="icon icon-th"></i> <?php echo __tr("List of sentences") ?>
		</h5>
		<form method="post" class="form-inline col-md-6">
			<div class="row">
				<div class="form-group">
					<label class="col-md-2 col-form-label" for="filter"><?php echo __tr('Filter'); ?></label>
					<div class="col-md-10">
						<input class="form-control" type="text" name="filter" value="<?php echo $filter ?>">
					</div>
				</div>
				<div class="form-group">
					<div class="col-sm-1">
						<input class="form-check-input" type="checkbox" value="1"<?php echo $missing ? ' checked="checked"' : '' ?> name="missing">
					</div>
					<label class="col-md-9 col-form-label" for="missing" ><?php echo __tr('Missing only ?') ?></label>
				</div>
				<div class="form-group">
					<div class="col-sm-1">
						<input type="submit" class="btn btn-sm btn-primary" value="<?php echo __tr('Filter') ?>"/>
					</div>
				</div>
			</div>
		</form>
		</div>
	</div>
	<div class="card-body">
		<table class="table table-bordered table-striped">
			<tr>
				<th class="col-md-4"><?php echo __tr("Type") ?></th>
				<th class="col-md-1"><?php echo __tr("Language") ?></th>
				<th class="col-md-7"><?php echo __tr("Sentences") ?></th>
			</tr>
			<?php
				foreach( $sentences as $type => $languages):
					foreach( $languages as $language => $list):
						if(!$missing || empty($list)):
			?>
			<tr>
				<td><?php echo $type ?></td>
				<td><?php echo $language ?></td>
				<td>
					<ul class="list-group">
						<?php foreach($list as $sentence): ?>
						<li class="list-group-item">
						<a href="?type=<?php echo $type ?>&language=<?php echo $language ?>&delete=<?php echo urlencode(trim($sentence)) ?>" class="btn btn-sm btn-danger"><i class="icon icon-trash"></i></a> <?php echo $sentence ?></li>
						<?php endforeach; ?>
					</ul>
				</td>
			</tr>
			<?php
					endif;
				endforeach;
			endforeach;
		?>
		</table>
	</div>
</div>

<?php require_once '../include/append.php'; ?>
