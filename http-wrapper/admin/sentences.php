<?php
require_once 'include/common.php';
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
	if(!is_array($sentences[$type])) {
		$sentences[$type] = array();
	}
	foreach($languages as $language)
	{
		$list = apcu_fetch(APC_PREFIX.'ojn_sentences_'.$type.'_'.$language);
		if($list === false) {
			$list = $ojnAPI->getApiList("sentences/sentence?action=list&type=".$type."&language=".$language."&".$ojnAPI->getToken());
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
<div class="row">
	<div class="span12">
		<div class="widget">
			<div class="widget-header">
				<i class="icon-th-large"></i>
				<h3><?php echo __tr("Insert new sentence") ?></h3>
				<div class="pull-right">
				<a href="sentences.php?save" class="btn btn-mini btn-primary"><?php echo __tr('Save') ?></a>
				&nbsp; &nbsp;
				</div>
			</div> <!-- /widget-header -->
			<div class="widget-content">
				<div class="tabbable">
					<ul class="nav nav-tabs">
					  <li class="active"><a href="#preset" data-toggle="tab"><?php echo __tr('Preset') ?></a></li>
					  <li><a href="#new" data-toggle="tab"><?php echo __tr('New type or language') ?></a></li>
					  <li><a href="#files" data-toggle="tab"><?php echo __tr('Files') ?></a></li>
					</ul>
					<div class="tab-content">
						<div class="tab-pane active" id="preset">
<form method="POST">
<select name="type" class="span6"><?php foreach($types as $type):?><option <?php if($type == $_sentence_type): ?>selected="selected" <?php endif; ?>value="<?php echo $type ?>"><?php echo $type ?></option><?php endforeach; ?></select>
<select name="language" class="span1"><?php foreach($languages as $language):?><option <?php if($language == $_sentence_language): ?>selected="selected" <?php endif; ?>value="<?php echo $language ?>"><?php echo $language ?></option><?php endforeach; ?></select>
<input class="span11" type="text" name="sentence">
<input type="submit" value="Enregistrer" class="btn btn-mini btn-primary"/>
</form>
						</div>
						<div class="tab-pane" id="new">
<form method="POST">
<input type="text" name="type" class="span6" value="<?php echo $_type; ?>"/>
<input type="text" name="language" class="span1" value="<?php echo $_language; ?>"/>
<input class="span11" type="text" name="sentence">
<input type="submit" value="Enregistrer" class="btn btn-mini btn-primary"/>
</form>
						</div>
						<div class="tab-pane" id="files">
<form enctype="multipart/form-data" method="POST">
Choose a file to upload: <input name="uploadedfile" type="file" /><br />
<input type="submit" class="btn btn-primary" value="<?php echo __tr('Upload file') ?>" />
<a class="btn btn-success" href="sentences.php?export"><?php echo __tr('Export') ?></a>
</form>
						</div>
					</div>
				</div>

			</div>
		</div>
	</div>
</div>
<div class="row">
	<div class="span12">
		<div class="widget">
			<div class="widget-header">
				<i class="icon-th-large"></i>
				<h3><?php echo __tr("List of sentences") ?></h3>
			</div> <!-- /widget-header -->
			<div class="widget-content">

				<form method="post">
				<input class="span2" type="text" name="filter" value="<?php echo $filter ?>">
				<input type="checkbox" value="1"<?php $missing ? ' checked="checked"' : '' ?> name="missing"> <?php echo __tr('Missing only ?') ?>
				<input type="submit" class="btn btn-primary" value="<?php echo __tr('Filter') ?>"/>
				</form>
<?php

?>
<table class="table table-bordered table-striped">
<tr>
<th><?php echo __tr("Type") ?></th>
<th><?php echo __tr("Language") ?></th>
<th><?php echo __tr("Sentences") ?></th>
</tr>
<?php
	foreach( $sentences as $type => $languages):
		foreach( $languages as $language => $list):
			if(!$missing || count($list) == 0):
?>
	<tr>
		<td><?php echo $type ?></td>
		<td><?php echo $language ?></td>
		<td><ul style="list-style: none"><?php foreach($list as $sentence): ?><li><a href="sentences.php?type=<?php echo $type ?>&language=<?php echo $language ?>&delete=<?php echo urlencode(trim($sentence)) ?>" class="btn btn-mini btn-danger">X</a>&nbsp; <?php echo $sentence ?></li><?php endforeach; ?></ul></td>
	</tr>
<?php
			endif;
		endforeach;
	endforeach;
?>
</table>
			</div>
		</div>
	</div>
</div>

<?php
require_once 'include/append.php';
?>
