<?php

if ( ! defined( 'ABSPATH' ) ) 
	die( 'No direct access allowed' );	

function sixscan_installation_install() {		
	try {		
		/*	Make sure we can create signature file and update the site's .htaccess file */
		if ( sixscan_common_is_writable_directory( ABSPATH ) == FALSE ){
			$err_message = "Install: Failed creating signature file at Wordpress directory " . ABSPATH . SIXSCAN_COMM_SIGNATURE_FILENAME .
			"<br/><br/>Please see <a href='http://codex.wordpress.org/Changing_File_Permissions' target='_blank'>this Wordpress article</a> for more information on how to add write permissions." .
			"<br/><br/>If you have additional questions, please visit our <a href='http://getsatisfaction.com/6scan' target='_blank'>community</a>";
			sixscan_installation_fail_install( $err_message );	
		}
		
		if ( sixscan_common_is_writable_htaccess() == FALSE ){
			$err_message = "6Scan Install: Failed writing .htaccess file " . SIXSCAN_HTACCESS_FILE . 
			"<br/><br/>Please see <a href='http://codex.wordpress.org/Changing_File_Permissions' target='_blank'>this Wordpress article</a> for more information on how to add write permissions." .
			"<br/><br/>If you have additional questions, please visit our <a href='http://getsatisfaction.com/6scan' target='_blank'>community</a>";
			sixscan_installation_fail_install( $err_message );
		}
		
		if ( ! function_exists ( 'openssl_verify' ) ){
			$err_message = "6Scan Install: Function \"openssl_verify()\" does not exist. Please contact your system administrator to add OpenSSL support on this server. 6Scan requires
OpenSSL functions for increased security.".
		"<br/><br/>If you have additional questions, please visit our <a href='http://getsatisfaction.com/6scan' target='_blank'>community</a>";
			sixscan_installation_fail_install( $err_message );
		}
		
		if ( ! WP_Filesystem() ){	    	    
			$err_message = "6Scan Install: Failed initializing WP_Filesystem(). This usually happens when security permissions do not allow writing to the Wordpress directory." . 
			"<br/><br/>Please see <a href='http://codex.wordpress.org/Changing_File_Permissions' target='_blank'>this Wordpress article</a> for more information on how to add write permissions." .
			"<br/><br/>If you have additional questions, please visit our <a href='http://getsatisfaction.com/6scan' target='_blank'>community</a>";
			sixscan_installation_fail_install( $err_message );
		}
				
		if ( ini_get( "allow_url_fopen" ) == FALSE ){
			$err_message = "6Scan Install: \"allow_url_fopen\" in your php.ini is disabled. 6Scan needs this option to be enabled, in order to contact its server for automatic updates." . 
			"Please see <a href='http://6scan.freshdesk.com/solution/categories/3294/folders/6728/articles/2681-i-am-seeing-an-error-that-is-similar-to-could-not-open-handle-for-fopen-' target='_blank'>this FAQ entry</a> for instructions on how to fix this." .
			"<br/><br/>If you have additional questions, please visit our <a href='http://getsatisfaction.com/6scan' target='_blank'>community</a>";
			sixscan_installation_fail_install( $err_message );
		}
		else{
			$fopen_status = sixscan_common_is_fopen_working();
			if ( $fopen_status !== TRUE ){
				$err_message = "6Scan Install: failed opening connection to its server. fopen() failed with message: $fopen_status." . 
				"<br/><br/>If you have additional questions, please visit our <a href='http://getsatisfaction.com/6scan' target='_blank'>community</a>";
				sixscan_installation_fail_install( $err_message );
			}
		}
							
		/*	Create the DB fields , unless already exists ( If user has reactivated the plugin , after deactivation ) */
		if ( ! sixscan_installation_is_installed() ){
							
			/*	Preparing options for further use */											
			add_option( SIXSCAN_OPTIONS_SETUP_ACCOUNT, SIXSCAN_ACCOUNT_SETUP_STAGE_INSTALLED );
			add_option( SIXSCAN_OPTION_MENU_IS_BLOG_REGISTERED , FALSE );
			add_option( SIXSCAN_OPTION_MENU_IS_BLOG_VERIFIED , FALSE );				
			add_option( SIXSCAN_OPTION_MENU_SITE_ID , '' );
			add_option( SIXSCAN_OPTION_MENU_API_TOKEN , '' );
			add_option( SIXSCAN_OPTION_MENU_VERIFICATION_TOKEN , '' );
			add_option( SIXSCAN_OPTION_MENU_DASHBOARD_TOKEN , '' );	
			add_option( SIXSCAN_OPTION_COMM_ORACLE_NONCE , 1 );
			add_option( SIXSCAN_OPTION_COMM_LAST_SIG_UPDATE_NONCE , 0 );
			add_option( SIXSCAN_OPTION_LAST_ERROR_OCCURED , 0 );
		}
		
		/*	Rewrite the htaccess and 6scan-gate file. */
		sixscan_htaccess_install();
	
	} catch( Exception $e ) {
		sixscan_common_report_analytics( SIXSCAN_ANALYTICS_INSTALL_CATEGORY , SIXSCAN_ANALYTICS_INSTALL_INIT_ACT , "Activation failed: " . $e );
		die( $e );
	}		
}

