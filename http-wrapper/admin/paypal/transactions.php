<?php
require_once "../include/common.php";
?>
<div class="card">
  <h5 class="card-header">
    <i class="icon-cog"></i> <?php echo __tr("Paypal transactions") ?>
  </h5>
  <div class="card-body">
		<table class="table table-bordered table-striped">
			<thead>
				<tr>
					<th class="col-sm-1"><?php echo __tr('ID'); ?></th>
					<th class="col-sm-3"><?php echo __tr('Email') ?></th>
					<th class="col-sm-auto"><?php echo __tr('Username') ?></th>
					<th class="col-sm-2"><?php echo __tr('Date') ?></th>
					<th class="col-sm-auto"><?php echo __tr('Type') ?></th>
					<th class="col-sm-auto"><?php echo __tr('Gross') ?></th>
					<th class="col-sm-auto"><?php echo __tr('Fee') ?></th>
					<th class="col-sm-auto"><?php echo __tr('Net') ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
				if (!$link) {
						die('Connexion impossible : ' . mysqli_error());
				}

				$total = 0; $fees = 0;
				$sql = "SELECT pp.*, don.id as id_don, gift.id as id_gift, premium.id as id_premium
									FROM paypal_txn as pp
									LEFT JOIN don
										ON pp.txn_id=don.txn_id
									LEFT JOIN gift
										ON pp.txn_id=gift.txn_id
									LEFT JOIN premium
										ON pp.txn_id=premium.txn_id
									WHERE pp.txn_gross > 0
									ORDER BY pp.date DESC";
				$res = mysqli_query($link, $sql);
				$date = '';
				while($row = mysqli_fetch_assoc($res)):
					$total += $row['txn_gross'];
					$fees  += $row['txn_fee'];
					$date   = date('d/m/Y H:i', strtotime($row['date']));
				?>
				<tr>
					<td><?php echo $row['txn_id']; ?></td>
					<td><?php echo $row['pay_email']; ?></td>
					<td><?php echo $row['username']; ?></td>
					<td><?php echo $date ?></td>
					<td>
						<?php if(!empty($row['id_don'])): ?><span title="#<?php echo $row['id_don']; ?>" class="badge badge-success">Donation</span>
						<?php elseif(!empty($row['id_gift'])): ?><span title="#<?php echo $row['id_gift']; ?>" class="badge badge-danger">Gift</span>
						<?php elseif(!empty($row['id_premium'])): ?><span title="#<?php echo $row['id_premium']; ?>" class="badge badge-primary">Premium</span>
						<?php else: ?> <span class="badge badge-warning">Unknown</span><?php endif; ?>
					</td>
					<td><?php echo round($row['txn_gross'], 2); ?> €</td>
					<td><?php echo round($row['txn_fee'], 2); ?> €</td>
					<td><?php echo round($row['txn_gross']-$row['txn_fee'], 2); ?> €</td>
				</tr>
				<?php endwhile; ?>
				<tr>
					<th colspan="5"><?php echo __tr('Total since %1',$date); ?></th>
					<td><?php echo round($total, 2); ?> €</td>
					<td><?php echo round($fees, 2); ?> €</td>
					<td><?php echo round($total-$fees, 2); ?> €</td>
				</tr>
			</tbody>
			<?php /* FIX Missing donations from transactions
			<hr />
			<pre>
			<?php foreach(array('EMAIL'=>array('username'=>'USERNAME','name'=>'NAME'))) as $email=>$user)
			{
				$sql = 'SELECT pp.*, don.id as id_don, gift.id as id_gift, premium.id as id_premium
									FROM paypal_txn as pp
									LEFT JOIN don
										ON pp.txn_id=don.txn_id
									LEFT JOIN gift
										ON pp.txn_id=gift.txn_id
									LEFT JOIN premium
										ON pp.txn_id=premium.txn_id
									WHERE pp.txn_gross > 0
										AND pp.pay_email=\''.$email.'\'
										AND don.id IS NULL
										AND gift.id IS NULL
										AND premium.id IS NULL
									ORDER BY pp.date DESC';
				//var_dump($sql);
				$res = mysqli_query($link, $sql);
				$date = '';
				while($row = mysqli_fetch_assoc($res))
				{
					$r = 'INSERT INTO don
								VALUES(NULL,
											 \''.date('Y/m/d H:i:s', strtotime($row['txn_date'])).'\',
											 \''.$user['name'].'\',
											 \''.$row['pay_email'].'\',
											 \''.$user['username'].'\',
											 \''.$row['txn_gross'].'\',
											 \''.$row['txn_id'].'\'
											)';
					var_dump($r);
					mysqli_query($link, $r) or die(mysqli_error($link));

				}

			}
			?>
			</pre>*/?>
		</table>
	</div>
</div>
<?php require_once "../include/append.php"; ?>
