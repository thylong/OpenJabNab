<?php
class ojnTemplate {
	private $titre_alt	= "";
	private $titre	= "";
	private $soustitre	= "Configuration";
	private $Api;
	private $UInfos;
	private $js = "";
	private $css = "ojn.css";

	public function __construct(&$api) {
		$this->Api=$api;
		$this->titre = __tr("openJabNab : the new burrow of Nabaztag");
	}

	public function setUInfos($v) {
		$this->UInfos = $v;
	}

	public function getLanguage()
	{
		return isset($this->UInfos['language']) ? $this->UInfos['language'] : 'en';
	}

	public function setTitle($titre)
	{
		$this->titre = $titre . " - openJabNab";
	}

	public function display($buffer) {
		$template = file_get_contents(ROOT_SITE.'include/class/template.tpl.php');

		$pattern = array(
				"|<!!TITLE!!>|",
				"|<!!ALTTITLE!!>|",
				"|<!!SUBTITLE!!>|",
				"|<!!CONTENT!!>|",
				"|<!!MENU!!>|",
				"|<!!USER!!>|",
				"|<!!FOOTER!!>|",
				"|<!!JS!!>|",
				"|<!!CSS!!>|",
			);
		$replace = array(
				__tr($this->titre),
				$this->titre_alt,
				$this->soustitre,
				$buffer,
				$this->makeMenu(),
				$this->makeUserMenu(),
				$this->makeFooter(),
				$this->getJS(),
				$this->getCSS(),
			);

		$template = preg_replace($pattern, $replace, $template);
    		$mtime = microtime();
    		$mtime = explode(" ",$mtime);
    		$mtime = $mtime[1] + $mtime[0];
    		$tend = $mtime;
		global $tstart;
    		$totaltime = ($tend - $tstart);
    		$template = preg_replace("|<!!TIME!!>|", __tr("Page was generated in %1 seconds", round($totaltime,4)), $template);
		return $template;
        }

	private function makeMenu() {
		$menu = '<ul class="nav">';
		$active = basename($_SERVER['PHP_SELF'], ".php");
		if(strpos($active, "_"))
			$active = substr($active, 0, strpos($active, "_") );
		$menu .= '<li'.($active == 'index' ? ' class="active"' : '').'><a href="/index.php"><span>'.__tr("Home").'</span></a></li>';
		if(isset($_SESSION['token']))	{
			$menu .= '<li class="dropdown'.($active == 'bunny' ? ' active' : '').'">'."\n";

			$bunnies = $this->Api->getListOfBunnies(false);
			if(!empty($bunnies))
			{
				if(count($bunnies) == 1)
				{
					$mac = array_keys($bunnies);
					$menu .= '<a href="/bunny/index.php?b='.$mac[0].'"><span>'.__tr("Bunny").'</span></a>';
				}
				else
				{
					$menu .= '<a href="/bunny/index.php" class="dropdown-toggle" data-toggle="dropdown"><span>'.__tr("Bunnies").'</span><b class="caret"></b></a><ul class="dropdown-menu">';
					$menu .= '<li><a href="/bunny/index.php?b=clear">'.__tr("List").'</a></li><li class="divider"></li>';
					foreach($bunnies as $mac => $bunny)
					{
						$menu .= '<li><a href="/bunny/index.php?b='.$mac.'" alt="'.$mac.'" title="'.$mac.'">'.($bunny != "Bunny" ? $bunny : $mac).'</a></li>';
					}
					$menu .= '</ul>';
				}
			}
			else
			{
				$menu .= '<a href="/bunny/index.php"><span>'.__tr("Bunnies").'</span></a>';
			}
			$menu .= '</li>'."\n";
			$menu .= '<li'.($active == 'ztamp' ? ' class="active"' : '').'><a href="/account/ztamp.php?z=clear"></i><span>'.__tr("Ztamps").'</span></a></li>';
		}
		$menu .= '<li'.($active == 'stats' ? ' class="active"' : '').'><a href="/stats.php"></i><span>'.__tr("Statistics").'</span></a></li>';
		$menu .= '<li'.($active == 'map' ? ' class="active"' : '').'><a href="/map.php"></i><span>'.__tr("Map").'</span></a></li>';
		$menu .= '<li'.($active == 'wiki' ? ' class="active"' : '').'><a href="http://wiki.openjabnab.fr"></i><span>'.__tr("Wiki").'</span></a></li>';
		$menu .= '<li'.($active == 'help' ? ' class="active"' : '').'><a href="/help/index.php"></i><span>'.__tr("Help").'</span></a></li></ul>';
		$menu .= '<ul class="nav pull-right'.($active == 'project' ? ' active' : '').'"><li class="donate"><a href="/donate/index.php"><span>'.__tr("Help the project").'</span></a></li>';
		$menu .= '<li class="donate"><a href="/donate/premium.php"><span>'.__tr("Premium status").'</span></a></li></ul>';
		return $menu;
	}

