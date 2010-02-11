<?php

/**
 * Some functions best used with the mvcha.php system, boss
 * @author <me@ha17.com>
 */



 /**
 * Class encrypts and decrypts strings.  Mostly used to "hide" url strings we don't want people messing with.
 * Works fine, but isn't NSA grade security, obviously.
 *
 * @package main_functions
 * @author Hans Anderson <me@ha17.com>
 * @return string
 */

class crypto
{
	
	var $password = 'al.W*j4R#nfa';
	var $ralphabet= "\"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890 |~'!@#$%^&*()_+-=\{}[]:;<,>.?/";
	var $iterations	= 7;
	/**
	 * Constructor
	 */

	public function crypto($iterations=7,$pwd=null)
	{
		if ( !empty ( $pwd ) )
		{
			$this->password  = $pwd;
		}

		if ( !empty ( $iterations ) )
		{
			$this->iterations = $iterations;
		}

		$this->alphabet  = $this->ralphabet . $this->ralphabet . $this->ralphabet;
	}

     /**
	 * Encrypts the string.  Uses a cool and useful routine that much, much better than some simple replacement
	 * scheme, and it can be reversed with decrypt(), but it's not going to be put on the list of unexportable munitions.
	 *
	 * @return string
	 * @param string $strtoencrypt String you want to encrypt.
	 */
	function encrypt ( $strtoencrypt )
	{

		$strtoencrypt = str_replace("\t","[tab]",$strtoencrypt);
		$strtoencrypt = str_replace("\n","[new]",$strtoencrypt);
		$strtoencrypt = str_replace("\r","[ret]",$strtoencrypt);

		for( $i=0; $i<strlen($this->password); $i++ )
		{
			$cur_pswd_ltr		= substr($this->password,$i,1);
			$pos_alpha_ary[]	= substr(strstr($this->alphabet,$cur_pswd_ltr),
								0,strlen($this->ralphabet));
		}

		$i	= 0;
		$n	= 0;
		$nn	= strlen($this->password);
		$c	= strlen($strtoencrypt);

		$encrypted_string = '';
		while ( $i < $c )
		{
			$encrypted_string .= substr($pos_alpha_ary[$n],strpos($this->ralphabet,substr($strtoencrypt,$i,1)),1);
 			$n++;
			if ( $n==$nn ) $n = 0;
			$i++;
		}

		return $encrypted_string;

	}


     /**
	 * Decrypts string, basically the reverse of encrypt
	 *
	 * @return string
	 * @param string $strtodecrypt Encrypted string to decrypt
	 */
	public function decrypt ( $strtodecrypt )
	{

		$pos_alpha_ary = array();
		for( $i=0; $i<strlen($this->password); $i++ )
		{
			$cur_pswd_ltr = substr($this->password,$i,1);
			$pos_alpha_ary[] = substr(strstr($this->alphabet,$cur_pswd_ltr),0,strlen($this->ralphabet));
		}

		$i	= 0;
		$n	= 0;
		$nn	= strlen($this->password);
		$c	= strlen($strtodecrypt);

		$decrypted_string = '';
		while ( $i < $c )
		{
			$decrypted_string .= substr($this->ralphabet,strpos($pos_alpha_ary[$n],substr($strtodecrypt,$i,1)),1);
 			$n++;
			if($n==$nn) $n = 0;
			$i++;
		}

		$decrypted_string = str_replace("[tab]","\t", $decrypted_string);
		$decrypted_string = str_replace("[new]","\n", $decrypted_string);
		$decrypted_string = str_replace("[ret]","\r", $decrypted_string);

		return $decrypted_string;
	}

	/**
	 * Hides a string based on a basic, home-cooked algorithm.  Hide URLs
	 * and hidden fields for basic snoop protection.
	 *
	 * @param string $string
	 * @param bool $urlencode
	 */

	public function hide ( $string )
	{

		for ( $i=0; $i<$this->iterations; $i++ )
		{
			$string = $this->encrypt ( $string );
		}
		
		$string 	= base64_encode ( $string );
		$len_before = strlen ( $string );
		$string 	= str_replace ( '=', '', $string );
		$len_after 	= strlen ( $string );

		$how_many_equal_signs = $len_before-$len_after; // in base64, there will never be more than two
		$string = $how_many_equal_signs . $string;
		
		return $string;
	}

	/**
	 * UnHides a string hidden with hide().  Todo: auto-text for urldecode (based on %)?
	 *
	 * @see hide()
	 * @param string $string
	 * @param bool $urlencode
	 */

