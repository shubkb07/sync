<?php
$_URI=explode('/', trim($_GET['path'], '/'));

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

if ( ! defined( 'INC' ) ) {
	define( 'INC', ABSPATH . 'includes/' );
}

if ( ! defined( 'CONTENT' ) ) {
	define( 'CONTENT', ABSPATH . 'public/' );
}

if ( ! defined( 'ADMIN' ) ) {
	define( 'ADMIN', ABSPATH . 'admin/' );
}

require_once INC . 'load.php';