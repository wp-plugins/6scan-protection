<?php

if ( ! defined( 'ABSPATH' ) )  
	die( 'No direct access allowed' );

function sixscan_events_uninstall() {
	sixscan_common_report_analytics( SIXSCAN_ANALYTICS_UNINSTALL_CATEGORY , SIXSCAN_ANALYTICS_UNINSTALL_RM_ACT , SIXSCAN_ANALYTICS_OK_STRING );
	if ( sixscan_installation_is_installed() ) {
		sixscan_installation_uninstall();
	}
}	

?>