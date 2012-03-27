<?php

$wp_load_location = sixscan_notice_find_wp_load_location();

if ( $wp_load_location == FALSE ){
	header( "HTTP/1.1 500 Can't initialize WP environment" );
	exit( 0 );
}
	
require( $wp_load_location );
require_once( '../../admin/includes/common.php' );

if ( defined( 'SIXSCAN_VERSION' ) == FALSE ){
	header( "HTTP/1.1 500 Can't initialize environment" );
	exit( 0 );
}
	
if ( sixscan_common_is_regdata_present() != TRUE ){
	header( "HTTP/1.1 500 6Scan not registered" );
	exit( 0 );
}

/*	Backwards compatibility. Plugins of versions <1.0.5 had another "active" indication */
$backward_compat_active = get_option( 'sixscan_setupaccount' );
if ( ( $backward_compat_active == 'SETUP_STAGE_RUNNING' ) || ( $backward_compat_active == 'SETUP_STAGE_INSTALLED' ) ){
	/*	Cleanup and activate for new version */
	delete_option( 'sixscan_setupaccount' );
	sixscan_common_set_account_active( TRUE );	
}

/*	Verify process. Make sure that sites belongs to the user that registered it */
if ( isset( $_GET[ SIXSCAN_NOTICE_VERIFICATION_NAME ] ) && ( isset( $_GET[ SIXSCAN_NOTICE_AUTH_NAME ] ) ) ){
	
	$expected_auth_id = md5( sixscan_common_get_api_token() . sixscan_common_get_site_id() );
	if ( ( $_GET[ SIXSCAN_NOTICE_VERIFICATION_NAME ] == sixscan_common_get_site_id() ) &&
		( $_GET[ SIXSCAN_NOTICE_AUTH_NAME ] == $expected_auth_id ) ){
		
		echo SIXSCAN_VERIFICATION_DELIMITER . sixscan_common_get_verification_token() . SIXSCAN_VERIFICATION_DELIMITER;		
	}
	else{
		header( "HTTP/1.1 500 Bad verification token" );		
	}
	
	exit( 0 );
}
		
if ( sixscan_common_is_account_active() != TRUE ){
	header( "HTTP/1.1 500 6Scan not active" );
	exit( 0 );
}

$oracle_nonce = intval( $_GET[ 'nonce' ] );
$last_nonce = intval( get_option( SIXSCAN_OPTION_COMM_LAST_SIG_UPDATE_NONCE ) );

if ( $last_nonce >= $oracle_nonce ){
	header( "HTTP/1.1 500 Bad nonce request" );
	exit( 0 );
}
	
$api_token = sixscan_common_get_api_token();
$site_id = sixscan_common_get_site_id();
$expected_token = md5( SIXSCAN_SIGNATURE_SCHEDULER_SALT . $oracle_nonce . $api_token );
$received_token = $_GET[ 'token' ];

if ( $expected_token != $received_token ){
	header( "HTTP/1.1 418 I'm a teapot" );	//as defined in RFC2324: http://tools.ietf.org/html/rfc2324
	exit( 0 );
}

/*	From now on, all errors will be caught and shown */
sixscan_common_show_all_errors();

/*	Mark this nonce as already used */
update_option( SIXSCAN_OPTION_COMM_LAST_SIG_UPDATE_NONCE , $oracle_nonce );	
	
/*	Include the update functionality */
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
require_once( 'update.php' );

/* Activated this blog with 6Scan server */
if ( isset( $_GET[ SIXSCAN_NOTICE_ACCOUNT_ENABLED ] ) ){
	if ( intval( $_GET[ SIXSCAN_NOTICE_ACCOUNT_ENABLED ] ) == 1 ){
		sixscan_common_set_account_operational( TRUE );
	}	
}

