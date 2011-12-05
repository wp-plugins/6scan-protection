<?php

if ( ! defined( 'ABSPATH' ) ) 
	die( 'No direct access allowed' );	

function sixscan_installation_install() {		
	try {
		
		/*	Make sure we can create signature file and update the site's .htaccess file */
		if ( sixscan_installation_is_writable_directory( ABSPATH ) == FALSE ){
			$err_message = "Can't create signature file " . ABSPATH . SIXSCAN_COMM_SIGNATURE_FILENAME;
			sixscan_common_report_analytics( SIXSCAN_ANALYTICS_INSTALL_CATEGORY , SIXSCAN_ANALYTICS_INSTALL_INIT_ACT , $err_message );
			die( $err_message );
		}
		
		if ( sixscan_installation_is_writable_htaccess() == FALSE ){
			$err_message = "Can't update .htaccess file at " . SIXSCAN_HTACCESS_FILE;
			sixscan_common_report_analytics( SIXSCAN_ANALYTICS_INSTALL_CATEGORY , SIXSCAN_ANALYTICS_INSTALL_INIT_ACT , $err_message );
			die( $err_message );
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
		}			
	} catch( Exception $e ) {
		sixscan_common_report_analytics( SIXSCAN_ANALYTICS_UNINSTALL_CATEGORY , SIXSCAN_ANALYTICS_UNINSTALL_RM_ACT , "Deactivation failed: " . $e );
		die( $e );
	}
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
	
function sixscan_installation_is_writable_directory( $dir_to_check ){	
	$test_fname = $dir_to_check . SIXSCAN_COMM_SIGNATURE_FILENAME;
	
	/*	We can't rely on is_writable() , since safe mode limitations are not taken into account. Lets try by ourselves: */
	$fh = @fopen( $test_fname , "a+" );
	if ($fh === FALSE)
		return FALSE;
	
	/*	Cleanup */
	fclose( $fh );
	unlink( $test_fname );
	return TRUE;	
}

function sixscan_installation_is_writable_htaccess(){
	/*	We can't rely on is_writable() , since safe mode limitations are not taken into account. Lets try by ourselves: */		
	$fh = @fopen( SIXSCAN_HTACCESS_FILE , "a+" );
	if ($fh == FALSE)
		return FALSE;
	
	fclose( $fh );
	
	/*	Even if there weren't an htaccess present, and we have just created it , there is no reason to unlink() it, since we will generate new one, anyways */	
	return TRUE;
}	
?>