<?php
/**
 * Error Protection API: Functions
 *
 * @since 5.2.0
 */

/**
 * Get the instance for storing paused plugins.
 *
 * @return Paused_Extensions_Storage
 */
function paused_plugins() {
	static $storage = null;

	if ( null === $storage ) {
		$storage = new Paused_Extensions_Storage( 'plugin' );
	}

	return $storage;
}

/**
 * Get the instance for storing paused extensions.
 *
 * @return Paused_Extensions_Storage
 */
function paused_themes() {
	static $storage = null;

	if ( null === $storage ) {
		$storage = new Paused_Extensions_Storage( 'theme' );
	}

	return $storage;
}

/**
 * Get a human readable description of an extension's error.
 *
 * @since 5.2.0
 *
 * @param array $error Error details from `error_get_last()`.
 * @return string Formatted error description.
 */
function get_extension_error_description( $error ) {
	$constants   = get_defined_constants( true );
	$constants   = isset( $constants['Core'] ) ? $constants['Core'] : $constants['internal'];
	$core_errors = array();

	foreach ( $constants as $constant => $value ) {
		if ( str_starts_with( $constant, 'E_' ) ) {
			$core_errors[ $value ] = $constant;
		}
	}

	if ( isset( $core_errors[ $error['type'] ] ) ) {
		$error['type'] = $core_errors[ $error['type'] ];
	}

	/* translators: 1: Error type, 2: Error line number, 3: Error file name, 4: Error message. */
	$error_message = __( 'An error of type %1$s was caused in line %2$s of the file %3$s. Error message: %4$s' );

	return sprintf(
		$error_message,
		"<code>{$error['type']}</code>",
		"<code>{$error['line']}</code>",
		"<code>{$error['file']}</code>",
		"<code>{$error['message']}</code>"
	);
}

/**
 * Registers the shutdown handler for fatal errors.
 *
 * The handler will only be registered if {@see is_fatal_error_handler_enabled()} returns true.
 *
 * @since 5.2.0
 */
function register_fatal_error_handler() {
	if ( ! is_fatal_error_handler_enabled() ) {
		return;
	}

	$handler = null;
	if ( defined( 'CONTENT' ) && is_readable( CONTENT . '/fatal-error-handler.php' ) ) {
		$handler = include CONTENT . '/fatal-error-handler.php';
	}

	if ( ! is_object( $handler ) || ! is_callable( array( $handler, 'handle' ) ) ) {
		$handler = new Fatal_Error_Handler();
	}

	register_shutdown_function( array( $handler, 'handle' ) );
}

/**
 * Checks whether the fatal error handler is enabled.
 *
 * A constant `DISABLE_FATAL_ERROR_HANDLER` can be set in `sync-config.php` to disable it, or alternatively the
 * {@see 'fatal_error_handler_enabled'} filter can be used to modify the return value.
 *
 * @since 5.2.0
 *
 * @return bool True if the fatal error handler is enabled, false otherwise.
 */
function is_fatal_error_handler_enabled() {
	$enabled = ! defined( 'DISABLE_FATAL_ERROR_HANDLER' ) || ! DISABLE_FATAL_ERROR_HANDLER;

	/**
	 * Filters whether the fatal error handler is enabled.
	 *
	 * **Important:** This filter runs before it can be used by plugins. It cannot
	 * be used by plugins, mu-plugins, or themes. To use this filter you must define
	 * a `$filter` global before Sync loads, usually in `sync-config.php`.
	 *
	 * Example:
	 *
	 *     $GLOBALS['filter'] = array(
	 *         'fatal_error_handler_enabled' => array(
	 *             10 => array(
	 *                 array(
	 *                     'accepted_args' => 0,
	 *                     'function'      => function() {
	 *                         return false;
	 *                     },
	 *                 ),
	 *             ),
	 *         ),
	 *     );
	 *
	 * Alternatively you can use the `DISABLE_FATAL_ERROR_HANDLER` constant.
	 *
	 * @since 5.2.0
	 *
	 * @param bool $enabled True if the fatal error handler is enabled, false otherwise.
	 */
	return apply_filters( 'fatal_error_handler_enabled', $enabled );
}

/**
 * Access the Sync Recovery Mode instance.
 *
 * @since 5.2.0
 *
 * @return Recovery_Mode
 */
function recovery_mode() {
	static $recovery_mode;

	if ( ! $recovery_mode ) {
		$recovery_mode = new Recovery_Mode();
	}

	return $recovery_mode;
}
