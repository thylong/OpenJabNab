<?php
// Nabaztag locate.jsp endpoint
// Returns server configuration for device initialization

header('Content-Type: text/html; charset=UTF-8');

// Get device serial number from query parameter
$sn = isset($_GET['sn']) ? $_GET['sn'] : '';

// Return server configuration similar to reference server
echo "ping nabaztag.yourdomain.com\n";
echo "broad nabaztag.yourdomain.com\n";
echo "xmpp_domain nabaztag.yourdomain.com:5222\n";
echo "xmpp_alt nabaztag.yourdomain.com:443\n";
echo "xmpp_timeout 8\n";
echo "date " . time() . "\n";
?>