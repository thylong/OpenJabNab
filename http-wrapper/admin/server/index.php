<?php
require_once '../include/common.php';

$ojnTemplate->setTitle(__tr('Server setup'));
$Plugins = $ojnAPI->getListOfPlugins(false, $ojnTemplate->getLanguage());

$reload = false;
if((!empty($_GET['plug']) && !empty($_GET['stat'])) || (!empty($_POST['plug']) && !empty($_POST['stat']))) {
	$a = !empty($_GET['stat']) ? $_GET : $_POST;
	if($a['stat'] == 'activate')
		$function='activate';
	else if($a['stat'] == 'deactivate')
		$function='deactivate';
	else
		$function='reload';
	if(isset($a['plug'],$Plugins))
		Message::AddFromApi($ojnAPI->getApiString('plugins/'.$function.'Plugin?name='.$a['plug'].'&'.$ojnAPI->getToken()));
	else
		Message::AddError(__tr("No plugin with such name."));
	$reload = true;
} else if(!empty($_GET['reloadA'])) {
	Message::AddFromApi($ojnAPI->getApiString('accounts/reloadAccount?login='.urldecode($_GET['reloadA']).'&'.$ojnAPI->getToken()));
	$reload = true;
	header('Location: server.php');
} else if(!empty($_GET['removeA'])) {
	Message::AddFromApi($ojnAPI->getApiString('accounts/removeAccount?login='.urlencode($_GET['removeA']).'&'.$ojnAPI->getToken()));
	$reload = true;
	header('Location: server.php');
} else if(!empty($_GET['removeB'])) {
	Message::AddFromApi($ojnAPI->getApiString('bunnies/removeBunny?serial='.$_GET['removeB'].'&'.$ojnAPI->getToken()));
	$reload = true;
} else if(!empty($_GET['removeZ'])) {
	Message::AddFromApi($ojnAPI->getApiString('ztamps/removeZtamp?serial='.urlencode($_GET['removeZ']).'&'.$ojnAPI->getToken()));
	$reload = true;
	header('Location: server.php');
}
if($reload) {
	header('Location: /admin/server/index.php');
	exit;
}

?>
	      <div class="row">
	      	<div class="span12">
	      		<div class="widget ">
	      			<div class="widget-header">
	      				<i class="icon-cog"></i> <h3><?php echo __tr('Server settings') ?></h3>
	  				</div> <!-- /widget-header -->
					<div class="widget-content">
						<div class="tabbable">
						<ul class="nav nav-tabs">
						  <li class="active"><a href="#plugins" data-toggle="tab"><?php echo __tr('Plugins') ?></a></li>
						  <li><a href="#bunnies" data-toggle="tab"><?php echo __tr('Bunnies') ?></a></li>
						  <li><a href="#ztamps" data-toggle="tab"><?php echo __tr('Ztamps') ?></a></li>
						  <li><a href="#accounts" data-toggle="tab"><?php echo __tr('Accounts') ?></a></li>
						</ul>
						<br />

							<div class="tab-content">
								<div class="tab-pane active" id="plugins">
									<fieldset>
<?php
require_once(ROOT_SITE.'/include/message.php');
?>
<?php
	$Plugins = $ojnAPI->getListOfPlugins(false, $ojnTemplate->getLanguage());
	asort($Plugins);
	$BPlugins = $ojnAPI->getListOfBunnyPlugins(false);
	$ZPlugins = $ojnAPI->getListOfZtampPlugins(false);
	$UPlugins = $BPlugins;
	/* Merge Bunny Plugins and ZTamp Plugins */
	if(!empty($ZPlugins))
		foreach($ZPlugins as $v)
			if(!in_array($v,$BPlugins))
				$BPlugins[] = $v;
	$APlugins = $ojnAPI->getListOfEnabledPlugins(false);
	$SPlugins = $ojnAPI->getListOfSystemPlugins(false);//ApiList("plugins/getListOfSystemPlugins?".$ojnAPI->getToken());
	$RPlugins = $ojnAPI->getListOfRequiredPlugins(false);//ApiList("plugins/getListOfRequiredPlugins?".$ojnAPI->getToken());
?>
<center>
<table class="table table-bordered table-striped span10">
	<thead>
	<tr>
		<th class="span6"><?php echo __tr('Required plugins') ?></th>
		<th colspan="2">Actions</th>
	</tr>
	</thead>
<tbody>
<?php
	$i = 0;
	foreach($Plugins as $p => $name)
	{
		if(in_array($p, $RPlugins))
		{
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><?php echo $name ?></td>
		<td class="span2"><?php if(file_exists("plugins/".$p.".plugin.php")) { ?><a href="server_plugin.php?p=<?php echo $p; ?>" class="btn btn-small btn-primary"><i class="icon-cog icon-large"></i> <?php echo __tr('Setup') ?></a><?php } else { ?>&nbsp;<?php } ?></td>
		<td class="span2"><?php if($Plugins[$p][1]): ?><a class="btn btn-small btn-primary" href="?stat=reload&plug=<?php echo $p ?>"><i class="icon-refresh icon-large"></i> <?php echo __tr('Reload') ?></a><?php endif; ?></td>
	</tr>
<?php
 		}
	}
 ?>
