<?php
function ga_remove_special_chars( $src_str ){
	return preg_replace("/[^a-zA-Z0-9.-]/", "_", $src_str);
}

$data_inf = base64_decode( $_GET['data'] );	

?>
<html><head>
<script type="text/javascript">
var _gaq = _gaq || [];
_gaq.push(['_setAccount', 'UA-21559206-3']);

<?php
	/*	Split the data to category/action/label - and send to analytics */
	$data_arr = explode( ":" , $data_inf );
	foreach ( $data_arr as $one_data_block ){
		$split_inf = explode( "," , $one_data_block );
		$category_name = ga_remove_special_chars( $split_inf[0] );
		$action_name = ga_remove_special_chars($split_inf[1] );
		$label_name = ga_remove_special_chars( $split_inf[2] );
		print("_gaq.push(['_trackEvent', '" . $category_name . "', '" . $action_name . "', '". $label_name ."']);\n");
	}
?>
_gaq.push(['_trackPageview']);
  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
	var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script></head><body></body>
</html>