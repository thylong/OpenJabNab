<?php
class ojnApi {
	private $Bunnies;								/* Registered Bunnies */
	private $ConnectedBunnies;				/* Connected Bunnies */
	private $Plugins;								/* All available plugins */
	private $EnabledPlugins;						/* Active Plugins */
	private $BunnyPlugins;						/* Available plugins for a bunny */
	private $BunnyEnabledPlugins;			/* Enabled plugins for a bunny */
	private $ZtampPlugins;						/* Available plugins for a Ztamp */
	private $ZtampEnabledPlugins;			/* Enabled plugins for a Ztamp */

	private $BunnyActivePlugins;				/* Enabled plugins on a specific bunny */

	private $Ztamps;								/* Known Ztamps */
	private $ZtampActivePlugins;			/* Enabled plugins for a ztamp */

	private $Stats;

	private $log = array();

	public function getLog()
	{
		foreach($this->log as $url)
			echo $url."<br />";
	}

	public function __construct() {
	}

	public function getUptime() {
		global $Infos;
		
		// For non-authenticated users, return default value
		if(!isset($_SESSION['token']) || empty($_SESSION['token'])) {
			return "Server online";
		}
		
		// For authenticated users, try to get real uptime with fallback
		if(!apcu_fetch(APC_PREFIX.'ojn_uptime_'.$Infos['language'])) {
			$up = $this->getApiString("global/uptime?".$this->getToken());
			if(!isset($up['value'])) {
				// If API call fails, return fallback value and cache it
				apcu_store(APC_PREFIX.'ojn_uptime_'.$Infos['language'], "Server online", 60);
				return "Server online";
			}
			$up = $up['value'];
			$ret = "";
			if($up >= 60)
			{
				$min = (int)($up / 60);
				if($min >= 60)
				{
					$hour = (int)($min / 60);
					$min = $min - 60 * $hour;
					if($hour >= 24)
					{
						$day = (int)($hour / 24);
						$hour = $hour - 24 * $day;
						$ret = $day.($Infos['language'] == 'fr' ? "jour" : "day").($day>1?'s':'').' '.str_pad($hour, 2, "0", STR_PAD_LEFT)."h ".str_pad($min, 2, "0", STR_PAD_LEFT)."min";
					}
					else
					{
						$ret = $hour."h ".str_pad($min, 2, "0", STR_PAD_LEFT)."min";
					}
				}
				else
				{
					$ret = $min."min";
				}
			}
			else
			{
				$ret = str_pad($up, 2, "0", STR_PAD_LEFT)."s";
			}
			apcu_store(APC_PREFIX.'ojn_uptime_'.$Infos['language'], $ret, 60);
		}
		return apcu_fetch(APC_PREFIX.'ojn_uptime_'.$Infos['language']);
	}

	public function getAbout($reload = false)
	{
		$r = true;
		$cache = apcu_fetch(APC_PREFIX.'ojn_about',$r);
		if(!$r || $reload)
		{
			$cache = $this->getApiMapped('global/about');
			if(!empty($cache))
				apcu_store(APC_PREFIX.'ojn_about', $cache, 30);
		}
		return $cache;
	}

	public function getLasts($bunny)
	{
		$r = $this->loginAccount(ADMIN_API_USER, ADMIN_API_PWD, false);
		if(!strpos($r,"AD_")) {
			$this->GetApi('accounts/settoken?tk='.$r);
			return $this->getApiMapped("bunny/" . $bunny . "/getlasts?token=".$r);
		}
		return false;
	}


	public function getStats($reload = false) {
		$r = true;
		$cache = apcu_fetch(APC_PREFIX.'ojn_stats',$r);
		if(!$r || $reload)
		{
			$cache = $this->getApiString('global/stats');
			apcu_store(APC_PREFIX.'ojn_stats', $cache, 30);
		}
		return $cache;
	}

	public function getListOfZtamps($reload = false) {
		$r = true;
		//$cache = apcu_fetch(APC_PREFIX.'ojn_ztamps_'.$this->getToken(),$r);

		//if(!$r || $reload)
		//{
			$cache = $this->getApiMapped("ztamps/getListOfZtamps?".$this->getToken());
			//apcu_store(APC_PREFIX.'ojn_ztamps_'.$this->getToken(),$cache,15);
		//}
		return $cache;
	}

