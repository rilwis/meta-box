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
	 * Add ajax actions callback.
	 */
	public static function add_actions() {
		add_action( 'wp_ajax_rwmb_get_posts', array( __CLASS__, 'ajax_get_posts' ) );
		add_action( 'wp_ajax_nopriv_rwmb_get_posts', array( __CLASS__, 'ajax_get_posts' ) );
	}

	/**
	 * Query posts via ajax.
	 */
	public static function ajax_get_posts() {
		check_ajax_referer( 'query' );

		$request = rwmb_request();

		$field = $request->filter_post( 'field', FILTER_DEFAULT, FILTER_FORCE_ARRAY );

		// Required for 'choice_label' filter. See self::filter().
		$field['clone']        = false;
		$field['_original_id'] = $field['id'];

		// Search.
		$field['query_args']['s'] = $request->filter_post( 'term', FILTER_SANITIZE_STRING );

		// Pagination.
		if ( 'query:append' === $request->filter_post( '_type', FILTER_SANITIZE_STRING ) ) {
			$field['query_args']['paged'] = $request->filter_post( 'page', FILTER_SANITIZE_NUMBER_INT );
		}

		// Query the database.
		$items = self::query( null, $field );
		$items = array_values( $items );

		$data = array( 'items' => $items );

		// More items for pagination.
		$limit = (int) $field['query_args']['posts_per_page'];
		if ( -1 !== $limit && count( $items ) === $limit ) {
			$data['more'] = true;
		}

		wp_send_json_success( $data );
	}

	/**
	 * Normalize parameters for field.
	 *
	 * @param array $field Field parameters.
	 * @return array
	 */
	public static function normalize( $field ) {
		$field = wp_parse_args(
			$field,
			array(
				'post_type'  => 'post',
				'parent'     => false,
				'query_args' => array(),
			)
		);

		$field['post_type'] = (array) $field['post_type'];

		/*
		 * Set default placeholder:
		 * - If multiple post types: show 'Select a post'.
		 * - If single post type: show 'Select a %post_type_name%'.
		 */
		$placeholder =esc_html__( 'Select a post', 'meta-box' );
		if ( 1 === count( $field['post_type'] ) ) {
			$post_type        = reset( $field['post_type'] );
			$post_type_object = get_post_type_object( $post_type );
			if ( ! empty( $post_type_object ) ) {
				// Translators: %s is the taxonomy singular label.
				$placeholder = sprintf(esc_html__( 'Select a %s', 'meta-box' ), strtolower( $post_type_object->labels->singular_name ) );
			}
		}
		$field = wp_parse_args(
			$field,
			array(
				'placeholder' => $placeholder,
			)
		);

		// Set parent option, which will change field name to `parent_id` to save as post parent.
		if ( $field['parent'] ) {
			$field['multiple']   = false;
			$field['field_name'] = 'parent_id';
		}

		$field = parent::normalize( $field );

		// Set default query args.
		$posts_per_page      = $field['ajax'] ? 10 : -1;
		$field['query_args'] = wp_parse_args(
			$field['query_args'],
			array(
				'post_type'      => $field['post_type'],
				'post_status'    => 'publish',
				'posts_per_page' => $posts_per_page,
			)
		);

		parent::set_ajax_params( $field );

		return $field;
	}

	/**
	 * Query posts for field options.
	 *
	 * @param  array $meta  Saved meta value.
	 * @param  array $field Field settings.
	 * @return array        Field options array.
	 */
	public static function query( $meta, $field ) {
		$args = wp_parse_args(
			$field['query_args'],
			array(
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		// Query only selected items.
		if ( ! empty( $field['ajax'] ) && ! empty( $meta ) ) {
			$args['posts_per_page'] = count( $meta );
			$args['post__in']       = $meta;
		}

		// Get from cache to prevent same queries.
		$last_changed = wp_cache_get_last_changed( 'posts' );
		$key          = md5( serialize( $args ) );
		$cache_key    = "$key:$last_changed";
		$options      = wp_cache_get( $cache_key, 'meta-box-post-field' );

		if ( false !== $options ) {
			return $options;
		}

		$query   = new WP_Query( $args );
		$options = array();
		foreach ( $query->posts as $post ) {
			$options[ $post->ID ] = array(
				'value'  => $post->ID,
				'label'  => self::filter( 'choice_label', $post->post_title, $field, $post ),
				'parent' => $post->post_parent,
			);
		}

		// Cache the query.
		wp_cache_set( $cache_key, $options, 'meta-box-post-field' );

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
	 * Format a single value for the helper functions. Sub-fields should overwrite this method if necessary.
	 *
	 * @param array    $field   Field parameters.
	 * @param string   $value   The value.
	 * @param array    $args    Additional arguments. Rarely used. See specific fields for details.
	 * @param int|null $post_id Post ID. null for current post. Optional.
	 *
	 * @return string
	 */
	public static function format_single_value( $field, $value, $args, $post_id ) {
		return ! $value ? '' : sprintf(
			'<a href="%s" title="%s">%s</a>',
			esc_url( get_permalink( $value ) ),
			the_title_attribute(
				array(
					'post' => $value,
					'echo' => false,
				)
			),
			get_the_title( $value )
		);
	}
}
