<?php
$a = "r.nabaztag.com/vl";
$a = "openjabnab.fr/vl";
$b = "";
$c = "";
$d = "";
$g = "0.0.0.0";
if(count($_FILES))
{
	$content = file_get_contents($_FILES['uploadedfile']['tmp_name']);
	$a = substr($content, 0, strpos($content, chr(0), 0));
}
/*

// ------------- Config debut
var CONF_SERVERURL=0;;		//41
var CONF_NETDHCP=41;;		//1
var CONF_NETIP=42;;			//4
var CONF_NETMASK=46;;		//4
var CONF_NETGATEWAY=50;;	//4
var CONF_NETDNS=54;;		//4
var CONF_WIFISSID=58;;		//32
var CONF_WIFIAUTH=90;;		//1
var CONF_WIFICRYPT=91;;		//1
var CONF_WIFIKEY0=92;;		//64
var CONF_PROXYENABLE=156;;	//1
var CONF_PROXYIP=157;;		//4
var CONF_PROXYPORT=161;;	//2
var CONF_LOGIN=163;;		//6
var CONF_PWD=169;;			//6
var CONF_WIFIPMK=175;;		//32
var CONF_MAGIC=207;;		//1
var CONF_LENGTH=208;;

var conf;;

var conf0=
"r.nabaztag.com/vl\0-----------------------\
\1\0\0\0\0\255\255\255\0\0\0\0\0\0\0\0\0\
\0-------------------------------\
\0\0\0---------------------------------------------------------------\
\0\0\0\0\0\0\0\
\0\0\0\0\0\0\
\0\0\0\0\0\0\
--------------------------------\
\$47";;

*/
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Nabaztag Setup</title>

<style type="text/css">
<!--
body {
	font-family: Arial, Helvetica, sans-serif;
	margin: 10px;
	font-size: 12px;
}
.intro {
	background-color: #EEEEEE;
	font-size: 120%;
	padding: 5px;
	font-weight: bold;
	width: 60%;
	border: 2px solid #CCCCCC;
}
.header_light {
	background-color: #EEEEEE;
	color: #006699;
	padding: 4px;
	font-weight: bold;
}
.bloc_info {
	border: 1px solid #333333;
	font-size: 11px;
	color: #999999;
	font-weight: bold;
}
.bloc {
	border: thin solid #9900CC;
}
.header {
	background-color: #9900CC;
	font-size: 130%;
	font-weight: bold;
	color: #FFFFFF;
}
.subhead {
	background-color: #EEEEEE;
	font-weight: bold;
	color: #333333;
}
.caution {
	font-size: 12px;
	color: #0000FF;
	font-weight: bold;
}
.input_text {
	background-color: #EEEEEE;
	padding: 3px;
	border: 2px solid #CCCCCC;
	color: #666666;
}
.input_text_on {
	background-color: #FFFFDE;
	padding: 3px;
	border-top-width: 2px;
	border-right-width: 2px;
	border-bottom-width: 2px;
	border-left-width: 2px;
	border-top-style: solid;
	border-right-style: solid;
	border-bottom-style: solid;
	border-left-style: solid;
	border-top-color: #999999;
	border-right-color: #EEEEEE;
	border-bottom-color: #EEEEEE;
	border-left-color: #999999;
}
.hint {
	font-style: italic;
	color: #666666;
}
.button {
	font-weight: bold;
	background-color: #999999;
	border: 1px solid #333333;
	color: #FFFFFF;
}
a, a:visited, a:link {
	text-decoration: none;
	font-weight: bold;
	padding: 3px;
	color: #9900CC;
}
a:hover {
	background-color: #9900CC;
	color: #FFFFFF;
}
.spacer {
	margin-top: 1000px;
	margin-bottom: 0px;
}
.firmware {
	font-size: 11px;
	color: #666666;
	margin: 0px;
}
.firmware a {
	font-size: 11px;
	color: #666666;
}
.firmware a:hover {
	font-size: 11px;
	color: #FFFFFF;
}
.Huge {font-size: xx-large}

-->
</style>
</head>

<body>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr valign="top">
    <td class="intro">
	<p align="right" class="firmware"><a href="u.htm">Click here for firmware upgrade</a></p>
	<p><span class="Huge">You are now connected to your Nabaztag.</span><br />
      <span class="caution">(You are NOT connected to the Internet. To reconnect to the Internet restart your Nabaztag.)<br />
        </span><br />
        To configure your Rabbit, you will need the following information : 
      </p>
      <ul>
<li>Your SSID (the name of your network)</li>
</ul>

      <blockquote>
        <p><em>And if applicable : </em>
        </p>
      </blockquote>
      <ul>
<li>Your encryption type (WEP/WPA)</li>
<li>Your wi-fi encryption key (WEP/WPA)</li>
<li>Your authentication method (WEP)</li>
</ul>

      <p>5 Steps to connect your Nabaztag </p>
      <ol>
        <li>Click on &quot;click here to Start&quot; </li>
        <li>Enter the required information in the following page</li>
        <li>When finished click on ''Update and start&quot;</li>
        <li>Nabaztag will connect to the Internet : all four lights are going from ORANGE to GREEN</li>
        <li>Reconnect to your usual wi-fi network and  finish your registration. </li>
      </ol>      
      <p align="center"><a href="#basic">Click here to Start </a></p>
    </td>
  </tr>
</table>

