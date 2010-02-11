<?php

/**
 *  
 *  Form Helper Functions for MVCHA
 *  
 *  @package main
 *  @since
 *  @todo
 *  @author Hans Anderson <me@ha17.com>
 * 
 */

/**
*  
*  Makes it easier to convert the attribute arrays to proper strings, used in the main form_helper
* 
* @return string
* @param array $attr 
*
* @author Hans Anderson <handerson@executiveboard.com>
*
*/

function attr2str ( $attr )
{
	if ( !is_array ( $attr ) )
	{
		return $attr;
	}


	$a = array ();
	foreach ( $attr as $k => $v )
	{
		$a[] = htmlentities ( $k ) . '="' . htmlspecialchars ( $v ) . '"';
	}

	return implode ( ' ', $a );
}

   /**
	* A better way to output select options in html
	* 
	*
	* @return string
	* @param array $options (keyed array)
	* @param string $selected Which one is selected?
	* @param bool $no_key_htmlentities Don't use htmlentities on the keys
	*
	* @author Hans Anderson <handerson@executiveboard.com>
	*
	*/
function html_options ( $options, $selected, $no_key_htmlentities=FALSE )
{

    $return = array();
	if ( !is_array ( $options ) )
	{
		return '<option></option>';
	}

    foreach ( $options as $k => $v )
    {
		$k = ( $no_key_htmlentities ) ? $k : htmlentities ( $k );
		$v = htmlentities ( $v );

		if ( !is_array ( $selected ) )
		{
			$s = ( $k == $selected ) ? ' selected' : '';
		} else {
			$s = ( in_array ( $k, $selected ) ) ? ' selected' : '';
		}

		$return[] = "<option $s value=\"$k\">$v</option>";
	}

	return implode ( "\n", $return );

}

/**
	*  *
	*  A way to create standardized forms without having to type all of the boxes. I use these in *views*
	*
	*
	* Example:
	* <?php echo form_helper ( 'text', 'TextField1', 'TextField1Value', array ( 'class' => 'classname1 classname2', 'maxlength' => '2' )  ); ?>
	*
	*  *
	*  * @return
	*  * @param string $type Type of form element (text, checkbox, select, etc)
	*  * @param string $name Name of form element (goes into id attr, too)
	*  * @param string $value Default value, if any, of the form element
	*  * @param array $attributes An array of attributes to be added to the form element, such as class or style
	*  * @param array $errors Any errors relating to the element, so you can display errors next to the field
	*  * @since 
	*  *
	*  * @author Hans Anderson <handerson@executiveboard.com>
	*  *
	*  */
