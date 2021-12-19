<?php
/**
 * Plugin Name: Hook Logger Plugin
 * Plugin URI: http://github.com/brucealdridge/
 * Description: Debug hooks in WordPress
 * Version: 1.0
 * Author: bruce aldridge
 * Author URI: http://brucealdridge.com
 * License: GPL2
 */

/*  Copyright 2021  bruce aldridge  (email : bruce.aldridge@gmail.com)
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class Hook_Logger_Plugin {

	/**
	 * @var string[] List of events to ignore.
	 * These are set as a default exclude list as they come up often and make debugging difficult.
	 * Can either be strings or regex.
	 * Regex must start with a / eg '/mail/'
	 * Strings will be matched if the string starts with that specifc string. eg 'option' will match 'option_test' but not 'test_option'
	 */
	public $exclude = [
		'gettext',
		'jetpack',
		'option',
		'alloptions',
		'ngettext',
		'default_option',
		'pre_option'
	];

	/**
	 * @var array list of events to explicitly include
	 * @see $exclude for the details on how this works
	 */
	public $include = [];

	/**
	 * @var string|null If monitor_from is set it will ignore everything up until that point.
	 * Perfect if you only care about events after a certain point
	 */
	public $monitor_from = null;

	/**
	 * @var bool automatically dump the log to a file for later review
	 */
	public $log_to_file = false;

	/**
	 * @var array internal array of actions and callers
	 */
	public $events = [];

	function __construct() {
		add_action( 'all', [ $this, 'log' ] );
	}

	public function log() {
		$current_action = current_action();

		if ( $this->monitor_from === $current_action ) {
			$this->monitor_from = null;
		}
		if ( null !== $this->monitor_from ) {
			return;
		}

		// if we have an inclusion list and it's not in there, skip it.
		if ( [] !== $this->include && ! $this->match( $current_action, $this->include ) ) {
			return;
		}

		// if we have an exclusion specified for the string, lets skip it
		if ( $this->match( $current_action, $this->exclude ) ) {
			return;
		}

		$e     = new \Exception();
		$trace = $e->getTrace();

		// The place that fired the action is 3 steps back on the backtrace, grab it
		$trace           = isset( $trace[3] ) ? $trace[3] : [];
		$source_location = isset( $trace['file'] ) ? $trace['file'] : null;
		$source_location = $source_location ? $source_location . ':' . $trace['line'] : '';

		// args might be helpful in future version, commented out for now.
		// $trace_args = $trace['args'];

		$this->events[] = [
			'time'   => new \DateTime(),
			'action' => $current_action,
			'source' => $source_location
		];
	}

	/**
	 * Check if the current action matches an item in the search_array
	 *
	 * @param string $current_action
	 * @param array $search_array
	 *
	 * @return bool
	 */
	private function match( $current_action, array $search_array ) {
		foreach ( $search_array as $search_string ) {
			// check if its a regex
			if ( strpos( $search_string, '/' ) === 0 ) {
				if ( preg_match( $search_string, $current_action ) >= 1 ) {
					return true;
				}
				continue;
			}
			// match against a normal string, check if the search string matches the start of the current action
			if ( strpos( $current_action, $search_string ) === 0 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Will return the order of actions / source
	 * @return string
	 */
	public function dump() {
		return implode( "\n", array_map( function ( $event ) {
			return $event['time']->format( 'Y-m-d H:i:s.v' ) . ' ' . $event['action'] . ' ' . $event['source'];
		}, $this->events ) );
	}

	public function __destruct() {
		if ( $this->log_to_file === false ) {
			return;
		}
		$this->export();
	}

	/**
	 * Export to a log file
	 * @return void
	 */
	public function export() {
		$now         = new \DateTime();
		$log_file    = __DIR__ . sprintf( '/action-%s.log', $now->format( 'YmdHis-v' ) );
		$request_uri = $_SERVER['REQUEST_URI'];

		/**
		 * Prevent logging noisy requests be default
		 */
		if (strpos($request_uri, '/wp-cron.php') !== false) {
			return;
		}
		if (strpos($request_uri, 'wp-admin/?service-worker') !== false) {
			return;
		}
		if (strpos($request_uri, '/wp-json/wc-admin/') !== false) {
			return;
		}
		if (strpos($request_uri, '/wp-admin/admin-ajax.php') !== false && ( $_POST['action'] ?? '' ) === 'heartbeat' ) {
			return;
		}

		$content     = "Date: " . $now->format( 'r' ) . "\nURL: {$request_uri}\nMethod: URL: {$_SERVER['REQUEST_METHOD']}\nPOST:\n";
		$content     .= var_export( $_POST, 1 ) . "\n\n";
		file_put_contents( $log_file, $content . $this->dump() );
	}
}
