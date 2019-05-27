<?php
require_once "../include/common.php";
require(ROOT_SITE.'include/message.php');
$ojnTemplate->setTitle(__tr('Plugin list'));
?>
<div class="row">
    <div class="span12">
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
        <div class="widget widget">
            <div class="widget-header">
                <i class="icon-list-alt"></i><h3><?php echo __tr("List of plugins") ?></h3>
            </div>
            <div class="widget-content">
<style>
.table tr td {
	text-align: center;
	vertical-align: middle;
}
.table tr th {
	text-align: center;
	text-size: 8px;
	vertical-align: middle;
	text-transform: none;
}
.rotate div {

    -webkit-transform: rotate(270deg);
    -moz-transform: rotate(270deg);
    -o-transform: rotate(270deg);
    writing-mode: lr-tb;

}
</style>
<table class="table table-bordered table-striped span11">
	<tr style="height: 100px">
<?php /*
		<th><?php echo __tr('Name') ?></th>
		<th colspan="2" style="width: 7%"><img src="image_table.php?text=<?php echo __tr('Click') ?>"></th>
		<th style="width: 7%"><img src="image_table.php?text=<?php echo __tr('RFID') ?>"></th>
		<th style="width: 7%"><img src="image_table.php?text=<?php echo __tr('Ears') ?>"></th>
		<th style="width: 7%"><img src="image_table.php?text=<?php echo __tr('Voice recognition') ?>"></th>
		<th style="width: 7%"><img src="image_table.php?text=<?php echo __tr('Record') ?>"></th>
		<th style="width: 7%"><img src="image_table.php?text=<?php echo __tr('Schedule') ?>"></th>
		<th style="width: 7%"><img src="image_table.php?text=<?php echo __tr('Automatic') ?>"></th>
		<th style="width: 7%"><img src="image_table.php?text=<?php echo __tr('Available soon') ?>"></th>
		<th style="width: 7%"><img src="image_table.php?text=<?php echo __tr('Premium') ?>"></th>
		<th style="width: 12%"><?php echo __tr('Languages') ?></th>
*/ ?>
		<th><?php echo __tr('Name') ?></th>
		<th class="rotate" colspan="2" style="width: 7%"><div><?php echo __tr('Click') ?></div></th>
		<th class="rotate" style="width: 7%"><div><?php echo __tr('RFID') ?></div></th>
		<th class="rotate" style="width: 7%"><div><?php echo __tr('Ears') ?></div></th>
		<th class="rotate" style="width: 7%"><div><?php echo __tr('Voice recognition') ?> <i>(<?php echo __tr('Premium') ?>)</i></div></th>
		<th class="rotate" style="width: 7%"><div><?php echo __tr('Record') ?></div></th>
		<th class="rotate" style="width: 7%"><div><?php echo __tr('Schedule') ?></div></th>
		<th class="rotate" style="width: 7%"><div><?php echo __tr('Automatic') ?></div></th>
		<th class="rotate" style="width: 7%"><div><?php echo __tr('Repeat') ?></div></th>
		<th class="rotate" style="width: 7%"><div><?php echo __tr('Available soon') ?></div></th>
		<th class="rotate" style="width: 7%"><div><?php echo __tr('Premium') ?></div></th>
		<th class="rotate" style="width: 12%"><div><?php echo __tr('Languages') ?></div></th>
	</tr>
<?php foreach($list as $plugin): ?>
	<tr>
		<td style="text-align: left" alt="<?php echo __tr('Version %1', $plugin['version']) ?>" title="<?php echo __tr('Version %1', $plugin['version']) ?>"><?php echo $plugin['name'] ?></td>
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
        </div>
            </div>
        </div>
<?php
require_once ROOT_SITE.'include/append.php';
?>
