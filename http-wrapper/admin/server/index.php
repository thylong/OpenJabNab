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

if(!empty($_GET['tab']))
{
	$_SESSION['tab'] = 'server_'.$_GET['tab'];
	$reload = true;
}
elseif(!isset($_SESSION['tab']) || !preg_match("|^server_|", $_SESSION['tab']))
	$_SESSION['tab'] = 'server_plugins';

$tab = $_SESSION['tab'];

if($reload) {
	header('Location: /admin/server/index.php');
	exit;
}

require_once(ROOT_SITE.'/include/message.php'); ?>
<div class="card">
  <h5 class="card-header">
    <i class="icon-cog"></i> <?php echo __tr('Server settings') ?>
  </h5>
  <div class="card-body">
    <ul class="nav nav-tabs">
      <li class="nav-item"><a class="nav-link<?php echo $tab == 'server_plugins' ? ' active' : ''?>" href="#plugins" data-toggle="tab"><?php echo __tr('Plugins') ?></a></li>
      <li class="nav-item"><a class="nav-link<?php echo $tab == 'server_bunnies' ? ' active' : ''?>" href="#bunnies" data-toggle="tab"><?php echo __tr('Bunnies') ?></a></li>
      <li class="nav-item"><a class="nav-link<?php echo $tab == 'server_ztamps' ? ' active' : ''?>" href="#ztamps" data-toggle="tab"><?php echo __tr('Ztamps') ?></a></li>
      <li class="nav-item"><a class="nav-link<?php echo $tab == 'server_accounts' ? ' active' : ''?>" href="#accounts" data-toggle="tab"><?php echo __tr('Accounts') ?></a></li>
    </ul>
		<div class="tab-content pt-4">
      <div class="tab-pane <?php echo $tab == 'server_plugins' ? ' active' : ''?>" id="plugins">
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
      <table class="table table-bordered table-striped">
	      <thead>
	        <tr>
            <th class="col-sm-7"><?php echo __tr('Required plugins') ?></th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          foreach($Plugins as $p => $name):
            if(!in_array($p, $RPlugins))
              continue;
          ?>
          <tr>
            <td><?php echo $name ?></td>
            <td class="text-right">
              <?php if(file_exists("plugins/".$p.".plugin.php")) { ?><a href="server_plugin.php?p=<?php echo $p; ?>" class="btn btn-sm btn-primary"><i class="icon-cog icon-large"></i> <?php echo __tr('Setup') ?></a><?php } ?>
              <?php if($Plugins[$p][1]): ?><a class="btn btn-sm btn-warning" href="?stat=reload&plug=<?php echo $p ?>"><i class="icon-refresh icon-large"></i> <?php echo __tr('Reload') ?></a><?php endif; ?>
            </td>
          </tr>
            <?php endforeach; ?>
        </tbody>
      </table>

      <table class="table table-bordered table-striped">
	      <tr>
		      <th class="col-sm-7"><?php echo __tr('System plugins') ?></th>
		      <th><?php echo __tr('Actions') ?></th>
	      </tr>
        <?php
        foreach($Plugins as $p => $name):
          if(!in_array($p, $SPlugins))
            continue;
        ?>
	      <tr>
		      <td><?php echo $name ?></td>
		      <td class="text-right">
            <?php if(file_exists("plugins/".$p.".plugin.php")) { ?><a href="server_plugin.php?p=<?php echo $p; ?>" class="btn btn-sm btn-primary"><i class="icon-cog icon-large"></i> <?php echo __tr('Setup') ?></a><?php } ?>
		        <a class="btn btn-sm btn-<?php echo in_array($p,$APlugins) ? "danger" : "success";?>" href="?stat=<?php echo in_array($p,$APlugins) ? "deactivate" : "activate"; ?>&plug=<?php echo $p ?>"><?php echo in_array($p,$APlugins) ? __tr('Disable plugin') : __tr('Enable plugin') ?></a>
		        <?php if(in_array($p,$APlugins)): ?><a href="?stat=reload&plug=<?php echo $p ?>" class="btn btn-sm btn-warning"><i class="icon-refresh icon-large"></i> <?php echo __tr('Reload') ?></a><?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </table>

      <table class="table table-bordered table-striped">
	      <tr>
		      <th class="col-sm-7"><?php echo __tr('Bunnies & Ztamps plugins') ?></th>
		      <th><?php echo __tr('Actions') ?></th>
	      </tr>
        <?php
	      foreach($Plugins as $p => $name):
          if(!in_array($p, $UPlugins))
            continue;
        ?>
        <tr>
          <td><?php echo $name; ?></td>
          <td class="text-right">
            <?php if(file_exists("plugins/".$p.".plugin.php")) { ?><a href="server_plugin.php?p=<?php echo $p; ?>" class="btn btn-sm btn-primary"><i class="icon-cog icon-large"></i> <?php echo __tr('Setup') ?></a><?php } ?>
            <a class="btn btn-sm btn-<?php echo in_array($p,$APlugins) ? "danger" : "success";?>" href="?stat=<?php echo in_array($p,$APlugins) ? "deactivate" : "activate"; ?>&plug=<?php echo $p ?>"><?php echo in_array($p,$APlugins) ? __tr('Disable plugin') : __tr('Enable plugin') ?></a>
            <?php if(in_array($p,$APlugins)): ?><a href="?stat=reload&plug=<?php echo $p ?>" class="btn btn-sm btn-warning"><i class="icon-refresh icon-large"></i> <?php echo __tr('Reload') ?></a><?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </table>
		</div>

    <div class="tab-pane<?php echo $tab == 'server_bunnies' ? ' active' : ''?>" id="bunnies">
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
		<td><a href='/bunny/index.php?b=<?php echo $mac; ?>' class="btn btn-sm btn-primary"><i class="icon-cog icon-large"></i> <?php echo __tr('Setup') ?></a>&nbsp;<a class="btn btn-sm btn-danger" href='server.php?removeB=<?php echo $mac; ?>'><?php echo __tr('Remove') ?></a></td>
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
	$("#btable tr:last").after('<tr class="btablerow"><td>'+i+'</td><td>'+val.name+'</td><td>'+(val.connected ? '<?php echo __tr('Connected') ?>' : '<?php echo __tr('Disconnected') ?>')+'</td><td><a href="/bunny/index.php?b='+i+'" class="btn btn-sm btn-primary"><i class="icon-cog icon-large"></i> <?php echo __tr('Setup') ?></a>&nbsp;<a class="btn btn-sm btn-warning" href="bunny_expert.php?mac='+i+'"><?php echo __tr('Expert') ?></a>&nbsp;<a class="btn btn-sm btn-danger" href="server.php?removeB='+i+'"><?php echo __tr('Remove') ?></a></td></tr>');
