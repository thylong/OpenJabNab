<?php
require_once "../include/common.php";
$translates = getTranslates($_SESSION['login']);
if(!isset($_SESSION['token']) || (!$Infos['isAdmin'] && !in_array($_GET['lng'], $translates)))
	header('Location: /index.php');

$reload = false;
if(count($_POST) && isset($_POST['format'])) {
	ob_end_clean();
	$filename = 'openjabnab-'.$_GET['lng'].'.'.$_POST['format'];
	$buffer = fopen('php://temp', 'r+');

	if($_POST['format'] == 'csv') {
		fputcsv($buffer, array('id', 'origine', 'translation'), ';');
	}
	if($_POST['format'] == 'xml') {
		fwrite($buffer, '<?xml version="1.0" encoding="utf-8"?>'."\n");
		fwrite($buffer, '<!DOCTYPE TS>'."\n");
		fwrite($buffer, '<TS version="2.0" language="'.$_GET['lng'].'" sourcelanguage="en">'."\n");
		fwrite($buffer, '<context>'."\n");
		fwrite($buffer, '    <name>openJabNab</name>'."\n");
	}
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
	    die('Connexion impossible : ' . mysqli_error());
	}

	$sql = "SELECT sentence.id, sentence.sentence, translation.translation FROM sentence LEFT JOIN translation ON translation.language='".$_GET['lng']."' AND translation.sentence_id = sentence.id ORDER BY sentence ASC";
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
} else if(count($_FILES) ) {
	$generate = false;
	$name = $_FILES['file']['name'];
	$type = $_FILES['file']['type'];


$finfo = finfo_open(FILEINFO_MIME_TYPE); // Retourne le type mime à la extension mimetype
$type = finfo_file($finfo, $_FILES['file']['tmp_name']);
finfo_close($finfo);
	if($type == 'text/xml')
	{
		$content = file_get_contents($_FILES['file']['tmp_name']);
		$xml = simplexml_load_string($content);
		$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		if (!$link) {
		    Message::AddError(__tr('Connexion impossible : ') . mysqli_error());
			header('Location: ?lng=' . $_GET['lng']);
			exit;
		}
		$total = 0;
		$unfinished = 0;
		$error = 0;
		foreach($xml->context->message as $ligne)
		{
			$total++;
			$attr = $ligne->attributes();
			$id = (int)$attr['id'];
			$tr = trim((string)$ligne->translation);
			if(strlen($tr)) {
				$sql = "INSERT INTO translation SET sentence_id='".$id."', translation='".addslashes($tr)."', language='".$_GET['lng']."', note=0 ON DUPLICATE KEY UPDATE translation='".addslashes($tr)."', language='".$_GET['lng']."', note=0";
				$res = mysqli_query($link, $sql);
				if(!$res)
					$error++;
			} else {
				$unfinished++;
			}
		}
		Message::AddInfo(__tr("Import summary : %1 total, %2 unfinished, %3 error", $total, $unfinished, $error));
		mysqli_close($link);
		$reload = true;
		$generate = true;
	} elseif($type == 'text/csv' || $type == 'text/plain')
	{
	$delim = ';';
		$content = file_get_contents($_FILES['file']['tmp_name']);
		$row = 0;
		if (($handle = fopen($_FILES['file']['tmp_name'], "r")) !== FALSE) {
			$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
			if (!$link) {
			    Message::AddError(__tr('Connexion impossible : ') . mysqli_error());
				header('Location: ?lng=' . $_GET['lng']);
				exit;
			}
			$total = 0;
			$unfinished = 0;
			$error = 0;

			$encode = 0;
			if(isset($_POST['utf81']) && $_POST['utf81'] == 1)
				$encode = 1;
			if(isset($_POST['utf82']) && $_POST['utf82'] == 1)
				$encode = 2;

$loc_de = setlocale(LC_ALL, 'fr_FR@euro', 'fr_FR');

		    while (($data = fgetcsv($handle, 1000, $delim)) !== FALSE) {
			$num = count($data);
			if($row++ > 0 && $num == 3) {
					$total++;
				$id = $data[0];
				if($encode == 1)
					$tr = utf8_encode(trim((string)$data[2]));
				elseif($encode == 2)
					$tr = utf8_decode(trim((string)$data[2]));
				else
					$tr = (trim((string)$data[2]));
/*
if($id == 1311 || $id == 2073) {
var_dump($data);
echo "<br />";
echo $encode ." :".$tr;
echo "<br />";
echo "0 :" . trim((string)$data[2]);
echo "<br />";
echo "1 :" . utf8_encode(trim((string)$data[2]));
echo "<br />";
echo "2 :" . utf8_decode(trim((string)$data[2]));
echo "<br />";
echo "<br />";
}
*/
			
				if(strlen($tr)) {
					$sql = "INSERT INTO translation SET sentence_id='".$id."', translation='".addslashes($tr)."', language='".$_GET['lng']."', note=0 ON DUPLICATE KEY UPDATE translation='".addslashes($tr)."', language='".$_GET['lng']."', note=0";
					$res = mysqli_query($link, $sql);
					if(!$res)
						$error++;
				} else {
					$unfinished++;
				}

			}
		    }
		    fclose($handle);
			mysqli_close($link);
		}


			Message::AddInfo(__tr("Import summary : %1 total, %2 unfinished, %3 error", $total, $unfinished, $error));
		$reload = true;
		$generate = true;

	if($generate) {
		$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		if (!$link) {
		    Message::AddError(__tr('Connexion impossible') . ' : '. mysqli_error());
			header('Location: translation.php');
			exit;
		}
		$sql = "SELECT translation.translation, sentence.sentence FROM sentence LEFT JOIN translation ON translation.sentence_id = sentence.id WHERE translation.language='".$_GET['lng']."' ORDER BY translation.note ASC, sentence ASC";
		$res = mysqli_query($link, $sql);
		$t = array();
		while($row = mysqli_fetch_assoc($res))
		{
			$t[$row['sentence']] = $row['translation'];
		}
		mysqli_close($link);
		$content = "<?php\nglobal \$translations;\n\$translations = ".var_export($t, true).";\n";
		file_put_contents("cache/translations.".$_GET['lng'].".php", $content);
		apcu_delete(APC_PREFIX.'ojn_tr_' . $_GET['lng']);
	}
	else
	{
		file_put_contents('logs/file_'.date('Ymd_His').'.log', $type."\n\n".file_get_contents($_FILES['file']['tmp_name']));
		Message::AddInfo(__tr("Work In Progress"));
	}
	}
}
if($reload) {
	header('Location: ?lng=' . $_GET['lng']);
	exit;
}
include(ROOT_SITE.'include/message.php');
?>
	      <div class="row">
	      	<div class="span12">
	      		<div class="widget">
					<div class="widget-header">
						<i class="icon-th-large"></i>
						<h3><?php echo __tr("Translation page (%1)", $_GET['lng']) ?></h3>
					</div> <!-- /widget-header -->
					<div class="widget-content">
