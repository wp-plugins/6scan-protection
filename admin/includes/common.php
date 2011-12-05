<?php

if ( ! defined( 'ABSPATH' ) ) 
	die( 'No direct access allowed' );

define ( 'SIXSCAN_VERSION',								'1.0.1' );

if( empty( $_SERVER[ "HTTPS" ] ) )
	define( 'SERVER_HTTP_PREFIX',						'http://' );
else
	define( 'SERVER_HTTP_PREFIX',						'https://' );	

define ( 'SIXSCAN_SERVER_ADDRESS',						'api.wp.6scan.com' );

/*	The server communication is always through SSL */
define ( 'SIXSCAN_SERVER',								'https://' . SIXSCAN_SERVER_ADDRESS . '/' );	

/*	User registration form url depends on the blog being on https/http */
define ( 'SIXSCAN_BODYGUARD_REGISTER_FORM_URL' ,		SERVER_HTTP_PREFIX . SIXSCAN_SERVER_ADDRESS .  '/dashboard/v1/register' );

define ( 'SIXSCAN_BODYGUARD_REGISTER_FORM_URL' ,		SIXSCAN_SERVER . 'dashboard/v1/register' );
define ( 'SIXSCAN_BODYGUARD_REGISTER_URL' , 			SIXSCAN_SERVER . 'wpapi/v1/register' );
define ( 'SIXSCAN_BODYGUARD_VERIFY_URL' , 				SIXSCAN_SERVER . 'wpapi/v1/verify' );
define ( 'SIXSCAN_BODYGUARD_6SCAN_UPDATE_SIG_URL' , 	SIXSCAN_SERVER . 'wpapi/v1/update-signatures' );
define ( 'SIXSCAN_BODYGUARD_6SCAN_UPDATE_APP_URL' , 	SIXSCAN_SERVER . 'wpapi/v1/update-application-code' );
define ( 'SIXSCAN_COMM_ORACLE_AUTH_DASHBOARD_URL' ,		SIXSCAN_SERVER . 'dashboard/v1?' );

define ( 'SIXSCAN_OPTIONS_SETUP_ACCOUNT', 				'sixscan_setupaccount' );
define ( 'SIXSCAN_ACCOUNT_NOT_ACTIVE',					'SETUP_STAGE_NON_ACTIVE' );
define ( 'SIXSCAN_ACCOUNT_SETUP_STAGE_INSTALLED',		'SETUP_STAGE_INSTALLED' );
define ( 'SIXSCAN_ACCOUNT_SETUP_STAGE_WORKING',			'SETUP_STAGE_RUNNING' );

define ( 'SIXSCAN_OPTION_MENU_IS_BLOG_REGISTERED' ,		'sixscan_is_blog_registered' );
define ( 'SIXSCAN_OPTION_MENU_IS_BLOG_VERIFIED' ,		'sixscan_is_blog_verified' );
define ( 'SIXSCAN_OPTION_MENU_SITE_ID' , 				'sixscan_registered_site_id' );
define ( 'SIXSCAN_OPTION_MENU_API_TOKEN' , 				'sixscan_registered_api_token' );
define ( 'SIXSCAN_OPTION_MENU_VERIFICATION_TOKEN' , 	'sixscan_registered_verification_token' );
define ( 'SIXSCAN_OPTION_MENU_DASHBOARD_TOKEN' , 		'sixscan_registered_dashboard_token' );

define ( 'SIXSCAN_UPDATE_OK_RESPONSE_CODE',				200 );
define ( 'SIXSCAN_UPDATE_LAST_VERSION_RESPONSE_CODE',	304 );
define ( 'SIXSCAN_COMM_ORACLE_AUTH_SALT' , 				':ou6s:6EF{z*_,^+8_#cNg8!+u5zp)ix' );
define ( 'SIXSCAN_SIGNATURE_SCHEDULER_SALT' ,			'Ia]g^X6d{PbvOmX}scMOM87.<.F1.~W' );
define ( 'SIXSCAN_OPTION_COMM_ORACLE_NONCE' ,			'sixscan_nonce_val' );
define ( 'SIXSCAN_OPTION_COMM_LAST_SIG_UPDATE_NONCE',	'sixscan_sig_last_update_nonce' );
define ( 'SIXSCAN_COMM_SIGNATURE_FILENAME', 			'6scan-signature.php' );
define ( 'SIXSCAN_SIGNATURE_LINKS_DELIMITER',			"\n" );
define ( 'SIXSCAN_SIGNATURE_MULTIPART_DELIMITER',		'###UZhup3v1ENMefI7Wy44QNppgZmp0cu6RPenZewotclc2ZCWUDE4zAfXIJX354turrscbFBL2pOiKpiNLYosm6Z1Qp8b3PNjgd1xqtuskjcT9MC4fZvQfx7FPUDF11oTiTrMeayQr7JHk3UuEK7fR0###' );
define ( 'SIXSCAN_SIGNATURE_SCANNER_IP_LIST',			'108.59.1.37, 108.59.2.209, 107.22.183.61' );
define ( 'SIXSCAN_SIGNATURE_DEFAULT_PLACEHOLDER_LINK',	'/just/a/random/dir/to/avoid/htaccess/mixups\.php' );

