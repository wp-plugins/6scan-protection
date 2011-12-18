<?php

if ( ! defined( 'ABSPATH' ) ) 
	die( 'No direct access allowed' );

/*	Used to request an update of databases from server */	
function sixscan_signatures_update_request_total( $site_id , $api_token ){	
	$signature_filename = ABSPATH . "/" . SIXSCAN_COMM_SIGNATURE_FILENAME;
	$error_list = "";
	
	if ( file_exists( $signature_filename ) )
		$current_signature_md5 = md5_file( $signature_filename );
	else
		$current_signature_md5 = "";
		
	$update_success_status = sixscan_signature_engine_update_get ( $site_id , $api_token , SIXSCAN_VERSION );
	
	/*	sixscan_signature_engine_update_get() returns TRUE , or error description */		
	if ( $update_success_status !== TRUE )
		$error_list = " engine_update_get() " . "$update_success_status";
	
	$update_success_status =  sixscan_signatures_update_get( $site_id , $api_token , $current_signature_md5 );
	if ( $update_success_status !== TRUE )
		$error_list = $error_list . " signatures_update_get() " . "$update_success_status";
	
	return $error_list;	
}

function sixscan_signature_engine_update_get ( $site_id , $api_token , $current_engine_version ){

	/*	Craft an URL to request new signature */
	$version_update_url = SIXSCAN_BODYGUARD_6SCAN_UPDATE_APP_URL 	. "?site_id=" . $site_id 
																	. "&api_token=" . $api_token 
																	. "&current_version=" . $current_engine_version;
	
	/*Request the new version from server */
	$response = wp_remote_get( $version_update_url , array(		
		'timeout' => 30,
		'redirection' => 5,
		'httpversion' => '1.1',
		'sslverify' => false,
		'blocking' => true,
		'headers' => array(),
		'cookies' => array()
		)
	);	
		
	if ( is_wp_error( $response ) ) {
		return "wp_remote_get() failed : " . $response->get_error_message();		
	}
	$response_code = wp_remote_retrieve_response_code( $response );
	
	/*	The signatures do not need an update */
	if ( SIXSCAN_UPDATE_LAST_VERSION_RESPONSE_CODE == $response_code )
		return TRUE;	
		
	/*	If the response isn't "you have the latest version" , and "Ok, there is new version" - return error */
	if ( SIXSCAN_UPDATE_OK_RESPONSE_CODE != $response_code )
		return "wp_remote_get() returned status code " . $response_code;
	
	/*	Handle the gzipped program here */	
	$zipped_program = wp_remote_retrieve_body( $response );
	
	/*	Get the headers , and extract the openssl signature from there */	
	$response_headers = wp_remote_retrieve_headers( $response );
	
	/* Check the authenticity of new signatures. have to be signed by 6Scan private key */
	$ssl_check_result = sixscan_signatures_update_check_ssl_signature( $zipped_program , $response_headers );
	if ( $ssl_check_result !== TRUE )
		return $ssl_check_result;
	
	/*	Prepare temporary names */
	$temp_upgrade_dir = get_temp_dir() . trailingslashit( "6scan_update" );
	$temp_zip_file = get_temp_dir() . "bguard.zip";
	
	/*	Create temp directory for update */	
	if ( ( is_dir( $temp_upgrade_dir ) == FALSE ) && ( mkdir( $temp_upgrade_dir ) == FALSE ) )
		return "Failed creating temp directory for update at " . $temp_upgrade_dir;		
		
	/*	Write the zip file */	
	if ( file_put_contents( $temp_zip_file , $zipped_program ) == FALSE )
		return "Failed writing file to " . $temp_zip_file;
		
	/*	Get the write credentials for the following unzip_file() function */	
	if ( ! WP_Filesystem() ) {	    	    
	    return "Failed initializing WP_Filesystem()";
	}	
	
	/*	unzip_file returns mixed on failure */	
	if ( unzip_file( $temp_zip_file , $temp_upgrade_dir ) !== TRUE )
		return "unzip_file() from $temp_zip_file to $temp_upgrade_dir failed";
	
	/*	Remove the no longer required zip file */
	unlink( $temp_zip_file );
	
	$plugin_main_directory = plugin_dir_path( __FILE__ ) . "../../";	
			
	$temp_upgrade_dir_internal = sixscan_signatures_update_find_plugin_dir( $temp_upgrade_dir );
	if ( $temp_upgrade_dir_internal == "")
		return "Couldn't find plugin dir in the unzipped folder $temp_upgrade_dir";
		
	/*	Now bulk copy the rest of files to their places: */
	sixscan_signatures_update_move_dir_recursive( $temp_upgrade_dir_internal , $plugin_main_directory );
		
	/*	Remove the tmp directory */
	rmdir ( $temp_upgrade_dir_internal );
	rmdir ( $temp_upgrade_dir );
	
	return TRUE;
}

