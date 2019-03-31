<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Hydra_Roller_Roll_Repository' ) ) {
	return;
}

class Hydra_Roller_Roll_Repository {
	const ACTION_ROLL = 1;
	const ACTION_FLIP = 2;
	const ACTION_FIX = 3;

	private $user_repository;

	public function __construct( Hydra_Roller_User_Repository $user_repository ) {
		$this->user_repository = $user_repository;
	}

	public static function create_table() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$table_name = self::get_table_name();
		$index = $wpdb->prefix . 'hydra_roll_user_action_index';

		$sql = "CREATE TABLE $table_name (
			id BIGINT NOT NULL AUTO_INCREMENT,
			name VARCHAR(100) NOT NULL,
			action TINYINT NOT NULL,
			time BIGINT NOT NULL,
			result INT NULL,
			UNIQUE KEY id (id),
			INDEX $index( name, action, time )
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

	public function get_history( $reverse = false ) {
		global $wpdb;
		$table_name = self::get_table_name();
		$order = $reverse ? 'ORDER BY id ASC' : 'ORDER BY id DESC';
		$history = $wpdb->get_results( "SELECT * FROM $table_name $order");
		return array_map( array( $this, 'translate_event_action_name' ), $history );
	}

	public function purge_history() {
		global $wpdb;
		$table_name = self::get_table_name();
		return $wpdb->query("TRUNCATE TABLE $table_name");
	}

	public function maybe_perform_action( $user_id, $action_type ) {
		$action = $this->is_valid_action( $action_type );
		if ( ! empty( $user_id ) && $action ) {
			$user = $this->user_repository->get_user_by_id( $user_id );
			if ( $this->check_can_act( $user, $action ) ) {
				$result = self::ACTION_ROLL === $action
					? $this->generate_pseudo_random( $user_id )
					: null;
				$this->add_action( $user['name'], $action, time(), $result );
			}
		}
	}

	private function generate_pseudo_random( $user_id ) {
		$seed_start = 0;
		$roll_history = $this->get_history();
		if ( ! empty( $roll_history ) && 'roll' === $roll_history[0]->action ) {
			$seed_start = $roll_history[0]->result;
		}
		$seed = intval( substr( $user_id, $seed_start, 8), 16 ) + time();
		srand( $seed );
		return random_int( 1, 20 );
	}

	private function is_valid_action( $action ) {
		switch ( $action ) {
			case 'roll':
				return self::ACTION_ROLL;
			case 'flip':
				return self::ACTION_FLIP;
			case 'fix':
				return self::ACTION_FIX;
		}
		return false;
	}

	private function add_action( $name, $action, $time, $result = null ) {
		$data = array(
			'name' => $name,
			'action' => $action,
			'time' => $time,
			'result' => $result,
		);
		$table_name = self::get_table_name();
		global $wpdb;
		$wpdb->insert( self::get_table_name(), $data );

		$id = $wpdb->insert_id - 100;
		if ( 0 >= $id ) {
			return;
		}
		$wpdb->query("DELETE FROM $table_name WHERE id < $id");
	}

	private function get_action_name( $roll, $flip, $fix ) {
		if ( $flip ) {
			return 'flip';
		}
		if ( $fix ) {
			return 'fix';
		}
		return 'roll';
	}

	private function translate_event_action_name( $event ) {
		$action = null;
		switch ( $event->action ) {
			case self::ACTION_ROLL:
				$action = 'roll';
				break;
			case self::ACTION_FLIP:
				$action = 'flip';
				break;
			case self::ACTION_FIX:
				$action = 'fix';
				break;
		}
		$event->action = $action;
		return $event;
	}

	private function check_can_act( $user, $action ) {
		if ( 'fix' === $action ) {
			return true;
		}

		$roll_history = $this->get_history( true );
		$item = array_pop( $roll_history );

		if ( 'flip' === $item->action && 'roll' == $action ) {
			return false;
		}

		// $time_limit = time() - 300; //5 min ago
		// while ( $item['time'] > $time_limit ) {
		// 	if ( $item['name'] === $user['name'] && $item['action'] === $action ) {
		// 		return false;
		// 	}
		// 	$item = array_pop( $roll_history );
		// }
		return true;
	}

	private static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'hydra_roll_history';
    }
}
