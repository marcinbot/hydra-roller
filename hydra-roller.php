<?php

/**
 * Plugin Name: Hydra Roller Shortcode
 * Description: Roll a d20
 * License: GPL2
 * Author: mb
 * Version: 0.7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/hydra-roller-admin.php';
require_once __DIR__ . '/hydra-roller-user-repository.php';
require_once __DIR__ . '/hydra-roller-rest-api.php';
require_once __DIR__ . '/hydra-roller-roll-repository.php';
require_once __DIR__ . '/hydra-roller-shortcode.php';

class Hydra_Roller {
	private $shortcode;
	private $user_repository;
	private $roll_repository;
	private $rest_api;

	public function __construct() {
		$this->user_repository = new Hydra_Roller_User_Repository();
		$this->roll_repository = new Hydra_Roller_Roll_Repository( $this->user_repository );
		$this->shortcode = new Hydra_Roller_Shortcode( $this->user_repository );
		$this->admin = new Hydra_Roller_Admin( $this->user_repository, $this->roll_repository );
		$this->rest_api = new Hydra_Roller_REST_API( $this->roll_repository );
	}

	public static function plugin_activation() {
		Hydra_Roller_User_Repository::create_table();
		Hydra_Roller_Roll_Repository::create_table();
	}

	public static function plugin_uninstall() {
		Hydra_Roller_User_Repository::drop_table();
		Hydra_Roller_Roll_Repository::drop_table();
	}
}

new Hydra_Roller();
register_activation_hook( __FILE__, array( 'Hydra_Roller', 'plugin_activation' ) );
register_uninstall_hook( __FILE__, array( 'Hydra_Roller', 'plugin_uninstall' ) );
