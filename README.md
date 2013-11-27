WordPress.com Remote Logging
============================
Extensible logging to remote providers

* Requires PHP 5.3 or greater
* License: GPLv2 or later
* License URI: http://www.gnu.org/licenses/gpl-2.0.html

Example usage:
--------------

	// 1) Register your custom log writer
	$email_writer_args = array(
		'path' => 'themes/vip/dev/wpcom-log/class-wpcom-log-writer-email.php',
		'class' => 'WPCOM_Log_Writer_Email',
	);
	WPCOM_Log::get_instance()
	         ->register_writer( $email_writer_args );

	// 2) Before you start logging, attach any writers you want to use for logging
	WPCOM_Log::get_instance()
	         ->attach_writer( 'WPCOM_Log_Writer_Email' )
	         ->add_recipients( 'gkoen@pmc.com' )
	         ->send_log_as_attachment();

	// 3) Use your preferred log writer
	WPCOM_Log::get_instance()
	         ->log( "Hello, world" );

Notes
-----

Cloud logging providers:

* http://www.loggly.com/
* http://www.sumologic.com/
* https://airbrake.io/
* https://papertrailapp.com/

