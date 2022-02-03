<?php
require_once "../include/common.php";
require_once "../include/tools.inc.php";
$link = getSQL();

$version = 2;

$max = 50;
require_once('../include/encode.functions.php');
require_once('../include/decode.functions.php');

$sql = 'SELECT z.* FROM ztamp as z';
if(!empty($_GET['z']))
{
  $z = mysqli_real_escape_string($link,trim($_GET['z']));
  $sql .= ' WHERE z.serial=\''.$z.'\'';
}
else if(!empty($_GET['zid']))
{
  $zid = mysqli_real_escape_string($link,trim($_GET['zid']));
  $sql .= ' WHERE z.id=\''.$zid.'\'';
}
else
{
  Message::AddError(__tr("No Ztamp selected"));
  header("Location: /admin/index.php");
  exit();
}
$ztamp = array();

$res = mysqli_query($link, $sql);
if($row = mysqli_fetch_assoc($res))
{
  $ztamp = $row;
  //echo '<pre>';
  //var_dump($ztamp);
}
else
{
  Message::AddError(__tr("Ztamp not found"));
  header("Location: /admin/index.php");
  exit();
}

$ZSettings = decodeZtampSettings($ztamp['settings']);
//var_dump($ZSettings);

$reload = false;

if($reload)
{
  header('Location: ztamp_expert.php?zid='.$ztamp['id']);
  exit(0);
}

$disable_edit = true; // 20220128 Not currently tested
$disable_edit  = $disable_edit ? ' disabled' : ''; // Convert to Html stuff

