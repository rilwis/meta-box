<?php
/**
 * The Open Street Map field.
 *
 * @package Meta Box
 * @since   4.15.0
 */

/**
 * Open Street Map field class.
 */
class RWMB_OSM_Field extends RWMB_Field {
	/**
	 * Enqueue scripts and styles.
	 */
	public static function admin_enqueue_scripts() {
		// Because map is a hosted service, it's ok to use hosted Leaflet scripts.
		wp_enqueue_style( 'leaflet', 'https://unpkg.com/leaflet@1.6.0/dist/leaflet.css', array(), '1.6.0' );
		wp_enqueue_script( 'leaflet', 'https://unpkg.com/leaflet@1.6.0/dist/leaflet.js', array(), '1.6.0', true );

		wp_enqueue_style( 'rwmb-osm', RWMB_CSS_URL . 'osm.css', array( 'leaflet' ), RWMB_VER );
		wp_enqueue_script( 'rwmb-osm', RWMB_JS_URL . 'osm.js', array( 'jquery', 'jquery-ui-autocomplete', 'leaflet' ), RWMB_VER, true );

		RWMB_Helpers_Field::localize_script_once(
			'rwmb-osm',
			'RWMB_Osm',
			array(
				'no_results_string' => __( 'No results found', 'meta-box' ),
			)
		);
	}

	/**
	 * Get field HTML.
	 *
	 * @param mixed $meta  Meta value.
	 * @param array $field Field parameters.
	 *
	 * @return string
	 */
	public static function html( $meta, $field ) {
		$address = is_array( $field['address_field'] ) ? implode( ',', $field['address_field'] ) : $field['address_field'];
		$html    = sprintf(
			'<div class="rwmb-osm-field" data-address-field="%s">',
			esc_attr( $address )
		);

		$attributes          = self::get_attributes( $field, $meta );
		$attributes['type']  = 'hidden';
		$attributes['value'] = $meta;

		$html .= sprintf(
			'<div class="rwmb-osm-canvas" data-default-loc="%s" data-region="%s" data-language="%s"></div>
			<input %s>',
			esc_attr( $field['std'] ),
			esc_attr( $field['region'] ),
			esc_attr( $field['language'] ),
			self::render_attributes( $attributes )
		);

		$html .= '</div>';

		return $html;
	}

	/**
	 * Normalize parameters for field.
	 *
	 * @param array $field Field parameters.
	 *
	 * @return array
	 */
	public static function normalize( $field ) {
		$field = parent::normalize( $field );
		$field = wp_parse_args(
			$field,
			array(
				'std'           => '',
				'address_field' => '',
				'language'      => '',
				'region'        => '',
			)
		);

		return $field;
	}

	/**
	 * Get the field value.
	 * The difference between this function and 'meta' function is 'meta' function always returns the escaped value
	 * of the field saved in the database, while this function returns more meaningful value of the field.
	 *
	 * @param  array    $field   Field parameters.
	 * @param  array    $args    Not used for this field.
	 * @param  int|null $post_id Post ID. null for current post. Optional.
	 *
	 * @return mixed Array(latitude, longitude, zoom)
	 */
	public static function get_value( $field, $args = array(), $post_id = null ) {
		$value                               = parent::get_value( $field, $args, $post_id );
		list( $latitude, $longitude, $zoom ) = explode( ',', $value . ',,' );
		return compact( 'latitude', 'longitude', 'zoom' );
	}

	/**
	 * Output the field value.
	 * Display Open Street Map using Leaflet
	 *
	 * @param  array    $field   Field parameters.
	 * @param  array    $args    Additional arguments for the map.
	 * @param  int|null $post_id Post ID. null for current post. Optional.
	 *
	 * @return string HTML output of the field
	 */
	public static function the_value( $field, $args = array(), $post_id = null ) {
		$value = parent::get_value( $field, $args, $post_id );
		return self::render_map( $value, $args );
	}

	/**
	 * Render a map in the frontend.
	 *
	 * @param array $location The [latitude, longitude[, zoom]] location.
	 * @param array $args     Additional arguments for the map.
	 *
	 * @return string
	 */
	public static function render_map( $location, $args = array() ) {
		list( $latitude, $longitude, $zoom ) = explode( ',', $location . ',,' );
		if ( ! $latitude || ! $longitude ) {
			return '';
		}

		$args = wp_parse_args(
			$args,
			array(
				'latitude'     => $latitude,
				'longitude'    => $longitude,
				'width'        => '100%',
				'height'       => '480px',
				'marker'       => true, // Display marker?
				'marker_title' => '', // Marker title, when hover.
				'info_window'  => '', // Content of info window (when click on marker). HTML allowed.
				'js_options'   => array(),
				'zoom'         => $zoom,
			)
		);

		wp_enqueue_style( 'leaflet', 'https://unpkg.com/leaflet@1.5.1/dist/leaflet.css', array(), '1.5.1' );
		wp_enqueue_script( 'leaflet', 'https://unpkg.com/leaflet@1.5.1/dist/leaflet.js', array(), '1.5.1', true );
		wp_enqueue_script( 'rwmb-osm-frontend', RWMB_JS_URL . 'osm-frontend.js', array( 'jquery', 'leaflet' ), RWMB_VER, true );

		/*
		 * More Open Street Map options
		 * @link https://leafletjs.com/reference-1.5.0.html#map-option
		 */
		$args['js_options'] = wp_parse_args(
			$args['js_options'],
			array(
				// Default to 'zoom' level set in admin, but can be overwritten.
				'zoom' => $args['zoom'],
			)
		);

		$output = sprintf(
			'<div class="rwmb-osm-canvas" data-osm_options="%s" style="width:%s;height:%s"></div>',
			esc_attr( wp_json_encode( $args ) ),
			esc_attr( $args['width'] ),
			esc_attr( $args['height'] )
		);
		return $output;
	}
}
