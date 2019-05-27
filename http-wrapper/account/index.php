<?php
require_once '../include/common.php';
if(!isset($_SESSION['token']))
	header('Location: /');

$reload = false;

if(!empty($_POST['bmac'])) {
    $mac = str_replace(':','',strtolower($_POST['bmac']));
    if(strlen($mac) == 12 && ctype_xdigit($mac)) {
        if(empty($_POST['bname'])) {
	    $_POST['bname'] = "Nabaztag" . strtoupper(substr($mac, 10, 2));
        }
        $_SESSION['tab'] = 'account_bunnies';
	$output = $ojnAPI->getApiString('accounts/addBunny?login='.urlencode($_SESSION['login']).'&bunnyid='.$mac.'&'.$ojnAPI->getToken());
	$id = Message::AddFromApi($output);
	if($output['ok']) {
		Message::AddFromApi($ojnAPI->getApiString("bunny/".$mac."/setBunnyName?name=".urlencode($_POST['bname'])."&".$ojnAPI->getToken()), $id);
	} else {
		Message::AddFromApi($ojnAPI->getApiString("plugin/reset/reset?action=free&bunny=".$mac."&".$ojnAPI->getToken()), $id);
	}
    } else
	Message::AddError(__tr('Please enter a valid MAC address'));
    $reload = true;
}

if(!empty($_POST['bmac_rm'])) {
    $_SESSION['tab'] = 'account_bunnies';
    Message::AddFromApi($ojnAPI->getApiString('accounts/removeBunny?login='.urlencode($_SESSION['login']).'&bunnyid='.$_POST['bmac_rm'].'&'.$ojnAPI->getToken()));
    $reload = true;
}

if(!empty($_POST['displayname'])) {
    apcu_delete(APC_PREFIX.'ojn_user_'.$_SESSION['login']);
    $_SESSION['tab'] = 'account_profile';
    Message::AddFromApi($ojnAPI->getApiString('accounts/changeUsername?login='.urlencode($_SESSION['login']).'&username='.urlencode(trim($_POST['displayname'])).'&'.$ojnAPI->getToken()));
    $reload = true;
}

if(!empty($_POST['email'])) {
    apcu_delete(APC_PREFIX.'ojn_user_'.$_SESSION['login']);
    $_SESSION['tab'] = 'account_profile';
    Message::AddFromApi($ojnAPI->getApiString('accounts/setemail?login='.urlencode($_SESSION['login']).'&email='.urlencode(trim($_POST['email'])).'&'.$ojnAPI->getToken()));
    $reload = true;
}

if(!empty($_POST['lng'])) {
    apcu_delete(APC_PREFIX.'ojn_user_'.$_SESSION['login']);
    $_SESSION['tab'] = 'account_settings';
    Message::AddFromApi($ojnAPI->getApiString('accounts/setlanguage?login='.urlencode($_SESSION['login']).'&lng='.$_POST['lng'].'&'.$ojnAPI->getToken()));
    $reload = true;
}

if(!empty($_POST['zid_rm'])) {
    $_SESSION['tab'] = 'account_ztamps';
    Message::AddFromApi($ojnAPI->getApiString('accounts/removeZtamp?login='.urlencode($_SESSION['login']).'&zid='.$_POST['zid_rm'].'&'.$ojnAPI->getToken()));
    $reload = true;
}

if(!empty($_POST['npwd']) && !empty($_POST['npwd2'])) {
    $_SESSION['tab'] = 'account_profile';
    if($_POST['npwd'] == $_POST['npwd2']) {
        Message::AddFromApi($ojnAPI->getApiString('accounts/changePassword?login='.$_SESSION['login'].'&pass='.$_POST['npwd'].'&'.$ojnAPI->getToken()));
    } else
        Message::AddError(__tr("Passwords mismatch. Try again"));
    $reload = true;
}
if(!isset($_SESSION['tab']) || !preg_match("|^account_|", $_SESSION['tab']))
	$_SESSION['tab'] = 'account_profile';

if($reload) {
    header('Location: /account/index.php');
    exit;
}

