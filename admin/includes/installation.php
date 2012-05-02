<?php

if ( ! defined( 'ABSPATH' ) ) 
	die( 'No direct access allowed' );	

function sixscan_installation_manager()
{
	/* If running from partner install, the logic is a bit different */
	if ( ( sixscan_common_is_partner_version() ) && ( sixscan_installation_partner_is_to_install() === FALSE ) )
		return;
	
	/* Run the install */
	$install_result = sixscan_installation_install();
	if ( $install_result !== TRUE ){

		/*	If the install failed - print error message and deactivate the plugin */				
		if ( sixscan_common_is_partner_version() === FALSE ){
			print $install_result;		
			
			$sixscan_plugin_name = plugin_basename( realpath( dirname( __FILE__ ) . "/../../6scan.php" ) );
			
			/*	This dirty patch is required because some hostings (free?) have a short sql timeout. When it timeouts, 6Scan can't
			disable itelf, and user gets stuck in infitie deactivate loop. 
			We can't enlarge the timeout, since it requires sql root access. We can only reconnect to the SQL.
			This rather dirty hack reconnects to SQL and deactivates the plugin */
			if ( mysql_errno() != 0 ){
				global $wpdb;
				$wpdb = new wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
				wp_set_wpdb_vars();
			}

			/*	deactivate myself */			
			deactivate_plugins( $sixscan_plugin_name );
		}
		else if ( sixscan_installation_partner_run_first_time() === FALSE  ){
			/* If we are in partner version, but not running for the first time - we can show the error */
			print $install_result;
		}
		
	}
	else{		
		/*	No redirects in partner version */
		if ( sixscan_common_is_partner_version() === FALSE ){
		
			/*	If the install has succeeded - forward user to the registration page */		
			$reg_page_address = get_bloginfo( "wpurl" ) . "/wp-admin/admin.php?page=" . SIXSCAN_COMMON_DASHBOARD_URL;
			
			/* If user's JavaScript is disabled, he will see this notice to upgrade */
			sixscan_installation_account_setup_required_notice();
			/*	Forward user to the registration screen */
			print <<<EOT
				<script type="text/javascript">
					document.getElementById('6scan_dashboard_redirect_caption').style.display = 'none';
					window.location = "$reg_page_address";
				</script>
EOT;
		}
	}
	
	/*	Zeroize our databse flag, so that we only try installing one time */
	if ( sixscan_common_is_partner_version() )
		sixscan_installation_partner_mark_install_tried();
}

function sixscan_installation_partner_is_to_install(){
	
	/*	We arrive to this function when 6Scan is not yet installed.
		Now we have to decide, whether to run install.
		First case to run install is when we are at 6scan dashboard page (one of them) - that means 6Scan is not installed, but user
		has requested to see his dashboard */
	$current_page = $_GET[ 'page' ];	

	if ( ( $current_page == SIXSCAN_COMMON_DASHBOARD_URL ) && ( sixscan_menu_is_ticket_requested() == FALSE ) ){		
		/*	Return TRUE to install means :
		1) We are not installed
		2) We are not in ticket support
		3) We have just requested 6Scan dashboard.
		*/
		return TRUE;
	}
	
	/*	Second option - 6Scan is not yet installed, but we have arrived to admin panel for the first time - try registering */
	if ( sixscan_installation_partner_run_first_time() )
		return TRUE;
		
	return FALSE;		
}

function sixscan_installation_partner_run_first_time(){
	/* For the first time the install key is not set */
	return get_option( SIXSCAN_PARTNER_INSTALL_KEY ) == "" ;
}

function sixscan_installation_partner_mark_install_tried(){
	update_option( SIXSCAN_PARTNER_INSTALL_KEY , "softacolous_sixscan" );
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
		
		return $e;
	}		
		

		
	return TRUE;
}

function sixscan_installation_uninstall() {
	try {		
		/*	Notify the server, to disable account */
		sixscan_communication_oracle_reg_uninstall( sixscan_common_get_site_id() , sixscan_common_get_api_token() );
		
		/*	Remove verification file, if exists */			
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
		delete_option( SIXSCAN_OPTION_VULNERABITILY_COUNT );
		delete_option( SIXSCAN_OPTION_WAF_REQUESTED );

	} catch( Exception $e ) {		
		die( $e );
	}
}

