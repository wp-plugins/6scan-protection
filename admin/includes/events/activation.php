<?php

if ( ! defined( 'ABSPATH' ) ) 
	die( 'No direct access allowed' );

function sixscan_events_activation() {		
	sixscan_installation_install();	
}	

?>