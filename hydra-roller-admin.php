<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Hydra_Roller_Admin' ) ) {
	return;
}

class Hydra_Roller_Admin {
	private $user_repository;
	private $roll_repository;

	public function __construct( Hydra_Roller_User_Repository $user_repository, Hydra_Roller_Roll_Repository $roll_repository ) {
		$this->user_repository = $user_repository;
		$this->roll_repository = $roll_repository;
		add_action( 'admin_menu', array( $this, 'add_admin_page' ) );
		add_action( 'admin_init', array( $this, 'init' ) );
	}

	public function init() {
		if ( empty( $_GET['page'] ) || 'hydra-roller' !== $_GET['page'] || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! empty( $_GET['delete-user'] ) ) {
			check_admin_referer( 'hydra_delete_user' );
			$this->user_repository->remove_user( $_GET['delete-user'] );
			wp_redirect( admin_url( 'admin.php?page=hydra-roller' ) );
			exit();
		}
		if ( ! empty( $_GET['purge-history'] ) && '1' ===  $_GET['purge-history'] ) {
			check_admin_referer( 'hydra_purge_history' );
			$roll_history = $this->roll_repository->purge_history();
			wp_redirect( admin_url( 'admin.php?page=hydra-roller' ) );
			exit();
		}
	}

	public function add_admin_page() {
		add_menu_page( 'Hydra Roller', 'Hydra Roller', 'manage_options', 'hydra-roller', array( $this, 'admin_page' ) );
	}

	public function admin_page() {
		$users = $this->user_repository->get_all_users();
		$roll_history = $this->roll_repository->get_history();
		?>
			<h1>Hydra Roller Admin Panel</h1>
			<div>
				<h2>Users:</h2>
				<ul>
					<?php foreach( $users as $user ): ?>
						<li>
							<?php echo $user['name']; ?>
							<a href="<?php echo wp_nonce_url( add_query_arg( [ 'delete-user' => $user['uid'] ], admin_url( 'admin.php?page=hydra-roller' ) ), 'hydra_delete_user' ); ?>">Delete</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div>
				<h2>Rolls:</h2>
				<ul>
					<?php foreach( $roll_history as $roll ): ?>
						<li><?php echo $this->history_item_to_string( $roll ); ?></li>
					<?php endforeach; ?>
				</ul>
				<a href="<?php echo wp_nonce_url( add_query_arg( [ 'purge-history' => '1' ], admin_url( 'admin.php?page=hydra-roller' ) ), 'hydra_purge_history' ); ?>" class="button-primary">Purge history</a>
			</div>
		<?php
	}

	private function history_item_to_string( $item ) {
		$result = esc_html( $item->name ) . ': ';
		if ( isset( $item->result ) ) {
			$result .= $item->result;
		} else {
			$result .= $item->action;
		}
		$result .= ' at ' . $item->time;
		return $result;
	}
}
