<?php

// Note apc.enable_cli must be enabled in PHP_INI_SYSTEM for the cache tests to work
require_once 'PHPUnit/Framework.php';
require_once '../library/functions.inc.php';

class MvchaFunctionsTest extends PHPUnit_Framework_TestCase
{

	public function testExtractEmailValidEmail()
	{
		$string = "my email is email@example.com and don't you forget it!";
		$e = extract_email ( $string );
		$this->assertEquals('email@example.com', $e );
	}

	public function testExtractEmailInvalidEmail()
	{
		$string = 'my email is emaiis!l%@e.m and don\'t you forget it!';
		$e = extract_email ( $string );
		$this->assertFalse($e);
	}

	// sort_by_sort_order functions () ##############################################################################
	/**
	* @dataProvider provider_sort_by_sort_order
	*/
	public function testSortBySortOrder ( $data )
	{
		
		// before sorting, should be same as we passed in (from provider)	
		$this->assertEquals(4, $data[0]['so']);
		$this->assertEquals(13, $data[(sizeof($data)-1)]['so']);

		usort ( $data, 'sort_by_sort_order' );
		
		// sorting done, should be what you see now	
		$this->assertEquals('1', $data[0]['so']);
		$this->assertEquals('44', $data[(sizeof($data)-1)]['so']);
	}

	/**
	* @dataProvider provider_sort_by_sort_order
	*/
	public function testSortBySortOrderDesc ( $data )
	{
		
		// before sorting, should be same as we passed in (from provider)	
		$this->assertEquals(4, $data[0]['so']);
		$this->assertEquals(13, $data[(sizeof($data)-1)]['so']);

		usort ( $data, 'sort_by_sort_order_desc' );
		
		// sorting done, should be what you see now	
		$this->assertEquals(44, $data[0]['so']);
		$this->assertEquals(1, $data[(sizeof($data)-1)]['so']);
	}

	public function provider_sort_by_sort_order()
	{
		$data   = array ();
		$data[] = array ( 'so' => '4' );
		$data[] = array ( 'so' => '1' );
		$data[] = array ( 'so' => '4' );
		$data[] = array ( 'so' => '44' );
		$data[] = array ( 'so' => '22' );
		$data[] = array ( 'so' => '13' );
 
		return array ( array ( $data ) ); // sic, provider method is a little weird with arrays...
	}


		
	// cache functions  ##############################################################################
	/**
	* @dataProvider provider_cache
	*/
	public function testCacheSet ( $key, $value )
	{
		$cache_ret = cache_set($key,$value);
		$this->assertTrue($cache_ret);
	}
	
	/**
	* @dataProvider provider_cache
	*/
	public function testCacheGet ( $key, $value )
	{
		$this->assertEquals(date('mdy'), cache_get($key));
	}

	public function provider_cache ()
	{
		$data   = array ();
		$data[] = 'phpunit_test_cache'; // key
		$data[] = date('mdy' ); // value; this is changes and won't be a problem if this isn't started at midnight... even then we might be fine
		return array ( $data );
	}

	// goto() #############################################################################
	public function testGoto ()
	{

		$a = time ();

		// goto() is usually used in MVC and will end with a view being displayed... 
		// so we need to echo (there is no return) and capture it with PHP's ob() functions
		function do_goto ($b)
		{
			echo $b; 
		}

		ob_start();
		goto ( 'do_goto', $a);
		$ob = ob_get_clean ();

		$this->assertEquals($a, $ob);
	}

	public function testGotoButGotoNotDefined ()
	{
		$a = array ();
		try {	
			goto ( 'do_goto_not_exists', $a);
		}
		catch (Exception $e) {
			$this->assertEquals('nowhere to goto() in functions.inc.php', $e->getMessage());
		}

	}

	// class Crypto  ##############################################################################
	public function testCryptoSuccess ()
	{
		$string = 'MVCHA_FUNCTION Test example';
		
		$crypto	= new crypto;
		$cryptd = $crypto->hide ( $string );
		$dcrypt = $crypto->unhide ( $cryptd );

		$this->assertEquals ( $dcrypt, $string );

	}

