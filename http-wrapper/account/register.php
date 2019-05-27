<?php
require_once('../include/common.php');
if(isset($_SESSION['token'])) {
	Message::AddWarning("You're already logged. Are you sure you wan't to create another account ?");
$ojnTemplate->setTitle(__tr('Registration form'));
}
if(!empty($_POST['login']) &&
        !empty($_POST['pwd']) && !empty($_POST['pwd2'])) {
		if(!strlen(trim($_POST['name'])))
			$_POST['name'] = $_POST['login'];
    		if((string)$_POST['pwd'] == (string)$_POST['pwd2']) {
        		$retour = $ojnAPI->getApiString('accounts/registerNewAccount?login='.urlencode($_POST['login']).'&username='.urlencode($_POST['name']).'&pass='.$_POST['pwd']);
			Message::AddFromApi($retour);
    		} else
			Message::AddError(__tr('Passwords mismatch. Try again'));
    if(isset($retour['ok'])) {

	$r = $ojnAPI->loginAccount($_POST['login'], $_POST['pwd']);
	if(!strpos($r,"AD_")) {
		$_SESSION['login'] = $_POST['login'];
		$ojnAPI->setToken($r);

		$real_client_ip = '-';
		if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
			$real_client_ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
		} else {
			$headers = apache_request_headers();
			if(isset($headers["X-Forwarded-For"])) {
				$real_client_ip = $headers["X-Forwarded-For"];
			}
		}
		if(strlen($real_client_ip)) {
			$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
			if (!$link) {
			    die('Connexion impossible : ' . mysqli_error());
			}
			mysqli_query($link, "UPDATE account SET lastip='".$real_client_ip."' WHERE username=\"".addslashes($_SESSION['login'])."\";");
			mysqli_close($link);
			//Message::Addsuccess('IP saved : ' . $real_client_ip);
		} else {
			//Message::AddError('No IP');
		}

	}

	if(strlen($_POST['lng'])) {
    		apcu_delete(APC_PREFIX.'ojn_user_'.$_SESSION['login']);
    		Message::AddFromApi($ojnAPI->getApiString('accounts/setlanguage?login='.urlencode($_POST['login']).'&lng='.$_POST['lng'].'&'.$ojnAPI->getToken()));
	}

	if(strlen(trim($_POST['email']))) {
    		Message::AddFromApi($ojnAPI->getApiString('accounts/setemail?login='.urlencode($_POST['login']).'&email='.trim($_POST['email']).'&'.$ojnAPI->getToken()));
	}

	session_write_close();
	header("Location: index.php");
        exit;
    }
} else if (count($_POST)) {
	Message::AddError(__tr('Incorrect parameters'));
}
?>
<?php
require(ROOT_SITE.'include/message.php');
?>
<div class="row">
    <div class="span12">
        <div class="widget widget">
            <div class="widget-header">
                <i class="icon-list-alt"></i><h3><?php echo __tr("Registration form") ?></h3>
            </div>
            <div class="widget-content">
    <form method="post" class="form-horizontal">

	<div class="control-group">
		<label class="control-label" for="login"><?php echo __tr('Login') ?></label>
		<div class="controls">
			<input type="text" class="input-medium" name="login" value="<?php echo isset($_POST['login']) ? $_POST['login'] : "" ?>">
			<p class="help-block"><?php echo __tr('Will be used for login') ?></p>
			<p class="help-block help-warning"><?php echo __tr('Use only lowercase letters and numbers (no space,accentued character, or other weird stuff...)') ?></p>
		</div>
	</div>
	<div class="control-group">
		<label class="control-label" for="name"><?php echo __tr('Name') ?></label>
		<div class="controls">
			<input type="text" class="input-medium" name="name" value="<?php echo isset($_POST['name']) ? $_POST['name'] : "" ?>">
			<p class="help-block"><?php echo __tr('Will be used for display') ?></p>
		</div>
	</div>
	<div class="control-group">
		<label class="control-label" for="pwd"><?php echo __tr('Password') ?></label>
		<div class="controls">
			<input type="password" class="input-medium" name="pwd" value="">
		</div>
	</div>
	<div class="control-group">
		<label class="control-label" for="pwd"><?php echo __tr('Confirm') ?></label>
		<div class="controls">
			<input type="password" class="input-medium" name="pwd2" value="">
		</div>
	</div>
	<hr />
	<h4><?php echo __tr('Optional informations') ?></h4>
	<br />
	<div class="control-group">
		<label class="control-label" for="email"><?php echo __tr('Email') ?></label>
		<div class="controls">
			<input type="text" class="input-medium" name="email" value="<?php echo isset($_POST['email']) ? $_POST['email'] : "" ?>">
		</div>
	</div>

											<div class="control-group">
												<label class="control-label" for="accountusername"><?php echo __tr('Language') ?></label>
												<div class="controls">

<select name="lng">
<option value=""><?php echo __tr('Choose your language') ?></option>
<?php
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
	    die('Connexion impossible : ' . mysqli_error());
	}

	$sql = "SELECT * FROM language";
	$sql .= " WHERE public=1";
	$res = mysqli_query($link, $sql);
	while($row = mysqli_fetch_assoc($res))
	{
?>
<option value="<?php echo $row['code'] ?>"><?php echo $row['language'] ?></option>
<?php
	}
	mysqli_close($link);
?>
</select>
												</div>
											</div>
	<div class="form-actions">
		<button type="submit" class="btn btn-primary"><?php echo __tr('Create account') ?></button>
	</div> <!-- /form-actions -->
</form>

		</div>
	</div>
    </div>
</div>
<?php
require_once(ROOT_SITE.'include/append.php');
?>
