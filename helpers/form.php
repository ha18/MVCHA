<?php

/**
 * Form helper functions.
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
