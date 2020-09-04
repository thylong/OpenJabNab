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


$reload = true;
if(!empty($_GET['rm_b']))
  Message::AddFromApi($ojnAPI->getApiString('accounts/user?login='.$account['username'].'&action=del&bunny='.$_GET['rm_b'].'&'.$ojnAPI->getToken()));
else if(!empty($_GET['rm_z']))
  Message::AddFromApi($ojnAPI->getApiString('accounts/user?login='.$account['username'].'&action=del&ztamp='.$_GET['rm_z'].'&'.$ojnAPI->getToken()));
else
  $reload = false;

if($reload)
{
  header('Location: account_expert.php?accid='.$account['id']);
  exit(0);
}

$disable_edit = true; // 20200826 Not currently tested
$disable_edit  = $disable_edit ? ' disabled' : ''; // Convert to Html stuff

require('../include/message.php');
?>
<div class="row">
  <div class="col-md-6">
    <div class="card">
      <h5 class="card-header">
        <i class="icon-user"></i> <?php echo __tr('Account %1, cleaned data', $account['username']) ?>
      </h5>
      <div class="card-body">
        <form id="edit-profile" method="post">
          <div class="form-group row">
            <label class="col-md-4 col-form-label" for="id"><?php echo __tr('ID') ?></label>
            <div class="col-md-5">
              <input type="text" class="form-control" name="id" value="<?php echo $account['id'] ?>"<?php echo $disable_edit; ?> />
            </div>
          </div>
          <div class="form-group row">
            <label class="col-md-4 col-form-label" for="username"><?php echo __tr('Login') ?></label>
            <div class="col-md-5">
              <input type="text" class="form-control" name="username" value="<?php echo $login ?>"<?php echo $disable_edit; ?> />
            </div>
          </div>
          <div class="form-group row">
            <label class="col-md-4 col-form-label" for="firstname"><?php echo __tr('Display name') ?></label>
            <div class="col-md-5">
              <input type="text" class="form-control" name="displayname" value="<?php echo $username ?>"<?php echo $disable_edit; ?> />
            </div>
          </div>
          <div class="form-group row">
            <label class="col-md-4 col-form-label" for="language"><?php echo __tr('Language') ?></label>
            <div class="col-md-5">
              <input type="text" class="form-control" name="language" value="<?php echo $language ?>"<?php echo $disable_edit; ?> />
            </div>
          </div>
          <div class="form-group row">
            <label class="col-md-4 col-form-label" for="email"><?php echo __tr('Email address') ?></label>
            <div class="col-md-5">
              <input type="text" class="form-control" name="email" value="<?php echo $email ?>"<?php echo $disable_edit; ?> />
            </div>
          </div>
          <div class="form-group row">
            <label class="col-md-4 col-form-label" for="npwd"><?php echo __tr('Password') ?></label>
            <div class="col-md-5">
              <input type="text" class="form-control" name="npwd" value=""<?php echo $disable_edit; ?> />
            </div>
          </div>
          <div class="form-group row">
            <label class="col-md-4 col-form-label" for="status"><?php echo __tr('Status') ?></label>
            <div class="col-md-5">
              <input type="text" class="form-control" name="status" value="<?php echo __tr($account['status']) ?>"<?php echo $disable_edit; ?> />
            </div>
          </div>
          <div class="form-group row">
            <label class="col-md-4 col-form-label" for="admin"><?php echo __tr("Status") ?></label>
            <div class="col-md-8">
              <div class="form-check form-check-inline">
                <input type="radio" name="admin" value="1" id="admin1" class="form-check-input" <?php echo $admin ? ' checked="checked"' : ''; ?><?php echo $disable_edit; ?> />
                <label class="form-check-label" for="admin1"><?php echo __tr("Administrator") ?></label>
              </div>
              <div class="form-check form-check-inline">
                <input type="radio" name="admin" value="0" id="admin0" class="form-check-input" <?php echo !$admin ? ' checked="checked"' : ''; ?><?php echo $disable_edit; ?> />
                <label class="form-check-label" for="admin0"><?php echo __tr("User") ?></label>
              </div>
            </div>
          </div>
          <div class="form-group row">
            <label class="col-md-4 col-form-label" for="premium"><?php echo __tr("Premium") ?></label>
            <div class="col-md-8">
              <div class="form-check form-check-inline">
                <input type="radio" name="premium" value="1" id="premium1" class="form-check-input" <?php echo $premium ? ' checked="checked"' : ''; ?><?php echo $disable_edit; ?> />
                <label class="form-check-label" for="premium1"><?php echo __tr("Yes") ?></label>
              </div>
              <div class="form-check form-check-inline">
                <input type="radio" name="premium" value="0" id="premium0" class="form-check-input" <?php echo !$premium ? ' checked="checked"' : ''; ?><?php echo $disable_edit; ?> />
                <label class="form-check-label" for="premium0"><?php echo __tr("No") ?></label>
              </div>
            </div>
          </div>
          <div class="form-group row">
            <label class="col-md-4 col-form-label" for="vip"><?php echo __tr("VIP") ?></label>
            <div class="col-md-8">
              <div class="form-check form-check-inline">
                <input type="radio" name="vip" value="1" id="vip1" class="form-check-input" <?php echo $vip ? ' checked="checked"' : ''; ?><?php echo $disable_edit; ?> />
                <label class="form-check-label" for="vip1"><?php echo __tr("Yes") ?></label>
              </div>
              <div class="form-check form-check-inline">
                <input type="radio" name="vip" value="0" id="vip0" class="form-check-input" <?php echo !$vip ? ' checked="checked"' : ''; ?><?php echo $disable_edit; ?> />
                <label class="form-check-label" for="vip0"><?php echo __tr("No") ?></label>
              </div>
            </div>
          </div>
          <div class="form-group row">
            <label class="col-md-4 col-form-label" for="nlogin"><?php echo __tr('Number of login') ?></label>
            <div class="col-md-5">
              <input type="text" class="form-control" name="nlogin" value="<?php echo $logincount ?>"<?php echo $disable_edit; ?> />
            </div>
          </div>
          <div class="form-group row">
            <label class="col-md-4 col-form-label" for="lastlogin"><?php echo __tr('Last login') ?></label>
            <div class="col-md-5">
              <input type="text" class="form-control" name="lastlogin" value="<?php echo date("d/m/Y H:i:s", $lastlogin) ?>"<?php echo $disable_edit; ?> />
            </div>
          </div>
          <div class="form-group row">
            <label class="col-md-4 col-form-label" for="nabus"><?php echo __tr('Number of abuses') ?></label>
            <div class="col-md-5">
              <input type="text" class="form-control" name="nabus" value="<?php echo $abusecount ?>"<?php echo $disable_edit; ?> />
            </div>
          </div>
          <div class="form-group row">
            <label class="col-md-4 col-form-label" for="lastban"><?php echo __tr('Last ban') ?></label>
            <div class="col-md-5">
              <input type="text" class="form-control" name="lastban" value="<?php echo date("d/m/Y H:i:s", $ban) ?>"<?php echo $disable_edit; ?> />
            </div>
          </div>
          <div class="form-group row">
            <label class="col-md-4 col-form-label" for="lastip"><?php echo __tr('Last IP address') ?></label>
            <div class="col-md-5">
              <input type="text" class="form-control" name="lastip" value="<?php echo $account['lastip'] ?>"<?php echo $disable_edit; ?> />
            </div>
          </div>
          <div class="form-group row">
            <label class="col-md-4 col-form-label" for="donation"><?php echo __tr('Donation') ?></label>
            <div class="col-md-5">
              <input type="text" class="form-control" name="donation" value="<?php echo (float)$account['don'] ?> &euro;"<?php echo $disable_edit; ?> />
            </div>
          </div>
          <div class="form-group row">
            <div class="col-md-8 offset-md-4">
              <input type="hidden" name="update" value="go">
              <button type="submit" class="btn btn-primary "<?php echo $disable_edit; ?> ><?php echo __tr('Update and reload') ?></button>
            </div>
          </div>
        </form>
        <hr />
        <div class="row m-3">
          <div class="col-sm-12 text-center">
            <a target="_blank" class="btn btn-sm btn-primary" href="/account/index.php?a=<?php echo $account['id'] ?>"><i class="icon-large icon-cog"></i> <?php echo __tr('Manage account') ?></a> &nbsp;
            <a target="_blank" class="btn btn-sm btn-success" href="/index.php?logid=<?php echo $account['id'] ?>"><i class="icon-large icon-user"></i> <?php echo __tr('Connect') ?></a> &nbsp;
            <a target="_blank" class="btn btn-sm btn-danger" href="server.php?removeA=<?php echo urlencode($account['username']) ?>"><i class="icon-large icon-trash"></i> <?php echo __tr('Remove account') ?></a>
          </div>
        </div>

        <?php if(count($bunnies)): ?>
        <table class="table table-bordered table-striped">
          <tr>
            <th><?php echo __tr('Bunnies (%1)', count($bunnies)) ?>
            <th class="col-sm-10"><?php echo __tr("Actions") ?></th>
          </tr>
          <?php foreach($bunnies as $mac): ?>
          <tr>
            <td><?php echo $mac ?></td>
            <td class="text-right">
              <a class="btn btn-sm btn-warning" href="bunny_expert.php?mac=<?php echo $mac ?>"><i class="icon-large icon-search"></i> <?php echo __tr('Expert') ?></a>
              <a class="btn btn-sm btn-primary" href="/bunny/index.php?b=<?php echo $mac ?>"><i class="icon-large icon-cog"></i> <?php echo __tr('Manage bunny') ?></a>
              <a class="btn btn-sm btn-danger" href="?accid=<?php echo $account['id']; ?>&rm_b=<?php echo $mac; ?>"><i class="icon-large icon-trash"></i> <?php echo __tr('Free from account'); ?></a>
            </td>
          </tr>
          <?php endforeach; ?>
        </table>
        <?php endif; ?>
        <?php if(count($ztamps)): ?>
        <table class="table table-bordered table-striped">
          <tr>
            <th><?php echo __tr('Ztamps (%1)', count($ztamps)) ?>
            <th class="col-sm-10"><?php echo __tr('Actions'); ?></th>
          </tr>
          <?php foreach($ztamps as $mac): ?>
          <tr>
            <td><?php echo $mac ?></td>
            <td class="text-right">
              <a class="btn btn-sm btn-warning" href="ztamp_expert.php?mac=<?php echo $mac ?>"><i class="icon-large icon-search"></i> <?php echo __tr('Expert') ?></a>
              <a class="btn btn-sm btn-primary" href="/account/ztamp.php?z=<?php echo $mac ?>"><i class="icon-large icon-cog"></i> <?php echo __tr('Manage ztamp') ?></a>
              <a class="btn btn-sm btn-danger" href="?accid=<?php echo $account['id']; ?>&rm_z=<?php echo $mac; ?>"><i class="icon-large icon-trash"></i> <?php echo __tr('Free from account'); ?></a>
            </td>
          </tr>
          <?php endforeach; ?>
        </table>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-md-6">
	  <div class="card">
			<h5 class="card-header">
				<i class="icon-cog"></i> <?php echo __tr('Account data') ?>
			</h5>
			<div class="card-body">
        <pre><?php var_dump($account); ?></pre>
      </div>
    </div>

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
		<div class="card">
			<h5 class="card-header">
				<i class="icon-cog"></i> <?php echo __tr('Cleaned raw data') ?>
			</h5>
			<div class="card-body text-center">
        <pre><?php
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
          ?></pre>
      </div>
    </div>
		<div class="card">
			<h5 class="card-header">
				<i class="icon-cog"></i> <?php echo __tr('Database raw data') ?>
			</h5>
			<div class="card-body text-center">
        <pre><?php
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
          ?></pre>
      </div>
    </div>
  </div>
</div>
<?php
require_once "include/append.php";
?>
