<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Hydra_Roller_REST_API' ) ) {
	return;
}

class Hydra_Roller_REST_API {
	private $roll_repository;

	public function __construct( Hydra_Roller_Roll_Repository $roll_repository ) {
		$this->roll_repository = $roll_repository;

		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
	}

	public function rest_api_init() {
		register_rest_route( 'hydra_roller/v1', '/roll_history', array(
			'methods' => 'POST',
			'callback' => array( $this, 'get_roll_history' ),
		) );

		register_rest_route( 'hydra_roller/v1', '/roll', array(
			'methods' => 'POST',
			'callback' => array( $this, 'perform_roll_action' ),
		) );
	}

	public function get_roll_history() {
		$roll_history = $this->roll_repository->get_history();

		$response = array(
			'results'   => $roll_history,
		);

		return $response;
	}

	public function perform_roll_action( $request ) {
		if ( empty( $request['actionType'] ) || empty( $request['userId'] ) ) {
			return new WP_Error( 'hydra_rest_error', 'no', array( 'status' => 400 ) );
		}
		$this->roll_repository->maybe_perform_action( $request['userId'], $request['actionType'] );
		return $this->get_roll_history();
	}
}
