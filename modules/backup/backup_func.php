<?php


function sixscan_backup_func_controller( $amazon_backup_address , $backup_type , &$backup_err_descr ){
	/* Empty error message */
	$backup_err_descr = "";

	/* Check whether backup can be run on this system */
	$can_run_backup = sixscan_backup_func_can_run();
	if ( $can_run_backup !== TRUE ){
		$backup_err_descr = $can_run_backup;
		return FALSE;
	}

	/* Set enough time for backup to work. */
	set_time_limit( SIXSCAN_BACKUP_MAX_RUN_SECONDS );	

	/* Run backup according to what was requested */
	if ( $backup_type == SIXSCAN_BACKUP_DATABASE_REQUEST ){		

		 $backup_result = sixscan_backup_func_db( $backup_name_file_result );					
	}
	else if ( $backup_type == SIXSCAN_BACKUP_FILES_REQUEST ){

		$backup_result = sixscan_backup_func_files( $backup_name_file_result );
	}
	else {
		 $backup_err_descr = "Bad action type requested";
		 return FALSE;
	}
	
	/*	An error occured */
	if ( $backup_result === FALSE){
		 	$backup_err_descr = $backup_name_file_result;
			return FALSE;				
	}

	/* Save to amazon */
	$amazon_save_val = sixscan_backup_comm_save_file( $amazon_backup_address , $backup_name_file_result );
	
	/* Cleanup */
	unlink( $backup_name_file_result );

	/* If we have failed uploading the file to amazon - return the error description */
	if ( $amazon_save_val !== TRUE ){
		$backup_err_descr = $amazon_save_val;
		return FALSE;
	}	
	
	return TRUE;
}

function sixscan_backup_func_can_run(){
	
	/* We don't run on Windows now */
	if ( sixscan_common_is_windows_os() == TRUE )
		return "os_limitation" . SIXSCAN_COMMON_BACKUP_MSG_DELIMITER . "Backup can currently only run on Linux platform";

	/*	Can't run in safe mode */
	if ( ini_get( 'safe_mode' ) )
		return "safe_mode_limitation" . SIXSCAN_COMMON_BACKUP_MSG_DELIMITER . "System is in safe mode, which prevents 6Scan from writing backup files";
	
	/* We need to be able to change execution time */	
	@set_time_limit( SIXSCAN_BACKUP_MAX_RUN_SECONDS );
	if ( ini_get( 'max_execution_time' ) != SIXSCAN_BACKUP_MAX_RUN_SECONDS )
		return "max_execution_time_limitation" . SIXSCAN_COMMON_BACKUP_MSG_DELIMITER . "6Scan can't set max_execution_time, which prevents creation of full backup";

	/* Requires libcurl for file upload */
	if ( function_exists( 'curl_init' ) == FALSE )
		return "libcurl_limitation" . SIXSCAN_COMMON_BACKUP_MSG_DELIMITER . "libcurl is required to be installed";

	/* Testing whether we can execute functions */
	ob_start();	passthru( "mysqldump --version" ); $dumpavailable = ob_get_contents(); ob_end_clean();
	if ( strlen( $dumpavailable ) == 0 )
		return "exec_limitation" . SIXSCAN_COMMON_BACKUP_MSG_DELIMITER . "Commands execution is disabled, which prevents 6Scan from running backup commands";

	return TRUE;
}

