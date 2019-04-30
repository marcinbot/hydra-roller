<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Hydra_Roller_User_Repository' ) ) {
	return;
}

class Hydra_Roller_User_Repository {
	const COOKIE = 'hydra_user';

	public function __construct() {
		add_action( 'wp', array( $this, 'process_user' ) );
	}

	public static function create_table() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$table_name = self::get_table_name();
		$uid_index = $wpdb->prefix . 'hydra_roll_user_id_index';
		$name_index = $wpdb->prefix . 'hydra_roll_name_index';

		$sql = "CREATE TABLE $table_name (
			uid CHAR(32) NOT NULL PRIMARY KEY,
			name VARCHAR(100) NOT NULL UNIQUE,
			INDEX $name_index(name)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	public static function drop_table() {
		global $wpdb;
		$table_name = self::get_table_name();
		$sql = "DROP TABLE IF EXISTS $table_name";
		$wpdb->query( $sql );
	}

	public function get_user_by_id( $user_id ) {
		global $wpdb;
		$table_name = self::get_table_name();
		$user = $wpdb->get_row( "SELECT * FROM $table_name WHERE uid = '$user_id'" );
		if ( empty( $user ) ) {
			return false;
		}
		return array( 'id' => $user->uid, 'name' => $user->name );
	}

	public function get_user() {
		if ( empty( $_COOKIE[ self::COOKIE ] ) ) {
			return false;
		}

		$cookie = explode( ':', $_COOKIE[ self::COOKIE ], 2 );
		if ( ! is_array( $cookie ) || 2 !== sizeof( $cookie ) || ! preg_match( '/^[a-f0-9]{32}$/i', $cookie[0] ) ) {
			return false;
		}

		$user_id = $cookie[0];
		$user_name = $cookie[1];
		$user = $this->get_user_by_id( $user_id );
		if ( false === $user ) {
			$this->add_user( $user_id, $user_name );
			return $this->get_user_by_id( $user_id );
		}
		return $user;
	}

	public function add_user( $user_id, $name ) {
		global $wpdb;
		$wpdb->hide_errors();
		$table_name = self::get_table_name();
		return $wpdb->insert( $table_name, array( 'uid' => $user_id, 'name' => $name ) );
	}

	public function remove_user( $user_id ) {
		global $wpdb;
		$table_name = self::get_table_name();
		return $history = $wpdb->delete( $table_name, array( 'uid' => $user_id ) );
	}

	public function process_user() {
		if ( empty( $_POST['hydra_name'] ) ) {
			return;
		}
		$name = stripslashes( $_POST['hydra_name'] );
		$user_id = md5( $name . uniqid( '', true ) );
		if ( $this->add_user( $user_id, $name ) ) {
			setcookie( self::COOKIE, $user_id . ':' . $name, 0, '/' );
			$query_args = array();
		} else {
			$query_args = array( 'hydra_error' => '1' );
		}

		global $wp;
		$current_url = home_url( add_query_arg( $query_args, trailingslashit( $wp->request ) ) );
		wp_redirect( $current_url );
	}

	public function get_all_users() {
		global $wpdb;
		$table_name = self::get_table_name();
		return $history = $wpdb->get_results( "SELECT * FROM $table_name", ARRAY_A );
	}

	private static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'hydra_roll_users';
    }
}
