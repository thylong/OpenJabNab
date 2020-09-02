<?php
require_once '../include/common.php';

//var_dump($ojnAPI->getApiRaw('plugin/rugby/getTeams'));
//echo "<pre>";
//var_dump($ojnAPI->getPlugins());
?>
<div class="card">
  <h5 class="card-header">
    <i class="icon-cog"></i> <?php echo __tr('Raw API Calls') ?>
  </h5>
  <div class="card-body">
		<h3><?php echo __tr("On this page, you can make a direct API call, your token will be added automatically") ?></h3>
		<form method="post">
			<div class="form-group row">
				<label class="col-sm-2 col-form-label" for="r"><?php echo __tr('Request(s)') ?></label>
				<div class="col-sm-10">
					<input type="text" name="r" value="<?php echo !empty($_POST['r']) ? $_POST['r'] : '' ?>" class="form-control" />
					<textarea name="rs" class="form-control"><?php echo !empty($_POST['rs']) ? $_POST['rs'] : '' ?></textarea>
				</div>
			</div>
			<div class="form-group row">
				<div class="col-sm-1 offset-sm-2">
					<input type="submit" value="<?php echo __tr("Go !") ?>" class="btn btn-primary"/>
				</div>
			</div>
		</form>
		<div class="form-group row">
			<label class="col-sm-2 col-form-label" for="rs"><?php echo __tr('Replie(s)') ?></label>
			<div class="col-sm-10" >
				<?php
				function printr($a,$l=0) {
					if(is_array($a))
						foreach($a as $b=>$sa) {
							if(!is_array($sa) || count($sa) < 2) {
								if(!is_array($sa))
									echo str_repeat('    ',$l).$b." => ".$sa."\n";
								else if(!is_numeric($b))
									echo str_repeat('    ',$l).$b."\n";
							}else
								echo str_repeat('    ',$l).$b."\n";
								printr($sa,$l+1);
						}
				}
				$rqs = array();
				if(!empty($_POST['r']))
					$rqs = array($_POST['r']);
				elseif(!empty($_POST['rs'])) 
					$rqs = explode("\n", $_POST['rs']); 
				else
				{
					echo __tr("Make your request").".";
				}	

				foreach($rqs as $rr)
				{
					$rr = trim($rr);
					if(strlen(trim($rr)))
					{
						$r = $ojnAPI->getApiXMLArray($rr.(strstr($rr,'?') ? '&': '?').$ojnAPI->getToken());
						echo "<h5>".$rr."</h5><pre>";
						printr($r);
						echo '</pre><hr />';
					}
				} 
				?>
			</div>
		</div>
	</div>
</div>
<?php
require_once '../include/append.php';
?>