function sixscan_signatures_update_get( $site_id , $api_token , $current_signature_md5sum = "" ){
		
	/*	Craft an URL to request new signature */
	$version_update_url = SIXSCAN_BODYGUARD_6SCAN_UPDATE_SIG_URL 	. "?site_id=" . $site_id 
																	. "&api_token=" . $api_token 
																	. "&current_sig_md5=" . $current_signature_md5sum;														
	
	/*	Request signatures from the server */
	$response = wp_remote_get( $version_update_url , array(		
		'timeout' => 30,
		'redirection' => 5,
		'httpversion' => '1.1',
		'sslverify' => false,
		'blocking' => true,
		'headers' => array(),
		'cookies' => array()
		)
	);
	
	
	if ( is_wp_error( $response ) )
		return "wp_remote_get() failed : " . $response->get_error_message();
		
	$response_code = wp_remote_retrieve_response_code( $response );
	
	/*	The signatures do not need an update */
	if ( SIXSCAN_UPDATE_LAST_VERSION_RESPONSE_CODE == $response_code )
		return TRUE;	
	
	$response_data = wp_remote_retrieve_body( $response );
	/*	Get the headers , and extract the openssl signature from there */	
	$response_headers = wp_remote_retrieve_headers( $response );	

	/* Check the authenticity of new signatures. have to be signed by 6Scan private key */
	$ssl_check_result = sixscan_signatures_update_check_ssl_signature( $response_data , $response_headers );
	if ( $ssl_check_result !== TRUE )
		return $ssl_check_result;
	
	/*	Server has returned an error */
	if ( SIXSCAN_UPDATE_OK_RESPONSE_CODE != $response_code )	
		return "wp_remote_get() returned status code " . $response_code;

	/*	OK - we need to update our signatures */
	return sixscan_signatures_update_parse( $response_data );	
}

function sixscan_signatures_update_parse( $raw_data ) {
		
	$signature_filename = ABSPATH . "/" . SIXSCAN_COMM_SIGNATURE_FILENAME;
	$signature_filename_tmp = $signature_filename . ".tmp";
	$signature_offset = strpos( $raw_data , SIXSCAN_SIGNATURE_MULTIPART_DELIMITER );
		
	if ($signature_offset === FALSE)
		return "Couldn't find MULTIPART_DELIMITER in signatures. Wrong format";
	
	if ($signature_offset == 0)	/*	No links in signature */
		$links_list = "";
	else
		$links_list = substr( $raw_data , 0 , $signature_offset - 1 );
		
	$signature_data = substr( $raw_data , $signature_offset + strlen( SIXSCAN_SIGNATURE_MULTIPART_DELIMITER ) );
	
	/*	Update the signatures file */
	if ( file_put_contents( $signature_filename_tmp , $signature_data ) === FALSE )
		return "Failed writing signature data to $signature_filename_tmp";
	
	/*	We want the update signatures change to be atomic. Calling rename() is the best option  */
	if ( rename( $signature_filename_tmp , $signature_filename ) == FALSE )
		return "Failed moving signature data from $signature_filename_tmp to $signature_filename";
	
	/*	Update the htaccess with new signatures. When finished, return TRUE if OK, or error description , if failed */
	return sixscan_signatures_update_htaccess( $links_list );
}

