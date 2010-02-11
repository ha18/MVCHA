<?php

/*

EXAMPLE

require_once 'plugins.inc.php';

function hook_add_test ( $data )
{
	$data['email'] = 'email@hansanderson.com';	
	return $data;
}

// each hook, like 'view_pre' should have specific data sent to it.  That will be annotated like this:
// then in the ~/documentation/HOOKS file, we can describe each hook, what it does, show the line number
// it is on and what file it is in and what, in that case, the $data parameter looks like.
/ * * @Hook
 *
 * @name view_pre
 * @data user_id content etc (what does the $data array contain?)
 * @description
 *
hook_add ( 'view_pre',   'hook_is_about_to_run_prepare' );
hook_add ( 'view_alter', 'hook_alter_the_view_of_what_is_display' );
hook_add ( 'view_post',  'hook_is_done_do_cleanup' );

$data = array ( 'name' => 'Hans', 'email' => 'me@ha17.com' );
$data = hook_run ( 'view_pre', $data );

print "<pre>"; print_r( $data ); print "</pre>";
 */


/**
 *
 * Load the plugins defined in PLUGINS/plugins
 *
 * @return 
 * @since v1.0
 *
 * @author Hans Anderson <handerson@executiveboard.com>
 *
 */
function plugins_load ()
{
	// how to scope outside of function?
	// open plugins dir
	// load all PLUGINS/dirname/plugins.php files found in dirs using plugin_load_this_one()
}

function plugin_load_this_one ()
{
	// how to scope outside of function?
    // load one, checking cache
	// them mtime
	// if exists, return, if not file_get_contents then into cache then return
	// actually, let's test this, I think that apc might cache it by default
}

function hook_add ( $hook_name, $hook_func )
{
	if ( empty ( $hook_name ) )
	{
		warn ( 'Hook name not defined' );
		return false;
	}

	if ( empty ( $hook_func ) )
	{
		warn ( 'Hook function name not defined' );
		return false;
	}

	if ( !function_exists ( $hook_func ) )
	{
		warn ( 'Hook function not found, cannot register' );
		return false;
	}

	hook_registry ( $hook_name, $hook_func );
}

function hook_run ( $hook_name, &$hook_data=null )
{
	if ( $funcs = hook_registry ( $hook_name ) )
	{
		foreach ( $funcs as $func )
		{
			if ( !function_exists ( $func ) )
			{
				warn_and_log ( "Hook function  {$func}() not found, skipping" );
				continue;
			}

			$hook_data = $func ( $hook_data );			
		}
	}

	// if there were no $funcs, we just pass the data back
	// if there were, the data will have changed in some way
	return $hook_data;
}

function hook_registry ( $hook_name, $hook_func=null )
{
	static $hooks;

	if ( $hook_func )
	{
		$hooks[$hook_name][] = $hook_func;
		return true;
	} else {
		return is_array ( $hooks[$hook_name] ) ? $hooks[$hook_name] : array ();
	}
}

?>