require('../include/message.php');
?>
	      <div class="row">
	      	<div class="span12">
	      		<div class="widget ">
	      			<div class="widget-header">
	      				<i class="icon-user"></i>
	      				<h3><?php echo __tr('Your account') ?></h3>
	  				</div> <!-- /widget-header -->
					<div class="widget-content">

						<div class="tabbable">
						<ul class="nav nav-tabs">
						  <li<?php echo $_SESSION['tab'] == 'account_profile' ? ' class="active"' : '' ?>><a href="#profile" data-toggle="tab"><?php echo __tr('Profile') ?></a></li>
						  <li<?php echo $_SESSION['tab'] == 'account_bunnies' ? ' class="active"' : '' ?>><a href="#bunnies" data-toggle="tab"><?php echo __tr('Bunnies') ?></a></li>
						  <li<?php echo $_SESSION['tab'] == 'account_ztamps' ? ' class="active"' : '' ?>><a href="#ztamps" data-toggle="tab"><?php echo __tr('Ztamps') ?></a></li>
						  <li<?php echo $_SESSION['tab'] == 'account_settings' ? ' class="active"' : '' ?>><a href="#settings" data-toggle="tab"><?php echo __tr('Settings') ?></a></li>
						</ul>
						<br />

							<div class="tab-content">
								<div class="tab-pane<?php echo $_SESSION['tab'] == 'account_profile' ? ' active' : '' ?>" id="profile">
								<form id="edit-profile" class="form-horizontal" method="post">
									<fieldset>
										<div class="control-group">
											<label class="control-label" for="username"><?php echo __tr('Login') ?></label>
											<div class="controls">
												<input type="text" class="input-medium disabled" id="username" value="<?php echo $_SESSION['login'] ?>" disabled>
												<p class="help-block"><?php echo __tr('Your login cannot be changed.') ?></p>
											</div> <!-- /controls -->
										</div> <!-- /control-group -->
										<div class="control-group">
											<label class="control-label" for="username"><?php echo __tr('Status') ?></label>
											<div class="controls">
												<input type="text" class="input-medium disabled" id="status" value="<?php echo __tr($Infos['status']) ?>" disabled>
											</div> <!-- /controls -->
										</div> <!-- /control-group -->

										<div class="control-group">
											<label class="control-label" for="firstname"><?php echo __tr('Username') ?></label>
											<div class="controls">
												<input type="text" class="input-medium" name="displayname" value="<?php echo $Infos['username'] ?>">
											</div> <!-- /controls -->
										</div> <!-- /control-group -->

										<div class="control-group">
											<label class="control-label" for="email"><?php echo __tr('Email address') ?></label>
											<div class="controls">
												<input type="text" class="input-large" name="email" value="<?php echo $Infos['email'] ?>">
											</div> <!-- /controls -->
										</div> <!-- /control-group -->
										<br />

										<div class="control-group">
											<label class="control-label" for="npwd"><?php echo __tr('Password') ?></label>
											<div class="controls">
												<input type="password" class="input-medium" name="npwd" value="">
											</div> <!-- /controls -->
										</div> <!-- /control-group -->


										<div class="control-group">
											<label class="control-label" for="npwd2"><?php echo __tr('Confirm') ?></label>
											<div class="controls">
												<input type="password" class="input-medium" name="npwd2" value="">
											</div> <!-- /controls -->
										</div> <!-- /control-group -->

											<br />


										<div class="form-actions">
											<button type="submit" class="btn btn-primary"><?php echo __tr('Save') ?></button>
											<button class="btn"><?php echo __tr('Cancel') ?></button>
										</div> <!-- /form-actions -->
									</fieldset>
								</form>
								</div>

								<div class="tab-pane<?php echo $_SESSION['tab'] == 'account_bunnies' ? ' active' : '' ?>" id="bunnies">
									<form id="edit-profile2" class="form-horizontal" method="post">
										<div class="control-group">
											<label class="control-label" for="bmac"><?php echo __tr('MAC address') ?></label>
											<div class="controls">
												<input type="text" class="input-medium" name="bmac" value="">
												<p class="help-block"><?php echo __tr('Will only work if the server allows it') ?></p>
											</div> <!-- /controls -->
										</div> <!-- /control-group -->
										<div class="control-group">
											<label class="control-label" for="bname"><?php echo __tr('Name of your Bunny') ?></label>
											<div class="controls">
												<input type="text" class="input-medium" name="bname" value="">
											</div> <!-- /controls -->
										</div> <!-- /control-group -->
											<div class="form-actions">
												<button type="submit" class="btn btn-primary"><?php echo  __tr('Add bunny') ?></button> <button class="btn"><?php echo __tr('Cancel') ?></button>
											</div>



										<div class="control-group">
											<label class="control-label" for="bmac_rm"><?php echo __tr('Remove a bunny from your account') ?></label>
											<div class="controls">
