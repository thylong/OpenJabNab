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
require('include/message.php');

?>
	      <div class="row">
<?php /*
	      	<div class="span5">      		
	      		<div class="widget ">
	      			<div class="widget-header">
	      				<i class="icon-user"></i>
	      				<h3><?php echo __tr('Bunny %1, cleaned data', $bunny['username']) ?></h3>
	  				</div> <!-- /widget-header -->
					<div class="widget-content">
						
								<form id="edit-profile" class="form-horizontal" method="post">
									<fieldset>
										<div class="control-group">											
											<label class="control-label" for="npwd2"><?php echo __tr('ID') ?></label>
											<div class="controls">
												<input type="text" class="input-medium disabled" id="username" value="<?php echo $bunny['id'] ?>" disabled>
											</div> <!-- /controls -->				
										</div> <!-- /control-group -->

										<div class="control-group">	
											<label class="control-label" for="username"><?php echo __tr('Login') ?></label>
											<div class="controls">
												<input type="text" class="input-medium disabled" id="username" value="<?php echo $login ?>" disabled>
											</div> <!-- /controls -->				
										</div> <!-- /control-group -->
										
										<div class="control-group">											
											<label class="control-label" for="firstname"><?php echo __tr('Display name') ?></label>
											<div class="controls">
												<input disabled type="text" class="input-medium" name="displayname" value="<?php echo $username ?>">
											</div> <!-- /controls -->				
										</div> <!-- /control-group -->
										<div class="control-group">											
											<label class="control-label" for="firstname"><?php echo __tr('Language') ?></label>
											<div class="controls">
												<input disabled type="text" class="input-medium" name="language" value="<?php echo $language ?>">
											</div> <!-- /controls -->				
										</div> <!-- /control-group -->
										
										
										<div class="control-group">
											<label class="control-label" for="email"><?php echo __tr('Email address') ?></label>
											<div class="controls">
												<input disabled type="text" class="input-large" name="email" value="<?php echo $email ?>">
											</div> <!-- /controls -->				
										</div> <!-- /control-group -->
          <div class="control-group">
            <label for="optionsCheckbox" class="control-label"><?php echo __tr("Status") ?></label>
            <div class="controls">
              <label class="checkbox">
		<input type="radio" name="admin" value="1" <?php echo $admin ? 'checked="checked"' : ''; ?> disabled/> <?php echo __tr("Administrator") ?><br />
              </label>
              <label class="checkbox">
		<input type="radio" name="admin" value="0" <?php echo !$admin ? 'checked="checked"' : ''; ?> disabled /> <?php echo __tr("User") ?>
              </label>
            </div>
          </div>
										
										<div class="control-group">											
											<label class="control-label" for="npwd"><?php echo __tr('Password') ?></label>
											<div class="controls">
												<input type="text" disabled class="input-medium" name="npwd" value="">
											</div> <!-- /controls -->				
										</div> <!-- /control-group -->
										
										<div class="form-actions">
											<input type="hidden" name="update" value="go">
											<button type="submit" class="btn btn-primary"><?php echo __tr('Update and reload') ?></button> 
											<button class="btn"><?php echo __tr('Cancel') ?></button>
										</div> <!-- /form-actions -->
									</fieldset>
								</form>
					</div> <!-- /widget-content -->
				</div> <!-- /widget -->
		    </div> <!-- /span5 -->
<?php
$settings = "";
$settings .= echoInt($version);
$settings .= echoString($login);
$settings .= echoString($username);
$settings .= echoStr($passwordhash);
$settings .= echoString($language);
$settings .= echoString($email);
$settings .= echoBool($admin);
$settings .= echoArrayInt(array(1,3,3,3,3,3,1,0));
$settings .= echoArrayStr($bunnies);
$settings .= echoArrayStr($ztamps);

*/
$pattern = "|[\w@\"'_\-,;.:!\? ]|";
?>
	      	<div class="span7">
				<div class="widget widget-box">
					<div class="widget-header">
	      				<h3><?php echo __tr('Database raw data') ?></h3>
	  				</div> <!-- /widget-header -->
					<div class="widget-content">
<pre>
<?php 
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
							

						
					</div> <!-- /widget-content -->
				</div> <!-- /widget-box -->
		      </div> <!-- /span4 -->
	      </div> <!-- /row -->
<?php
require_once "include/append.php";
?>