	public function getListOfBunnies($reload=false) {
		$r = true;
		$cache = apcu_fetch(APC_PREFIX.'ojn_bunnies_'.$this->getToken(),$r);
		if(!$r || $reload)
		{
			$cache = $this->getApiMapped("bunnies/getListOfBunnies?".$this->getToken());
			apcu_store(APC_PREFIX.'ojn_bunnies_'.$this->getToken(), $cache, 15);
		}
		return $cache;
	}

	public function getListOfAllAccounts($reload=false) {
		$r = true;
		$cache = apcu_fetch(APC_PREFIX.'ojn_all_accounts',$r);
		if(!$r || $reload)
		{
			$cache = $this->getApiMapped("accounts/GetUserlist?".$this->getToken());
			$r = apcu_store(APC_PREFIX.'ojn_all_accounts', $cache, 300);
		}
		return $cache;
	}

	public function getListOfAllConnectedAccounts($reload=false) {
		$r = true;
		$cache = apcu_fetch(APC_PREFIX.'ojn_all_connected_accounts',$r);
		if(!$r || $reload)
		{
			$cache = $this->getApiList("accounts/GetConnectedUsers?".$this->getToken());
			$r = apcu_store(APC_PREFIX.'ojn_all_connected_accounts', $cache, 60);
		}
		return $cache;
	}

	public function getListOfAllZtamps($reload=false) {
		$r = true;
		$cache = apcu_fetch(APC_PREFIX.'ojn_all_ztamps',$r);
		if(!$r || $reload)
		{
			$cache = $this->getApiMapped("ztamps/getListOfAllZtamps?".$this->getToken());
			$r = apcu_store(APC_PREFIX.'ojn_all_ztamps', $cache, 60);
		}
		return $cache;
	}

	public function getListOfAllBunnies($reload=false) {
		$r = true;
		$cache = apcu_fetch(APC_PREFIX.'ojn_all_bunnies',$r);
		if(!$r || $reload)
		{
			$cache = $this->getApiMapped("bunnies/getListofAllBunnies?".$this->getToken());
			$r = apcu_store(APC_PREFIX.'ojn_all_bunnies', $cache, 300);
		}
		return $cache;
	}

	public function getListOfAllConnectedBunnies($reload=false)	{
		$r = true;
		$cache = apcu_fetch(APC_PREFIX.'ojn_all_connected_bunnies',$r);
		if(!$r || $reload)
		{
			$cache = $this->getApiMapped("bunnies/getListofAllConnectedBunnies?".$this->getToken());
			$r = apcu_store(APC_PREFIX.'ojn_all_connected_bunnies', $cache, 60);
		}
		return $cache;
	}

	public function getListOfConnectedBunnies($reload)	{
		if(!apcu_fetch(APC_PREFIX.'ojn_connected_bunnies_'.$this->getToken()) || $reload)
			apcu_store(APC_PREFIX.'ojn_connected_bunnies_'.$this->getToken(), $this->getApiMapped("bunnies/getListOfConnectedBunnies?".$this->getToken()), 15);
		return apcu_fetch(APC_PREFIX.'ojn_connected_bunnies_'.$this->getToken());
	}

	public function getListOfEnabledPlugins($reload) {
		if(empty($this->EnabledPlugins) || $reload)
			$this->EnabledPlugins = $this->getApiList("plugins/getListOfEnabledPlugins?".$this->getToken());
		return $this->EnabledPlugins;
	}

	public function getListOfSystemPlugins($reload) {
		if(!apcu_fetch(APC_PREFIX.'ojn_system_plugins') || $reload)
			apcu_store(APC_PREFIX.'ojn_system_plugins', $this->getApiList("plugins/getListOfSystemPlugins?".$this->getToken()), 60);
		return apcu_fetch(APC_PREFIX.'ojn_system_plugins');
	}

	public function getListOfRequiredPlugins($reload) {
		if(!apcu_fetch(APC_PREFIX.'ojn_required_plugins') || $reload)
			apcu_store(APC_PREFIX.'ojn_required_plugins', $this->getApiList("plugins/getListOfRequiredPlugins?".$this->getToken()), 60);
		return apcu_fetch(APC_PREFIX.'ojn_required_plugins');
	}

