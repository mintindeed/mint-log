<?php
/*
Requires PHP 5.3 or greater
Writers will autoload when attached


Cloud logging providers:
http://www.loggly.com/
http://www.sumologic.com/
https://airbrake.io/
https://papertrailapp.com/
https://www.splunkstorm.com/
*/

include __DIR__ . '/class-wpcom-log-abstract.php';
include __DIR__ . '/class-wpcom-log-writer-abstract.php';
include __DIR__ . '/class-wpcom-log.php';

//Example usage:
$logger = WPCOM_Log::get_instance();
$writer = $logger->attach_writer( 'WPCOM_Log_Writer_Email', 'dev', 'wpcom-log' );
if ( ! is_wp_error( $writer ) ) {
	$writer->add_recipients( 'gkoen@pmc.com' )->send_log_as_attachment();
}
$logger->log( "Hello, world" );

//EOF