<?php
require_once '../include/common.php';
$ojnTemplate->setTitle(__tr('News setup'));

$statuses = array(
	0 => __tr('Draft'),
	1 => __tr('Published'),
	2 => __tr('Archived'),
	3 => __tr('Removed'),
	4 => __tr('Always on top'),
);
$reload = false;

function getTr($text, $lng)
{
	$url ='https://api.mymemory.translated.net/get?de=translation@openjabnab.fr&langpair=en|'.$lng.'&q='.urlencode($text);
	$content = file_get_contents($url);
	$jscontent = json_decode($content);
	//var_dump($content);
	if(!empty($jscontent->responseData->translatedText))
		return html_entity_decode($jscontent->responseData->translatedText,ENT_QUOTES);
	return 'Error. Got: '.$content;

	/*$url = 'https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl='.$lng.'&dt=t&q='.urlencode($s);
	$content = (string)(file_get_contents($url));
	if($content)
	{
		if(preg_match('|\[\[\["([^"]+)","'.$text.'"|isU', $content, $match))
		{
			return ($match[1]);
		}
		return "Don't match : ".$content;
	}
	return "No content";
	 */
}

/*
$t = getTr("From 8 December 2014, the first version for the Nabaztag V1 will be test. If you want to participate, send a message through the contact form with the MAC address of your bunny. Do not hesitate to report problems or suggest improvements. Thank you all for your participation.","fr");
var_dump($t);
die();
 */

if(isset($_POST['aid']) && $_POST['aid'] == -1)
{
	$content = trim($_POST['content']);
	$title = trim($_POST['title']);
	$row = array('content' => $content, 'title' => $title);

	if(!strlen($title))
	{
		Message::AddError(__tr("Title can't be empty"));
	}
	else if(!strlen($content))
	{
		Message::AddError(__tr("Content can't be empty"));
	}
	else
	{
		$sql = "INSERT INTO news SET title='".addslashes($title)."', content='".addslashes($content)."', date=NOW(), status=0;";
		$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		if (!$link) {
		    die('Connexion impossible : ' . mysqli_error());
		}

		if(mysqli_query($link, $sql))
		{
			Message::AddSuccess(__tr("News successfully added"));
			$reload = true;
		}
		else
		{
			Message::AddError(__tr("News can't be added"));
		}
		mysqli_close($link);
	}
}
if(isset($_POST['tid']) && $_POST['tid'] > 0)
{
	$id = $_GET['tid'];
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
	    die('Connexion impossible : ' . mysqli_error());
	}
	foreach($_POST['code'] as $lng)
	{
		$title = trim($_POST['title'][$lng]);
		$content = trim($_POST['content'][$lng]);
		if(strlen($title) && strlen($content))
		{
			$sql = "INSERT INTO news_translation SET news='".$id."', title='".addslashes($title)."', content='".addslashes($content)."', language='".$lng."' ON DUPLICATE KEY UPDATE title='".addslashes($title)."', content='".addslashes($content)."'";
			if(mysqli_query($link, $sql))
			{
				Message::AddSuccess(__tr("News successfully translated in %1", $lng));
				$reload = true;
			}
			else
			{
				Message::AddError(__tr("News can't be translated in %1", $lng));
			}
		}
	}

	mysqli_close($link);
}
if(isset($_GET['did']) && $_GET['did'] > 0)
{
	$id = $_GET['did'];

	$sql = "UPDATE news SET status=3 WHERE id='".$id."'";
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
	    die('Connexion impossible : ' . mysqli_error());
	}

	if(mysqli_query($link, $sql))
	{
		Message::AddSuccess(__tr("News successfully removed"));
		$reload = true;
	}
	else
	{
		Message::AddError(__tr("News can't be removed"));
	}
	mysqli_close($link);
}
if(isset($_POST['eid']) && $_POST['eid'] > 0)
{
	$id = trim($_POST['eid']);
	$content = trim($_POST['content']);
	$title = trim($_POST['title']);
	$date = trim($_POST['date']);
	$status = trim($_POST['status']);

	$row = array('id' => $id, 'status' => $status, 'date' => $date, 'content' => $content, 'title' => $title);

	if(!strlen($title))
	{
		Message::AddError(__tr("Title can't be empty"));
	}
	else if(!strlen($content))
	{
		Message::AddError(__tr("Content can't be empty"));
	}
	else if(!strlen($date))
	{
		Message::AddError(__tr("Date can't be empty"));
	}
	else if(!strlen($status))
	{
		Message::AddError(__tr("Status can't be empty"));
	}
	else if(!in_array($status, array_keys($statuses)))
	{
		Message::AddError(__tr("Bad status"));
	}
	else
	{
		$sql = "UPDATE news SET title='".addslashes($title)."', content='".addslashes($content)."', date='".$date."', status='".$status."' WHERE id='".$id."';";
		$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		if (!$link) {
		    die('Connexion impossible : ' . mysqli_error());
		}

		if(mysqli_query($link, $sql))
		{
			Message::AddSuccess(__tr("News successfully updated"));
			foreach(array('fr', 'es', 'en') as $lng)
				apcu_delete(APC_PREFIX.'ojn_news_'.$lng);
			$reload = true;
		}
		else
		{
			Message::AddError(__tr("News can't be updated"));
		}
		mysqli_close($link);
	}
}

