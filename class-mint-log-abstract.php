<?php
/**
 * Methods, constants, and variables common to loggers and writers,
 * and Singleton boilerplate.
 */
abstract class Mint_Log_Abstract {

	/**
	 * Emergency: system is unusable
	 */
	const EMERG = 0;

	/**
	 * Alert: action must be taken immediately
	 */
	const ALERT = 1;

	/**
	 * Critical: critical conditions
	 */
	const CRIT = 2;

	/**
	 * Error: error conditions
	 */
	const ERR = 3;

	/**
	 * Warning: warning conditions
	 */
	const WARN = 4;

	/**
	 * Notice: normal but significant condition
	 */
	const NOTICE = 5;

	/**
	 * Informational: informational messages
	 */
	const INFO = 6;

	/**
	 * Debug: debug messages
	 */
	const DEBUG = 7;

	/**
	 * Priority numbers descend in order of importance. EMERG (0) is the most important priority. DEBUG (7) is the least important priority of the built-in priorities. You may define priorities of lower importance than DEBUG. When selecting the priority for your log message, be aware of this priority hierarchy and choose appropriately.
	 *
	 * Priorities taken from http://framework.zend.com/manual/1.12/en/zend.log.overview.html#zend.log.overview.builtin-priorities
	 *
	 * The priorities are not arbitrary. They come from the BSD syslog protocol, which is described in RFC-3164. The names and corresponding priority numbers are also compatible with PEAR Log and Zend_Log, which perhaps promotes interoperability between it and this class.
	 */
	 protected $_priority_labels = array(
	 	0 => 'EMERG',
	 	1 => 'ALERT',
	 	2 => 'CRIT',
	 	3 => 'ERR',
	 	4 => 'WARN',
	 	5 => 'NOTICE',
	 	6 => 'INFO',
	 	7 => 'DEBUG',
	 );

	protected static $_instance = array();

	/**
	 * Prevent direct object creation
	 */
	final private function  __construct() { }

	/**
	 * Prevent object cloning
	 */
	final private function  __clone() { }

	/**
	 * Returns new or existing singleton instance
	 *
	 * @return obj self::$_instance
	 */
	final public static function get_instance() {
		/*
		 * If you extend this class, self::$_instance will be part of the base class.
		 * In the sinlgeton pattern, if you have multiple classes extending this class,
		 * self::$_instance will be overwritten with the most recent class instance
		 * that was instantiated.  Thanks to late static binding we use get_called_class()
		 * to grab the caller's class name, and store a key=>value pair for each
		 * classname=>instance in self::$_instance for each subclass.
		 */
		$class = get_called_class();
		if ( ! isset( self::$_instance[$class] ) ) {
			self::$_instance[$class] = new $class();

			// Run's the class's _init() method, where the class can hook into actions and filters, and do any other initialization it needs
			self::$_instance[$class]->_init();
		}
		return self::$_instance[$class];
	}

	public function get_priority_string( $priority ) {
		 if ( isset( $this->_priority_labels[$priority] ) ) {
			 return $this->_priority_labels[$priority];
		 }
		 return 'Unknown';
	}

}

//EOF