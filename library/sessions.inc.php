<?php

/**
 * Best if used with mvcha.php system
 *
 */

// SESSION_START() BELOW

if ( defined ( 'SESSION_HANDLER' ) and SESSION_HANDLER == 'apc' )
{

	function session_open_apc ( $path, $name )
	{
		return TRUE; 
	}

	function session_close_apc ()
	{
		return TRUE; 
	}

	function session_read_apc ( $session_id )
	{
		return apc_fetch ( $session_id );
	}

	function session_write_apc ( $session_id, $data )
	{
		return apc_store ( $session_id, $data );
	}

	function session_destroyer_apc ( $session_id )
	{
		return apc_delete ( $session_id );
	}


	function session_garbage_cleanup_apc ( $life=30, $admin_or_non='non' )
	{
		return TRUE;
	}

	session_set_save_handler ( 'session_open_apc', 'session_close_apc', 'session_read_apc', 
							   'session_write_apc', 'session_destroyer_apc', 'session_garbage_cleanup_apc' );

} else {

	function session_open ( $path, $name )
	{
		if ( get_dbh() )
		{
			return TRUE;
		}

		return FALSE;
	}

	function session_close ()
	{
		return TRUE; // we use a persistent connection
	}

	function session_read ( $session_id )
	{
		$q  = "select data from sessions where id = ?";
		$r = dbq ( $q, 'read', $session_id, __FILE__, __LINE__, __FUNCTION__, null );

		if ( !$r )
		{
			return false;
		}

		return ( mysql_num_rows ( $r ) ) ? mysql_result ( $r, 0, 'data' ) : false;
	}

	function session_write ( $session_id, $data )
	{
		
		$q = "replace into sessions set id = ?, data = ?";

		$vars = array ( $session_id, $data );	
		$r = dbq ( $q, 'write', $vars, __FILE__, __LINE__, __FUNCTION__, null );

		if ( $r )
		{
			return TRUE;
		}

		return false;

	}

	function session_destroyer ( $session_id )
	{
		$q = "delete from sessions where id = ?";
		$r = dbq ( $q, 'write', $session_id, __FILE__, __LINE__, __FUNCTION__, null );

		$_SESSION = array();

		if ( $r )
		{
			return true;
		}

		return false;
	}


	/**
	 * We can have different timeouts for admins and normal members
	 * @param int $life In minutes
	 * @param string $admin_or_non 'admin' or 'non' 
	 *
	 */

	function session_garbage_cleanup ( $life=3000, $admin_or_non='non' )
	{
		$modified = date ( 'Y-m-d H:i:s', time() - ( 60 * $life ) );

		$q =<<<EOF
				delete from sessions where modified < ? 
EOF;

		$vars = array ( 'modified' => $modified );
		$r = dbq ( $q, 'write', $vars, __FILE__, __LINE__, __FUNCTION__, null );

		if ( $r )
		{
			return true;
		}

		return false;

	}
	
	
	session_set_save_handler ( 'session_open',  'session_close',     'session_read', 
							   'session_write', 'session_destroyer', 'session_garbage_cleanup' );
}



session_start ();


################################################################################################
// Session functions that have nothing to do with the PHP Session functions


function load_session ( $name, $rs )
{
	//session_regenerate_id ();
    foreach ( $rs as $k => $v )
    {
		$_SESSION["{$name}.{$k}"] = $v;
    }

    return TRUE;
}



function session ( $name, $value=null )
{

	if ( isset ( $_SESSION[$name] ) and $value == null )
	{
		return $_SESSION[$name];

	} elseif ( !isset ( $_SESSION[$name] ) and $value == null ) {
		return false;
	
	} elseif ( isset ( $_SESSION[$name] ) and $value == 'unset' ) {
		$_SESSION[$name] = null;
		return true;
	
	} else {
		$_SESSION[$name] = $value;
		return true;
	}
}

function is_logged_in ()
{
	if ( '' == session('users.id') )
	{
		return FALSE;
	} else {
		return TRUE;
	}
}


function redirect_no_login ()
{
	if ( '' == session('users.id') )
	{
		redirect ( '/login/' );
		die;
	}
}