</tbody>
</table>

<table class="table table-bordered table-striped span10">
	<tr>
		<th><?php echo __tr('System plugins') ?></th>
		<th colspan="3"><?php echo __tr('Actions') ?></th>
	</tr>
<?php
	$i = 0;
	foreach($Plugins as $p => $name)
	{
		if(in_array($p, $SPlugins))
		{
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><?php echo $name ?></td>
		<td class="span2"><?php if(file_exists("plugins/".$p.".plugin.php")) { ?><a href="server_plugin.php?p=<?php echo $p; ?>" class="btn btn-small btn-primary"><i class="icon-cog icon-large"></i> <?php echo __tr('Setup') ?></a><?php } else { ?>&nbsp;<?php } ?></td>
		<td class="span2"><a class="btn btn-small btn-<?php echo in_array($p,$APlugins) ? "danger" : "success";?>" href="?stat=<?php echo in_array($p,$APlugins) ? "deactivate" : "activate"; ?>&plug=<?php echo $p ?>"><?php echo in_array($p,$APlugins) ? __tr('Disable plugin') : __tr('Enable plugin') ?></a></td>
		<?php if(in_array($p,$APlugins)): ?><td width="14%"><a href="?stat=reload&plug=<?php echo $p ?>" class="btn btn-small btn-primary"><i class="icon-refresh icon-large"></i> <?php echo __tr('Reload') ?></a></td><?php endif; ?>
	</tr>
<?php
 		}
	}
 ?>
</table>

<table class="table table-bordered table-striped span10">
	<tr>
		<th><?php echo __tr('Bunnies & Ztamps plugins') ?></th>
		<th colspan="3"><?php echo __tr('Actions') ?></th>
	</tr>
<?php
	$i = 0;
	foreach($Plugins as $p => $name)
	{
		if(in_array($p, $UPlugins))
		{
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><?php echo $name; ?></td>
		<td class="span2"><?php if(file_exists("plugins/".$p.".plugin.php")) { ?><a href="server_plugin.php?p=<?php echo $p; ?>" class="btn btn-small btn-primary"><i class="icon-cog icon-large"></i> <?php echo __tr('Setup') ?></a><?php } else { ?>&nbsp;<?php } ?></td>
		<td class="span2"><a class="btn btn-small btn-<?php echo in_array($p,$APlugins) ? "danger" : "success";?>" href="?stat=<?php echo in_array($p,$APlugins) ? "deactivate" : "activate"; ?>&plug=<?php echo $p ?>"><?php echo in_array($p,$APlugins) ? __tr('Disable plugin') : __tr('Enable plugin') ?></a></td>
		<?php if(in_array($p,$APlugins)): ?></td><td width="14%"><a href="?stat=reload&plug=<?php echo $p ?>" class="btn btn-small btn-primary"><i class="icon-refresh icon-large"></i> <?php echo __tr('Reload') ?></a><?php endif; ?>
		</td>
	</tr>
<?php
 		}
	}
 ?>
</table>

</p>
</center>
						</fieldset>
						</div>
								<div class="tab-pane" id="bunnies">
<h3 id="bunnies"><?php echo __tr('List of bunnies') ?></h3>
<center>
<table class="table table-bordered table-striped span10" id="btable">
	<tr>
		<th colspan="4">
<span id="bpages"><button onclick="updateBTable(--bpage)" id="bprev"><i class="icon-backward"></i></button> <span id="bpage"></span><button id="bnext" onclick="updateBTable(++bpage)"><i class="icon-forward"></i></button></span>
<div class="pull-right"><?php echo __tr('Search') ?> <input type="text" id="bsearch" value="" onKeyUp="updateBTable(0);"></div>
		</th>
	</tr>
	<tr>
		<th class="span2"><?php echo __tr('MAC') ?></th>
		<th><?php echo __tr('Name') ?></th>
		<th class="span1"><?php echo __tr('Status') ?></th>
		<th class="span4"><?php echo __tr('Actions') ?></th>
	</tr>
	<tbody>
<?php
/*
	$i = 0;
	$cbunnies = $ojnAPI->getApiMapped("bunnies/getListofAllConnectedBunnies?".$ojnAPI->getToken());
	$bunnies = $ojnAPI->getApiMapped("bunnies/getListofAllBunnies?".$ojnAPI->getToken());
    if(!empty($bunnies))
	foreach($bunnies as $mac=>$name){
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td><?php echo $mac; ?></td>
		<td><?php echo $name; ?></td>
		<td><?php echo isset($cbunnies[$mac]) ? __tr('Connected') : __tr('Disconnected') ?></td>
		<td><a href='/bunny/index.php?b=<?php echo $mac; ?>' class="btn btn-small btn-primary"><i class="icon-cog icon-large"></i> <?php echo __tr('Setup') ?></a>&nbsp;<a class="btn btn-small btn-danger" href='server.php?removeB=<?php echo $mac; ?>'><?php echo __tr('Remove') ?></a></td>
	</tr>
<?php } */?>
	</tbody>
