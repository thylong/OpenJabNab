<?php
require_once "../include/common.php";

if(count($_POST))
{
  if(!strlen($_POST['mail'])) {
    Message::AddError(__tr('Email field need to be filled'));
  }
  if(!strlen($_POST['login'])) {
    Message::AddError(__tr('Login field need to be filled'));
  }
  else {
    $pass = str_split($_POST['login']."OJN");
    shuffle($pass);
    $pass = implode('', $pass);
    $pass = preg_replace(array("|l|i", "|i|i"), array("L","i"), $pass);
    $pass = substr(preg_replace("|[^\w]|", "", $pass), 0, 8);
    $message = __tr('Your new password is now : %1', $pass)."\r\n";
    $message .= __tr('Use it on http://openjabnab.fr/ojn_admin/')."\r\n";
    $message .= __tr('You can change it later in your account')."\r\n";
    $subject = __tr("Your new openJabNab password");
    $from = __tr('openJabNab Admin')." <password@openjabnab.fr>";
    $to = "\"".$_POST['login']."\" <".$_POST['mail'].">";
    $r = $ojnAPI->loginAccount(ADMIN_API_USER, ADMIN_API_PWD, false);
    $ojnAPI->setToken($r);
    $email = $ojnAPI->getApiValue('accounts/getemail?login='.$_POST['login']);
    if($email != '' && $email != $_POST['mail'])
    {
      $to = "\"".$_POST['login']."\" <".$email.">";
      $id = Message::AddError(__tr("You didn't enter the email address associated with your account"));
      Message::AddError(__tr("An email was sent to this email address"), $id);
      Message::AddError(__tr("If you don't have access to this mailbox, please use the contact form"), $id);

      $message = __tr('Your new password is now : %1', $pass)."\r\n";
      $message .= __tr('Use it on http://openjabnab.fr/ojn_admin/')."\r\n";
      $message .= __tr('You can change it later in your account')."\r\n";
      $message .= "--------------------------------------\r\n";
      $message .= __tr("This password change was asked by %1",$_POST['mail'])."\r\n";

      $ret = $ojnAPI->getApiString('accounts/changePassword?login='.$_POST['login'].'&pass='.$pass.'&'.$ojnAPI->getToken());
      unset($_SESSION['bunny']);
      unset($_SESSION['bunny_name']);
      unset($_SESSION['ztamp']);
      unset($_SESSION['ztamp_name']);
      unset($_SESSION['login']);
      $ojnAPI->SetToken('');
      unset($_SESSION['token']);
      if(!isset($ret['error']))
      {
        if(sendMail($message, $subject, $email, $from)) {

          Message::AddSuccess(__tr("Your new password will arrive shortly in the mailbox associated to your account"));
          header('Location: index.php');
          exit();
        } else {
          Message::AddError(__tr("An error occured"));
        }
      }
      else
      {
        Message::AddError(__tr("Unknow login"));
      }
    }
    else
    {
      $ret = $ojnAPI->getApiString('accounts/changePassword?login='.$_POST['login'].'&pass='.$pass.'&'.$ojnAPI->getToken());
      unset($_SESSION['bunny']);
      unset($_SESSION['bunny_name']);
      unset($_SESSION['ztamp']);
      unset($_SESSION['ztamp_name']);
      unset($_SESSION['login']);
      $ojnAPI->SetToken('');
      unset($_SESSION['token']);
      if(!isset($ret['error']))
      {
        if(sendMail($message, $subject, $to, $from)) {

          Message::AddSuccess(__tr("Your new password will arrive shortly in your inbox"));
          header('Location: index.php');
          exit();
        } else {
          Message::AddError(__tr("An error occured"));
        }
      }
      else
      {
        Message::AddError(__tr("Unknow login"));
      }
    }
  }
  header('Location: /account/password.php');
  exit();
}
require('../include/message.php');
?>
<div class="card">
  <h5 class="card-header">
    <i class="icon-list-alt"></i> <?php echo __tr("Remind Password") ?>
  </h5>
  <form class="card-body" method="post">
    <div class="form-group row">
      <label class="col-sm-2 col-form-label" for="login"><?php echo __tr('Login') ?></label>
      <div class="col-sm-3">
        <input type="text" class="form-control" name="login" value="">
      </div>
    </div>
    <div class="form-group row">
      <label class="col-sm-2 col-form-label" for="mail"><?php echo __tr('Email address') ?></label>
      <div class="col-sm-3">
        <input type="text" class="form-control" name="mail" value="">
      </div>
    </div>
    <div class="form-group row">
      <div class="col-sm-12 text-left">
        <button type="submit" class="btn btn-primary"><?php echo __tr('Get a new password') ?></button> 
      </div>
    </div>
  </form>
</div> 
<?php
require_once "../include/append.php";
?>
