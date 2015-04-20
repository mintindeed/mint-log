<?php
class WPCOM_Log_Writer_Email extends WPCOM_Log_Writer_Abstract {
	/**
	 * Override the default cache key
	 * @var string
	 */
	protected $_cache_key = 'email';

	/**
	 * An array of e-mail addresses to send the logs to.
	 * @var array
	 */
	protected $_recipients = array();

	/**
	 * Mail headers to send with the message.
	 * @var array
	 */
	protected $_headers = array();

	/**
	 * The subject of the message.
	 * @var string
	 */
	protected $_subject = array();

	/**
	 * Name to use when attaching the log to e-mails.
	 * @var string
	 */
	protected $_attachment_name = null;

	/**
	 * Add a single recipient
	 * @param string $recipient Email address
	 * @return obj $this
	 */
	public function add_recipient( $recipient = '' ) {
		return $this->add_recipients( $recipient );
	}

	/**
	 * Add multiple recipients
	 * @param array $recipients Array of email addresses
	 * @return obj $this
	 */
	public function add_recipients( $recipients = array() ) {
		$this->_recipients = $this->_recipients + (array) $recipients;

		return $this;
	}

	/**
	 * Set the email subject
	 * @param string $subject
	 * @return obj $this
	 */
	public function set_subject( $subject = '' ) {
		$this->_subject = $subject;

		return $this;
	}

	/**
	 * Add a single header
	 * @param string $header
	 * @return obj $this
	 */
	public function add_header( $header = '' ) {
		return $this->add_headers( $header );
	}

	/**
	 * Add multiple headers
	 * @param array $headers Array of headers
	 * @return obj $this
	 */
	public function add_headers( $headers = array() ) {
		$this->_headers = $this->_headers + (array) $headers;

		return $this;
	}

	/**
	 * Flag to send the log as an attachment in addition to within the body
	 * @param string $attachment_name
	 * @return obj $this
	 */
	public function send_log_as_attachment( $attachment_name = null ) {
		if ( ! $attachment_name ) {
			$this->_attachment_name = self::CACHE_GROUP . '_' . $this->_cache_key . '_' . date( 'Y-m-d-H-i-s' ) . '.log';
		} else {
			$this->_attachment_name = sanitize_file_name( $attachment_name );
		}

		return $this;
	}

	/**
	 * Sends the logged messages to the logger. This is where the actual
	 * logging happens.
	 * @param string $formatted_messages
	 * @return void
	 */
	protected function _send_log( $formatted_messages ) {

		if ( ! $this->_recipients ) {
			return new WP_Error( 'error', 'Email has no recipients' );
		}

		if ( ! $this->_subject ) {
			$this->_subject = '[' . self::CACHE_GROUP .  '] ' . str_replace( array( '-', '_' ), ' ', $this->_cache_key ) . ' log';
		}

		if ( $this->_attachment_name ) {
			$tmp_attachment = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->_attachment_name;

			file_put_contents( $tmp_attachment, $formatted_messages );

		} else {
			$tmp_attachment = null;
		}

		wp_mail( $this->_recipients, $this->_subject, $formatted_messages, $this->_headers, $tmp_attachment );

		if ( $this->_attachment_name ) {
			unlink( $tmp_attachment );
		}
	}
}

//EOF