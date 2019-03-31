<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Hydra_Roller_Shortcode' ) ) {
	return;
}

class Hydra_Roller_Shortcode {
	private $user_repository;

	public function __construct( Hydra_Roller_User_Repository $user_repository ) {
		$this->user_repository = $user_repository;
		add_shortcode( 'hydra_roller', array( $this, 'render_shortcode' ) );
	}

	function render_shortcode() {
		$user = $this->user_repository->get_user();
		if ( $user ) {
			$this->render_logged_in( $user );
			return;
		}

		$this->render_logged_out();
	}

	function render_logged_out() {
		?>
		<form method="post">
			<h2>Hey Hydra</h2>
			<p>Enter your preferred username to roll a d20 with others!</p>
			<input type="text" name="hydra_name" placeholder="Enter your name" maxlength="50" />
			<input type="submit" />
			<?php if ( isset( $_GET['hydra_error'] ) && '1' === $_GET['hydra_error'] ): ?>
				<p style="color: red;">Could not register the name. Please try again or try a different name as this one might already be taken.</p>
			<?php endif; ?>
		</form>
		<?php
	}

	function render_logged_in( $user ) {
		$user_id = $user['id'];
		$name = $user['name'];
		$plugin_data = get_plugin_data( dirname( __FILE__ ) . '/hydra-roller.php', false, false );
		wp_register_script( 'hydra_roller_js', plugins_url( 'hydra-roller.js', __FILE__ ), array( 'jquery' ), $plugin_data['Version'] );
		wp_localize_script( 'hydra_roller_js', 'hydra_data', array(
			'ajax_url' => get_rest_url() . 'hydra_roller/v1/roll',
			'user_id' => $user_id,
		) );
		wp_enqueue_script( 'hydra_roller_js' );

		?>
			<h2>Hello <?php echo esc_html( $name ) ?></h2>
			<h3>Last hour's leader is <span id="hydra-leader">n/a</span></h3>
			<button id="hydra-roll">ROLL!!!</button>
			<div style="border: 1px solid #ddd; width: 500px; height: 500px; padding: 5px; margin: 10px; overflow-y: scroll;">
				<ul id="hydra-results" style="list-style: none; margin: 0;">
					<li>Loading...</li>
				</ul>
			</div>
			<button id="hydra-flip">(ノಠ益ಠ)ノ彡┻━┻</button>
			<button id="hydra-fix">┬──┬ ノ( ゜-゜ノ)</button>
		<?php
	}
}