	public function getListOfPlugins($reload, $lng = 'en') {
		if(!apcu_fetch(APC_PREFIX.'ojn_plugins_' . $lng) || $reload)
			apcu_store(APC_PREFIX.'ojn_plugins_' . $lng, $this->getApiMapped("plugins/getListOfPlugins?".$this->getToken()), 60);
		return apcu_fetch(APC_PREFIX.'ojn_plugins_' . $lng);
	}

	public function loginAsAccount($login, $count = true) {
		$loginAccount = $this->getApiString("accounts/authAs?login=".$login."&".$this->getToken());
		if(isset($loginAccount['error']))
			$loginAccount['value'] = $loginAccount['error'];//$loginAccount['error'] == 'BAD_LOGIN' ? 'BAD_LOGIN' : 'BAD_ACCOUNT';
		return $loginAccount['value'];
	}

	public function loginAccount($login, $pass, $count = true) {
		$loginAccount = $this->getApiString("accounts/auth?login=".urlencode($login)."&pass=".$pass.($count?'':'&notcount'));
		if(isset($loginAccount['error']))
			$loginAccount['value'] = $loginAccount['error'];//$loginAccount['error'] == 'BAD_LOGIN' ? 'BAD_LOGIN' : 'BAD_ACCOUNT';
		return isset($loginAccount['value']) ? $loginAccount['value'] : NULL;
	}

	public function getListOfBunnyPlugins($reload)	{
		if(empty($this->BunnyPlugins) || $reload)
			$this->BunnyPlugins = $this->getApiList("plugins/getListOfBunnyPlugins?".$this->getToken());
		return $this->BunnyPlugins;
	}

	public function getListOfBunnyEnabledPlugins($reload) {
		if(empty($this->BunnyEnabledPlugins) || $reload)
			$this->BunnyEnabledPlugins = $this->getApiList("plugins/getListOfBunnyEnabledPlugins?".$this->getToken());
		return $this->BunnyEnabledPlugins;
	}

	public function getListOfZtampPlugins($reload)	{
		if(empty($this->ZtampPlugins) || $reload)
			$this->ZtampPlugins = $this->getApiList("plugins/getListOfZtampPlugins?".$this->getToken());
		return $this->ZtampPlugins;
	}

	public function getListOfZtampEnabledPlugins($reload) {
		if(empty($this->ZtampEnabledPlugins) || $reload)
			$this->ZtampEnabledPlugins = $this->getApiList("plugins/getListOfZtampEnabledPlugins?".$this->getToken());
		return $this->ZtampEnabledPlugins;
	}

	public function bunnyListOfPlugins($serial,$reload) {
		if(empty($this->BunnyActivePlugins) || $reload)
			$this->BunnyActivePlugins = $this->getApiList('bunny/'.$serial.'/getListOfActivePlugins?'.$this->getToken());
		return $this->BunnyActivePlugins;
	}

	public function ztampListOfPlugins($serial,$reload) {
		if(empty($this->ZtampActivePlugins) || $reload)
			$this->ZtampActivePlugins = $this->getApiList('ztamp/'.$serial.'/getListOfActivePlugins?'.$this->getToken());
		return $this->ZtampActivePlugins;
	}

	public function getApiList($url) {
		return $this->transformList($this->getApi($url));
	}

	public function getApiMapped($url)	{
		return $this->transformMappedList($this->getApi($url));
	}

	public function getApiXMLArray($url) {
//echo "*".htmlentities($this->getApi($url))."*";
		return $this->XmlToArray($this->getApi($url));
	}

	private function getPluginsAttribute($attr)
	{
		$ret = array();
		foreach($this->getPlugins() as $p => $plugin)
			if($plugin[$attr] == "1")
				$ret[$p] = $plugin;
		return $ret;
	}

	public function getClickPlugins($type = "single")
	{
		return $this->getPluginsAttribute($type);
	}

	public function getRequiredPlugins()
	{
		return $this->getPluginsAttribute('required');
	}

	public function getSystemPlugins()
	{
		return $this->getPluginsAttribute('system');
	}

	public function getPlugins()
	{
		//apcu_delete(APC_PREFIX.'ojn_list_plugins');
		if(!apcu_fetch(APC_PREFIX.'ojn_list_plugins')) {
			$data = $this->get("plugins/getPlugins");
			$xml = $this->loadXmlString($data);
			$xml = ($xml->plugins->plugin);
			$ret = array();
			foreach($xml as $k => $v)
			{
				$plugin = array();
				$attr = $v->attributes();
				foreach($attr as $a => $aa)
					$plugin[$a] = (string)$aa;
				$plugin['name'] = (string)$v;
				$ret[$plugin['id']] = $plugin;
			}
			apcu_store(APC_PREFIX.'ojn_list_plugins', $ret, 60);
		}
		return apcu_fetch(APC_PREFIX.'ojn_list_plugins');
	}

