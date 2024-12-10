<?php
require_once CLASSES . 'class-object-cache.php';

function cache_init() {
	$GLOBALS['object_cache'] = new Object_Cache();
}

function cache_add( $key, $data, $group = '', $expire = 0 ) {
	global $object_cache;

	return $object_cache->add( $key, $data, $group, (int) $expire );
}

function cache_add_multiple( array $data, $group = '', $expire = 0 ) {
	global $object_cache;

	return $object_cache->add_multiple( $data, $group, $expire );
}

function cache_replace( $key, $data, $group = '', $expire = 0 ) {
	global $object_cache;

	return $object_cache->replace( $key, $data, $group, (int) $expire );
}

function cache_set( $key, $data, $group = '', $expire = 0 ) {
	global $object_cache;

	return $object_cache->set( $key, $data, $group, (int) $expire );
}

function cache_set_multiple( array $data, $group = '', $expire = 0 ) {
	global $object_cache;

	return $object_cache->set_multiple( $data, $group, $expire );
}

function cache_get( $key, $group = '', $force = false, &$found = null ) {
	global $object_cache;

	return $object_cache->get( $key, $group, $force, $found );
}

function cache_get_multiple( $keys, $group = '', $force = false ) {
	global $object_cache;

	return $object_cache->get_multiple( $keys, $group, $force );
}

function cache_delete( $key, $group = '' ) {
	global $object_cache;

	return $object_cache->delete( $key, $group );
}

function cache_delete_multiple( array $keys, $group = '' ) {
	global $object_cache;

	return $object_cache->delete_multiple( $keys, $group );
}

function cache_incr( $key, $offset = 1, $group = '' ) {
	global $object_cache;

	return $object_cache->incr( $key, $offset, $group );
}

function cache_decr( $key, $offset = 1, $group = '' ) {
	global $object_cache;

	return $object_cache->decr( $key, $offset, $group );
}

function cache_flush() {
	global $object_cache;

	return $object_cache->flush();
}

function cache_flush_runtime() {
	return cache_flush();
}

function cache_flush_group( $group ) {
	global $object_cache;

	return $object_cache->flush_group( $group );
}

function cache_supports( $feature ) {
	switch ( $feature ) {
		case 'add_multiple':
		case 'set_multiple':
		case 'get_multiple':
		case 'delete_multiple':
		case 'flush_runtime':
		case 'flush_group':
			return true;

		default:
			return false;
	}
}

function cache_close() {
	return true;
}

function cache_add_global_groups( $groups ) {
	global $object_cache;

	$object_cache->add_global_groups( $groups );
}

function cache_add_non_persistent_groups( $groups ) {
	// Default cache doesn't persist so nothing to do here.
}

function cache_switch_to_blog( $blog_id ) {
	global $object_cache;

	$object_cache->switch_to_blog( $blog_id );
}
