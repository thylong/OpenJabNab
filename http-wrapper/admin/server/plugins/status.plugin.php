<?php
$bunnies = array();
$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
    die('Connexion impossible : ' . mysqli_error());
}
$reload = false;

$actions = array('save','display','unique');
$types=array('config','shortconfig','running','silent');

if(!empty($_GET['action']) && !empty($_GET['type']) && isset($_GET['value']) )
{
  $a = $_GET['action'];
  if(!in_array($a,$actions)) $a = '';
  $t = $_GET['type'];
  if(!in_array($t,$types)) $t = '';
  $v = !empty($_GET['value']) ? '1' : '0';

  $req = 'plugin/status/config?action='.$a.'&'.$a.'='.$t.'&value='.$v;
  Message::AddFromApi($ojnAPI->getApiString($req.'&'.$ojnAPI->getToken()));
	$reload = true;
}


$val = array();
foreach($actions as $a)
{
  foreach($types as $p)
  {
    // plugin/status/config?action=unique&unique=running
    $req = 'plugin/status/config?action='.$a.'&'.$a.'='.$p;
    //var_dump($req);
    $v = $ojnAPI->getApiValue($req.'&'.$ojnAPI->getToken());
    $val[$p][$a] = $v;
  }
}

if($reload)
{
	header("Location: ?p=status");
	exit();
}
include('include/message.php');

?>
<table>
  <thead>
    <tr>
      <th>Type</th>
      <?php foreach($actions as $a): ?>
      <th><?php echo ucfirst($a); ?></th>
      <?php endforeach; ?>
    </tr>
  </thead>
  <tbody>
  <?php foreach($val as $type => $values): ?>
    <tr>
      <td><?php echo $type; ?></td>
      <?php foreach($values as $action => $v): ?>
      <td><a href="?p=status&action=<?php echo $action; ?>&type=<?php echo $type; ?>&value=<?php echo $v == '0' ? '1' : '0'; ?>"><?php echo $v; ?></a></td>
      <?php endforeach;?>
    </tr>
  <?php endforeach;?>
  </tbody>
</table>
