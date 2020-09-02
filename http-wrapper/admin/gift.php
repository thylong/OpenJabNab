<?php
$reload = false;
require_once "include/common.php";
require_once 'include/tools.inc.php';

if(isset($_GET['new']))
{
	if(!empty($_POST['code']) && !empty($_POST['days']))
		{
			$code = $_POST['code'];
			$days = $_POST['days'];
			$buyer = !empty($_POST['buyer']) ? '\''.$_POST['buyer'].'\'' : 'NULL';

			$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
			if (!$link) {
					die('Connexion impossible : ' . mysqli_error());
			}
		$sql = 'INSERT INTO gift(id,date,code,start_date,days,username,buyer,txn_id)
										VALUES(NULL,NOW(),\''.$code.'\',NULL,'.$days.',NULL,'.$buyer.',NULL)';
		$res = mysqli_query($link, $sql) or die(mysqli_error($link));
		mysqli_close($link);
		$reload = true;
	}
}
elseif(!empty($_GET['edit']))
{
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link)
			die('Connexion impossible : ' . mysqli_error());

	// FIXME, secure all that stuff
	$id = $_GET['edit'];
	if(!empty($_POST['code']) && !empty($_POST['days']) && $id == $_POST['id'])
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
		$reload = true;
	}
	else
	{
		$sql = 'SELECT *
							FROM gift
							WHERE id='.$id;
		$res = mysqli_query($link, $sql);
		if($row = mysqli_fetch_assoc($res))
			$code = $row;
	}
	mysqli_close($link);
}

if(isset($_GET['update']))
{
	include('include/update_status.inc.php');
	$reload = true;
	Message::AddSuccess(__tr("Statuses successfully updated"));
}
if($reload)
{
	header('Location: gift.php');
	exit();
}
require(ROOT_SITE.'include/message.php');
?>
<div class="card">
  <h5 class="card-header">
    <i class="icon-gift"></i> <?php echo __tr("Gift codes") ?>
  </h5>
  <div class="card-body">
	<?php if(!empty($_GET['edit']) && !empty($code)):	?>
		<h5><?php echo __tr('Edit a gift code'); ?></h5>
		<form method="post">
			<input type="hidden" name="id" value="<?php echo $code['id']; ?>">
			<div class="form-group row">
				<label class="col-sm-2 col-form-label" for="code"><?php echo __tr('Code') ?></label>
				<div class="col-sm-3">
					<input type="text" class="form-control" name="code" value="<?php echo $code['code']; ?>">
				</div>
      </div>
			<div class="form-group row">
				<label class="col-sm-2 col-form-label" for="days"><?php echo __tr('Duration') ?></label>
				<div class="col-sm-1">
					<input type="text" class="form-control" name="days" value="<?php echo $code['days']; ?>">
				</div>
      </div>
			<div class="form-group row">
				<label class="col-sm-2 col-form-label" for="buyer"><?php echo __tr('Buyer') ?></label>
				<div class="col-sm-2">
					<input type="text" class="form-control" name="buyer" value="<?php echo $code['buyer']; ?>">
				</div>
      </div>
			<div class="form-group row">
				<label class="col-sm-2 col-form-label" for="user"><?php echo __tr('User') ?></label>
				<div class="col-sm-2">
					<input type="text" class="form-control" name="user" value="<?php echo $code['username']; ?>">
				</div>
      </div>
			<div class="form-group row">
				<label class="col-sm-2 col-form-label" for="start_date"><?php echo __tr('Start date') ?></label>
				<div class="col-sm-2">
					<input type="text" class="form-control" name="start_date" value="<?php echo $code['start_date']; ?>">
				</div>
      </div>
			<div class="form-group row">
				<label class="col-sm-2 col-form-label" for="txn"><?php echo __tr('Transaction') ?></label>
				<div class="col-sm-2">
					<input type="text" class="form-control" name="txn" value="<?php echo $code['txn_id']; ?>">
				</div>
      </div>
			<div class="form-group row">
				<div class="col-sm-4 offset-sm-2">
					<button type="submit" class="btn btn-primary"><?php echo  __tr('Save') ?></button>
					<a href="?" class="btn btn-secondary"><?php echo __tr("Cancel") ?></a>
				</div>
			</div>
		</form>
		<?php elseif(isset($_GET['new'])): ?>
			<form method="post">
			<div class="form-group row">
				<label class="col-sm-2 col-form-label" for="code"><?php echo __tr('Code') ?></label>
				<div class="col-sm-3">
					<input type="text" class="form-control" name="code">
				</div>
      </div>
			<div class="form-group row">
				<label class="col-sm-2 col-form-label" for="days"><?php echo __tr('Duration') ?></label>
				<div class="col-sm-1">
					<input type="text" class="form-control" name="days">
				</div>
      </div>
			<div class="form-group row">
				<label class="col-sm-2 col-form-label" for="buyer"><?php echo __tr('Buyer') ?></label>
				<div class="col-sm-2">
					<input type="text" class="form-control" name="buyer">
				</div>
      </div>
			<div class="form-group row">
				<div class="col-sm-4 offset-sm-2">
					<button type="submit" class="btn btn-primary"><?php echo  __tr('Save') ?></button>
					<a href="?" class="btn btn-secondary"><?php echo __tr("Cancel") ?></a>
				</div>
			</div>
		</form>
		<?php else: ?>
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
				<?php
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
				while($row = mysqli_fetch_assoc($res)):
				?>
				<tr>
					<td><?php echo $row['date']; ?></td>
					<td><?php echo $row['code']; ?></td>
					<td class="text-center"><?php echo $row['days']; ?></td>
					<td><?php echo $row['buyer']; ?><?php if(!empty($row['txn_id'])): ?> <i>(<a href="#<?php echo $row['txn_id']; ?>"><?php echo 'Transaction'; ?></a>)</i><?php endif; ?></td>
					<td><?php echo $row['username'].(strlen($row['status']) ? " <i>(".__tr($row['status']).")</i>" : ''); ?></td>
					<td><?php echo $row['start_date'] == NULL ? __tr('Not used') : date('d/m/Y', strtotime($row['start_date'])) ?></td>
					<td>
						<a class="btn btn-sm btn-primary" href="?edit=<?php echo $row['id'] ?>"><?php echo __tr('Edit') ?></a>
					</td>
				</tr>
				<?php endwhile; ?>
			</tbody>
		</table>
		<div>
			<a href="?new" class="btn btn-primary"><?php echo __tr("Insert a gift code") ?></a>
			<a href="?update" class="btn btn-warning"><?php echo __tr("Update status") ?></a>
		</div>
		<?php
		endif;
		?>
	</div>
</div>
<?php
require_once "include/append.php";
?>
