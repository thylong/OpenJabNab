<?php
$reload = false;

require_once "include/common.php";
if(!isset($_SESSION['token']) || !$Infos['isAdmin'])
	header('Location: index.php');
if(isset($_GET['insert']) && $_GET['insert'] == 'member' && count($_POST))
{
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
	    die('Connexion impossible : ' . mysqli_error());
	}
	$id = isset($_POST['id']) ? ' id="'.$_POST['id'].'", ' : "";

	if($_POST['remain'] == 0)
	{
		$_POST['remain'] = $_POST['value'] - (0.25 + $_POST['value'] * 0.034);
	}
	//$_POST['username'] = utf8_decode($_POST['username']);

	$sql = 'INSERT INTO premium SET '.$id.' date="'.date('Y-m-d H:i:s', strtotime($_POST['date'])).'", email="'.addslashes($_POST['email']).'", username="'.addslashes($_POST['username']).'", duration="'.$_POST['duration'].'", value="'.$_POST['value'].'", remain="'.$_POST['remain'].'" ON DUPLICATE KEY UPDATE date="'.date('Y-m-d H:i:s', strtotime($_POST['date'])).'", email="'.addslashes($_POST['email']).'", username="'.addslashes($_POST['username']).'", value="'.$_POST['value'].'", remain="'.$_POST['remain'].'", duration="'.$_POST['duration'].'";';
	$res = mysqli_query($link, $sql);
	mysqli_close($link);
	$reload = true;
}
if(isset($_GET['update']) && $_GET['update'] == "status")
{
	include("include/update_status.inc.php");
	$reload = true;
	Message::AddSuccess(__tr("Status successfully updated"));
}
if($reload)
{
	header('Location: member.php');
	exit();
}
require(ROOT_SITE.'include/message.php');
?>
	<div class="row">
		<div class="span12">
			<div class="widget">
			<div class="widget-header">
			    <h3><?php echo __tr("Donations") ?></h3>
			</div>
			<div class="widget-content">
