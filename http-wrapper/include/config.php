<?php
// OpenJabNab Docker Configuration

// Database Configuration
define('DB_HOST', getenv('DB_HOST') ?: 'mysql');
define('DB_NAME', getenv('DB_NAME') ?: 'ojn');
define('DB_USER', getenv('DB_USER') ?: 'ojn');
define('DB_PASS', getenv('DB_PASS') ?: 'ojn');
define('DB_PORT', getenv('DB_PORT') ?: 3306);

// Application Constants (from common-def.php)
define('ROOT_SITE', '/var/www/html/');
define('ROOT_WWW_ADMIN', 'http://' . (getenv('OJN_DOMAIN') ?: 'localhost') . '/ojn_admin/');
define('ROOT_WWW_API', 'http://' . (getenv('OJN_DOMAIN') ?: 'localhost') . '/ojn_api/');
define('ADMIN_EMAIL', 'admin@' . (getenv('OJN_DOMAIN') ?: 'localhost'));
define('APC_PREFIX', 'ojn_docker_');

// API Configuration
define('OJN_API_HOST', 'openjabnab');
define('OJN_API_PORT', 8080);

// Mail Configuration (disabled for Docker)
define('MAIL_SENDER', 'admin@' . (getenv('OJN_DOMAIN') ?: 'localhost'));
define('MAIL_SERVER', getenv('OJN_DOMAIN') ?: 'localhost');
define('MAIL_USER', '');
define('MAIL_PASS', '');

// Server Settings
define('SERVER_MONTHLY_FEES', 0.0);

// Logging
define('LOG_OJNAPI', false);
define('LOGS_SITE', '/tmp/');

// Map Configuration
define('THUNDERFOREST_APIKEY', getenv('THUNDERFOREST_APIKEY') ?: '');

// Server Configuration
define('OJN_HTTP_HOST', getenv('OJN_HTTP_HOST') ?: 'localhost');
define('OJN_HTTP_PORT', getenv('OJN_HTTP_PORT') ?: 8080);
define('OJN_XMPP_HOST', getenv('OJN_XMPP_HOST') ?: 'localhost');
define('OJN_XMPP_PORT', getenv('OJN_XMPP_PORT') ?: 5222);

// Application Settings
define('OJN_DOMAIN', getenv('OJN_DOMAIN') ?: 'localhost');
define('OJN_ALLOW_REGISTRATION', getenv('OJN_ALLOW_REGISTRATION') ?: true);
define('OJN_DEBUG', getenv('OJN_DEBUG') ?: true);
define('ENABLE_DONATE', getenv('ENABLE_DONATE') ?: false);

// Paths  
define('ROOT_LOCAL', '/var/www/html/ojn_local');
define('OJN_ROOT_PATH', '/var/www/html/');
define('OJN_LOCAL_PATH', '/var/www/html/ojn_local/');
define('OJN_PLUGINS_PATH', '/var/www/html/plugins/');

// Session Configuration
ini_set('session.gc_maxlifetime', 1800); // 30 minutes
ini_set('session.cookie_lifetime', 1800); // 30 minutes
ini_set('session.cache_expire', 30); // 30 minutes

// Error Reporting for Development
if (OJN_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>