<p class="spacer"></p>
<a name="basic" id="basic"></a>
<form method=GET name=form action="update.php">
<table width="100%" border="0" cellspacing="3" cellpadding="3" class="bloc">
  <tr>
    <td colspan="2" class="header">Basic configuration </td>
  </tr>
  <tr>
    <td colspan="2" class="subhead">SSID</td>
  </tr>
  <tr>
    <td width="33%"><select name="w" id="w" class="input_text" onblur="this.className='input_text'" onclick="this.className='input_text_on'">
	  <option value='-'>-- Select in the list --</option><option value='my'>Mon réseau Wifi</option>
    </select>
    <br />
        <br />
      OR type your network name :<br />
      <input name="k" type="text" id="k" value="<?php echo isset($k) ? $k : '' ?>" class="input_text" onblur="this.className='input_text'" onclick="this.className='input_text_on'"/></td>
    <td class="hint">Your wi-fi network should be in the list on the left.<br />
      If this is not the case, please type the name of your network in the  field.</td>
  </tr>
  <tr>
    <td colspan="2" class="subhead">Encryption</td>
  </tr>
  <tr>
    <td><input type="radio" name="m" value="0" />
      No encryption<br />
      <input type="radio" name="m" value="1" />
      WEP encryption<br />
      <input type="radio" name="m" value="2" />
      WPA encryption</td>
    <td class="hint">Select your network's encryption type.</td>
  </tr>
  <tr>
    <td colspan="2" class="subhead">Key</td>
  </tr>
  <tr>
    <td><input name="n" type="text" id="n" size=50 value="<?php echo isset($n) ? $n : '' ?>" class="input_text" onblur="this.className='input_text'" onclick="this.className='input_text_on'"/></td>
    <td class="hint">WEP : Key syntax is hexadecimal (10 or 26 chars) or ascii (5 or 13 chars)<br />
      WPA : Key syntax can be any string</td>
  </tr>

  <tr>
    <td colspan="2" align="center">&nbsp;</td>
  </tr>
  <tr>
    <td colspan="2" align="center"><input name="z" type="submit" class="button" id="z" value="Update and Start"/></td>
  </tr>
  <tr>
    <td colspan="2" align="right"><a href="#advanced">Advanced configuration</a> </td>
  </tr>
</table>
<p class="spacer"></p>
<a name="advanced" id="advanced"></a>
<table width="100%" border="0" cellpadding="3" cellspacing="3" class="bloc">
  <tr>
    <td colspan="2" class="header">Advanced configuration <em>(majority of users will not have to fill in this part)</em> </td>
  </tr>
  <tr>
  <td colspan="2" align="right"><a href="#basic">Back to basic setup</a>  </td>
  </tr>
  <tr>
    <td colspan="2" class="subhead">Authentication (WEP)</td>
  </tr>
  <tr>
    <td><select name="l" class="input_text" id="l" title="Choose your authentication method/Choisissez votre mode d'authentification" onblur="this.className='input_text'" onclick="this.className='input_text_on'">
     <option value="0">OpenSystem</option><option value="1">SharedKey</option> 
    </select></td>
    <td class="hint">Select  your authentication method.</td>
  </tr>  
  <tr>
    <td colspan="2" class="subhead">DHCP server </td>
  </tr>
  <tr>
    <td width="33%">DHCP enabled ?
      <select name="f"class="input_text" onblur="this.className='input_text'" onclick="this.className='input_text_on'" >
        <option value=1>Yes</option><option value=0">No</option>
      </select></td>
    <td class="hint">If your router gives  IP addresses automatically to the peripherals on your network, leave this option on 'Yes' </td>
  </tr>
  <tr>
    <td colspan="2" class="caution"><em>If you do not have DHCP server enabled, you do not need to fill in the following section </em></td>
  </tr>
  <tr>
    <td colspan="2" class="subhead">Local IP </td>
  </tr>
  <tr>
    <td><input type="text" name="g" value="<?php echo $g ?>" class="input_text" onclick="this.className='input_text_on'" onblur="this.className='input_text'" /></td>
    <td class="hint">Enter the static address assigned to your rabbit </td>
  </tr>
  <tr>
    <td colspan="2" class="subhead">Local Mask </td>
  </tr>
  <tr>
    <td><input type="text" name="h" value="255.255.255.0" class="input_text" onclick="this.className='input_text_on'" onblur="this.className='input_text'"/></td>
    <td class="hint">Enter the mask assigned to your rabbit </td>
  </tr>
  <tr>
    <td colspan="2" class="subhead">Local gateway</td>
  </tr>
  <tr>
    <td><input type="text" name="i" value="0.0.0.0" class="input_text" title="Enter your wifi key/Entrez votre cl&eacute; r&eacute;seau" onclick="this.className='input_text_on'" onblur="this.className='input_text'"/></td>
    <td class="hint">Enter the gateway IP address assigned to your rabbit </td>
  </tr>
  <tr>
    <td colspan="2" class="subhead">DNS Server </td>
  </tr>
  <tr>
    <td><input type="text" name="j" value="0.0.0.0" class="input_text" onclick="this.className='input_text_on'" onblur="this.className='input_text'"/></td>
    <td class="hint">Enter the IP address of the DNS assigned to your rabbit </td>
  </tr>
  <tr>
    <td colspan="2">&nbsp;</td>
  </tr>
  <tr>
    <td colspan="2" class="header">Proxy Server </td>
  </tr>
  <tr>
    <td colspan="2" class="subhead">HTTP Proxy </td>
  </tr>
  <tr>
    <td><select name="c" class="input_text" onclick="this.className='input_text_on'" onblur="this.className='input_text'">
        <option value=0">No</option><option value=1>Yes</option>
    </select></td>
    <td class="hint">If you are accessing the Internet through a proxy, set this option to Yes and fill in the following fields </td>
  </tr>
  <tr>
    <td colspan="2" class="subhead">Proxy IP address </td>
  </tr>
  <tr>
    <td><input type="text" name="d" value="" class="input_text" onclick="this.className='input_text_on'" onblur="this.className='input_text'"/></td>
    <td class="hint">Enter the proxy IP address </td>
  </tr>
  <tr>
    <td colspan="2" class="subhead">Proxy port</td>
  </tr>
  <tr>
    <td><input type="text" name="e" value="" class="input_text" onclick="this.className='input_text_on'" onblur="this.className='input_text'"/></td>
    <td class="hint">Enter the port number used for the proxy </td>
  </tr>
  <tr>
    <td colspan="2">&nbsp;</td>
  </tr>
  <tr>
    <td colspan="2" align="center"><input name="z2" type="submit" class="button" id="z2" value="Update and Start" /></td>
  </tr>
