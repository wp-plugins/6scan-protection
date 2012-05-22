<?php

if ( ! defined( 'ABSPATH' ) ) 
        die( 'No direct access allowed' );

/* Called by the backup controller to send files to Amazon storage */
function sixscan_backup_comm_save_file( $amazon_backup_address , $backed_filename ){
        $sixscan_set_fields = array( 'key' , 'AWSAccessKeyId' , 'acl' , 'policy' , 'signature' );
        $sixscan_amazon_options = array();

        if ( file_exists( $backed_filename ) == FALSE )
                return FALSE;

        /* Set parameters for amazon request */
        foreach ( $_REQUEST as $amazon_key => $amazon_val ) {
                if ( in_array(  $amazon_key , $sixscan_set_fields ) )
                        $sixscan_amazon_options[ $amazon_key ] = $amazon_val;
        }               
        
        /* Special value that has to be added */
        $sixscan_amazon_options[ 'Content-Type' ] = 'application/gzip';

        /*      Actual Amazon upload code */
        return sixscan_backup_comm_post_request( urldecode( $amazon_backup_address ) , $sixscan_amazon_options , $backed_filename );     
}


/*      Request to Amazon servers       */
function sixscan_backup_comm_post_request( $remote_url , $headers_array , $file_name ){

        global $sixscan_comm_data_prefix;
        global $sixscan_comm_data_appendix;

        /*      Random string to define data boundary in post request.
        Based on php.net information about fsockopen
        */
        srand( (double) microtime() * 1000000 );
        $boundary = "---------------------" . substr( md5 ( rand( 0, 32000 )) , 0 , 10 );        

        /* Build variables */
        foreach( $headers_array as $key => $value){
            $sixscan_comm_data_prefix .="--$boundary\r\n";
            $sixscan_comm_data_prefix .= "Content-Disposition: form-data; name=\"$key\"\r\n";
            $sixscan_comm_data_prefix .= "\r\n$value\r\n";
        }

        $sixscan_comm_data_prefix .= "--$boundary\r\n";
        $sixscan_comm_data_prefix .= "Content-Disposition: form-data; name=\"file\"; filename=\"$file_name\"\r\n";
        $sixscan_comm_data_prefix .= "Content-Type: application/octet-stream\r\n\r\n";

        $sixscan_comm_data_appendix = "\r\n--$boundary--\r\n";
        $data_file_size = filesize( $file_name );
        $data_size = $data_file_size + strlen( $sixscan_comm_data_prefix ) + strlen( $sixscan_comm_data_appendix );

        /* Open the file and pass it to libcurl */
        $fp = fopen( $file_name , 'r' );
        $curl_handle = curl_init();         
        curl_setopt( $curl_handle , CURLOPT_URL , $remote_url );
        curl_setopt( $curl_handle , CURLOPT_RETURNTRANSFER , 1 ); 
        curl_setopt( $curl_handle , CURLOPT_HTTPHEADER ,array(  'Content-Type: multipart/form-data; boundary=' . $boundary ,
                                                                'Content-Length: ' . $data_size ) );
        curl_setopt( $curl_handle , CURLOPT_POST , TRUE );
        curl_setopt( $curl_handle , CURLOPT_HEADER , FALSE );
        curl_setopt( $curl_handle , CURLOPT_READFUNCTION , 'sixscan_backup_comm_reader_callback' );
        curl_setopt( $curl_handle , CURLOPT_INFILESIZE , $data_size );
        curl_setopt( $curl_handle , CURLOPT_INFILE , $fp );
        
        $response = curl_exec( $curl_handle ); 
        $http_ret_code = curl_getinfo( $curl_handle , CURLINFO_HTTP_CODE );
        curl_close( $curl_handle ); 
        fclose( $fp );

        /* Empty response (204) is the code for successful upload */
        if ( $http_ret_code == 204 )
                return TRUE;
        else                
                return $response;
}

/*      This will be called to read every chunk from file and pass it to server */
function sixscan_backup_comm_reader_callback( $curl_handle , $fp , $requested_len) {       
        static $first_read_happened = 0;
        global $sixscan_comm_data_prefix;
        global $sixscan_comm_data_appendix;

        /* First data chunk - send the prefix and part of the data */
        if ( $first_read_happened == 0 ){
                $first_read_happened++;               
                $first_data_chunk_sz = $requested_len -  strlen( $sixscan_comm_data_prefix ) ;                
                return $sixscan_comm_data_prefix . fread( $fp , $first_data_chunk_sz );
        }
        
        /*      Read data from file and send it */
        $data_chunk = fread( $fp , $requested_len );      

        /* If we had data to send - use it. Otherwise send appendix data */
        if ( strlen ( $data_chunk ) > 0 )
                return $data_chunk;                
        else
                return $sixscan_comm_data_appendix;
}


?>