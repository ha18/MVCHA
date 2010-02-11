<?php

/*****************************************************************
Copyright (c) 2008 {kissmvc.php version 0.2}
Eric Koh <erickoh75@gmail.com> http://kissmvc.com

Permission is hereby granted, free of charge, to any person
obtaining a copy of this software and associated documentation
files (the "Software"), to deal in the Software without
restriction, including without limitation the rights to use,
copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following
conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.
*****************************************************************/
//===============================================================
// Controller
// Parses the HTTP request and routes to the appropriate function
//===============================================================
function requestRouter()
{
	$controller = defined('DEFAULT_ROUTE') ? DEFAULT_ROUTE  : 'index';
	$def_action = $action = defined('DEFAULT_ACTION')? DEFAULT_ACTION : 'index';
	$params 	= array();

	if (function_exists('requestParserCustom'))
	{
		requestParserCustom($controller,$action,$params);
	} else {
		requestParser($controller,$action,$params);
	}
	
	// require the controller
  	$controller_file = APP_PATH . "/controllers/{$controller}.php";

  	if ( !preg_match('#^[A-Za-z0-9_-]+$#', $controller) or !file_exists ( $controller_file ) )
	{
    	die(view_by_inc('404.php'));
	}

	// after the next line, all functions in that file are created, called, etc.
	// If there is a check-all function, like a kickout-if-not-logged-in function, it is in action after the next line	
	require( $controller_file );

	// $action is a function that should be defined in the $controller_file
	if ( !function_exists($action) )
	{
		// where the action should be may be a variable instead, let's see if the default action exists
		if ( !function_exists ( $def_action ) )
		{ 
			// it doesn't, so nothing we're going works; time to bail
			die(view_by_inc('404.php'));
		} else {

			$params[] = $action; // what we thought was an action is really a param
			$action   = $def_action;

		}


	}


# to use it this time; hopefully it is right but it should be apparent to the dev if it's not!
	###########################################################
	#   $_SESSION is defined by now, but the function
	#   session() is not, so we directly access $_SESSION
	#   variables in order to bail if necessary before
	#   we get anywhere important.  May be worth revisiting
	#   if we want better control over where they go
	###########################################################
	

	// clean off some basic xss attacks	
	if ( isset ( $_POST ) and sizeof ( $_POST ) > 0 )
	{
		$_OPST = $_POST; // save the original for the scripts that need html; we only use _OPST when we know, for sure, that we might accept HTML or other possibly tainted data, and never any time else
		$_POST = xss_clean ( $_POST, 'strip_tags' );

		// use the nonce/random token method to prevent CSRF attacks
		// if one uses the _form('open') helper then the CSRF stuff is always added
		
		// this field defines the name of the token, might as well make it challenging
		$csrf_field_name = isset ( $_SESSION['csrf.field_name'] ) 	? $_SESSION['csrf.field_name'] : '';

		if ( '' <> $csrf_field_name and 'disabled' <> $csrf_field_name ) // if set to 'disabled', the coder decided against using the CSRF measure; '' is another way of saying we aren't using it (perhaps as part of ajax)
		{
			$csrf_error_uri  = isset ( $_SESSION['csrf.error_uri'] ) 	? $_SESSION['csrf.error_uri'] : '/';
			$csrf_nonce_array= isset ( $_SESSION['csrf.nonce_array'] ) 	? $_SESSION['csrf.nonce_array'] : array ();
			
			if ( !is_array ( $csrf_nonce_array ) )
			{
				redirect ( $csrf_error_uri );
				die;
			}

			$nonce_passed = false;
			foreach ( $csrf_nonce_array as $nonce_key => $nonce_val )
			{
				if ( isset ( $_POST[$nonce_key] ) and $_POST[$nonce_key] == $nonce_val )
				{
					$nonce_passed = true;
					unset ( $_SESSION['csrf.nonce_array'][$nonce_key] );
					break;
				}
			}
		
			// if here, it's not 'disabled' so we check it
			if ( $nonce_passed === false )		 // we have a list of nonces, but this ain't one, bub, NSFY!
			{
				redirect ( $csrf_error_uri );
				die;
			}
		}
	}

	// clean up the parameters... for these, I'm thinking the basics alphanum and dash/underscore are all that are necessary.  
	// note, these aren't query string or post variables, these are the parameters passed into the system like /blog/post/123 or /user/profile/ay78392/
	$new_params = array ();
	foreach ( $params as $pval )
	{
		$new_params[] = preg_replace ( '#[^-_A-Za-z0-9]#i', '', $pval ); // only letters, numbers and the underscore and dash
	}
	
	// call the appropriate controller action
	call_user_func_array ( $action, $new_params );
}

