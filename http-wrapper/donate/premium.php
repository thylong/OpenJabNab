<?php
$reload = false;
require_once "../include/common.php";

if(isset($_POST) && count($_POST))
{
	if(isset($_SESSION['login']))
	{
		if(isset($_POST['gift']))
		{
			$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
			if (!$link) {
			    die('Connexion impossible : ' . mysqli_error());
			}
			$sql = "SELECT * FROM gift WHERE code='".$_POST['gift']."'";
			$res = mysqli_query($link, $sql);
			if($row = mysqli_fetch_assoc($res))
			{
				$code = $row['code'];
				$used = $row['used'];
				$genuine = $row['genuine'];
				if($used !== null && $used != '0000-00-00 00:00:00')
				{
					Message::AddError(__tr('This gift code is no more available'));
				}
				else if($genuine != 1 || $code != $_POST['gift'])
				{
					Message::AddError(__tr('This gift code is not official'));
				}
				else
				{
					$sql = "UPDATE gift SET username='".$_SESSION['login']."', used=NOW() WHERE code='".$code."'";
					$res = mysqli_query($link, $sql);
					include("include/update_status.inc.php");
					Message::AddSuccess(__tr("Gift code used with success"));
					Message::AddSuccess(__tr("Status successfully updated"));
				}
			}
			else
			{
				Message::AddError(__tr('This gift code does not exist'));
				$sql = "INSERT INTO gift_error SET date=NOW(), username='".$_SESSION['login']."', code='".$_POST['gift']."';";
				$res = mysqli_query($link, $sql);
			}
			mysqli_close($link);
			$reload = true;
		}
	}
	else
	{
		Message::AddError(__tr('You need to be connected to apply for a premium status'));
	}
}
if($reload) {
	header('Location: /donate/premium.php');
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
  <?php if(!isset($_SESSION['login'])): ?>
  <div class="col-md-12">
    <div class="card">
      <h5 class="card-header">
        <i class="icon-list-alt"></i> <?php echo __tr("Premium status and gift codes") ?>
      </h5>
      <div class="card-body">
        <p><?php echo __tr("You need to be connected to apply for a premium status") ?></p>
      </div>
  </div>
  <?php else: ?>
  <div class="col-md-6">
    <div class="card">
      <h5 class="card-header">
        <i class="icon-list-alt"></i> <?php echo __tr("Premium registration") ?>
      </h5>
      <div class="card-body">
        <?php
        $link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if (!$link) {
            die('Connexion impossible : ' . mysqli_error());
        }
        $user = $_SESSION['login'];
        $sql = 'SELECT * FROM `premium` WHERE `username`="'.$user.'" ORDER BY `date` ASC';
        $res = mysqli_query($link, $sql);
        $vip = 0;
        while($row = mysqli_fetch_assoc($res))
        {
          $days = 31 * $row['duration'];
          if($vip == 0)
          {
            $date = date_create($row['date']);
            date_add($date, date_interval_create_from_date_string(($days).' day'));
            $vip = date_format($date, 'Y-m-d');
          }
          else
          {
            $d = date_create($vip);
            $date = date_create($row['date']);
            if($d > $date) {
              $date = $d;
            }
            date_add($date, date_interval_create_from_date_string(($days).' day'));
            $vip = date_format($date, 'Y-m-d');
          }
        }

        $sql = 'SELECT * FROM `don` WHERE `username`="'.$user.'" ORDER BY `date` ASC';
        $res = mysqli_query($link, $sql);
        while($row = mysqli_fetch_assoc($res))
        {
          $days = floor(365 * $row['value'] / 10);
          if($vip == 0)
          {
            $date = date_create($row['date']);
            date_add($date, date_interval_create_from_date_string(($days).' day'));
            $vip = date_format($date, 'Y-m-d');
          }
          else
          {
            $d = date_create($vip);
            $date = date_create($row['date']);
            if($d > $date) {
              $date = $d;
            }
            date_add($date, date_interval_create_from_date_string(($days).' day'));
            $vip = date_format($date, 'Y-m-d');
          }
        }

        $sql = 'SELECT * FROM `gift` WHERE genuine=1 AND `username`="'.$user.'" ORDER BY `used` ASC';
        $res = mysqli_query($link, $sql);
        while($row = mysqli_fetch_assoc($res))
        {
          $days = $row['days'];
          if($vip == 0)
          {
            $date = date_create($row['used']);
            date_add($date, date_interval_create_from_date_string(($days).' day'));
            $vip = date_format($date, 'Y-m-d');
          }
          else
          {
            $d = date_create($vip);
            $date = date_create($row['date']);
            if($d > $date) {
              $date = $d;
            }
            date_add($date, date_interval_create_from_date_string(($days).' day'));
            $vip = date_format($date, 'Y-m-d');
          }
        }
        $sql = 'SELECT * FROM `gift` WHERE genuine=1 AND `buyer`="'.$user.'" ORDER BY `used` ASC';
        $res = mysqli_query($link, $sql);
        $gifts = array();
        while($row = mysqli_fetch_assoc($res))
        {
          $gifts[] = $row;
        }
        mysqli_close($link);
        if($vip): ?>
        <p><?php echo __tr('Your premium subscription is valid until %1', date('d/m/Y', strtotime($vip))) ?></p>
        <?php else: ?>
        <p><?php echo __tr('You have no premium subscription') ?></p>
        <?php endif; ?>
        <h3><?php echo __tr('Register for premium account') ?>

        <form class="form-horizontal" target="paypal" action="https://www.paypal.com/cgi-bin/webscr" method="post">
        <input type="hidden" name="return" value="http://openjabnab.fr/ojn_admin/premium_done.php">
        <input type="hidden" name="cmd" value="_s-xclick">
        <input type="hidden" name="on0" value="Duration">
        <input type="hidden" name="on1" value="User">
        <input type="hidden" name="os1" value="<?php echo $_SESSION['login'] ?>">
                <fieldset>
                  <div class="control-group">
                    <label for="input01" class="control-label"><?php echo __tr("Premium status duration") ?></label>
                    <div class="controls">
        <select name="os0">
          <option value="1 month"><?php echo __tr('%1 month, %2', 1, '2.00') ?> &euro;</option>
          <option value="3 months"><?php echo __tr('%1 months, %2', 3, '3.00') ?> &euro;</option>
          <option value="6 months"><?php echo __tr('%1 months, %2', 6, '5.00') ?> &euro;</option>
          <option value="1 year"><?php echo __tr('%1 year, %2', 1, '10.00') ?> &euro;</option>
          <option value="2 years"><?php echo __tr('%1 years, %2', 2, '20.00') ?> &euro;</option>
        </select>
        <p class="help-block"><?php echo __tr('Your premium status will be activated a few hours after payment') ?></p>
            </div>
          </div>
        <input type="hidden" name="currency_code" value="EUR">
        <input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----
        ">
                  <div class="form-actions">
        <input type="submit" class="btn btn-primary" value="<?php echo __tr('Add to cart') ?>">
          </div>
          </fieldset>
        </form>

        <h3><?php echo __tr('Buy gift codes') ?>
        <form class="form-horizontal" target="paypal" action="https://www.paypal.com/cgi-bin/webscr" method="post">
        <input type="hidden" name="return" value="http://openjabnab.fr/ojn_admin/premium_done.php">
        <input type="hidden" name="cmd" value="_s-xclick">
        <input type="hidden" name="on0" value="Duration">
        <input type="hidden" name="on1" value="User">
        <input type="hidden" name="os1" value="<?php echo $_SESSION['login'] ?>">
                <fieldset>
                  <div class="control-group">
                    <label for="input01" class="control-label"><?php echo __tr("Gift code duration") ?></label>
                    <div class="controls">
        <select name="os0">
          <option value="1 month"><?php echo __tr('%1 month, %2', 1, '2.00') ?> &euro;</option>
          <option value="3 months"><?php echo __tr('%1 months, %2', 3, '3.00') ?> &euro;</option>
          <option value="6 months"><?php echo __tr('%1 months, %2', 6, '5.00') ?> &euro;</option>
          <option value="1 year"><?php echo __tr('%1 year, %2', 1, '10.00') ?> &euro;</option>
          <option value="2 years"><?php echo __tr('%1 years, %2', 2, '20.00') ?> &euro;</option>
        </select>
        <p class="help-block"><?php echo __tr('Your gift codes will be available a few hours after payment') ?></p>
            </div>
          </div>
        <input type="hidden" name="currency_code" value="EUR">
        <input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----
        ">
                  <div class="form-actions">
        <input type="submit" class="btn btn-primary" value="<?php echo __tr('Add to cart') ?>">
          </div>
          </fieldset>
        </form>
	    </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card">
      <h5 class="card-header">
        <i class="icon-list-alt"></i> <?php echo __tr("Gift codes") ?>
      </h5>
      <div class="card-body">
        <?php if(!count($gifts)): ?>
        <p><?php echo __tr('You have no gift codes available') ?></p>
        <?php else: ?>
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
            <td><?php echo $gift['used'] == null ? __tr('Not used') : __tr('Used by %1 on %2', $gift['username'], date('d/m/Y', $gift['used'])) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
        </table>
        <?php endif; ?>
        <form class="form-horizontal" method="POST">
          <fieldset>
            <div class="control-group">
              <label for="input01" class="control-label"><?php echo __tr("Use a gift code") ?></label>
              <div class="controls">
                <input type="text" name="gift" value="" class="input-xlarge">
              </div>
            </div>
            <div class="form-actions">
              <button class="btn btn-primary" type="submit"><?php echo __tr("Apply") ?></button>
            </div>
          </fieldset>
        </form>
      </div>
    </div>
  </div>
	<?php endif;?>
</div>
<?php
require_once ROOT_SITE.'include/append.php';
?>
