<?php
if(!empty($_SERVER['DOCUMENT_ROOT']) && !empty($_GET['http_cron']))
    die('HTTP use is forbidden');

require_once realpath(dirname(__FILE__).'/..').'/include/tools.inc.php';

function getAPI()
{
    require_once(ROOT_SITE.'include/class/api.class.php');
    $ojnAPI = new ojnApi();
    $r = $ojnAPI->loginAccount(CRON_API_USER, CRON_API_PWD, false);
    $ojnAPI->setToken($r);
    return $ojnAPI;
}