	public function testCryptoFail ()
	{
		$string = 'MVCHA_FUNCTION Test example';
		$string2= '2 MVCHA_FUNCTION Test example';
		
		$crypto	= new crypto;
		$cryptd = $crypto->hide ( $string );
		$dcrypt = $crypto->unhide ( $cryptd );

		$this->assertNotEquals ( $dcrypt, $string2 );

	}

	public function testCryptoFailBadChars ()
	{
		$string = 'MVCHA_FUNCTION Test ~`!@#$%^&*()-_+= example';
		
		$crypto	= new crypto;
		$cryptd = $crypto->hide ( $string );
		$dcrypt = $crypto->unhide ( $cryptd );

		$this->assertNotEquals ( $dcrypt, $string );

	}
	
	public function testCryptoSuccessFeedIterationsAndPassword ()
	{
		$string = 'MVCHA_FUNCTION Test example';
		
		$crypto	= new crypto( 10, '#LK#(LLsiseli' );
		$cryptd = $crypto->hide ( $string );
		$dcrypt = $crypto->unhide ( $cryptd );

		$this->assertEquals ( $dcrypt, $string );

	}


	// format_query () ##############################################################################
	public function testFormatQuerySimpleAsScalar ()
	{
		$q = "select * from table where column = ?";
		$a = 'test';

		$q = format_query ( $q, $a );
		$this->assertEquals ( $q, "select * from table where column = 'test'" );
	}

	public function testFormatQuerySimpleAsArray ()
	{
		$q = "select * from table where column = ?";
		$a = array ( 'test' );

		$q = format_query ( $q, $a );
		$this->assertEquals ( $q, "select * from table where column = 'test'" );
	}

	public function testFormatQueryLittleMoreComplex ()
	{
		$q = "select * from table where column1 = ? and column2 = ?";
		$a = array ( 'test1', 'test2' );

		$q = format_query ( $q, $a );
		$this->assertEquals ( $q, "select * from table where column1 = 'test1' and column2 = 'test2'" );
	}

	public function testFormatQueryBoundParametersSingleAsScalar ()
	{
		$q = "select * from table where column1 = :column1";
		$a = 'test1';

		$q = format_query ( $q, $a );
		$this->assertEquals ( $q, "select * from table where column1 = 'test1'" );
	}

	public function testFormatQueryBoundParametersSingleAsScalarNoParameterFound ()
	{
		$q = "select * from table where column1 = 'nothing'";
		$a = 'test1';

		try {
			$q = format_query ( $q, $a );
		}
		catch (Exception $e) {
			$this->assertEquals('No :bound parameter found for single parameter', $e->getMessage());
		}
	}

	public function testFormatQueryBoundParametersSingleAsScalarLeftOverParametersFound ()
	{
		$q = "select * from table where column1 = :column1 and column2 = :column2";
		$a = 'test1';

		try {
			$q = format_query ( $q, $a );
		}
		catch (Exception $e) {
			$this->assertEquals('Unknown left over :bound parameters when given single parameter', $e->getMessage());
		}

	}

	public function testFormatQueryBoundParametersSingleAsArray ()
	{
		$q = "select * from table where column1 = :column1";
		$a = array ( 'column1' => 'test1' );

		$q = format_query ( $q, $a );
		$this->assertEquals ( $q, "select * from table where column1 = 'test1'" );
	}

	public function testFormatQueryBoundParametersMultiple ()
	{
		$q = "select * from table where column1 = :column1 and column2 = :column2";
		$a = array ( 'column1' => 'test1', 'column2' => 'test2' );

		$q = format_query ( $q, $a );
		$this->assertEquals ( $q, "select * from table where column1 = 'test1' and column2 = 'test2'" );
	}


	// random_string() ##############################################################################
	public function testRandomStringDefaults ()
	{
		$str5 = random_string (); // default length should be 5
		$this->assertEquals(strlen($str5), 5);
	}

	public function testRandomStringLenSix ()
	{
		$str6 = random_string (6); // pass in a length
		$this->assertEquals(strlen($str6), 6);
	}

	public function testRandomStringLenSixNotShellSafe ()
	{
		$str6 = random_string (6, 0); // pass in a length
		$this->assertEquals(strlen($str6), 6);
	}

