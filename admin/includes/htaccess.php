<?php
if ( ! defined( 'ABSPATH' ) ) 
	die( 'No direct access allowed' );

function sixscan_htaccess_install( $htaccess_sixscan_version = "") {

	try { 
		$htaccess_sixscan = trim ( file_get_contents( SIXSCAN_HTACCESS_6SCAN . $htaccess_sixscan_version ) ) . "\n\n";		
		
		if ( ! copy( SIXSCAN_HTACCESS_6SCAN_GATE_SOURCE, SIXSCAN_HTACCESS_6SCAN_GATE_DEST ) ) {
			throw new Exception( 'Failed to find ' . SIXSCAN_HTACCESS_6SCAN_GATE_FILE_NAME . ' during installation' );
		}
		
		if ( ! copy( SIXSCAN_SIGNATURE_SRC, SIXSCAN_SIGNATURE_DEST ) ) {
			throw new Exception( 'Failed to find ' . SIXSCAN_SIGNATURE_SRC . ' during installation' );
		}
		
		if ( file_exists( SIXSCAN_HTACCESS_FILE ) ) {
			$htaccess_content = file_get_contents( SIXSCAN_HTACCESS_FILE );
			$htaccess_sixscan .= preg_replace( '@# Created by 6Scan plugin(.*?)# End of 6Scan plugin@s' , '' , $htaccess_content ) ;
		}
	
		$htaccess_file = @fopen( SIXSCAN_HTACCESS_FILE, 'w' );
		
		if ( ! $htaccess_file )
			throw new Exception('Failed to open htaccess during installation');
			
		fwrite( $htaccess_file , $htaccess_sixscan );
		fclose( $htaccess_file );
	} catch( Exception $e ) {
		return( $e );
	}			
	
	return TRUE;
}
	
function sixscan_htaccess_uninstall() {
	try {
		if ( file_exists( SIXSCAN_HTACCESS_FILE ) ) {
			$htaccess_content = file_get_contents(SIXSCAN_HTACCESS_FILE);
			$a = preg_replace( '@# Created by 6Scan plugin(.*?)# End of 6Scan plugin@s', '', $htaccess_content) ;
		}
	
		$htaccess_file = @fopen( SIXSCAN_HTACCESS_FILE, 'w' );
		
		if (! $htaccess_file)
			return 'Failed to open htaccess during installation' ;
			
		fwrite( $htaccess_file, $a );
		fclose( $htaccess_file );
		
		if ( filesize( SIXSCAN_HTACCESS_FILE ) == 1 ) 
			unlink( SIXSCAN_HTACCESS_FILE );
			
		if ( file_exists( SIXSCAN_HTACCESS_6SCAN_GATE_DEST ) )
			unlink( SIXSCAN_HTACCESS_6SCAN_GATE_DEST );	
			
		if ( file_exists( SIXSCAN_SIGNATURE_DEST ) )
			unlink ( SIXSCAN_SIGNATURE_DEST ) ;
		
	} catch( Exception $e ) {
		return( $e );
	}
	
	return TRUE;
}
?>