<a style="float:right" href="translator_edit.php?lng=<?php echo $_GET['lng'] ?>" class="btn btn-success"><?php echo __tr("Online translation") ?></a>
<br />
								<form id="edit-profile" class="form-horizontal" method="post" enctype="multipart/form-data">
										<div class="control-group">	
											<label class="control-label" for="sentence"><?php echo __tr('Import') ?></label>
											<div class="controls">
												<input type="file" class="input-xlarge span8" name="file" value="">
											</div> <!-- /controls -->				
										</div> <!-- /control-group -->
										<div class="control-group">	
											<label class="control-label" for="sentence"><?php echo __tr('UTF8') ?>, 1</label>
											<div class="controls">
												<input type="checkbox" class="input-xlarge span8" name="utf81" value="1">
											</div> <!-- /controls -->				
										</div> <!-- /control-group -->
										<div class="control-group">	
											<label class="control-label" for="sentence"><?php echo __tr('UTF8') ?>, 2</label>
											<div class="controls">
												<input type="checkbox" class="input-xlarge span8" name="utf82" value="1">
											</div> <!-- /controls -->				
										</div> <!-- /control-group -->
											
										<div class="form-actions">
											<button type="submit" class="btn btn-primary"><?php echo __tr('Load file') ?></button> 
											<a href="index.php" class="btn"><?php echo __tr('Back') ?></a>
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
											<a href="index.php" class="btn"><?php echo __tr('Back') ?></a>
										</div> <!-- /form-actions -->
								</form>
					</div> <!-- /widget-content -->
				</div> <!-- /widget -->					
		    </div> <!-- /span12 -->     	
	      </div> <!-- /row -->
<?php
require_once "../include/append.php"
?>
