<?php
require_once '../include/common.php';
global $img;
$img = 1;
function addId($matches)
{
	global $img;
	return '<img id="img'.($img++).'" src=';
}
function putIdOnImg($str)
{
	$str = preg_replace_callback('/(<img src=)/s', 'addId', $str);
	return $str;
}

$ojnTemplate->setTitle(__tr('Frequently Ask Questions'));

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
    Message::AddError(__tr('Connexion impossible : ') . mysqli_error());
	header('Location: /help/faq.php');
	exit;
}
$faqs = array();
$searchs = null;

function shuffle_assoc(&$array) {
	$keys = array_keys($array);

	shuffle($keys);

	$new = array();
	foreach($keys as $key) {
	    $new[$key] = $array[$key];
	}

	$array = $new;

	return true;
}

$tags = array();
$sql = "SELECT * FROM faq WHERE language='".$Infos['language']."' ORDER BY RAND()";
$res = mysqli_query($link, $sql);
while($row = mysqli_fetch_assoc($res)) {
	$faqs[] = $row;
	foreach(preg_split('/,/', $row['keyword']) as $word) {
		$word = trim($word);
		if($word != '') {
			if(!isset($tags[$word])) {
				$tags[$word] = 0;
			}
			$tags[$word]++;
		}
	}
}
shuffle_assoc($tags);

if(isset($_GET['search']) && strlen(trim($_GET['search']))) {
	$exclude = array('?', 'le', 'les', 'la', 'de', 'un', 'une', 'je', 'vous', 'de', 'des', 'mon', 'ne', 'que');
	$words = $_GET['search'];
	$words = preg_split('/[ ,]/', $words);
	foreach($words as $word) {
		$word = strtolower(trim($word));
		if(strlen($word) && !in_array($word, $exclude)) {
			$sql = "SELECT * FROM faq WHERE language='".$Infos['language']."' AND (question LIKE '%".$word."%' OR answer LIKE '%".$word."%' OR keyword LIKE '%".$word."%') ORDER BY viewed DESC;";
			$res = mysqli_query($link, $sql);
			$c = 0;
			while($row = mysqli_fetch_assoc($res)) {
				$c++;
				$faqs[$row['id']] = $row;
			}
			$sql = "INSERT INTO search SET word='".$word."', results=".$c.", searched=1, language='".$Infos['language']."', last=NOW() ON DUPLICATE KEY UPDATE searched=searched+1, last=NOW(), results=".$c.";";
			$res = mysqli_query($link, $sql);
		}
	}
} else {
	if(isset($_GET['question']) && trim($_GET['question']) != '') {
		//$sql = "SELECT * FROM faq WHERE language='".$Infos['language']."' AND slug='".$_GET['question']."' ORDER BY viewed DESC;";
		$sql = "SELECT * FROM faq WHERE slug='".$_GET['question']."' ORDER BY viewed DESC;";
		$res = mysqli_query($link, $sql);
		if($res) {
			while($row = mysqli_fetch_assoc($res)) {
				$searchs[] = $row;

				if(!isset($_GET['debug'])) {
					$sql = "UPDATE faq SET viewed=viewed+1 WHERE id='".$row['id']."';";
					mysqli_query($link, $sql);
				}
			}
		}
	}
}
mysqli_close($link);

require(ROOT_SITE.'include/message.php');
?>
<?php if(is_array($searchs) && !empty($searchs)): ?>
<div class="row">
    <div class="span12">
        <div class="widget widget">
            <div class="widget-header">
                <i class="icon-list-alt"></i><h3><?php echo __tr("Frequently Ask Questions") ?></h3>
            </div>
            <div class="widget-content">
<a href="faq.php"><?php echo __tr('&lt; Back to the list') ?></a><br />
<?php $annotate = array(); ?>
	<?php foreach($searchs as $f): ?>
<?php $annotate[] = $f['annotation']; ?>
<?php $ojnTemplate->setTitle($f['question'] . ' - ' . __tr('FAQ')); ?>
<h3><?php echo $f['question'] ?></h3>
<hr />
<?php echo putIdOnImg($f['answer']) ?>
<br />
<br />
	<?php endforeach; ?>
<?php if(count($annotate)): ?>
		<style type="text/css" media="all">@import "/media/css/annotation.css";</style>
<?php $js = '<script language="javascript">
		jQuery(window).load(function() {
			jQuery("#img1").annotateImage({
				editable: false,
				useAjax: false,
				notes: [ { "top": 286,
						   "left": 161,
						   "width": 52,
						   "height": 37,
						   "text": "Small people on the steps",
						   "id": "e69213d0-2eef-40fa-a04b-0ed998f9f1f5",
			 	    	           "editable": false } ]
				});
			});
		</script>';
?>
<?php //$ojnTemplate->setJs('<script type="text/javascript" src="js/jquery.annotate.js"></script>'.$js); ?>
<?php endif; ?>
            </div>
        </div>
<?php else: ?>
<div class="row">
    <div class="span8">
        <div class="widget widget">
            <div class="widget-header">
                <i class="icon-list-alt"></i><h3><?php echo __tr("Frequently Ask Questions") ?></h3>
            </div>
            <div class="widget-content">
<?php if( count($faqs) ): ?>
	<?php foreach($faqs as $f): ?>
	<a href="/help/faq.php?question=<?php echo $f['slug'] ?><?php if($Infos['isAdmin']): ?><?php /*&debug=true<?php*/ endif; ?>"><?php echo $f['question'] ?></a><br />
	<?php endforeach; ?>
<?php else: ?>
<?php endif; ?>
            </div>
        </div>
    </div>
    <div class="span4">
        <div class="widget widget">
            <div class="widget-header">
                <i class="icon-list-alt"></i><h3><?php echo __tr("Search a solution") ?></h3>
            </div>
            <div class="widget-content">
<form method="get">
<input type="text" name="search" value="<?php echo isset($_GET['search']) ? $_GET['search'] : '' ?>" style="width: 98%">
<br />
<center><input type="submit" class="btn btn-primary" value="<?php echo __tr('Search') ?>"></center>
</form>
<hr />
            </div>
        </div>
<?php endif; ?>
<?php
require_once ROOT_SITE.'include/append.php';
?>
