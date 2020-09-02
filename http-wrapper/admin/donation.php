<?php
$reload = false;

require_once "include/common.php";
if(isset($_GET['insert']) && $_GET['insert'] == 'demo' && count($_POST))
{
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
	    die('Connexion impossible : ' . mysqli_error());
	}

	$sql = 'INSERT INTO demo SET username="'.addslashes($_POST['username']).'", date=NOW();';
	$res = mysqli_query($link, $sql);
	mysqli_close($link);
	$reload = true;
}
if(isset($_GET['convert']))
{
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
	    die('Connexion impossible : ' . mysqli_error());
	}
	$id = $_GET['convert'];

	$sql = "SELECT * FROM don WHERE id='$id';";
	$res = mysqli_query($link, $sql);
	if($row = mysqli_fetch_assoc($res))
	{
		$duration = 0;
		switch($row['value'])
		{
			case 2: $duration = 1; break;
			case 3: $duration = 3; break;
			case 5: $duration = 6; break;
			case 10: $duration = 12; break;
			case 20: $duration = 24; break;
			default:
		}
		if($duration)
		{
			$sql = "INSERT INTO premium SET date='".$row['date']."', email='".$row['email']."', username='".$row['username']."', value='".$row['value']."', remain='".$row['remain']."', duration='$duration';";
			$res = mysqli_query($link, $sql);
			$sql = "DELETE FROM don WHERE id='".$row['id']."';";
			$res = mysqli_query($link, $sql);
			Message::AddSuccess("Conversion successfull");
			header("Location: member.php");
			exit;
		}
		else
		{
			Message::AddError(__tr("Can't convert this donation to premium registration"));
		}
	}
	mysqli_close($link);
	$reload = true;
}
if(isset($_GET['insert']) && $_GET['insert'] == 'donation' && count($_POST))
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

	$sql = 'INSERT INTO don SET '.$id.' date="'.date('Y-m-d H:i:s', strtotime($_POST['date'])).'", email="'.addslashes($_POST['email']).'", username="'.addslashes($_POST['username']).'", type="'.addslashes($_POST['type']).'", value="'.$_POST['value'].'", remain="'.$_POST['remain'].'" ON DUPLICATE KEY UPDATE date="'.date('Y-m-d H:i:s', strtotime($_POST['date'])).'", email="'.addslashes($_POST['email']).'", username="'.addslashes($_POST['username']).'", type="'.addslashes($_POST['type']).'", value="'.$_POST['value'].'", remain="'.$_POST['remain'].'";';
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
	header('Location: donation.php');
	exit();
}
require(ROOT_SITE.'include/message.php');
?>
<div class="card">
  <h5 class="card-header">
    <i class="icon-gift"></i> <?php echo __tr("Donations") ?>
  </h5>
  <div class="card-body">
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

	$sql = 'SELECT * FROM don WHERE id = "'.$_GET['edit'].'";';
	$res = mysqli_query($link, $sql);
	$c = 0;
	if($row = mysqli_fetch_assoc($res))
	{
?>
<form method="post" class="form-horizontal" action="donation.php?insert=donation">
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
		<input type="text" name="type" value="<?php echo isset($row['type']) ? $row['type'] : 'donation' ?>"/>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <a href="donation.php" class="btn"><?php echo __tr("Cancel") ?></a>
          </div>
</form>

<?php
	}
}
else if(isset($_GET['insert']) && $_GET['insert'] == 'demo')
{
?>
<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Username") ?></label>
            <div class="controls">
		<input type="text" name="username" value=""/>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <a href="donation.php" class="btn"><?php echo __tr("Cancel") ?></a>
          </div>
</form>

<?php
}
else if(isset($_GET['insert']) && $_GET['insert'] == 'donation')
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
		<input type="text" name="type" value="donation"/>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
            <a href="donation.php" class="btn"><?php echo __tr("Cancel") ?></a>
          </div>
</form>

<?php
}
else
{
?>

<table class="table table-bordered table-striped span11">
	<thead>
	<tr>
		<th class="span3"><?php echo __tr('Email') ?></th>
		<th class="span3"><?php echo __tr('Username') ?></th>
		<th class="span2"><?php echo __tr('Date') ?></th>
		<th class="span1"><?php echo __tr('Donation') ?></th>
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
$sql = "SELECT don.*, account.status FROM don LEFT JOIN account ON account.username=don.username ORDER BY date DESC;";
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
		<td>
			<a class="btn btn-success" href="donation.php?search=<?php echo base64_encode($row['email']) ?>"><?php echo __tr('Search user') ?></a>
			&nbsp;<a class="btn btn-primary" href="donation.php?edit=<?php echo $row['id'] ?>"><?php echo __tr('Edit') ?></a>
			&nbsp;<a class="btn btn-primary" href="donation.php?convert=<?php echo $row['id'] ?>"><?php echo __tr('Convert') ?></a>
		</td>
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
<br />
<table class="table table-bordered table-striped span10">
	<thead>
	<tr>
		<th class="span3"><?php echo __tr('Username') ?></th>
		<th class="span2"><?php echo __tr('Date') ?></th>
		<th class="span2"><?php echo __tr('End') ?></th>
	</tr>
	</thead>
<tbody>
<?php
$sql = "SELECT demo.*, DATE_ADD(demo.date, INTERVAL 7 DAY) as end, account.status FROM demo LEFT JOIN account ON account.username=demo.username ORDER BY date DESC LIMIT 20;";
$res = mysqli_query($link, $sql);
while($row = mysqli_fetch_assoc($res))
{
?>
	<tr>
		<td><?php echo $row['username'].(strlen($row['status']) ? " <i>(".__tr($row['status']).")</i>" : ''); ?></td>
		<td><?php echo date('d/m/Y', strtotime($row['date'])) ?></td>
		<td><?php echo date('d/m/Y', strtotime($row['end'])) ?></td>
	</tr>
<?php
}
mysqli_close($link);
?>
</tbody>
</table>
<br style="clear:both"/>
<form method="get" class="form-horizontal">
          <div class="form-actions">
            <button class="btn btn-primary" type="submit" name="insert" value="donation"><?php echo __tr("Insert a donation") ?></button>
            <button class="btn btn-primary" type="submit" name="insert" value="demo"><?php echo __tr("Set demo") ?></button>
            <button class="btn btn-primary" type="submit" name="update" value="status"><?php echo __tr("Update status") ?></button>
            <a class="btn btn-primary" href="listplugins.php"><?php echo __tr("List plugins associated") ?></a>
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
