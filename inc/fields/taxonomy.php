<?php
/**
 * The taxonomy field which aims to replace the built-in WordPress taxonomy UI with more options.
 *
 * @package Meta Box
 */

/**
 * Taxonomy field class which set post terms when saving.
 */
class RWMB_Taxonomy_Field extends RWMB_Object_Choice_Field {
	/**
	 * Add ajax actions callback.
	 */
	public static function add_actions() {
		add_action( 'wp_ajax_rwmb_get_terms', array( __CLASS__, 'ajax_get_terms' ) );
		add_action( 'wp_ajax_nopriv_rwmb_get_terms', array( __CLASS__, 'ajax_get_terms' ) );
	}

	/**
	 * Query terms via ajax.
	 */
	public static function ajax_get_terms() {
		check_ajax_referer( 'query' );

		$request = rwmb_request();

		$field = $request->filter_post( 'field', FILTER_DEFAULT, FILTER_FORCE_ARRAY );

		// Required for 'choice_label' filter. See self::filter().
		$field['clone']        = false;
		$field['_original_id'] = $field['id'];

		// Search.
		$field['query_args']['name__like'] = $request->filter_post( 'term', FILTER_SANITIZE_STRING );

		// Pagination.
		$limit = isset( $field['query_args']['number'] ) ? (int) $field['query_args']['number'] : 0;
		if ( 'query:append' === $request->filter_post( '_type', FILTER_SANITIZE_STRING ) ) {
			$page                          = $request->filter_post( 'page', FILTER_SANITIZE_NUMBER_INT );
			$field['query_args']['offset'] = $limit * ( $page - 1 );
		}

		// Query the database.
		$items = self::query( null, $field );
		$items = array_values( $items );

		$data = array( 'items' => $items );

		// More items for pagination.
		if ( $limit && count( $items ) === $limit ) {
			$data['more'] = true;
		}

		wp_send_json_success( $data );
	}

	/**
	 * Add default value for 'taxonomy' field.
	 *
	 * @param array $field Field parameters.
	 * @return array
	 */
	public static function normalize( $field ) {
		// Backwards compatibility with field args.
		if ( isset( $field['options']['args'] ) ) {
			$field['query_args'] = $field['options']['args'];
		}
		if ( isset( $field['options']['taxonomy'] ) ) {
			$field['taxonomy'] = $field['options']['taxonomy'];
		}
		if ( isset( $field['options']['type'] ) ) {
			$field['field_type'] = $field['options']['type'];
		}

		// Set default field args.
		$field = wp_parse_args(
			$field,
			array(
				'taxonomy'       => 'category',
				'query_args'     => array(),
				'remove_default' => false,
			)
		);

		// Force taxonomy to be an array.
		$field['taxonomy'] = (array) $field['taxonomy'];

		/*
		 * Set default placeholder:
		 * - If multiple taxonomies: show 'Select a term'.
		 * - If single taxonomy: show 'Select a %taxonomy_name%'.
		 */
		$placeholder   =esc_html__( 'Select a term', 'meta-box' );
		$taxonomy_name = self::get_taxonomy_singular_name( $field );
		if ( $taxonomy_name ) {
			// Translators: %s is the taxonomy singular label.
			$placeholder = sprintf(esc_html__( 'Select a %s', 'meta-box' ), strtolower( $taxonomy_name ) );
		}
		$field = wp_parse_args(
			$field,
			array(
				'placeholder' => $placeholder,
			)
		);

		$field = parent::normalize( $field );

		// Set default query args.
		$limit               = $field['ajax'] ? 10 : 0;
		$field['query_args'] = wp_parse_args(
			$field['query_args'],
			array(
				'taxonomy' => $field['taxonomy'],
				'number'   => $limit,
			)
		);

		parent::set_ajax_params( $field );

		// Prevent cloning for taxonomy field, not for child fields (taxonomy_advanced).
		if ( 'taxonomy' === $field['type'] ) {
			$field['clone'] = false;
		}

		return $field;
	}

	/**
	 * Query terms for field options.
	 *
	 * @param  array $meta  Saved meta value.
	 * @param  array $field Field settings.
	 * @return array        Field options array.
	 */
	public static function query( $meta, $field ) {
		$args = wp_parse_args(
			$field['query_args'],
			array(
				'hide_empty'             => false,
				'count'                  => false,
				'update_term_meta_cache' => false,
			)
		);

		// Query only selected items.
		if ( ! empty( $field['ajax'] ) && ! empty( $meta ) ) {
			$args['include'] = $meta;
		}

		$terms = get_terms( $args );
		if ( ! is_array( $terms ) ) {
			return array();
		}
		$options = array();
		foreach ( $terms as $term ) {
			$options[ $term->term_id ] = array(
				'value'  => $term->term_id,
				'label'  => self::filter( 'choice_label', $term->name, $field, $term ),
				'parent' => $term->parent,
			);
		}
		return $options;
	}

	/**
	 * Get meta values to save.
	 *
	 * @param mixed $new     The submitted meta value.
	 * @param mixed $old     The existing meta value.
	 * @param int   $post_id The post ID.
	 * @param array $field   The field parameters.
	 *
	 * @return array
	 */
	public static function value( $new, $old, $post_id, $field ) {
		$new   = (array) $new;
		$new[] = self::add_term( $field );
		$new   = array_unique( array_map( 'intval', array_filter( $new ) ) );

		return $new;
	}

