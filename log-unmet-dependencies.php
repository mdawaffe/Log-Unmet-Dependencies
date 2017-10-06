<?php

/*
 * Plugin Name: Log Unmet Dependencies
 * Description: Logs an error when a style or script is *not* printed because of an unmet dependency.
 * Author: mdawaffe
 * Version: 0.1
 */

class Log_Unmet_Dependencies {
	static $instance = null;

	private $did_scripts = false;
	private $did_styles = false;

	private function __construct() {
		$this->add_hooks();
	}

	static public function instance() {
		if ( ! static::$instance ) {
			static::$instance = new Log_Unmet_Dependencies;
		}

		return static::$instance;
	}

	public function add_hooks() {
		add_action( 'shutdown', array( $this, 'output_warnings' ) );

		add_filter( 'print_scripts_array', array( $this, 'print_scripts_array' ) );
		add_filter( 'print_styles_array', array( $this, 'print_styles_array' ) );
	}

	public function remove_hooks() {
		remove_action( 'shutdown', array( $this, 'output_warnings' ) );
	}

	public function print_scripts_array( $scripts ) {
		$this->did_scripts = true;
		return $scripts;
	}

	public function print_styles_array( $styles ) {
		$this->did_styles = true;
		return $styles;
	}

	public function get_unmet_dependencies( $wp_dependencies ) {
		// Should we also check $wp_dependencies->in_footer?
		// I don't think so. In any normal page load, we'd process those
		// by the end of the page as well.
		$unmet = array_diff( $wp_dependencies->queue, $wp_dependencies->done );

		$return = [];
		foreach ( $unmet as $handle ) {
			$dependencies = $this->get_all_dependencies( $wp_dependencies, $handle );
			$missing = array_diff( $dependencies, $wp_dependencies->done );
			$return[$handle] = $missing;
		}

		return $return;
	}

	public function output_warnings() {
		global $wp_scripts, $wp_styles;

		if ( $this->did_scripts ) {
			$unmet_scripts = $this->get_unmet_dependencies( $wp_scripts );

			foreach ( $unmet_scripts as $script => $missing ) {
				trigger_error( wp_sprintf( _n(
					"Script '%s' was not printed because of an unmet dependency. It looks like %l is missing.",
					"Script '%s' was not printed because of an unmet dependency. It looks like %l are missing.",
					count( $missing ),
					'log-unmet-dependencies'
				), $script, array_map( 'escapeshellarg', $missing ) ) );
			}
		}

		if ( $this->did_styles ) {
			$unmet_styles  = $this->get_unmet_dependencies( $wp_styles );

			foreach ( $unmet_styles as $style => $missing ) {
					trigger_error( wp_sprintf( _n(
					"Style '%s' was not printed because of an unmet dependency. It looks like %l is missing.",
					"Style '%s' was not printed because of an unmet dependency. It looks like %l are missing.",
					count( $missing ),
					'log-unmet-dependencies'
				), $style, array_map( 'escapeshellarg', $missing ) ) );
			}
		}
	}

	public function get_all_dependencies( $wp_dependencies, $handle ) {
		$item = $wp_dependencies->query( $handle );
		if ( ! $item ) {
			return [];
		}

		$dependencies = $item->deps;
		if ( ! $dependencies ) {
			return [];
		}

		return array_unique( array_merge(
			$dependencies,
			call_user_func_array( 'array_merge', array_map( function( $dependency ) use ( $wp_dependencies ) {
				return $this->get_all_dependencies( $wp_dependencies, $dependency );
			}, $dependencies ) )
		) );
	}
}

Log_Unmet_Dependencies::instance();
