<?php
require_once realpath(dirname(__FILE__).'/..').'/include/config.php';

if(!empty($_SERVER['DOCUMENT_ROOT']))
        die('HTTP use is forbidden');

function getAPI()
{
    require_once(ROOT_SITE.'class/api.class.php');
    $ojnAPI = new ojnApi();
    $r = $ojnAPI->loginAccount(CRON_API_USER, CRON_API_PWD, false);
    $ojnAPI->setToken($r);
    return $ojnAPI;
}

function getSQL()
{
    $link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$link) {
        die('Connexion impossible : ' . mysqli_error());
    }
    return $link;
}
