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
				$this->getCSS().'?'.time(),
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
		$menu = '<ul class="navbar-nav mr-auto">'."\n";
		$active = basename($_SERVER['PHP_SELF'], ".php");
		if(strpos($active, "_"))
			$active = substr($active, 0, strpos($active, "_") );
		$menu .= '  <li class="nav-item'.($active == 'index' ? ' active' : '').'"><a class="nav-link" href="/index.php">'.__tr("Home").'</a></li>'."\n";
		if(isset($_SESSION['token']))	{
			$menu .= '  <li class="nav-item dropdown'.($active == 'bunny' ? ' active' : '').'">'."\n";

			$bunnies = $this->Api->getListOfBunnies(false);
			if(!empty($bunnies))
			{
				if(count($bunnies) == 1)
				{
					$mac = array_keys($bunnies);
					$menu .= '    <a class="nav-link" href="/bunny/?b='.$mac[0].'">'.__tr("Bunny").'</a>';
				}
				else
				{
					$menu .= '    <a class="nav-link dropdown-toggle" id="bunny_dd" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'.__tr("Bunnies").'</a>'."\n";
					$menu .= '    <div class="dropdown-menu" aria-labelledby="bunny_dd">'."\n";
					$menu .= '      <a class="dropdown-item" href="/bunny/index.php?b=clear">'.__tr("List").'</a>'."\n";
					$menu .= '      <div class="dropdown-divider"></div>'."\n";
					foreach($bunnies as $mac => $bunny)
					{
						$menu .= '      <a class="dropdown-item" href="/bunny/index.php?b='.$mac.'" alt="'.$mac.'" title="'.$mac.'">'.($bunny != "Bunny" ? $bunny : $mac).'</a>'."\n";
					}
					$menu .= '    </div>'."\n";
				}
			}
			else
			{
				$menu .= '    <a class="nav-link" href="/bunny/">'.__tr("Bunnies").'</a>';
			}
			$menu .= '  </li>'."\n";
			$menu .= '  <li class="nav-item '.($active == 'ztamp' ? ' active' : '').'"><a class="nav-link" href="/account/ztamp.php?z=clear">'.__tr("Ztamps").'</a></li>';
		}
		$menu .= '  <li class="nav-item '.($active == 'stats' ? ' active' : '').'"><a class="nav-link" href="/stats.php">'.__tr("Statistics").'</a></li>'."\n";
		$menu .= '  <li class="nav-item'.($active == 'map' ? ' active' : '').'"><a class="nav-link" href="/map.php">'.__tr("Map").'</a></li>'."\n";
		$menu .= '  <li class="nav-item'.($active == 'wiki' ? ' active' : '').'"><a class="nav-link" href="http://wiki.openjabnab.fr">'.__tr("Wiki").'</a></li>'."\n";
		$menu .= '  <li class="nav-item'.($active == 'help/index' ? ' active' : '').'"><a class="nav-link" href="/help/">'.__tr("Help").'</a></li>'."\n";
		$menu .= '</ul>'."\n";
		$menu .= '<ul class="navbar-nav donate">'."\n";
		$menu .= '  <li class="nav-item"><a class="nav-link" href="/donate/index.php">'.__tr("Help the project").'</a></li>'."\n";
		$menu .= '  <li class="nav-item"><a class="nav-link" href="/donate/premium.php">'.__tr("Premium status").'</a></li>'."\n";
		$menu .= '</ul>'."\n";
		return $menu;
	}

	private function makeUserMenu()	{
		$menu = "";
		if(isset($this->UInfos['token']) && $this->UInfos['token'] != '')
		{
			if($this->UInfos['isAdmin']) {
				$menu .= '<form class="form-inline my-2 my-lg-0" action="/admin/search.php" method="post">'."\n";
				$menu .= '  <input type="search" class="form-control search-query" name="search" placeholder="'.__tr('Search').'">'."\n";
				$menu .= '</form>'."\n";
				$menu .= '<li class="nav-item dropdown">'."\n";
				$menu .= '  <a class="nav-link dropdown-toggle" id="admin_dd" href="/admin/index.php" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'."\n";
				$menu .= '    <i class="icon-cog"></i> '.__tr("Server settings")."\n";
				$menu .= '  </a>'."\n";
				$menu .= '  <div class="dropdown-menu dropdown-menu-right" aria-labelledby="admin_dd">'."\n";
				$menu .= '    <a class="dropdown-item" href="/admin/server/index.php#plugins">'.__tr("Plugins").'</a>'."\n";
				$menu .= '    <a class="dropdown-item" href="/admin/server/index.php#bunnies">'.__tr("Bunnies").'</a>'."\n";
				$menu .= '    <a class="dropdown-item" href="/admin/server/index.php#ztamps">'.__tr("Ztamps").'</a>'."\n";
				$menu .= '    <a class="dropdown-item" href="/admin/server/index.php#accounts">'.__tr("Accounts").'</a>'."\n";
				$menu .= '    <a class="dropdown-item" href="/admin/faq.php">'.__tr("FAQ").'</a>'."\n";
				$menu .= '    <a class="dropdown-item" href="/admin/news.php">'.__tr("News").'</a>'."\n";
				$menu .= '    <div class="dropdown-divider"></div>'."\n";
				$menu .= '    <a class="dropdown-item" href="/admin/server/rawapi.php">'.__tr("Raw API Call").'</a>'."\n";
				$menu .= '    <a class="dropdown-item" href="/admin/server/settings.php">'.__tr("Bunny settings").'</a>'."\n";
				$menu .= '    <div class="dropdown-divider"></div>'."\n";
				$menu .= '    <a class="dropdown-item" href="/admin/translation/index.php">'.__tr("Translations").'</a>'."\n";
				$menu .= '    <a class="dropdown-item" href="/admin/sentences.php">'.__tr("Sentences").'</a>'."\n";
				$menu .= '    <a class="dropdown-item" href="/admin/language.php">'.__tr("Languages").'</a>'."\n";
				$menu .= '    <div class="dropdown-divider"></div>'."\n";
				$menu .= '    <a class="dropdown-item" href="/admin/logins.php">'.__tr("Logins").'</a>'."\n";
				$menu .= '    <a class="dropdown-item" href="/admin/quota.php">'.__tr("Quotas").'</a>'."\n";
				$menu .= '    <a class="dropdown-item" href="/admin/member.php">'.__tr("Members").'</a>'."\n";
				$menu .= '    <a class="dropdown-item" href="/admin/donation.php">'.__tr("Donations").'</a>'."\n";
				$menu .= '    <a class="dropdown-item" href="/admin/gift.php">'.__tr("Gift codes").'</a>'."\n";
				$menu .= '    <a class="dropdown-item" href="/admin/server/silent.php">'.__tr("Silent bunnies").'</a>'."\n";
				$menu .= '    <a class="dropdown-item" href="/admin/server/status.php?online=1">'.__tr("Status").'</a>'."\n";
				$menu .= '  </div>'."\n";
				$menu .= '</li>'."\n";
			}

			$menu .= '<li class="nav-item dropdown">'."\n";
			$menu .= '  <a class="nav-link dropdown-toggle" id="user_dd" href="/account/index.php" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'."\n";
			$menu .= '    <i class="'.(empty($_SESSION['logged_from']) ? 'icon-user' : 'icon-info-sign').'"></i> '.$this->UInfos['username']."\n";
			$menu .= '  </a>'."\n";
			$menu .= '  <div class="dropdown-menu dropdown-menu-right" aria-labelledby="user_dd">'."\n";
			$menu .= '    <a class="dropdown-item" href="/account/index.php">'.__tr("My profile").' ( <i>'.__tr($this->UInfos['status']).'</i> )</a>'."\n";
			foreach(getTranslates($_SESSION['login']) as $lng)
				$menu .= '    <a class="dropdown-item" href="/admin/translation/edit.php?lng='.$lng.'">'.__tr("Translation (%1)", $lng).'</a>'."\n";
			$menu .= '    <div class="dropdown-divider"></div>'."\n";
			$menu .= '    <a class="dropdown-item" href="/index.php?logout">'.__tr("Logout").'</a>'."\n";
			$menu .= '  </div>'."\n";
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
		$ret = '<div class="row justify-content-md-center">'."\n";
		$ret .= '  <div class="col-md-3 mt-md-0 mt-3">'."\n";
		$ret .= '    <h5>'.__tr("About openJabNab").'</h5>'."\n";
		$ret .= '    <ul>'."\n";
		$ret .= '      <li><a href="/donate/">'.__tr("About").'</a></li>'."\n";
		$ret .= '      <li><a target="_blank" href="https://www.facebook.com/openjabnab.fr">'.__tr("Facebook").'</a></li>'."\n";
		$ret .= '      <li><a target="_blank" href="https://twitter.com/openJabNab">'.__tr("Twitter").'</a></li>'."\n";
		$ret .= '      <li><a target="_blank" href="https://github.com/OpenJabNab/OpenJabNab">'.__tr("GitHub").'</a></li>'."\n";
		$ret .= '      <li><a target="_blank" href="http://nabaztag.forumactif.fr/">'.__tr("Forum").'</a></li>'."\n";
		$ret .= '    </ul>'."\n";
		$ret .= '  </div>'."\n";
		$ret .= '  <div class="col-md-3 mb-md-0 mb-3">'."\n";
		$ret .= '    <h5>'.__tr("Other informations").'</h5>'."\n";
		$ret .= '    <ul>'."\n";
		//$ret .= '      <li><a href="/donate/index.php">'.__tr("Credits").'</a></li>'."\n";
		$ret .= '      <li><a href="/help/faq.php">'.__tr("FAQ").'</a></li>'."\n";
		$ret .= '    </ul>'."\n";
		$ret .= '  </div>'."\n";
		$ret .= '</div>'."\n";

		return $ret;
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
