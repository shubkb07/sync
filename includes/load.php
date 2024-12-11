<?php
if ( function_exists( 'error_reporting' ) ) {
	error_reporting( E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR | E_USER_WARNING | E_RECOVERABLE_ERROR );
}

require_once INC_DIR . 'define.php';

if ( file_exists( ABSPATH . 'sync-config.php' ) ) {
	require_once ABSPATH . 'sync-config.php';
	require_once INC_DIR . 'config.php';
	require_once INC_DIR . 'load-functions.php';
	require_once INC_DIR . 'default-filters.php';
	
	if ( defined( 'SITE_STATUS' ) && 'PRESETUP' !== SITE_STATUS ) {

	} else {
		define( 'INSTALLING', true );
	}
} else {
	require_once ADMIN . 'setup/config.php';
}
