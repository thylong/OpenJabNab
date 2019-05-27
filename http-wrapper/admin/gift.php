<?php
$reload = false;

function generate()
{
	$codes = array();
	for($i=0; $i<4; $i++)
	{
		$codes[] = strtoupper(substr(base_convert(rand() % 9999999999, 10, 36), 0, 5));
	}
	$code = implode("-", $codes);
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
	    die('Connexion impossible : ' . mysqli_error());
	}

	$sql = 'SELECT * FROM gift WHERE code="'.addslashes($code).'";';
	$res = mysqli_query($link, $sql);
	$num = mysqli_num_rows($res);
	mysqli_close($link);
	if($num)
	{
		$code = generate();
	}
	return $code;
}

require_once "include/common.php";
if(!isset($_SESSION['token']) || !$Infos['isAdmin'])
	header('Location: index.php');
if(isset($_GET['insert']) && $_GET['insert'] == 'gift' && count($_POST))
{
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
	    die('Connexion impossible : ' . mysqli_error());
	}
	$id = isset($_POST['id']) ? ' id="'.$_POST['id'].'", ' : "";
	$other = isset($_POST['id']) ? ', username="'.addslashes($_POST['username']).'"'.(strlen($_POST['date']) ? ', used="'.addslashes($_POST['date']).'" ' : ', used=NULL') : "";

	$sql = 'INSERT INTO gift SET '.$id.' code="'.addslashes($_POST['code']).'", days="'.$_POST['days'].'", genuine=1 '.$other.' ON DUPLICATE KEY UPDATE code="'.addslashes($_POST['code']).'", days="'.$_POST['days'].'", genuine=1 '.$other.';';
	$res = mysqli_query($link, $sql);
	mysqli_close($link);
	$reload = true;
}
if(isset($_GET['update']) && $_GET['update'] == "status")
{
	include('include/update_status.inc.php');
	$reload = true;
	Message::AddSuccess(__tr("Status successfully updated"));
}
if($reload)
{
	header('Location: gift.php');
	exit();
}
require(ROOT_SITE.'include/message.php');
?>
	<div class="row">
		<div class="span12">
			<div class="widget">
			<div class="widget-header">
			    <h3><?php echo __tr("Gift codes") ?></h3>
			</div>
			<div class="widget-content">
<?php
if(isset($_GET['edit']))
{
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
	    die('Connexion impossible : ' . mysqli_error());
	}

	$sql = 'SELECT * FROM gift WHERE id = "'.$_GET['edit'].'";';
	$res = mysqli_query($link, $sql);
	$c = 0;
	if($row = mysqli_fetch_assoc($res))
	{
?>
<form method="post" class="form-horizontal" action="gift.php?insert=gift">
	<input type="hidden" name="id" value="<?php echo $_GET['edit'] ?>">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Code") ?></label>
            <div class="controls">
		<input type="text" name="code" value="<?php echo $row['code'] ?>"/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Duration") ?></label>
            <div class="controls">
		<input type="text" name="days" value="<?php echo $row['days'] ?>"/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Username") ?></label>
            <div class="controls">
		<input type="text" name="username" value="<?php echo $row['username'] ?>"/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Date") ?></label>
            <div class="controls">
		<input type="text" name="date" value="<?php echo $row['used'] === null ? '' : date('Y-m-d H:i:s', strtotime($row['used'])) ?>"/>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <a href="gift.php" class="btn"><?php echo __tr("Cancel") ?></a>
          </div>
</form>

<?php
	}
}
else if(isset($_GET['insert']) && $_GET['insert'] == 'gift')
{
?>
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Code") ?></label>
            <div class="controls">
		<input type="text" name="code" value="<?php echo generate() ?>"/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Duration") ?></label>
            <div class="controls">
		<input type="text" name="days" value=""/>
            </div>
          </div>

          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <a href="gift.php" class="btn"><?php echo __tr("Cancel") ?></a>
          </div>
</form>

<?php
}
else
{
?>

<table class="table table-bordered table-striped span10">
	<thead>
	<tr>
		<th class="span4"><?php echo __tr('Code') ?></th>
		<th class="span4"><?php echo __tr('Username') ?></th>
		<th class="span2"><?php echo __tr('Date') ?></th>
		<th class="span2"><?php echo __tr('Duration') ?></th>
		<th class="span2"><?php echo __tr('Actions') ?></th>
	</tr>
	</thead>
<tbody>
<?php
$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
    die('Connexion impossible : ' . mysqli_error());
}

$total = 0;
$sql = "SELECT gift.*, account.status FROM gift LEFT JOIN account ON account.username=gift.username ORDER BY used DESC;";
$res = mysqli_query($link, $sql);
while($row = mysqli_fetch_assoc($res))
{
	$total += $row['days'];
?>
	<tr>
		<td><?php echo $row['code']; ?></td>
		<td><?php echo $row['username'].(strlen($row['status']) ? " <i>(".__tr($row['status']).")</i>" : ''); ?></td>
		<td><?php echo $row['used'] === null || $row['used']  == '0000-00-00 00:00:00' ? __tr('Not used') : date('d/m/Y', strtotime($row['used'])) ?></td>
		<td><?php echo $row['days']; ?></td>
		<td>
			<a class="btn btn-primary" href="gift.php?edit=<?php echo $row['id'] ?>"><?php echo __tr('Edit') ?></a>
		</td>
	</tr>
<?php
}
?>
</tbody>
</table>
<br style="clear:both"/>
<form method="get" class="form-horizontal">
          <div class="form-actions">
            <button class="btn btn-primary" type="submit" name="insert" value="gift"><?php echo __tr("Insert a gift code") ?></button>
            <button class="btn btn-primary" type="submit" name="update" value="status"><?php echo __tr("Update status") ?></button>
          </div>
</form>
<?php
}
?>

</div>
</div>
			</div>
			</div>
		</div>
	</div>
<?php
require_once "include/append.php";
?>

