<?php
class WPCOM_Log extends WPCOM_Log_Abstract {

	protected $_logger_paths = array();

	/**
	 * Stores each message and a count of how many times it was called.
	 *
	 * @var array $this->_messages[$message_hash] = array( 'message' => $message, 'priority' => $priority, 'count' => intval($repeats) );
	 */
	protected $_messages = array();

	/**
	 * A log of the first occurrence of each message.
	 * This is a multidimensional array in case of race conditions or unexpectedly fast operations.
	 *
	 * @var array $this->_log[microtime()][] = $message_hash;
	 */
	protected $_log = array();

	/**
	 * Keeps a count of the total number of messages received.
	 * Used to prevent log flooding.  By keeping the log and message content separate we can easily detect duplicate messages.
	 *
	 * @var int
	 */
	protected $_message_count = 0;

	/**
	 * Max number of message that may be logged
	 *
	 * @var int
	 */
	protected $_max_messages = 100;

	/**
	 * Error message text
	 */
	protected $_max_messages_limit_text = 'You have reached the max number of log messages for this request (%d)';


	/**
	 * Initialization function called when object is instantiated
	 */
	protected function _init() {
		$this->_max_messages_limit_text = sprintf( $this->_max_messages_limit_text, $this->_max_messages );

		add_action( 'shutdown', array( $this, 'send_log' ) );
	}

	public function register_writer( $args ) {

		if ( ! isset( $args['path'] ) ) {
			return new WP_Error( 'error', '"path" cannot be empty when registering a new log writer.' );
		}

		$class_path = untrailingslashit( WP_CONTENT_DIR . DIRECTORY_SEPARATOR . ltrim( $args['path'], DIRECTORY_SEPARATOR ) );

		if ( ! file_exists( $class_path ) ) {
			return new WP_Error( 'error', 'path "' . esc_html($args['path']) . '" does not exist.' );
		}

		if ( ! isset( $args['class'] ) ) {
			return new WP_Error( 'error', '"class" cannot be empty when registering a new log writer.' );
		}

		$is_valid_class_name = $this->_is_valid_class_name( $args['class'] );

		if ( ! $is_valid_class_name ) {
			return new WP_Error( 'error', esc_html( $args['class'] ) . ' is not a valid class name.' );
		}

		$this->_logger_paths[$args['class']] = $class_path;

		return $this;
	}

	protected function _is_valid_class_name( $class_name ) {
		return (bool) preg_match( '/^([a-z_]+)$/i', $class_name );
	}

	public function attach_writer( $class_name ) {

		$is_valid_class_name = $this->_is_valid_class_name( $class_name );

		if ( ! $is_valid_class_name ) {
			return new WP_Error( 'error', esc_html( $class_name ) . ' is not a valid class name.' );
		}

		if ( ! class_exists( $class_name ) ) {
			if ( ! isset( $this->_logger_paths[$class_name] ) || ! file_exists( $this->_logger_paths[$class_name] ) ) {
				return new WP_Error( 'error', 'No valid path to ' . esc_html( $class_name ) . '.  Please use the register_writer() method.' );
			}

			require_once $this->_logger_paths[$class_name];

			// Sanity check
			if ( ! class_exists( $class_name ) ) {
				return new WP_Error( 'error', esc_html( $class_name ) . ' does not exist.' );
			}

		}

		$writer = $class_name::get_instance();

		// Sanity check
		if ( ! is_callable( array( $writer, 'process_log' ) ) ) {
			return new WP_Error( 'error', esc_html( $class_name ) . '::process_log() is not callable.' );
		}

		add_action( 'wpcom_send_log', array( $writer, 'process_log' ), 10, 2 );

		return $writer;
	}

	/**
	 * Add message to the log
	 *
	 * @param string $message
	 * @param int $priority
	 */
	public function log( $message, $priority = self::INFO ) {

		// Don't log too much.
		if ( $this->_message_count > $this->_max_messages ) {
			$message = $this->_max_messages_limit_text;
			$priority = self::WARN;
		}

		// Hash using CRC32 for speed. Higher chance of digest collision than MD5, but we have a very small sample set ($this->_max_messages+1).
		// Computed 100,000 hash calculations x 100 sets on an Intel Core i5 2.4GHz and PHP 5.3.15 to get the following average times:
		// Average MD5: 0.058228240013123
		// Average crc32: 0.028677275180817
		$hash = crc32( $message );

		if ( isset( $this->_messages[$hash] ) ) {
			$this->_messages[$hash]['count']++;
		} else {
			$this->_messages[$hash] = array(
				'message' => $message,
				'priority' => $priority,
				'count' => 1,
			);
		}

		// Log the first occurrence of a message with a timestamp.
		if ( $this->_messages[$hash]['count'] === 1 ) {
			// When using microtime(true) as an array key, it drops the float
			$this->_log[ (string) microtime(true) ][] = $hash;
		}


		$this->_message_count++;
	}

	/**
	 * Convenience method for logging with the EMERG priority.
	 *
	 * @param string $message
	 */
	public function emerg( $message ) {
		$this->log( $message, self::EMERG );
	}

	/**
	 * Convenience method for logging with the ALERT priority.
	 *
	 * @param string $message
	 */
	public function alert( $message ) {
		$this->log( $message, self::ALERT );
	}

	/**
	 * Convenience method for logging with the CRIT priority.
	 *
	 * @param string $message
	 */
	public function crit( $message ) {
		$this->log( $message, self::CRIT );
	}

	/**
	 * Convenience method for logging with the ERR priority.
	 *
	 * @param string $message
	 */
	public function err( $message ) {
		$this->log( $message, self::ERR );
	}

	/**
	 * Convenience method for logging with the WARN priority.
	 *
	 * @param string $message
	 */
	public function warn( $message ) {
		$this->log( $message, self::WARN );
	}

	/**
	 * Convenience method for logging with the NOTICE priority.
	 *
	 * @param string $message
	 */
	public function notice( $message ) {
		$this->log( $message, self::NOTICE );
	}

	/**
	 * Convenience method for logging with the INFO priority.
	 *
	 * @param string $message
	 */
	public function info( $message ) {
		$this->log( $message, self::INFO );
	}

	/**
	 * Convenience method for logging with the DEBUG priority.
	 *
	 * @param string $message
	 */
	public function debug( $message ) {
		$this->log( $message, self::DEBUG );
	}

	/**
	 * Sends the log messages to any observers and flushes the log.
	 */
	public function send_log() {
		// Might want to optimize this by passing by reference or using a static var
		// but if the messages are being limited to ~100 it probably doesn't matter
		do_action( 'wpcom_send_log', $this->_messages, $this->_log );

		// Flush the in-memory log.  Log writers extending WPCOM_Log_Writer_Abstract will cache and throttle log messages.
		$this->_messages = array();
		$this->_log = array();
	}

}

//EOF