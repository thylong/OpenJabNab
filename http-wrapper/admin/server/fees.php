<?php
require_once "../include/common.php";

require(ROOT_SITE.'include/message.php');
$Target = !empty($_GET['tgt']) ? (int)$_GET['tgt'] : SERVER_MONTHLY_FEES;
?>
<div class="card">
  <h5 class="card-header">
    <i class="icon-cog"></i> <?php echo __tr('Fees fullfilment');?> <span class="float-right badge badge-secondary"><?php echo __tr('Target: %1 €/month', $Target); ?></span>
  </h5>
  <div class="card-body">
		<?php $earnings = getServerFeesFullfilment($Target);
		//<pre><?php echo var_dump($earnings); </pre>
		?>
		<table class="table table-bordered table-striped">
		  <thead>
			  <tr>
					<th class="col-sm-auto"><?php echo __tr('Month'); ?></th>
					<th class="col-sm-1"><?php echo __tr('Percentage'); ?></th>
					<th class="col-sm-auto"><?php echo __tr('Donation'); ?></th>
					<th class="col-sm-auto"><?php echo __tr('Balance'); ?></th>
				</tr>
			</thead>
			<tbody>
					<?php foreach($earnings as $m => $a): ?>
					<tr>
						<td><?php echo $m; ?></td>
						<td><h5><span class="badge badge-<?php if($a['p'] == 100): ?>success<?php elseif($a['p'] == 0): ?>danger<?php else: ?>secondary<?php endif; ?>"><?php echo $a['p']; ?> %</span></h5></td>
						<td><?php echo round($a['month'],2); ?> €</td>
						<td><?php echo round($a['sum'],2); ?> €</td>
					</tr>
					<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
<?php require_once "../include/append.php"; ?>
