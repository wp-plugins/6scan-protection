<?php

/* This is the original URL the user tried to access before the rewrite,
 e.g. /dir/file.php or /images/img.jpg */
$url = $_SERVER[ 'REQUEST_URI' ];
$qspos = strpos( $url , '?' );
if ( $qspos !== FALSE ){
	$url = substr( $url , 0 , $qspos );
}

/* This is the file that would map to the URL without any rewriting */
/*	Construct real path  , while eliminating the extra '/' and '..' and other chars from request*/
$path = realpath( $_SERVER['DOCUMENT_ROOT'] . $url );

/*	"Subtract" cwd() from full path , to get the relative path to the vuln script */
$path_to_cwd = substr( $path , strlen( getcwd() ) );
/*	If there is windows style path , make it linux */
$path_to_cwd = str_replace( "\\" , "/" , $path_to_cwd );

if ( file_exists( '6scan-signature.php' ) ) {
	require_once( '6scan-signature.php' );	

	/*	If there is no such file , we are referred here by permalinks redirection. 
		Sanitize rules expect "/index.php" as vulnerable url in that case*/
		
	if  ( is_file( $path ) == FALSE )
		sixscan_sanitize_input( "/index.php" );
	else
		sixscan_sanitize_input( $path_to_cwd );
}
/*	We continue to the requested file , or to index.php , if we were redirected by permalinks (and does not really exist) */
if ( is_file( $path ) ){
	chdir( dirname( $path ) );
	require $path;
}
else{
	require( "index.php" );
}

?>