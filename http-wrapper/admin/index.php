<?php
require_once('../include/common.php');
if(isset($_GET['clear_cache']))
{
  apcu_clear_cache();
  Message::AddSuccess(__tr("PHP cache cleared"));
}
header('Location: /admin/server/index.php');
require_once('../include/append.php');
?>