<?php
function displaySearch($row)
{
	echo "<li><a href='account_expert.php?accid=".$row['id']."' target='_blank' class='btn btn-mini'><i class='icon-eye-open'></i></a>&nbsp;".utf8_encode($row['username'])."</li>";
}
if(isset($_GET['search']))
{
?>
<style>
ul li {
	list-style: none;

}
</style>
<?php
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
	    die('Connexion impossible : ' . mysqli_error());
	}
	$search = base64_decode($_GET['search']);
	echo "<b>".__tr("Search with %1", "<i>".$search."</i>") . "</b><br /><br /><ul>";
	$sql = 'SELECT * FROM account WHERE settings LIKE "%'.implode('\0', str_split($search)).'%";';
	$res = mysqli_query($link, $sql);
	$c = 0;
	while($row = mysqli_fetch_assoc($res))
	{
		$c++;
		displaySearch($row);
	}
	echo "</ul><br />";
	if(!$c)
	{
		$email = base64_decode($_GET['search']);
		$base = preg_replace("|@.*$|", "", $email);

		$search = $base;
		echo "<b>".__tr("Search with %1", "<i>".$search."</i>") . "</b><br /><br /><ul>";
		$sql = 'SELECT * FROM account WHERE settings LIKE "%'.implode('\0', str_split($search)).'%";';
		$res = mysqli_query($link, $sql);
		while($row = mysqli_fetch_assoc($res))
		{
			$c++;
			displaySearch($row);
		}
		echo "</ul><br />";

		$search = preg_replace("/\./", "", $base);
		echo "<b>".__tr("Search with %1", "<i>".$search."</i>") . "</b><br /><br /><ul>";
		$sql = 'SELECT * FROM account WHERE settings LIKE "%'.implode('\0', str_split($search)).'%";';
		$res = mysqli_query($link, $sql);
		while($row = mysqli_fetch_assoc($res))
		{
			$c++;
			displaySearch($row);
		}
		echo "</ul><br />";

		$searchs = preg_split("/[. \-_]/", $base);
		echo "<b>".__tr("Search with %1", "<i>".implode(', '.__tr('or').' ', $searchs)."</i>") . "</b><br /><br /><ul>";
		$where = "";
		foreach($searchs as $search)
		{
			if(strlen($search) > 2)
				$where .= ' OR settings LIKE "%'.implode('\0', str_split($search)).'%"';
		}
		$sql = 'SELECT * FROM account WHERE 0=1'.$where.';';
		$res = mysqli_query($link, $sql);
		while($row = mysqli_fetch_assoc($res))
		{
			$c++;
			displaySearch($row);
		}
		echo "</ul><br />";

		if(!$c)
		{
			$baseletter = preg_replace("/\d/", "", $base);

			$search = $baseletter;
			echo "<b>".__tr("Search with %1", "<i>".$search."</i>") . "</b><br /><br /><ul>";
			$sql = 'SELECT * FROM account WHERE settings LIKE "%'.implode('\0', str_split($search)).'%";';
			$res = mysqli_query($link, $sql);
			while($row = mysqli_fetch_assoc($res))
			{
				displaySearch($row);
			}
			echo "</ul><br />";

			$search = preg_replace("/\./", "", $baseletter);
			echo "<b>".__tr("Search with %1", "<i>".$search."</i>") . "</b><br /><br /><ul>";
			$sql = 'SELECT * FROM account WHERE settings LIKE "%'.implode('\0', str_split($search)).'%";';
			$res = mysqli_query($link, $sql);
			while($row = mysqli_fetch_assoc($res))
			{
				displaySearch($row);
			}
			echo "</ul><br />";

			$searchs = preg_split("/[. \-_]/", $baseletter);
			echo "<b>".__tr("Search with %1", "<i>".implode(', '.__tr('or').' ', $searchs)."</i>") . "</b><br /><br /><ul>";
			$where = "";
			foreach($searchs as $search)
			{
				if(strlen($search) > 2)
					$where .= ' OR settings LIKE "%'.implode('\0', str_split($search)).'%"';
			}
			$sql = 'SELECT * FROM account WHERE 0=1'.$where.';';
			$res = mysqli_query($link, $sql);
			while($row = mysqli_fetch_assoc($res))
			{
				displaySearch($row);
			}
			echo "</ul><br />";
		}
	}
	mysqli_close($link);
}
else if(isset($_GET['edit']))
{
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
	    die('Connexion impossible : ' . mysqli_error());
	}
	$search = implode('\0', str_split(base64_decode($_GET['search'])));
	echo $search."<br /><br />";

	$sql = 'SELECT * FROM premium WHERE id = "'.$_GET['edit'].'";';
	$res = mysqli_query($link, $sql);
	$c = 0;
	if($row = mysqli_fetch_assoc($res))
	{
?>
<form method="post" class="form-horizontal" action="member.php?insert=member">
	<input type="hidden" name="id" value="<?php echo $_GET['edit'] ?>">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Email") ?></label>
            <div class="controls">
		<input type="text" name="email" value="<?php echo $row['email'] ?>"/>
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
		<input type="text" name="date" value="<?php echo date('Y-m-d H:i:s', strtotime($row['date'])) ?>"/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Duration") ?></label>
            <div class="controls">
		<input type="text" name="duration" value="<?php echo $row['duration'] ?>"/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Value") ?></label>
            <div class="controls">
		<input type="text" name="value" value="<?php echo $row['value'] ?>"/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Remain") ?></label>
            <div class="controls">
		<input type="text" name="remain" value="<?php echo $row['remain'] ?>"/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Type") ?></label>
            <div class="controls">
		<input type="text" name="type" value="<?php echo isset($row['type']) ? $row['type'] : 'member' ?>"/>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <a href="member.php" class="btn"><?php echo __tr("Cancel") ?></a>
          </div>
</form>

<?php
	}
}
else if(isset($_GET['insert']) && $_GET['insert'] == 'member')
{
?>
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Email") ?></label>
            <div class="controls">
		<input type="text" name="email" value=""/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Username") ?></label>
            <div class="controls">
		<input type="text" name="username" value=""/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Date") ?></label>
            <div class="controls">
		<input type="text" name="date" value="<?php echo date('Y-m-d H:i:s') ?>"/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Duration") ?></label>
            <div class="controls">
		<input type="text" name="duration" value=""/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Value") ?></label>
            <div class="controls">
		<input type="text" name="value" value=""/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Remain") ?></label>
            <div class="controls">
		<input type="text" name="remain" value=""/>
            </div>
          </div>
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Type") ?></label>
            <div class="controls">
		<input type="text" name="type" value="member"/>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <a href="member.php" class="btn"><?php echo __tr("Cancel") ?></a>
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
		<th class="span3"><?php echo __tr('Email') ?></th>
		<th class="span3"><?php echo __tr('Username') ?></th>
		<th class="span2"><?php echo __tr('Date') ?></th>
		<th class="span2"><?php echo __tr('Donation') ?></th>
		<th class="span5"><?php echo __tr('Actions') ?></th>
	</tr>
	</thead>
<tbody>
<?php
$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
    die('Connexion impossible : ' . mysqli_error());
}

$total = 0;
$sql = "SELECT premium.*, account.status FROM premium LEFT JOIN account ON account.username=premium.username ORDER BY date DESC;";
$res = mysqli_query($link, $sql);
while($row = mysqli_fetch_assoc($res))
{
	$total += $row['value'];
?>
	<tr>
		<td><?php echo $row['email']; ?></td>
		<td><?php echo $row['username'].(strlen($row['status']) ? " <i>(".__tr($row['status']).")</i>" : ''); ?></td>
		<td><?php echo date('d/m/Y', strtotime($row['date'])) ?></td>
		<td><?php echo round($row['value'], 2); ?></td>
		<td><a class="btn btn-success" href="member.php?search=<?php echo base64_encode($row['email']) ?>"><?php echo __tr('Search user') ?></a>&nbsp;<a class="btn btn-primary" href="member.php?edit=<?php echo $row['id'] ?>"><?php echo __tr('Edit') ?></a></td>
	</tr>
<?php
}
?>
	<tr>
		<th colspan="3"><?php echo __tr('Total'); ?></th>
		<td colspan="2"><?php echo round($total, 2); ?></td>
	</tr>
</tbody>
</table>
<br style="clear:both"/>
<form method="get" class="form-horizontal">
          <div class="form-actions">
            <button class="btn btn-primary" type="submit" name="insert" value="member"><?php echo __tr("Insert a member") ?></button>
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

