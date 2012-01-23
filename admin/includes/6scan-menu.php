<?php
	
if ( ! defined( 'ABSPATH' ) ) 
	die( 'No direct access allowed' );
	
function sixscan_menu_install(){
	add_menu_page( '6Scan' , '6Scan' , 'manage_options' , SIXSCAN_COMMON_DASHBOARD_URL , '' , SIXSCAN_PLUGIN_URL . 'data/img/logo_small.png' );
	add_submenu_page( SIXSCAN_COMMON_DASHBOARD_URL , '6Scan Dashboard' , 'Dashboard' , 'manage_options' , SIXSCAN_COMMON_DASHBOARD_URL , 'sixscan_menu_dashboard' );
	if ( sixscan_common_is_account_operational() == TRUE )
		add_submenu_page( SIXSCAN_COMMON_DASHBOARD_URL , '6Scan Settings' , 'Settings' , 'manage_options' , SIXSCAN_COMMON_SETTINGS_URL , 'sixscan_menu_settings' );
	add_submenu_page( SIXSCAN_COMMON_DASHBOARD_URL , '6Scan Support' , 'Support' , 'manage_options' , SIXSCAN_COMMON_SUPPORT_URL , 'sixscan_menu_support' );
}

function sixscan_menu_support(){
	
	/*	If user has already submitted a ticket, show him a "Thank you" */
	if ( isset( $_GET[ 'ticket_submitted' ] ) && ( $_GET[ 'ticket_submitted' ] == 1 ) ){
		print "<center>\nThank you for your submission.  6Scan support will be in touch shortly.\n</center>\n";		
		return;
	}
	
	$custom_message = "<br>For any questions, please visit our <a href='http://6scan.com/support' target='_blank'>support community</a>.<br/><br/>";
	$custom_message .= "If there is a problem with 6Scan's plugin, our support team would like to help you solve it.<br/>  Please verify your email below, add any comments you may have, and <b>click Submit to automatically open a support ticket.</b>\n<br><br>";
	$err_form = sixscan_menu_get_error_submission_form( "" , $custom_message );
	print $err_form;
}

function sixscan_menu_settings(){
	
	/*	Create dashboard frame with settings redirect request */
	sixscan_menu_create_dashboard_frame( SIXSCAN_COMMON_DASHBOARD_URL_SETTINGS );
}

function sixscan_menu_dashboard(){

	/* Create dashboard frame with default redirect request (to the main dashboard) */
	sixscan_menu_create_dashboard_frame();
}

function sixscan_menu_create_dashboard_frame( $redirect_request = SIXSCAN_COMMON_DASHBOARD_URL_MAIN ){
	print "<iframe id='sixscan_dashboard_iframe' src=\"" . sixscan_communication_oracle_auth_get_link( $redirect_request ) . "\" width='100%' height='100%'>\n";
	print "</iframe>\n";
?>	
	<script language='javascript'>
            var frame = document.getElementById('sixscan_dashboard_iframe');
            frame.height = document.body.scrollHeight - 125;
	</script>
<?php
}

function sixscan_menu_wrap_error_msg( $err_msg ){
	$result_html = "";
	
	$result_html .= "<center>\n";
	$result_html .=	"<div style=\"padding-top: 20px;\"></div>";
	$result_html .= "	<div class=\"rounded_box\" style=\"width: 600px; margin: 0; padding: 30px 10px; font-size: 16px; font-family:arial, 'Times New Roman', Times, serif; background-color: rgb(220, 219, 219); border: 1px inset #bbbbbb; box-shadow: 1px 0px 0px #bbbbbb inset; border-radius: 6px 6px 6px 6px; border-bottom: 1px solid #f0f0f0; border-right: 1px solid #f0f0f0;\">";
	$result_html .= $err_msg;
	$result_html .= "</div>\n";
	$result_html .= "</center>";

	return $result_html;
}

function sixscan_menu_get_error_submission_form( $err_data = "" , $custom_form_message = "" ){	
	
	$result_html = "";
	$error_details = base64_encode( "User error: " . $err_data . "\n\n" . sixscan_common_gather_system_information_for_anonymous_support_ticket() );
	$result_html .= "<center>\n";
	$result_html .= "<div style=\"padding-top: 20px;\"></div>";
	$result_html .= "<div class=\"rounded_box\" style=\"width: 680px; margin: 0; padding: 30px 10px; font-size: 16px; font-family:arial, 'Times New Roman', Times, serif; background-color: rgb(220, 219, 219); border: 1px inset #bbbbbb; box-shadow: 1px 0px 0px #bbbbbb inset; border-radius: 6px 6px 6px 6px; border-bottom: 1px solid #f0f0f0; border-right: 1px solid #f0f0f0;\">\n";
	if ( $custom_form_message == "" )
		$result_html .= "6Scan's support team would like to help you solve this problem!  Please verify your email below, add any comments you may have, and <b>click Submit to automatically open a support ticket.</b>\n<br><br>";	
	else
		$result_html .= $custom_form_message;
	$result_html .= "<form action=\"" . SIXSCAN_BODYGUARD_ERROR_REPORT_FORM_URL . "\" method=POST>\n";
	$result_html .= "<input type=hidden name=root_url value=\"" . get_option( 'siteurl' ) . "\">\n";
	$result_html .= "<input type=hidden name=wordpress_version value=\"" . get_bloginfo('version') . "\">\n";
	$result_html .= "<input type=hidden name=6scan_version value=\"" . SIXSCAN_VERSION . "\">\n";	
	$result_html .= "<input type=hidden name=error_details value=\"" . $error_details . "\"><br>\n";
	$result_html .= "<table>\n";
	$result_html .= "<tr><td width='80'>Email:</td><td><input type=text name=admin_email value=\"" . get_option( "admin_email" ) . "\"></td></tr>\n";
	$result_html .= "<tr><td width='80'>Comments:</td><td><textarea name=admin_comments cols=60 rows=3></textarea></td></tr>\n";
	$result_html .= "<input type=hidden name=return_url value='" . SERVER_HTTP_PREFIX . $_SERVER[ "SERVER_NAME" ] . $_SERVER[ "REQUEST_URI" ] . "&ticket_submitted=1'>\n";
	$result_html .= "<tr><td width='80'></td><td><input type=submit value='Submit error log'></td>\n";
	$result_html .= "</table>";
	$result_html .= "</form>\n";
	$result_html .= "<span style='font-size:0.8em'>We will automatically send troubleshooting information along with your ticket.  6Scan respects your privacy and will never use your information except to help you with your problem.</span>\n";
	$result_html .= "</div>\n";	
	$result_html .= "</center>\n";
	
	return $result_html;
}
?>