function sixscan_signatures_update_htaccess( $links_list ) {
		
	if ( file_exists( SIXSCAN_HTACCESS_FILE ) ) {
		$htaccess_content = file_get_contents( SIXSCAN_HTACCESS_FILE );
		/*	Remove old 6Scan signature contents */
		$new_content = preg_replace( '@# Created by 6Scan plugin(.*?)# End of 6Scan plugin@s', '', $htaccess_content) ;
	}
	else {
		$new_content = "";
	}
	
	/*	Those symbols have to be escaped , if written into htaccess file as RuleCond */
	$chars_to_escape_arr = array( '.' , '^' , '$' , '+' , '{' , '}' , '[' , ']' , '(' , ')' );
	$escaped_chars_arr = array( '\.' , '\^' , '\$' , '\+' , '\{' , '\}' , '\[' , '\]' , '\(' , '\)' );
	
	/*	We need the site relative path */
	$parsed = parse_url( home_url() );	
	$rel_path = $parsed[ 'path' ];
	$vuln_urls = "";
	if ( strlen( $links_list ) > 0 ) {
		$links = explode( SIXSCAN_SIGNATURE_LINKS_DELIMITER , $links_list );
		/* Prepare rules for the htaccess */
		foreach ( $links as $one_link ){
			$one_link = trailingslashit( $rel_path ) .  substr( $one_link , 1 );
			$one_link = str_replace( $chars_to_escape_arr , $escaped_chars_arr , $one_link );				
			
			$vuln_urls .= "RewriteCond %{REQUEST_URI} ^" . trim( $one_link ) . "$ [OR]\n";		
		}	
	}	
	
	$vuln_urls .= "RewriteCond %{REQUEST_URI} ^" . SIXSCAN_SIGNATURE_DEFAULT_PLACEHOLDER_LINK . "$\n";
	$vuln_urls .= "RewriteRule .* 6scan-gate.php [E=sixscaninternal:accessgranted,L]\n";	
	
	$htaccess_links = "#Patrol's IPs needs access, to check whether rules update is required\n";
				
	/*  IP's , that are allowed to see non-filtered version of scripts. This is to enable 6Scan backend's decision ,
		whether the patch is still required , or can be removed */	 	
	$ip_list_arr = explode( ',' , SIXSCAN_SIGNATURE_SCANNER_IP_LIST );
		
	foreach ( $ip_list_arr as $ip_index => $one_ok_ip ){
		$one_ok_ip = str_replace ( "." , "\\." , $one_ok_ip );		
		$htaccess_links .= "RewriteCond %{REMOTE_ADDR} ^" . trim( $one_ok_ip ) . "$";
		
		/*	Last IP should not have [OR] flag */
		if ( $ip_index != count( $ip_list_arr ) - 1 )
			$htaccess_links .= " [OR]\n";
		else
			$htaccess_links .= "\n";
	}
	
	/*	If an IP maches one of the listed , skip the next rule */
	$htaccess_links .= "RewriteRule ^(.*)$ - [S=1]\n\n";

	/*	Now add the URL rules */
	$htaccess_links .= $vuln_urls;		
	
	$mixed_site_address = parse_url( get_option( 'siteurl' ) );
	
	if ( ( strlen( $mixed_site_address[ 'path' ] ) == 0 ) || ( $mixed_site_address[ 'path' ] == '/' ) ) 
		$wordpress_base_dirname = "/";
	else
		$wordpress_base_dirname = untrailingslashit( $mixed_site_address[ 'path' ] );		
	
	$tmp_htaccess_file = SIXSCAN_HTACCESS_FILE . ".tmp";
					
	$htaccess_file = @fopen( $tmp_htaccess_file, 'w' );				
	if ( $htaccess_file == FALSE )
		return "Failed opening htaccess file $tmp_htaccess_file for write";
	
	$new_content = "# Created by 6Scan plugin\n" .
					"#Those are used by 6Scan Gateway\n" .
					"SetEnv SIXSCAN_HTACCESS_VERSION	" . SIXSCAN_HTACCESS_VERSION . "\n" .
					"SetEnv SIXSCAN_WP_BASEDIR			" . $wordpress_base_dirname . "\n\n" .
					"<IfModule mod_rewrite.c>\n" .
					"RewriteEngine On\n" .
					"\n#avoid direct access to the 6scan-gate.php file\n" .
					"RewriteCond %{ENV:REDIRECT_sixscaninternal} !^accessgranted$\n" .
					"RewriteCond %{ENV:sixscaninternal} !^accessgranted$\n" .
					"RewriteCond %{REQUEST_URI} 6scan-gate\.php$\n" . 
					"RewriteRule ^(.*)$ - [F]\n\n" .
					"#This is not really a must, but speeds things up a bit\n" .
					"RewriteRule ^6scan-gate\.php$ - [L]\n\n" .
					$htaccess_links . 
					"</IfModule>\n" .
					"# End of 6Scan plugin\n\n" .
					$new_content;
	
	fwrite( $htaccess_file, $new_content );
	fclose( $htaccess_file );
	
	/*	We want the htaccess change to be atomic. Calling rename() is the best option  */
	if ( rename( $tmp_htaccess_file , SIXSCAN_HTACCESS_FILE ) == FALSE )
		return "Failed moving htaccess from $tmp_htaccess_file to " . SIXSCAN_HTACCESS_FILE;
		
	return TRUE;
}

