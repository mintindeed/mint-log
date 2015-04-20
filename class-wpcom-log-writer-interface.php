<?php
interface WPCOM_Log_Writer_Interface {
	public function send_log( $messages, $log );
}

//EOF