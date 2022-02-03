<?php
require_once "../include/common.php";
$version = 2;

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$link) {
    die('Connexion impossible : ' . mysqli_error());
}
$max = 50;
require_once('../include/encode.functions.php');
require_once('../include/decode.functions.php');

$sql = "SELECT * FROM bunny ";
if(isset($_GET['mac']))
{
	$sql .= "WHERE mac='".$_GET['mac']."'";
}
else
{
	Message::AddError(__tr("No bunny selected"));
	header("Location: index.php");
	exit();
}
$bunny = array();
$res = mysqli_query($link, $sql);
if($row = mysqli_fetch_assoc($res))
{
	$bunny = $row;
}
else
{
	Message::AddError(__tr("Bunny not found"));
	header("Location: index.php");
	exit();
}
require_once(ROOT_SITE.'/include/message.php');

$BSettings = decodeBunnySettings($bunny['settings']);

$disable_edit = true; // 20200826 Not currently tested
$disable_edit  = $disable_edit ? ' disabled' : ''; // Convert to Html stuff

$pattern = "|[\w@\"'_\-,;.:!\? ]|";
?>
<div class="row">
  <div class="col-md-6">
    <div class="card">
      <h5 class="card-header">
        <i class="icon-search"></i> <?php echo __tr('Bunny').' '.$bunny['mac'] ?>
      </h5>
      <div class="card-body">
      <form class="form-horizontal" method="post">
          <h5><?php echo __tr('Database data'); ?></h5>
          <?php foreach($bunny as $k => $v): ?>
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
          <?php foreach($BSettings['GlobalSettings'] as $k => $v): ?>
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
      </div>
    </div>

    <div class="card">
      <h5 class="card-header">
        <i class="icon-search"></i> <?php echo __tr('Plugins config for').' '.$bunny['mac'] ?>
      </h5>
      <div class="card-body">
        <?php foreach($BSettings['PluginsSettings'] as $plugin => $conf): ?>
        <h5><?php echo ucfirst($plugin); if(in_array($plugin,$BSettings['listOfPlugins'])): ?> <span class="badge badge-success"><?php echo __tr('Enabled'); ?></span><?php endif; ?></h5>
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
        <i class="icon-search"></i> <?php echo __tr('Enabled plugins for').' '.$bunny['mac'] ?>
      </h5>
      <div class="card-body">
        <ul>
          <?php foreach($BSettings['listOfPlugins'] as $p): ?>
          <li><?php echo ucfirst($p); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>

    <div class="card">
      <h5 class="card-header">
        <i class="icon-search"></i> <?php echo __tr('Known Ztamps for').' '.$bunny['mac'] ?>
      </h5>
      <div class="card-body">
        <ul>
          <?php foreach($BSettings['knownRFIDTags'] as $z): if(empty($z)) continue; ?>
          <li><?php echo bin2hex($z); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>

  </div>
  <div class="col-md-6">
    <div class="card">
      <h5 class="card-header">
        <i class="icon-cog"></i> <?php echo __tr('Bunny data') ?>
      </h5>
      <div class="card-body">
        <pre><?php var_dump($bunny); ?></pre>
      </div>
    </div>

    <div class="card">
      <h5 class="card-header">
        <i class="icon-cog"></i> <?php echo __tr('Bunny settings') ?>
      </h5>
      <div class="card-body">
        <pre><?php var_dump($BSettings); ?></pre>
      </div>
    </div>

		<div class="card">
			<h5 class="card-header">
				<i class="icon-cog"></i> <?php echo __tr('Database raw data') ?>
			</h5>
			<div class="card-body text-center">
				<pre><?php
					$add = 0;
					foreach(str_split($bunny['settings'], 16) as $i => $line)
					{
						$hex = $str = "";
						foreach(str_split($line) as $k => $chr)
						{
							$hex .= str_pad(dechex(ord($chr)), 2, "0", STR_PAD_LEFT)." ";
							if($k == 7)
								$hex .= "  ";
							$str .= preg_match($pattern, $chr) ? $chr : ".";
						}
						echo "0x".str_pad(str_pad(dechex($add), 4, "0", STR_PAD_LEFT)."    ".$hex, 58," ")."   ".$str."<br />";
						$add += 16;
					}
					?>
				</pre>
			</div>
		</div>
	</div>
</div>
<?php
require_once "../include/append.php";
?>
