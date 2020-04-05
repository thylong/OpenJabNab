<?php
require_once 'common.php';

$ojnAPI = getAPI();
$vips_server = $ojnAPI->getApiList('accounts/GetListOfVips?'.$ojnAPI->getToken());

include('../donate/update_status.inc.php');

$vips_bdd = array_keys($vip);
$remove = array_diff($vips_server, $vips_bdd);
$need = array_diff($vips_bdd, $vips_server);

foreach($need as $user)
{
	echo('User '.$user.' wins VIP status'."\n");
	$ret = $ojnAPI->getApiString('accounts/setvip?user='.$user.'&vip=true&'.$ojnAPI->getToken());
}
foreach($remove as $user)
{
	echo('User '.$user.' looses VIP status'."\n");
	$ret = $ojnAPI->getApiString('accounts/setvip?user='.$user.'&vip=false&'.$ojnAPI->getToken());
}

?>
