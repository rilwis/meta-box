<?php
/**
 * The key-value field which allows users to add pairs of keys and values.
 *
 * @package Meta Box
 */

/**
 * Key-value field class.
 */
class RWMB_Key_Value_Field extends RWMB_Text_Field {
	/**
	 * Get field HTML.
	 *
	 * @param mixed $meta  Meta value.
	 * @param array $field Field parameters.
	 *
	 * @return string
	 */
	public static function html( $meta, $field ) {
		// Key.
		$key                       = isset( $meta[0] ) ? $meta[0] : '';
		$attributes                = self::get_attributes( $field, $key );
		$attributes['placeholder'] = $field['placeholder']['key'];
		$html                      = sprintf( '<input %s>', self::render_attributes( $attributes ) );

		// Value.
		$val                       = isset( $meta[1] ) ? $meta[1] : '';
		$attributes                = self::get_attributes( $field, $val );
		$attributes['placeholder'] = $field['placeholder']['value'];
		$html                     .= sprintf( '<input %s>', self::render_attributes( $attributes ) );

		return $html;
	}

	/**
	 * Show begin HTML markup for fields.
	 *
	 * @param mixed $meta  Meta value.
	 * @param array $field Field parameters.
	 *
	 * @return string
	 */
	public static function begin_html( $meta, $field ) {
		$desc = $field['desc'] ? "<p id='{$field['id']}_description' class='description'>{$field['desc']}</p>" : '';

		if ( empty( $field['name'] ) ) {
			return '<div class="rwmb-input">' . $desc;
		}

		return sprintf(
			'<div class="rwmb-label">
				<label for="%s">%s</label>
			</div>
			<div class="rwmb-input">
			%s',
			$field['id'],
			$field['name'],
			$desc
		);
	}

	/**
	 * Do not show field description.
	 *
	 * @param array $field Field parameters.
	 *
	 * @return string
	 */
	public static function input_description( $field ) {
		return '';
	}

	/**
	 * Do not show field description.
	 *
	 * @param array $field Field parameters.
	 *
	 * @return string
	 */
	public static function label_description( $field ) {
		return '';
	}

	/**
	 * Escape meta for field output.
	 *
	 * @param mixed $meta Meta value.
	 *
	 * @return mixed
	 */
	public static function esc_meta( $meta ) {
		foreach ( (array) $meta as $k => $pairs ) {
			$meta[ $k ] = array_map( 'esc_attr', (array) $pairs );
		}
		return $meta;
	}

	/**
	 * Sanitize field value.
	 *
	 * @param mixed $new     The submitted meta value.
	 * @param mixed $old     The existing meta value.
	 * @param int   $post_id The post ID.
	 * @param array $field   The field parameters.
	 *
	 * @return array
	 */
	public static function value( $new, $old, $post_id, $field ) {
		foreach ( $new as &$arr ) {
			if ( empty( $arr[0] ) && empty( $arr[1] ) ) {
				$arr = false;
			}
		}
		$new = array_filter( $new );
		return $new;
	}

	/**
	 * Normalize parameters for field.
	 *
	 * @param array $field Field parameters.
	 *
	 * @return array
	 */
	public static function normalize( $field ) {
		$field['clone']    = true;
		$field['multiple'] = true;
		$field             = parent::normalize( $field );

		$field['attributes']['type'] = 'text';
		$field['placeholder']        = wp_parse_args(
			(array) $field['placeholder'],
			array(
				'key'   =>esc_html__( 'Key', 'meta-box' ),
				'value' =>esc_html__( 'Value', 'meta-box' ),
			)
		);
		return $field;
	}

	/**
	 * Format value for the helper functions.
	 *
	 * @param array        $field   Field parameters.
	 * @param string|array $value   The field meta value.
	 * @param array        $args    Additional arguments. Rarely used. See specific fields for details.
	 * @param int|null     $post_id Post ID. null for current post. Optional.
	 *
	 * @return string
	 */
	public static function format_clone_value( $field, $value, $args, $post_id ) {
		return sprintf( '<label>%s:</label> %s', $value[0], $value[1] );
	}
}
