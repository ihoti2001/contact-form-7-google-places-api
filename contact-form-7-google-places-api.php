<?php
/*
Plugin Name: Contact Form 7 - Google Places API
Plugin URI: https://github.com/AmmoCan/contact-form-7-google-places-api
Description: Based on a plugin by Pasquale Bucci, that wasn't working and only autocompletes a user's input of a city. This plugin provides a text input field for an autocomplete places search, based on the Google Places API. The Contact Form 7 plugin and a Google API key is required.
Version: 1.1
Author: AmmoCan
Author URI: https://www.linkedin.com/in/ammocan
License: GPLv3 or later
*/

/* Copyright 2015 Two Drops (email : ammo@2-Drops.com)

   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License, version 3, as 
   published by the Free Software Foundation.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with this plugin; if not, write to the following:

   Free Software Foundation, Inc.,
   51 Franklin St, Fifth Floor,
   Boston, MA 02110-1301 USA
*/

//do not allow direct access
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/*
* Set up Settings page
*/
function gpa_settings_page() {
?>
  <div class="wrap">
    <h1>Google Places API Info.</h1>
    <form method="post" action="options.php">
      <?php
        settings_fields('section');
        do_settings_sections('gpa-options');      
        submit_button(); 
      ?>          
    </form>
  </div>
<?php
}

function add_gpa_menu_item() {
  
	add_menu_page(
	  'Google Places API',
	  'Google Places API',
	  'manage_options',
	  'gpa',
	  'gpa_settings_page',
	  null,
	  99
  );
	
}
add_action('admin_menu', 'add_gpa_menu_item');

function display_gpa_page_element() {
	?>
    <input type="text" name="gpa_page" id="gpa_page" value="<?php echo get_option('gpa_page')?>" />
  <?php
}

function display_api_key_element() {
	?>
    <input type="password" name="api_key" id="api_key" value="<?php echo get_option('api_key')?>" />
  <?php
}

function display_gpa_fields() {
	add_settings_section('section', 'All Settings', null, 'gpa-options');
	
	add_settings_field('gpa_page', 'What Page Will I Be On?', 'display_gpa_page_element', 'gpa-options', 'section');
  add_settings_field('api_key', 'Google Places API Key', 'display_api_key_element', 'gpa-options', 'section');
  
  register_setting('section', 'gpa_page');
  register_setting('section', 'api_key');
}
add_action('admin_init', 'display_gpa_fields');

/*
* Loads scripts
*/
function gpa_load_user_api() {
  $gpa_page = get_option( 'gpa_page' );
  $api_key = get_option( 'api_key' );
  $api_script .= '//maps.googleapis.com/maps/api/js?key=' . $api_key . '&libraries=places';
  // Below we are making sure the script is only loaded on the page designated in the Google Places API settings page (i.e. Contact, Home, etc.).
	if( is_page( $gpa_page ) ) {
  	// You will need to get your own API key from Google at: https://developers.google.com/maps/documentation/javascript/get-api-key
  	// Once you have your key enter it in the Google Places API Key field on the Google Places API settings page.
	  wp_enqueue_script( 'gpa-google-places-api', $api_script, array(), 'null', true );
	}
}
add_action( 'wp_enqueue_scripts', 'gpa_load_user_api' );

function gpa_plugin_script() {
  $gpa_page = get_option( 'gpa_page' );
  // Below we are making sure the script is only loaded on the page designated in the Google Places API settings page (i.e. Contact, Home, etc.).
  if( is_page( $gpa_page ) ) { ?>
    <script>
      window.onload = function initialize_gpa() {
      // Create the autocomplete object and associate it with the UI input control.
      // Restrict the search to geographical location types.
        autocomplete = new google.maps.places.Autocomplete(
            /** @type {HTMLInputElement} */( document.getElementById( 'autocomplete' ) ),
            { types: ['geocode'] });
      
        google.maps.event.addListener( autocomplete, 'place_changed', function() {
          infowindow.close();
          marker.setVisible(false);
          var place = autocomplete.getPlace();
        });
      }
    </script>
  <?php }
}
add_action( 'wp_footer', 'gpa_plugin_script', 21, 1 );

