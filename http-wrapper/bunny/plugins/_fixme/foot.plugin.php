<?php
//$Ztamps = $ojnAPI->GetListofZtamps(false);
// http://medias.lequipe.fr/logo-football/1615/20
$reload = false;
if(isset($_POST['addteam'])) {
	$_SESSION['subtab'] = "foot_team";
	if(strlen(trim($_POST['addteam']))) {
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/foot/addTeam?team=".urlencode($_POST['addteam'])."&".$ojnAPI->getToken()));
	} else {
		Message::AddError(__tr("Team ID can't be empty"));
	}
	$reload = true;
}
else if(isset($_GET['rp'])) {
	$_SESSION['subtab'] = "foot_team";
	Message::AddFromApi($ojnAPI->getApiString("bunny/".$_SESSION['bunny']."/foot/removeTeam?".$ojnAPI->getToken()."&team=".urlencode($_GET['rp'])."&".$ojnAPI->getToken()));
	$reload = true;
}
$teams = array();
$groups = array();
$matchs = array();
$pList = $ojnAPI->getApiList("bunny/".$_SESSION['bunny']."/foot/getTeams?".$ojnAPI->getToken());
$tList = $ojnAPI->getApiString("plugin/foot/getTeams?".$ojnAPI->getToken());
$wList = $ojnAPI->getApiString("plugin/foot/getMatchs?".$ojnAPI->getToken());

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
		$teams[$id] =  (string)$v2;
	}
}

if(!isset($_SESSION['subtab']) || !preg_match("|^foot_|", $_SESSION['subtab'])) {
	$_SESSION['subtab'] = "foot_team";
}
if($reload)
{
	header("Location: bunny_plugin.php?p=foot");
	exit();
}
?>

		<div class="tabbable">
		<ul class="nav nav-tabs">
		  <li<?php echo $_SESSION['subtab'] == 'foot_team' ? ' class="active"' : '' ?>><a href="#team" data-toggle="tab"><?php echo __tr('Teams') ?></a></li>
		  <li<?php echo $_SESSION['subtab'] == 'foot_match' ? ' class="active"' : '' ?>><a href="#match" data-toggle="tab"><?php echo __tr('Matchs') ?></a></li>
		</ul>
		<br />
		
			<div class="tab-content">
				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'foot_team' ? ' active' : '' ?>" id="team">

<form method="post" class="form-horizontal">
          <div class="control-group">
            <label for="input01" class="control-label"><?php echo __tr("Add an team") ?></label>
            <div class="controls">
<select name="addteam" id="teams" style="width: 250px">
	<option value=""></option>
	<?php 
	foreach($groups as $group => $list) { ?> 
		<optgroup label="<?php echo $group ?>">
	<?php foreach($list as $id => $team) { ?>
		<option value="<?php echo $id ?>"><?php echo urldecode($team); ?></option>
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
		<th colspan="4"><?php echo __tr('Team List') ?></th>
	</tr>
	<tr>
		<th class="span8"><?php echo __tr('Team') ?></th>
		<th><?php echo __tr('Actions') ?></th>
	</tr>
<?php
	$i = 0;
	foreach($pList as $item) {
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><?php echo $teams[$item] ?></td>
		<td class="span1"><a class="btn btn-danger" href="bunny_plugin.php?p=foot&rp=<?php echo $item ?>"><?php echo __tr("Remove") ?></a></td>
	</tr>
<?php } ?>
</table>
<?php
}
?>
				</div>

				<div class="tab-pane<?php echo $_SESSION['subtab'] == 'foot_match' ? ' active' : '' ?>" id="match">
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
?>
	<td class="span3"><i><?php echo $competition ?>&nbsp;<?php echo $niveau != "" ? ", ".$niveau : '' ?></i></td>
	<td class="span2"><center><?php if(in_array((string)$domatt->id, $pList) || in_array((string)$extatt->id, $pList)) { ?><span class="label label-info"><?php echo __tr('Following match') ?></span><?php } ?></center></td>
	<td class="span2"><center><?php echo (string)$dom ?></center></td>
	<td class="span2"><center><b><?php echo $statut == 'avenir' ? 'A venir ('.date('H:i', $timestamp).')' : ($domatt->score . " - " . $extatt->score .($statut == "termine" ? " (terminé)" : ($statut == "mitemps" ? " (mi-temps)" : ""))) ?></b></center></td>
	<td class="span2"><center><?php echo (string)$ext ?></center></td>
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
/*
function format(state) {
    if (!state.id) return state.text; // optgroup
    return "<img class='flag' src='images/flags/" + state.id.toLowerCase() + ".png'/>" + state.text;
    }
    $("#e4").select2({
    formatResult: format,
    formatSelection: format
    });
// http://medias.lequipe.fr/logo-football/1615/20
*/
$js = '<link href="js/select2.css" rel="stylesheet"/><script src="js/select2.js"></script> <script>function format(team) {if (!team.id) return team.text; return "<img class=\'flag\' src=\'http://medias.lequipe.fr/logo-football/" + team.id.toLowerCase() + "/20\'/>" + team.text; }; $(document).ready(function() { $("#teams").select2({formatResult: format,formatSelection: format}); });</script>';
$ojnTemplate->setJS($js);
?>