if($reload) {
	header('Location: /admin/news.php');
	exit;
}
require_once(ROOT_SITE.'include/message.php');

?>
	      <div class="row">
	      	<div class="span12">
	      		<div class="widget ">
	      			<div class="widget-header">
	      				<i class="icon-cog"></i> <h3><?php echo __tr('News') ?></h3>
	  			</div> <!-- /widget-header -->
				<div class="widget-content">
<?php
if(isset($_GET['add']) && $_GET['add'] == 'new')
{
?>
	<form id="edit-profile" class="form-horizontal" method="post">
			<input type="hidden" name="aid" value="-1">
			<div class="control-group">
				<label class="control-label" for="title"><?php echo __tr('Title') ?></label>
				<div class="controls">
					<input type="text" class="input-xlarge span8" name="title" value="<?php echo !empty($row) ? $row['title'] : '' ?>">
				</div> <!-- /controls -->
			</div> <!-- /control-group -->

			<div class="control-group">
				<label class="control-label" for="content"><?php echo __tr('Content') ?></label>
				<div class="controls">
					<textarea class="input-xlarge span8" name="content"><?php echo !empty($row) ? $row['content'] : '' ?></textarea>
				</div> <!-- /controls -->
			</div> <!-- /control-group -->
			<br />

			<div class="form-actions">
				<button type="submit" class="btn btn-primary"><?php echo __tr('Save') ?></button>
				<a class="btn" href="/admin/news.php"><?php echo __tr('Cancel') ?></a>
			</div> <!-- /form-actions -->
	</form>
<?php
}
elseif(isset($_GET['tid']) && is_numeric($_GET['tid']) && $_GET['tid'] > 0)
{
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
	    die('Connexion impossible : ' . mysqli_error());
	}

	$sql = "SELECT language.code, language.language, news_translation.title, news_translation.content, news.title as orig_title, news.content as orig_content FROM language LEFT JOIN news_translation ON news_translation.language=language.code AND news_translation.news='".$_GET['tid']."' LEFT JOIN news ON news.id='".$_GET['tid']."' WHERE language.code != 'en';";
	$res = mysqli_query($link, $sql);
	$n = 0;
?>
	<form id="edit-profile" class="form-horizontal" method="post">
			<input type="hidden" name="tid" value="<?php echo $_GET['tid'] ?>">
<?php
	while($row = mysqli_fetch_assoc($res))
	{
		if($n++ == 0)
		{
?>
			<div class="control-group">
				<div class="controls">
					<input class="input-xlarge span8" disabled type="text" value="<?php echo $row['orig_title'] != null ? trim($row['orig_title']) : '' ?>">
					<textarea class="input-xlarge span8" disabled><?php echo $row['orig_content'] != null ? trim($row['orig_content']) : '' ?></textarea>
				</div> <!-- /controls -->
			</div> <!-- /control-group -->
<?php
		}
?>
			<div class="control-group">
				<label class="control-label" for="status"><?php echo $row['language'] ?></label>
				<div class="controls">
					<input type="hidden" name="code[<?php echo $row['code'] ?>]" value="<?php echo $row['code'] ?>">
					<input class="input-xlarge span8" name="title[<?php echo $row['code'] ?>]" type="text" value="<?php echo $row['title'] != null ? trim($row['title']) : getTr($row['orig_title'], $row['code']) ?>">
					<textarea class="input-xlarge span8" name="content[<?php echo $row['code'] ?>]"><?php echo $row['content'] != null ? trim($row['content']) : getTr($row['orig_content'], $row['code']) ?></textarea>
				</div> <!-- /controls -->
			</div> <!-- /control-group -->
<?php
	}
	mysqli_close($link);

