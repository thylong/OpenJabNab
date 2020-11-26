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
<div class="card ">
  <h5 class="card-header">
    <i class="icon-user"></i> <?php echo __tr('Your account') ?>
  </h5>
  <div class="card-body">
    <ul class="nav nav-tabs">
      <li class="nav-item <?php echo $_SESSION['tab'] == 'account_profile' ? 'active' : '' ?>">
        <a class="nav-link" href="#profile" data-toggle="tab" role="tab" aria-controls="profile" aria-selected="true"><?php echo __tr('Profile') ?></a>
      </li>
      <li class="nav-item <?php echo $_SESSION['tab'] == 'account_bunnies' ? 'active' : '' ?>">
        <a class="nav-link" href="#bunnies" data-toggle="tab" role="tab" aria-controls="bunnies" aria-selected="false"><?php echo __tr('Bunnies') ?></a>
      </li>
      <li class="nav-item <?php echo $_SESSION['tab'] == 'account_ztamps' ? 'active' : '' ?>">
        <a class="nav-link" href="#ztamps" data-toggle="tab" role="tab" aria-controls="ztamps" aria-selected="false"><?php echo __tr('Ztamps') ?></a>
      </li>
      <li class="nav-item <?php echo $_SESSION['tab'] == 'account_settings' ? 'active' : '' ?>">
        <a class="nav-link" href="#settings" data-toggle="tab" role="tab" aria-controls="settings" aria-selected="false"><?php echo __tr('Settings') ?></a>
      </li>
    </ul>
    <div class="tab-content">
      <br />
      <div class="tab-pane<?php echo $_SESSION['tab'] == 'account_profile' ? ' active' : '' ?>" id="profile">
        <form method="post">
          <div class="form-group row">
            <label class="col-sm-2 col-form-label" for="login"><?php echo __tr('Login') ?></label>
            <div class="col-sm-2">
              <input type="text" class="form-control" name="login" value="<?php echo !empty($_SESSION['login']) ? $_SESSION['login'] : __tr('Unknown') ?>" disabled>
            </div>
            <div class="col-sm-8">
              <p class="help-block"><?php echo __tr('Your login cannot be changed.') ?></p>
            </div>
          </div>
          <div class="form-group row">
            <label class="col-sm-2 col-form-label" for="status"><?php echo __tr('Status') ?></label>
            <div class="col-sm-2">
              <input type="text" class="form-control" name="status" value="<?php echo __tr($Infos['status']) ?>" disabled>
            </div>
          </div>
          <div class="form-group row">
            <label class="col-sm-2 col-form-label" for="username"><?php echo __tr('Username') ?></label>
            <div class="col-sm-2">
              <input type="text" class="form-control" name="username" value="<?php echo $Infos['username'] ?>">
            </div>
          </div>
          <div class="form-group row">
            <label class="col-sm-2 col-form-label" for="email"><?php echo __tr('Email address') ?></label>
            <div class="col-sm-3">
              <input type="text" class="form-control" name="email" value="<?php echo $Infos['email'] ?>">
            </div>
          </div>
          <div class="form-group row">
            <label class="col-sm-2 col-form-label" for="npwd"><?php echo __tr('Password') ?></label>
            <div class="col-sm-2">
              <input type="password" class="form-control" name="npwd" value="">
            </div>
          </div>
          <div class="form-group row">
            <label class="col-sm-2 col-form-label" for="npwd2"><?php echo __tr('Confirm') ?></label>
            <div class="col-sm-2">
              <input type="password" class="form-control" name="npwd2" value="">
            </div>
          </div>
          <div class="form-group row">
            <div class="col-sm-12 text-center">
              <button type="submit" class="btn btn-primary"><?php echo __tr('Save') ?></button>
              <button class="btn btn-warning"><?php echo __tr('Cancel') ?></button>
            </div>
          </div>
        </form>
      </div>

      <div class="tab-pane<?php echo $_SESSION['tab'] == 'account_bunnies' ? ' active' : '' ?>" id="bunnies">
        <form method="post">
          <fieldset class="border p-3">
            <legend><h6><?php echo __tr('Add a bunny to your account') ?></h6></legend>
            <div class="form-group row">
              <label class="col-sm-2 col-form-label" for="bmac"><?php echo __tr('MAC address') ?></label>
              <div class="col-sm-2">
                <input type="text" class="form-control" name="bmac" value="">
              </div>
              <div class="col-sm-8">
                <p class="help-block"><?php echo __tr('Will only work if the server allows it') ?></p>
              </div>
            </div>
            <div class="form-group row">
              <label class="col-sm-2 col-form-label" for="bname"><?php echo __tr('Name of your Bunny') ?></label>
              <div class="col-sm-2">
                <input type="text" class="input-medium" name="bname" value="">
              </div>
            </div>
            <div class="form-group row">
              <div class="col-sm-12 text-left">
                <button type="submit" class="btn btn-primary"><?php echo  __tr('Add bunny') ?></button>
                <button class="btn  btn-warning"><?php echo __tr('Cancel') ?></button>
              </div>
            </div>
          </fieldset>
        </form>
        <br />
        <form method="post">
          <fieldset class="border p-3">
          <legend><h6><?php echo __tr('Remove a bunny from your account') ?></h6></legend>
            <div class="form-group row">
              <label class="col-sm-1 col-form-label" for="bmac_rm"><?php echo __tr('Bunny'); ?></label>
              <div class="col-sm-3">
                <select name="bmac_rm">
                  <option value=""><?php echo __tr('Choose the bunny to remove') ?></option>
                  <?php
                  $bunnies = $ojnAPI->getListOfBunnies(true);
                  foreach($bunnies as $mac => $bunny): ?>
                    <option value="<?php echo $mac; ?>"><?php echo $bunny; ?> (<?php echo $mac; ?>)</option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-sm-6">
                <p class="help-block"><?php echo __tr('No confirmation, so... be careful!') ?></p>
              </div>
            </div>
            <div class="form-group row">
              <div class="col-sm-12 text-left">
                <button type="submit" class="btn btn-danger"><?php echo  __tr('Remove bunny') ?></button>
              </div>
            </div>
          </fieldset>
        </form>
      </div>

      <div class="tab-pane<?php echo $_SESSION['tab'] == 'account_ztamps' ? ' active' : '' ?>" id="ztamps">
        <form method="post">
          <fieldset class="border p-3">
            <legend><h6><?php echo __tr('Remove a ztamp from your account') ?></h6></legend>
            <div class="form-group row">
              <label class="col-sm-1 col-form-label" for="zid_rm"><?php echo __tr('Ztamp'); ?></label>
              <div class="col-sm-3">
                <select name="zid_rm">
                  <option value=""><?php echo __tr('Choose the ztamp to remove') ?></option>
                  <?php
                  $ztamps = $ojnAPI->getListOfZtamps(true);
                  if(!empty($ztamps))
                      foreach($ztamps as $id => $ztamp):
                  ?><option value="<?php echo $id; ?>"><?php echo $ztamp; ?> (<?php echo $id; ?>)</option>
                  <?php endforeach; ?>
                </select>
                </select>
              </div>
              <div class="col-sm-6">
                <p class="help-block"><?php echo __tr('No confirmation, so... be careful!') ?></p>
              </div>
            </div>
            <div class="form-group row">
              <div class="col-sm-12 text-left">
                <button type="submit" class="btn btn-danger"><?php echo  __tr('Remove ztamp') ?></button>
              </div>
            </div>
          </fieldset>
        </form>
      </div>

      <?php
        $lng = $Infos['language'];
      ?>
			<div class="tab-pane<?php echo $_SESSION['tab'] == 'account_settings' ? ' active' : '' ?>" id="settings">
			  <form method="post">
            <div class="form-group row">
              <label class="col-sm-1 col-form-label" for="lng"><?php echo __tr('Language') ?></label>
              <div class="col-sm-3">
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
                    while($row = mysqli_fetch_assoc($res)):
                  ?>
                  <option value="<?php echo $row['code'] ?>"<?php if($lng == $row['code']) { ?> selected="selected"<?php } ?>>
                    <?php echo $row['language'] ?>
                  </option>
                  <?php endwhile;
                    mysqli_close($link); ?>
                </select>
              </div>
            </div>
            <div class="form-group row">
              <div class="col-sm-12 text-left">
                <button type="submit" class="btn btn-primary"><?php echo __tr('Save'); ?></button>
                <button class="btn btn-warning"><?php echo __tr('Cancel'); ?></button>
              </div>
            </div>
          </fieldset>
        </form>
      </div>
		</div>
  </div>
</div>
<?php
require_once '../include/append.php';
?>
