<?php
require_once '../include/common.php';
if(isset($_SESSION['token']))
{
ob_end_clean();
$url = "http://locdrop.query.yahoo.com/v1/public/yql?q=" . preg_replace(array("| |"), array("%20"), "select city, state, country, woeid from locdrop.placefinder where text='".addslashes($_GET['city'])."' and locale='fr' and gflags='f'");
$content = file_get_contents($url);
$xml = simplexml_load_string($content);
$results = (array)$xml->results;
if(isset($results['Result']) && $results['Result'] instanceof SimpleXMLElement)
	$results['Result'] = array($results['Result']);
if(count($results))
{
?>
<table>
<?php
foreach($results['Result'] as $key => $result)
{
$name = array();
if(strlen($result->city))
	$name[] = $result->city;
if(strlen($result->state))
	$name[] = $result->state;
$name[] = $result->country;
$name = implode(", ", $name);
echo "<tr><td>";
echo $name;
echo "</td><td>";
echo "<a class='btn btn-mini' onclick=\"useCity(".$result->woeid.", '".addslashes($name)."')\">".__tr("Use this city")."</a>";
echo "</td></tr>";
}
?>
</table>
<?php
}
}
else
ob_end_clean();
?>
