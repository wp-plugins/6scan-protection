<?php
/*
Plugin Name: 6Scan
Plugin URI: http://www.6scan.com/
Description: 6Scan protects your website against hackers destroying, stealing or defacing your website's precious and vulnerable data.
Author: 6Scan
Version: 1.0.6
Author URI: http://www.6scan.com
*/

if ( ! defined( 'ABSPATH' ) ) 
	die( 'No direct access allowed' );

/* Get the current plugin directory name and URL , while we are at the root */
define( 'SIXSCAN_PLUGIN_DIR',	trailingslashit( dirname(__FILE__) ) );	
define( 'SIXSCAN_PLUGIN_URL',	trailingslashit( plugins_url( basename (dirname (__FILE__) ) ) ) );

require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
require_once( 'admin/includes/common.php' );	
require_once( 'admin/includes/htaccess.php' );
require_once( 'admin/includes/installation.php' );
require_once( 'admin/includes/events/deactivation.php' );
require_once( 'admin/includes/events/uninstall.php' );
require_once( 'modules/communication/oracle-reg.php' );
require_once( 'modules/communication/oracle-auth.php' );
require_once( 'modules/signatures/update.php' );
require_once( 'admin/includes/6scan-menu.php' );
require_once( 'modules/stat/analytics.php' );

if ( is_admin() ) { 
	/*	We do not use the usual activation hook, since we want to show extended error message, if something went sideways */
	register_deactivation_hook( __FILE__ , 	'sixscan_events_deactivation' );
	register_uninstall_hook( __FILE__ , 	'sixscan_events_uninstall' );	
	
	/*	This action installs the plugin */
	if ( sixscan_common_is_account_active() == FALSE ){
		add_action( 'admin_notices' , 'sixscan_installation_manager' );
	}
	else{
		/*	This action checks whether the plugin has registered, and if not - shows the "don't forget to register" notice to the user 
		This is only shown, if the plugin is active */
		add_action( 'admin_notices' , 'sixscan_installation_account_setup_required_notice' );
	}
	
	/*	6Scan menu in Wordpress toolbar */
	add_action( 'admin_menu' , 'sixscan_menu_install' );

	
}


?>