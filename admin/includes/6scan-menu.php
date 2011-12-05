<?php
	
if ( ! defined( 'ABSPATH' ) ) 
	die( 'No direct access allowed' );
	
function sixscan_menu_install() {
	add_menu_page( '6Scan' , '6Scan' , 'manage_options' , SIXSCAN_COMMON_DASHBOARD_URL , '' , SIXSCAN_PLUGIN_URL . 'data/img/logo_small.png' );
	add_submenu_page( SIXSCAN_COMMON_DASHBOARD_URL , '6Scan Dashboard' , 'Dashboard' , 'manage_options' , SIXSCAN_COMMON_DASHBOARD_URL , 'sixscan_menu_dashboard' );
}

function sixscan_menu_dashboard() {
	if ( sixscan_common_is_oracle_registered() == TRUE ) {
		if ( sixscan_common_is_oracle_verified() == TRUE ) {	/*	Registered and validated */
			
			/* Show the dashboard */
			sixscan_menu_show_options_screen();
		}
		else {		/*	Registered , but not validated */
			if ( isset( $_POST['retry_validation'] ) ) {

				/*	We are retrying validation */
				if ( sixscan_communication_oracle_reg_verification() == TRUE )
				{
					sixscan_common_set_oracle_verified_true();
					sixscan_menu_show_options_screen();
					return;
				}
			}
			/* verification failed. Show "Try now" button here */
			sixscan_menu_show_verification_screen();
			return;
		}
	}
	else {		/*	Not registered , and not validated */
		if ( ! isset( $_POST[ 'user_email' ] ) ||  is_email( $_POST[ 'user_email' ] ) == FALSE ) {
			/*	Show registration screen */
			sixscan_menu_show_reg_screen("");
			return;
		}
		else {
			/*	Registration process */			 
			$registration_form_data = base64_encode( json_encode( $_POST ) );
			$sixscan_oracle_auth_struct = sixscan_communication_oracle_reg_register( get_option( 'siteurl' ) , $_POST[ 'user_email' ] , SIXSCAN_PLUGIN_URL . "modules/signatures/notice.php" , $registration_form_data );
			
			if ( $sixscan_oracle_auth_struct == FALSE ){	
				sixscan_menu_show_reg_screen( "There was a problem registering your site with 6Scan.  Please verify your server has outgoing Internet access.  If you continue to encounter problems, please visit our <a href='http://6scan.com/support' target='_blank'>support community</a>." );
				return;
			}
			
			/*	Mark as registered */
			sixscan_common_set_oracle_registered_true();
			
			/*	Save the values from registration to database */
			sixscan_common_set_site_id( $sixscan_oracle_auth_struct[ 'site_id' ] );
			sixscan_common_set_api_token( $sixscan_oracle_auth_struct[ 'api_token' ] );
			sixscan_common_set_verification_token( $sixscan_oracle_auth_struct[ 'verification_token' ] );
			sixscan_common_set_dashboard_token( $sixscan_oracle_auth_struct[ 'dashboard_token' ] );		
	
			/*	Verify the site */
			if ( sixscan_communication_oracle_reg_verification() == FALSE ) {
				sixscan_menu_show_verification_screen();
				return;
			}	
			sixscan_common_set_oracle_verified_true();
			
			/*	Show user his options screen */
			sixscan_menu_show_options_screen( TRUE );
		}	
	}		
}

function sixscan_menu_show_options_screen( $is_first_time = FALSE) {
	print "<iframe id='sixscan_dashboard_iframe' src=\"" . sixscan_communication_oracle_auth_get_link( $is_first_time ) . "\" width='100%' height='100%'>\n";
	print "</iframe>\n";
?>	
	<script language='javascript'>
            var frame = document.getElementById('sixscan_dashboard_iframe');
            frame.height = document.body.scrollHeight - 125;
	</script>
<?php
}

function sixscan_menu_show_reg_screen( $err_happened = "" ) {	
	
	if ( ! empty( $err_happened ) ){
		print "<center>\n";
		sixscan_menu_show_error_msg( $err_happened );
		print "</center>\n";
	}
		
	$registration_url = SERVER_HTTP_PREFIX . $_SERVER[ "SERVER_NAME" ] . $_SERVER[ "REQUEST_URI" ];
	$registration_email = get_option( "admin_email" );
			
	
	$register_form_url = SIXSCAN_BODYGUARD_REGISTER_FORM_URL . "?user_email=" . urlencode( $registration_email ) . 
						"&submit_link=" . urlencode( $registration_url );
	
	print "<iframe id='sixscan_registration_iframe' src='" . $register_form_url . "' width='100%' height='100%'>\n";
	print "</iframe>\n";
	?>
	<script language='javascript'>
            var frame = document.getElementById('sixscan_registration_iframe');
            frame.height = document.body.scrollHeight - 125;
	</script>
	<?php
}

function sixscan_menu_show_verification_screen() {
	print "<center>\n";
	sixscan_menu_show_error_msg( "There was a problem verifying your site with 6Scan.  Please ensure your site is publicly accessible at the following URL: " . SERVER_HTTP_PREFIX . 
				$_SERVER[ "SERVER_NAME" ] . " and then click below to try again.  If you continue to encounter problems, please visit our <a href='http://6scan.com/support' target='_blank'>support community</a>." );
	?>	<br><br>
	<form method=POST>
	<input type=hidden name="retry_validation" value="1">
	<input type=submit value="Retry Verification">
	</form>
	<?php
	print "</center>\n";
}

function sixscan_menu_show_error_msg( $err_msg ){
?>
		<div style="padding-top: 20px;"></div>		
			<div class="rounded_box" style="width: 600px; margin: 0; padding: 30px 10px; font-size: 16px; font-family:arial, 'Times New Roman', Times, serif; background-color: rgb(220, 219, 219); border: 1px inset #bbbbbb; box-shadow: 1px 0px 0px #bbbbbb inset; border-radius: 6px 6px 6px 6px; border-bottom: 1px solid #f0f0f0; border-right: 1px solid #f0f0f0;">
				<?php echo $err_msg; ?>  
			</div>		
<?php
}
?>