<?php
require_once "../include/common.php";
require(ROOT_SITE.'include/message.php');
$ojnTemplate->setTitle(__tr('Plugin list'));
?>
<?php
$xml = $ojnAPI->getApiRaw('plugins/getPlugins?lng='.$Infos['language']);
$plugins = simplexml_load_string($xml);

$list = array();
$premium = array();
$wip = array();

foreach($plugins->plugins->plugin as $plugin)
{
	$p = array();
	$p['name'] = (string)$plugin;
	$attrs = (array)$plugin->attributes();
	foreach($attrs['@attributes'] as $key => $value)
	{
		$p[$key] = trim($value);
	}

	if(!($p['required'] || $p['system']))
		$list[$p['name']] = $p;
}
ksort($list);
function check($str)
{
	echo $str ? "X" : "&nbsp;";
}
?>
  <div class="card">
    <h5 class="card-header">
      <i class="icon-list-alt"></i> <?php echo __tr("List of plugins") ?>
    </h5>
  <div class="">
    <table class="card-content table table-bordered table-striped">
	    <tr class="text-center">
        <th><?php echo __tr('Name') ?></th>
        <th colspan="2" style="width: 7%"><div><?php echo __tr('Click') ?></div></th>
        <th><div><?php echo __tr('RFID') ?></div></th>
        <th><div><?php echo __tr('Ears') ?></div></th>
        <th><div><?php echo __tr('Voice recognition') ?> <i>(<?php echo __tr('Premium') ?>)</i></div></th>
        <th><div><?php echo __tr('Record') ?></div></th>
        <th><div><?php echo __tr('Schedule') ?></div></th>
        <th><div><?php echo __tr('Automatic') ?></div></th>
        <th><div><?php echo __tr('Repeat') ?></div></th>
        <th><div><?php echo __tr('Available soon') ?></div></th>
        <th><div><?php echo __tr('Premium') ?></div></th>
        <th><div><?php echo __tr('Languages') ?></div></th>
	    </tr>
      <?php foreach($list as $plugin): ?>
      <tr class="text-center">
        <td class="text-left" alt="<?php echo __tr('Version %1', $plugin['version']) ?>" title="<?php echo __tr('Version %1', $plugin['version']) ?>"><?php echo $plugin['name'] ?></td>
        <td><?php echo check($plugin['single']) ?></td>
        <td><?php echo check($plugin['double']) ?></td>
        <td><?php echo check($plugin['rfid']) ?></td>
        <td><?php echo check($plugin['ears']) ?></td>
        <td><?php echo check($plugin['voice']) ?></td>
        <td><?php echo check($plugin['record']) ?></td>
        <td><?php echo check($plugin['cron']) ?></td>
        <td><?php echo check($plugin['period']) ?></td>
        <td><?php echo check($plugin['message']) ?></td>
        <td><?php echo check($plugin['dev']) ?></td>
        <td><?php echo check($plugin['premium']) ?></td>
        <td><?php echo $plugin['languages'] == 'all' ? __tr('All languages') : implode(', ',explode(',', $plugin['languages'])) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
</div>
<?php
require_once ROOT_SITE.'include/append.php';
?>
