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

$sql = "SELECT account.*, SUM(value) AS don FROM account LEFT JOIN don ON don.username=account.username ";
if(isset($_GET['accid']))
{
	$sql .= "WHERE account.id='".$_GET['accid']."'";
}
else if(isset($_GET['acc']))
{
	$sql .= "WHERE account.username=\"".$_GET['acc']."\"";
}
else
{
	Message::AddError(__tr("No account selected"));
	header("Location: index.php");
	exit();
}
$account = array();
$res = mysqli_query($link, $sql);
if($row = mysqli_fetch_assoc($res))
{
	$account = $row;
}
else
{
	Message::AddError(__tr("Account not found"));
	header("Location: index.php");
	exit();
}
include('account_expert.decode.php');
require('../include/message.php');

?>
	      <div class="row">
	      	<div class="span5">
	      		<div class="widget ">
	      			<div class="widget-header">
	      				<i class="icon-user"></i>
	      				<h3><?php echo __tr('Account %1, cleaned data', $account['username']) ?></h3>
	  				</div> <!-- /widget-header -->
					<div class="widget-content">

								<form id="edit-profile" class="form-horizontal" method="post">
									<fieldset>
										<div class="control-group">
											<label class="control-label" for="npwd2"><?php echo __tr('ID') ?></label>
											<div class="controls">
												<input type="text" class="input-medium disabled" id="username" value="<?php echo $account['id'] ?>" disabled>
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
		    <label for="optionsCheckbox" class="control-label"><?php echo __tr("Premium") ?></label>
		    <div class="controls">
		      <label class="checkbox">
			<input type="radio" name="premium" value="1" <?php echo $premium ? 'checked="checked"' : ''; ?> disabled/> <?php echo __tr("Yes") ?><br />
		      </label>
		      <label class="checkbox">
			<input type="radio" name="premium" value="0" <?php echo !$premium ? 'checked="checked"' : ''; ?> disabled /> <?php echo __tr("No") ?>
		      </label>
		    </div>
		  </div>
		  <div class="control-group">
		    <label for="optionsCheckbox" class="control-label"><?php echo __tr("VIP") ?></label>
		    <div class="controls">
		      <label class="checkbox">
			<input type="radio" name="vip" value="1" <?php echo $vip ? 'checked="checked"' : ''; ?> disabled/> <?php echo __tr("Yes") ?><br />
		      </label>
		      <label class="checkbox">
			<input type="radio" name="vip" value="0" <?php echo !$vip ? 'checked="checked"' : ''; ?> disabled /> <?php echo __tr("No") ?>
		      </label>
		    </div>
		  </div>
										<div class="control-group">
											<label class="control-label" for="npwd"><?php echo __tr('Number of login') ?></label>
											<div class="controls">
												<input type="text" disabled class="input-medium" name="" value="<?php echo $logincount ?>">
											</div> <!-- /controls -->
										</div> <!-- /control-group -->
										<div class="control-group">
											<label class="control-label" for="npwd"><?php echo __tr('Last login') ?></label>
											<div class="controls">
												<input type="text" disabled class="input-medium" name="" value="<?php echo date("d/m/Y H:i:s", $lastlogin) ?>">
											</div> <!-- /controls -->
										</div> <!-- /control-group -->
										<div class="control-group">
											<label class="control-label" for="npwd"><?php echo __tr('Number of abuses') ?></label>
											<div class="controls">
												<input type="text" disabled class="input-medium" name="" value="<?php echo $abusecount ?>">
											</div> <!-- /controls -->
										</div> <!-- /control-group -->
										<div class="control-group">
											<label class="control-label" for="npwd"><?php echo __tr('Last ban') ?></label>
											<div class="controls">
												<input type="text" disabled class="input-medium" name="" value="<?php echo date("d/m/Y H:i:s", $ban) ?>">
											</div> <!-- /controls -->
										</div> <!-- /control-group -->

										<div class="control-group">
											<label class="control-label" for="npwd"><?php echo __tr('Password') ?></label>
											<div class="controls">
												<input type="text" disabled class="input-medium" name="npwd" value="">
											</div> <!-- /controls -->
										</div> <!-- /control-group -->
										<div class="control-group">
											<label class="control-label" for="npwd"><?php echo __tr('Status') ?></label>
											<div class="controls">
												<input type="text" disabled class="input-medium" name="" value="<?php echo __tr($account['status']) ?>">
											</div> <!-- /controls -->
										</div> <!-- /control-group -->
										<div class="control-group">
											<label class="control-label" for="npwd"><?php echo __tr('IP address') ?></label>
											<div class="controls">
												<input type="text" disabled class="input-medium" name="" value="<?php echo $account['lastip'] ?>">
											</div> <!-- /controls -->
										</div> <!-- /control-group -->
										<div class="control-group">
											<label class="control-label" for="npwd"><?php echo __tr('Donation') ?></label>
											<div class="controls">
												<input type="text" disabled class="input-medium" name="" value="<?php echo 0+$account['don'] ?> &euro;">
											</div> <!-- /controls -->
										</div> <!-- /control-group -->