</table>
</center>
						</div>
<script type="text/javascript">
var bpage = 0;
setTimeout("updateBTable(0);", 1000);

function updateBTable(p)
{
	if(p <0)
	p = 0;
     $(".btablerow").remove();
	var url = "/admin/json.php?";
	url += "bpage=" + p;
	if($("#bsearch").val())
		url += "&bsearch=" + $("#bsearch").val();
     $.get(url, {}, function(result) {
	if(p > result.pages -1 )
	p = result.pages -1;
	result = JSON.parse(result);
	$('#bpage').html('Page ' + (result.page+1) + ' sur ' + result.pages + ' ('+ result.total+' bunnies) ');
	$.each(result['data'], function(i, val) {
	$("#btable tr:last").after('<tr class="btablerow"><td>'+i+'</td><td>'+val.name+'</td><td>'+(val.connected ? '<?php echo __tr('Connected') ?>' : '<?php echo __tr('Disconnected') ?>')+'</td><td><a href="/bunny/index.php?b='+i+'" class="btn btn-small btn-primary"><i class="icon-cog icon-large"></i> <?php echo __tr('Setup') ?></a>&nbsp;<a class="btn btn-small btn-danger" href="server.php?removeB='+i+'"><?php echo __tr('Remove') ?></a>&nbsp;<a class="btn btn-small" href="bunny_expert.php?mac='+i+'"><?php echo __tr('Expert') ?></a></td></tr>');
//  	alert(i + " / " + val);

});
//        $("#txtJSON").val(result);
//        $("#jobtable").html(result);
     });
}

</script>
								<div class="tab-pane" id="ztamps">
									<fieldset>
<h1 id="ztamps">Liste des Ztamps</h1>
<p>Voici la liste des ztamps enregistr&eacute;s sur ce serveur.</p>
<center>
<table style="width: 80%">
	<tr>
		<th>ID</th>
		<th>Nom</th>
		<th>Actions</th>
	</tr>
<?php

	$i = 0;
	$Ztamps = $ojnAPI->getListOfAllZtamps(false);

//$fp = fopen('ztamps.csv', 'r+');

    if(!empty($Ztamps))
	foreach($Ztamps as $id=>$name){
		//fputcsv($fp, array($id, $name), ";");

?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td width="20%"><?php echo $id; ?></td>
		<td><?php echo $name; ?></td>
		<td width="20%"><a href='/account/ztamp.php?z=<?php echo $id; ?>'>Configurer</a>&nbsp;
						<a href="?removeZ=<?php echo urlencode($id); ?>"><?php echo __tr('Remove') ?></a>
		</td>
	</tr>
<?php } ?>
<?php //fclose($fp); ?>
</table>
</center>

						</fieldset>
						</div>
								<div class="tab-pane" id="accounts">
									<fieldset>
<h1 id="userlist">Liste des Utilisateurs</h1>
<p>Voici la liste des comptes enregistr&eacute;s sur ce serveur.</p>
<center>
<table style="width: 80%">
	<tr>
		<th>Login</th>
		<th>Username</th>
		<th>Statut</th>
		<th>Actions</th>
	</tr>
<?php
	$i = 0;
	$Users = $ojnAPI->getListOfAllAccounts(false);
	$Online = $ojnAPI->getListOfAllConnectedAccounts(false);
	$Admins = $ojnAPI->getApiList("accounts/GetListOfAdmins?".$ojnAPI->getToken());
    if(!empty($Users))
	foreach($Users as $l=>$name){
?>
	<tr<?php echo $i++ % 2 ? " class='l2'" : "" ?>>
		<td width="20%" <?php echo in_array($l,$Admins) ? 'style="font-weight:bold;"' :''; ?>><?php echo $l; ?></td>
		<td><?php echo $name; ?></td>
		<td width="20%"><?php echo in_array($l,$Online) ? "C":"D&eacute;c"; ?>onnect&eacute;</td>
		<td><a href="?removeA=<?php echo urlencode($l); ?>"><?php echo __tr('Remove') ?></a> &nbsp;<a href="?reloadA=<?php echo urlencode($l); ?>"><?php echo __tr('Reload') ?></a>&nbsp;<a href="account_expert.php?acc=<?php echo urlencode($l); ?>"><?php echo __tr('Expert') ?></a></td>
	</tr>
<?php } ?>
</table>
</center>
						</fieldset>
						</div>

						</div>
						</div>
						</div>
						</div>
						</div>
						</div>
<?php
require_once '../include/append.php'
?>