require_once(ROOT_SITE.'/include/message.php');
?>
<div class="row">
  <div class="col-md-6">
    <div class="card">
      <h5 class="card-header">
        <i class="icon-user"></i> <?php echo __tr('Ztamp %1', $ztamp['serial']) ?>
      </h5>
      <div class="card-body">
        <form class="form-horizontal" method="post">
          <h5><?php echo __tr('Database data'); ?></h5>
          <?php foreach($ztamp as $k => $v): ?>
          <div class="form-group row">
            <label class="col-md-4 col-form-label" for="<?php echo $k; ?>"><?php echo $k; ?></label>
            <div class="col-md-8">
              <input type="text" class="form-control" name="<?php echo $k; ?>" value="<?php echo $v; ?>"<?php echo $disable_edit; ?> />
            </div>
          </div>
          <?php endforeach; ?>
          <div class="form-group row">
            <div class="col-md-8 offset-md-4">
              <input type="hidden" name="update" value="go">
              <button type="submit" class="btn btn-sm btn-primary "<?php echo $disable_edit; ?> ><?php echo __tr('Update and reload') ?></button>
            </div>
          </div>
        </form>
        <hr />
        <form class="form-horizontal" method="post">
        <h5><?php echo __tr('Global Settings'); ?></h5>
          <?php foreach($ZSettings['GlobalSettings'] as $k => $v): ?>
          <div class="form-group row">
            <label class="col-md-4 col-form-label" for="<?php echo $k; ?>"><?php echo $k; ?></label>
            <div class="col-md-8">
            <?php if(is_array($v)): ?>
              <textarea class="form-control" name="<?php echo $k; ?>" disabled><?php if(!empty($v)) var_dump($v); ?></textarea>
            <?php else: ?>
              <input type="text" class="form-control" name="<?php echo $k; ?>" value="<?php echo $v; ?>"<?php echo $disable_edit; ?> />
            <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
          <div class="form-group row">
            <div class="col-md-8 offset-md-4">
              <input type="hidden" name="update" value="go">
              <button type="submit" class="btn btn-sm btn-primary "<?php echo $disable_edit; ?> ><?php echo __tr('Update and reload') ?></button>
            </div>
          </div>
        </form>
        <?php if(!empty($ZSettings['GlobalSettings']['OwnerAccounts'])): ?>
        <hr />
        <div class="form-group row">
          <label class="col-md-4 col-form-label" for="ztamp_owners"><?php echo __tr('Ztamp Owners'); ?></label>
          <div class="col-md-8">
            <?php foreach($ZSettings['GlobalSettings']['OwnerAccounts'] as $name): ?>
            <div class="row col-md-12">
              <input type="text" class="form-control col-md-9" name="own_<?php echo $name; ?>" value="<?php echo $name; ?>"<?php echo $disable_edit; ?> />&nbsp;
              <a target="_blank" class="btn btn-sm btn-warning" href="account_expert.php?name=<?php echo urlencode($name) ?>"><i class="icon-large icon-search"></i></a>&nbsp;
              <a target="_blank" class="btn btn-sm btn-danger" href="?rm_owner&z=<?php echo urlencode($ztamp['serial']); ?>&a=<?php echo urlencode($name) ?>"><i class="icon-large icon-trash"></i></a>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
        <?php if(!empty($ZSettings['GlobalSettings']['Associations'])): ?>
        <hr />
        <div class="form-group row">
          <label class="col-md-4 col-form-label" for="ztamp_owners"><?php echo __tr('Associations'); ?></label>
          <div class="col-md-8">
            <?php foreach($ZSettings['GlobalSettings']['Associations'] as $b => $p): ?>
            <div class="row col-md-12">
              <input type="text" class="form-control col-md-10" name="assoc_<?php echo $b; ?>_<?php echo $p; ?>" value="<?php echo $b; ?> => <?php echo $p; ?>"<?php echo $disable_edit; ?> />
              <a target="_blank" class="btn btn-sm btn-danger" href="?rm_assoc&z=<?php echo urlencode($ztamp['serial']); ?>&b=<?php echo urlencode($b); ?>&p=<?php echo urlencode($p); ?>"><i class="icon-large icon-trash"></i> </a>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
        <hr />
        <div class="row m-3">
          <div class="col-sm-12 text-center">
            <a target="_blank" class="btn btn-sm btn-primary" href="/account/ztamp.php?z=<?php echo $ztamp['id'] ?>"><i class="icon-large icon-cog"></i> <?php echo __tr('Manage ztamp') ?></a> &nbsp;
            <a target="_blank" class="btn btn-sm btn-danger" href="server.php?removeZ=<?php echo urlencode($ztamp['serial']) ?>"><i class="icon-large icon-trash"></i> <?php echo __tr('Remove ztamp') ?></a>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <h5 class="card-header">
        <i class="icon-search"></i> <?php echo __tr('Plugins config for').' '.$ztamp['serial'] ?>
      </h5>
      <div class="card-body">
        <?php foreach($ZSettings['PluginsSettings'] as $plugin => $conf): ?>
        <h5><?php echo ucfirst($plugin); if(in_array($plugin,$ZSettings['listOfPlugins'])): ?> <span class="badge badge-success"><?php echo __tr('Enabled'); ?></span><?php endif; ?></h5>
        <form class="form-horizontal" method="post">
            <?php foreach($conf as $k => $v): ?>
            <div class="form-group row">
              <label class="col-md-4 col-form-label" for="<?php echo $plugin.'_'.$k; ?>"><?php echo $k; ?></label>
              <div class="col-md-8">
              <?php if(is_array($v)): ?>
                <div class="row">
                <?php foreach($v as $kk => $vv): ?>
                  <div class="col-md-4">
                    <input type="text" class="form-control" name="<?php echo $plugin.'_'.$k.'_'.$kk.'_val'; ?>" value="<?php echo $kk; ?>"<?php echo $disable_edit; ?> />
                  </div>
                  <div class="col-md-8">
                    <input type="text" class="form-control" name="<?php echo $plugin.'_'.$k.'_'.$kk.'_key'; ?>" value="<?php echo $vv; ?>"<?php echo $disable_edit; ?> />
                  </div>
                <?php endforeach; ?>
                </div>
              <?php else: ?>
                <input type="text" class="form-control" name="<?php echo $plugin.'_'.$k; ?>" value="<?php echo $v; ?>"<?php echo $disable_edit; ?> />
              <?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
        </form>
        <hr />
        <?php endforeach; ?>
      </div>
    </div>

    <div class="card">
      <h5 class="card-header">
        <i class="icon-search"></i> <?php echo __tr('Enabled plugins for').' '.$ztamp['serial'] ?>
      </h5>
      <div class="card-body">
        <ul>
          <?php foreach($ZSettings['listOfPlugins'] as $p): ?>
          <li><?php echo ucfirst($p); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>

  </div>

  <div class="col-md-6">
    <div class="card">
      <h5 class="card-header">
        <i class="icon-cog"></i> <?php echo __tr('Ztamp data') ?>
      </h5>
      <div class="card-body">
        <pre><?php var_dump($ztamp); ?></pre>
      </div>
    </div>

    <div class="card">
      <h5 class="card-header">
        <i class="icon-cog"></i> <?php echo __tr('Ztamp settings') ?>
      </h5>
      <div class="card-body">
        <pre><?php var_dump($ZSettings); ?></pre>
      </div>
    </div>

    <?php
    /* FIXME !!
    $settings = "";
    $settings .= echoInt($version);
    $settings .= echoString($login);
    $settings .= echoString($username);
    $settings .= echoStr($passwordhash);
    $settings .= echoString($language);
    $settings .= echoString($email);
    $settings .= echoBool($admin);
    $settings .= echoBool($ZSettings['isPremium']);
    $settings .= echoBool($ZSettings['isVip']);
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
      $sql = "UPDATE account SET settings='".addslashes($settings)."' where id=".$ztamp['id'];
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
        header('Location: account_expert.php?accid=' . $ztamp['id']);
        exit;
    }

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
          //* /
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
    <?php */ ?>
		<div class="card">
			<h5 class="card-header">
				<i class="icon-cog"></i> <?php echo __tr('Database raw data') ?>
			</h5>
			<div class="card-body text-center">
        <pre><?php
          $add = 0;
          $offset = 0;
          $pattern = "|[\w@\"'_\-,;.:!\?]|";
          foreach(str_split($ztamp['settings'], 16) as $i => $line)
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
require_once "../include/append.php";
?>