	private function makeUserMenu()	{
		$menu = "";
		if(isset($this->UInfos['token']) && $this->UInfos['token'] != '')
		{
			if($this->UInfos['isAdmin']) {
				$menu .= '<form class="navbar-search" action="/admin/search.php" method="post">';
				$menu .= '<input type="text" class="search-query" name="search" placeholder="'.__tr('Search').'">';
				$menu .= '</form>';
				$menu .= '<li class="dropdown">'."\n";
				$menu .= '<a href="/admin/index.php" class="dropdown-toggle" data-toggle="dropdown">'."\n";
				$menu .= '<i class="icon-cog"></i>'.__tr("Server settings").' <b class="caret"></b></a>'."\n";
				$menu .= '<ul class="dropdown-menu">'."\n";
				$menu .= '<li><a href="/admin/server/index.php#plugins">'.__tr("Plugins").'</a></li>'."\n";
				$menu .= '<li><a href="/admin/server/index.php#bunnies">'.__tr("Bunnies").'</a></li>'."\n";
				$menu .= '<li><a href="/admin/server/index.php#ztamps">'.__tr("Ztamps").'</a></li>'."\n";
				$menu .= '<li><a href="/admin/server/index.php#accounts">'.__tr("Accounts").'</a></li>'."\n";
				$menu .= '<li><a href="/admin/faq.php">'.__tr("FAQ").'</a></li>'."\n";
				$menu .= '<li><a href="/admin/news.php">'.__tr("News").'</a></li>'."\n";
				$menu .= ' <li class="divider"></li>'."\n";
				$menu .= '<li><a href="/admin/server/rawapi.php">'.__tr("Raw API Call").'</a></li>'."\n";
				$menu .= '<li><a href="/admin/server/settings.php">'.__tr("Bunny settings").'</a></li>'."\n";
				$menu .= ' <li class="divider"></li>'."\n";
				$menu .= '<li><a href="/admin/translation/index.php">'.__tr("Translations").'</a></li>'."\n";
				$menu .= '<li><a href="/admin/sentences.php">'.__tr("Sentences").'</a></li>'."\n";
				$menu .= '<li><a href="/admin/language.php">'.__tr("Languages").'</a></li>'."\n";
				$menu .= ' <li class="divider"></li>'."\n";
				$menu .= '<li><a href="/admin/logins.php">'.__tr("Logins").'</a></li>'."\n";
				$menu .= '<li><a href="/admin/quota.php">'.__tr("Quotas").'</a></li>'."\n";
				$menu .= '<li><a href="/admin/member.php">'.__tr("Members").'</a></li>'."\n";
				$menu .= '<li><a href="/admin/donation.php">'.__tr("Donations").'</a></li>'."\n";
				$menu .= '<li><a href="/admin/gift.php">'.__tr("Gift codes").'</a></li>'."\n";
				$menu .= '<li><a href="/admin/server/silent.php">'.__tr("Silent bunnies").'</a></li>'."\n";
				$menu .= '<li><a href="/admin/server/status.php?online=1">'.__tr("Status").'</a></li>'."\n";
				$menu .= '</ul>'."\n";
				$menu .= '</li>'."\n";
			}

			$menu .= '<li class="dropdown">'."\n";
			$menu .= '<a href="/account/index.php" class="dropdown-toggle" data-toggle="dropdown">'."\n";
			$menu .= '<i class="icon-user"></i>'.$this->UInfos['username'].' <b class="caret"></b></a>'."\n";
			$menu .= '<ul class="dropdown-menu">'."\n";
			$menu .= '<li><a href="/account/index.php">'.__tr("My profile").' ( <i>'.__tr($this->UInfos['status']).'</i> )</a></li>'."\n";
			foreach(getTranslates($_SESSION['login']) as $lng)
				$menu .= '<li><a href="/admin/translation/edit.php?lng='.$lng.'">'.__tr("Translation (%1)", $lng).'</a></li>'."\n";
			$menu .= ' <li class="divider"></li>'."\n";
			$menu .= '<li><a href="/index.php?logout">'.__tr("Logout").'</a></li>'."\n";
			$menu .= '</ul>'."\n";
			$menu .= '</li>'."\n";

		}
		return $menu;
	}

