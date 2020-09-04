<?php
require_once '../include/common.php';

$plugins = $ojnAPI->getListOfPlugins(false, $ojnTemplate->getLanguage());
if(is_array($plugins)) {
        asort($plugins);
}

$unique=!empty($_POST['unique']);
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
<input type="checkbox" name="unique" <?php echo $unique ? 'checked="checked"' : ''; ?> /> Unique
<input type="submit" value="<?php echo __tr("Go !") ?>" class="btn btn-primary"/>
</form>
<?php
$unique_v = array();
if(isset($_POST['r'])) {
	$url = "bunnies/settingsForBunnies?action=get&key=".trim($_POST['r']);
	if($_POST['sender'] != '--')
		$url .= '&plugin=' . $_POST['sender'];
	$url .= '&' . $ojnAPI->getToken();
	$r = $ojnAPI->getApiXMLArray($url);
	$data = array();
	foreach($r['api']['list'] as $k => $v)
	{
    $key = $v['item'][0]['key'];
    $val = $v['item'][1]['value'];

    if(!$unique)
  		$data[$key] = $val;
    else
    {
      if(!isset($data[$val])) $data[$val] = array();
      array_push($data[$val],$key);
    }
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
    if($unique)
    {
      $value = implode(',',$value);
      if(strlen($value) > 80)
        $value = substr($value,0, 80).'...';
      $t = $bunny;
      $bunny = $value;
      $value = $t;
    }
?>
	<tr>
		<td><?php echo $bunny; ?></td>
		<td><?php echo $value; ?></td>
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