	public function unhide ( $string )
	{

		$len = strlen ( $string );
		$how_many_equal_signs = substr ( $string, 0, 1 );
		
		$string 			  = substr ( $string, 1 );
		$string = str_pad ( $string, strlen($string) + $how_many_equal_signs, '=' );

		$string = base64_decode ( $string );

		for ( $i=0; $i<$this->iterations; $i++ )
		{
			$string = $this->decrypt ( $string );
		}

		return $string;
	}

} // end cl.a.ss Crypto

 
function format_query ( $q, $a )
{
	if ( strpos ( $q,'?' ) === false )
	{
		// :bound paramenters
		if ( is_array ( $a ) ) // this array needs to be associative
		{
			foreach ( $a as $k=>$v )
			{
				// $k is case SenSitIvE
				$q = str_replace ( ":{$k}", "'" . mysql_escape_string ( $v ) . "'", $q );
			}
		} else {
			// if we have a string, find the only bound parameter and just assume that's the one they want to replace.  By happenstance, if it's the same :bound name used more than once, all will be replaced
			preg_match_all( "(:[_a-zA-Z0-9]+)", $q, $matches ); // finds :bound :bou_nd :b0und :BounD etc
			
			if ( sizeof ( $matches[0] ) == 0 ) // must be at least one, or we're trying to add a parameter where no placeholder exists 
			{
				throw new Exception('No :bound parameter found for single parameter');
			}

			if ( sizeof ( $matches[0] ) > 1 ) // should be [0][0] and that's all...
			{
				throw new Exception('Unknown left over :bound parameters when given single parameter');
			}

			$q = str_replace ( "{$matches[0][0]}", "'" . mysql_escape_string ( $a ) . "'", $q );
		}
	} else {

		// the question mark might occur in a replaced variable and if it does, then it gets replaced instead of the bound parameter and it's all messed up
		$placeholder = '--REALLY-HRD-2-Mess-Up-BOUND-PARAMETER-PLACEHOLDER--';
		$q = str_replace ( '?', $placeholder, $q );
		if ( is_array ( $a ) )
		{
			foreach ( $a as $k=>$v )
			{
				$q = preg_replace ( "/{$placeholder}/", "'" . mysql_escape_string ( $v ) . "'", $q, 1 );
			}
		} else {
			$q = preg_replace ( "/{$placeholder}/", "'" . mysql_escape_string ( $a ) . "'", $q );
		}

	}

	return $q;
}

function extract_email ( $string )
{
	$matches = array ();
	eregi ( '[-+\"\'_.0-9a-z]+@([-_0-9a-z]+\.){1,5}[a-z]{1,3}', $string, $matches );

	if ( !isset ( $matches[0] ) )
	{
		return FALSE;
	} else {
		return $matches[0];
	}
}

/**
 * Do I really need to explain this one?
 */
function random_string ( $length=5, $shell_safe=1, $a='' )
{

	if ( empty ( $a ) )
	{
		if ( $shell_safe == 0 )
		{
				/* this gives us 95 ascii characters, starting
						at #33 and ending at #126 */
			$a = range ( '!', '~' );
		} else {
				/* only alphanumeric */
			$a = array_merge ( range ( '0' , '9'), range ( 'A', 'Z' ) , range ( 'a', 'z' ) );
		}

	} else {

		$aa = $a;
		$a  = array ();

		for($i=0; $i<strlen($aa); $i++ )
		{
			$a[] = $aa[$i];
		}

	}

	shuffle ( $a );
	srand ((float) microtime() * 10000000);
	$rand_keys = array_rand ($a, $length);

	$p = array ();
	foreach ( $rand_keys as $k=>$v )
	{
		$p[] = $a[$v];
	}

	return implode ( '', $p );

}


function redirect ( $url=null )
{
	$scheme = !empty ( $_SERVER['HTTPS'] ) ? 'https' : 'http';
	$url = $scheme . '://' . $_SERVER['HTTP_HOST'] . $url; 
	header ( "Location: {$url}", true, 303 );
	return 1;
}


/**
 * Kind of an internal redirect, probably not 100% useful
 * 
 */
/* now a php 5.3 keyword ...
function goto ()
{
	$args 		= func_get_args ();
	$function   = array_shift ( $args );

	if ( empty ( $function ) or !function_exists ( $function ) )
	{
		throw new Exception ( 'nowhere to goto() in ' . basename ( __FILE__ ) );
	}

	call_user_func_array ( $function, $args );
}
 */

function registry ( $name, $value=null )
{
	static $registry = array();

	if ( isset ( $registry[$name] ) and $value == null )
	{
		return $registry[$name];

	} elseif ( !isset ( $registry[$name] ) and $value == null ) {
		return null;
	
	} else {
		$registry[$name] = $value;
		return true;
	
	}

	return FALSE;
}


