<?php
require_once "../include/common.php";
require_once '../include/tools.inc.php';

$reload = isset($_GET['reload']);
if(!empty($_GET['type']))
{
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
		die('Connexion impossible : ' . mysqli_error());
	}
	if($_GET['type'] == 'gift')
	{
		if(isset($_GET['new']) && (!empty($_POST['code']) && !empty($_POST['days']) ))
		{
			$code = $_POST['code'];
			$days = $_POST['days'];
			$buyer = !empty($_POST['buyer']) ? '\''.$_POST['buyer'].'\'' : 'NULL';
			$sql = 'INSERT INTO gift(id,date,code,start_date,days,username,buyer,txn_id)
											VALUES(NULL,NOW(),\''.$code.'\',NULL,'.$days.',NULL,'.$buyer.',NULL)';
			$res = mysqli_query($link, $sql) or die(mysqli_error($link));
			// FIXME Add message
			$reload = true;
		}
		elseif(!empty($_GET['delete']))
		{
			$id = $_GET['delete'];
			$sql = 'DELETE FROM gift where id='.$id;
			$res = mysqli_query($link, $sql) or die(mysqli_error($link));
			// FIXME Add message
			$reload = true;
		}
		elseif(!empty($_GET['edit']))
		{
			// FIXME, secure all that stuff
			$id = $_GET['edit'];
			if(!empty($_POST['code']) && !empty($_POST['days']) && !empty($_POST['id']) && $id == $_POST['id'])
			{
				$code = $_POST['code'];
				$days = $_POST['days'];
				$buyer = !empty($_POST['buyer']) ? '\''.$_POST['buyer'].'\'' : '';
				$username = !empty($_POST['user']) ? '\''.$_POST['user'].'\'' : 'NULL';
				$start_date = !empty($_POST['start_date']) ? '\''.$_POST['start_date'].'\'' : 'NULL';
				$txn = !empty($_POST['txn']) ? '\''.$_POST['txn'].'\'' : 'NULL';

				$sql = 'UPDATE gift
								SET /*date=NOW(),*/
										code=\''.$code.'\',
										days='.$days.',
										buyer='.$buyer.',
										username='.$username.',
										start_date='.$start_date.',
										txn_id='.$txn.'
								WHERE id='.$id;
				$res = mysqli_query($link, $sql);
				// FIXME Add message
				$reload = true;
			}
			else
			{
				$sql = 'SELECT *
									FROM gift
									WHERE id='.$id;
				$res = mysqli_query($link, $sql);
				if($row = mysqli_fetch_assoc($res))
					$gift = $row;
			}
		}
	} // type == gift
	else if($_GET['type'] == 'demo')
	{
		if(isset($_GET['new']) && (!empty($_POST['username']) && !empty($_POST['date'])))
		{
			$date = $_POST['date'];
			$user = $_POST['username'];
			$sql = 'INSERT INTO demo(username,date)
											VALUES(\''.$user.'\',\''.$date.'\')';
			$res = mysqli_query($link, $sql) or die(mysqli_error($link));
			// FIXME Add message
			$reload = true;
		}
		elseif(!empty($_GET['delete']))
		{
			$user = $_GET['delete'];
			$sql = 'DELETE FROM demo where username=\''.$user.'\'';
			$res = mysqli_query($link, $sql) or die(mysqli_error($link));
			// FIXME Add message
			$reload = true;
		}
		elseif(!empty($_GET['edit']))
		{
			// FIXME, secure all that stuff
			$user = $_GET['edit'];
			if(!empty($_POST['username']) && !empty($_POST['date']))
			{
				$nuser = $_POST['username'];
				$days = $_POST['days'];
				$start_date = $_POST['date'];

				$sql = 'UPDATE demo
								SET username=\''.$nuser.'\',
										date=\''.$start_date.'\'
								WHERE username=\''.$user.'\'';
				$res = mysqli_query($link, $sql);
				// FIXME Add message
				$reload = true;
			}
			else
			{
				$sql = 'SELECT *,
											 7 as days
									FROM demo
									WHERE username=\''.$user.'\'';
				$res = mysqli_query($link, $sql);
				if($row = mysqli_fetch_assoc($res))
					$demo = $row;
			}
		}
	} // type == demo
	else if($_GET['type'] == 'premium')
	{
		var_dump($_POST);
		if(isset($_GET['new']) && (!empty($_POST['username']) && !empty($_POST['days'])  && !empty($_POST['date'])) )
		{
			$date = $_POST['date'];
			$days = $_POST['days'];
			$username = $_POST['username'];
			$txn = !empty($_POST['txn']) ? '\''.$_POST['txn'].'\'' : 'NULL';
			$sql = 'INSERT INTO premium(id,date,username,days,txn_id)
											VALUES(NULL,\''.$date.'\',\''.$username.'\','.$days.','.$txn.')';
			$res = mysqli_query($link, $sql) or die(mysqli_error($link));
			// FIXME Add message
			$reload = true;
		}
		elseif(!empty($_GET['delete']))
		{
			$id = $_GET['delete'];
			$sql = 'DELETE FROM premium where id='.$id;
			$res = mysqli_query($link, $sql) or die(mysqli_error($link));
			// FIXME Add message
			$reload = true;
		}
		elseif(!empty($_GET['edit']))
		{
			// FIXME, secure all that stuff
			$id = $_GET['edit'];
			if(!empty($_POST['username'])  && !empty($_POST['date'])  && !empty($_POST['days']) && !empty($_POST['id']) &&$id == $_POST['id'])
			{
				$days = $_POST['days'];
				$username = $_POST['username'];
				$date = $_POST['date'];
				$txn = !empty($_POST['txn']) ? '\''.$_POST['txn'].'\'' : 'NULL';

				$sql = 'UPDATE premium
								SET date=\''.$date.'\',
										days='.$days.',
										username=\''.$username.'\',
										txn_id='.$txn.'
								WHERE id='.$id;
				$res = mysqli_query($link, $sql);
				// FIXME Add message
				$reload = true;
			}
			else
			{
				$sql = 'SELECT *
									FROM premium
									WHERE id='.$id;
				$res = mysqli_query($link, $sql);
				if($row = mysqli_fetch_assoc($res))
					$premium = $row;
			}
		}
	} // type == premium
	mysqli_close($link);
}

if(isset($_GET['update']))
{
	include('../include/update_status.inc.php');
	Message::AddSuccess(__tr("Statuses successfully updated"));
	$reload = true;
}

if(isset($_GET['type']))
	$_SESSION['tab'] = 'premium_'.$_GET['type'];
elseif(!isset($_SESSION['tab']) || !preg_match("|^premium_|", $_SESSION['tab']))
	$_SESSION['tab'] = 'premium_premium';

if($reload)
{
	header('Location: premium.php');
	exit();
}

// Gift code
$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
		die('Connexion impossible : ' . mysqli_error());
}

$sql = 'SELECT gift.*, account.status
					FROM gift
					LEFT JOIN account
								ON account.username=gift.username
					ORDER BY id DESC;';
$res = mysqli_query($link, $sql);
$gifts = array();
while($row = mysqli_fetch_assoc($res))
	$gifts[] = $row;

if(!isset($gift))
	$gift = array(
		'id'=>NULL,
		'code'=>generateGiftCode(),
		'days'=>31,
		'buyer'=>'',
		'username'=>'',
		'start_date'=>NULL,
		'txn_id'=>NULL
	);
// Demo
$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
		die('Connexion impossible : ' . mysqli_error());
}
$sql = 'SELECT demo.*,
							7 as days,
							account.status
					FROM demo
					LEFT JOIN account
						ON account.username=demo.username
					ORDER BY date DESC';
