<?php
if ( function_exists( 'error_reporting' ) ) {
	error_reporting( E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR | E_USER_WARNING | E_RECOVERABLE_ERROR );
}

define ('CLASSES', INC_DIR . 'classes/');
define ('FUNCTIONS', INC_DIR . 'functions/');
define ('LIB', INC_DIR . 'lib/');

require_once INC_DIR . 'define.php';

if ( file_exists( ABSPATH . 'sync-config.php' ) ) {
	require_once ABSPATH . 'sync-config.php';
	require_once INC_DIR . 'config.php';
	require_once INC_DIR . 'load-functions.php';
	require_once INC_DIR . 'default-filters.php';

	if ( 'static' !== $_SERVER['ACCESS'] && defined( 'SITE_STATUS' ) && 'PRESETUP' === SITE_STATUS ) {
		define( 'INSTALLING', true );
		require_once ADMIN_DIR . 'setup/presetup.php';
		die();
	} elseif ( 'static' !== $_SERVER['ACCESS'] ) {
		sync_die('Please Define `SITE_STATUS` in `sync-config.php` before starting fun development.');
	}
} elseif ( 'static' !== $_SERVER['ACCESS'] ) {
	require_once ADMIN_DIR . 'setup/config.php';
	die();
}

if ( 'static' === $_SERVER['ACCESS'] ) {

	if ( ! file_exists( ABSPATH . 'sync-config.php' ) ) {
		require_once FUNCTIONS . 'includes\functions\static-preinstall.php';
	}
	$extension = pathinfo($_SERVER['REQUEST_PATH'], PATHINFO_EXTENSION);
	header( 'Content-Type: ' . get_mime_types()[$extension] );
	echo file_get_contents( $_SERVER['REQUEST_PATH'] );
}
