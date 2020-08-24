<?php
require_once "include/common.php";
if(!isset($_SESSION['token']))
	header('Location: index.php');
$ojnTemplate->setTitle(__tr('Silent bunny'));

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
    die('Connexion impossible : ' . mysqli_error());
}

//Message::AddSuccess(__tr("Your mail has been sent to administrator"));
//Message::AddError(__tr("An error occured"), 0);
//Message::AddWarning(__tr("No bunny specified"), 0);


if(isset($_GET['id']) && isset($_GET['s']))
{
	$sql = "UPDATE silent_report SET sound='".$_GET['s']."' WHERE id='".$_GET['id']."';";
	$res = mysqli_query($link, $sql);
//	Message::AddSuccess(__tr(""));
	header('Location: silent.php?b=' . $_GET['b']);
}

require('include/message.php');
$sounds = array();

if(isset($_GET['b']))
{
	$sql = "SELECT * FROM silent_report WHERE mac='".$_GET['b']."' AND sound IS NULL AND type IN ('MU', 'ST') ORDER BY date ASC";
	$res = mysqli_query($link, $sql);
	while($row = mysqli_fetch_assoc($res))
	{
		$sounds[] = $row;
	}
}
mysqli_close($link);

?>
<div class="row">
    <div class="span12">
        <div class="widget widget">
            <div class="widget-header">
                <i class="icon-list-alt"></i><h3><?php echo __tr("Silent bunny") ?></h3>
            </div> 
            <div class="widget-content">
<table class="table table-bordered table-striped span10">
	<tr>
		<th><?php echo __tr('File') ?></th>
		<th><?php echo __tr('Date') ?></th>
		<th colspan="2"><?php echo __tr('Actions') ?></th>
	</tr>
<?php foreach(array_slice($sounds, max(0, count($sounds) - 10), 10) as $sound): ?>
	<tr>
		<td>
			<object type="application/x-shockwave-flash" data="/media/player_mp3.swf" width="200" height="20">
			     <param name="movie" value="/media/player_mp3.swf" />
			     <param name="FlashVars" value="loadingcolor=0074CC&slidercolor1=0088CC&slidercolor2=0055CC&sliderovercolor=0074CC&buttonovercolor=0074CC&mp3=<?php echo $sound['file'] ?>" />
			</object>
		</td>
		<td><?php echo $sound['date'] ?></td>
		<td colspan="2">
			<a class="btn btn-mini btn-danger" href="silent.php?b=<?php echo $_GET['b'] ?>&id=<?php echo $sound['id'] ?>&s=0"><?php echo __tr("I didn't heard this sound") ?></a>
			<a class="btn btn-mini btn-success" href="silent.php?b=<?php echo $_GET['b'] ?>&id=<?php echo $sound['id'] ?>&s=1"><?php echo __tr("I heard this sound") ?></a>
			<a class="btn btn-mini btn-primary" href="silent.php?b=<?php echo $_GET['b'] ?>&id=<?php echo $sound['id'] ?>&s=2"><?php echo __tr("I don't know, I wasn't with my bunny") ?></a>
		</td>
	</tr>
<?php endforeach; ?>
</table>


            </div> 
        </div>
<?php
require_once "include/append.php";
?>
