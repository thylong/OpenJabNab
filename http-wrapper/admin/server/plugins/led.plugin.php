<?php
if(!empty($_POST)) {
	if($_POST['type']=="weather") {
		$retour = $ojnAPI->getApiString("plugin/led/setvalue?type=weather&condition=".$_POST['condition']."&value=".$_POST['value']."&".$ojnAPI->getToken());
		$_SESSION['message'] = isset($retour['ok']) ? $retour['ok'] : "Error : ".$retour['error'];
		header("Location: server_plugin.php?p=led");
	}
}
$weatherConditions = array( -1 => "Unknow", 0 => "Sun", 1 => "Cloud", 2 => "Fog", 3 => "Rain", 4 => "Snow", 5 => "Storm");
$weatherList = $ojnAPI->getApiMapped("plugin/led/getvalues?type=weather&".$ojnAPI->getToken());
asort($weatherList);

?>
<fieldset>
<?php
if(!empty($weatherList)) {
?>
<center>
<form method="POST">
<table style="width: 80%">
	<tr>
		<th colspan="4">Weather</th>
	</tr>
	<tr>
		<th>Condition</th>
		<th>Value</th>
		<th>Actions</th>
	</tr>
<?php
	$i = 0;
	foreach($weatherList as $c => $v) {
		if(isset($_GET['e']) && $_GET['e'] == "weather" && isset($_GET['c']) && $_GET['c'] == $c)
		{
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
<input type="hidden" name="type" value="weather" />
		<td><input type="hidden" name="condition" value="<?php echo $c ?>" /><?php echo $c ?></td>
		<td><select name="value"><?php foreach($weatherConditions as $val => $cond) { echo '<option value="'.$val.'"'.($val==$v?' selected="selected"':'').'>'.$cond.'</option>'; }  ?></select></td>
		<td width="15%"><input type="submit" value="Enregistrer" /></td>
	</tr>
<?php 	 
		}
		else
		{
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><?php echo $c ?></td>
		<td><?php echo $weatherConditions[$v] ?></td>
		<td width="15%"><a href="server_plugin.php?p=led&e=weather&c=<?php echo $c ?>">Edit</a></td>
	</tr>
<?php 	} ?>
<?php } ?>
</table>
</form>
<?php
}
?>
</fieldset>
