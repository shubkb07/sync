<?php
if ( function_exists( 'error_reporting' ) ) {
	error_reporting( E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR | E_USER_WARNING | E_RECOVERABLE_ERROR );
}

require_once INC . 'define.php';

if ( file_exists( ABSPATH . 'sync-config.php' ) ) {
	require_once ABSPATH . 'sync-config.php';
	require_once INC . 'config.php';
	require_once INC . 'load-functions.php';
	if ( defined( 'SITE_STATUS' ) && 'PRESETUP' !== SITE_STATUS ) {

	} else {
		define( 'INSTALLING', true );
	}
} else {
	require_once ADMIN . 'setup/config.php';
}
