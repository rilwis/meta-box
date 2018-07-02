<?php
/**
 * The post field which allows users to select existing posts.
 *
 * @package Meta Box
 */

/**
 * Post field class.
 */
class RWMB_Post_Field extends RWMB_Object_Choice_Field {
	/**
	 * Normalize parameters for field.
	 *
	 * @param array $field Field parameters.
	 * @return array
	 */
	public static function normalize( $field ) {
		$field = wp_parse_args( $field, array(
			'post_type'  => 'post',
			'parent'     => false,
			'query_args' => array(),
		) );

		$field['post_type'] = (array) $field['post_type'];

		/*
		 * Set default placeholder:
		 * - If multiple post types: show 'Select a post'.
		 * - If single post type: show 'Select a %post_type_name%'.
		 */
		$placeholder = __( 'Select a post', 'meta-box' );
		if ( 1 === count( $field['post_type'] ) ) {
			$post_type = reset( $field['post_type'] );
			$post_type_object = get_post_type_object( $post_type );
			if ( ! empty( $post_type_object ) ) {
				// Translators: %s is the taxonomy singular label.
				$placeholder = sprintf( __( 'Select a %s', 'meta-box' ), strtolower( $post_type_object->labels->singular_name ) );
			}
		}
		$field = wp_parse_args( $field, array(
			'placeholder' => $placeholder,
		) );

		// Query posts for field options.
		$field['options'] = self::query( $field );

		// Set parent option, which will change field name to `parent_id` to save as post parent.
		if ( $field['parent'] ) {
			$field['multiple']   = false;
			$field['field_name'] = 'parent_id';
		}

		$field = parent::normalize( $field );

		return $field;
	}

	/**
	 * Query posts for field options.
	 * @param  array $field Field settings.
	 * @return array        Field options array.
	 */
	public static function query( $field ) {
		$args = wp_parse_args( $field['query_args'], array(
			'post_type'              => $field['post_type'],
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		) );
		$query   = new WP_Query( $args );
		$options = array();
		foreach ( $query->posts as $post ) {
			$options[$post->ID] = array(
				'value'  => $post->ID,
				'label'  => $post->post_title,
				'parent' => $post->post_parent,
			);
		}
		return $options;
	}

	/**
	 * Get meta value.
	 * If field is cloneable, value is saved as a single entry in DB.
	 * Otherwise value is saved as multiple entries (for backward compatibility).
	 *
	 * @see "save" method for better understanding
	 *
	 * @param int   $post_id Post ID.
	 * @param bool  $saved   Is the meta box saved.
	 * @param array $field   Field parameters.
	 *
	 * @return mixed
	 */
	public static function meta( $post_id, $saved, $field ) {
		return $field['parent'] ? wp_get_post_parent_id( $post_id ) : parent::meta( $post_id, $saved, $field );
	}

	/**
	 * Get option label.
	 *
	 * @param array  $field Field parameters.
	 * @param string $value Option value.
	 *
	 * @return string
	 */
	public static function get_option_label( $field, $value ) {
		return sprintf(
			'<a href="%s" title="%s">%s</a>',
			esc_url( get_permalink( $value ) ),
			the_title_attribute( array(
				'post' => $value,
				'echo' => false,
			) ),
			get_the_title( $value )
		);
	}
}