	private function get($url) {
		$ip = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '127.0.0.1';
		if(preg_match('/,/', $ip)) {
			$ips = preg_split('/,/', $ip);
			$ip = trim($ips[0]);
		}
		$options  = array('http' => array(
			'header' => 'X-Forwarded-For: '.$ip,
			'user_agent' => 'OJN Admin',
			'timeout' => 30
		));
		$context  = stream_context_create($options);

		$this->log[] = $url;
		$content = file_get_contents(ROOT_WWW_API.$url, false, $context);
		
		// Handle file_get_contents failure with detailed error logging
		if($content === false) {
			$error = error_get_last();
			error_log("OpenJabNab API call failed - URL: " . $full_url . " - Error: " . ($error ? $error['message'] : 'Unknown error'));
			return NULL;
		}
		
		if($content == "Problem with OpenJabNab !")
			$content = NULL;
		return $content;
	}

	public function getApiRaw($url) {
		return $this->get($url);
	}

	private function getApi($url) {
		$r = $this->get($url);
		
		// Check for access denied errors and clear invalid token
		if ($r != NULL && strpos($r, '<error>Access denied</error>') !== false) {
			// Token is invalid, clear it to force re-authentication
			if (isset($_SESSION['token'])) {
				unset($_SESSION['token']);
				// Clear user cache to force fresh login
				if (isset($_SESSION['login'])) {
					apcu_delete(APC_PREFIX.'ojn_user_'.$_SESSION['login']);
				}
			}
		}
		
		return $r != NULL ? $this->loadXmlString($r) : NULL;
	}

	public function getApiValue($url) {
		$value = (array)$this->getApi($url);
		return isset($value['value']) ? $value['value'] : false;
	}

	public function getApiString($url) {
		return (array)$this->getApi($url);
	}

	private function getMappedList($url) {
		return $this->transformMappedList($this->getApi($url));
	}

	private function loadXmlString($string) {
		return simplexml_load_string($string, "SimpleXMLElement", LIBXML_NOCDATA);
	}

	public function setToken($token) {
		$this->GetApi('accounts/settoken?tk='.$token.'&'.$this->getToken());
		$_SESSION['token'] = $token;
	}

	public function getToken() {
		return isset($_SESSION['token']) ? 'token='.$_SESSION['token'] : '';
	}

	private function transformMappedList($mapped) {
		if(isset($mapped->list)) {
			$mapped = (array)$mapped->list->children();

			if(count($mapped)) {
				if(!is_array($mapped['item']))
					$mapped['item'] = array($mapped['item']);
				$mapped = $mapped['item'];
			}
			$temp = array();
			foreach($mapped as $item) {
				$item = (array)$item;
				if(is_string($item['key'])) {
					$temp[$item['key']] = is_array($item['value']) && count($item['value']) == 0 ? "" : $item['value'];
				}
			}
		} else
			$temp = false;
		return $temp;
	}

	private function transformValue($value) {
		if(isset($value->value))	{
			$value = (array)$value;
			$value = (string)$value['value'];
		} else
			$value = false;
		return $value;
	}

	private function transformList($list) {
		$list = (array)$list;
        if(isset($list['list']))
    		$list = (array)$list['list'];
		$temp = array();
        if(isset($list['item'])) {
                if(is_array($list['item'])) {
                foreach($list['item'] as $item)
                    $temp[] = $item;
            } else
                $temp = array($list['item']);
        }
		return $temp;
	}

	private function XmlToArray($xml) {
		if(is_object($xml))
		{
			$name = $xml->getName();
			$nbc = count($xml->children());
			$val = str_replace(array('>','<'),array('&gt;','&lt;'),(string)$xml);
			if($nbc == 0)
				$a=array($name => $val);
			else {
				$t =array();
				foreach($xml->children() as $nme => $xmlchild) {
					$t[]=$this->XmlToArray($xmlchild);
				}
				$a = array($name=>($nbc == 1 ? $t[0] : $t));
			}
			return $a;
		}
		return array();
	}

}
?>