//This function parses the HTTP request to get the controller, action and parameter parts.
function requestParser(&$controller,&$action,&$params)
{
  	$requri=preg_replace('#^'.addslashes(WEB_FOLDER).'#', '', $_SERVER['REQUEST_URI']);

  	list ( $requri, $query_string ) = explode ( '?', $requri );

	$pieces = array (); // default

	if ( $requri <> '' and $requri <> '/' )
	{
		$pieces = explode ( '/', $requri );
		if ( empty ( $pieces[0] ) )
		{
			array_shift ( $pieces ); // toss it
		}
	}

	if ( !empty ( $pieces[0] ) )
	{
		$controller = array_shift ( $pieces );
	}

	if ( !empty ( $pieces[0] ) ) // unshifted, still on 0...
	{
		$action = array_shift ( $pieces );
	}

	define ( 'CONTROLLER', $controller );
	define ( 'OCTION', $action );
	$action = str_replace ( '-', '_', $action );
	define ( 'ACTION', $action );

	$params = $pieces; // if there is anything left or not ...
}

class crude
{

	var $conf 	  = array();
	var $rs 	  = array();
	var $order_by = null; 
	var $limit    = null; 

	function crude ( $pkname='', $tablename='' )
	{
		$this->conf['pkname']	= $pkname;    //Name of auto-incremented Primary Key
		$this->conf['tablename']= $tablename; //Corresponding table in database
	}

	function order_by ()
	{
		if ( $this->order_by <> null )
		{
			return " order by {$this->order_by} ";	
		} else {
			return '';
		}
	}

	function limit ()
	{
		if ( $this->limit <> null )
		{
			if ( is_array ( $this->limit ) )
			{
				list ( $from, $to ) = $this->limit;
				if ( empty ( $from ) or $from < 0 )
				{
					$from = 0;
				} 

				if ( empty ( $to ) or $to < 0 )
				{
					$to = 1;
				} 

				return " limit {$from}, {$to} ";	
			} else {
				
				if ( $this->limit < 0 )
				{
					$limit = 1;
				} else {
					$limit = $this->limit;
				}

				return ' limit ' . $limit ;	
			}

		} else {
			return '';
		}
	}

	function pkname()
	{
		return $this->conf['pkname'];
	}

	function pkvalue()
	{
		return $this->rs[$this->conf['pkname']];
	}

	function tablename()
	{
		return $this->conf['tablename'];
	}

	function set ( $key, $val )
	{
		$this->rs[$key] = $val;
	}

	function get ( $key )
	{
		return $this->rs[$key];
	}

