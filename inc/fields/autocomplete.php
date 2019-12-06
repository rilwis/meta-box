<?php
/**
 * The autocomplete field.
 *
 * @package Meta Box
 */

/**
 * Autocomplete field class.
 */
class RWMB_Autocomplete_Field extends RWMB_Multiple_Values_Field {
	/**
	 * Enqueue scripts and styles.
	 */
	public static function admin_enqueue_scripts() {
		wp_enqueue_style( 'rwmb-autocomplete', RWMB_CSS_URL . 'autocomplete.css', '', RWMB_VER );
		wp_enqueue_script( 'rwmb-autocomplete', RWMB_JS_URL . 'autocomplete.js', array( 'jquery-ui-autocomplete' ), RWMB_VER, true );

		RWMB_Helpers_Field::localize_script_once(
			'rwmb-autocomplete',
			'RWMB_Autocomplete',
			array(
				'delete' =>esc_html__( 'Delete', 'meta-box' ),
			)
		);
	}

	/**
	 * Get field HTML.
	 *
	 * @param mixed $meta  Meta value.
	 * @param array $field Field parameters.
	 * @return string
	 */
	public static function html( $meta, $field ) {
		if ( ! is_array( $meta ) ) {
			$meta = array( $meta );
		}

		$field   = apply_filters( 'rwmb_autocomplete_field', $field, $meta );
		$options = $field['options'];

		if ( is_array( $field['options'] ) ) {
			$options = array();
			foreach ( $field['options'] as $value => $label ) {
				$options[] = array(
					'value' => $value,
					'label' => $label,
				);
			}
			$options = wp_json_encode( $options );
		}

		// Input field that triggers autocomplete.
		// This field doesn't store field values, so it doesn't have "name" attribute.
		// The value(s) of the field is store in hidden input(s). See below.
		$html = sprintf(
			'<input type="text" class="rwmb-autocomplete-search" size="%s">
			<input type="hidden" name="%s" class="rwmb-autocomplete" data-options="%s" disabled>',
			esc_attr( $field['size'] ),
			esc_attr( $field['field_name'] ),
			esc_attr( $options )
		);

		$html .= '<div class="rwmb-autocomplete-results">';

		// Each value is displayed with label and 'Delete' option.
		// The hidden input has to have ".rwmb-*" class to make clone work.
		$tpl = '
			<div class="rwmb-autocomplete-result">
				<div class="label">%s</div>
				<div class="actions">%s</div>
				<input type="hidden" class="rwmb-autocomplete-value" name="%s" value="%s">
			</div>
		';

		if ( is_array( $field['options'] ) ) {
			foreach ( $field['options'] as $value => $label ) {
				if ( ! in_array( $value, $meta ) ) {
					continue;
				}
				$html .= sprintf(
					$tpl,
					esc_html( $label ),
					esc_html__( 'Delete', 'meta-box' ),
					esc_attr( $field['field_name'] ),
					esc_attr( $value )
				);
			}
		} else {
			$meta = array_filter( $meta );
			foreach ( $meta as $value ) {
				$label = apply_filters( 'rwmb_autocomplete_result_label', $value, $field );
				$html .= sprintf(
					$tpl,
					esc_html( $label ),
					esc_html__( 'Delete', 'meta-box' ),
					esc_attr( $field['field_name'] ),
					esc_attr( $value )
				);
			}
		}

		$html .= '</div>'; // .rwmb-autocomplete-results.

		return $html;
	}

	/**
	 * Normalize parameters for field.
	 *
	 * @param array $field Field parameters.
	 * @return array
	 */
	public static function normalize( $field ) {
		$field = parent::normalize( $field );
		$field = wp_parse_args(
			$field,
			array(
				'size' => 30,
			)
		);
		return $field;
	}
}
