<?php
require_once "include/common.php";
if(!isset($_SESSION['token']) || !$Infos['isAdmin'])
	header('Location: index.php');

$version = 2;

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
    die('Connexion impossible : ' . mysqli_error());
}
$max = 50;
include('encode.functions.php');

$sql = "SELECT * FROM bunny ";
if(isset($_GET['mac']))
{
	$sql .= "WHERE mac='".$_GET['mac']."'";
}
else
{
	Message::AddError(__tr("No bunny selected"));
	header("Location: index.php");
	exit();
}
$bunny = array();
$res = mysqli_query($link, $sql);
if($row = mysqli_fetch_assoc($res))
{
	$bunny = $row;
}
else
{
	Message::AddError(__tr("Bunny not found"));
	header("Location: index.php");
	exit();
}
include('bunny_expert.decode.php');
require('../include/message.php');
?>
<?php
$pattern = "|[\w@\"'_\-,;.:!\? ]|";
?>
<div class="row">

	<div class="col-md-6">
		<div class="card">
			<h5 class="card-header">
				<i class="icon-search"></i> <?php echo __tr('Bunny').' '.$bunny['mac'] ?>
			</h5>
			<div class="card-body text-center">
			</div>
		</div>
	</div>

	<div class="col-md-6">
		<div class="card">
			<h5 class="card-header">
				<i class="icon-cog"></i> <?php echo __tr('Database raw data') ?>
			</h5>
			<div class="card-body text-center">
				<pre><?php
					$add = 0;
					foreach(str_split($bunny['settings'], 16) as $i => $line)
					{
						$hex = $str = "";
						foreach(str_split($line) as $k => $chr)
						{
							$hex .= str_pad(dechex(ord($chr)), 2, "0", STR_PAD_LEFT)." ";
							if($k == 7)
								$hex .= "  ";
							$str .= preg_match($pattern, $chr) ? $chr : ".";
						}
						echo "0x".str_pad(str_pad(dechex($add), 4, "0", STR_PAD_LEFT)."    ".$hex, 58," ")."   ".$str."<br />";
						$add += 16;
					}
					?>
				</pre>
			</div>
		</div>
	</div>
</div>
<?php
require_once "include/append.php";
?>