<table class="table table-bordered table-striped">
<tr>
	<th><?php echo __tr('Bunnies (%1)', count($bunnies)) ?>
	<th><?php echo __tr("Actions") ?></th>
</tr>
<?php if(count($bunnies)): ?>
<?php foreach($bunnies as $mac): ?>
<tr><td><?php echo $mac ?></td><td><a class="btn btn-small" href="bunny_expert.php?mac=<?php echo $mac ?>"><?php echo __tr('Expert') ?></a>&nbsp;<a class="btn btn-small" href="/bunny/index.php?b=<?php echo $mac ?>"><?php echo __tr('View') ?></a></td></tr>
<?php endforeach; ?>
<?php else: ?>
<tr><td><?php echo __tr('No bunny in this account') ?></td></tr>
<?php endif; ?>
	</table>
<br />
<table class="table table-bordered table-striped">
<tr>
	<th><?php echo __tr('Ztamps (%1)', count($ztamps)) ?>
</tr>
<?php if(count($ztamps)): ?>
<?php foreach($ztamps as $mac): ?>
<tr><td><?php echo $mac ?></td></tr>
<?php endforeach; ?>
<?php else: ?>
<tr><td><?php echo __tr('No ztamp in this account') ?></td></tr>
<?php endif; ?>
	</table>


										<div class="form-actions">
											<input type="hidden" name="update" value="go">
											<button type="submit" class="btn btn-primary"><?php echo __tr('Update and reload') ?></button>
											<a target="_blank" class="btn btn-small btn-danger" href="server.php?removeA=<?php echo urlencode($account['username']) ?>"><?php echo __tr('Remove account') ?></a>
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
$settings .= echoBool($premium);
$settings .= echoBool($vip);
$settings .= echoInt($logincount);
$settings .= echoInt($lastlogindate);
$settings .= echoInt($lastlogintime);
$settings .= echoBool($lastloginformat);
$settings .= echoInt($abusecount);
$settings .= echoInt($bandate);
$settings .= echoInt($bantime);
$settings .= echoBool($banformat);
$settings .= echoArrayInt(array(1,3,3,3,3,3,1,0));
$settings .= echoArrayStr($bunnies);
$settings .= echoArrayStr($ztamps);

if(count($_POST))
{
	$sql = "UPDATE account SET settings='".addslashes($settings)."' where id=".$account['id'];
	$res = mysqli_query($link, $sql);
	if($res)
	{
		Message::AddSuccess(__tr("Account data updated"));
		Message::AddFromApi($ojnAPI->getApiString('accounts/reloadAccount?login='.$login.'&'.$ojnAPI->getToken()));
	}
	else
	{
		Message::AddError(__tr("Account data can't be updated"));
		Message::AddError(mysqli_error());
	}
    header('Location: account_expert.php?accid=' . $account['id']);
    exit;
}

?>
	      	<div class="span7">
				<div class="widget widget-box">
					<div class="widget-header">
	      				<h3><?php echo __tr('Cleaned raw data') ?></h3>
	  				</div> <!-- /widget-header -->
					<div class="widget-content">
<pre>
<?php
$lines = str_split($settings, 16);
$add = 0;
$pattern = "|[\w@\"'_\-,;.:!\?]|";
$offset = 0;
function changecolor($color = '', $close = true)
{
	return ($close ? "</span>" : "").($color != '' ? "<span style='background: #".$color."'>" : "");
}
foreach($lines as $i => $line)
{
	$hex = $str = "";
//	$hex = changecolor('915B40', false);
//	$str = changecolor('915B40', false);
	foreach(str_split($line) as $k => $chr)
	{
/*
		if($offset == 4)
		{
			$hex .= changecolor('C72828');
			$str .= changecolor('C72828');
		}
		if($offset == 7)
			$hex .= "</span>";
*/
		$hex .= str_pad(dechex(ord($chr)), 2, "0", STR_PAD_LEFT)." ";
		if($k == 7)
			$hex .= "  ";
		$str .= preg_match($pattern, $chr) ? $chr : ".";
		$offset++;
	}
	echo "0x".str_pad(str_pad(dechex($add), 4, "0", STR_PAD_LEFT)."    ".$hex, 58," ")."   ".$str."<br />";
	$add += 16;
}
?>
</pre>



					</div> <!-- /widget-content -->
				</div> <!-- /widget-box -->

				<div class="widget widget-box">
					<div class="widget-header">
	      				<h3><?php echo __tr('Database raw data') ?></h3>
	  				</div> <!-- /widget-header -->
					<div class="widget-content">
<pre>
<?php
$add = 0;
foreach(str_split($account['settings'], 16) as $i => $line)
{
	$hex = $str = "";
	foreach(str_split($line) as $k => $chr)
	{
		$hex .= str_pad(dechex(ord($chr)), 2, "0", STR_PAD_LEFT)." ";
		if($k == 7)
			$hex .= "  ";
		$str .= preg_match($pattern, $chr) ? $chr : ".";
		$offset++;
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
