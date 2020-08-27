<?php
//$Ztamps = $ojnAPI->GetListofZtamps(false);
// http://medias.lequipe.fr/logo-tennisball/1615/20
$reload = false;
if(isset($_POST['addplayer'])) {
	$_SESSION['subtab'] = "tennis_player";
	if(strlen(trim($_POST['addplayer']))) {
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/tennis/addPlayer?player=".urlencode($_POST['addplayer'])."&".$ojnAPI->getToken()));
	} else {
		Message::AddError(__tr("Player ID can't be empty"));
	}
	$reload = true;
}
else if(isset($_GET['rp'])) {
	$_SESSION['subtab'] = "tennis_player";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/tennis/removePlayer?".$ojnAPI->getToken()."&player=".urlencode($_GET['rp'])."&".$ojnAPI->getToken()));
	$reload = true;
}
$players = array();
$groups = array();
$matchs = array();
$pList = $ojnAPI->getApiList("bunny/".$_SESSION['bunny']."/tennis/getPlayers?".$ojnAPI->getToken());
$tList = $ojnAPI->getApiString("plugin/tennis/getPlayers?".$ojnAPI->getToken());
$wList = $ojnAPI->getApiString("plugin/tennis/getMatchs?".$ojnAPI->getToken());

foreach((array)$tList['group'] as $k => $v)
{
	$a = $v->attributes();
	$group = (string)$a->name;
	if(!isset($groups[$group]))
		$groups[$group] = array();
	$v = $v->children();
	foreach($v as $k2 => $v2)
	{
		$a2 = $v2->attributes();
		$id = (string)$a2['id'];
		$groups[$group][$id] = (string)$v2;
		$players[$id] =  (string)$v2;
	}
}

if(!isset($_SESSION['subtab']) || !preg_match("|^tennis_|", $_SESSION['subtab'])) {
	$_SESSION['subtab'] = "tennis_player";
}
if($reload)
{
	header("Location: bunny_plugin.php?p=tennis");
	exit();
}
?>

		<div class="tabbable">
		<ul class="nav nav-tabs">
		  <li<?php echo $_SESSION['subtab'] == 'tennis_player' ? ' class="active"' : '' ?>><a href="#player" data-toggle="tab"><?php echo __tr('Players') ?></a></li>
		  <li<?php echo $_SESSION['subtab'] == 'tennis_match' ? ' class="active"' : '' ?>><a href="#match" data-toggle="tab"><?php echo __tr('Matchs') ?></a></li>
		</ul>
		<br />
		
			<div class="tab-content">
				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'tennis_player' ? ' active' : '' ?>" id="player">

<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Add a player") ?></label>
            <div class="controls">
<select name="addplayer" id="players" style="width: 250px">
	<option value=""></option>
	<?php 
	foreach($groups as $group => $list) { ?> 
		<optgroup label="<?php echo $group ?>">
	<?php foreach($list as $id => $player) { ?>
		<option value="<?php echo $id ?>"><?php echo urldecode(str_replace(":", " ", $player)); ?></option>
	<?php } ?>
		</optgroup>
	<?php } ?>
</select>
            </div>
          </div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo __tr("Save") ?></button>
          </div>
</form>


<?php
if(!empty($pList)) {
?>
<hr />
<table class="table table-bordered table-striped span11">
	<tr>
		<th colspan="4"><?php echo __tr('Player List') ?></th>
	</tr>
	<tr>
		<th class="span8"><?php echo __tr('Player') ?></th>
		<th><?php echo __tr('Actions') ?></th>
	</tr>
<?php
	$i = 0;
	foreach($pList as $item) {
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><?php echo $players[$item] ?></td>
		<td class="span1"><a class="btn btn-danger" href="bunny_plugin.php?p=tennis&rp=<?php echo $item ?>"><?php echo __tr("Remove") ?></a></td>
	</tr>
<?php } ?>
</table>
<?php
}
?>
				</div>

				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'tennis_match' ? ' active' : '' ?>" id="match">
<table class="table table-bordereded table-striped span11">
<?php
foreach((array)$wList['match'] as $k => $v)
{
?>
	<tr>
<?php
	$a = $v->attributes();
	$statut = (string)$a->statut;
	$niveau = (string)$a->niveau;
	$timestamp = (int)$a->timestamp;
	$competition = (string)$a->competition;
	$v = $v->children();
	$dom = $v->dom;
	$domatt = $dom->attributes();
	$ext = $v->ext;
	$extatt = $ext->attributes();
	$score = "";
	$scoreDom = preg_split("|,|", (string)$domatt->score, -1, PREG_SPLIT_NO_EMPTY);
	$scoreExt = preg_split("|,|", (string)$extatt->score, -1, PREG_SPLIT_NO_EMPTY);
	foreach($scoreDom as $key => $point)
	{
		$c1 = '#666666';
		$c2 = $c1;
		if($point + 0 > $scoreExt[$key] + 0)
		{
			$c1 = '#0088CC';
		}
		if($point + 0 < $scoreExt[$key] + 0)
		{
			$c2 = '#0088CC';
		}
		$score .= "<span style='color: ".$c1."'>".$point."</span>/<span style='color: ".$c2."'>".$scoreExt[$key]."</span> &nbsp; ";
	}
	$score = trim($score);
?>
	<td class="span3"><i><?php echo $competition ?>&nbsp;<?php echo $niveau != "" ? ", ".$niveau : '' ?></i></td>
	<td class="span2"><center><?php if(in_array((string)$domatt->id, $pList) || in_array((string)$extatt->id, $pList)) { ?><span class="label label-info"><?php echo __tr('Following match') ?></span><?php } ?></center></td>
	<td class="span2"><center><?php echo str_replace(":", " ", (string)$dom) ?></center></td>
	<td class="span2"><center><b><?php echo $statut == 'avenir' ? 'A venir ('.date('H:i', $timestamp).')' : ($score .($statut == "termine" ? " (terminé)" : ($statut == "mitemps" ? " (mi-temps)" : ""))) ?></b></center></td>
	<td class="span2"><center><?php echo str_replace(":", " ", (string)$ext) ?></center></td>
<?php
?>
	</tr>
<?php
}
?>
</table>

				</div>

			</div>
		</div>
<?php
$js = '<link href="js/select2.css" rel="stylesheet"/><script src="js/select2.js"></script> <script>$(document).ready(function() { $("#players").select2({}); });</script>';
$ojnTemplate->setJS($js);
?>
