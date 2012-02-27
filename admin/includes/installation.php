<?php

if ( ! defined( 'ABSPATH' ) ) 
	die( 'No direct access allowed' );	

function sixscan_installation_manager()
{
	/*	Run the install */
	$install_result = sixscan_installation_install();	
	if ( $install_result !== TRUE ){

		/*	If the install failed - print error message and deactivate the plugin */		
		print $install_result;	
		sixscan_common_report_analytics( SIXSCAN_ANALYTICS_INSTALL_CATEGORY , SIXSCAN_ANALYTICS_INSTALL_INIT_ACT , $err_description );
		
		$sixscan_plugin_name = plugin_basename( realpath( dirname( __FILE__ ) . "/../../6scan.php" ) );
		/*	deactivate myself */			
		deactivate_plugins( $sixscan_plugin_name );
	}
	else{
		/*	If the install has succeeded - forward user to the registration page */		
		$reg_page_address = get_bloginfo( "wpurl" ) . "/wp-admin/admin.php?page=" . SIXSCAN_COMMON_DASHBOARD_URL;
		
		print 'Redirecting to 6Scan registration page.<a href="' . $reg_page_address . '">Click here</a> if the redirect didn\'t work<br>';
		print( '<script type="text/javascript">
				window.location = "' . $reg_page_address . '"
				</script>' );		
	}
	
}

function sixscan_installation_install() {	

	try {		
		/*	Clear the operational flag. It will be set, if activation is successful  */
		sixscan_common_set_account_operational( FALSE );
		
		/*	Make sure we can create signature file and update the site's .htaccess file */
		if ( sixscan_common_is_writable_directory( ABSPATH ) == FALSE ){
			$err_message = "6Scan Install <b>Error</b>: Failed creating signature file at Wordpress directory " . ABSPATH . SIXSCAN_COMM_SIGNATURE_FILENAME .
			"<br/><br/>Please see <a href='http://codex.wordpress.org/Changing_File_Permissions' target='_blank'>this Wordpress article</a> for more information on how to add write permissions." .
			"<br/><br/>If you have additional questions, please visit our <a href='http://getsatisfaction.com/6scan' target='_blank'>community</a>";
			return sixscan_menu_wrap_error_msg( $err_message );
		}
		
		if ( sixscan_common_is_writable_htaccess() == FALSE ){
			$err_message = "6Scan Install <b>Error</b>: Failed writing .htaccess file " . SIXSCAN_HTACCESS_FILE . 
			"<br/><br/>Please see <a href='http://codex.wordpress.org/Changing_File_Permissions' target='_blank'>this Wordpress article</a> for more information on how to add write permissions." .
			"<br/><br/>If you have additional questions, please visit our <a href='http://getsatisfaction.com/6scan' target='_blank'>community</a>";
			return sixscan_menu_wrap_error_msg( $err_message );
		}
		
		if ( ! function_exists ( 'openssl_verify' ) ){
			$err_message = "6Scan Install <b>Error</b>: Function \"openssl_verify()\" does not exist. Please contact your system administrator to add OpenSSL support on this server. 6Scan requires
OpenSSL functions for increased security.".
		"<br/><br/>If you have additional questions, please visit our <a href='http://getsatisfaction.com/6scan' target='_blank'>community</a>";
			return sixscan_menu_wrap_error_msg( $err_message );
		}
		
		if ( ! WP_Filesystem() ){	    	    
			$err_message = "6Scan Install <b>Error</b>: Failed initializing WP_Filesystem(). This usually happens when security permissions do not allow writing to the Wordpress directory." . 
			"<br/><br/>Please see <a href='http://codex.wordpress.org/Changing_File_Permissions' target='_blank'>this Wordpress article</a> for more information on how to add write permissions." .
			"<br/><br/>If you have additional questions, please visit our <a href='http://getsatisfaction.com/6scan' target='_blank'>community</a>";
			return sixscan_menu_wrap_error_msg( $err_message );
		}
		
		
		if ( ( ini_get( "allow_url_fopen" ) == FALSE ) && ( ! function_exists( 'curl_init' ) ) ) {
			$err_message = "6Scan Install <b>Error</b>: No libcurl found <b>and</b> \"allow_url_fopen\" in your php.ini is disabled. 6Scan needs at least <b>one</b> transport layer to be enabled, in order to contact its server for automatic updates.<br>" . 
			"*Please see <a href='http://6scan.freshdesk.com/solution/articles/3257-installing-curl-extension-on-a-system' target='_blank'> this FAQ entry</a> in order to enable Curl<br>" .
			"*Please see <a href='http://6scan.freshdesk.com/solution/categories/3294/folders/6728/articles/2681-i-am-seeing-an-error-that-is-similar-to-could-not-open-handle-for-fopen-' target='_blank'>this FAQ entry</a> for instructions on how to enable the \"allow_url_fopen\" flag<br>" .
			"<br/><br/>If you have additional questions, please visit our <a href='http://getsatisfaction.com/6scan' target='_blank'>community</a>";
			return sixscan_menu_wrap_error_msg( $err_message );
		}		
		
		/*	Rewrite the htaccess and 6scan-gate file */
		$htaccess_install_result = sixscan_htaccess_install();
		if ( $htaccess_install_result !== TRUE )
			return sixscan_menu_wrap_error_msg( $htaccess_install_result );		
		
		if ( sixscan_common_is_regdata_present() == TRUE ){
			if ( sixscan_communication_oracle_reg_reactivate( sixscan_common_get_site_id() , sixscan_common_get_api_token() ) == TRUE ){
			
			/* There is no real install to go on, just reactivation */
				sixscan_common_set_account_operational( TRUE );
				sixscan_common_set_account_active( TRUE );
				return TRUE;
			}
			else{
				sixscan_common_erase_regdata();
			}
		}
		
		/*	Register process */
		$server_registration_result = sixscan_installation_register_with_server();
		
		if ( $server_registration_result !== TRUE ){
			/* If something went wrong in the registration/verification process */
			sixscan_common_erase_regdata();
			return $server_registration_result;
		}
		
		/*	Account is now active, but not yet operational ( operation is set by server, when user completes the registration */
		sixscan_common_set_account_active( TRUE );
						
		/*	Preparing options for further use */											
		update_option( SIXSCAN_OPTION_COMM_ORACLE_NONCE , 1 );
		update_option( SIXSCAN_OPTION_COMM_LAST_SIG_UPDATE_NONCE , 0 );						
		
	} catch( Exception $e ) {
		/* Exception aborts the process */
		sixscan_common_erase_regdata();
		sixscan_common_set_account_active( FALSE );
		sixscan_common_set_account_operational( FALSE );
		
		sixscan_common_report_analytics( SIXSCAN_ANALYTICS_INSTALL_CATEGORY , SIXSCAN_ANALYTICS_INSTALL_INIT_ACT , "Activation failed: " . $e );
		return $e;
	}		
		
	/*	We have completed the registration. Now we have to remind user to register */
	sixscan_installation_account_setup_required_notice();
		
	return TRUE;
}

function sixscan_installation_uninstall() {
	try {		
		/*	Notify the server, to disable account */
		sixscan_communication_oracle_reg_uninstall( sixscan_common_get_site_id() , sixscan_common_get_api_token() );
		
		/* Remove the verification file , htaccess data , and then all the options from db */
		sixscan_communication_oracle_reg_remove_verification_file();
		
		/* Remove lines from htaccess */
		sixscan_htaccess_uninstall();			

		/* Clear the database */
		delete_option( SIXSCAN_OPTIONS_SETUP_ACCOUNT );
		delete_option( SIXSCAN_OPTION_MENU_IS_BLOG_VERIFIED );			
		delete_option( SIXSCAN_OPTION_MENU_SITE_ID );
		delete_option( SIXSCAN_OPTION_MENU_API_TOKEN );
		delete_option( SIXSCAN_OPTION_MENU_VERIFICATION_TOKEN );
		delete_option( SIXSCAN_OPTION_MENU_DASHBOARD_TOKEN );
		delete_option( SIXSCAN_OPTION_MENU_IS_ACCOUNT_OPERATIONAL );
		delete_option( SIXSCAN_OPTION_COMM_ORACLE_NONCE );				
		delete_option( SIXSCAN_OPTION_COMM_LAST_SIG_UPDATE_NONCE );		
		
	} catch( Exception $e ) {
		sixscan_common_report_analytics( SIXSCAN_ANALYTICS_UNINSTALL_CATEGORY , SIXSCAN_ANALYTICS_UNINSTALL_RM_ACT , "Deactivation failed: " . $e );
		die( $e );
	}
}

function sixscan_installation_register_with_server(){

	$sixscan_register_result = sixscan_communication_oracle_reg_register( get_option( 'siteurl' ) ,
							get_option( 'admin_email' ) , SIXSCAN_PLUGIN_URL . "modules/signatures/notice.php" , $sixscan_oracle_auth_struct );			

	if ( $sixscan_register_result !== TRUE ){	
		$err_descr = "There was a problem registering your site with 6Scan: <b>$sixscan_register_result</b>.<br><br>";		
		$err_msg .= sixscan_menu_wrap_error_msg( $err_descr );
		$err_msg .= sixscan_menu_get_error_submission_form( $sixscan_register_result );
		return $err_msg;	/* Fail activation with error message and submission form */	
	}
		
	/*	Save the values from registration to database */ 
	sixscan_common_set_site_id( $sixscan_oracle_auth_struct[ 'site_id' ] );
	sixscan_common_set_api_token( $sixscan_oracle_auth_struct[ 'api_token' ] );
	sixscan_common_set_verification_token( $sixscan_oracle_auth_struct[ 'verification_token' ] );
	sixscan_common_set_dashboard_token( $sixscan_oracle_auth_struct[ 'dashboard_token' ] );		
	
	/*	Verify the site */
	$verification_result = sixscan_communication_oracle_reg_verification();
	if ( $verification_result !== TRUE ) {		
		$err_descr = "There was a problem verifying your site with 6Scan: " . $verification_result . "<br>";					
		$err_msg .= sixscan_menu_wrap_error_msg( $err_descr );
		$err_msg .= sixscan_menu_get_error_submission_form( $verification_result );		
		return $err_msg; /* Fail activation with error message and submission form */		
	}	
	
	return TRUE;
}

function sixscan_installation_account_setup_required_notice() {		
	
	/*	Show the notice "Don't forget to register" , only if we are not registered , and are not on the register page */
	if ( ( sixscan_common_is_account_operational() == FALSE ) && ( $_GET[ 'page' ] != SIXSCAN_COMMON_DASHBOARD_URL ) ){			
			echo '<div class="updated" style="text-align: center;"><p><p>6Scan: In order to enable protection, please <a href="admin.php?page=' . 
			SIXSCAN_COMMON_DASHBOARD_URL . '">create your account</a> now.</p></p></div>';		
		}
}	
	
?>