</table>
<p></p>
<table width="100%" border="0" cellpadding="2" class="bloc_info">
  <tr>
    <td colspan="2" class="header_light">General Info</td>
  </tr>
  <tr>
    <td>Serial number: </td>
    <td>AA:BB:CC:DD:EE:FF</td>
  </tr>
  <tr>
    <td>Violet Platform: </td>
    <td><input name="a" type="text" value="<?php echo $a ?>" size="30" /></td>
  </tr>
  <tr>
    <td>Login: </td>
    <td></td>
  </tr>
  <tr>
    <td>Password: </td>
    <td></td>
  </tr>
  <tr>
    <td>Firmware: </td>
    <td>0.0.0.10</td>
  </tr>
</table>
<p>&nbsp;</p>
</form>
</body>
</html>
<?php
/*
var page_done=
"<html><head><title>Nabaztag Setup</title>
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
*/
/*

"<html>
<head>
<meta http-equiv="content-type" content="text/html;charset=ISO-8859-1">
<title>Upgrade your Nabaztag</title> 
<style type="text/css">
<!--
body {
	font-family: Arial, Helvetica, sans-serif;
	margin: 10px;
	font-size: 12px;
}
.bloc {
	border: thin solid #9900CC;
	background-color: #EEEEEE;
}
.header {
	background-color: #9900CC;
	font-size: 100%;
	font-weight: bold;
	color: #FFFFFF;
}
.button {
	font-weight: bold;
	background-color: #999999;
	border: 1px solid #333333;
	color: #FFFFFF;
}
.firmware {
	font-size: 11px;
	color: #666666;
	margin: 0px;
}
.firmware a {
	font-size: 11px;
	color: #666666;
}
.firmware a:hover {
	font-size: 11px;
	color: #FFFFFF;
}
a, a:visited, a:link {
	text-decoration: none;
	font-weight: bold;
	padding: 3px;
	color: #9900CC;
}
a:hover {
	background-color: #9900CC;
	color: #FFFFFF;
}
-->
</style>
</head>
<body>
	<form method="POST" action="c" ENCTYPE="multipart/form-data">
	<table cellpadding="3" cellspacing="3" width="100%" class="bloc">
		<tr>
			<td class="header">Upgrade your Nabaztag</td>
		</tr>
<tr>
  <td><p align="right" class="firmware"><a href="a.htm">Go back to the setup page</a></p></td>
</tr>
		<tr>
			<td align="left">
				Select the upgrade file from your hard disk:		  </td>
	  </tr>
				<tr>
			<td align="left"><INPUT TYPE="FILE" NAME="mtenFWUpload" SIZE="40" MAXLENGTH="128" value=""></td>
			</tr>
<tr>
  <td></td>
</tr>
		<tr>
			<td align="center"><input NAME="Upgrade" type="SUBMIT" class="button" VALUE="Upload" >
			</td>
	  </tr>
	</table>
</form>
</body>
</html>
*/
/*
<html><head><title>Nabaztag Setup</title>
<style type="text/css">
<!--
body {
	font-family: Arial, Helvetica, sans-serif;
	margin: 10px;
	font-size: 12px;
}
.intro {
	background-color: #EEEEEE;
	font-size: 120%;
	padding: 5px;
	font-weight: bold;
	width: 80%;
	border: 2px solid #CCCCCC;
	line-height: 150%;
	text-align: left;
}
a, a:visited, a:link {
	text-decoration: none;
	font-weight: bold;
	padding: 3px;
	color: #9900CC;
}
a:hover {
	background-color: #9900CC;
	color: #FFFFFF;
}
-->
</style>
</head><body>
<div align="center">
<div class="intro">Oooops, something went wrong ! <br>
  <br>
  <a href="a.htm">Go back to the setup page</a></div>
</div>
</body></html>
*/

