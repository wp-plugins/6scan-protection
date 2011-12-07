<?php

$wp_load_location = find_wp_load_location();

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

$error_list = sixscan_signatures_update_request_total( $site_id ,  $api_token );

/*	Show the status header */
if ( $error_list != "" ){
	header( 'HTTP/1.1 500' . $error_list );		
	sixscan_common_report_analytics( SIXSCAN_ANALYTICS_NORMAL_CATEGORY , SIXSCAN_ANALYTICS_NORMAL_UPDATING_ACT , SIXSCAN_ANALYTICS_FAIL_PREFIX_STRING . $error_list );
}
else{
	header( 'HTTP/1.1 200 OK ' );
	print 'Engine update successful';
}

/*	And exit */
exit( 0 );


function find_wp_load_location(){
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

?>