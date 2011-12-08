<?php
	
if ( ! defined( 'ABSPATH' ) ) 
	die( 'No direct access allowed' );

function sixscan_stat_analytics_showcode(){
/* This function is called via hook, and the nicest way to pass information to it, is by using global variables */
	global $data_inf;
	
	$url_params = "data=" . base64_encode($data_inf);
	?>
	<iframe src="<?php echo SIXSCAN_PLUGIN_URL . 'modules/stat/ga.php?' . $url_params?>" width="0" height="0"></iframe>
	<?php
}
	

function sixscan_stat_analytics_log_action( $cat_name , $act_name , $label_name ){
	global $data_inf;
	global $is_analytics_set;
	
	$tmp_inf = $cat_name . "," . $act_name . "," . sixscan_common_remove_special_chars( $label_name );

	/*	combine several calls of log_action to one long string */
	if ( ! isset ( $data_inf ) )
		$data_inf = $tmp_inf;
	else
		$data_inf = $data_inf . ":" . $tmp_inf;

	if ( ! isset( $is_analytics_set ) )
	{
		$is_analytics_set = 1;
		add_action( 'admin_footer', 'sixscan_stat_analytics_showcode' );				
	}	
}

?>