/*
* A base module for [placesfieldtext], [placesfieldtext*]
*/
function wpcf7_placesfieldtext_init() {

	if( function_exists( 'wpcf7_add_shortcode' ) ) {

  	/* Shortcode handler */		
  	wpcf7_add_shortcode( 'placesfieldtext', 'wpcf7_placesfieldtext_shortcode_handler', true );
  	wpcf7_add_shortcode( 'placesfieldtext*', 'wpcf7_placesfieldtext_shortcode_handler', true );
	
  	}
  	
  	add_filter( 'wpcf7_validate_placesfieldtext', 'wpcf7_placesfieldtext_validation_filter', 10, 2 );
  	add_filter( 'wpcf7_validate_placesfieldtext*', 'wpcf7_placesfieldtext_validation_filter', 10, 2 );
  	add_action( 'admin_init', 'wpcf7_add_tag_generator_placesfieldtext', 15 );
	
}
add_action( 'plugins_loaded', 'wpcf7_placesfieldtext_init' , 20 );

/*
* PlacesFieldText shortcode
*/
function wpcf7_placesfieldtext_shortcode_handler( $tag ) {
	
	$tag = new WPCF7_Shortcode( $tag );

	if ( empty( $tag->name ) )
		return '';

	$validation_error = wpcf7_get_validation_error( $tag->name );

	$class = wpcf7_form_controls_class( $tag->type, 'wpcf7-text' );

	if ( in_array( $tag->basetype, array( 'email', 'url', 'tel' ) ) )
		$class .= ' wpcf7-validates-as-' . $tag->basetype;

	if ( $validation_error )
		$class .= ' wpcf7-not-valid';

	$atts = array();

	$atts['size'] = $tag->get_size_option( '40' );
	$atts['maxlength'] = $tag->get_maxlength_option();
	$atts['minlength'] = $tag->get_minlength_option();

	if ( $atts['maxlength'] && $atts['minlength'] && $atts['maxlength'] < $atts['minlength'] ) {
		unset( $atts['maxlength'], $atts['minlength'] );
	}

	$atts['class'] = $tag->get_class_option( $class );
	$atts['id'] = $tag->get_id_option();
	$atts['tabindex'] = $tag->get_option( 'tabindex', 'int', true );

	if ( $tag->has_option( 'readonly' ) )
		$atts['readonly'] = 'readonly';

	if ( $tag->is_required() )
		$atts['aria-required'] = 'true';

	$atts['aria-invalid'] = $validation_error ? 'true' : 'false';

	$value = (string) reset( $tag->values );

	if ( $tag->has_option( 'placeholder' ) || $tag->has_option( 'watermark' ) ) {
		$atts['placeholder'] = $value;
		$value = '';
	}

	$value = $tag->get_default_option( $value );

	$value = wpcf7_get_hangover( $tag->name, $value );

	$atts['value'] = $value;

	if ( wpcf7_support_html5() ) {
		$atts['type'] = 'text';
	}

	$atts['name'] = $tag->name;

	$atts = wpcf7_format_atts( $atts );

	$html = sprintf(
		'<span class="wpcf7-form-control-wrap %1$s"><input %2$s />%3$s</span>',
		sanitize_html_class( $tag->name ), $atts, $validation_error );

	return $html;
}

/*
* PlacesFieldText validation filter
*/
function wpcf7_placesfieldtext_validation_filter( $result, $tag ) {
	
	$tag = new WPCF7_FormTag( $tag );

	$name = $tag->name;

	$value = isset( $_POST[$name] )
		? trim( wp_unslash( strtr( (string) $_POST[$name], "\n", " " ) ) )
		: '';
		
  $message = wpcf7_get_message( 'invalid_required' );

	if ( 'placesfieldtext' == $tag->basetype ) {
		if ( $tag->is_required() && '' == $value ) {
			$result->invalidate( $tag, $message );
		}
	}

	return $result;
}

/*
* PlacesFieldText tag generator
*/
function wpcf7_add_tag_generator_placesfieldtext() {
	if ( ! function_exists( 'wpcf7_add_tag_generator' ) )
	  return;
	  
  wpcf7_add_tag_generator( 'placesfieldtext', __( 'Places Text Field', 'contact-form-7' ),
			'wpcf7-tg-pane-placesfieldtext', 'wpcf7_tg_pane_placesfieldtext_' );
}

