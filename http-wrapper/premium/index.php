<?php
$reload = false;
require_once '../include/tools.inc.php';
require_once "../include/common.php";

$user = $Infos['login'];

if(!empty($_GET['gift']))
{
  if(!empty($user))
	{
    $link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$link) {
      die('Connexion impossible : ' . mysqli_error());
    }
    $gift_code = mysqli_real_escape_string($link,trim($_GET['gift']));
    $sql = 'SELECT start_date, username
              FROM gift
              WHERE code=\''.$gift_code.'\'';
    $res = mysqli_query($link, $sql);
    if($row = mysqli_fetch_assoc($res))
    {
      if(!empty($row['start_date']) || !empty($row['username']))
        Message::AddError(__tr('This gift code is no more available'));
      else
      {
        $sql = "UPDATE gift
                  SET username='".$user."',
                      start_date=NOW()
                WHERE code='".$gift_code."'";
        $res = mysqli_query($link, $sql);
        //include("include/update_status.inc.php");
        if($res)
          Message::AddSuccess(__tr("Gift code used with success"));
        else
          Message::AddError(__tr("An error happened. Please retry or contact an administrator."));
      }
    }
    else
    {
      Message::AddError(__tr('This gift code does not exist'));
      $sql = 'INSERT INTO gift_error
                      SET date=NOW(),
                          username=\''.$user.'\',
                          code=\''.$gift_code.'\'';
      $res = mysqli_query($link, $sql);
    }
    mysqli_close($link);
    $reload = true;
	}
	else
	{
		Message::AddError(__tr('You need to be connected to apply for a premium status'));
	}
}

$show_demo = false;
if(!empty($Infos['token']))
{
  $link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
  if (!$link) {
    die('Connexion impossible : ' . mysqli_error());
  }
  $sql = 'select count(username) as cnt from demo where username=\''.$Infos['login'].'\'';
  $res = mysqli_query($link, $sql);
  $row = mysqli_fetch_assoc($res);
  mysqli_close($link);
  $show_demo = empty($row['cnt']);
}

if(isset($_GET['demo']))
{
  if(!empty($Infos['token']))
	{

    if($show_demo)
    {
      $link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
      if (!$link) {
        die('Connexion impossible : ' . mysqli_error());
      }
      $sql = 'INSERT INTO demo SET username="'.addslashes($user).'", date=NOW();';
      $res = mysqli_query($link, $sql);
    	if($res)
		    Message::AddSuccess(__tr("You can now try all premium plugins"));
      else
        Message::AddError(__tr("An error happened. Please retry or contact an administrator."));
      mysqli_close($link);
    }
    else
      Message::AddError(__tr("You already have subscribe to the demo"));
	}
	else
	{
    Message::AddError(__tr('You need to be connected to apply for a demo status'));
	}
  $reload = true;
}

if($reload) {
	header('Location: /premium/index.php');
	exit;
}

$online = $ojnAPI->getListOfConnectedBunnies(false);
if(is_array($online)) {
	$online = array_keys($online);
	if(isset($Infos['isAdmin']) && $Infos['isAdmin']) {
		$online = array_keys($ojnAPI->getListOfAllConnectedBunnies(true));
	}
} else {
	$online = array();
}

require_once(ROOT_SITE.'include/message.php');

$xml = $ojnAPI->getApiRaw('plugins/getPlugins?lng='.$Infos['language']);
$plugins = simplexml_load_string($xml);

$list = array();
$premium = array();
$wip = array();

foreach($plugins->plugins->plugin as $plugin)
{
	$p = array();
	$p['name'] = (string)$plugin;
	$attrs = (array)$plugin->attributes();
	foreach($attrs['@attributes'] as $key => $value)
	{
		$p[$key] = trim($value);
	}

	if($p['premium'] && !$p['dev'])
		$list[] = $p['name'];
}
sort($list);

