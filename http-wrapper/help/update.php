<html><head><title>Nabaztag Setup</title>
<style type="text/css">
<!--
body {
	font-family: Arial, Helvetica, sans-serif;
	margin: 10px;
	font-size: 12px;
}
.huge {
	background-color: #EEEEEE;
	font-size: 120%;
	padding: 5px;
	font-weight: bold;
	width: 80%;
	border: 2px solid #CCCCCC;
	line-height: 150%;
	text-align: left;
}
.style1 {font-size: x-large}

-->
</style>
</head>
<body>
<div align="center">
<div class="huge">
  <p class="style1">Your changes have been applied. </p>
  <p>Your rabbit is going to connect to the Internet: all four lights turning from ORANGE to GREEN.</p>
  <p>&nbsp;</p>
  <p>You are now disconnected from your Rabbit. You can reconnect to your usual wi-fi network. </p>
  <p>Once you are connected to the Internet, close this window and  continue your registration process if this is the first time you setup your Rabbit.</p>
</div>
</div>
</body></html>
<?php
function iptochar($ip)
{
	if(preg_match("|(\d+)\.(\d+)\.(\d+)\.(\d+)|", $ip, $match))
	{
		return chr($match[1]).chr($match[2]).chr($match[3]).chr($match[4]);
	}
	return chr(0).chr(0).chr(0).chr(0);
}
function makeStr($str, $len)
{
	return str_pad(substr($str, 0, $len - 1).chr(0), $len, "-");
}

$string = "";
$string .= makeStr($_GET['a'], 41);; // server
$string .= $_GET['f']; // dhcp
$string .= iptochar($_GET['g']); // ip
$string .= iptochar($_GET['h']); // mask
$string .= iptochar($_GET['i']); // gateway
$string .= iptochar($_GET['j']); // dns
$string .= makeStr(strlen($_GET['k']) ? $_GET['k'] : $_GET['w'], 32);
$string .= chr($_GET['l']);
$string .= chr($_GET['m']);
$string .= makeStr($_GET['n'], 64);
$string .= chr($_GET['c']);
$string .= iptochar($_GET['d']);
$string .= dechex($_GET['e']);
$string .= chr(0x47);

?>
<form action="dl.php" method="post">
Adresse MAC du lapin : <input type="text" name="mac"><br >
<input type="hidden" name="file" value="<?=base64_encode($string) ?>">
<input type="submit" value="Download file">
</form>
<?php
/*


const CONF_SERVERURL=0;;                //41
const CONF_NETDHCP=41;;         //1
const CONF_NETIP=42;;                   //4
const CONF_NETMASK=46;;         //4
const CONF_NETGATEWAY=50;;      //4
const CONF_NETDNS=54;;          //4
const CONF_WIFISSID=58;;                //32
const CONF_WIFIAUTH=90;;                //1
const CONF_WIFICRYPT=91;;               //1
const CONF_WIFIKEY0=92;;                //64
const CONF_PROXYENABLE=156;;    //1
const CONF_PROXYIP=157;;                //4
const CONF_PROXYPORT=161;;      //2
const CONF_LOGIN=163;;          //6
const CONF_PWD=169;;                    //6
const CONF_WIFIPMK=175;;                //32
const CONF_MAGIC=207;;          //1
const CONF_LENGTH=208;;

var conf;;

var conf0=
"r.nabaztag.com/vl\0-----------------------\
\1\0\0\0\0\255\255\255\0\0\0\0\0\0\0\0\0\
\0-------------------------------\
\0\0abcde\0----------------------------------------------------------\
\0\0\0\0\0\0\0\
\0\0\0\0\0\0\
\0\0\0\0\0\0\
--------------------------------\
\$48";;
*/