// shorter to write esc than htmlentities
function esc ( $var )
{
	return htmlentities ( $var );
}

// shorter to write enc than urlencode 
function enc ( $var )
{
	return urlencode ( $var );
}

/**
 * Validates an array of rules against an array of variables (as from $_POST or $_GET)
 * 
 * - use compact() to create array of variables if using scalars currently in scope
 * - $to_be_validated defaults to $_POST
 *
 * @author Hans Anderson <me@ha17.com>
 *
 * @usage:
 *
 *	list ( $v8_pass, $v8 )  = valid8 ( array ( 'date_start::date_yyyy_mm_dd', 'date_end::date_yyyy_mm_dd' ), compact ( 'date_start', 'date_end' ) );
 *  list ( $v8_pass, $v8 )  = valid8 ( array ( 'email::email::strlen=6', 'pwd::only_safe::strlen=6') ); // defaults to checking $_POST
 *
 *  if ( !$v8_pass ) // it did not pass
 *  {
 *      // return or display an error
 *  } 
 *
 *  // now you can use the validated items in $v8: echo $v8['email']; echo $v8['date_start']; // etc
 *
 */

function valid8 ( $rules, $to_be_validated=null )
{
	if ( !$to_be_validated )
	{
		$to_be_validated = $_POST;
	}

	if ( !is_array ( $rules ) )
	{
		$rules = array ( $rules );
	}

	$validated = $validation_errors = array ();
	foreach ( $rules as $rule)
	{
		$rules_array = explode ( '::', $rule );
		$vname 		 = array_shift ( $rules_array );
		$vval 		 = $to_be_validated[$vname]; 
		$valid8_types= $rules_array; unset ( $rules_array );

		// pre-check for unhide & match
		foreach ( $valid8_types as $vtype )
		{
			if ( $vtype == 'unhide' )
			{
				$vval = unhide ( $vval );
			} elseif ( ereg ( '^match=', $vtype ) ) {
				list ( , $to_be_matched ) = explode ( '=', $vtype );
				$match_value 			  = $to_be_validated[$to_be_matched];
				registry ( 'match_'.$to_be_matched, $match_value );
			}
		}

		foreach ( $valid8_types as $vtype )
		{
			list ( $valid, $errors ) = valid8_in( $vval, $vtype, $vname );
				
			if ( !$valid )
			{
				$validation_errors[$vname] = $errors;
			} else {
				$validated[$vname] = trim ( $vval );
			}
		}
	}

	// out of all of them, something was invalid
	if ( sizeof ( $validation_errors ) > 0 )
	{
	    return array ( false, $validation_errors );
	} else {
		return array ( true, $validated );
	}

}


/**
 * Validate form data;
 *
 * @return array Int, Errors array
 *
 */
