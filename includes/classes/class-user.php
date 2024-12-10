<?php
class User{

	public $data;
	public $ID = 0;
	public $caps = array();
	public $cap_key;
	public $roles = array();
	public $allcaps = array();
	public $filter = null;
	private $site_id = 0;
	private static $back_compat_keys;

	public function __construct( $id = 0, $name = '', $site_id = '' ) {
		global $db;

		if ( ! isset( self::$back_compat_keys ) ) {
			$prefix = $db->prefix;

			self::$back_compat_keys = array(
				'user_firstname'             => 'first_name',
				'user_lastname'              => 'last_name',
				'user_description'           => 'description',
				'user_level'                 => $prefix . 'user_level',
				$prefix . 'usersettings'     => $prefix . 'user-settings',
				$prefix . 'usersettingstime' => $prefix . 'user-settings-time',
			);
		}

		if ( $id instanceof User ) {
			$this->init( $id->data, $site_id );
			return;
		} elseif ( is_object( $id ) ) {
			$this->init( $id, $site_id );
			return;
		}

		if ( ! empty( $id ) && ! is_numeric( $id ) ) {
			$name = $id;
			$id   = 0;
		}

		if ( $id ) {
			$data = self::get_data_by( 'id', $id );
		} else {
			$data = self::get_data_by( 'login', $name );
		}

		if ( $data ) {
			$this->init( $data, $site_id );
		} else {
			$this->data = new stdClass();
		}
	}

	public function init( $data, $site_id = '' ) {
		if ( ! isset( $data->ID ) ) {
			$data->ID = 0;
		}
		$this->data = $data;
		$this->ID   = (int) $data->ID;

		$this->for_site( $site_id );
	}
}