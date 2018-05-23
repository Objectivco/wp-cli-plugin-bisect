<?php
namespace ObjectivPluginBisect\App;

/**
 * Class Main
 * @package Objectiv\App
 */
class Main {
	var $processor;

	public function __construct() {
		// Silence is golden, yo
	}

	public function run() {
		// If we're in a WP-CLI context, load the WP-CLI command.
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$this->processor = new Processor();

			\WP_CLI::add_command( 'plugin-bisect', $this->processor );
		} else {
			add_filter('option_active_plugins', array($this, 'remove_test_plugins') );
		}
	}

	function turn_off_wc_emails($value, $instance, $the_value, $key, $empty_value) {
		error_log("THE KEY: " . $key);
		if ( $key == "enabled" ) {
			return "no";
		}

		return $value;
	}

	function remove_test_plugins( $active_plugins ) {
		$to_test  = get_option( 'objectiv_plugin_bisect_to_test' );

		if ( ! empty($to_test) && is_array($to_test) ) {
			add_action( 'admin_notices', array($this, 'warn_the_user_testing_is_active') );

			return array_diff( $active_plugins, $to_test );
		}

		return $active_plugins;
	}

	function warn_the_user_testing_is_active() {
		?>
		<div class="notice notice-warning">
			<p><?php _e( 'Warning: WP-CLI Plugin Bisect is running! Some plugins are being deactivated! To deactivate, run: wp plugin-bisect end' ); ?></p>
		</div>
		<?php
	}

	function activate() {

	}

	function deactivate() {

	}
}