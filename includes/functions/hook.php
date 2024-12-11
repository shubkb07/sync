<?php
require CLASSES . '/class-hook.php';

global $filter;
global $actions;
global $filters;
global $current_filter;

if ( $filter ) {
	$filter = Hook::build_preinitialized_hooks( $filter );
} else {
	$filter = array();
}

if ( ! isset( $actions ) ) {
	$actions = array();
}

if ( ! isset( $filters ) ) {
	$filters = array();
}

if ( ! isset( $current_filter ) ) {
	$current_filter = array();
}

function add_filter( $hook_name, $callback, $priority = 10, $accepted_args = 1 ) {
	global $filter;

	if ( ! isset( $filter[ $hook_name ] ) ) {
		$filter[ $hook_name ] = new Hook();
	}

	$filter[ $hook_name ]->add_filter( $hook_name, $callback, $priority, $accepted_args );

	return true;
}

function apply_filters( $hook_name, $value, ...$args ) {
	global $filter, $filters, $current_filter;

	if ( ! isset( $filters[ $hook_name ] ) ) {
		$filters[ $hook_name ] = 1;
	} else {
		++$filters[ $hook_name ];
	}

	// Do 'all' actions first.
	if ( isset( $filter['all'] ) ) {
		$current_filter[] = $hook_name;

		$all_args = func_get_args();
		_call_all_hook( $all_args );
	}

	if ( ! isset( $filter[ $hook_name ] ) ) {
		if ( isset( $filter['all'] ) ) {
			array_pop( $current_filter );
		}

		return $value;
	}

	if ( ! isset( $filter['all'] ) ) {
		$current_filter[] = $hook_name;
	}

	// Pass the value to Hook.
	array_unshift( $args, $value );

	$filtered = $filter[ $hook_name ]->apply_filters( $value, $args );

	array_pop( $current_filter );

	return $filtered;
}

function apply_filters_ref_array( $hook_name, $args ) {
	global $filter, $filters, $current_filter;

	if ( ! isset( $filters[ $hook_name ] ) ) {
		$filters[ $hook_name ] = 1;
	} else {
		++$filters[ $hook_name ];
	}

	// Do 'all' actions first.
	if ( isset( $filter['all'] ) ) {
		$current_filter[] = $hook_name;
		$all_args            = func_get_args();
		_call_all_hook( $all_args );
	}

	if ( ! isset( $filter[ $hook_name ] ) ) {
		if ( isset( $filter['all'] ) ) {
			array_pop( $current_filter );
		}

		return $args[0];
	}

	if ( ! isset( $filter['all'] ) ) {
		$current_filter[] = $hook_name;
	}

	$filtered = $filter[ $hook_name ]->apply_filters( $args[0], $args );

	array_pop( $current_filter );

	return $filtered;
}

function has_filter( $hook_name, $callback = false ) {
	global $filter;

	if ( ! isset( $filter[ $hook_name ] ) ) {
		return false;
	}

	return $filter[ $hook_name ]->has_filter( $hook_name, $callback );
}

function remove_filter( $hook_name, $callback, $priority = 10 ) {
	global $filter;

	$r = false;

	if ( isset( $filter[ $hook_name ] ) ) {
		$r = $filter[ $hook_name ]->remove_filter( $hook_name, $callback, $priority );

		if ( ! $filter[ $hook_name ]->callbacks ) {
			unset( $filter[ $hook_name ] );
		}
	}

	return $r;
}

function remove_all_filters( $hook_name, $priority = false ) {
	global $filter;

	if ( isset( $filter[ $hook_name ] ) ) {
		$filter[ $hook_name ]->remove_all_filters( $priority );

		if ( ! $filter[ $hook_name ]->has_filters() ) {
			unset( $filter[ $hook_name ] );
		}
	}

	return true;
}

function current_filter() {
	global $current_filter;

	return end( $current_filter );
}

function doing_filter( $hook_name = null ) {
	global $current_filter;

	if ( null === $hook_name ) {
		return ! empty( $current_filter );
	}

	return in_array( $hook_name, $current_filter, true );
}

function did_filter( $hook_name ) {
	global $filters;

	if ( ! isset( $filters[ $hook_name ] ) ) {
		return 0;
	}

	return $filters[ $hook_name ];
}

function add_action( $hook_name, $callback, $priority = 10, $accepted_args = 1 ) {
	return add_filter( $hook_name, $callback, $priority, $accepted_args );
}

function do_action( $hook_name, ...$arg ) {
	global $filter, $actions, $current_filter;

	if ( ! isset( $actions[ $hook_name ] ) ) {
		$actions[ $hook_name ] = 1;
	} else {
		++$actions[ $hook_name ];
	}

	// Do 'all' actions first.
	if ( isset( $filter['all'] ) ) {
		$current_filter[] = $hook_name;
		$all_args            = func_get_args();
		_call_all_hook( $all_args );
	}

	if ( ! isset( $filter[ $hook_name ] ) ) {
		if ( isset( $filter['all'] ) ) {
			array_pop( $current_filter );
		}

		return;
	}

	if ( ! isset( $filter['all'] ) ) {
		$current_filter[] = $hook_name;
	}

	if ( empty( $arg ) ) {
		$arg[] = '';
	} elseif ( is_array( $arg[0] ) && 1 === count( $arg[0] ) && isset( $arg[0][0] ) && is_object( $arg[0][0] ) ) {
		// Backward compatibility for PHP4-style passing of `array( &$this )` as action `$arg`.
		$arg[0] = $arg[0][0];
	}

	$filter[ $hook_name ]->do_action( $arg );

	array_pop( $current_filter );
}

