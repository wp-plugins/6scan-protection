<?php
/*
Plugin Name: 6Scan
Plugin URI: http://www.6scan.com/
Description: 6Scan protects your website against hackers destroying, stealing or defacing your website's precious and vulnerable data.
Author: 6Scan
Version: 1.0.4
Author URI: http://www.6scan.com
*/

if ( ! defined( 'ABSPATH' ) ) 
	die( 'No direct access allowed' );

/* Get the current plugin directory name and URL , while we are at the root */
define( 'SIXSCAN_PLUGIN_DIR',	trailingslashit( dirname(__FILE__) ) );	
define( 'SIXSCAN_PLUGIN_URL',	trailingslashit( plugins_url( basename (dirname (__FILE__) ) ) ) );

require_once( 'admin/includes/common.php' );	
require_once( 'admin/includes/htaccess.php' );
require_once( 'admin/includes/installation.php' );
require_once( 'admin/includes/events/activation.php' );
require_once( 'admin/includes/events/deactivation.php' );
require_once( 'admin/includes/events/uninstall.php' );
require_once( 'modules/communication/oracle-reg.php' );
require_once( 'modules/communication/oracle-auth.php' );
require_once( 'modules/signatures/update.php' );
require_once( 'admin/includes/6scan-menu.php' );
require_once( 'modules/stat/analytics.php' );

if ( is_admin() ) { 
	register_activation_hook( __FILE__ , 	'sixscan_events_activation' );
	register_deactivation_hook( __FILE__ , 	'sixscan_events_deactivation' );
	register_uninstall_hook( __FILE__ , 	'sixscan_events_uninstall' );	
	
	/*	First time we're up */
	if (  get_option(SIXSCAN_OPTIONS_SETUP_ACCOUNT) != SIXSCAN_ACCOUNT_SETUP_STAGE_WORKING ){
		update_option( SIXSCAN_OPTIONS_SETUP_ACCOUNT , SIXSCAN_ACCOUNT_SETUP_STAGE_WORKING );	
		sixscan_stat_analytics_log_action( SIXSCAN_ANALYTICS_INSTALL_CATEGORY , SIXSCAN_ANALYTICS_INSTALL_INIT_ACT , SIXSCAN_ANALYTICS_OK_STRING );
		
		/*	If we are activated, and already verified (This could happen, if user has registered, deactivated and then reactivated the plugin), we have to 
			update the signatures */
		if ( sixscan_common_is_oracle_verified() == TRUE ){
			sixscan_signatures_update_request_total( sixscan_common_get_site_id() , sixscan_common_get_api_token() );
		}
	}
		
	add_action( 'admin_menu' , 'sixscan_menu_install' );
	
	/*	This action checks whether the plugin has registered, and if not - shows the "don't forget to register" notice to the user */
	add_action( 'admin_notices' , 'sixscan_installation_account_setup_required_notice' );
}
?>