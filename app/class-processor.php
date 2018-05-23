<?php
namespace Objectiv_PerformanceBisect\App;

class Processor extends \WP_CLI_Command {
	/**
	 * Route migration requests
	 *
	 * ## OPTIONS
	 *
	 * <type>
	 * : The type of the data to migrate.
	 *
	 * [<ID>]
	 * : The ID of the specific product or order.
	 *
	 * [--force]
	 * : Force migration or not
	 *
	 * ## EXAMPLES
	 *
	 *     wp shopp-woo migrate products [1234]
	 *     wp shopp-woo migrate orders [1234]
	 *
	 * @when after_wp_load
	 */
	function migrate( $args, $assoc_args ) {
		if ( ! empty( $args[0] ) ) {
			switch ( $args[0] ) {
				case 'start':
					$this->start( $args, $assoc_args );
					break;
				case 'good':
					$this->good( $args, $assoc_args );
					break;
				case 'bad':
					$this->bad( $args, $assoc_args );
					break;
				case 'end':
					$this->end( $args, $assoc_args );
				default:
					$this->default();
					break;
			}
		}
	}

	function debug() {
		$to_test  = get_option( 'objectiv_performance_bisect_to_test' );
		$untested = get_option( 'objectiv_performance_bisect_untested' );
		\WP_CLI::debug('To test: ' . print_r($to_test, true) );
		\WP_CLI::debug('Untested: ' . print_r($untested, true) );
	}

	function start( $args, $assoc_args ) {
		if ( get_option( 'objectiv_performance_bisect_to_test' ) !== false && $assoc_args['force'] !== 'true' ) {
			\WP_CLI::error( 'A performance-bisect session is already running. Override with --force=true' );
		}

		$active_plugins = get_option( 'active_plugins' );
		$position       = array_search( OBJECTIV_PERFORMANCE_BISET_SELF, $active_plugins );
		unset( $active_plugins[ $position ] );

		$offset = 0;

		if ( count($active_plugins) % 2 != 0 ) {
			$offset = 1;
		}

		update_option( 'objectiv_performance_bisect_to_test', array_slice( $active_plugins, 0, count( $active_plugins ) / 2 + $offset ) );
		update_option( 'objectiv_performance_bisect_untested', array_slice( $active_plugins, count( $active_plugins ) / 2 + $offset ) );

		$this->debug();

		\WP_CLI::success( 'Performance Bisect session started. Plugins to test: ' . $this->count_remaining() );
	}

	function end( $args, $assoc_args ) {
		$this->clean_up();
		\WP_CLI::success( 'Performance Bisect session ended. Everything is back to normal.' );
	}

	function good( $args, $assoc_args ) {
		// This indicates one of the plugins in objectiv_performance_bisect_disable is bad
		$to_test  = get_option( 'objectiv_performance_bisect_to_test' );
		$untested = get_option( 'objectiv_performance_bisect_untested' );

		if ( count( $to_test ) == 1 ) {
			$this->clean_up();
			\WP_CLI::success( 'Done! It looks like ' . $to_test[0] . ' is the culprit!' );
			exit();
		}

		$offset = 0;

		if ( count($to_test) % 2 != 0 ) {
			$offset = 1;
		}
		update_option( 'objectiv_performance_bisect_to_test', array_slice( $to_test, 0, count( $to_test ) / 2 + $offset ) );
		update_option( 'objectiv_performance_bisect_untested', array_slice( $to_test, count( $to_test ) / 2 + $offset ) );

		$this->debug();

		\WP_CLI::line( "Great! Let's keep going! Try again and mark the result as good or bad. Plugins to test: " . $this->count_remaining() );
		\WP_CLI::line( '   wp performance-bisect good|bad' );
	}

	function bad( $args, $assoc_args ) {
		// This indicates *none* of the plugins in objectiv_performance_bisect_disable are bad
		$to_test  = get_option( 'objectiv_performance_bisect_to_test' );
		$untested = get_option( 'objectiv_performance_bisect_untested' );

		if ( count( $to_test ) == 1 && count($untested) == 1 ) {
			$this->clean_up();
			\WP_CLI::success( 'Done! It looks like ' . $untested[0] . ' is the culprit!' );
			exit();
		}

		update_option( 'objectiv_performance_bisect_to_test', $to_test   = $untested );
		update_option( 'objectiv_performance_bisect_untested', $untested = [] );

		$this->debug();

		\WP_CLI::line( 'Ok, good to know! Try again and mark the result as good or bad. Plugins to test: ' . $this->count_remaining() );
		\WP_CLI::line( '   wp performance-bisect good|bad' );
	}

	function clean_up() {
		delete_option( 'objectiv_performance_bisect_to_test' );
		delete_option( 'objectiv_performance_bisect_untested' );
	}

	function count_remaining() {
		$to_test  = get_option( 'objectiv_performance_bisect_to_test' );
		$untested = get_option( 'objectiv_performance_bisect_untested' );

		return count( $to_test ) + count( $untested );
	}

	function default() {
		$message_lines = array(
			'Performance Bsiect requires at least one additional command',
			'   wp performance-bisect start',
			'   wp performance-bisect good',
			'   wp performance-bisect bad',
			'   wp performance-bisect end',
		);

		\WP_CLI::error_multi_line( $message_lines );
	}
}