function valid8_in( $variable, $rule, $vname )
{
	$error = array ();
	// for things like mustbe & match
	if ( strpos ( $rule, '=' ) !== false )
	{
		list ( $k, $l ) = explode ( '=', $rule, 2 );
		$rule      = $k;
		$rule_info = $l;
	} else {
		$rule_info = '';
	}

   	switch ( $rule )
	{
		case 'url':
			if ( strlen ( $variable ) > 0 )
			{
				if ( !ereg ( "^https?://.+", $variable ) )
				{
					$error['url'] = "Input for $vname (" . htmlentities ( $variable ) . ") is not a URL";
				}
			}
		break;

   		case 'mustbe':
			$mustbe = explode ( '|', $rule_info );

			if ( !in_array ( $variable, $mustbe ) )
			{
				$error[$rule] = "{$vname} is in violation of specific input requirements";
			}
		break;	

   		case 'is_array':
			if ( !empty( $variable ) )
			{
				if ( !is_array ( $variable ) )
				{
					$error[$rule] = 'The input type should be array, not scalar' . $variable;
				}
			}
		break;

		case 'strlen':
			if ( strpos ( $rule_info, '|' ) === false )  // no delimiter, default to 'min'
			{
				$strlen_min = $rule_info;
				$strlen_max = '';
			} else {
				list ( $strlen_min, $strlen_max ) = explode ( '|', $rule_info );
			}

			$real_len = strlen ( $variable );

			if ( '' == $strlen_min and '' == $strlen_max ) // poorly set up, we'll return false so admin must fix 
			{
				$error[$rule] = "STRLEN rule incorrectly set up for {$vname}";

			} elseif ( '' == $strlen_min and '' <> $strlen_max ) { // basically, "string must be less than #"
				if ( $real_len > $strlen_max )
				{
					$error[$rule] = "{$vname} was not the correct length (too long)";
				}
				// else we've passed rule

			} elseif ( '' <> $strlen_min and '' == $strlen_max ) { // basically, "string must be greater than #"
				if ( $real_len < $strlen_min ) 
				{
					$error[$rule] = "{$vname} was not the correct length (too short)";
				}
				// else we've passed rule

			} elseif ( '' <> $strlen_min and '' <> $strlen_max ) { // basically, "string must be between # and #"
				if ( $real_len < $strlen_min or $real_len > $strlen_max ) 
				{
					$error[$rule] = "{$vname} was not the correct length (outside given range)";
				}
				// else we've passed rule

			} else {
				// default
				$error[$rule] = "{$vname} was not the correct length";
			}
		break;	

		case 'selected':
			if ( !$variable )
			{
				$error[$rule] = "Please check the {$vname} selection to continue.";
			}
		break;

		case 'not_empty':
			if ( empty ( $variable ) )
			{
				$error[$rule] = "Invalid input in " . strtoupper ( $vname ) . " (empty)";
			}
		break;

		case 'only_alpha':
			if ( eregi ( '[^ _A-Z]', $variable ) ) // more than just digits exist
			{
				$error[$rule] = "{$variable} can only be letters";
			}
		break;

		case 'only_variable_rules':
			if ( !ereg ( '^[_a-zA-Z][_a-zA-Z0-9]*', $variable ) )
			{
				$error[$rule] = 'Illegal characters found in ' . htmlentities ( $variable );
			}
		break;

   		case 'only_digits':
			if ( ereg ( '[^-.0-9]', $variable ) ) // more than just digits exist
			{
				$error[$rule] = 'Input ' . htmlentities ( $variable ) . ' can only contain digits.';
			}
		break;

   		case 'valid_mysql_id':
			if ( ereg ( '[^0-9]', $variable ) ) // more than just digits exist
			{
				$error[$rule] = 'Input value ' . htmlentities ( $variable ) . ' improper value';
			}
		break;

		case 'no_digits':
			if ( ereg ( '[^0-9]', $variable ) ) // more than just digits exist
			{
				$error[$rule] = 'Numbers are not allowed in input '  . htmlentities ( $variable );
			}
		break;

		case 'date_yyyy_mm_dd':
			if ( !ereg ( '^[012][0-9][0-9][0-9][-/. ][01][0-9][-/. ][0-3][0-9]$', $variable ) ) // not correct date format
			{
				$error[$rule] = "Date {$variable} not in correct format YYYY-MM-DD";
			}
		break;

		case 'date_mm_dd_yyyy':
			if ( !ereg ( '^[01][0-9][-/. ][0-3][0-9][-/. ][012][0-9][0-9][0-9]$', $variable, $regs ) ) // not correct date format
			{
				$error[$rule] = 'Date ' . htmlentities ( $variable ) . ' not in correct format MM/DD/YYYY';
			}
		break;

		case 'date_mm_dd_yy':
			if ( !ereg ( '^[01][0-9][-/. ][0-3][0-9][-/. ][0-9][0-9]$', $variable ) ) // not correct date format
			{
				$error[$rule] = 'Date ' . htmlentities ( $variable ) . ' not in correct format MM/DD/YY';
			}
		break;

		case 'email':
			if ( !eregi ( '^[\'-_.0-9a-z]+@([-_0-9a-z]+\.){1,5}[a-z]{1,3}$', $variable ) )
			{
				$error[$rule] =  "{$variable} is not a valid email";
			}
		break;

		// this is for passwords and things that need to match other things
		case 'match':
			$match_value = registry ( 'match_'.$rule['match'] );
			if ( $variable <> $match_value )
			{
				$error[$rule] =  strtoupper ( $vname ) . " did not match " . strtoupper ( $rule['match'] );
			}
		break;

		case 'month':
			if ( !ereg ( "[01]?[0-9]", $variable ) )
			{
				$error[$rule] =  htmlentities ( $variable ) . ' is not a month';
			}
		break;

		case 'yyyy':
			if ( !ereg ( "[012][0-9][0-9][0-9]", $variable ) )
			{
				$error[$rule] =  "{$variable} is not a year (YYYY)";
			}
		break;

		case 'yy':
			if ( !ereg ( "[0-9]{2,2}", $variable ) )
			{
				$error[$rule] =  "{$variable} is not a year (YY)";
			}
		break;	

		case 'hour':
			if ( !ereg ( "[012][0-9]", $variable ) )
			{
				$error[$rule] =  "{$variable} is not an hour";
			}
		break;

		case 'minute':
			if ( !ereg ( "[0-6][0-9]", $variable ) )
			{
				$error[$rule] =  "{$variable} is not a minute";
			}
		break;

		case 'allow_these':
			$regex = "[" . $rule['allow_these'] . "]*";
			$disallowed_characters = ereg_replace ( $regex, '', $variable );
			if ( strlen ( $disallowed_characters ) > 0 )
			{
				$error[$rule] = "Disallowed characters found in input: $disallowed_characters ("  . htmlentities ( $variable ) . ")";
			}
		break;

		case 'disallow_these':
			$disallow = array();
			if ( eregi ( $rule['disallow_these'], $variable, $disallow ) )
			{
				$error[$rule] = "Disallowed characters found in input: $disallow[1] ("  . htmlentities ( $variable ) . ")";
			}
		break;
	}

    if ( sizeof ( $error ) == 0 )
    {
		return array ( 1, array () );
	} else {
		return array ( 0, $error[$rule] );
	}
}

