<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/fr_FR/sdk.js#xfbml=1&version=v2.5&appId=74985060802";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
<div class="row">
    <div class="span7">
        <div class="widget">
            <div class="widget-header">
                <i class="icon-file"></i><h3><?php echo __tr("Your nabaztag is not connected on this server ?") ?></h3>
            </div>
            <div class="widget-content">
                <p><?php echo __tr('If you want to join the openJabNab solution, you need three steps') ?> :</p>
                <p>&bull; <?php echo __tr('Setup your bunny') ?> : <a href="/help/setup.php"><?php echo __tr('How to setup my bunny ?') ?></a></p>
                <p>&bull; <?php echo __tr('Create an account') ?> : <a href="/account/register.php"><?php echo __tr('Sign Up') ?></a></p>
                <p>&bull; <?php echo __tr('Add the bunny to your account') ?></p>
                <p>&bull; <?php echo __tr('Choose and setup the plugins') ?> : <a href="/help/plugins.php"><?php echo __tr('Plugin list') ?></a></p>
            </div>
        </div>

        <div class="widget widget-nopad">
            <div class="widget-header">
                <i class="icon-list-alt"></i><h3><?php echo __tr("Recent News") ?></h3>
            </div>
            <div class="widget-content">
<?php require('index.anonymous.news.php'); ?>
            </div>
        </div>

        <div class="widget">
            <div class="widget-header">
                <i class="icon-file"></i><h3><?php echo __tr("What is openJabNab ?") ?></h3>
            </div>
            <div class="widget-content">
                <p><?php echo __tr('openJabNab is an open-source alternative server to the official one') ?> : <a target="_blank" href="http://www.nabaztag.com/">http://www.nabaztag.com/</a>. <?php echo __tr('This solution is currently only for the second version of the nabaztag, also known as nabaztag:tag') ?>. <a href="/help/compatible.php"><?php echo __tr('Is my bunny compatible with openJabNab ?') ?></a></p>
		<p><?php echo __tr('It was first created to add features to the nabaztag:tag, but as Violet closed the official server, openJabNab became a true alternative solution.') ?> <?php echo __tr('openJabNab is not affiliate to Violet or Aldebaran Robotics. It\'s developped by volunteers who don\'t want to see their bunnies in a box.') ?> <?php echo __tr('openJabNab may not be perfect, but it\'s improving day by day with everyone work.') ?> <?php echo __tr('Everyone should contribute to the project with its own possibilities. See the related page for more informations.') ?></p>
            </div>
        </div>

    </div>

    <div class="span5">
        <div class="widget">
            <div class="widget-header">
                <i class="icon-signin"></i><h3><?php echo __tr("Sign In") ?></h3>
	    </div>
            <div class="widget-content clearfix">
<?php if(true && $uptime) : ?>
                <form method="post">
                    <div class="login-fields">
                        <p><?php echo __tr("Sign in using your registered account:") ?></p>
                        <div class="field">
                            <label for="username"><?php echo __tr("Username") ?>:</label>
                            <input type="text" id="login" name="login" value="" placeholder="<?php echo __tr("Username") ?>" class="login username-field" />
                        </div>
                        <div class="field">
                            <label for="password"><?php echo __tr("Password") ?>:</label>
                            <input type="password" id="password" name="password" value="" placeholder="<?php echo __tr("Password") ?>" class="login password-field"/>
                        </div>
                    </div>
                    <div class="login-actions">
<?php /*
                        <span class="login-checkbox">
                            <input id="Field" name="Field" type="checkbox" class="field login-checkbox" value="First Choice" tabindex="4" />
                            <label class="choice" for="Field"><?php echo __tr("Keep me signed in") ?></label>
                        </span>
*/ ?>
                        <button class="button btn btn-primary btn-large"><?php echo __tr("Sign In") ?></button>
                    </div>

                    <div class="login-other">
                        <div class="login-extra">
                            <?php echo __tr("Don't have an account?") ?> <a href="/account/register.php"><?php echo __tr("Sign Up") ?></a><br/>
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
if(!($percent = apcu_fetch(APC_PREFIX.'ojn_stats_server_'.date('Ym')))) {
$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
    die('Connexion impossible : ' . mysqli_error());
}

