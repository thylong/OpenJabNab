<?php
require_once "include/common.php";
if(!isset($_SESSION['token']))
	header('Location: index.php');
if(count($_POST)) {
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
	    die('Connexion impossible : ' . mysqli_error());
	}
	
	$sql = 'INSERT INTO demo SET username="'.addslashes($_SESSION['login']).'", date=NOW();';
	$res = mysqli_query($link, $sql);
	if($res)
		Message::AddSuccess(__tr("You can now try all premium plugins"));
	else
		Message::AddError(__tr("You already have subscribe to the demo"));

	mysqli_close($link);

	include('include/update_status.inc.php');

	header('Location: demo.php');
}
require('include/message.php');
?>
<div class="row">
    <div class="span12">
        <div class="widget widget">
            <div class="widget-header">
                <i class="icon-list-alt"></i><h3><?php echo __tr("Try premium plugins") ?></h3>
            </div> 
            <div class="widget-content">
		<form class="form-horizontal" method="post">
			<div class="control-group">											
<?php echo __tr("You can try all premium plugins for one week if you want to know how they work"); ?>.
<br />
<span class="label label-warning"><?php echo __tr('Warning') ?></span> <?php echo __tr("You can only ask for a demo one time"); ?>.
			</div> <!-- /control-group -->
			<div class="form-actions">
				<button type="submit" class="btn btn-primary" name="ask"><?php echo __tr('Ask for demo') ?></button> 
			</div> <!-- /form-actions -->
		</form>
            </div> 
        </div>
<?php

require_once "include/append.php";
?>