function xss_clean ( $data, $options=null )
{

	if ( !is_array ( $options ) and !empty ( $options ) )
	{
		$options = explode ( ',', $options );
	}

	if (is_array($data))
	{
		foreach ($data as $key => $val)
		{
			$data[$key] = xss_clean( $val, $options );
		}

		return $data;
	}

	// Do not clean empty strings
	if (trim($data) === '')
	{
		return $data;
	}
		
	// http://svn.bitflux.ch/repos/public/popoon/trunk/classes/externalinput.php
	// +----------------------------------------------------------------------+
	// | Copyright (c) 2001-2006 Bitflux GmbH                                 |
	// +----------------------------------------------------------------------+
	// | Licensed under the Apache License, Version 2.0 (the "License");      |
	// | you may not use this file except in compliance with the License.     |
	// | You may obtain a copy of the License at                              |
	// | http://www.apache.org/licenses/LICENSE-2.0                           |
	// | Unless required by applicable law or agreed to in writing, software  |
	// | distributed under the License is distributed on an "AS IS" BASIS,    |
	// | WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or      |
	// | implied. See the License for the specific language governing         |
	// | permissions and limitations under the License.                       |
	// +----------------------------------------------------------------------+
	// | Author: Christian Stocker <chregu@bitflux.ch>                        |
	// +----------------------------------------------------------------------+
	//
	// Kohana Modifications:
	// * Changed double quotes to single quotes, changed indenting and spacing
	// * Removed magic_quotes stuff
	// * Increased regex readability:
	//   * Used delimeters that aren't found in the pattern
	//   * Removed all unneeded escapes
	//   * Deleted U modifiers and swapped greediness where needed
	// * Increased regex speed:
	//   * Made capturing parentheses non-capturing where possible
	//   * Removed parentheses where possible
	//   * Split up alternation alternatives
	//   * Made some quantifiers possessive

	// Fix &entity\n;
	$data = str_replace(array('&amp;','&lt;','&gt;'), array('&amp;amp;','&amp;lt;','&amp;gt;'), $data);
	$data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
	$data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
	$data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');

	// Remove any attribute starting with "on" or xmlns
	$data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);

	// Remove javascript: and vbscript: protocols
	$data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
	$data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
	$data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);

	// Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
	$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
	$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
	$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);

	// Remove namespaced elements (we do not need them)
	$data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);
	//$data = strip_tags ( $data, 'br p a img b' ); // we are STRICT!!!
	$data = strip_tags ( $data ); // we are STRICT!!!
		
	do
	{
		// Remove really unwanted tags
		$old_data = $data;
		$data = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data);
	}
	while ($old_data !== $data);
	return $data;
}


function dbq ( $query, $type='read', $vars=null, $script=null, $line=null, $func=null, $options=null )
{

	if ( empty ( $query ) )
	{ 
		$args = array ( 'line' => $line, 'script' => $script, 'func' => $func );
		log_query ( "db.noquery: $query", $script, $line, $type );
		dead ( 'db.noquery', null, $args );
	}

	if ( !$dbh = get_dbh( $type ) )
	{ 
		$args = array ( 'line' => $line, 'script' => $script, 'func' => $func );
		log_query ( "db.noconn: $query", $script, $line, $type );
		dead ( 'db.noconn', null, $args );
	}

	if ( ( is_array ( $vars ) and sizeof ( $vars ) > 0 ) or ( is_scalar ( $vars ) and !empty ( $vars ) ) )
	{
		try 
		{
			$query = format_query ( $query, $vars );
		} catch (Exception $e) {
			$args = array ( 'line' => $line, 'script' => $script, 'func' => $func );
			$args['exception']  = $e->getMessage();
			log_query ( "db.format_query: $query", $script, $line, $type );
			dead ( 'db.format_query', $e->getMessage(), $args );
		}
	}

	if ( DB_PROFILER ) 
	{
		log_query ( $query, $script, $line, $type );
	}

	$r = mysql_query ( $query, $dbh );

	if ( !$r )
	{
		$args = array ( 'line' => $line, 'script' => $script, 'func' => $func );
		if ( DB_PROFILER ) 
		{
			log_query ( 'MYSQL ERROR: ' . mysql_error(), $script, $line, 'MYSQL_ERROR' );
		}
		dead ( 'db.mysql_error', mysql_error(), $args );
	}

	return $r;
}

