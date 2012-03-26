<?php

if ( ! defined( 'ABSPATH' ) ) 
	die( 'No direct access allowed' );

/*	Register new user/site */
function sixscan_communication_oracle_reg_register( $site_url , $user_email , $notice_script_url , & $sixscan_oracle_auth_struct , $partner_id , $partner_key ){
		
	$expected = array( "site_id" , "api_token" , "dashboard_token" , "verification_token" );
	
	try{
		/*	The notice script will be relative to the blog's URL */
		$relative_notice_url = substr( $notice_script_url , strlen( $site_url ) + 1 );
		
		/*	Sending registration data to server, using GET */
		$request_register_url = SIXSCAN_BODYGUARD_REGISTER_URL ."?platform=wordpress&platform_version=" . get_bloginfo( 'version' ) . "&platform_locale=" . get_locale() . "&url=$site_url&email=$user_email&notice_script_url=$relative_notice_url";
		
		/*	If partner ID and Key exists, add it to registration request. */		
		if ( ( $partner_id != "" ) && ( $partner_key != "" ) ){
			$request_register_url .= "&partner_id=$partner_id&partner_key=$partner_key";
		}
		
		$response = sixscan_common_request_network( $request_register_url , "" , "GET" );

		$raw_register_data =  wp_remote_retrieve_body( $response ) ;	
		
		if ( is_wp_error( $response ) ) {
			$error_string = $response->get_error_message();
			sixscan_stat_analytics_log_action( SIXSCAN_ANALYTICS_INSTALL_CATEGORY , SIXSCAN_ANALYTICS_INSTALL_REG_ACT , SIXSCAN_ANALYTICS_FAIL_PREFIX_STRING . "wp_remote_get " . $error_string );		
			
			$error_string = str_replace( $request_register_url , SIXSCAN_BODYGUARD_REGISTER_URL , $error_string );
			return $error_string;			
		}
		else if ( 200 != wp_remote_retrieve_response_code( $response ) ) {
			$error_string = "wp_remote_get returned httpd status " . wp_remote_retrieve_response_code( $response ) . ", data:" . urldecode( $raw_register_data );
			sixscan_stat_analytics_log_action( SIXSCAN_ANALYTICS_INSTALL_CATEGORY , SIXSCAN_ANALYTICS_INSTALL_REG_ACT , SIXSCAN_ANALYTICS_FAIL_PREFIX_STRING . $error_string );	
			return $error_string;
		}
		
		$registration_answer = explode( "&" , $raw_register_data );
		$request_error_log = "";
		
		/*	Register site_id , api_token , dashboard_token , verification_token */
		foreach ( $registration_answer as $onekey ) {
			list ( $key , $val) = explode( "=" , $onekey );
			$request_error_log = $request_error_log . "$key=___&";	 /* Because this error is logged , we do not want to send data over the net. Replace the real keys with '___' chars */
			
			$arr_location = array_search( $key , $expected );
			
			/*	If there was some mistake in the way, and we have received a key , which is not in our array. */
			if ( $arr_location === FALSE ){	
				sixscan_stat_analytics_log_action( SIXSCAN_ANALYTICS_INSTALL_CATEGORY , SIXSCAN_ANALYTICS_INSTALL_REG_ACT , SIXSCAN_ANALYTICS_FAIL_PREFIX_STRING . "oracle_response_failed_unknown_parameter" . $request_error_log );
				return "Bad value received from 6Scan server.";		
			}
							
			$sixscan_oracle_auth_struct[ $key ] = trim( $val );		
			
			/*	The key was handled , and we can remove it from the array */
			unset( $expected [ $arr_location ] );	
		}
		
		/*	If we have not updated all the required values there was some error during registration */
		if ( ! empty( $expected ) )
		{
			sixscan_stat_analytics_log_action( SIXSCAN_ANALYTICS_INSTALL_CATEGORY , SIXSCAN_ANALYTICS_INSTALL_REG_ACT , SIXSCAN_ANALYTICS_FAIL_PREFIX_STRING . "oracle_response_failed_" . $request_error_log );
			return "Bad value received from 6Scan server.";		
		}
				
		sixscan_stat_analytics_log_action( SIXSCAN_ANALYTICS_INSTALL_CATEGORY , SIXSCAN_ANALYTICS_INSTALL_REG_ACT , SIXSCAN_ANALYTICS_OK_STRING );
		/*	Return the data from registration server */
		return TRUE;
	}
	catch( Exception $e ) {
		sixscan_stat_analytics_log_action( SIXSCAN_ANALYTICS_INSTALL_CATEGORY , SIXSCAN_ANALYTICS_INSTALL_REG_ACT , SIXSCAN_ANALYTICS_FAIL_PREFIX_STRING . "sixscan_communication_oracle_reg_register_" . $e );
		die( $e );
	}
}