//  	alert(i + " / " + val);

});
//        $("#txtJSON").val(result);
//        $("#jobtable").html(result);
     });
}

</script>
								<div class="tab-pane<?php echo $tab == 'server_ztamps' ? ' active' : ''?>" id="ztamps">
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
								<div class="tab-pane<?php echo $tab == 'server_accounts' ? ' active' : ''?>" id="accounts">
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
		<td>
			<!--a target="_blank" class="btn btn-sm btn-primary" href="/account/index.php?a=<?php echo $l ?>"><i class="icon-large icon-cog"></i> <?php echo __tr('Manage account') ?></a-->
			<!--a target="_blank" class="btn btn-sm btn-success" href="/index.php?logid=<?php echo $l ?>"><i class="icon-large icon-user"></i> <?php echo __tr('Connect') ?></a-->
			<a class="btn btn-sm btn-primary" href="?reloadA=<?php echo urlencode($l); ?>"><i class="icon-refresh"></i> <?php echo __tr('Reload') ?></a> 
			<a class="btn btn-sm btn-warning" target="_blank" href="/admin/account/account_expert.php?acc=<?php echo urlencode($l); ?>"><i class="icon-search"></i> <?php echo __tr('Expert') ?></a> 
			<a class="btn btn-sm btn-danger" href="?removeA=<?php echo urlencode($l); ?>"><i class="icon-trash"></i> <?php echo __tr('Remove') ?></a>
		</td>
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
