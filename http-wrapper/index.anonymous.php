<div class="row">
  <div class="col-md-7">
    <div class="card">
      <h6 class="card-header">
        <i class="icon-file"></i> <?php echo __tr("Your nabaztag is not connected on this server ?") ?>
      </h6>
      <div class="card-body">
          <p><?php echo __tr('If you want to join the openJabNab solution, you need three steps') ?> :</p>
          <p>&bull; <?php echo __tr('Setup your bunny') ?> : <a href="/help/setup.php"><?php echo __tr('How to setup my bunny ?') ?></a></p>
          <p>&bull; <?php echo __tr('Create an account') ?> : <a href="/account/register.php"><?php echo __tr('Sign Up') ?></a></p>
          <p>&bull; <?php echo __tr('Add the bunny to your account') ?></p>
          <p>&bull; <?php echo __tr('Choose and setup the plugins') ?> : <a href="/help/plugins.php"><?php echo __tr('Plugin list') ?></a></p>
      </div>
    </div>

    <div class="card">
      <h6 class="card-header">
        <i class="icon-list-alt"></i> <?php echo __tr("Recent News") ?>
      </h6>
      <div class="card-body">
  <?php require('index.anonymous.news.php'); ?>
      </div>
    </div>

    <div class="card">
      <h6 class="card-header">
        <i class="icon-file"></i> <?php echo __tr("What is openJabNab ?") ?>
      </h6>
      <div class="card-body">
        <p><?php echo __tr('openJabNab is an open-source alternative server to the official one') ?> : <a target="_blank" href="http://www.nabaztag.com/">http://www.nabaztag.com/</a>. <?php echo __tr('This solution is currently only for the second version of the nabaztag, also known as nabaztag:tag') ?>. <a href="/help/compatible.php"><?php echo __tr('Is my bunny compatible with openJabNab ?') ?></a>
        </p>
        <p><?php echo __tr('It was first created to add features to the nabaztag:tag, but as Violet closed the official server, openJabNab became a true alternative solution.') ?> <?php echo __tr('openJabNab is not affiliate to Violet or Aldebaran Robotics. It\'s developped by volunteers who don\'t want to see their bunnies in a box.') ?> <?php echo __tr('openJabNab may not be perfect, but it\'s improving day by day with everyone work.') ?> <?php echo __tr('Everyone should contribute to the project with its own possibilities. See the related page for more informations.') ?>
        </p>
      </div>
    </div>
  </div>
  <div class="col-md-5">
    <div class="card">
      <h6 class="card-header">
        <i class="icon-signin"></i> <?php echo __tr("Sign In") ?>
      </h6>
      <div class="card-body">
        <?php if($uptime) : ?>
        <form method="post">
          <div class="form-row align-items-center">
            <p><?php echo __tr("Sign in using your registered account:") ?></p>
            <div class="input-group">
              <div class="input-group-preprend">
                <div class="input-group-text"><i class="icon-user"></i></div>
              </div>
              <input type="text" class="form-control" name="login" placeholder="<?php echo __tr("Username") ?>" />
            </div>
            <div class="input-group">
              <div class="input-group-preprend">
                <div class="input-group-text"><i class="icon-key"></i></div>
              </div>
              <input type="password" class="form-control" name="password" placeholder="<?php echo __tr("Password") ?>" />
            </div>
          </div>
          <div class="row mt-2">
            <div class="col-md-6">
              <div class="form-check">
                <input name="keepLogin" type="checkbox" class="form-check-input" tabindex="4" disabled="disabled" />
                <label class="form-check-label" for="keepLogin"><?php echo __tr("Keep me signed in") ?></label>
              </div>
            </div>
            <div class="col-md-6 text-right">
              <button class="btn btn-primary"><?php echo __tr("Sign In") ?></button>
            </div>
          </div>
          <hr />
          <div class="input-group">
            <div class="form-check">
              <?php echo __tr("Don't have an account?"); ?>&nbsp; <a href="/account/register.php"><?php echo __tr("Sign Up") ?></a>
              <br />
              <a href="/account/password.php"><?php echo __tr("Remind Password") ?></a>
            </div>
          </div>
        </form>
        <?php else : ?>
        <?php echo __tr("Website under maintenance. Please come back later") ?>
        <?php endif; ?>
      </div>
    </div>

    <?php
    if(!($this_month = apcu_fetch(APC_PREFIX.'ojn_stats_server_'.date('mY'))))
    {
      $srv = getServerFeesFullfilment();
      $this_month = isset($srv[date('m/Y')]) ? $srv[date('m/Y')]['p'] : 0;
      apcu_store(APC_PREFIX.'ojn_stats_server_'.date('mY'), $this_month, 3600);
    }

    $Stats = $uptime ? $ojnAPI->getStats() : array();

    $lapins = isset($Stats['connected_bunnies']) ? $Stats['connected_bunnies'] : 0;
    $plugins = isset($Stats['enabled_plugins']) ? $Stats['enabled_plugins'] : 0;
    $ztamps = isset($Stats['ztamps']) ? $Stats['ztamps'] : 0;

    ?>

    <div class="card">
      <h6 class="card-header">
        <i class="icon-star"></i> <?php echo __tr("Social") ?>
      </h6>
      <div class="card-body">
        <div class="fb-page" data-href="https://www.facebook.com/openjabnab.fr" data-small-header="false" data-adapt-container-width="true" data-hide-cover="false" data-show-facepile="true">
          <div class="fb-xfbml-parse-ignore">
            <blockquote cite="https://www.facebook.com/openjabnab.fr">
              <a href="https://www.facebook.com/openjabnab.fr">Openjabnab.fr</a>
            </blockquote>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <h6 class="card-header">
        <i class="icon-star"></i> <?php echo __tr("Quick Stats") ?>
      </h6>
      <div class="card-body">
        <div class="stats">
          <div class="stat">
            <span class="stat-value"><?php echo $lapins; ?></span>
            <?php echo __tr("Nabaztags") ?>
          </div>
          <div class="stat">
            <span class="stat-value"><?php echo $ztamps; ?></span>
            <?php echo __tr("Ztamps") ?>
          </div>
          <div class="stat">
            <span class="stat-value"><?php echo $plugins; ?></span>
            <?php echo __tr("Plugins") ?>
          </div>
        </div>
        <div class="stats">
          <div class="stat stat-time">
            <?php echo __tr('Participation in financing the server for the current month') ?> : <?php echo $this_month; ?>%
            <div class="progress mt-1">
              <div class="progress-bar<?php echo $this_month > 80 ? ' bg-success text-light' :'';?>" style="width: <?php echo $this_month ?>%;">
                <?php if($this_month > 80): ?>
                <?php echo __tr('Many thanks to all donators') ?> !
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
        <div id="chart-stats" class="stats">
          <div class="stat stat-time">
            <span class="stat-value"><?php echo $uptime; ?></span>
            <?php echo __tr("Server uptime") ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