/*	Default value, in case we don't need to send security env */
$security_result = TRUE;
if ( isset( $_GET[ SIXSCAN_NOTICE_SECURITY_ENV_NAME ] ) && ( $_GET[ SIXSCAN_NOTICE_SECURITY_ENV_NAME ] == 1 ) ){
	$security_result = sixscan_send_security_environment( $site_id , $api_token );
}

if ( isset( $_GET[ SIXSCAN_NOTICE_SECURITY_LOG_NAME ] ) && ( $_GET[ SIXSCAN_NOTICE_SECURITY_LOG_NAME ] == 1 ) ){
	$tmp_result = sixscan_send_security_log( $site_id ,  $api_token );
	
	/* Checking result values, and appending  error message, if needed */
	if ( $security_result === TRUE )
		$security_result = $tmp_result;
	else
		$security_result .= "  " . $tmp_result;	
}

/* Update signatures, if needed */
$error_list = "";
if ( isset( $_GET[ SIXSCAN_NOTICE_UPDATE_NAME ] ) && ( $_GET[ SIXSCAN_NOTICE_UPDATE_NAME ] == 1 ) ){
	$error_list = sixscan_signatures_update_request_total( $site_id ,  $api_token );
}

if ( ( $security_result === TRUE ) && ( $error_list == "" ) ){
	header( 'HTTP/1.1 200 OK ' );
	print "OK";
}
else{
	$reported_error = "";
	
	if ( $security_result != TRUE )
		$reported_error .= $security_result;

	if ( $error_list != "" )
		$reported_error .= $error_list;
	
	header( 'HTTP/1.1 500 ' . $reported_error );	
}
/*	And exit */
exit( 0 );


function sixscan_notice_find_wp_load_location(){
	$current_wp_load_location = "../../../../../wp-load.php";	
	$max_possible_nesting_levels = 5;
	
	for ( $i = 0; $i < $max_possible_nesting_levels ; $i++ ){
		if ( file_exists ( $current_wp_load_location ) == TRUE ){
			return $current_wp_load_location;
		}
		else{
			$current_wp_load_location = "../" . $current_wp_load_location;
		}
	}
	return FALSE;
}

function sixscan_send_security_environment( $site_id ,  $api_token ){

	$plugin_list = get_plugins();	
	$data_arr = array();
	
	foreach ( $plugin_list as $plugin => $plugin_data ){
		$plugin_info = array();
		$plugin_info[ "Name" ] = $plugin_data[ "Name" ];
		$plugin_info[ "Version" ] = $plugin_data[ "Version" ];
		$plugin_info[ "URL" ] = $plugin;
		$plugin_info[ "IsActive" ] = is_plugin_active( $plugin ) == TRUE ? "true" : "false";
		$data_arr[] = $plugin_info;		
	}
	
	$enc_data = json_encode( $data_arr );
	
	$version_update_url = SIXSCAN_BODYGUARD_6SCAN_UPDATE_SEC_URL 	. "?site_id=" . $site_id 
																	. "&api_token=" . $api_token;

	$response = sixscan_common_request_network( $version_update_url , $enc_data , "POST" );																	
	
	if ( is_wp_error( $response ) ) {
		return $response->get_error_message();
	}
		
	return TRUE;
}

function sixscan_send_security_log( $site_id ,  $api_token ){
	$version_update_url = SIXSCAN_BODYGUARD_6SCAN_UPDATE_LOG_URL 	. "?site_id=" . $site_id 
																	. "&api_token=" . $api_token;	
	
	$log_fname = "../../" . SIXSCAN_SECURITY_LOG_FILENAME;
	if ( is_file( $log_fname ) === FALSE)
		return TRUE;
		
	$log_data = file_get_contents( $log_fname );
	
	if ( $log_data === FALSE )
		$log_data = "";	#empty
	
	$response = sixscan_common_request_network( $version_update_url , $log_data , "POST" );	
	
	if ( is_wp_error( $response ) ) {
		return $response->get_error_message();
	}
	
	unlink( $log_fname );
	
	return TRUE;
}

?>