function sixscan_installation_uninstall() {
	try {
		if ( sixscan_installation_is_installed() ) {
			/* Remove the verification file , htaccess data , and then all the options from db */
			sixscan_communication_oracle_reg_remove_verification_file();
			sixscan_htaccess_uninstall();			
											
			delete_option( SIXSCAN_OPTIONS_SETUP_ACCOUNT );
			delete_option( SIXSCAN_OPTION_MENU_IS_BLOG_VERIFIED );
			delete_option( SIXSCAN_OPTION_MENU_IS_BLOG_REGISTERED );
			delete_option( SIXSCAN_OPTION_MENU_SITE_ID );
			delete_option( SIXSCAN_OPTION_MENU_API_TOKEN );
			delete_option( SIXSCAN_OPTION_MENU_VERIFICATION_TOKEN );
			delete_option( SIXSCAN_OPTION_MENU_DASHBOARD_TOKEN );
			delete_option( SIXSCAN_OPTION_COMM_ORACLE_NONCE );				
			delete_option( SIXSCAN_OPTION_COMM_LAST_SIG_UPDATE_NONCE );
			delete_option( SIXSCAN_OPTION_LAST_ERROR_OCCURED );
		}			
	} catch( Exception $e ) {
		sixscan_common_report_analytics( SIXSCAN_ANALYTICS_UNINSTALL_CATEGORY , SIXSCAN_ANALYTICS_UNINSTALL_RM_ACT , "Deactivation failed: " . $e );
		die( $e );
	}
}

function sixscan_installation_fail_install( $err_description ){
	sixscan_common_report_analytics( SIXSCAN_ANALYTICS_INSTALL_CATEGORY , SIXSCAN_ANALYTICS_INSTALL_INIT_ACT , $err_description );
	die( $err_description );
}


function sixscan_installation_is_installed() {
	if ( get_option( SIXSCAN_OPTIONS_SETUP_ACCOUNT ) == FALSE )
		return FALSE;			
	return TRUE;
}

function sixscan_installation_account_setup_required() {
	if ( get_option( SIXSCAN_OPTION_MENU_IS_BLOG_REGISTERED  ) == TRUE )
		return TRUE;			
	return FALSE;
}

function sixscan_installation_account_setup_required_notice() {		
	
	/*	Show the notice "Don't forget to register" , only if we are not registered , and are not on the register page */
	if ( ( sixscan_common_is_oracle_verified() == FALSE ) && ( $_GET[ 'page' ] != SIXSCAN_COMMON_DASHBOARD_URL ) ){			
			echo '<div class="updated" style="text-align: center;"><p><p>6Scan: In order to enable protection, please <a href="admin.php?page=' . 
			SIXSCAN_COMMON_DASHBOARD_URL . '&account_setup=1">create your account</a> now.</p></p></div>';		
		}
}	
	
?>