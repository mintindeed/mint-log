<?php
abstract class WPCOM_Log_Writer_Abstract extends WPCOM_Log_Abstract {

	const CACHE_GROUP = 'wpcom-log';

	protected $_cache_key = 'default';

	protected $_default_log_data = array(
		'last_run' => 0,
		'messages' => array(),
		'log' => array(),
	);

	public $log_data = array();

	public $has_cache = false;

	/**
	 * How many seconds between e-mails
	 * @var int Seconds
	 */
	protected $_throttle = 60;

	protected function _init() {
		$class_name = get_called_class();

		$this->_cache_key = $class_name;
		$cache_data = wp_cache_get( $this->_cache_key, self::CACHE_GROUP, false, $this->has_cache );
		$this->log_data = wp_parse_args( $cache_data, $this->_default_log_data );

		// Attach the writer
		$writer = $class_name::get_instance();
		add_action( 'wpcom_send_log', array( $writer, 'process_log' ), 10, 2 );
	}

	/**
	 *
	 * @param array $messages
	 * @param array $log

	 * @return null|obj $caught_error WP_Error object (if error), or null (no error)
	 */
	public function process_log( $messages, $log ) {
		$now = time();
		$this->log_data['last_run'] = ( $this->log_data['last_run'] ) ? $this->log_data['last_run'] : $now;
		$this->log_data['messages'] = (array) $this->log_data['messages'] + (array) $messages ;
		$this->log_data['log'] = (array) $this->log_data['log'] + (array) $log;

		// If it's not time to send the log then add the message and bail
		if ( $this->has_cache && $this->_throttle > ( $now - $this->log_data['last_run'] ) ) {
			wp_cache_set( $this->_cache_key, $this->log_data, self::CACHE_GROUP );
			return;
		}

		if ( ! $this->log_data['log'] ) {
			return new WP_Error( 'error', 'No data to log' );
		}

		$formatted_messages = $this->_format_messages( $this->log_data['messages'], $this->log_data['log'] );

		$caught_error = $this->_send_log( $formatted_messages );

		wp_cache_delete( $this->_cache_key, self::CACHE_GROUP );

		return $caught_error;
	}

	protected function _send_log( $formatted_messages ) {
		return $formatted_messages;
	}

	protected function _format_messages( $messages, $log ) {
		$formatted_messages = '';

		foreach ( $log as $timestamp => $message_keys ) {
			$timestamp = (float) $timestamp;

			foreach ( $message_keys as $message_key ) {
				$message = $messages[ (string) $message_key ];
				$formatted_messages .= date( 'c', $timestamp ) . "\t";
				$formatted_messages .= $this->get_priority_string( $message['priority'] ) . "\t";
				$formatted_messages .= $message['message'] . "\t";
				if ( $message['count'] > 1 ) {
					$formatted_messages .= "\t\t" . 'The previous message was logged ' . $message['count'] . ' times.';
				}
				$formatted_messages .= PHP_EOL;
			}

		}

		return $formatted_messages;
	}
}

//EOF