	public function testRandomStringShellSafeOurString ()
	{
		$str6 = random_string (6, 1, 'abcdefghi' );
		$this->assertEquals(strlen($str6), 6);
		
		// we should only have abcdefghi; fewer characters means it used characters we didn't feed it
		$str = preg_replace ( '/[^a-i]/', '', $str6 );
		$this->assertEquals(strlen($str), 6);
	}

	public function testRandomStringShellSafeOurStringTooShort ()
	{
		// this should throw a PHP error because there aren't enough characters in the input string to cover the length of the random string
		try {
			$str6 = random_string (6, 1, 'abcde' );
		}
		catch (Exception $e) {
			$this->assertEquals('array_rand(): Second argument has to be between 1 and the number of elements in the array', $e->getMessage());
		}

	}

	public function testRandomStringCheckShellSafe ()
	{
		$str1 = random_string (); // shouldn't have anything but numbers & letters
		$str2 = preg_replace ( '/[^a-zA-Z0-9]/', '', $str1 );
		$this->assertEquals(strlen($str1), strlen($str2));
	}





	public function testRegistryValid ()
	{
		$a = time ();
		registry ( 'reg_test', $a );
		$b = registry ( 'reg_test' );

		$this->assertEquals ( $a, $b );
	}

	public function testRegistryUnsetReturnsNull ()
	{
		$a = registry ( 'return_null_test' ); // isn't set
		$this->assertEquals ( null, $a );
	}

	public function testEscAkaEscapeAkaHtmlentities ()
	{
		$str = 'This is a & long * "string" = <with></with> -_() weird characters';
		$esc = esc ( $str ); // esc() is just a shorter way to write htmlentities(); should be identical
		$htm = htmlentities ( $str );
		
		$this->assertEquals($esc, $htm);

	}

	public function testEncAkaEncodeAkaUrlencode ()
	{
		$str = "This is a & long * string = with -_() weird characters";
		$enc = enc ( $str ); // enc() is just a shorter way to write urlencode(); should be identical
		$url = urlencode ( $str );
		
		$this->assertEquals($enc, $url);
	}

}



/*

remove all ereg in functions as we go!!!!!!!!!!!!!!!!

function redirect ( $url=null )
function valid8 ( $rules, $to_be_validated=null )
function valid8_in( $variable, $rule, $vname )
function xss_clean ( $data, $options=null )
function dbq ( $query, $type='read', $vars=null, $script=null, $line=null, $func=null, $options=null )
function dead ( $where, $what, $args )
function php_error_and_debugging($error, $error_string, $filename, $line, $symbols, $fatal=0)
function php_exception ( $e )
function logNdie ( $log_msg, $display_msg='' )
function view_by_inc ($filename,&$vars=null) {
function view ( $view, $vars=null )
function get_view ( $view )
function get_dbh( $access='read' )
function script ( $scripts, $nocache=false )
function style ( $styles, $nocache=false )
function return_as_is ( $e )
function seems_like_a_filename ( $f )
function get_file_type ( $filename )
function format_date ( $d, $m, $Y, $type='mysql' )
function log_query ( $query, $file, $line, $type )
function elog ( $msg )

*/

/*
class StackTest extends PHPUnit_Framework_TestCase
{

	public function testExtractEmailValidEmail()
	{
		$string = "my email is email@example.com and don't you forget it!";
		$this->assertEquals('email@example.com', extract_email ( $string ));
	}

	public function testExtractEmailInvalidEmail()
	{
		$string = "my email is email@e.m and don't you forget it!";
		$this->assertNotEquals('email@e.m', extract_email ( $string ));
	}

	public function testPushAndPop()
	{
		$stack = array();
		$this->assertEquals(0, count($stack));

		array_push($stack, 'foo');
		$this->assertEquals('foo', $stack[count($stack)-1]);
		$this->assertEquals(1, count($stack));

		$this->assertEquals('foo', array_pop($stack));
		$this->assertEquals(0, count($stack));
	}
}
*/

#class DataTest extends PHPUnit_Framework_TestCase
#{
	/**
	* @dataProvider provider
	*/
#	public function testAdd($a, $b, $c)
#	{
#		$this->assertEquals($c, $a + $b);
#	}

#	public function provider()
#	{
#		return array(
#			array(0, 0, 0),
#			array(0, 1, 1),
#			array(1, 0, 1),
#			array(1, 1, 2)
#		);
#	}
#}

