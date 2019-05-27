<?php
require_once "../include/common.php";
if(isset($_SESSION['token']))
{
	ob_end_clean();
	if(isset($_SESSION['bunny']))
	{
		define("BUNNY_API", "bunny/" . $_SESSION['bunny']);
		$str = $ojnAPI->getApiValue(BUNNY_API."/voice?action=test&sentence=".urlencode($_GET['sentence'])."&voice=".$_GET['voice']."&".$ojnAPI->getToken());
        echo $str;
		if($str)
		{
?>
			<object type="application/x-shockwave-flash" data="player_mp3.swf" width="200" height="20">
			     <param name="movie" value="/media/player_mp3.swf" />
			     <param name="FlashVars" value="autoplay=1&loadingcolor=0074CC&slidercolor1=0088CC&slidercolor2=0055CC&sliderovercolor=0074CC&buttonovercolor=0074CC&mp3=http://openjabnab.fr/<?php echo preg_replace("|^broadcast/|", "", $str) ?>" />
			</object>
<?php
		}
	}
	else
	{
		echo __tr('Error');
	}
}
else
{
	ob_end_clean();
	echo __tr('Error');
}
?>
