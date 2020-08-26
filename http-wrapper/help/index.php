<?php
require_once "../include/common.php";
$ojnTemplate->setTitle(__tr('Help'));

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
    Message::AddError(__tr('Connexion impossible : ') . mysqli_error());
  header('Location: faq.php');
  exit;
}
$faqs = array();
$sql = "SELECT * FROM faq WHERE language='".$Infos['language']."' ORDER BY RAND()";
$res = mysqli_query($link, $sql);
while($row = mysqli_fetch_assoc($res))
  $faqs[] = $row;

mysqli_close($link);

require('../include/message.php');
?>

<div class="row">
  <div class="col-md-6">
    <div class="card">
    <h5 class="card-header">
      <i class="icon-list-alt"></i> <?php echo __tr("Ask for support") ?>
    </h5>
    <div class="card-body">
<?php if(!defined('ADMIN_EMAIL')): ?>
<?php echo __tr('This feature is not activated') ?>
<?php else: ?>
<?php

$problemes = array(
  0 => __tr("I'm a bot, and I don't choose an option"),
  1 => __tr("I can't create a user account"),
  2 => __tr("I can't add my bunny to my account"),
  3 => __tr("My bunny isn't connecting to the server"),
  4 => __tr("My timezone is not in openJabNab"),
  5 => __tr("I've lost bunnies and ztamps since new version"),
  6 => __tr("I can't upload files"),
  7 => __tr("I don't know how to use a plugin"),
  8 => __tr("I've made a donation, but I'm not a VIP nor Premium user"),
  9 => __tr("My bunny is silent"),
  10 => __tr("I want to help translating openJabNab in my language"),
  11 => __tr("I don't have access to the mailbox associated with my account"),
  12 => __tr("I want to try the Nabaztag V1"),
  99 => __tr("Other"),
);

