WordPress.com Remote Logging
============================
Extensible logging to remote providers

* Requires PHP 5.3 or greater
* License: GPLv2 or later
* License URI: http://www.gnu.org/licenses/gpl-2.0.html

Example usage:
--------------

	// 1) Register your custom log writer
	require __DIR__ . '/class-wpcom-log-writer-email.php';
	WPCOM_Log_Writer_Email::get_instance();

	// 2) Before you start logging, do any setup your logger needs
	WPCOM_Log_Writer_Email::get_instance()
	         ->add_recipients( 'gkoen@pmc.com' )
	         ->send_log_as_attachment();

	// 3) Log. Use your preferred log writer
	WPCOM_Log::get_instance()
	         ->log( "Hello, world" );

Notes
-----

Cloud logging providers:

* http://www.loggly.com/
* http://www.sumologic.com/
* https://airbrake.io/
* https://papertrailapp.com/

