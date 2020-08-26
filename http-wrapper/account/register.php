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
<div class="card">
	<h5 class="card-header">
		<i class="icon-list-alt"></i> <?php echo __tr("Registration form") ?>
	</h5>
  <form class="card-body" method="post">
    <div>
      <h4><?php echo __tr('Required informations') ?></h4>
      <div class="form-group row">
        <label class="col-sm-2 col-form-label" for="login"><?php echo __tr('Login') ?></label>
        <div class="col-sm-2">
          <input type="text" class="form-control" name="login" value="<?php echo isset($_POST['login']) ? $_POST['login'] : "" ?>">
        </div>
        <div class="col-sm-8">
          <?php echo __tr('Will be used for login') ?><br />
          <?php echo __tr('Use only lowercase letters and numbers (no space,accentued character, or other weird stuff...)') ?>
        </div>
      </div>
      <div class="form-group row">
        <label class="col-sm-2 col-form-label" for="name"><?php echo __tr('Name') ?></label>
        <div class="col-sm-2">
          <input type="text" class="form-control" name="name" value="<?php echo isset($_POST['name']) ? $_POST['name'] : "" ?>">
        </div>
        <div class="col-sm-8">
          <?php echo __tr('Will be used for display') ?>
        </div>
      </div>
      <div class="form-group row">
        <label class="col-sm-2 col-form-label" for="pwd"><?php echo __tr('Password') ?></label>
        <div class="col-sm-3">
          <input type="password" class="form-control" name="pwd" value="">
        </div>
      </div>
      <div class="form-group row">
        <label class="col-sm-2 col-form-label" for="pwd2"><?php echo __tr('Confirm') ?></label>
        <div class="col-sm-3">
          <input type="password" class="form-control" name="pwd2" value="">
        </div>
      </div>
    </div>
    <hr />
    <div>
      <h4><?php echo __tr('Optional informations') ?></h4>
      <div class="form-group row">
        <label class="col-sm-2 col-form-label" for="email"><?php echo __tr('Email') ?></label>
        <div class="col-sm-3">
          <input type="text" class="form-control" name="email" value="<?php echo isset($_POST['email']) ? $_POST['email'] : "" ?>">
        </div>
        <div class="col-sm-7">
          <?php echo __tr('Will only be used for password recovery and important OpenJabNab communications') ?>
        </div>
      </div>
      <div class="form-group row">
        <label class="col-sm-2 col-form-label" for="lng"><?php echo __tr('Language') ?></label>
        <div class="col-sm-2">
          <select class="form-control" name="lng">
            <option value=""><?php echo __tr('Choose your language') ?></option>
            <?php
            $link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if (!$link) {
                die('Connexion impossible : ' . mysqli_error());
            }

            $sql = "SELECT * FROM language";
            $sql .= " WHERE public=1";
            $res = mysqli_query($link, $sql);
            while($row = mysqli_fetch_assoc($res)):
            ?>
            <option value="<?php echo $row['code'] ?>"><?php echo $row['language'] ?></option>
            <?php endwhile;
            mysqli_close($link);
            ?>
          </select>
        </div>
      </div>
      <div class="form-group row">
        <div class="col-sm-12 text-center">
          <button type="submit" class="btn btn-primary"><?php echo __tr('Create account') ?></button>
        </div>
      </div>
    </form>
  </div>
</div>
<?php
require_once(ROOT_SITE.'include/append.php');
?>
