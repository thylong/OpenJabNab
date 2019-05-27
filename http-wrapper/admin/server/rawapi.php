<?php
require_once '../include/common.php';

//var_dump($ojnAPI->getApiRaw('plugin/rugby/getTeams'));
//echo "<pre>";
//var_dump($ojnAPI->getPlugins());
?>
	      <div class="row">
	      	<div class="span12">
	      		<div class="widget">
					<div class="widget-header">
						<i class="icon-th-large"></i>
						<h3><?php echo __tr("On this page, you can make a direct API call, your token will be added automatically") ?></h3>
					</div> <!-- /widget-header -->
					<div class="widget-content">
<form method="post">
<input type="text" style="width:80%" name="r" value="<?php echo !empty($_POST['r']) ? $_POST['r'] : '' ?>"/><br />
<textarea style="width:80%" name="rs"></textarea><br />
<input type="submit" value="<?php echo __tr("Go !") ?>" class="btn btn-primary"/>
</form>
<pre style="border: 1px solid grey ; width:80% background-color:grey">
<?php
function printr($a,$l=0) {
	if(is_array($a))
		foreach($a as $b=>$sa) {
			if(count($sa) < 2) {
				if(!is_array($sa))
					echo str_repeat('    ',$l).$b." => ".$sa."\n";
				else if(!is_numeric($b))
					echo str_repeat('    ',$l).$b."\n";
			}else
				echo str_repeat('    ',$l).$b."\n";
				printr($sa,$l+1);
		}
}
if(!empty($_POST['rs'])) {
	foreach(spliti("\r\n", $_POST['rs']) as $rr)
	{
		$rr = trim($rr);
		if(strlen(trim($rr)))
		{
			$r = $ojnAPI->getApiXMLArray($rr.(strstr($rr,'?') ? '&': '?').$ojnAPI->getToken());
			echo "<b>".$rr."</b><br />";
			printr($r);
		}
	}
}
else if(!empty($_POST['r'])) {
//	$r = $ojnAPI->getApiString(trim($_POST['r']).(strstr($_POST['r'],'?') ? '&': '?').$ojnAPI->getToken());
	$r = $ojnAPI->getApiXMLArray(trim($_POST['r']).(strstr($_POST['r'],'?') ? '&': '?').$ojnAPI->getToken());
//echo htmlentities($ojnAPI->getApiRaw(trim($_POST['r']).(strstr($_POST['r'],'?') ? '&': '?').$ojnAPI->getToken()));
	echo "<b>".$_POST['r']."</b><br />";
	printr($r);
} else
	echo __tr("Make your request").".";
?></pre>
					</div> <!-- /widget-content -->
				</div> <!-- /widget -->
		    </div> <!-- /span12 -->
	      </div> <!-- /row -->
<?php
require_once '../include/append.php';
?>