$sql = "SELECT SUM(remain) AS remain FROM ((SELECT sum(remain) as remain from don as d where MONTH(date) = ".(date('m') + 0)." AND YEAR(date) = ".date('Y').") UNION (SELECT sum(remain) as remain from premium as p where MONTH(date) = ".(date('m') + 0)." AND YEAR(date) = ".date('Y').")) s;";
$sql = "SELECT sum(remain) as remain, date FROM ((SELECT sum(remain) as remain, CONCAT(YEAR(date), IF(MONTH(date)<10,CONCAT(\"0\",MONTH(date)),MONTH(date))) as date from don as d GROUP BY CONCAT(YEAR(date), MONTH(date)) ORDER BY date)
UNION
(SELECT sum(remain) as remain, CONCAT(YEAR(date), IF(MONTH(date)<10,CONCAT(\"0\",MONTH(date)),MONTH(date))) as date from premium as d GROUP BY CONCAT(YEAR(date), MONTH(date)) ORDER BY date)) d GROUP BY date ORDER BY date DESC;";
$res = mysqli_query($link, $sql);

$percent = array();
$ponder = 0.9;
while($row = mysqli_fetch_assoc($res))
{
	$percent[$row['date']] = round(min(($row['remain'] / 50 * 100) * $ponder, 100));
}

mysqli_close($link);
apcu_store(APC_PREFIX.'ojn_stats_server_'.date('Ym'), $percent, 3600);
}

$Stats = $uptime ? $ojnAPI->getStats() : array();

$lapins = isset($Stats['connected_bunnies']) ? $Stats['connected_bunnies'] : 0;
$plugins = isset($Stats['enabled_plugins']) ? $Stats['enabled_plugins'] : 0;
$ztamps = isset($Stats['ztamps']) ? $Stats['ztamps'] : 0;

?>

        <div class="widget">
            <div class="widget-header">
                <i class="icon-star"></i><h3><?php echo __tr("Social") ?></h3>
            </div>
            <div class="widget-content">
	<div class="fb-page" data-href="https://www.facebook.com/openjabnab.fr" data-small-header="false" data-adapt-container-width="true" data-hide-cover="false" data-show-facepile="true"><div class="fb-xfbml-parse-ignore"><blockquote cite="https://www.facebook.com/openjabnab.fr"><a href="https://www.facebook.com/openjabnab.fr">Openjabnab.fr</a></blockquote></div></div>
            </div>
        </div>

        <div class="widget">
            <div class="widget-header">
                <i class="icon-star"></i><h3><?php echo __tr("Quick Stats") ?></h3>
            </div>
            <div class="widget-content">

                <div class="stats">
                    <div class="stat">
			<span class="stat-value"><?php echo $lapins; ?></span><?php echo __tr("Nabaztags") ?>
                    </div>
                    <div class="stat">
			<span class="stat-value"><?php echo $ztamps; ?></span><?php echo __tr("Ztamps") ?>
                    </div>
                    <div class="stat">
			<span class="stat-value"><?php echo $plugins; ?></span><?php echo __tr("Plugins") ?>
                    </div>
                </div>

                <div class="stats">
                    <div class="stat stat-time">
<?php $Spercent = isset($percent[date('Ym')]) ? $percent[date('Ym')] : 0; ?>
	<?php echo __tr('Participation in financing the server for the current month') ?> : <?php echo $Spercent ?>%
<?php if($Spercent > 80) {
?><br /><?php echo __tr('Many thanks to all donators') ?>
<?php
}
?>
    <div class="progress" style="margin-right: 20px">
    <div class="bar" style="width: <?php echo $Spercent ?>%;"></div>
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