function do_action_ref_array( $hook_name, $args ) {
	global $filter, $actions, $current_filter;

	if ( ! isset( $actions[ $hook_name ] ) ) {
		$actions[ $hook_name ] = 1;
	} else {
		++$actions[ $hook_name ];
	}

	// Do 'all' actions first.
	if ( isset( $filter['all'] ) ) {
		$current_filter[] = $hook_name;
		$all_args            = func_get_args();
		_call_all_hook( $all_args );
	}

	if ( ! isset( $filter[ $hook_name ] ) ) {
		if ( isset( $filter['all'] ) ) {
			array_pop( $current_filter );
		}

		return;
	}

	if ( ! isset( $filter['all'] ) ) {
		$current_filter[] = $hook_name;
	}

	$filter[ $hook_name ]->do_action( $args );

	array_pop( $current_filter );
}

function has_action( $hook_name, $callback = false ) {
	return has_filter( $hook_name, $callback );
}

function remove_action( $hook_name, $callback, $priority = 10 ) {
	return remove_filter( $hook_name, $callback, $priority );
}

function remove_all_actions( $hook_name, $priority = false ) {
	return remove_all_filters( $hook_name, $priority );
}

function current_action() {
	return current_filter();
}

function doing_action( $hook_name = null ) {
	return doing_filter( $hook_name );
}

function did_action( $hook_name ) {
	global $actions;

	if ( ! isset( $actions[ $hook_name ] ) ) {
		return 0;
	}

	return $actions[ $hook_name ];
}

function apply_filters_deprecated( $hook_name, $args, $version, $replacement = '', $message = '' ) {
	if ( ! has_filter( $hook_name ) ) {
		return $args[0];
	}

	_deprecated_hook( $hook_name, $version, $replacement, $message );

	return apply_filters_ref_array( $hook_name, $args );
}

function do_action_deprecated( $hook_name, $args, $version, $replacement = '', $message = '' ) {
	if ( ! has_action( $hook_name ) ) {
		return;
	}

	_deprecated_hook( $hook_name, $version, $replacement, $message );

	do_action_ref_array( $hook_name, $args );
}

function plugin_basename( $file ) {
	global $plugin_paths;

	// $plugin_paths contains normalized paths.
	$file = normalize_path( $file );

	arsort( $plugin_paths );

	foreach ( $plugin_paths as $dir => $realdir ) {
		if ( str_starts_with( $file, $realdir ) ) {
			$file = $dir . substr( $file, strlen( $realdir ) );
		}
	}

	$plugin_dir    = normalize_path( PLUGIN_DIR );
	$mu_plugin_dir = normalize_path( MU_PLUGIN_DIR );

	// Get relative path from plugins directory.
	$file = preg_replace( '#^' . preg_quote( $plugin_dir, '#' ) . '/|^' . preg_quote( $mu_plugin_dir, '#' ) . '/#', '', $file );
	$file = trim( $file, '/' );
	return $file;
}

function register_plugin_realpath( $file ) {
	global $plugin_paths;

	// Normalize, but store as static to avoid recalculation of a constant value.
	static $plugin_path = null, $mu_plugin_path = null;

	if ( ! isset( $plugin_path ) ) {
		$plugin_path   = normalize_path( PLUGIN_DIR );
		$mu_plugin_path = normalize_path( MU_PLUGIN_DIR );
	}

	$plugin_path     = normalize_path( dirname( $file ) );
	$plugin_realpath = normalize_path( dirname( realpath( $file ) ) );

	if ( $plugin_path === $plugin_path || $plugin_path === $mu_plugin_path ) {
		return false;
	}

	if ( $plugin_path !== $plugin_realpath ) {
		$plugin_paths[ $plugin_path ] = $plugin_realpath;
	}

	return true;
}

function plugin_dir_path( $file ) {
	return trailingslashit( dirname( $file ) );
}

function plugin_dir_url( $file ) {
	return trailingslashit( plugins_url( '', $file ) );
}

function register_activation_hook( $file, $callback ) {
	$file = plugin_basename( $file );
	add_action( 'activate_' . $file, $callback );
}

function register_deactivation_hook( $file, $callback ) {
	$file = plugin_basename( $file );
	add_action( 'deactivate_' . $file, $callback );
}

function register_uninstall_hook( $file, $callback ) {
	if ( is_array( $callback ) && is_object( $callback[0] ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'Only a static class method or function can be used in an uninstall hook.' ), '3.1.0' );
		return;
	}

	/*
	 * The option should not be autoloaded, because it is not needed in most
	 * cases. Emphasis should be put on using the 'uninstall.php' way of
	 * uninstalling the plugin.
	 */
	$uninstallable_plugins = (array) get_option( 'uninstall_plugins' );
	$plugin_basename       = plugin_basename( $file );

	if ( ! isset( $uninstallable_plugins[ $plugin_basename ] ) || $uninstallable_plugins[ $plugin_basename ] !== $callback ) {
		$uninstallable_plugins[ $plugin_basename ] = $callback;
		update_option( 'uninstall_plugins', $uninstallable_plugins );
	}
}

function _call_all_hook( $args ) {
	global $filter;

	$filter['all']->do_all_hook( $args );
}

function _filter_build_unique_id( $hook_name, $callback, $priority ) {
	if ( is_string( $callback ) ) {
		return $callback;
	}

	if ( is_object( $callback ) ) {
		$callback = array( $callback, '' );
	} else {
		$callback = (array) $callback;
	}

	if ( is_object( $callback[0] ) ) {
		return spl_object_hash( $callback[0] ) . $callback[1];
	} elseif ( is_string( $callback[0] ) ) {
		return $callback[0] . '::' . $callback[1];
	}
}
