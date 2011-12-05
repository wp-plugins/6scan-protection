<?php

if ( ! defined( 'ABSPATH' ) )  
	die( 'No direct access allowed' );

function sixscan_events_deactivation() {
	sixscan_common_report_analytics( SIXSCAN_ANALYTICS_UNINSTALL_CATEGORY , SIXSCAN_ANALYTICS_DEACTIVATE_ACT , SIXSCAN_ANALYTICS_OK_STRING );
	
	/*	"Not active" , this will disallow scanner to work on this host */
	update_option( SIXSCAN_OPTIONS_SETUP_ACCOUNT , SIXSCAN_ACCOUNT_NOT_ACTIVE );
	
	/* Revert the .htaccess to "pre-6scan" state */
	sixscan_htaccess_uninstall();
}	

?>