function dead ( $where, $what, $args )
{	
	$log   = array ();

	$log[] = "{$where}: {$what}";

	if ( $args['line'] )
	{
		$log[] = "on line {$args['line']}";
	}

	if ( $args['script'] )
	{
		$script = basename ( $args['script'] );
		$log[] = "in script {$script}";
	}

	if ( $args['function'] )
	{
		$log[] = "in function {$args['function']}";
	}

	$log = implode ( ' ', $log );
	error_log ( $log );
	die ( "DEAD: {$where}");


// implement someday
	if ( empty ( $q ) or empty ( $db_host ) )
	{
    	$time_10_min_ago = time () - 600;
		$qq				 = "select value from ssr.variables where vkey = 'email_mysql_no_host' ";
		$r 				 = db_query ( $qq, $conn_db_mstr, __FILE__, __LINE__, FALSE );
		$last_sent_time  = mysql_result ( $r, 0, 'value' );

		if ( $last_sent_time < $time_10_min_ago ) // alert the admin every 10 minutes so he's not bombarded
		{
			$qq = "update ssr.variables set value = '" . time() . "' where vkey = 'email_mysql_no_host'";
			$r = db_query ( $qq, $conn_db_mstr, __FILE__, __LINE__, FALSE );
			email ( ADMIN_EMAIL, 'empty host or query!', "in $script on $line (host: $db_host) (q: $q)" );
		}
	}

	return mysql_query ( $q, $conn ) or mysql_die ( mysql_error(), $q, $line, $script );
}

function php_error_and_debugging($error, $error_string, $filename, $line, $symbols, $fatal=0)
{
   /*
    * No need to panic if this is just an E_NOTICE,
    * but the rest we want.
    *
    */
    
	if ( $error == E_NOTICE or $error == E_WARNING )
    {
        return FALSE;
    }
    
	$get_symbols = array ();
	
	if ( isset ( $_POST ) )
	{
		$get_symbols[] = $_POST;
	}
	
	if ( isset ( $_SESSION ) )
	{
		$get_symbols[] = $_SESSION;
	}
  
  	$sym = ''; 
   	foreach ( $get_symbols as $symbols )
	{ 
		if ( is_array ( $symbols ) )
		{
			foreach ( $symbols as $k=>$v )
			{
				if ( in_array ( $k, $no_symbols ) )
				{
					continue;
				} else {

					if ( sizeof ( $symbols[$k] ) == 0 )
					{
						continue;
					}

					$sym .= " -- $k DUMP -- \n\n";
					$sym .= var_export ( $symbols[$k], TRUE ) . "\n\n";
					$sym .= " --------------------------------------------------- \n";

				}
			}
		}
	}
   
   $now = MYSQL_DATE_TIME;
   $err =<<<EOF

ERROR!
 filename: $filename
     line: $line
    error: $error
  message: $error_string
     date: $now
user-agnt: {$_SERVER['HTTP_USER_AGENT']}
   method: {$_SERVER['REQUEST_METHOD']}
 protocol: {$_SERVER['SERVER_PROTOCOL']}
query str: {$_SERVER['QUERY_STRING']}
     host: {$_SERVER['HTTP_HOST']}
  $sym
EOF;

	error_log( $err );
	
	// if it's one of these, it's a big deal, we've already logged it (above); on dev, let's just show it; on prod, we'll email the admin
	if ( !in_array ( $error, array ( E_NOTICE, E_WARNING, E_STRICT, E_USER_WARNING, E_USER_NOTICE, E_RECOVERABLE_ERROR ) ) )
    {
	    if ( PORD == 'dev' )
	    {
			die ( "<pre>$err</pre>" );

		} else {
	    	mail (ADMIN_EMAIL, "PHP Error and Debugging on " . $_SERVER['HTTP_HOST'], $err, 'From: me@ha17.com' );
		}
	}

	// anything that makes it here is a non-fatal error; it was logged and that's good enough.  The admin should clean out that
	// error log often to make sure that the code isn't littered with E_WARNINGs and E_NOTICEs	

}