define ( 'SIXSCAN_ANALYTICS_INSTALL_CATEGORY',			'install' );
define ( 'SIXSCAN_ANALYTICS_INSTALL_INIT_ACT',			'init' );
define ( 'SIXSCAN_ANALYTICS_INSTALL_REG_ACT',			'registration' );
define ( 'SIXSCAN_ANALYTICS_INSTALL_VERIF_ACT',			'verification' );
define ( 'SIXSCAN_ANALYTICS_UNINSTALL_CATEGORY',		'uninstall' );
define ( 'SIXSCAN_ANALYTICS_DEACTIVATE_ACT',			'deactivate' );
define ( 'SIXSCAN_ANALYTICS_UNINSTALL_RM_ACT',			'remove' );
define ( 'SIXSCAN_ANALYTICS_NORMAL_CATEGORY',			'normal' );
define ( 'SIXSCAN_ANALYTICS_NORMAL_UPDATING_ACT',		'updating' );


define ( 'SIXSCAN_ANALYTICS_OK_STRING',					'ok' );
define ( 'SIXSCAN_ANALYTICS_FAIL_PREFIX_STRING',		'error_' );

define( 'SIXSCAN_HTACCESS_FILE',  						ABSPATH . '/.htaccess' );
define( 'SIXSCAN_HTACCESS_6SCAN', 						SIXSCAN_PLUGIN_DIR . '/data/.htaccess.dat' );	
define( 'SIXSCAN_SIGNATURE_SRC',						SIXSCAN_PLUGIN_DIR . '/data/' . SIXSCAN_COMM_SIGNATURE_FILENAME );
define( 'SIXSCAN_HTACCESS_6SCAN_GATE_FILE_NAME', 		'6scan-gate.php' );
define( 'SIXSCAN_HTACCESS_6SCAN_GATE_SOURCE',  			SIXSCAN_PLUGIN_DIR . '/data/' . SIXSCAN_HTACCESS_6SCAN_GATE_FILE_NAME );
define( 'SIXSCAN_HTACCESS_6SCAN_GATE_DEST', 			ABSPATH . SIXSCAN_HTACCESS_6SCAN_GATE_FILE_NAME );
define( 'SIXSCAN_SIGNATURE_DEST',						ABSPATH . SIXSCAN_COMM_SIGNATURE_FILENAME );
define( 'SIXSCAN_COMMON_DASHBOARD_URL',					'six-scan-dashboard' );

define( 'SIXSCAN_SIGNATURE_HEADER_NAME',				'x-6scan-signature' );

define( 'SIXSCAN_SIGNATURE_PUBLIC_KEY',	<<<EOD
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA1282HttE2wknm8qOX756
pQzR4uXSKCySGGx/xwAb3XHeX4AvHYB5NG7Cg6cGUIfRkD5JQImsGnm3rUPxw8AG
sEWRcEEsfZqEBB48h2rAXck0qpnroFRVGtxGD9ppiDvYBJKM0jsRnjdsBF9uryyB
jIEwViGKtHIU75AJGSWk/V3G2GVU8kd1he4lNNQTBcMIu6tQxb/HVqzVzcWhA7st
N6S5abDRWIEExuW8UTnnjMWDSt+g/tXZR4Po97rfGFio1fx4kROiq6/fcWpw7kq9
Mn+NflF/S+/biB5c+hbgGA6tpQj6Ta42ArCqIR6wJdetW2ljAbo2YCM/kmoTr7r4
kwIDAQAB
-----END PUBLIC KEY-----
EOD
);

function sixscan_common_set_site_id( $site_id ) {
	update_option( SIXSCAN_OPTION_MENU_SITE_ID , $site_id );
}

function sixscan_common_get_site_id() {
	return get_option ( SIXSCAN_OPTION_MENU_SITE_ID );
}