?>
			<br />

			<div class="form-actions">
				<button type="submit" class="btn btn-primary"><?php echo __tr('Save') ?></button>
				<a class="btn" href="news.php"><?php echo __tr('Cancel') ?></a>
			</div> <!-- /form-actions -->
	</form>
<?php
}
elseif(isset($_GET['eid']) && is_numeric($_GET['eid']) && $_GET['eid'] > 0)
{
	if(!isset($row))
	{
		$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		if (!$link) {
		    die('Connexion impossible : ' . mysqli_error());
		}

		$sql = "SELECT * FROM news WHERE id='".$_GET['eid']."'";
		$res = mysqli_query($link, $sql);
		$row = mysqli_fetch_assoc($res);
		mysqli_close($link);
	}
?>
	<form id="edit-profile" class="form-horizontal" method="post">
			<input type="hidden" name="eid" value="<?php echo $row['id'] ?>">

			<div class="control-group">
				<label class="control-label" for="status"><?php echo __tr('Status') ?></label>
				<div class="controls">
					<select class="input-xlarge span8" name="status">
<?php foreach($statuses as $k => $v): ?>
	<?php if($k != 3): ?>
						<option value="<?php echo $k ?>"<?php echo $k == $row['status'] ? ' selected="selected"' : ''?>><?php echo $v ?></option>
	<?php endif; ?>
<?php endforeach; ?>
					</select>
				</div> <!-- /controls -->
			</div> <!-- /control-group -->

			<div class="control-group">
				<label class="control-label" for="title"><?php echo __tr('Title') ?></label>
				<div class="controls">
					<input type="text" class="input-xlarge span8" name="title" value="<?php echo $row['title'] ?>">
				</div> <!-- /controls -->
			</div> <!-- /control-group -->

			<div class="control-group">
				<label class="control-label" for="content"><?php echo __tr('Content') ?></label>
				<div class="controls">
					<textarea class="input-xlarge span8" name="content"><?php echo $row['content'] ?></textarea>
				</div> <!-- /controls -->
			</div> <!-- /control-group -->

			<div class="control-group">
				<label class="control-label" for="date"><?php echo __tr('Date') ?></label>
				<div class="controls">
					<input type="text" class="input-xlarge span8" name="date" value="<?php echo $row['date'] ?>">
				</div> <!-- /controls -->
			</div> <!-- /control-group -->

			<br />

			<div class="form-actions">
				<button type="submit" class="btn btn-primary"><?php echo __tr('Save') ?></button>
				<a class="btn" href="/admin/news.php"><?php echo __tr('Cancel') ?></a>
			</div> <!-- /form-actions -->
	</form>
<?php
}
else
{
?>
<table class="table table-bordered table-striped span11">
	<thead>
	<tr>
		<th class="span2"><?php echo __tr('Date') ?></th>
		<th class="span5"><?php echo __tr('Title') ?></th>
		<th class="span2"><?php echo __tr('Status') ?></th>
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

	$sql = "SELECT * FROM news ORDER BY date DESC";
	$res = mysqli_query($link, $sql);
	while($row = mysqli_fetch_assoc($res))
	{
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><?php echo date('d/m/Y', strtotime($row['date'])); ?></td>
		<td><?php echo $row['title']; ?></td>
		<td><?php echo $statuses[$row['status']]; ?></td>
		<td>
			<a href="/admin/news.php?eid=<?php echo $row['id']; ?>" class="btn btn-primary"><?php echo __tr('Edit') ?></a> &nbsp;
			<a href="/admin/news.php?did=<?php echo $row['id']; ?>" class="btn btn-danger"><?php echo __tr('Remove') ?></a> &nbsp;
			<a href="/admin/news.php?tid=<?php echo $row['id']; ?>" class="btn btn-success"><?php echo __tr('Translate') ?></a>
		</td>
	</tr>
<?php
	}
	mysqli_close($link);
?>
</tbody>
</table>
<br />
<a href="/admin/news.php?add=new" class="btn btn-primary"><?php echo __tr("Add a news") ?></a>
<?php
}
?>

				</div>

			</div>
		</div>
	</div>
<?php
require_once '../include/append.php';
?>
