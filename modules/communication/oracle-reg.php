<?php

if ( ! defined( 'ABSPATH' ) ) 
	die( 'No direct access allowed' );

/*	Register new user/site */
function sixscan_communication_oracle_reg_register( $site_url , $user_email , $notice_script_url , $registration_form_data ){	
		
	$expected = array( "site_id" , "api_token" , "dashboard_token" , "verification_token" );
	
	try{
		/*	The notice script will be relative to the blog's URL */
		$relative_notice_url = substr( $notice_script_url , strlen( $site_url ) + 1 );
		
		/*	Sending registration data to server, using GET */
		 $response = wp_remote_get( SIXSCAN_BODYGUARD_REGISTER_URL ."?url=$site_url&email=$user_email&notice_script_url=$relative_notice_url&registration_form_data=$registration_form_data" , array(		
			'timeout' => 30,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'headers' => array(),
			'cookies' => array()
			)
		);
		
		if ( is_wp_error( $response ) ) {
			$error_string = $response->get_error_message();
			sixscan_stat_analytics_log_action( SIXSCAN_ANALYTICS_INSTALL_CATEGORY , SIXSCAN_ANALYTICS_INSTALL_REG_ACT , SIXSCAN_ANALYTICS_FAIL_PREFIX_STRING . "wp_remote_post " . $error_string );		
			return FALSE;
		}
		else if ( 200 != wp_remote_retrieve_response_code( $response ) ) {
			sixscan_stat_analytics_log_action( SIXSCAN_ANALYTICS_INSTALL_CATEGORY , SIXSCAN_ANALYTICS_INSTALL_REG_ACT , SIXSCAN_ANALYTICS_FAIL_PREFIX_STRING . "wp_remote_post returned httpd status" . 
																wp_remote_retrieve_response_code( $response ) );	
			return FALSE;
		}

		$raw_register_data =  wp_remote_retrieve_body( $response ) ;	
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
				return FALSE;		
			}
							
			$sixscan_oracle_auth_struct[ $key ] = trim( $val );		
			
			/*	The key was handled , and we can remove it from the array */
			unset( $expected [ $arr_location ] );	
		}
		
		/*	If we have not updated all the required values there was some error during registration */
		if ( ! empty( $expected ) )
		{
			sixscan_stat_analytics_log_action( SIXSCAN_ANALYTICS_INSTALL_CATEGORY , SIXSCAN_ANALYTICS_INSTALL_REG_ACT , SIXSCAN_ANALYTICS_FAIL_PREFIX_STRING . "oracle_response_failed_" . $request_error_log );
			return FALSE;
		}
		
		sixscan_stat_analytics_log_action( SIXSCAN_ANALYTICS_INSTALL_CATEGORY , SIXSCAN_ANALYTICS_INSTALL_REG_ACT , SIXSCAN_ANALYTICS_OK_STRING );
		/*	Return the data from registration server */
		return $sixscan_oracle_auth_struct;
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
			return FALSE;
						
		/*	Create temporary verification url */														
		$verification_file_name = ABSPATH . "/" . sixscan_common_get_verification_token() . ".php";				
		
		$file_handle = fopen( $verification_file_name , "w" ); 	
		if ( $file_handle == FALSE)
		{
			$error_desc = error_get_last();
			sixscan_stat_analytics_log_action( SIXSCAN_ANALYTICS_INSTALL_CATEGORY , SIXSCAN_ANALYTICS_INSTALL_VERIF_ACT , SIXSCAN_ANALYTICS_FAIL_PREFIX_STRING . "_verification_file_creation_" . $error_desc[ 'message' ] . '_' . $error_desc[ 'type' ]);
			return FALSE;	
		}
		
		$verificiation_data = '<?php print " ' . sixscan_common_get_site_id() . '"; ?>';
		fwrite( $file_handle , $verificiation_data  );
		fclose( $file_handle );
				
		$response = wp_remote_get( SIXSCAN_BODYGUARD_VERIFY_URL . "?site_id=" . sixscan_common_get_site_id() . "&api_token=" . sixscan_common_get_api_token(),  array(
			'timeout' => 30,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'headers' => array(),		
			'cookies' => array()
			)); 
			
		/*	Remove verification url */
		unlink( $verification_file_name );
		
		if ( is_wp_error( $response ) ) {
			$error_string = $response->get_error_message();
			sixscan_stat_analytics_log_action( SIXSCAN_ANALYTICS_INSTALL_CATEGORY , SIXSCAN_ANALYTICS_INSTALL_REG_ACT , SIXSCAN_ANALYTICS_FAIL_PREFIX_STRING . "_verification_process_" . $error_string );
			return FALSE;
		}
		else if (200 != wp_remote_retrieve_response_code( $response ) ) {
			sixscan_stat_analytics_log_action( SIXSCAN_ANALYTICS_INSTALL_CATEGORY , SIXSCAN_ANALYTICS_INSTALL_REG_ACT , SIXSCAN_ANALYTICS_FAIL_PREFIX_STRING . "_verification_process_server_response_" . wp_remote_retrieve_response_code( $response ) );
			return FALSE;
		}
		
		sixscan_stat_analytics_log_action( SIXSCAN_ANALYTICS_INSTALL_CATEGORY , SIXSCAN_ANALYTICS_INSTALL_VERIF_ACT , SIXSCAN_ANALYTICS_OK_STRING );
		return TRUE;
	}
	catch( Exception $e ) {
		sixscan_stat_analytics_log_action( SIXSCAN_ANALYTICS_INSTALL_CATEGORY , SIXSCAN_ANALYTICS_INSTALL_REG_ACT , SIXSCAN_ANALYTICS_FAIL_PREFIX_STRING . "sixscan_communication_oracle_reg_verification" . $e );
		die( $e );
	}
}

?>