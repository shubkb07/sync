<?php
/**
 * These functions are needed to load.
 */

require_once CLASSES . 'class-paused-extensions-storage.php';
require_once CLASSES . 'class-fatal-error-handler.php';
require_once CLASSES . 'class-recovery-mode-cookie-service.php';
require_once CLASSES . 'class-recovery-mode-key-service.php';
require_once CLASSES . 'class-recovery-mode-link-service.php';
require_once CLASSES . 'class-recovery-mode-email-service.php';
require_once CLASSES . 'class-recovery-mode.php';
require_once CLASSES . 'class-list-util.php';
require_once CLASSES . 'class-token-map.php';
require_once CLASSES . 'class-meta-query.php';
require_once CLASSES . 'class-matchesmapregex.php';
require_once CLASSES . 'class-sync.php';
require_once CLASSES . 'class-error.php';
require_once CLASSES . 'class-minify.php';
require_once CLASSES . 'class-image-optimizer.php';
require_once CLASSES . 'class-download.php';

// Load Sync Functions.
require_once FUNCTIONS . 'sync-load.php';


check_php_mysql_versions();
initial_constants();
register_fatal_error_handler();
date_default_timezone_set( 'UTC' );
fix_server_vars();
maintenance();
timer_start();
debug_mode();

/**
 * Filters whether to enable loading of the advanced-cache.php drop-in.
 *
 * This filter runs before it can be used by plugins. It is designed for non-web
 * run-times. If false is returned, advanced-cache.php will never be loaded.
 *
 * @since 4.6.0
 *
 * @param bool $enable_advanced_cache Whether to enable loading advanced-cache.php (if present).
 *                                    Default true.
 */
if ( CACHE && apply_filters( 'enable_loading_advanced_cache_dropin', true ) && file_exists( CONTENT_DIR . '/advanced-cache.php' ) ) {
	// For an advanced caching plugin to use. Uses a static drop-in because you would only want one.
	include CONTENT_DIR . '/advanced-cache.php';

	// Re-initialize any hooks added manually by advanced-cache.php.
	if ( $filter ) {
		$filter = Hook::build_preinitialized_hooks( $filter );
	}
}

set_lang_dir();
load_translations_early();
require_db();
$GLOBALS['table_prefix'] = DB_PREFIX;
set_db_vars();
start_object_cache();