<select name="bmac_rm">
        <option value="">- <?php echo __tr('Choose the bunny to remove') ?> -</option>
    <?php
    $bunnies = $ojnAPI->getListOfBunnies(true);
    if(!empty($bunnies))
        foreach($bunnies as $mac => $bunny) { ?>
        <option value="<?php echo $mac; ?>"><?php echo $bunny; ?> (<?php echo $mac; ?>)</option>
    <?php } ?>
</select>
												<p class="help-block"><?php echo __tr('No confirmation, so... be careful!') ?></p>
											</div> <!-- /controls -->
										</div> <!-- /control-group -->
											<div class="form-actions">
												<button type="submit" class="btn btn-primary"><?php echo  __tr('Remove bunny') ?></button>
											</div>
									</form>
								</div>
								<div class="tab-pane<?php echo $_SESSION['tab'] == 'account_ztamps' ? ' active' : '' ?>" id="ztamps">
									<form id="edit-profile2" class="form-horizontal" method="post">


										<div class="control-group">
											<label class="control-label" for="zid_rm"><?php echo __tr('Remove a ztamp from your account') ?></label>
											<div class="controls">
<select name="zid_rm">
        <option value="">- <?php echo __tr('Choose the ztamp to remove') ?> -</option>
    <?php
    $ztamps = $ojnAPI->getListOfZtamps(true);
    if(!empty($ztamps))
        foreach($ztamps as $id => $ztamp) { ?>
        <option value="<?php echo $id; ?>"><?php echo $ztamp; ?> (<?php echo $id; ?>)</option>
    <?php } ?>
</select>
												<p class="help-block"><?php echo __tr('No confirmation, so... be careful!') ?></p>
											</div> <!-- /controls -->
										</div> <!-- /control-group -->
											<div class="form-actions">
												<button type="submit" class="btn btn-primary"><?php echo  __tr('Remove ztamp') ?></button>
											</div>

									</form>
								</div>
<?php
$lng = $Infos['language'];
?>
								<div class="tab-pane<?php echo $_SESSION['tab'] == 'account_settings' ? ' active' : '' ?>" id="settings">
									<form id="edit-profile2" class="form-horizontal" method="post">
										<fieldset>
											<div class="control-group">
												<label class="control-label" for="accountusername"><?php echo __tr('Language') ?></label>
												<div class="controls">
<select name="lng">
<?php
	$tr = getTranslates($_SESSION['login']);
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
	    die('Connexion impossible : ' . mysqli_error());
	}

	$sql = "SELECT * FROM language";
	if(!$Infos['isAdmin'])
		$sql .= " WHERE public=1";
	// Exceptions de dev :
	if(count($tr))
		foreach($tr as $t)
			$sql .= " OR code='".$t."'";

	$res = mysqli_query($link, $sql);
	while($row = mysqli_fetch_assoc($res))
	{
?>
<option value="<?php echo $row['code'] ?>"<?php if($lng == $row['code']) { ?> selected="selected"<?php } ?>><?php echo $row['language'] ?></option>
<?php
	}
	mysqli_close($link);
?>
</select>
												</div>
											</div>

											<br />
											<div class="form-actions">
												<button type="submit" class="btn btn-primary">Save</button> <button class="btn">Cancel</button>
											</div>
										</fieldset>
									</form>
								</div>
							</div>
						</div>
					</div> <!-- /widget-content -->
				</div> <!-- /widget -->
		    </div> <!-- /span8 -->

<?php /*
	      	<div class="span4">
				<div class="widget widget-box">
					<div class="widget-header">
	      				<h3>Extra Info</h3>
	  				</div> <!-- /widget-header -->
					<div class="widget-content">

						<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>

						<p> Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>

					</div> <!-- /widget-content -->
				</div> <!-- /widget-box -->
		      </div> <!-- /span4 -->
*/ ?>
	      </div> <!-- /row -->
<?php
require_once '../include/append.php';
?>
