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
	
if ( get_option( SIXSCAN_OPTIONS_SETUP_ACCOUNT ) == SIXSCAN_ACCOUNT_NOT_ACTIVE ) {
	header( "HTTP/1.1 500 6Scan not installed" );
	exit( 0 );
}

if ( sixscan_common_is_oracle_verified() != TRUE ){
	header( "HTTP/1.1 500 6Scan account is not yet verified" );
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

/*	Mark this nonce as already used */
update_option( SIXSCAN_OPTION_COMM_LAST_SIG_UPDATE_NONCE , $oracle_nonce );	
	
/*	Include the update functionality */
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
require_once( 'update.php' );

/*	Default value, in case we don't need to send security env */
$security_result = TRUE;
if ( isset( $_GET[ SIXSCAN_NOTICE_SECURITY_ENV_NAME ] ) && ( $_GET[ SIXSCAN_NOTICE_SECURITY_ENV_NAME ] == 1 ) ){
	$security_result = sixscan_send_security_environment( $site_id , $api_token );
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
		
	$response = wp_remote_post( $version_update_url , array(		
		'timeout' => 30,
		'redirection' => 5,
		'httpversion' => '1.1',
		'sslverify' => false,
		'blocking' => true,
		'headers' => array(),
		'body' => $enc_data,
		'cookies' => array()
		)
	);	
	
	if ( is_wp_error( $response ) ) {
		return $response->get_error_message();
	}
		
	return TRUE;
}

?>