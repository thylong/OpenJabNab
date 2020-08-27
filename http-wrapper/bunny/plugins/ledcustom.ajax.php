<?php
require_once '../../include/common.php';
ob_end_clean();
if(!isset($_SESSION['token']))
header('Location: index.php');
if(isset($_GET['sn']))
  define("BUNNY_API", "bunny/" . $_GET['sn']);
else
header('Location: index.php');

$data = $ojnAPI->getApiRaw(BUNNY_API."/ledcustom/chor?action=fetch&".$ojnAPI->getToken());
$chors = simplexml_load_string($data);

require_once dirname(realpath(__FILE__)).'/ledcustom.inc.php';

?>
<h5><?php echo __tr('Choregraphies List') ?></h5>
<table class="table table-bordered table-striped">
  <tr>
    <th><?php echo __tr('Service') ?></th>
    <th><?php echo __tr('Value') ?></th>
    <th><?php echo __tr('Delay') ?></th>
    <th colspan="2"><?php echo __tr('LEDs') ?></th>
  </tr>
<?php
foreach($chors->chors->chor as $chor):
  $attrs = (array)$chor->attributes();
  $attrs = $attrs['@attributes'];
  $leds = (string)$chor;
  $id = 'sys_' . $attrs['service'] . '_' . $attrs['value'];
?>
<tr>
  <td><?php echo $attrs['service'] ?></td>
  <td><?php echo $attrs['value'] ?></td>
  <td><?php echo $attrs['tempo'] ?></td>
  <td><?php echo $leds ?></td>
  <td><?php getLed($leds, $attrs['tempo'], $id) ?></td>
</tr>
<?php endforeach; ?>
</table>