  	//Inserts record into database with a new auto-incremented primary key
  	//Assumes primary key is set to auto increment
	// to check if exists, set $only_if_not_exists to the field to check ... the NAME of the field, not the value
  	function create( $only_if_not_exists=null )
	{
		$columns = $values = array ();

		$table   = $this->tablename();
		$pkname  = $this->pkname();

		$this->set($pkname,'');

		if ( $only_if_not_exists )
		{
			$sql = "select {$only_if_not_exists} from `{$table}` where `{$only_if_not_exists}` = ?";
			$r   = dbq ( $sql, 'read', array ( $only_if_not_exists => $this->rs[$only_if_not_exists] ) , __FILE__, __LINE__, __FUNCTION__, null );
			$n = mysql_num_rows ( $r );

			if ( $n > 0 )
			{
				throw new Exception ( "{$only_if_not_exists} exists" );
				return ;
			}
			
		}

		foreach ( $this->rs as $k => $v )
		{
			if ( $k == $pkname )
			{
				continue;
			}

			if ( is_scalar($v) )
			{
				$columns[] = "`{$k}` = ?";
				$values[]  = $v;
			}
		}

		$columns = implode ( ', ', $columns ); 

    	$sql = "INSERT INTO `{$table}` SET {$columns}";
		$r   = dbq ( $sql, 'write', $values, __FILE__, __LINE__, __FUNCTION__, null );
		
		if ( !$r )
		{
			return false;
		}

		$this->set($pkname,mysql_insert_id());
		return true;
  	}

	function load ( $id )
	{
		$this->retrieve ( $id );
	}

  	function retrieve ( $pkvalue, $select='t.*' )
	{
		$table   = $this->tablename();
		$pkname  = $this->pkname();

		$order_by= $this->order_by ();
		$limit   = $this->limit ();

		$sql 	= "SELECT {$select} FROM `{$table}` as t WHERE `{$pkname}` = ? {$order_by} {$limit}";
		$r 		= dbq ( $sql, 'read', $pkvalue, __FILE__, __LINE__, __FUNCTION__, null );
			
		if ( !$r )
		{
			return false;
		}

		$rs 	= mysql_fetch_array ( $r, MYSQL_ASSOC ); 

		if ( !is_array ( $rs ) or sizeof ( $rs ) == 0 )
		{
			return false;
		}

		foreach ( $rs as $k => $v )
		{
			$this->set($k,$v);
		}

		return true;
  	}
	
	function save ()
	{
		$this->update ();
	}


  	function update()
	{
		$table   = $this->tablename();
		$pkname  = $this->pkname();

		$columns = $values = array ();

		foreach ( $this->rs as $k => $v )
		{
			if ( $pkname == $k ) 
			{
				continue;
			}

			if ( is_scalar($v) )
			{
				$columns[] = "`{$k}` = ?";
				$values[]  = $v;
			}
		}

		$values[]= $this->pkvalue();
		$columns = implode ( ', ', $columns ); 

    	$sql = "UPDATE `{$table}` SET {$columns} WHERE `{$pkname}` = ?";
		return dbq ( $sql, 'write', $values, __FILE__, __LINE__, __FUNCTION__, null );
  	}

  	function delete()
	{
		$sql = 'DELETE FROM `'.$this->tablename().'` WHERE `' . $this->pkname() . '` = ? ';
		$r   = dbq ( $sql, 'write', $this->pkvalue(), __FILE__, __LINE__, __FUNCTION__, null );
		return ( !$r ) ? false : true; //mysql_affected_rows ( $r );
  	}

  	function exists()
	{
		if (!$this->rs[$this->pkname()])
		{
			return false;
		}

		$sql = 'SELECT 1 FROM `'.$this->tablename().'` WHERE `'.$this->pkname().'`= ?';
		$r   = dbq ( $sql, 'read', $this->pkvalue(), __FILE__, __LINE__, __FUNCTION__, null );
		return ( !$r ) ? false : mysql_num_rows ( $r );

  	}


	/* for use with compleX queries where I don't worry so much about the current table */
  	function write_x ( $sql, $values )
	{
		return dbq ( $sql, 'write', $values, __FILE__, __LINE__, __FUNCTION__, null );
	}

  	function read_x ( $sql, $values )
	{
		$r 		= dbq ( $sql, 'read', $values, __FILE__, __LINE__, __FUNCTION__, null );
			
		if ( !$r )
		{
			return false;
		}

		$ret = array ();
		while ( $rs = mysql_fetch_array ( $r, MYSQL_ASSOC ) ) 
		{
			$ret[] = $rs;
		}


		return $ret;
	}
}