$vip = 0;
if(!empty($Infos['token']))
{
  $link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
  if (!$link) {
      die('Connexion impossible : ' . mysqli_error());
  }

  $sql = 'SELECT date, days FROM `premium` WHERE `username`="'.$user.'" ORDER BY `date` ASC';
  $res = mysqli_query($link, $sql);
  while($row = mysqli_fetch_assoc($res))
    $vip = date_add_days($row['date'], $row['days'], $vip);

  $sql = 'SELECT date FROM `demo` WHERE `username`="'.$user.'" ORDER BY `date` ASC';
  $res = mysqli_query($link, $sql);
  while($row = mysqli_fetch_assoc($res))
    $vip = date_add_days($row['date'], 7, $vip);

  $sql = 'SELECT date, value FROM `don` WHERE `username`="'.$user.'" ORDER BY `date` ASC';
  $res = mysqli_query($link, $sql);
  while($row = mysqli_fetch_assoc($res))
    $vip = date_add_days($row['date'], floor(365 * $row['value'] / 10), $vip);

  $sql = 'SELECT start_date, days days FROM `gift` WHERE `username`="'.$user.'" ORDER BY `start_date` ASC';
  $res = mysqli_query($link, $sql);
  while($row = mysqli_fetch_assoc($res))
    $vip = date_add_days($row['start_date'], $row['days'], $vip);

  if(date_create($vip) < date_create("now"))
    $vip = 0;

  $sql = 'SELECT * FROM `gift` WHERE `buyer`="'.$user.'" ORDER BY `id` ASC';
  $res = mysqli_query($link, $sql);
  $gifts = array();
  while($row = mysqli_fetch_assoc($res))
    $gifts[] = $row;

  mysqli_close($link);
}

?>
<div class="row">
  <div class="col-md-12">
    <div class="card">
      <h5 class="card-header">
        <i class="icon-list-alt"></i> <?php echo __tr("What about the premium status ?") ?>
      </h5>
      <div class="card-body">
        <p><?php echo __tr('The premium status adds the following advantages :') ?></p>
		    <ul>
			    <li><?php echo __tr('Voice recognition for plugins supporting it') ?></li>
			    <li><?php echo __tr('More plugins :') ?></li>
			    <ul>
				    <li>
            <?php echo implode('</li><li>', $list); ?>
				    </li>
			    </ul>
		      <li><?php echo __tr('The plugin that keeps all read messages can keep them longer (1 week instead of 12 hours)') ?></li>
			    <li><?php echo __tr('No login limit (classic users can only login 24 times a day)') ?></li>
		    </ul>
		    <p><?php echo __tr('The premium status helps me keeping the server online, up-to-date and as far as possible bug-free') ?>.</p>
      </div>
    </div>
  </div>
