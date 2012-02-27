<?php

if ( ! defined( 'ABSPATH' ) ) 
	die( 'No direct access allowed' );
		
function sixscan_signatures_analyzer_suspicious_request(){
	
	/*	Only log suspicious requests, that were triggered by .htaccess rule */
	if ( getenv( "REDIRECT_sixscansecuritylog" ) != "1" && ( getenv( "sixscansecuritylog" ) != "1" ) ){
		return;
	}
	
	if ( is_writeable (dirname ( SIXSCAN_ANALYZER_LOG_FILEPATH ) . "/" ) == FALSE )
		return;
	
	/* If it exists, we want to limit the filesize to some maximum */
	if ( is_file( SIXSCAN_ANALYZER_LOG_FILEPATH ) && ( filesize( SIXSCAN_ANALYZER_LOG_FILEPATH  ) > SIXSCAN_ANALYZER_MAX_LOG_FILESIZE ) )
		return;
	
	$data_log = "\"" . $_SERVER['SCRIPT_NAME'] . "\" \"" . addslashes( $_SERVER['QUERY_STRING'] ) . "\" \"" . addslashes( $_SERVER['HTTP_REFERER'] ) . "\" \"" . addslashes( $_SERVER['HTTP_USER_AGENT'] ) . "\"" . SIXSCAN_SECURITY_LOG_SEPARATOR;
	file_put_contents( SIXSCAN_ANALYZER_LOG_FILEPATH , $data_log ,  FILE_APPEND );
}
	
?>