$res = mysqli_query($link, $sql);
mysqli_close($link);
$demos = array();
while($row = mysqli_fetch_assoc($res))
	$demos[] = $row;

if(!isset($demo))
	$demo = array(
		'days'=>7,
		'username'=>'',
		'date'=>date_add_days('now',0,0),
	);
// Premium
$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
		die('Connexion impossible : ' . mysqli_error());
}
$sql = 'SELECT premium.*,
							account.status,
							account.id as uid
					FROM premium
					LEFT JOIN account
						ON account.username=premium.username
					ORDER BY premium.date ASC,
										premium.username DESC';
$res = mysqli_query($link, $sql) or die(mysqli_error($link));
mysqli_close($link);
$uid = 0;
$end = 0;
$premiums = array();
while($row = mysqli_fetch_assoc($res))
{
	if($uid != $row['uid']) $end = 0;
	$row['start'] = date_add_days($row['date'],0,$end);
	$end = date_add_days($row['date'],$row['days'],$end);
	$row['end'] = $end;
	$uid = $row['uid'];
	$premiums[] = $row;
}
$premiums = array_reverse($premiums);

if(!isset($premium))
	$premium = array(
		'id'=>NULL,
		'days'=>31,
		'username'=>'',
		'date'=>date_add_days('now',0,0),
		'txn_id'=>NULL
	);