</div>
<div class="row">
  <?php if(!ENABLE_PREMIUM): ?>
  <div class="col-md-12">
    <div class="card">
      <h5 class="card-header">
        <i class="icon-list-alt"></i> <?php echo __tr("Premium status and gift codes"); ?>
      </h5>
      <div class="card-body">
        <div class="alert alert-success"><?php echo __tr('Disabled for now'); ?></div>
      </div>
    </div>
  </div>
  <?php elseif(empty($Infos['token'])): ?>
  <div class="col-md-12">
    <div class="card">
      <h5 class="card-header">
        <i class="icon-list-alt"></i> <?php echo __tr("Premium status and gift codes") ?>
      </h5>
      <div class="card-body">
        <p><?php echo __tr("You need to be connected to apply for a premium status") ?></p>
      </div>
    </div>
  </div>
  <?php else: ?>
  <div class="col-md-5">
    <div class="card">
      <h5 class="card-header">
        <i class="icon-list-alt"></i> <?php echo __tr("Premium registration") ?>
      </h5>
      <div class="card-body">
        <?php if($vip): ?>
        <p class="alert alert-success"><?php echo __tr('Your premium subscription is valid until %1', date('d/m/Y', strtotime($vip))) ?></p>
        <?php else: ?>
        <p class="alert alert-warning"><?php echo __tr('You have no premium subscription') ?></p>
        <?php endif; ?>
        <fieldset class="border p-3">
	        <legend><h5><?php echo __tr('Register for premium account') ?></h5></legend>
          <?php $formType="Premium"; require('form.inc.php'); ?>
          <div class="form-group row">
            <div class="col-sm-10 offset-sm-2 help-block">
              <?php echo __tr('Your premium status will be activated a few hours after payment') ?>
            </div>
          </div>
        </fieldset>
        <?php if(!$vip && $show_demo): ?>
        <fieldset class="border p-3 mt-3">
	        <legend><h5><?php echo __tr("Try premium plugins") ?></h5></legend>
          <div class="row">
            <p class="col-sm-12">
              <?php echo __tr("You can try all premium plugins for one week if you want to know how they work"); ?>.<br />
              <span class="badge badge-danger"><?php echo __tr('Warning') ?></span> <?php echo __tr("You can only ask for a demo one time"); ?>.
            </p>
          </div>
          <div class="row">
            <div class="col-sm-12 text-center mb-2">
              <a href="?demo" class="btn btn-success"><?php echo __tr('Ask for demo') ?></a>
            </div>
          </div>
        </fieldset>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-md-7">
    <div class="card">
      <h5 class="card-header">
        <i class="icon-list-alt"></i> <?php echo __tr("Gift codes") ?>
      </h5>
      <div class="card-body">
        <form method="get">
          <fieldset class="border p-3">
            <legend><h5><?php echo __tr("Use a gift code") ?></h5></legend>
            <div class="form-group row">
              <label class="col-sm-2 col-form-label" for="gift"><?php echo __tr('Gift code'); ?></label>
              <div class="col-sm-4">
                <input type="text" name="gift" value="" class="form-control">
              </div>
              <div class="col-sm-4">
                <input type="submit" class="btn btn-sm btn-primary" value="<?php echo __tr('Apply') ?>">
              </div>
            </div>
          </fieldset>
        </form>
        <hr />
        <?php if(!count($gifts)): ?>
        <p class="alert alert-warning"><?php echo __tr('You have no gift codes available') ?></p>
        <?php else: ?>
        <h5><?php echo __tr('Available gift codes'); ?></h5>
        <table class="table table-bordered table-striped">
          <thead>
            <tr>
              <th><?php echo __tr('Date') ?></th>
              <th><?php echo __tr('Duration') ?></th>
              <th><?php echo __tr('Code') ?></th>
              <th><?php echo __tr('Used') ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($gifts as $gift): ?>
            <tr>
              <td><?php echo date('d/m/Y', strtotime($gift['date'])) ?></td>
              <td><?php echo __tr('%1 days', $gift['days']) ?></td>
              <td><?php echo $gift['code'] ?></td>
              <td>
                <?php if($gift['start_date'] == null): ?>
                <?php echo __tr('Not used'); ?> <a href="?gift=<?php echo $gift['code']; ?>" class="btn btn-sm btn-primary"><?php echo __tr('Use'); ?></a>
                <?php else:
                  echo __tr('Used by %1 on %2', $gift['username'], date('d/m/Y', strtotime($gift['start_date'])));
                endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
        <fieldset class="border p-3">
          <legend><h5><?php echo __tr('Buy gift codes') ?></h5></legend>
          <?php $formType="Gift code"; require('form.inc.php'); ?>
          <div class="form-group row">
            <div class="col-sm-10 offset-sm-2 help-block">
            <?php echo __tr('Your gift codes will be available a few hours after payment') ?>
            </div>
          </div>
        </fieldset>
      </div>
    </div>
  </div>
	<?php endif;?>
</div>
<?php
require_once ROOT_SITE.'include/append.php';
?>