if(count($_POST))
{
  $_SESSION['help'] = $_POST;
  $ok = true;
  $spam = false;
  if(!isset($_POST['mail']) || trim($_POST['mail']) == "")
  {
    $ok = false;
    Message::AddError(__tr("No email address"), 1);
  }
  if(!preg_match("/.+@.+\..+/", $_POST['mail']))
  {
    $ok = false;
    Message::AddError(__tr("Bad email address"), 1);
  }
  if(!isset($_POST['desc']) || trim($_POST['desc']) == "")
  {
    $ok = false;
    Message::AddError(__tr("No description"), 1);
  }
  if(strlen(trim($_POST['desc'])) < 20)
  {
    $ok = false;
    Message::AddError(__tr("Your message is too short to be understood"), 1);
  }
  if(!isset($_POST['probleme']) || $_POST['probleme'] == 0)
  {
    $ok = false;
    Message::AddError(__tr("Your mail was not sent as your are a bot"), 1);
  }
  $drugs = array('zopiclone', 'imovane', 'pharmacy', 'acetaminophen', 'codeine', 'promethazine', 'homeopathic', 'oxycodone', 'hydrocodone', 'pharmaceutical', 'motilium', 'zithromax', 'mifepristone', 'misoprostol', 'tetracycline', 'imitrex', 'albuterol', 'mebendazole', 'terbinafine', 'aldactone', 'zoloft', 'lexapro', 'domperidone', 'voltaren', 'isotretinoin', 'accutane', 'diflucan', 'fluconazole');
  if(
    preg_match('/('.implode('|', $drugs).')/i', $_POST['desc']) ||
    preg_match('/\d+ ?mg/i', $_POST['desc']) ||
    preg_match('/FyLitCl7Pf7kjQdDUOLQOuaxTXbj5iNG/i', $_POST['desc']) ||
    preg_match('/\[(url|link)=/i', $_POST['desc'])
  )
  {
    $spam = true;
    error_log("Spammer detected", 0);
  }

  {
    $from = "openJabNab Admin <".ADMIN_EMAIL.">";
    if(isset($_POST['mail']) && $_POST['mail'] != "")
    {
      $message = __tr('Email address')." : ".$_POST['mail']."\n";
      $from = $_POST['mail'];
    }
    $message .= __tr('Problem')." : ".$problemes[$_POST['probleme']]."\n";
    if(isset($_POST['serial']) && $_POST['serial'] != "")
      $message .= __tr('Bunny')." : ".strtolower(preg_replace("|[: ]*|", "", $_POST['serial']))."\n";
    if(isset($_POST['user_name']) && $_POST['user_name'] != "")
      $message .= __tr("User account")." : ".$_POST['user_name']."\n";
    $message .= strip_tags(stripslashes($_POST['desc']))."\n";
    $subject = __tr("openJabNab help form");
    $to = "openJabNab Admin <".ADMIN_EMAIL.">";
    if($ok) {
      if($spam) {
        Message::AddSuccess(__tr("Your mail has been sent to administrator"));
        $_SESSION['help'] = null;
        unset($_SESSION['help']);
      } else if(sendMail($message, $subject, $to, $from)) {
        Message::AddSuccess(__tr("Your mail has been sent to administrator"));
        $_SESSION['help'] = null;
        unset($_SESSION['help']);
      } else {
        Message::AddError(__tr("An error occured"), 0);
      }
    } else {
      Message::AddError(__tr("An error occured"), 0);
    }
  }
  if(!isset($_POST['serial']) || trim($_POST['serial']) == "")
  {
    Message::AddWarning(__tr("No bunny specified"), 0);
  }
  if(!isset($_POST['user_name']) || trim($_POST['user_name']) == "")
  {
    Message::AddWarning(__tr("No username specified"), 0);
  }
  header('Location: /help/index.php');
  exit();
}
?>
      <form method="post">
        <div class="form-group row">
          <label class="col-sm-4 col-form-label" for="mail"><?php echo __tr('Email address') ?></label>
          <div class="col-sm-6">
            <input type="text" class="form-control" name="mail" value="<?php echo isset($_SESSION['help']['mail']) ? $_SESSION['help']['mail'] : "" ?>">
          </div>
        </div>
        <div class="form-group row">
          <label class="col-sm-4 col-form-label" for="description"><?php echo __tr('Username') ?></label>
          <div class="col-sm-6">
            <?php if(isset($_SESSION['token'])): ?>
            <?php echo $_SESSION['login'] ?>
            <input type="hidden" name="user_name" value="<?php echo $_SESSION['login'] ?>">
            <?php else: ?>
            <input type="text" class="form-control" name="user_name" value="<?php echo isset($_SESSION['help']['user_name']) ? $_SESSION['help']['user_name'] : "" ?>">
            <?php endif; ?>
          </div>
        </div>
        <div class="form-group row">
          <label class="col-sm-4 col-form-label" for="serial"><?php echo __tr('MAC address of bunny') ?></label>
          <div class="col-sm-6">
            <input type="text" class="form-control" name="serial" value="<?php echo isset($_SESSION['help']['serial']) ? $_SESSION['help']['serial'] : "" ?>" placeholder="00xxxxxxxxxx">
          </div>
        </div>
        <div class="form-group row">
          <label class="col-sm-4 col-form-label" for="probleme"><?php echo __tr('Title') ?></label>
          <div class="col-sm-8">
            <select name="probleme" class='form-control'>
              <?php foreach($problemes as $id => $probleme): ?>
              <option value="<?php echo $id ?>"<?php echo (isset($_GET['pb']) && $_GET['pb']==$id) || (isset($_SESSION['help']['probleme']) && $_SESSION['help']['probleme'] == $id) ? ' selected="selected"' : ''?>><?php echo $probleme ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group row">
          <label class="col-sm-4 col-form-label" for="desc"><?php echo __tr('Description') ?></label>
          <div class="col-sm-8">
            <textarea name="desc" class="form-control" style="height: 150px"><?php echo isset($_SESSION['help']['desc']) ? $_SESSION['help']['desc'] : "" ?></textarea>
          </div>
        </div>
        <div class="form-group row">
          <div class="col-sm-12 text-right">
            <button type="submit" class="btn btn-primary"><?php echo __tr('Send') ?></button>
          </div>
        </div>
      </form>
      <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card">
      <h5 class="card-header">
        <i class="icon-list-alt"></i> <?php echo __tr("Frequently Ask Questions") ?>
      </h5>
      <ul class="card-body">
        <?php foreach($faqs as $f):
        ?><li>
          <a href="faq.php?question=<?php echo $f['slug'] ?>"><?php echo $f['question'] ?></a>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>
<?php
require_once "../include/append.php";
?>
