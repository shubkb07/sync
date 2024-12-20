<?php
$_SERVER['REQUEST_PATH'] = $_GET['path'];
$_SERVER['URI'] = explode( '/', trim( $_GET['path'], '/' ) );
$_SERVER['ACCESS'] = $_GET['access'];

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

if ( ! defined( 'INC' ) ) {
	define( 'INC', 'includes/' );
}

if ( ! defined( 'INC_DIR' ) ) {
	define( 'INC_DIR', ABSPATH . INC );
}

if ( ! defined( 'CONTENT' ) ) {
	define( 'CONTENT', 'public/' );
}

if ( ! defined( 'CONTENT_DIR' ) ) {
	define( 'CONTENT_DIR', ABSPATH . CONTENT );
}

if ( ! defined( 'ADMIN_PATH' ) ) {
	define( 'ADMIN_PATH', 'admin/' );
}

if ( ! defined( 'ADMIN_DIR' ) ) {
	define( 'ADMIN_DIR', ABSPATH . ADMIN_PATH );
}

require_once INC . 'load.php';