function wpcf7_tg_pane_placesfieldtext_( $contact_form ) {
	wpcf7_tg_pane_placesfieldtext_and_relatives( 'placesfieldtext' );
}

function wpcf7_tg_pane_placesfieldtext_and_relatives( $type = 'placesfieldtext' ) {
  if ( ! in_array( $type, array() ) )
    $type = 'placesfieldtext';
?>

<div class="control-box">
  <fieldset>
    
    <table class="form-table">
      <tbody>
        
      	<tr>
      	  <th scope="row"><?php echo esc_html( __( 'Field type', 'contact-form-7' ) ); ?></th>
        	<td>
        		<fieldset>
        		  <legend class="screen-reader-text"><?php echo esc_html( __( 'Field type', 'contact-form-7' ) ); ?></legend>
        		  <label><input type="checkbox" name="required" /> <?php echo esc_html( __( 'Required field', 'contact-form-7' ) ); ?></label>
        		</fieldset>
        	</td>
      	</tr>
      
      	<tr>
        	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-name' ); ?>"><?php echo esc_html( __( 'Name', 'contact-form-7' ) ); ?></label></th>
        	<td><input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" /></td>
      	</tr>
      
      	<tr>
        	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-values' ); ?>"><?php echo esc_html( __( 'Default value', 'contact-form-7' ) ); ?></label></th>
        	<td><input type="text" name="values" class="oneline" id="<?php echo esc_attr( $args['content'] . '-values' ); ?>" /><br />
        	<label><input type="checkbox" name="placeholder" class="option" /> <?php echo esc_html( __( 'Use this text as the placeholder of the field', 'contact-form-7' ) ); ?></label></td>
      	</tr>
      
      <?php if ( in_array( $type, array( 'text', 'email', 'url' ) ) ) : ?>
      	<tr>
        	<th scope="row"><?php echo esc_html( __( 'Akismet', 'contact-form-7' ) ); ?></th>
        	<td>
        		<fieldset>
          		<legend class="screen-reader-text"><?php echo esc_html( __( 'Akismet', 'contact-form-7' ) ); ?></legend>
          
            <?php if ( 'text' == $type ) : ?>
          		<label>
          			<input type="checkbox" name="akismet:author" class="option" />
          			<?php echo esc_html( __( "This field requires author's name", 'contact-form-7' ) ); ?>
          		</label>
            <?php elseif ( 'email' == $type ) : ?>
          		<label>
          			<input type="checkbox" name="akismet:author_email" class="option" />
          			<?php echo esc_html( __( "This field requires author's email address", 'contact-form-7' ) ); ?>
          		</label>
            <?php elseif ( 'url' == $type ) : ?>
          		<label>
          			<input type="checkbox" name="akismet:author_url" class="option" />
          			<?php echo esc_html( __( "This field requires author's URL", 'contact-form-7' ) ); ?>
          		</label>
            <?php endif; ?>
        
        		</fieldset>
        	</td>
      	</tr>
      <?php endif; ?>
      
      	<tr>
        	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-id' ); ?>"><?php echo esc_html( __( 'Id attribute', 'contact-form-7' ) ); ?></label></th>
        	<td><input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-id' ); ?>" /></td>
      	</tr>
      
      	<tr>
        	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-class' ); ?>"><?php echo esc_html( __( 'Class attribute', 'contact-form-7' ) ); ?></label></th>
        	<td><input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-class' ); ?>" /></td>
      	</tr>
      
      </tbody>
    </table>
  </fieldset>
</div>

<div class="insert-box">
	<input type="text" name="<?php echo $type; ?>" class="tag code" readonly="readonly" onfocus="this.select()" />

	<div class="submitbox">
	  <input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'contact-form-7' ) ); ?>" />
	</div>

	<br class="clear" />

	<p class="description mail-tag"><label for="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>"><?php echo sprintf( esc_html( __( "To use the value input through this field in a mail field, you need to insert the corresponding mail-tag (%s) into the field on the Mail tab.", 'contact-form-7' ) ), '<strong><span class="mail-tag"></span></strong>' ); ?><input type="text" class="mail-tag code hidden" readonly="readonly" id="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>" /></label></p>
</div>

<?php
}
?>
