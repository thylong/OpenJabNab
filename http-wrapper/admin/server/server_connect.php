<?php
require_once "include/common.php";
if(!isset($_SESSION['token']) || !$Infos['isAdmin'])
	header('Location: index.php');

global $fail;
$wait = $ojnAPI->getApiMapped('plugin/locate/server?action=waiting&delay&'.$ojnAPI->getToken());
$fails = $ojnAPI->getApiList('plugin/locate/server?action=failing&'.$ojnAPI->getToken());
$bad = $ojnAPI->getApiMapped('plugin/locate/server?action=list&'.$ojnAPI->getToken());
asort($wait);
function fail($mac)
{
	global $fails;
	$color = 'success';
	if(in_array($mac, $fails))
		$color = 'error';
	return '<span class="badge badge-'.$color.'">'.$mac.'</span>';
}
?>
<div class="row">
	<div class="span12">
		<div class="widget">
			<div class="widget-header">
				<i class="icon-th-large"></i>
				<h3><?php echo $nbr ?> <?php echo __tr("Bunnies") ?></h3>
			</div> <!-- /widget-header -->
			<div class="widget-content">
<table class="table table-bordered table-striped">
	<thead>
	<tr>
		<th><?php echo __tr('MAC') ?></th>
		<th><?php echo __tr('Delay') ?></th>
		<th><?php echo __tr('Server') ?></th>
		<th><?php echo __tr('Actions') ?></th>
	</tr>
	</thead>
<tbody>
<?php
foreach($wait as $bunny => $time)
{
?>
	<tr>
		<td><?php echo fail($bunny); ?></td>
		<td><?php echo $time . 's' ?></td>
		<td><?php echo isset($bad[$bunny]) ? $bad[$bunny] : '&nbsp;' ?></td>
		<td>
			<a target="_blank" class="btn btn-mini btn-primary" href="/bunny/index.php?b=<?php echo $bunny ?>"><?php echo __tr('Manage bunny') ?></a>
		</td>
	</tr>
<?php
}
?>		
</tbody>
</table>
			</div>
		</div>
	</div>
</div>
<?php
require_once "include/append.php";
?>