require(ROOT_SITE.'include/message.php');
?>
<div class="card">
  <h5 class="card-header">
    <i class="icon-gift"></i> <?php echo __tr("Premium") ?> <a href="?update" class="float-right btn btn-sm btn-warning"><?php echo __tr("Update status") ?></a>
  </h5>
  <div class="card-body">
		<ul class="nav nav-tabs">
      <li class="nav-item">
        <a class="nav-link <?php echo $_SESSION['tab'] == 'premium_premium' ? 'active' : '' ?>" href="#premium" data-toggle="tab" role="tab" aria-controls="premium" aria-selected="true"><?php echo __tr('Status') ?></a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?php echo $_SESSION['tab'] == 'premium_demo' ? 'active' : '' ?>" href="#demo" data-toggle="tab" role="tab" aria-controls="demo" aria-selected="false"><?php echo __tr('Demo') ?></a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?php echo $_SESSION['tab'] == 'premium_gift' ? 'active' : '' ?>" href="#gift" data-toggle="tab" role="tab" aria-controls="ztamps" aria-selected="false"><?php echo __tr('Gift codes') ?></a>
      </li>
    </ul>
    <div class="tab-content pt-2">
			<div class="tab-pane<?php echo $_SESSION['tab'] == 'premium_premium' ? ' active' : '' ?>" id="premium">
			<table class="table table-bordered table-striped span10">
					<thead>
						<tr>
							<th class="col-sm-2"><?php echo __tr('Username') ?></th>
							<th class="col-sm-2"><?php echo __tr('Date') ?></th>
							<th class="col-sm-2"><?php echo __tr('Start date') ?></th>
							<th class="col-sm-1"><?php echo __tr('Days') ?></th>
							<th class="col-sm-2"><?php echo __tr('End date') ?></th>
							<th class="col-sm-5"><?php echo __tr('Actions') ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach($premiums as $row): ?>
						<tr>
							<td><?php echo $row['username'].(strlen($row['status']) ? " <i>(".__tr($row['status']).")</i>" : ''); ?></td>
							<td><?php echo $row['date']; ?></td>
							<td><?php echo $row['start']; ?></td>
							<td><?php echo $row['days']; ?></td>
							<td><?php echo $row['end'] ?></td>
							<td>
								<a class="btn btn-sm btn-primary" href="?type=premium&edit=<?php echo $row['id']; ?>"><i class="icon icon-edit"></i> <?php echo __tr('Edit'); ?></a>
								<a class="btn btn-sm btn-danger" href="?type=premium&delete=<?php echo $row['id']; ?>"><i class="icon icon-trash"></i> <?php echo __tr('Delete'); ?></a>
							</td>
						</tr>
						<?php	endforeach;?>
					</tbody>
				</table>
				<hr />
				<h5><?php echo empty($premium['id']) ? __tr('Add a new premium status') : __tr('Edit a new premium status'); ?></h5>
				<form method="post" <?php echo empty($premium['id']) ? 'action="premium.php?type=premium&new"' :'' ?>>
					<input type="hidden" name="id" value="<?php echo $premium['id']; ?>">
					<div class="form-group row">
						<label class="col-sm-2 col-form-label" for="username"><?php echo __tr('User') ?></label>
						<div class="col-sm-2">
							<input type="text" class="form-control" name="username" value="<?php echo $premium['username']; ?>">
						</div>
					</div>
					<div class="form-group row">
						<label class="col-sm-2 col-form-label" for="date"><?php echo __tr('Start date') ?></label>
						<div class="col-sm-2">
							<input type="text" class="form-control" name="date" value="<?php echo $premium['date']; ?>">
						</div>
					</div>
					<div class="form-group row">
						<label class="col-sm-2 col-form-label" for="days"><?php echo __tr('Duration') ?></label>
						<div class="col-sm-1">
							<input type="text" class="form-control" name="days" value="<?php echo $premium['days']; ?>">
						</div>
					</div>
					<div class="form-group row">
						<label class="col-sm-2 col-form-label" for="txn"><?php echo __tr('Transaction') ?></label>
						<div class="col-sm-2">
							<input type="text" class="form-control" name="txn" value="<?php echo $premium['txn_id']; ?>">
						</div>
					</div>
					<div class="form-group row">
						<div class="col-sm-4 offset-sm-2">
							<button type="submit" class="btn btn-primary"><?php echo  __tr('Save') ?></button>
							<?php if(!empty($premium['id'])): ?>
							<a href="?reload" class="btn btn-secondary"><?php echo __tr("Cancel") ?></a>
							<?php endif; ?>
						</div>
					</div>
				</form>
			</div>

			<div class="tab-pane<?php echo $_SESSION['tab'] == 'premium_demo' ? ' active' : '' ?>" id="demo">
				<table class="table table-bordered table-striped span10">
					<thead>
						<tr>
							<th class="col-sm-2"><?php echo __tr('Username') ?></th>
							<th class="col-sm-2"><?php echo __tr('Date') ?></th>
							<th class="col-sm-1"><?php echo __tr('Days') ?></th>
							<th class="col-sm-2"><?php echo __tr('End') ?></th>
							<th class="col-sm-5"><?php echo __tr('Actions') ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach($demos as $row): ?>
						<tr>
							<td><?php echo $row['username'].(strlen($row['status']) ? " <i>(".__tr($row['status']).")</i>" : ''); ?></td>
							<td><?php echo date_add_days($row['date'],0,0); ?></td>
							<td><?php echo $row['days']; ?></td>
							<td><?php echo date_add_days($row['date'],$row['days'],0); ?></td>
							<td>
							<a class="btn btn-sm btn-primary" href="?type=demo&edit=<?php echo $row['username'] ?>"><i class="icon icon-edit"></i> <?php echo __tr('Edit') ?></a>
								<a class="btn btn-sm btn-danger" href="?type=demo&delete=<?php echo $row['username']; ?>"><i class="icon icon-trash"></i> <?php echo __tr('Delete'); ?></a>
							</td>
						</tr>
						<?php	endforeach; ?>
					</tbody>
				</table>
				<h5><?php echo empty($demo['username']) ? __tr('Add a new demo status') : __tr('Edit a demo status'); ?></h5>
				<form method="post" <?php echo empty($demo['username']) ? 'action="premium.php?type=demo&new"' :'' ?>>
					<div class="form-group row">
						<label class="col-sm-2 col-form-label" for="username"><?php echo __tr('User') ?></label>
						<div class="col-sm-2">
							<input type="text" class="form-control" name="username" value="<?php echo $demo['username']; ?>">
						</div>
					</div>
					<div class="form-group row">
						<label class="col-sm-2 col-form-label" for="days"><?php echo __tr('Duration') ?></label>
						<div class="col-sm-1">
							<input type="text" class="form-control" name="days" value="<?php echo $demo['days']; ?>" disabled>
						</div>
					</div>
					<div class="form-group row">
						<label class="col-sm-2 col-form-label" for="date"><?php echo __tr('Start date') ?></label>
						<div class="col-sm-2">
							<input type="text" class="form-control" name="date" value="<?php echo $demo['date']; ?>">
						</div>
					</div>
					<div class="form-group row">
						<div class="col-sm-4 offset-sm-2">
							<button type="submit" class="btn btn-primary"><?php echo  __tr('Save') ?></button>
							<?php if(!empty($demo['username'])): ?>
							<a href="?reload" class="btn btn-secondary"><?php echo __tr("Cancel") ?></a>
							<?php endif; ?>
						</div>
					</div>
				</form>
			</div>

			<div class="tab-pane<?php echo $_SESSION['tab'] == 'premium_gift' ? ' active' : '' ?>" id="gift">
				<table class="table table-bordered table-striped span10">
					<thead>
						<tr>
							<th><?php echo __tr('Date') ?></th>
							<th><?php echo __tr('Code') ?></th>
							<th class="col-md-1"><?php echo __tr('Duration') ?></th>
							<th><?php echo __tr('Buyer') ?></th>
							<th><?php echo __tr('User') ?></th>
							<th><?php echo __tr('Start date') ?></th>
							<th><?php echo __tr('Actions') ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach($gifts as $row): ?>
						<tr>
							<td><?php echo $row['date']; ?></td>
							<td><?php echo $row['code']; ?></td>
							<td class="text-center"><?php echo $row['days']; ?></td>
							<td><?php echo $row['buyer']; ?><?php if(!empty($row['txn_id'])): ?> <i>(<a href="#<?php echo $row['txn_id']; ?>"><?php echo 'Transaction'; ?></a>)</i><?php endif; ?></td>
							<td><?php echo $row['username'].(strlen($row['status']) ? " <i>(".__tr($row['status']).")</i>" : ''); ?></td>
							<td><?php echo $row['start_date'] == NULL ? __tr('Not used') : date('d/m/Y', strtotime($row['start_date'])) ?></td>
							<td>
								<a class="btn btn-sm btn-primary" href="?type=gift&edit=<?php echo $row['id'] ?>"><i class="icon icon-edit"></i> <?php echo __tr('Edit') ?></a>
								<a class="btn btn-sm btn-danger" href="?type=gift&delete=<?php echo $row['id'] ?>"><i class="icon icon-trash"></i> <?php echo __tr('Delete') ?></a>
							</td>
						</tr>
							<?php endforeach; ?>
					</tbody>
				</table>
				<hr />
				<h5><?php echo empty($gift['id']) ? __tr('Insert a gift code') : __tr('Edit a gift code'); ?></h5>
				<form method="post" <?php echo empty($gift['id']) ? 'action="premium.php?type=gift&new"' :'' ?>>
					<input type="hidden" name="id" value="<?php echo $gift['id']; ?>">
					<div class="form-group row">
						<label class="col-sm-2 col-form-label" for="code"><?php echo __tr('Code') ?></label>
						<div class="col-sm-3">
							<input type="text" class="form-control" name="code" value="<?php echo $gift['code']; ?>">
						</div>
					</div>
					<div class="form-group row">
						<label class="col-sm-2 col-form-label" for="days"><?php echo __tr('Duration') ?></label>
						<div class="col-sm-1">
							<input type="text" class="form-control" name="days" value="<?php echo $gift['days']; ?>">
						</div>
					</div>
					<div class="form-group row">
						<label class="col-sm-2 col-form-label" for="buyer"><?php echo __tr('Owner') ?></label>
						<div class="col-sm-2">
							<input type="text" class="form-control" name="buyer" value="<?php echo $gift['buyer']; ?>">
						</div>
					</div>
					<div class="form-group row">
						<label class="col-sm-2 col-form-label" for="user"><?php echo __tr('User') ?></label>
						<div class="col-sm-2">
							<input type="text" class="form-control" name="user" value="<?php echo $gift['username']; ?>">
						</div>
					</div>
					<div class="form-group row">
						<label class="col-sm-2 col-form-label" for="start_date"><?php echo __tr('Start date') ?></label>
						<div class="col-sm-2">
							<input type="text" class="form-control" name="start_date" value="<?php echo $gift['start_date']; ?>">
						</div>
					</div>
					<div class="form-group row">
						<label class="col-sm-2 col-form-label" for="txn"><?php echo __tr('Transaction') ?></label>
						<div class="col-sm-2">
							<input type="text" class="form-control" name="txn" value="<?php echo $gift['txn_id']; ?>">
						</div>
					</div>
					<div class="form-group row">
						<div class="col-sm-4 offset-sm-2">
							<button type="submit" class="btn btn-primary"><?php echo  __tr('Save') ?></button>
							<?php if(!empty($gift['id'])): ?>
							<a href="?reload" class="btn btn-secondary"><?php echo __tr("Cancel") ?></a>
							<?php endif; ?>
						</div>
					</div>
				</form>
			</div>

		</div>
	</div>
</div>
<?php
require_once "../include/append.php";
?>
