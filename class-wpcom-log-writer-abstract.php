<?php
abstract class WPCOM_Log_Writer_Abstract extends WPCOM_Log_Abstract implements WPCOM_Log_Writer_Interface {

	const CACHE_GROUP = 'wpcom-log';

	/**
	 * Cache key for flood protection
	 * @var string
	 */
	protected $_cache_key = 'default';

	/**
	 * Holds the messages and meta data for logging
	 * @var array
	 */
	protected $_default_log_data = array(
		'last_run' => 0,
		'messages' => array(),
		'log' => array(),
	);

	/**
	 * Holds the messages and meta data for logging
	 * @var array
	 */
	public $log_data = array();

	/**
	 * Whether wp_cache_get() has cached data
	 * @var bool
	 */
	public $has_cache = false;

	/**
	 * How many seconds between e-mails
	 * @var int Seconds
	 */
	protected $_throttle = 60;

	/**
	 * Always call this parent method in child classes. Classes implementing
	 * this abstract can override the _init() method. The _init() method does
	 * three critical things: 1) Sets the cache key to something unique; 2)
	 * Implements flood protection; 3) Attaches the log writer.
	 * @return void
	 */
	protected function _init() {
		$class_name = get_called_class();

		$this->_cache_key = $class_name;
		$cache_data = wp_cache_get( $this->_cache_key, self::CACHE_GROUP, false, $this->has_cache );
		$this->log_data = wp_parse_args( $cache_data, $this->_default_log_data );

		$writer = $class_name::get_instance();
		add_action( 'wpcom_send_log', array( $writer, 'send_log' ), 10, 2 );
	}

	/**
	 * Processes log data and sends to the logger. You probably shouldn't
	 * override this method, _send_log() is meant to be the method that actually * passes the log data to the logging service.
	 * @param array $messages
	 * @param array $log
	 * @return null|obj $caught_error WP_Error object (if error), or null (no error)
	 */
	public function send_log( $messages, $log ) {
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

	/**
	 * This is where you connect to the 3rd party logging service and send it
	 * your log data.
	 * @param string $formatted_messages
	 * @return string|obj $caught_error WP_Error object (if error), or null (no error)
	 */
	protected function _send_log( $formatted_messages ) {
		$caught_error = ( empty( $formatted_messages ) ) ? WP_Error( 'error', 'No logger implemented.' ) : null;
		return $caught_error;
	}

	/**
	 * Takes the raw log data and formats it. Override this if you want to
	 * control the log format.
	 * @param array $messages
	 * @param array $log
	 * @return string $formatted_messages
	 */
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
