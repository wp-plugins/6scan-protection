<?php
	
if ( ! defined( 'ABSPATH' ) ) 
	die( 'No direct access allowed' );
	
function sixscan_menu_install(){
	add_menu_page( '6Scan' , '6Scan' , 'manage_options' , SIXSCAN_COMMON_DASHBOARD_URL , '' , SIXSCAN_PLUGIN_URL . 'data/img/logo_small.png' );
	add_submenu_page( SIXSCAN_COMMON_DASHBOARD_URL , '6Scan Dashboard' , 'Dashboard' , 'manage_options' , SIXSCAN_COMMON_DASHBOARD_URL , 'sixscan_menu_dashboard' );
}

function sixscan_menu_dashboard(){

	/*	If user has submitted a ticket, show him a "Thank you" */
	if ( isset( $_GET[ 'ticket_submitted' ] ) && ( $_GET[ 'ticket_submitted' ] == 1 ) ){
		print "<center>\nThank you for your submission.  6Scan support is working on your problem and will be in touch shortly.\n</center>\n";
		
		/* There is no more last error */
		update_option( SIXSCAN_OPTION_LAST_ERROR_OCCURED , '');	
		return;
	}
	
	if ( sixscan_common_is_oracle_registered() == TRUE ){
		if ( sixscan_common_is_oracle_verified() == TRUE ){	/*	Registered and validated */
			
			/* Show the dashboard */
			sixscan_menu_show_options_screen();
		}
		else {		/*	Registered , but not validated */
			if ( isset( $_POST['retry_validation'] ) ){

				/*	We are retrying validation */
				$verification_result = sixscan_communication_oracle_reg_verification();
				if ( $verification_result === TRUE ){
					sixscan_common_set_oracle_verified_true();
					sixscan_menu_show_options_screen();
					return;
				}else{
					$error_message = "There was a problem verifying your site with 6Scan: " . $verification_result . "<br>";
					update_option( SIXSCAN_OPTION_LAST_ERROR_OCCURED , base64_encode( $verification_result ) );
					sixscan_menu_show_error_msg( $error_message );
					sixscan_menu_show_error_submission_form( $verification_result );
				}
			}else{
				/* User just reentered verification screen */				
				$previous_error = get_option( SIXSCAN_OPTION_LAST_ERROR_OCCURED );
				if ( $previous_error != '' ){
					$previous_error = base64_decode( $previous_error );
					$error_message = "There was a problem verifying your site with 6Scan: " . $previous_error . "<br>";
					sixscan_menu_show_error_msg( $error_message );
					sixscan_menu_show_error_submission_form( $previous_error );
				}else{				
					/* Technicall , this menu should never be displayed, but let's leave this code, in case somebody plays with DB */
					$error_message = "There was a problem verifying your site with 6Scan.<br> Please click 'Retry Verification'.<br>";
					sixscan_menu_show_error_msg( $error_message );
				}
			}
						
			/* verification failed. Show "Try now" button here */
			sixscan_menu_show_verification_screen();
			return;
		}
	}
	else {		/*	Not registered , and not validated */
		if ( ! isset( $_POST[ 'user_email' ] ) ||  is_email( $_POST[ 'user_email' ] ) == FALSE ){
			/*	Show registration screen */
			sixscan_menu_show_reg_screen();
			return;
		}
		else {
			/*	Registration process */			 
			$registration_form_data = base64_encode( json_encode( $_POST ) );
			$sixscan_register_result = sixscan_communication_oracle_reg_register( get_option( 'siteurl' ) ,
											$_POST[ 'user_email' ] , SIXSCAN_PLUGIN_URL . "modules/signatures/notice.php" , $registration_form_data , $sixscan_oracle_auth_struct );
			
			if ( $sixscan_register_result !== TRUE ){	
					
				$err_descr = "There was a problem registering your site with 6Scan: <b>$sixscan_register_result</b>.<br><br>";		
				sixscan_menu_show_error_msg( $err_descr );
				sixscan_menu_show_error_submission_form( $sixscan_register_result );
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
			$verification_result = sixscan_communication_oracle_reg_verification();
			if ( $verification_result !== TRUE ) {
				
				$error_message = "There was a problem verifying your site with 6Scan: " . $verification_result . "<br>";	
				update_option( SIXSCAN_OPTION_LAST_ERROR_OCCURED , base64_encode( $verification_result ) );				
				sixscan_menu_show_error_msg( $error_message );
				sixscan_menu_show_error_submission_form( $verification_result );
				
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

function sixscan_menu_show_reg_screen() {	
			
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
?>
	<br><br>
	<center>
	<form method=POST>
	<input type=hidden name="retry_validation" value="1">
	<input type=submit value="Retry Verification">
	</form>
	<br>If you continue to encounter problems, please visit our <a href='http://6scan.com/support' target='_blank'>support community</a>.
	</center>	
	<?php
}

function sixscan_menu_show_error_msg( $err_msg ){
?>
	<center>
		<div style="padding-top: 20px;"></div>		
			<div class="rounded_box" style="width: 600px; margin: 0; padding: 30px 10px; font-size: 16px; font-family:arial, 'Times New Roman', Times, serif; background-color: rgb(220, 219, 219); border: 1px inset #bbbbbb; box-shadow: 1px 0px 0px #bbbbbb inset; border-radius: 6px 6px 6px 6px; border-bottom: 1px solid #f0f0f0; border-right: 1px solid #f0f0f0;">
				<?php echo $err_msg; ?>  
			</div>
	</center>
<?php
}

function sixscan_menu_show_error_submission_form( $err_data = "" ){	
	
	$error_details = base64_encode( "User error: " . $err_data . "\n\n" . sixscan_common_gather_system_information_for_anonymous_support_ticket() );
	print "<center>\n";
	print "<div style=\"padding-top: 20px;\"></div>";
	print "<div class=\"rounded_box\" style=\"width: 600px; margin: 0; padding: 30px 10px; font-size: 16px; font-family:arial, 'Times New Roman', Times, serif; background-color: rgb(220, 219, 219); border: 1px inset #bbbbbb; box-shadow: 1px 0px 0px #bbbbbb inset; border-radius: 6px 6px 6px 6px; border-bottom: 1px solid #f0f0f0; border-right: 1px solid #f0f0f0;\">\n";
	print "6Scan's support team would like to help you solve this problem!  Please verify your email below, add any comments you may have, and <b>click Submit to automatically open a support ticket.</b>\n<br><br>";	
	print "<form action=\"" . SIXSCAN_BODYGUARD_ERROR_REPORT_FORM_URL . "\" method=POST>\n";
	print "<input type=hidden name=root_url value=\"" . get_option( 'siteurl' ) . "\">\n";
	print "<input type=hidden name=wordpress_version value=\"" . get_bloginfo('version') . "\">\n";
	print "<input type=hidden name=6scan_version value=\"" . SIXSCAN_VERSION . "\">\n";	
	print "<input type=hidden name=error_details value=\"" . $error_details . "\"><br>\n";
	print "<table>\n";
	print "<tr><td width='80'>Email:</td><td><input type=text name=admin_email value=\"" . get_option( "admin_email" ) . "\"></td></tr>\n";
	print "<tr><td width='80'>Comments:</td><td><textarea name=admin_comments cols=60 rows=3></textarea></td></tr>\n";
	print "<input type=hidden name=return_url value='" . SERVER_HTTP_PREFIX . $_SERVER[ "SERVER_NAME" ] . $_SERVER[ "REQUEST_URI" ] . "&ticket_submitted=1'>\n";
	print "<tr><td width='80'></td><td><input type=submit value='Submit error log'></td>\n";
	print "</table>";
	print "</form>\n";
	print "<span style='font-size:0.8em'>We will automatically send <b>non-identifying</b> troubleshooting information along with your ticket.  6Scan respects your privacy and will never use your information except to help you with your problem.</span>\n";
	print "</div>\n";	
	print "</center>\n";
}
?>