	function getCSS()
	{
		return $this->css;
	}

	function setCSS($j)
	{
		$this->css = $j;
	}

	function getJS()
	{
		return $this->js;
	}

	function setJS($j)
	{
		$this->js = $j;
	}

	function makeFooter()
	{
		$ret = '';
		$ret .= '<div class="span3"><h4>'.__tr("About openJabNab").'</h4>'."\n";
		$ret .= '<ul>'."\n";
		$ret .= '<li><a href="/donate/index.php">'.__tr("About").'</a></li>'."\n";
		$ret .= '<li><a href="https://www.facebook.com/openjabnab.fr">'.__tr("Facebook").'</a></li>'."\n";
		$ret .= '<li><a href="https://twitter.com/openJabNab">'.__tr("Twitter").'</a></li>'."\n";
		$ret .= '<li><a target="_blank" href="https://github.com/OpenJabNab/OpenJabNab">'.__tr("GitHub").'</a></li>'."\n";
		$ret .= '<li><a target="_blank" href="http://nabaztag.forumactif.fr/">'.__tr("Forum").'</a></li>'."\n";
		$ret .= '</ul>'."\n";
		$ret .= '</div>'."\n";
		$ret .= '<div class="span3"><h4>'.__tr("Other informations").'</h4>'."\n";
		$ret .= '<ul>'."\n";
		//$ret .= '<li><a href="/donate/index.php">'.__tr("Credits").'</a></li>'."\n";
		$ret .= '<li><a href="/help/faq.php">'.__tr("FAQ").'</a></li>'."\n";
		$ret .= '</ul>'."\n";
		$ret .= '</div>'."\n";

		return $ret;
/*


    			<div class="span3">
    				<h4>About administrator</h4>
    				<ul>
    					<li><a href="javascript:;">About</a></li>
    					<li><a href="javascript:;">Contact</a></li>
    				</ul>
    			</div> <!-- /span3 -->

    			<div class="span3">
    				<h4>Support</h4>
    				<ul>
    					<li><a href="javascript:;">Frequently Asked Questions</a></li>
    					<li><a href="javascript:;">Ask a Question</a></li>
    					<li><a href="javascript:;">Feedback</a></li>
    				</ul>
    			</div> <!-- /span3 -->

    			<div class="span3">
    				<h4>Legal</h4>
    				<ul>
    					<li><a href="javascript:;">License</a></li>
    					<li><a href="javascript:;">Terms of Use</a></li>
    					<li><a href="javascript:;">Privacy Policy</a></li>
    					<li><a href="javascript:;">Security</a></li>
    				</ul>
    			</div> <!-- /span3 -->
*/
	}
}
function __tr($text)
{
	global $translations;
	if(isset($translations) && isset($translations[$text]))
		$text = $translations[$text];
	if(func_num_args() > 1)
	{
		foreach(func_get_args() as $k => $v)
		{
			if($k > 0)
			{
				$text = preg_replace("|%$k|", $v, $text);
			}
		}
	}
	return $text;
}
?>
