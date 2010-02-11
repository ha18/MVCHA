<?php

/**
 * CHECK YOUR ERROR SETUP
 *
 * php.ini settings:
 *  - ignore_repeated_errors On
 *  - ignore_repeated_source On
 *  - display_errors Off
 *  - display_startup_errors Off
 *  - log_errors On
 *  - error_log /path/to/php_parser_errors
 *
 * if you have those set up, you don't need to do httpd.conf or .htaccess
 * but those are an option if you want to set them up there
 *
 */

// these are NOT bitwise (obviously); you cannot use them with trigger error, 
// but you can use them with error()

define ( 'E_DEBUG', -3 );
define ( 'E_PROFILER', -5 );
define ( 'E_WARN_AND_LOG', -7 );

define ( 'E_FILE_NOT_FOUND', -404 );
define ( 'E_NOT_FOUND', -404 );
define ( 'E_404', -404 );

define ( 'E_SYSTEM_ERROR', -503 ); // 503 is "Service Unavailable" which is preferable and more honest that "System Error 500"
define ( 'E_503', -503 );

define ( 'E_PERMISSION_DENIED', -401 );
define ( 'E_401', -401 );


/**
 * Check the error setup on a production server.  Should be called from a bootstrap file.
 *
 * Don't check if we're on dev or local here; that's more for wherever we call the function
 */
function error_sanity_check ()
{
	$display_errors 		= ini_get ( 'display_errors' );
	$display_startup_errors = ini_get ( 'display_startup_errors' );
	$log_errors 			= ini_get ( 'log_errors' );
	$error_log 				= ini_get ( 'error_log' );

// FINISH ME TODO	

}

########## DOWNLOADED #################

                                              // need these for trigger_error but that's all
function mvcha_error ( $error=0, $message='', $file=null, $line=null, $context=null )
{
	$backtrace = debug_backtrace ();
    $toss 	   = array_shift($backtrace); // the top of the stack is useless

	$tracks = array ();

	if ( $file ) // from PHP's error mechanism
	{
		$tracks[] = "Origination: {$file}\t{$line}";
	}

	foreach ( $backtrace as $bt )
	{
		$func = isset ( $bt['function'] ) ? $bt['function'].'()' : '';
		$file = isset ( $bt['file'] )     ? $bt['file'] : '';
		$line = isset ( $bt['line'] )     ? $bt['line'] : '';

		if ( isset ( $bt['args'] ) )
		{
			if ( is_object ( $bt['args'] ) )
			{
				$args = '';
				continue;
			}


			if ( is_array ( $bt['args'] ) )
			{
				$args = '';
				foreach ( $bt['args'] as $bt_args )
				{
					if ( is_array ( $bt_args ) )
					{
						$args = 'Array'; // - see ' . __FILE__ . ' line ' . __LINE__; //implode ( ',', $bt_args );
					} else {
						$args = $bt_args;
					}
				}
			} else {
				$args = $bt['args'];
			}

		} else {
			$args = '';
		}
			
		if ( is_object ( $args ) )
		{
			continue;
		}
	
		try
		{
			$tracks[] = "{$func}\t{$file}\t{$line}\t{$args}";
		} catch ( Exception $e ) {
			echo $e->getMessage() . "\n";
		}
	}

	$tracks_br = implode ( "<br />\n", $tracks );
	$tracks    = implode ( "\n", $tracks );
	
	switch ( $message )
	{
		case 'db.noquery':
		case 'db.no_connect':
		case 'db.format_query':
		case 'db.mysql_error':
		case 'db.no_select_db':

		list ( $log_msg, $message ) = get_error_msg_string ( $message ); // 'db.connect' becomes something more useful
	}

	switch ( $error ) 
	{

		// errors we'll send to the admin and abort, with a simple message to the user; eg: database connection down. 
		// User sees "there was a problem with the web site but we're looking at it"

		case E_USER_ERROR: 			// errors we'll send to the admin, and display to user, "File Not Found" or "Invalid Email"
		
			//session ( 'msg', "USER ERROR: {$message}" ); // see view/web.php for $_SESSION['msg']
			elog ( "USER ERROR: {$message}\n{$tracks}" );
			// pseudo-die; stop processing, show message, but let page around it fill in...
			// HOW?
			// have a views/errors directory with an error html file for each $error numbers: 128.txt
		break;

		case E_USER_WARNING:
		case E_USER_NOTICE:
		case E_NOTICE:
			registry ( 'end_stack:errors', $tracks_br, true );
			registry ( 'end_stack:errors', $message, true );
			elog ( "NOTICE: {$message}\n{$tracks}" );
			// DON'T die
		break;

		case E_PERMISSION_DENIED:
		case E_401:
			// see wiki for details: /wiki/doku.php/benchmarking_platform:mvcha_overall_error_strategy
			header ( H401, true, 401 );
			registry ( 'end_stack:errors', $tracks_br, true );
			registry ( 'end_stack:errors', $message, true );
			elog ( "PERMISSION DENIED: {$message}\n{$tracks}" );
			die(view('401.php'));
		break;

		case E_404:
		case E_NOT_FOUND:
		case E_FILE_NOT_FOUND:
			header ( H404, true, 404 );
			die(view('404.php'));
		break;

		case E_503:
		case E_SYSTEM_ERROR:
			header ( H503, true, 503 );
			elog ( "SYSTEM ERROR: {$message}\n{$tracks}" );
			die(view('503.php'));
		break;

		case E_DEBUG:
			//firebug/firephp
			elog ( "DEBUG: {$message}\n{$tracks}" );
			registry ( 'end_stack:errors', $tracks_br, true );
			registry ( 'end_stack:errors', $message, true );
		break;

		case E_PROFILER:
			// firebug/firephp
			elog ( "PROFILER: {$message}\n{$tracks}" );
			registry ( 'end_stack:errors', $tracks_br, true );
			registry ( 'end_stack:errors', $message, true );
		break;

		case E_WARN_AND_LOG:
			elog ( "WARN & LOG: {$message}\n{$tracks}" );
			registry ( 'end_stack:errors', $tracks_br, true );
			registry ( 'end_stack:errors', $message, true );
		break;

		// we can't handle the fatal E_ERROR & E_COMPILE_ERROR & E_CORE_ERROR etc, but anything else we get is something we want
		// to log for sure.  
		default:
		break;

	}

}


function elog ( $msg )
{
	static $fp;

	if ( !defined ( 'ELOG' ) )
	{
		return false;
	}

	if ( !$fp )
	{   
		$fp = fopen ( ELOG, 'a' );
	}   

	fputs ( $fp, "$msg\n" );

	// no close, so we can reuse the statically defined $fp
}

function error ( $message, $level )
{
	mvcha_error ( $level, $message );
}


set_error_handler('mvcha_error');