/*
fun filterweb val=
	strreplace (strreplace val """ "&quot;") "<" "&lt;";;

fun webcrypt i=
	if i==IEEE80211_CRYPT_NONE then "No encryption"
	else if i==IEEE80211_CRYPT_WEP64 then "Wep64 encryption"
	else if i==IEEE80211_CRYPT_WEP128 then "Wep128 encryption"
	else if i==IEEE80211_CRYPT_WPA then "WPA-PSK encryption"
	else "Unkown encryption";;

fun isinscan s l=
	if l==nil then 0
	else let hd l->[ssid _ _ _ _ _ _] in
	if !strcmp ssid s then 1 else isinscan s tl l;;

fun selectscan s l=
	if l!=nil then let hd l->[ssid mac bssid rssi channel rateset encryption] in
	"<option value=""::(filterweb ssid)::"""::(if !strcmp ssid s then " selected")::">"::
	(filterweb ssid)::" - "::(webcrypt encryption)::" - Channel "::(itoa channel)::" - Power "::(itoa rssi)::"</option>"::selectscan s tl l;;



fun pagefill l p=
	if l==nil then p
	else let hd l ->[key val] in pagefill tl l listreplacestr p key val;;

fun webSelect yes=strcatlist "<option value=1"::(if yes then " selected")::
	">Yes</option><option value=0"::(if !yes then " selected")::">No</option>"::nil;;

fun _webSelectList val l=
	if l!=nil then let hd l->[v txt] in
	"<option value="::(itoa v)::(if v==val then " selected")::">"::(filterweb txt)::"</option>"::_webSelectList val tl l;;

fun webSelectList val l=strcatlist _webSelectList val l;;



fun httpdone=
	page_done;;

fun httpupgrade=
	page_u;;


fun httpindex=
	let webmac netMac -> mac in
	let webmac confGetLogin -> login in
	let webmac confGetPwd -> pwd in
	let confGetServerUrl -> server in
	let confGetWifissid -> ssid in
	let confGetWificrypt-> crypt in
	let confGetWifikey0 -> key in
	let confGetWifiauth -> auth in
	let confGetDhcp -> dhcp in
	let webip confGetNetip -> netip in
	let webip confGetNetmask -> netmask in
	let webip confGetNetgateway -> netgateway in
	let webip confGetNetdns -> netdns in
	let confGetProxy -> proxy in
	let webip confGetProxyip -> proxyip in
	let confGetProxyport -> proxyport in

	strcatlist pagefill
			["<MAC>" mac]::
			["<LOGIN>" filterweb login]::
			["<PWD>" filterweb pwd]::
			["<SERVER>" filterweb server]::
			["<DHCP>" webSelect dhcp]::
			["<NETIP>" netip]::
			["<NETMSK>" netmask]::
			["<NETGW>" netgateway]::
			["<NETDNS>" netdns]::
			["<PROXYIP>" proxyip]::
			["<PROXYPORT>" itoa proxyport]::
			["<PROXY>" webSelect proxy]::
			["<SSID>" if (!isinscan ssid wifiscans) then filterweb ssid]::
			["<SCAN>" strcatlist selectscan ssid wifiscans]::
			["<ENC-NONE>" if crypt==0 then "checked"]::
			["<ENC-WEP>" if crypt==1 then "checked"]::
			["<ENC-WPA>" if crypt==2 then "checked"]::
			["<AUTH>" webSelectList auth [0 "OpenSystem"]::[1 "SharedKey"]::nil]::
			["<KEY>" filterweb key]::
			["<FIRMWARE>" webip FIRMWARE]::

			nil
		page_a
	;;



fun useparamcheck val=
	let strget val 0 -> i in
	let if i==nil then '0' else i -> i in
	ctoa i-'0';;

fun _useparammac val i len=
	if i<len then
	(htoi strsub val i 2)::_useparammac val i+2 len;;

fun useparammac val=
	let strreplace val ":" "" -> val in
	listtostr _useparammac val 0 12;;

fun useparam v val=
	if v=='a' then confSetstr CONF_SERVERURL val 40
	else if v=='c' then confSet CONF_PROXYENABLE useparamcheck val 1
	else if v=='d' then confSet CONF_PROXYIP useparamip val 4
	else if v=='e' then confSet CONF_PROXYPORT atoibin2 val 2
	else if v=='f' then confSet CONF_NETDHCP useparamcheck val 1
	else if v=='g' then confSet CONF_NETIP useparamip val 4
	else if v=='h' then confSet CONF_NETMASK useparamip val 4
	else if v=='i' then confSet CONF_NETGATEWAY useparamip val 4
	else if v=='j' then confSet CONF_NETDNS useparamip val 4
	else if v=='k' then (if val!=nil && strlen val then confSetstr CONF_WIFISSID val 32)
	else if v=='w' then (if strcmp val "-" then confSetstr CONF_WIFISSID val 32)
	else if v=='l' then confSet CONF_WIFIAUTH useparamcheck val 1
	else if v=='m' then confSet CONF_WIFICRYPT useparamcheck val 1
	else if v=='n' then confSetstr CONF_WIFIKEY0 val 64
	else if v=='o' then confSet CONF_LOGIN useparammac val 4
	else if v=='p' then confSet CONF_PWD useparammac val 4
	else if v=='z' then (set master=-40; nil)
	;;

fun filterplus s=
	let strlen s -> n in
	for i=0;i<n do if (strget s i)=='+' then strset s i 32;
	s;;

fun filterpercent s i0=
	let strstr s "%" i0 -> i in
	if i==nil then (strsub s i0 nil)::nil
	else (strsub s i0 i-i0)::(ctoa htoi strsub s i+1 2)::(filterpercent s i+3);;

fun extractargs uri i=
	let strstr uri "=" i-> j in
	if j!=nil then let strstr uri "&" j-> k in
	let if k==nil then strlen uri else k -> k in
	[(strget uri i) strcatlist filterpercent filterplus strsub uri j+1 k-j-1 0]::extractargs uri k+1;;

fun extractpage uri=
	let strget uri 1 -> x in
	if x=='b' then 1
	else if x=='c' then 2
	else if x=='d' then 3
	else if x=='u' then 4
	else 0;;

fun uriextract uri =
	let strstr uri "?" 0 -> i in
	if i==nil then [extractpage uri nil]
	else [extractpage strsub uri 0 i extractargs uri i+1];;

fun updateconf args=
	let confGetWifissid -> ssid0 in
	let confGetWificrypt-> crypt0 in
	let confGetWifikey0 -> key0 in
	(
		Secholn "args :";
		for l=args;l!=nil;tl l do let hd l->[n v] in
			(useparam n v;Iecho n; Secho ":";Secho v;Secholn "<");
		if args!=nil then confSave;
		let confGetWifissid -> ssid in
		let confGetWificrypt-> crypt in
		let confGetWifikey0 -> key in
		if crypt==2 &&
		((crypt0!=crypt)||(strcmp ssid0 ssid)||(strcmp key0 key))
		then	// recalculer le pmk
		(
			Secholn "compute pmk";
			confSetbin CONF_WIFIPMK dump (netPmk ssid key) 32;
			setleds 0xff;
			confSave
		)
	);;
			

var firmwarelimit="-violet-";;

fun getbinary bin src i off len=
	if i<len then
	(
		strset bin i (htoi strsub src off 2);
		getbinary bin src i+1 off+2 len
	);;

fun getfirmware req=
	let strstr req firmwarelimit 0 -> i0 in
	if i0!=nil then let i0+8->i0 in
	let htoi strsub req i0 8 -> len in
	let i0+8->i0 in
	let strsub req i0+len 8 -> end in
	if !strcmp end firmwarelimit then
	let strnew len>>1 -> bin in
	(
		Secholn "getbinary";
		setleds 0xff0000;
		getbinary bin req 0 i0 len>>1;
		uncrypt bin 0 nil 0x47 47;
		bin
	);;

fun httpflash req=
	Secholn "FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFf";
//	Secholn req;
	let getfirmware req -> firm in
	if firm!=nil then
	(
//		dump firm;
		Secholn "######### firmware found";
		setleds 0xffffff;
		flashFirmware firm 0x13fb6754 0x0407FE58;
		nil
	)
	else
	(
		setleds 0xff;
		page_error
	);;


fun cbhttp req=
	let strstr req " " 0 -> i in
	let strstr req " " i+1 -> j in
	let strsub req i+1 j-i-1 -> uri in
	let uriextract uri -> [page args] in
	(
		Secho "page : "; Iecholn page;
		updateconf args;
		if page==1 then httpdone
		else if page==2 then httpflash req
		else if page==3 then (reboot 0x0407FE58 0x13fb6754;nil)
		else if page==4 then httpupgrade
		else httpindex
	);;

fun startconfigserver port=	starthttpsrv port #cbhttp;;

}
else
{
fun startconfigserver port=	0;;
}

//-------------------
var HTTP_NORMAL=0;;
var HTTP_STREAM=1;;
var HTTP_DIRECT=2;;

var HTTP_SOLVE=0;;
var HTTP_REACH=1;;
var HTTP_CONNECTED=2;;

var lasthttpevent;;

// type Httpreq contenant l'état d'une requête
type Httpreq=[cnxH inputH outputH indexH cbH typeH stateH startH];;

// callback de lecture sur la socket d'une requête
fun tcpread cnx input httpreq=
	set httpreq.startH=time;
	if input==nil ||0==strlen input then	// erreur ou fin
	(	closetcp cnx;	// on ferme la socket
		if httpreq.typeH==HTTP_NORMAL then call httpreq.cbH [httpreq strcatlist rev httpreq.inputH nil]	// on retourne ce qui a été reçu
		else call httpreq.cbH [httpreq nil]
	)
	else
	(
		set lasthttpevent=time;
		if httpreq.typeH==HTTP_NORMAL then set httpreq.inputH=input::httpreq.inputH	// on bufferise ce qui a été reçu
		else if httpreq.typeH==HTTP_DIRECT then
		(
			call httpreq.cbH [httpreq input];
			nil
		)
		else let strcat hd httpreq.inputH input -> s in
		let strstr s "\13\10\13\10" 0 -> i in
		if i==nil then
		(
			set httpreq.inputH=s::nil
		)
		else
		(
			set httpreq.inputH=nil;
			set httpreq.typeH=HTTP_DIRECT;
			call httpreq.cbH [httpreq strsub s 0 i];
			if i+4<strlen s then call httpreq.cbH [httpreq strsub s i+4 nil];
			nil
		);
		nil
	);;

// callback d'écriture sur la socket d'une requête
fun tcpwrite cnx httpreq=
	set httpreq.stateH=HTTP_CONNECTED; 
	if httpreq.outputH!=nil then	// s'il y a des choses à envoyer (notamment la première fois)
	(	set httpreq.indexH=writetcp cnx httpreq.outputH httpreq.indexH;	// envoyer ce qui peut l'être
		if httpreq.indexH==nil then	// si erreur lors de l'envoi
		(	closetcp cnx;	// on ferme la socket
			call httpreq.cbH [httpreq nil]	)	// on retourne nil
		else if httpreq.indexH>=strlen httpreq.outputH then	// sinon si tout a été envoyé
		(	set httpreq.indexH=nil;	// purger les données d'émission
			set httpreq.outputH=nil;
			nil
		)
	);;

var http_prefurl="http://";;	// en-tête normal (mais ici facultatif) d'une requête http

fun isip s i=
	if i>=strlen s then 1
	else let strget s i -> c in
	if (c<'0' || c>'9')&&c!='.' then 0
	else isip s i+1;;


// découper une url en [host port path].
// host est de la forme ip:port
// path ne commence pas par /
fun cuturl url =
	if !strcmp (strsub url 0 strlen http_prefurl) http_prefurl then cuturl strsub url strlen http_prefurl strlen url
	else let strstr url "/" 0 -> i in
		let if i==nil then url else strsub url 0 i -> addr in
		let strstr addr ":" 0 -> j in
		let if j==nil then [addr 80]
			else [strsub addr 0 j atoi strsub addr j+1 strlen addr] -> [host port] in
		let if i==nil then "/" else strsub url i strlen url -> path in
		[host port path];;

fun tcpevent t val msg sock=
	if val==TCPWRITE then tcpwrite t sock
	else if val==TCPCLOSE then tcpread t nil sock
	else tcpread t msg sock;
	0;;


fun httpsendreq ip x=
	Secho "found ip>>>>>>>>>>>>>>>>>>>>>>>>>"; Secholn ip;
	let x->[port httpreq] in
	if ip==nil then (call httpreq.cbH [httpreq nil]; nil)
	else
	(
		set httpreq.cnxH=opentcp netip nil useparamip ip port fixarg4 #tcpevent httpreq;
		set httpreq.stateH=HTTP_REACH;
		nil
	);
	0;;


//##> création d'une requête http
// paramètres : verb=verbe de la requête url=url de la requête postdata=données supplémentaires (nil si aucune) cb=callback de retour
fun httprequest verb url postdata cb type=
	Secho "HTTPREQUEST url =";Secholn url;
	let cuturl url ->[host port path] in	// décodage de l'url de la requête
	let if confGetProxy then strcatlist "http://"::host::":"::(itoa port)::path::nil else path -> path in
	let Secholn strcatlist verb::" "::path::" HTTP/1.0\13\nUser-Agent: MTL\13\nPragma: no-cache\13\nHost: "::host::"\13\n"::
			if postdata==nil then "\13\n"::nil
			else "Content-length: "::(itoa strlen postdata)::"\13\n\13\n"::postdata::nil
		-> request in	// création de la chaîne requête
	let if confGetProxy then webip confGetProxyip else host -> host in
	let if confGetProxy then confGetProxyport else port -> port in
	let [outputH:request indexH:0 cbH:cb typeH:type stateH:HTTP_SOLVE startH:time] -> httpreq in	// création de la structure requête
	(
		Secho "HTTPREQUEST host =";Secholn host;
		if isip host 0 then httpsendreq host [port httpreq]
		else
		(
			dnsreq host fixarg2 #httpsendreq [port httpreq];
			nil
		);
		httpreq	// on retourne la structure requête pour pouvoir éventuellement l'interrompre en cours de route
	);;

//##> interruption d'une requête en cours
fun httpabort httpreq=
	closetcp httpreq.cnxH;;	// on ferme la socket de la requête

fun httpenable httpreq v=
	enabletcp httpreq.cnxH v;;

fun httpstate httpreq = httpreq.stateH;;

fun httpstart httpreq = httpreq.startH;;


var http_sep="\13\n\13\n";;	// séparateur entre l'en-tête et le corps de la réponse à une requête

	

//##> retourne le header d'une réponse à une requête
fun httpgetheader res =
	let strstr res http_sep 0 -> i in
	if i==nil then res
	else strsub res 0 i+strlen http_sep;;

//##> retourne le contenu d'une réponse à une requête (sans header)
fun httpgetcontent res =
	let strstr res http_sep 0 -> i in
	if i==nil then nil
	else strsub res i+strlen http_sep strlen res;;

//-------------------
ifdef AUDIOLIB {

var WAV_IDLE=0;;
var WAV_RUN=1;;
var WAV_EOF=2;;

var WAV_BUFFER_STARTSIZE=80000;;
var WAV_BUFFER_MAXSIZE=400000;;

var WAV_END_TIMEOUT=500;;
var WAV_NET_TIMEOUT=10000;;


var wav_state=0;;
var wav_http;;
var wav_fifo;;
var wav_buffering;;
var wav_index;;
var wav_lasttime;;
var wav_lastnet;;
var wav_zeros;;

fun wavgetzeros=
	if wav_zeros==nil then
	(
		set wav_zeros=strnew 2048;
		for i=0;i<2048 do strset wav_zeros i 0
	);
	wav_zeros;;

fun wavstop =
	if wav_state!=WAV_IDLE then
	(
		playStop;
		if wav_http!=nil then httpabort wav_http;
		set wav_http=nil;
		set wav_state=WAV_IDLE
	);;

fun wavrunning =
	if wav_state==WAV_IDLE then 0
	else if wav_fifo==nil && wav_state==WAV_EOF && (time_ms-wav_lasttime>WAV_END_TIMEOUT) then
	(
		wavstop;
		0
	)
	else if wav_lasttime==nil then -1 else 1;;


fun itobin4 i=strcatlist (ctoa i)::(ctoa i>>8)::(ctoa i>>16)::(ctoa i>>24)::nil;;
fun itobin2 i=strcatlist (ctoa i)::(ctoa i>>8)::nil;;

fun mkwav freq channel bps=
	let strcatlist 
		"WAVEfmt "::(itobin4 0x12)::
			(itobin2 1)::(itobin2 channel)::
			(itobin4 freq)::(itobin4 freq*channel*bps/8)::
			(itobin2 channel*bps/8)::(itobin4 bps)::
		"data"::(itobin4 0)::nil -> c in
	strcatlist "RIFF"::(itobin4 (strlen c))::c::nil;;

/*
fun _wavcbhttp httpreq req=
	set wav_lastnet=time_ms;
	if req==nil then
	(
		Secholn ">>>>>>>>>>>>>>>>>>>>>>>>>>>>><end of file";
		set wav_state=WAV_EOF;
		if wav_index==nil then
		(
			set wav_fifo=tl wav_fifo;
			if wav_fifo==nil then wavstop
			else _wavstartnow
		);
		0
	)
	else
	(
		set wav_fifo=conc wav_fifo req::nil;
		let slistlen wav_fifo -> n in
		if wav_index==nil && n>WAV_BUFFER_STARTSIZE then
		(
			set wav_fifo=tl wav_fifo;
			_wavstartnow
		)
		else if n>WAV_BUFFER_MAXSIZE then
		(
			Secholn "\n>>>>>>>>>>>>>>http wait";
			httpenable httpreq 0
		);
		nil
	);
	0;;

}


//-------------------

//-------------------




var RT2501_S_BROKEN=0;;
var RT2501_S_IDLE=1;;
var RT2501_S_SCAN=2;;
var RT2501_S_CONNECTING=3;;
var RT2501_S_CONNECTED=4;;
var RT2501_S_MASTER=5;;

var IEEE80211_M_MANAGED=0;;
var IEEE80211_M_MASTER=1;;

var wifitry;;



fun _scanserialize l=
	if l!=nil then
	let hd l->[ssid mac bssid rssi channel rateset encryption] in
	ssid::"\0"::mac::bssid::(itoh4 rssi)::(itoh4 channel)::(itoh4 rateset)::(itoh4 encryption)::
	_scanserialize tl l;;

fun scanserialize l=
	(itoh4 listlen l)::_scanserialize l;;


fun ssidlen s i=
	if i>=strlen s then i
	else if !strget s i then i
	else ssidlen s i+1;;

fun scanunserialize s n i0=
	if n>0 then
	let ssidlen s i0 -> j in
	let j+1->i in
	[
		strsub s i0 j-i0
		strsub s i 6
		strsub s i+6 6
		htoi strsub s i+12 8
		htoi strsub s i+20 8
		htoi strsub s i+28 8
		htoi strsub s i+36 8
	]::scanunserialize s n-1 i+44;;


fun envmake =
	strcatlist netip::netmask::netgateway::netdns::scanserialize wifiscans;;

fun envrestore s =
	if s!=nil then
	(
		set netip=strsub s 0 4;
		set netmask=strsub s 4 4;
		set netgateway=strsub s 8 4;
		set netdns=strsub s 12 4;
		let htoi strsub s 16 8 -> nscan in
		set wifiscans=scanunserialize s nscan 24;
		0
	);;

fun scancmpssid a b=
	let a->[sa _ _ _ _ _ _] in
	let b->[sb _ _ _ _ _ _] in
	strcmp sa sb;;

fun otherscan sa l=
	if l!=nil then let hd l->[sb _ _ _ _ _ _] in
	if !strcmp sa sb then otherscan sa tl l
	else (hd l)::otherscan sa tl l;;

fun bestscan l sa res resval=
	if l==nil then res
	else let hd l->[sb _ _ vb _ _ _] in
	if strcmp sa sb then bestscan tl l sa res resval
	else if resval==nil || vb>resval then bestscan tl l sa hd l vb
	else bestscan tl l sa res resval;;

fun filterscan l=
	if l!=nil then let hd l->[sa _ _ _ _ _ _] in
	if sa==nil || !strlen sa then filterscan tl l
	else (bestscan l sa nil nil)::filterscan otherscan sa tl l;;

fun wifiInit rescan=
	set wifitry=nil;
	let envget -> env in
	if env==nil then
	(
		setleds 0xff00ff;
		set wifi=initW;
		if rescan then set wifiscans=nil;
		if master then
		(
			set netip=netip_master;
			set netmask=netmask_master;
			set netgateway=netgateway_master;
			0
		)
		else
		(
			if confGetDhcp then	set netip=netip_empty
			else
			(
				set netmask=confGetNetmask;
				set netgateway=confGetNetgateway;
				set netdns=confGetNetdns;
				set netip=confGetNetip
			);
			0
		);
		0
	)
	else
	(
		setleds 0x00ff00;
		set mymac=netMac;
		set wifi=stationW;
		envrestore env;
		envset nil;
		nil
	);
	0;;

var laststate;;

fun wifibyssid x v=let x->[s _ _ _ _ _ _] in (s!=nil)&& !strcmp v s;;


var retrytime;;

fun _wifiwepkey val i len=
	if i<len then
	(htoi strsub val i 2)::_wifiwepkey val i+2 len;;

fun wifiwepkey val=
	let strlen val -> len in
	if len==5 || len==13 then val
	else let strreplace val ":" "" -> val in
	let if len<10 then 0 else if len<26 then 5 else 13 -> len in
	listtostr _wifiwepkey val 0 len<<1;;

fun wificrypttype crypt key=
	if crypt==1 then if 5==strlen key then IEEE80211_CRYPT_WEP64 else IEEE80211_CRYPT_WEP128
	else if crypt==2 then IEEE80211_CRYPT_WPA
	else IEEE80211_CRYPT_NONE;;

fun wifiAuth=
	setleds 0xff8000;
	if wifiscans==nil then 0
	else
		let Iecholn confGetWificrypt -> crypt in
		let if crypt==1 then confGetWifiauth else 0-> auth in
		let if crypt==1 then wifiwepkey confGetWifikey0
			else if crypt==2 then confGetWifipmk -> key in
		(
			dump key;
			set wifitry=time;
			netAuth hd wifiscans Iecholn auth (Iecholn wificrypttype crypt key) key;	//## ajouter les paramètres de crypto
			1
		);;

fun Hx v=
	if v<10 then '0'+v
	else 'A'+v-10;;

fun uppermac v=
	let strnew 2 -> s in
	(
		strset s 0 Hx (v>>4)&15;
		strset s 1 Hx v&15;
		s
	);;

fun wifiRun=
	let netState -> state in
	(
		if state!=laststate then (Secho "wifi state=";Iecholn state);
		let match wifi with
		(stationW -> nil)
		|(initW -> if state==RT2501_S_IDLE then
				(
					set mymac=MACecho netMac 0 1;
					if master then
					(
						dumpscan set wifiscans=sort filterscan netScan nil #scancmpssid;
						netSetmode IEEE80211_M_MASTER (Secholn strcat "Nabaztag" uppermac strget mymac 5) 1;
						Secholn "-------------gomaster";
						gomasterW
					)
					else
					(
						setleds 0xff8000;
						if wifiscans==nil then
						(
							let confGetWifissid -> ssid in
							let if strlen ssid then ssid else nil -> ssid in
							let netScan ssid -> lscan in
							let sort filterscan lscan #scancmpssid -> l in
							let if ssid==nil then l else select l ssid #wifibyssid-> l in
							dumpscan set wifiscans=l
						);
						if wifiAuth then
						(
							Secho confGetWifissid; Secholn ":-------------gostation";
							gostationW [0 time]
						)
					)
				)
			)
		|(gomasterW -> if state==RT2501_S_MASTER then
				(
					setleds 0x0000ff;
					Secholn "-------------master";
					startdhcpserver;
					startconfigserver 80;
					masterW)
			)
		|(masterW -> if master<0 then
					(
						set master=master+1;
						if !master then
						(
							wifiInit 1;
							resetudp;
							netSetmode IEEE80211_M_MANAGED nil 11;
							nil)
					)
			)
		|(gostationW x-> if state==RT2501_S_CONNECTED then
				(
					Secholn "-------------dhcp";
					if confGetDhcp then startdhcp;
					startdnsclient;
					dhcpW time
				)
			)
		|(dhcpW t-> if netip!=netip_empty then
				(
					Secholn "-------------station";
					stationW
				)
				else if (time-t)>3 then	// retry dhcp client
				(
					startdhcp;
					dhcpW time
				)
			)
		-> nwifi in
		if nwifi!=nil then set wifi=nwifi;
		set laststate=state
	);
	if retrytime!=time then
	(
		set retrytime=time;
		nettime;
		dnstime;
		0
	)
	;;

fun wifiReady= match wifi with (stationW -> 1)|(_ -> 0);;

fun wifiConnected= match wifi with (stationW -> 1)|(_ -> 0);;

ifdef BOOT 
{
var BOOT_HTTPTIMEOUT=10;;
var BOOT_WIFITIMEOUT=20;;
var httpboot;;

var GREEN=0xff00;;
var AMBER=0xff8000;;
var BLACK=0;;

fun boot_leds=
	let
		if netState!=RT2501_S_CONNECTED then 0
		else if netip==netip_empty then 1
		else let httpstate httpboot -> state in 
		if state==HTTP_SOLVE then 2
		else if state==HTTP_REACH then 3
		else if state==HTTP_CONNECTED then 4
	-> step in
	let (time_ms>>8)&1 -> t in
	(
		led 4 if t then AMBER else BLACK;
		for i=0;i<3 do led 1+i if step>i then GREEN else if step<i || t then AMBER else BLACK;
		led 0 if t then AMBER else if step<3 then AMBER else if step>3 then GREEN else BLACK
	);;

fun getbytecode res=
	if res!=nil then
	let httpgetcontent res -> content in
	if !strcmp Secholn strsub content 0 5 "amber" then
	let htoi Secholn strsub content 5 8 -> len in
	if !strcmp Secholn strsub content 13+len 4 "Mind" then strsub content 13 len;;

fun _bootcbhttp httpreq res=
	let getbytecode res -> bc in
	if bc!=nil then
	(
		Secholn "BOOT DONE";
		envset envmake;
		dump envget;
		bytecode bc;
		0
	)
	else
	(
		Secholn "BOOT ERROR : not a bytecode";
		0
	);
	0;;


fun boot_url url =
	Secholn strcatlist url::"/bc.jsp?v="::(webip FIRMWARE)::"&m="::(webmac netMac)::"&l="::(webmac confGetLogin)::"&p="::(webmac confGetPwd)::"&h="::(itoa HARDWARE)::nil;;

fun boot_loop=
	if wifitry!=nil && time-wifitry>BOOT_WIFITIMEOUT && httpboot==nil then	// essayer un autre réseau
	(
		wifiInit 0;
		resetudp;
		set httpboot=nil;
		netSetmode IEEE80211_M_MANAGED nil 11;
		set wifiscans=tl wifiscans;
		nil
	)
	else
	if httpboot==nil then
	let confGetServerUrl -> url in
//	let "nabdev.no-ip.org:8080/vl" -> url in
	(
		if wifiReady then set httpboot=httprequest "GET" boot_url url nil #_bootcbhttp HTTP_NORMAL;
		0
	)
	else let httpstart httpboot -> t0 in
	if time-t0>BOOT_HTTPTIMEOUT then
	(
		httpabort httpboot;
		set httpboot=nil;
		0
	);;

}
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

/*
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