function php_exception ( $e )
{
	php_error_and_debugging($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine(), null );
	//php_error($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString, 0);
}



function logNdie ( $log_msg, $display_msg='' )
{
	if ( empty ( $display_msg ) )
	{
		$display_msg = $log_msg;
	}

	error_log ( $log_msg );
	die ( display_msg ( $display_msg ) );
}


//===============================================================
// View
// Various ways to include and return plain PHP templates
//===============================================================
function view_by_inc ($filename,&$vars=null) {
  if (is_array($vars))
    extract($vars);
  ob_start();
  require(APP_PATH.'/views/'.$filename);
  return ob_get_clean();
}


function view ( $view, $vars=null )
{

	if ( is_array ( $vars ) )
	{
		extract ( $vars );
	}

	$tpl = get_view ( $view ) ;
	ob_start();
		eval("?>$tpl");
	return ob_get_clean();
}

function get_view ( $view )
{
	static $registry = array();

	if ( isset ( $registry[$view] ) )
	{
		return $registry[$view];
	} else {
		$file = APP_PATH.'/views/'.$view;
		
		$key  	  = "VIEW: $view";

		// this will only work if we've set up some sort of cache
		if ( function_exists ( 'cache_get' ) )
		{
			$mtimef = filemtime ( $file ); // get the last time the file was modified
			$mtimec = cache_get ( $file ); // use $file as the key name

			if ( $mtimef <> $mtimec )
			{
				$contents = file_get_contents ( $file );
				 cache_set ( $key, $contents );
				 cache_set ( $file, $mtimef );
			} else {
				$contents = cache_get ( $key );
			}

			if ( !empty ( $contents ) )
			{
				return $contents;
			}
		}

		// always need to get the contents if it's not already set
		$contents = file_get_contents ( $file ); // APP_PATH.'/views/'.$view);
	
		// only if cache set up	
		if ( function_exists ( 'cache_set' ) )
		{
			cache_set ( $key, $contents );
		}

		// always do this, no sense in hitting even the cache unnecessarily
		$registry[$view] = $contents;
		
		return $contents;
	}
}

function get_dbh( $access='read' )
{
	// I'm not sure if this is better than mysql_pconnect.  I'm going to assume they are smarter than me in this case
						#static $dbh = array ();
						
						#if ( !empty ( $dbh[$access] ) )
						#{	
						#	return $dbh[$access];
						#} else {
							// do what is below
						#}
				
	$args = array ();
	
	$user = ( $access == 'write' ) ? DB_WRITE_USER : DB_READ_USER;
	$pwd  = ( $access == 'write' ) ? DB_WRITE_PWD  : DB_READ_PWD;

	$dbh = mysql_pconnect ( DB_HOST , $user, $pwd ) or dead ( 'db.no_connect',   mysql_error(), $args );
		   mysql_select_db ( DB_NAME, $dbh )   	    or dead ( 'db.no_select_db', mysql_error(), $args );

	return $dbh;


}

function script ( $scripts, $nocache=false )
{
	$scripts = explode ( ",", $scripts );
	$ret     = array ();

	$nocache = ( $nocache ) ? '?' . time() : '';

	foreach ( $scripts as $script )
	{
		$script = trim ( $script );
		$ret[]  = '<script charset="utf-8" type="text/javascript" src="' . $script . $nocache . '"></script>';
	}

	return implode ( "\n", $ret );
}

function style ( $styles, $nocache=false )
{
	$styles  = explode ( ",", $styles );
	$ret     = array ();

	$nocache = ( $nocache ) ? '?' . time() : '';

	foreach ( $styles as $style )
	{
		$style  = trim ( $style );
		$ret[]  = '<link rel="stylesheet" type="text/css" media="screen" href="' . $style . $nocache . '" />'; 
	}

	return implode ( "\n", $ret );
}


/**
 * Function return_as_is() - give this func a style or script and it will display either the 
 * contents or a link to the file, depending on what it needs 
 *
 * SET IN CONTROLLER like this:
 *
 * $layout_vars['script'][]  		 = '/jquery.js'; 
 * or
 * $layout_vars['script'][]  		 = 'http://www.example.com/js/jquery.js'; 
 *
 * 
 * USE IN VIEW SCRIPT header like this: 
 *		<?php echo isset ( $script ) ? return_as_is ( $script ) : ''; ?>
 *		<?php echo isset ( $style )  ? return_as_is ( $style  ) : ''; ?>
 *
 *
 *
 * 
 * @copyright Hans Anderson Corp
 * @author Hans Anderson <me@ha17.com> 
 * @param mixed $e 
 * @access public
 * @return void
 */