/*  Run files backup */
function sixscan_backup_func_files( &$backed_up_filename ){
	
	/*	Generate random seed and random file name */
	srand( (double) microtime() * 1000000 );
	$tmp_random_seed = date("Y-m-d-H-i-s") . "_" . substr( md5 ( rand( 0, 32000 )) , 0 , 10 );	
	$temp_file_archived = get_temp_dir() . "files_backup_$tmp_random_seed.tar.gz";
	$tmp_backup_dir = "/tmp/6scan_backup_$tmp_random_seed/";

	/*	If a previous file was not deleted from some reason, delete it now */
	sixscan_backup_func_delete_previous( SIXSCAN_BACKUP_LAST_FS_NAME , $temp_file_archived );

	/* Prepare backup directory */	
	$backup_cmd = "mkdir $tmp_backup_dir; cp -r " . ABSPATH . " $tmp_backup_dir";
	ob_start(); passthru( $backup_cmd ); ob_end_clean();

	/* Linux backup is using tar.gz */		
	$backup_cmd = "tar -czf $temp_file_archived $tmp_backup_dir 2>&1";
	$ret_val = 0;

	/* Run the tar command, while ignoring its output */
	passthru( $backup_cmd , $ret_val );

	$cleanup_cmd = "rm -rf $tmp_backup_dir 2>&1";
	passthru( $cleanup_cmd );

	/* Check for error that might've occured while running tar. Retval 0 is ok */
	if ( $ret_val == 0 ){
		$backed_up_filename = $temp_file_archived;		 		
		return TRUE;
	}
	else{
		$backed_up_filename = "Failed running tar. Retval = $ret_val " . SIXSCAN_COMMON_BACKUP_MSG_DELIMITER . "Tar command failed";
		return FALSE;
	}
}

/* Run DB backup */
function sixscan_backup_func_db( &$backed_up_filename ){

	/*	Generate random seed and random file name */
	srand( (double) microtime() * 1000000 );
	$tmp_sql_dmp = "sql_dump" . date("Y-m-d-H-i-s") . "_" . substr( md5 ( rand( 0, 32000 )) , 0 , 10 );

	$temp_sql_file_name = get_temp_dir() . $tmp_sql_dmp . ".sql";	
	$temp_sql_file_tgzipped = get_temp_dir() . $tmp_sql_dmp . ".tar.gz";

	/*	If a previous file was not deleted from some reason, delete it now and save the current filename */
	sixscan_backup_func_delete_previous( SIXSCAN_BACKUP_LAST_DB_NAME , $temp_sql_file_tgzipped );

	/* Prepare the mysqldump command, using defines from wp-config.php */
	$db_backup_cmd = "mysqldump -h " . DB_HOST . " -u " . DB_USER . " -p" . DB_PASSWORD . " " . DB_NAME . " > $temp_sql_file_name";	

	/* Run the mysqldump */	
	$ret_val = 0;
	passthru( $db_backup_cmd , $ret_val ); 

	if ( $ret_val != 0 ){
		@unlink($temp_sql_file_name);
		$backed_up_filename = "Mysqldump failed. Retval = $ret_val " . SIXSCAN_COMMON_BACKUP_MSG_DELIMITER . "mysqldump command failed";
		return FALSE;
	}	

	/* Create tar.gz and remove the original .sql, while ignoring the output */
	$archive_command = "tar -czf $temp_sql_file_tgzipped $temp_sql_file_name 2>&1";
	$ret_val = "";
	passthru( $archive_command , $ret_val );

	$cleanup_cmd = "rm $temp_sql_file_name 2>&1";
	passthru( $cleanup_cmd );

	if ( $ret_val != 0 ){
		$backed_up_filename = "Failed running tar of sql dump file. Retval = $ret_val" . SIXSCAN_COMMON_BACKUP_MSG_DELIMITER . "Tar command failed";
		return FALSE;
	}
	else{
		$backed_up_filename = $temp_sql_file_tgzipped;
		return TRUE;
	}
}

/*	If, from any reason (like OOM, server reset or anything else), the script was interrupted
	while creating/uploading a backup, we should cleanup the archive files. */
function sixscan_backup_func_delete_previous( $backup_type , $new_backup_filename ){

	$last_db_backup_name = get_option( $backup_type );
	if ( file_exists( $last_db_backup_name ) )
		@unlink( $last_db_backup_name );
	update_option( $backup_type , $new_backup_filename );
}

?>