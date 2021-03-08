<?php
require_once "../include/common.php";

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
<div class="card">
  <h5 class="card-header">
    <i class="icon-cog"></i> <?php echo count($wait); ?> <?php echo __tr("Bunnies") ?>
  </h5>
  <div class="card-body">
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
      <?php foreach($wait as $bunny => $time): ?>
        <tr>
          <td><?php echo fail($bunny); ?></td>
          <td><?php echo $time . 's' ?></td>
          <td><?php echo isset($bad[$bunny]) ? $bad[$bunny] : '&nbsp;' ?></td>
          <td>
            <a target="_blank" class="btn btn-sm btn-primary" href="/bunny/index.php?b=<?php echo $bunny ?>"><i class="icon-large icon-cog"></i> <?php echo __tr('Manage bunny') ?></a>
            <a target="_blank" class="btn btn-sm btn-warning" href="/admin/bunny/bunny_expert.php?mac=<?php echo $bunny; ?>"><i class="icon-large icon-search"></i> <?php echo __tr('Expert view') ?></a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once "../include/append.php"; ?>
