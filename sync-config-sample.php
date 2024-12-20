<?php
// Database Configuration.
define('DB_HOST', '');
define('DB_USER', '');
define('DB_PASS', '');
define('DB_NAME', '');
define('DB_PREFIX', '');
define( 'DB_CHARSET', 'utf8' );
define('DB_COLLATE', '');

define('DEBUG', false);
define('DEBUG_LOG', false);
define('DEBUG_DISPLAY', false);

// PRESETUP, ACTIVE, MAINTANANCE.
define( 'SITE_STATUS', 'PRESETUP' );
// production, development, staging.
define( 'ENVIRONMENT_TYPE', 'development' );

// Cache.
define('NO_CACHE', false);
define('FORCE_DEV_CACHE', false);

define('ENFORCE_HTTPS', true);
define('SECURE_AUTH_KEY', '');
define('LOGGED_IN_KEY', '');
define('NONCE_KEY', '');
define('AUTH_SALT', '');
define('SECURE_AUTH_SALT', '');
define('LOGGED_IN_SALT', '');
define('NONCE_SALT', '');
define('CACHE_KEY_SALT', '');