function sixscan_installation_partner_info_get( & $partner_id , & $partner_key ){
	$partner_file_path = trailingslashit( dirname( __FILE__ ) ) . SIXSCAN_PARTNER_INFO_FILENAME;
	
	$partner_id = "";
	$partner_key = "";
	
	if ( file_exists( $partner_file_path ) ){
		require_once( $partner_file_path );	
		
		$partner_id = isset( $sixscan_partner_id ) ? $sixscan_partner_id : "";
		$partner_key = isset( $sixscan_partner_key ) ? $sixscan_partner_key : "";
	}	
}

function sixscan_installation_register_with_server(){
		
	/*	If there is partner file, partner_id and partner_key are filled */
	sixscan_installation_partner_info_get( $partner_id , $partner_key );

	$sixscan_register_result = sixscan_communication_oracle_reg_register( get_option( 'siteurl' ) ,
							get_option( 'admin_email' ) , SIXSCAN_PLUGIN_URL . "modules/signatures/notice.php" , 
							$sixscan_oracle_auth_struct , $partner_id , $partner_key );			

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

		/*	If verification failed, try running older verification method */
		sixscan_communication_oracle_reg_create_verification_file();
		$verification_result = sixscan_communication_oracle_reg_verification( TRUE );		
		
		if ( $verification_result !== TRUE ) {
			/*	Try to diagnose what caused the verification failure */
			sixscan_installation_verification_fail_reason();
			
			sixscan_communication_oracle_reg_remove_verification_file();
			$err_descr = "There was a problem verifying your site with 6Scan: <br>";					
			$err_msg .= sixscan_menu_wrap_error_msg( $err_descr );
			$err_msg .= sixscan_menu_get_error_submission_form( $verification_result );		
			return $err_msg; /* Fail activation with error message and submission form */		
		}	
	}
	
	return TRUE;
}

function sixscan_installation_verification_get_page_result( $page_url ){

	$response = sixscan_common_request_network( $page_url , "" , "GET" );
	return wp_remote_retrieve_response_code( $response );
}

function sixscan_installation_verification_fail_reason(){

	/* Try to find what stage caused 500 error */
	$failed_verification = array();
	$image_url = home_url() . "/" . SIXSCAN_VERIFICATION_FILE_PREFIX . sixscan_common_get_verification_token() . ".gif";

	/*	Try accessing blog index */
	$failed_verification[ 'home_url_htaccess_modification' ] = sixscan_installation_verification_get_page_result( home_url() );

	/*	Try accessing verification image */	
	$failed_verification[ 'image_pic_htaccess_modification' ] = sixscan_installation_verification_get_page_result( $image_url );

	sixscan_htaccess_uninstall();		

	/*	Try accessing verification image */	
	$failed_verification[ 'image_pic_no_htaccess_modification' ] = sixscan_installation_verification_get_page_result( $image_url );

	/*	Try different haccess variations */
	sixscan_htaccess_install('1');

	$failed_verification[ 'image_pic_htaccess_1_modification' ] = sixscan_installation_verification_get_page_result( $image_url );

	sixscan_htaccess_uninstall();
	sixscan_htaccess_install('2');
	
	$failed_verification[ 'image_pic_htaccess_2_modification' ] = sixscan_installation_verification_get_page_result( $image_url );

	sixscan_htaccess_uninstall();
	sixscan_htaccess_install('3');

	$failed_verification[ 'image_pic_htaccess_3_modification' ] = sixscan_installation_verification_get_page_result( $image_url );
	sixscan_htaccess_uninstall();
	
	/*	Prepare error log */
	$fail_verification_diag = urlencode( base64_encode( print_r( $failed_verification , TRUE) ) );
	$failure_data = "root_url=" . home_url() . "&wordpress_version=" . get_bloginfo('version') . "&6scan_version=" . SIXSCAN_VERSION .  "&error_details=$fail_verification_diag&admin_email=&admin_comments=";
	sixscan_common_request_network( SIXSCAN_BODYGUARD_INTERNAL_ERROR_URL , $failure_data , "POST" );	
}

function sixscan_installation_account_setup_required_notice() {		
	
	/*	Show the notice "Don't forget to register" , only if we are not registered , we are not on the register page 
		and this is not a partner installed version*/
	if ( ( sixscan_common_is_account_operational() == FALSE ) && ( $_GET[ 'page' ] != SIXSCAN_COMMON_DASHBOARD_URL )
		&& ( sixscan_common_is_partner_version() == FALSE ) ){			
			echo '<div id="6scan_dashboard_redirect_caption" class="updated" style="text-align: center;"><p><p>6Scan: In order to enable protection, please <a href="admin.php?page=' . 
			SIXSCAN_COMMON_DASHBOARD_URL . '">create your account</a> now.</p></p></div>';
		}
}	
	
?>