function form_helper ( $type, $name=null, $value=null, $attributes=null, $errors=null )
{
	if ( empty ( $type ) ) 
	{
		return "UNKNOWN FORM TYPE";
	}

	if ( $type <> 'open' and $type <> 'close' and $name == null )
	{
		return "UNKNOWN FORM TYPE";
	}


	if ( is_array ( $attributes ) )
	{
		$attr = $attributes;
	} elseif ( $attributes ) {
		parse_str ( $attributes, $attr);
	} else {
		$attr = array ();
	}
		
	if ( empty ( $attr['id'] ) )
	{
		$attr['id'] = !empty ( $name ) ? $name : '';
	}

	switch ( $type )
	{

		case 'hidden':
			$form = '<input type="hidden" ' . attr2str ( $attr ) . ' name="' . htmlentities ( $name ) . '" value="' . htmlentities ( $value ) . '" />';
		break;

		case 'label':
			$form = '<label for="' . htmlentities ( $name ) . '">' . htmlentities ( $value ) . '</label>';
		break;

		case 'text':
			$form = '<input type="text" ' . attr2str ( $attr ) . ' name="' . htmlentities ( $name ) . '" value="' . htmlentities ( $value ) . '" />';
		break;

		case 'textarea':
			$form = '<textarea ' . attr2str ( $attr ) . ' name="' . htmlentities ( $name ) . '">' . htmlentities ( $value ) . '</textarea>';
		break;

		case 'select':
		case 'dropdown':
			$selected = $attr['selected']; unset ( $attr['selected'] );
			$form  = '<select ' . attr2str ( $attr ) . ' name="' . htmlentities ( $name ) . '">';
			$form .= html_options ( $value, $selected);
			$form .= '</select>';

		break;

		case 'checkbox':
			$checked = $attr['checked']; unset ( $attr['checked'] );
			$checked = ( $checked ) ? ' checked ' : '';
			$form = '<input type="checkbox" ' . attr2str ( $attr ) . ' name="' . htmlentities ( $name ) . '" value="' . htmlentities ( $value ) . '" ' . $checked . '/>';
		break;

		case 'radio':
			$checked = $attr['checked']; unset ( $attr['checked'] );
			$checked = ( $checked ) ? ' checked ' : '';
			$form    = '<input type="radio" ' . attr2str ( $attr ) . ' name="' . htmlentities ( $name ) . '" value="' . htmlentities ( $value ) . '" ' . $checked . '/>';
		break;

		case 'submit':
			$form = '<input type="submit" ' . attr2str ( $attr ) . ' name="' . htmlentities ( $name ) . '" value="' . htmlentities ( $value ) . '" />';
		break;

		case 'button':
			$form = '<input type="submit" ' . attr2str ( $attr ) . ' name="' . htmlentities ( $name ) . '" value="' . htmlentities ( $value ) . '" />';
		break;

		case 'close':
			$form = '</form>';
		break;
		
		case 'password':
		case 'pwd':
			$form = '<input type="password" ' . attr2str ( $attr ) . ' name="' . htmlentities ( $name ) . '" value="' . htmlentities ( $value ) . '" />';
		break;

		case 'upload':
			$form = '<input type="file" ' . attr2str ( $attr ) . ' name="' . htmlentities ( $name ) . '" value="' . htmlentities ( $value ) . '" />';
		break;

		case 'open':

			$attr['method'] = !empty ( $attr['method'] ) ? $attr['method'] : 'POST';
			$attr['action'] = !empty ( $attr['action'] ) ? $attr['action'] : '/' . CONTROLLER . '/' . ACTION;


			// disable for now
			// need to be able to handle multiple valid instances... several forms on a page, several windows open, etc
			//$attr['nocsrf'] = 1;

			if ( !isset ( $attr['nocsrf'] ) ) // add CSRF attack difficulty enhancer; this prevents people from setting up a CSRF attack to take advantage of logged-in user
			{
				$fname  = 'v' . random_string ( 10 );
				$value = md5 ( CSRF_PWD . time() . $fname . CONTROLLER . ACTION );
				session("csrf.error_uri", '/' . CONTROLLER . '/' . ACTION . '?csrf_error' );  // checked in the mvcha.php file
				
				session("csrf.field_name", $fname);  // checked in the mvcha.php file
				//session("csrf.{$fname}", $value );   // checked in the mvcha.php file
				
				$csrf_nonce_array = session('csrf.nonce_array');
				if ( !is_array ( $csrf_nonce_array ) )
				{
					$csrf_nonce_array = array ();
				}
				
				$csrf_nonce_array[$fname] = $value; // easier to unset
				session("csrf.nonce_array", $csrf_nonce_array );

				$form_b = form_helper ( 'hidden', $fname, $value );
			} else {
				session("csrf.field_name", 'disabled' );  // checked in the mvcha.php file
				unset ( $attr['nocsrf'] );
				$form_b = '';
			}

			$form = '<form ' . attr2str($attr) . '>' . $form_b;
		break;






		case 'open_multipart':
			$attr['enctype'] = 'multipart/form-data';
			$form = form_helper ( 'open', $name, '', $attr, $errors );
		break;


	}

	$errors = registry ( 'form.errors' );
	if ( is_array ( $errors ) )
	{
		if ( isset ( $errors[$name] ) )
		{
			$template = registry ( 'form.error_template' );
			if ( !empty ( $template ) )
			{
				$terror = str_replace ( '{%error}', $errors[$name], $template );
			} else { // default
				$terror = '<span class="form-error form-error-' . $type . '" id="error-' . $name . '">' . htmlentities ( $errors[$name] ) . '</span>';
			}

			if ( registry ( 'form.error_location' ) == 'above' )
			{
				$form = $terror . $form;
			} else {
				$form = $form . $terror;
			}
		}
	}

	return $form . "\n";

}