/*	When unzipped in a temporary directory , find the source location (it will usually be "6scan" , but in any case... */
function sixscan_signatures_update_find_plugin_dir( $src_dir ){
	$file_list = scandir( $src_dir );

		foreach( $file_list as $current_file ) {
			if( $current_file == "." || $current_file == ".." ) {
				/* skip "current" and "previous" directory */
				continue;
			}
			
			if( is_dir( $src_dir . "/" . $current_file ) )
				return $src_dir . "/" . $current_file;
		}
		
		return "";	//Not found :(
}

/*	Recursively move directory with its contents */
function sixscan_signatures_update_move_dir_recursive( $source , $dest ){ 	
	
	if( is_dir( $source ) ) {
	/* Source is a directory , and we need to go over it , copying all files inside */
		@mkdir( $dest );
		$file_list = scandir( $source );

		foreach( $file_list as $current_file ) {
			if( $current_file == "." || $current_file == ".." ) {
				/* skip "current" and "previous" directory */
				continue;
			}
			
			if( is_dir( $source . "/" . $current_file ) ) {	
			/*	If it is directory , we have to call the recursion.*/
				sixscan_signatures_update_move_dir_recursive( $source . "/" . $current_file, $dest. "/" . $current_file );
				/*	We can now remove the source directory */
				rmdir( $source . "/" . $current_file );
			}
			else {
				/*just a file - simply move it */				
				rename( $source. "/" .$current_file , $dest . "/" . $current_file );
			}
		}
		return;
	}
	else if( is_file( $source ) ) {
		/*	Source is just a file - move it */
		return rename( $source , $dest );
	}
	
	/* Not a directory or a file */
	return;
} 

function sixscan_signatures_update_check_ssl_signature( $response_data , $response_headers ){
	
	if ( isset ( $response_headers[ SIXSCAN_SIGNATURE_HEADER_NAME ] ) ){
		$openssl_sha1_signature = $response_headers[ SIXSCAN_SIGNATURE_HEADER_NAME ];
	}
	else {
		return "SixScan signature not present in the response";
	}
	
	/*	Verify that program data was signed by 6Scan */
	if ( function_exists ( 'openssl_verify' ) ) {
		$sig_ver_result = openssl_verify( $response_data , base64_decode ( $openssl_sha1_signature ) , SIXSCAN_SIGNATURE_PUBLIC_KEY );	
		if ( $sig_ver_result != 1 ){
			return "openssl_verify() failed with error code " . $sig_ver_result;
		}			
	}
	else {
		return "Function openssl_verify() does not exist";
	}
	
	return TRUE;
}
?>