function sixscan_common_set_api_token( $api_token ){
	update_option( SIXSCAN_OPTION_MENU_API_TOKEN , $api_token );
}

function sixscan_common_get_api_token() {
	return get_option( SIXSCAN_OPTION_MENU_API_TOKEN );
}

function sixscan_common_set_verification_token( $verification_token ){
	update_option( SIXSCAN_OPTION_MENU_VERIFICATION_TOKEN , $verification_token );
}

function sixscan_common_get_verification_token() {
	return get_option( SIXSCAN_OPTION_MENU_VERIFICATION_TOKEN );
}

function sixscan_common_set_dashboard_token( $dashboard_token ){
	update_option( SIXSCAN_OPTION_MENU_DASHBOARD_TOKEN , $dashboard_token );
}

function sixscan_common_get_dashboard_token() {
	return get_option( SIXSCAN_OPTION_MENU_DASHBOARD_TOKEN );
}

function sixscan_common_is_oracle_registered() {
	return get_option( SIXSCAN_OPTION_MENU_IS_BLOG_REGISTERED );
}

function sixscan_common_set_oracle_registered_true() {
	update_option( SIXSCAN_OPTION_MENU_IS_BLOG_REGISTERED , TRUE );
}

function sixscan_common_is_oracle_verified() {
	return get_option( SIXSCAN_OPTION_MENU_IS_BLOG_VERIFIED );
}

function sixscan_common_set_oracle_verified_true() {
	update_option( SIXSCAN_OPTION_MENU_IS_BLOG_VERIFIED , TRUE );
}

function sixscan_common_remove_special_chars( $src_str ) {
	return preg_replace( "/[^a-zA-Z0-9.-]/" , "_" , $src_str );
}

function sixscan_common_report_analytics( $category , $action , $label ){
	/*
	This is custom request for google analytics. Based on
	http://code.google.com/apis/analytics/docs/tracking/gaTrackingTroubleshooting.html#gifParameters
	*/

	$google_analytics_url = "http://www.google-analytics.com/__utm.gif?";

	/*	analytics version */
	$utmwv = "5.2.0";

	/*	Unique value , to avoid caching */
	$utmn = mt_rand ( 1000000000 , 9999999999 );
	/* Another random number for Analytics */
	$utmhid = mt_rand( 1000000000, 9999999999 );

	$utmhn = $_SERVER[ 'SERVER_NAME' ];

	/*	Request type: */
	$utmt = "event";

	/* Event parameters described at http://code.google.com/apis/analytics/docs/tracking/gaTrackingTroubleshooting.html#pageNotAppearing  
	5(object*action*label) */
	$utme = '5(' . $category . '*' . $action . '*' . sixscan_common_remove_special_chars( $label ) . ')'; 

	/*	Charset and language */
	$utmcs = "UTF-8";
	$utmul = "en-us";

	/*	Account ID */
	$utmac = "UA-21559206-3";

	/*	Prepare the $utmcc data , which is "cookie" information */
	$utm_rand_val1 = mt_rand( 100000 , 999999 );
	$utm_rand_val2 = mt_rand( 1000000 , 9999999 );
	$now_time = time();	
	
	$utmcc ='__utma%3D' . $utm_rand_val1 . '.' . $utm_rand_val2 . '.' . $now_time . '.' . $now_time . '.' . $now_time . '.5';
	
	$utm_rand_val1 = mt_rand( 100000000 , 999999999 );
	$utm_rand_val2 = mt_rand( 1000000000 , 9999999999 );
	$utm_rand_val3 = mt_rand( 10 , 99 );

	$utmcc = $utmcc . '%3B%2B__utmz%3D' . $utm_rand_val1 . '.' . $utm_rand_val2 . '.' . $utm_rand_val3 . '.10.utmcsr%3D(direct)%7Cutmccn%3D(direct)7Cutmcmd%3D(none)%3B';
	
	$utmp = urlencode( $_SERVER[ 'REQUEST_URI' ] );

	/*	Prepare the final GET request */
	$analytics_get_request	= "utmac=$utmac&utmcc=$utmcc&utmcs=$utmcs&utme=$utme&utmhn=$utmhn&utmhid=$utmhid&utmn=$utmn&utmt=$utmt&utmul=$utmul&utmwv=$utmwv&utmp=$utmp&utmu=4~";
	$analytics_get_request = $google_analytics_url . $analytics_get_request;

	/*	Send the request to Google server */
	@$ret_data = file_get_contents( $analytics_get_request );
}
?>