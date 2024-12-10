<?php

function create_nonce( $action = -1 ) {
	$user = get_current_user();
	$uid  = (int) $user->ID;
	if ( ! $uid ) {
		$uid = apply_filters( 'nonce_user_logged_out', $uid, $action );
	}

	$token = get_session_token();
	$i     = nonce_tick( $action );

	return substr( sync_hash( $i . '|' . $action . '|' . $uid . '|' . $token, 'nonce' ), -12, 10 );
}