	/**
	 * Save meta value.
	 *
	 * @param mixed $new     The submitted meta value.
	 * @param mixed $old     The existing meta value.
	 * @param int   $post_id The post ID.
	 * @param array $field   The field parameters.
	 */
	public static function save( $new, $old, $post_id, $field ) {
		if ( empty( $field['id'] ) || ! $field['save_field'] ) {
			return;
		}

		foreach ( $field['taxonomy'] as $taxonomy ) {
			wp_set_object_terms( $post_id, $new, $taxonomy );
		}
	}

	/**
	 * Add new terms if users created some.
	 *
	 * @param array $field Field settings.
	 * @return int|null Term ID if added successfully, null otherwise.
	 */
	protected static function add_term( $field ) {
		$term = rwmb_request()->post( $field['id'] . '_new' );
		if ( ! $field['add_new'] || ! $term || 1 !== count( $field['taxonomy'] ) ) {
			return null;
		}

		$taxonomy = reset( $field['taxonomy'] );
		$term     = wp_insert_term( $term, $taxonomy );

		return isset( $term['term_id'] ) ? $term['term_id'] : null;
	}

	/**
	 * Get raw meta value.
	 *
	 * @param int   $object_id Object ID.
	 * @param array $field     Field parameters.
	 * @param array $args      Arguments of {@see rwmb_meta()} helper.
	 *
	 * @return mixed
	 */
	public static function raw_meta( $object_id, $field, $args = array() ) {
		if ( empty( $field['id'] ) ) {
			return '';
		}

		$meta = wp_get_object_terms(
			$object_id,
			$field['taxonomy'],
			array(
				'orderby' => 'term_order',
			)
		);
		if ( is_wp_error( $meta ) ) {
			return '';
		}
		$meta = wp_list_pluck( $meta, 'term_id' );

		return $field['multiple'] ? $meta : reset( $meta );
	}

	/**
	 * Get the field value.
	 * Return list of post term objects.
	 *
	 * @param  array    $field   Field parameters.
	 * @param  array    $args    Additional arguments.
	 * @param  int|null $post_id Post ID. null for current post. Optional.
	 *
	 * @return array List of post term objects.
	 */
	public static function get_value( $field, $args = array(), $post_id = null ) {
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}
		$value = wp_get_object_terms(
			$post_id,
			$field['taxonomy'],
			array(
				'orderby' => 'term_order',
			)
		);

		// Get single value if necessary.
		if ( ! $field['clone'] && ! $field['multiple'] && is_array( $value ) ) {
			$value = reset( $value );
		}
		return $value;
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
		return sprintf(
			'<a href="%s" title="%s">%s</a>',
			// @codingStandardsIgnoreLine
			esc_url( get_term_link( $value ) ),
			esc_attr( $value->name ),
			esc_html( $value->name )
		);
	}

	/**
	 * Render "Add New" form
	 *
	 * @param array $field Field settings.
	 * @return string
	 */
	public static function add_new_form( $field ) {
		// Only add new term if field has only one taxonomy.
		if ( 1 !== count( $field['taxonomy'] ) ) {
			return '';
		}

		$taxonomy        = reset( $field['taxonomy'] );
		$taxonomy_object = get_taxonomy( $taxonomy );
		if ( false === $taxonomy_object ) {
			return '';
		}

		$html = '
		<div class="rwmb-taxonomy-add">
			<button class="rwmb-taxonomy-add-button">%s</button>
			<div class="rwmb-taxonomy-add-form rwmb-hidden">
				<input type="text" name="%s_new" size="30" placeholder="%s">
			</div>
		</div>';

		$html = sprintf(
			$html,
			esc_html( $taxonomy_object->labels->add_new_item ),
			esc_attr( $field['id'] ),
			esc_attr( $taxonomy_object->labels->new_item_name )
		);

		return $html;
	}

	/**
	 * Enqueue scripts and styles.
	 */
	public static function admin_enqueue_scripts() {
		parent::admin_enqueue_scripts();
		wp_enqueue_style( 'rwmb-taxonomy', RWMB_CSS_URL . 'taxonomy.css', array(), RWMB_VER );
		wp_enqueue_script( 'rwmb-taxonomy', RWMB_JS_URL . 'taxonomy.js', array( 'jquery' ), RWMB_VER, true );

		// Field is the 1st param.
		$args  = func_get_args();
		$field = $args[0];
		self::remove_default_meta_box( $field );
	}

	/**
	 * Remove default WordPress taxonomy meta box.
	 *
	 * @param array $field Field settings.
	 */
	protected static function remove_default_meta_box( $field ) {
		if ( empty( $field['remove_default'] ) || ! function_exists( 'remove_meta_box' ) ) {
			return;
		}
		foreach ( $field['taxonomy'] as $taxonomy ) {
			$id = is_taxonomy_hierarchical( $taxonomy ) ? "{$taxonomy}div" : "tagsdiv-{$taxonomy}";
			remove_meta_box( $id, null, 'side' );
		}
	}

	/**
	 * Get taxonomy singular name.
	 *
	 * @param array $field Field settings.
	 * @return string
	 */
	protected static function get_taxonomy_singular_name( $field ) {
		if ( 1 !== count( $field['taxonomy'] ) ) {
			return '';
		}
		$taxonomy        = reset( $field['taxonomy'] );
		$taxonomy_object = get_taxonomy( $taxonomy );

		return false === $taxonomy_object ? '' : $taxonomy_object->labels->singular_name;
	}
}