function return_as_is ( $e )
{
	$ret = array ();

	if ( !isset ( $e ) )
	{
		return  '';

	} elseif ( is_array ( $e ) ) {

		foreach ( $e as $f )
		{
			if ( seems_like_a_filename ( $f ) )
			{
				$file_type = get_file_type ( $f );

				if ( 'JS' == $file_type )
				{
					$ret[] = script ( $f ); // js to include from .js file
				} elseif ( 'CSS' == $file_type ) {
					$ret[] = style ( $f ); // css to include from .css file
				}

			} else {

				// just css or js to show on page

				// make it valid <script> tag if it's not already
				$f     = str_replace ( '<script>', '<script charset="utf-8" type="text/javascript">', $f );

				// make it value <style> tag if it's not already
				// find out syntax & enable $f     = str_replace ( '<style>', '<script charset="utf-8" type="text/javascript">', $f );
				$ret[] = $f; 
			}
		}

	} else {
			
		if ( seems_like_a_filename ( $e ) )
		{
			$file_type = get_file_type ( $e );

			if ( 'JS' == $file_type )
			{
				$ret[] = script ( $e ); // js to include from .js file
			} elseif ( 'CSS' == $file_type ) {
				$ret[] = style ( $e ); // css to include from .css file
			}

		} else {
			$ret[] = $e; // just js to show on page
		}

	}

	return implode ( "\n", $ret );

}

/**
 * Function seems_like_a_filename() - Given a string, this tells us whether it's likely a filename 
 * or just a string of text 
 * 
 * @copyright Hans Anderson Corp
 * @author Hans Anderson <me@ha17.com> 
 * @param mixed $f 
 * @access public
 * @return void
 */
function seems_like_a_filename ( $f )
{
	if ( file_exists ( $f ) ) // full path file exists
	{
		return true;

	} elseif ( ereg ( '^/', $f ) ) { // relative file 

		return true;

	} elseif ( ereg ( '^http[s]://', $f ) ) { // external file 

		return true;

	} elseif ( file_exists ( APP_PATH . $f ) ) {
		
		return true;

	} elseif ( file_exists ( APP_PATH . '/' . $f ) ) {
		
		return true;

	// JS
	} elseif ( ereg ( '^<script', trim ( $f ) ) ) {
		
		return false;
	
	// CSS
	} elseif ( ereg ( '^<style', trim ( $f ) ) ) {
		
		return false;
		
	} else {

		return false;

	}
}





function get_file_type ( $filename )
{
	$exts = explode ( '.', basename ( $filename ) ) ;
	$ext  = array_pop ( $exts );

	switch ( $ext )
	{
		case 'js':
			return 'JS';
			break;

		case 'css':
			return 'CSS';
			break;

		default:
			return false;
			break;
	}

	return false;
}



function format_date ( $d, $m, $Y, $type='mysql' )
{
	if ( !checkdate ( $m, $d, $Y ) )
	{
		return 'invalid date';
	}

	switch ( $type )
	{
		case 'mysql':
			return sprintf ( '%04d-%02d-%02d',  $Y, $m, $d );
		break;
	}

}


function log_query ( $query, $file, $line, $type )
{
	static $fp;

	if ( empty ( $fp  ) )
	{
		try {
			$fp = fopen ( QUERY_LOG, 'a' );
		} catch (PDOException $e) {
			die('Opening query log failed '.$e->getMessage());
			// implement emailing/logging etc, show a 404.php style message
		}
	}

	$date = MYSQL_DATE_TIME;
	$file = basename ( $file );
	$query= ereg_replace ( "[\n\t]", ' ', $query );
	fputs ( $fp, "{$date}|{$file}|{$line}|{$type}|{$query}\n-----\n" );

	// no close; leave it open for further writing.  PHP garbage cleanup will close

}


function sort_by_sort_order ( $a, $b )
{
	// so is shorthand for sort_order

	if ( $a['so'] == $b['so'] )
	{
		return 0;
	}

	return ( $a['so'] < $b['so'] ) ? -1 : 1 ;
}

function sort_by_sort_order_desc ( $a, $b ) // reverse sort 
{
	// so is shorthand for sort_order

	if ( $a['so'] == $b['so'] )
	{
		return 0;
	}

	return ( $a['so'] > $b['so'] ) ? -1 : 1 ;
}

function cache_set ( $key, $value )
{
	return apc_store ( $key, $value ); // NOT APC_ADD!!!
}

function cache_get ( $key )
{
	return apc_fetch ( $key );
}

function cache_del ( $key )
{
	return apc_delete ( $key );
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
