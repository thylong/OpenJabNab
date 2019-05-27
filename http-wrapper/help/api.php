<?php
require_once "include/common.php";
require('include/message.php');
$ojnTemplate->setTitle(__tr('API Help'));
$bunny = "XXXXXXXXXXXX";
$token = "YYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYY";
$limit = 5;

if(isset($_SESSION['bunny']))
{
	$bunny = $_SESSION['bunny'];
	$token = $ojnAPI->getApiString("bunny/".$bunny."/getVAPIToken?".$ojnAPI->getToken());
	$token = isset($token['value']) ? $token['value'] : 'YYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYY';
}

$xml = $ojnAPI->getApiRaw('plugins/getPlugins?lng='.$Infos['language']);
$plugins = simplexml_load_string($xml);

$list = array();

foreach($plugins->plugins->plugin as $plugin)
{
	$p = array();
	$p['name'] = (string)$plugin;
	$attrs = (array)$plugin->attributes();
	foreach($attrs['@attributes'] as $key => $value)
	{
		$p[$key] = trim($value);
	}

	$list[$p['id']] = $p['name'];
}

$plugins = simplexml_load_string($ojnAPI->getApiRaw("plugins/getPluginsApiCall?".$ojnAPI->getToken()));
$plugins = $plugins->plugins;
?>
<style>
h4 {
	margin-top: 20px;
}
h5 {
	margin-top: 15px;
}
</style>
<div class="row">
    <div class="span12">
        <div class="widget widget">
            <div class="widget-header">
                <i class="icon-list-alt"></i><h3><?php echo __tr("Violet API") ?></h3>
            </div> 
            <div class="widget-content">
		<p><?php echo __tr("Each call need at least two parameters :") ?></p>
		<ul>
            		<li>sn (<?php echo __tr("MAC address") ?>)</li>
            		<li>token (<?php echo __tr("Violet API Token") ?>)</li>
		</ul>
		<h4><?php echo __tr("Classic API") ?>, <?php echo __tr('Actions') ?></h4>
        <p><?php echo __tr('You can send only one action request by call') ?></p>
		<p><?php echo __tr("All answers are in the following format:") ?></p>
		<pre>&lt;?xml version="1.0" encoding="UTF-8"?&gt;&lt;rsp&gt;RETURN&lt;/rsp&gt;</pre>

		<h5><?php echo __tr("List friends") ?></h5>
		<i><?php echo __tr("Function is available, but feature is not implemented") ?></i>
		<pre>http://openjabnab.fr/ojn/FR/api.jsp?sn=<?php echo $bunny ?>&token=<?php echo $token ?>&action=2</pre>
		<p><?php echo __tr("Return example:") ?></p>
		<pre class="return">&lt;listfriend nb="0"/&gt;</pre>

		<h5><?php echo __tr("List of received messages") ?></h5>
		<i><?php echo __tr("Function is available, but feature is not implemented") ?></i>
		<pre>http://openjabnab.fr/ojn/FR/api.jsp?sn=<?php echo $bunny ?>&token=<?php echo $token ?>&action=3</pre>
		<p><?php echo __tr("Return example:") ?></p>
		<pre class="return">&lt;listreceivedmsg nb="0"/&gt;</pre>

		<h5><?php echo __tr("Get bunny timezone") ?></h5>
		<pre>http://openjabnab.fr/ojn/FR/api.jsp?sn=<?php echo $bunny ?>&token=<?php echo $token ?>&action=4</pre>
		<p><?php echo __tr("Return examples:") ?></p>
		<pre class="return">&lt;timezone&gt;UTC&lt;/timezone&gt;<br />&lt;timezone&gt;Europe/Paris&lt;/timezone&gt;</pre>

		<h5><?php echo __tr("List of blocked users") ?></h5>
		<i><?php echo __tr("Function is available, but feature is not implemented") ?></i>
		<pre>http://openjabnab.fr/ojn/FR/api.jsp?sn=<?php echo $bunny ?>&token=<?php echo $token ?>&action=6</pre>
		<p><?php echo __tr("Return example:") ?></p>
		<pre class="return">&lt;blacklist nb="0"/&gt;</pre>

		<h5><?php echo __tr("Bunny sleeping") ?></h5>
		<h6><?php echo __tr("Get status") ?></h6>
		<pre>http://openjabnab.fr/ojn/FR/api.jsp?sn=<?php echo $bunny ?>&token=<?php echo $token ?>&action=7</pre>
		<p><?php echo __tr("Returns:") ?></p>
		<pre class="return">&lt;rabbitSleep&gt;YES&lt;/rabbitSleep&gt;</pre>
		<i><?php echo __tr("or") ?></i>
		<pre class="return">&lt;rabbitSleep&gt;NO&lt;/rabbitSleep&gt;</pre>
		<h6><?php echo __tr("Wake up") ?></h6>
		<pre>http://openjabnab.fr/ojn/FR/api.jsp?sn=<?php echo $bunny ?>&token=<?php echo $token ?>&action=13</pre>
		<p><?php echo __tr("Returns:") ?></p>
		<pre class="return">&lt;message&gt;&lt;COMMANDSENT&gt;&lt;/message&gt;&lt;comment&gt;Your rabbit will change status&lt;/comment&gt;</pre>
		<h6><?php echo __tr("Go to sleep") ?></h6>
		<pre>http://openjabnab.fr/ojn/FR/api.jsp?sn=<?php echo $bunny ?>&token=<?php echo $token ?>&action=14</pre>
		<p><?php echo __tr("Returns:") ?></p>
		<pre class="return">&lt;message&gt;&lt;COMMANDSENT&gt;&lt;/message&gt;&lt;comment&gt;Your rabbit will change status&lt;/comment&gt;</pre>

		<h5><?php echo __tr("Bunny version") ?></h5>
		<pre>http://openjabnab.fr/ojn/FR/api.jsp?sn=<?php echo $bunny ?>&token=<?php echo $token ?>&action=8</pre>
		<p><?php echo __tr("Return example:") ?></p>
		<pre class="return">&lt;rabbitVersion&gt;V2&lt;/rabbitVersion&gt;</pre>

		<h5><?php echo __tr("Supported voices") ?></h5>
		<pre>http://openjabnab.fr/ojn/FR/api.jsp?sn=<?php echo $bunny ?>&token=<?php echo $token ?>&action=9</pre>
		<p><?php echo __tr("Return example:") ?></p>
		<pre class="return">&lt;voiceListTTS nb="1"/&gt;&lt;voice lang="fr" command="nabalive/fr"/&gt;</pre>

		<h5><?php echo __tr("Bunny name") ?></h5>
		<pre>http://openjabnab.fr/ojn/FR/api.jsp?sn=<?php echo $bunny ?>&token=<?php echo $token ?>&action=10</pre>
		<p><?php echo __tr("Return example:") ?></p>
		<pre class="return">&lt;rabbitName&gt;Bunny&lt;/rabbitName&gt;</pre>

		<h5><?php echo __tr("Bunny connected") ?></h5>
		<pre>http://openjabnab.fr/ojn/FR/api.jsp?sn=<?php echo $bunny ?>&token=<?php echo $token ?>&action=15</pre>
		<p><?php echo __tr("Returns:") ?></p>
		<pre class="return">&lt;rabbitConnected&gt;YES&lt;/rabbitConnected&gt;</pre>
		<i><?php echo __tr("or") ?></i>
		<pre class="return">&lt;rabbitConnected&gt;NO&lt;/rabbitConnected&gt;</pre>

		<h5><?php echo __tr("Last online") ?></h5>
		<pre>http://openjabnab.fr/ojn/FR/api.jsp?sn=<?php echo $bunny ?>&token=<?php echo $token ?>&action=16</pre>
		<p><?php echo __tr("Returns:") ?></p>
		<pre class="return">&lt;lastOnline&gt;<?php $date = time(); echo date('Y-m-d H:i:s', $date) ?>&lt;/lastOnline&gt;</pre>
		<i><?php echo __tr("or") ?></i>
		<pre class="return">&lt;rabbitConnected&gt;Never connected&lt;/rabbitConnected&gt;</pre>

		<h5><?php echo __tr("Reboot the bunny") ?></h5>
		<pre>http://openjabnab.fr/ojn/FR/api.jsp?sn=<?php echo $bunny ?>&token=<?php echo $token ?>&action=17</pre>
		<p><?php echo __tr("Returns:") ?></p>
		<pre class="return">&lt;message&gt;&lt;COMMANDSENT&gt;&lt;/message&gt;&lt;comment&gt;Bunny is going to reboot !&lt;/comment&gt;</pre>

		<h5><?php echo __tr("Restart the bunny") ?> (<?php echo __tr("Soft reboot") ?>)</h5>
		<pre>http://openjabnab.fr/ojn/FR/api.jsp?sn=<?php echo $bunny ?>&token=<?php echo $token ?>&action=18</pre>
		<p><?php echo __tr("Returns:") ?></p>
		<pre class="return">&lt;message&gt;&lt;COMMANDSENT&gt;&lt;/message&gt;&lt;comment&gt;Bunny is going to restart !&lt;/comment&gt;</pre>

		<h5><?php echo __tr("Get list of records") ?></h5>
		<pre>http://openjabnab.fr/ojn/FR/api.jsp?sn=<?php echo $bunny ?>&token=<?php echo $token ?>&action=19</pre>
		<p><?php echo __tr("Returns:") ?></p>
		<pre class="return">&lt;recordList nb="1" offset="0" limit="20"&gt;&lt;record file="http://openjabnab.fr/ojn_local/plugins/record/record_<?php echo $bunny ?>_<?php echo date('Ymd') ?>_<?php echo date('His') ?>.wav"/&gt;&lt;/recordList&gt;</pre>

		<h4><?php echo __tr("Classic API") ?>, <?php echo __tr('Other') ?></h4>
        <p><?php echo __tr('You can add as many parameters as you want for each call') ?></p>

		<h5><?php echo __tr("Left ear position") ?></h5>
		<pre>http://openjabnab.fr/ojn/FR/api.jsp?sn=<?php echo $bunny ?>&token=<?php echo $token ?>&posleft=<?php echo __tr('value') ?></pre>
        <p><?php echo __tr("'%1' is a value between %2 and %3", __tr('value'), 0, 16) ?></p>

		<h5><?php echo __tr("Right ear position") ?></h5>
		<pre>http://openjabnab.fr/ojn/FR/api.jsp?sn=<?php echo $bunny ?>&token=<?php echo $token ?>&posright=<?php echo __tr('value') ?></pre>
        <p><?php echo __tr("'%1' is a value between %2 and %3", __tr('value'), 0, 16) ?></p>

		<h5><?php echo __tr("Voice for TTS") ?></h5>
		<pre>http://openjabnab.fr/ojn/FR/api.jsp?sn=<?php echo $bunny ?>&token=<?php echo $token ?>&voice=claire</pre>

		<h5><?php echo __tr("TTS") ?></h5>
		<pre>http://openjabnab.fr/ojn/FR/api.jsp?sn=<?php echo $bunny ?>&token=<?php echo $token ?>&tts=<?php echo urlencode(__tr('Hello little bunny')) ?></pre>

		<h4><?php echo __tr("Streaming API") ?></h4>
		<pre>http://openjabnab.fr/ojn/FR/api_stream.jsp?sn=<?php echo $bunny ?>&token=<?php echo $token ?>&urlList=<?php echo urlencode('http://www.myserver.com/music/mymusic.mp3') ?></pre>
		<pre>http://openjabnab.fr/ojn/FR/api_stream.jsp?sn=<?php echo $bunny ?>&token=<?php echo $token ?>&urlList=<?php echo urlencode('http://www.myserver.com/music/mymusic_1.mp3') ?>|<?php echo urlencode('http://www.myserver.com/music/mymusic_2.mp3') ?></pre>
		<h4><?php echo __tr("Extended API") ?></h4>
		<p><?php echo __tr("Extended API is limited to 5 calls a day for user that do not subscribe to premium status.") ?></p>
<?php
	foreach($plugins->plugin as $plugin) {
		$attributes = $plugin->Attributes();
		$name = (string)$attributes->id;
?>
		<h5><?php echo $list[$name] ?></h5>
<?php
		foreach($plugin->function as $function)
		{
?>
		<pre>http://openjabnab.fr/ojn/FR/api.jsp?sn=<?php echo $bunny ?>&token=<?php echo $token ?>&plugin=<?php echo $name ?>&function=<?php echo $function ?></pre>
<?php
		}
?>
<?php
	}
?>

            </div> 
        </div>
    </div> 
</div>
<div class="row">
    <div class="span12">
        <div class="widget widget">
            <div class="widget-header">
                <i class="icon-list-alt"></i><h3><?php echo __tr("openJabNab API") ?></h3>
            </div> 
            <div class="widget-content">

            </div> 
        </div>
    </div> 
</div>
<?php
require_once "include/append.php";
?>
