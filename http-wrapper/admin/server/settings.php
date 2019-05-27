<?php
require_once '../include/common.php';

$plugins = $ojnAPI->getListOfPlugins(false, $ojnTemplate->getLanguage());
if(is_array($plugins)) {
        asort($plugins);
}

?>
	      <div class="row">
	      	<div class="span12">
	      		<div class="widget">
					<div class="widget-header">
						<i class="icon-th-large"></i>
					</div> <!-- /widget-header -->
					<div class="widget-content">
<form method="post">

<select name="sender" class="span5">
	<option value="--"><?php echo __tr('Global') ?></option>
	<?php foreach($plugins as $k=>$p): ?>
	<option value="<?php echo $k; ?>"<?php if(isset($_POST['sender']) && $_POST['sender'] == $k): ?> selected="selected"<?php endif; ?>><?php echo $p; ?></option>
	<?php endforeach; ?>
</select>
<input type="text" class="span5" name="r" value="<?php echo !empty($_POST['r']) ? $_POST['r'] : '' ?>"/><br />
<input type="submit" value="<?php echo __tr("Go !") ?>" class="btn btn-primary"/>
</form>
<?php
if(isset($_POST['r'])) {
	$url = "bunnies/settingsForBunnies?action=get&key=".trim($_POST['r']);
	if($_POST['sender'] != '--')
		$url .= '&plugin=' . $_POST['sender'];
	$url .= '&' . $ojnAPI->getToken();
	$r = $ojnAPI->getApiXMLArray($url);
	$data = array();
	foreach($r['api']['list'] as $k => $v)
	{
		$d = $v['item'];
		$data[$d[0]['key']] = $d[1]['value'];
	}
	asort($data);
?>
<table class="table table-bordered table-striped span10">
	<tr>
		<th><?php echo __tr('Bunny') ?></th>
		<th><?php echo __tr('Value') ?></th>
	</tr>
<?php
	foreach($data as $bunny => $value)
	{
?>
	<tr>
		<td><?php echo $bunny ?></td>
		<td><?php echo $value ?></td>
	</tr>
<?php
	}
?>
</table>
<?php
}
?>
					</div> <!-- /widget-content -->
				</div> <!-- /widget -->
		    </div> <!-- /span12 -->
	      </div> <!-- /row -->
<?php
require_once '../include/append.php';
?>
