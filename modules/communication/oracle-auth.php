<?php

if ( ! defined( 'ABSPATH' ) ) 
	die( 'No direct access allowed' );

function sixscan_communication_oracle_auth_get_link( $is_first_time ) {
	/*	Nonce increases everytime user accesses dashboard */
	$current_nonce = get_option( SIXSCAN_OPTION_COMM_ORACLE_NONCE ) + 1;	
	update_option( SIXSCAN_OPTION_COMM_ORACLE_NONCE , $current_nonce );
	
	return sixscan_communication_oracle_auth_dashboard_get( $current_nonce , $is_first_time );
}

function sixscan_communication_oracle_auth_dashboard_get( $nonce , $is_first_time) {
	
	if ( ( sixscan_common_get_dashboard_token() == FALSE ) || ( sixscan_common_get_site_id() == FALSE ) )
		return FALSE;
	
	$token_for_dashboard = md5( SIXSCAN_COMM_ORACLE_AUTH_SALT . $nonce . sixscan_common_get_dashboard_token() );
	$dashboard_url = SIXSCAN_COMM_ORACLE_AUTH_DASHBOARD_URL . 'site_id=' .  sixscan_common_get_site_id()
															 . '&nonce=' . $nonce . '&token=' . $token_for_dashboard;
	
	
	/*	When user sees the dashboard first time , show him a welcome message */
	if ( $is_first_time == TRUE ){
		$dashboard_url .= '&firsttime=1';
	}
	
	return $dashboard_url;	
}

?>