/* Prove to server , that we are indeed here */
function sixscan_communication_oracle_reg_verification(){

	try{
		if ( ( sixscan_common_get_verification_token() == FALSE ) || ( sixscan_common_get_site_id() == FALSE ) || ( sixscan_common_get_api_token() == FALSE ) )
			return "6Scan was not registered properly.Data from DB is missing";
						
		$verif_file = sixscan_communication_oracle_reg_create_verification_file();
		if ($verif_file != TRUE)
			return $verif_file;
					
		$request_verification_url = SIXSCAN_BODYGUARD_VERIFY_URL . "?site_id=" . sixscan_common_get_site_id() . "&api_token=" . sixscan_common_get_api_token();
		$response = sixscan_common_request_network( $request_verification_url , "" , "GET" );
					
		/*	We do not remove the verification url, since the server wants to check the site's ownership once in a while */		
		
		if ( is_wp_error( $response ) ) {
			sixscan_communication_oracle_reg_remove_verification_file();
			$error_string = $response->get_error_message();
			sixscan_stat_analytics_log_action( SIXSCAN_ANALYTICS_INSTALL_CATEGORY , SIXSCAN_ANALYTICS_INSTALL_REG_ACT , SIXSCAN_ANALYTICS_FAIL_PREFIX_STRING . "_verification_process_" . $error_string );
			
			/*	Make the error message simplier for user */
			$error_string = str_replace( $request_verification_url , SIXSCAN_BODYGUARD_VERIFY_URL , $error_string );
			return $error_string;
		}
		else if ( 200 != wp_remote_retrieve_response_code( $response ) ) {
			sixscan_communication_oracle_reg_remove_verification_file();
			$server_response = "";
			parse_str( wp_remote_retrieve_body( $response ), $server_response );
			$error_string = "<br><br>" . $server_response['reason'];			
			sixscan_stat_analytics_log_action( SIXSCAN_ANALYTICS_INSTALL_CATEGORY , SIXSCAN_ANALYTICS_INSTALL_REG_ACT , SIXSCAN_ANALYTICS_FAIL_PREFIX_STRING . "_verification_process_server_response_" . $error_string );
			return $error_string;
		}
		
		sixscan_stat_analytics_log_action( SIXSCAN_ANALYTICS_INSTALL_CATEGORY , SIXSCAN_ANALYTICS_INSTALL_VERIF_ACT , SIXSCAN_ANALYTICS_OK_STRING );
		return TRUE;
	}
	catch( Exception $e ) {
		sixscan_stat_analytics_log_action( SIXSCAN_ANALYTICS_INSTALL_CATEGORY , SIXSCAN_ANALYTICS_INSTALL_REG_ACT , SIXSCAN_ANALYTICS_FAIL_PREFIX_STRING . "sixscan_communication_oracle_reg_verification" . $e );
		die( $e );
	}
}

function sixscan_communication_oracle_reg_create_verification_file(){
	
	/*	Create verification url */														
	$verification_file_name = ABSPATH . "/" . SIXSCAN_VERIFICATION_FILE_PREFIX . sixscan_common_get_verification_token() . ".gif";	
		
	$file_handle = fopen( $verification_file_name , "w" ); 	
	
	if ( $file_handle == FALSE){
		$error_desc = error_get_last();
		sixscan_stat_analytics_log_action( SIXSCAN_ANALYTICS_INSTALL_CATEGORY , SIXSCAN_ANALYTICS_INSTALL_VERIF_ACT , SIXSCAN_ANALYTICS_FAIL_PREFIX_STRING . "_verification_file_creation_" . $error_desc[ 'message' ] . '_' . $error_desc[ 'type' ]);
		return "Failed creating file " . $verification_file_name . " for verification purposes. Reason:" . $error_desc[ 'message' ] . ' Type:' . $error_desc[ 'type' ];	
	}
	
	$verificiation_data = SIXSCAN_VERIFICATION_DELIMITER . sixscan_common_get_site_id() . SIXSCAN_VERIFICATION_DELIMITER;
	fwrite( $file_handle , $verificiation_data  );
	fclose( $file_handle );
	
	return TRUE;
}

function sixscan_communication_oracle_reg_remove_verification_file(){
	$verification_file_name = ABSPATH . "/" . SIXSCAN_VERIFICATION_FILE_PREFIX . sixscan_common_get_verification_token() . ".gif";	
	
	@unlink( $verification_file_name );
}

function sixscan_communication_oracle_reg_reactivate( $site_id , $api_token ){

	$request_reactivate_url = SIXSCAN_BODYGUARD_REACTIVATE_URL . "?site_id=$site_id&api_token=$api_token";	
	
	$response = sixscan_common_request_network( $request_reactivate_url , "" , "GET" );
		
	if ( 200 != wp_remote_retrieve_response_code( $response ) ) {
		return FALSE;
	}
	
	return TRUE;		
}

function sixscan_communication_oracle_reg_deactivate( $site_id , $api_token ){
	
	$request_deactivation_url = SIXSCAN_BODYGUARD_DEACTIVATE_ACCOUNT . "?site_id=$site_id&api_token=$api_token";	
	$response = sixscan_common_request_network( $request_deactivation_url , "" , "GET" );
		
	return TRUE;
}

function sixscan_communication_oracle_reg_uninstall( $site_id , $api_token ){
	
	$request_uninstall_url = SIXSCAN_BODYGUARD_UNINSTALL_ACCOUNT . "?site_id=$site_id&api_token=$api_token";	
	$response = sixscan_common_request_network( $request_uninstall_url , "" , "GET" );
		
	return TRUE;
}

?>