<?php
/**
 * Media field class which users WordPress media popup to upload and select files.
 *
 * @package Meta Box
 */

/**
 * The media field class.
 */
class RWMB_Media_Field extends RWMB_File_Field {
	/**
	 * Enqueue scripts and styles.
	 */
	public static function admin_enqueue_scripts() {
		wp_enqueue_media();
		if ( ! is_admin() ) {
			wp_register_script( 'media-grid', includes_url( 'js/media-grid.min.js' ), array( 'media-editor' ), '4.9.7', true );
		}
		wp_enqueue_style( 'rwmb-media', RWMB_CSS_URL . 'media.css', array(), RWMB_VER );
		wp_enqueue_script( 'rwmb-media', RWMB_JS_URL . 'media.js', array( 'jquery-ui-sortable', 'underscore', 'backbone', 'media-grid' ), RWMB_VER, true );

		RWMB_Helpers_Field::localize_script_once(
			'rwmb-media',
			'i18nRwmbMedia',
			array(
				'add'                => apply_filters( 'rwmb_media_add_string',esc_html_x( '+ Add Media', 'media', 'meta-box' ) ),
				'single'             => apply_filters( 'rwmb_media_single_files_string',esc_html_x( ' file', 'media', 'meta-box' ) ),
				'multiple'           => apply_filters( 'rwmb_media_multiple_files_string',esc_html_x( ' files', 'media', 'meta-box' ) ),
				'remove'             => apply_filters( 'rwmb_media_remove_string',esc_html_x( 'Remove', 'media', 'meta-box' ) ),
				'edit'               => apply_filters( 'rwmb_media_edit_string',esc_html_x( 'Edit', 'media', 'meta-box' ) ),
				'view'               => apply_filters( 'rwmb_media_view_string',esc_html_x( 'View', 'media', 'meta-box' ) ),
				'noTitle'            =>esc_html_x( 'No Title', 'media', 'meta-box' ),
				'loadingUrl'         => admin_url( 'images/spinner.gif' ),
				'extensions'         => self::get_mime_extensions(),
				'select'             => apply_filters( 'rwmb_media_select_string',esc_html_x( 'Select Files', 'media', 'meta-box' ) ),
				'or'                 => apply_filters( 'rwmb_media_or_string',esc_html_x( 'or', 'media', 'meta-box' ) ),
				'uploadInstructions' => apply_filters( 'rwmb_media_upload_instructions_string',esc_html_x( 'Drop files here to upload', 'media', 'meta-box' ) ),
			)
		);
	}

	/**
	 * Add actions.
	 */
	public static function add_actions() {
		$args  = func_get_args();
		$field = reset( $args );
		add_action( 'print_media_templates', array( RWMB_Helpers_Field::get_class( $field ), 'print_templates' ) );
	}

	/**
	 * Get meta value.
	 *
	 * @param int   $post_id Post ID.
	 * @param bool  $saved   Whether the meta box is saved at least once.
	 * @param array $field   Field parameters.
	 *
	 * @return mixed
	 */
	public static function meta( $post_id, $saved, $field ) {
		$meta = parent::meta( $post_id, $saved, $field );

		/*
		 * Update meta cache for all attachments, preparing for getting data for rendering in JS.
		 * This reduces the number of queries for updating all attachments' meta.
		 * @see get_attributes()
		 */
		$ids = (array) $meta;
		if ( $field['clone'] ) {
			foreach ( $ids as &$value ) {
				$value = (array) $value;
			}
			$ids = call_user_func_array( 'array_merge', $ids );
		}
		update_meta_cache( 'post', $ids );

		return $meta;
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
		$attributes = self::call( 'get_attributes', $field, $meta );

		$html = sprintf(
			'<input %s data-options="%s">',
			self::render_attributes( $attributes ),
			esc_attr( wp_json_encode( $field['js_options'] ) )
		);

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
				'std'              => array(),
				'mime_type'        => '',
				'max_file_uploads' => 0,
				'force_delete'     => false,
				'max_status'       => true,
				'js_options'       => array(),
			)
		);

		$field['js_options'] = wp_parse_args(
			$field['js_options'],
			array(
				'mimeType'    => $field['mime_type'],
				'maxFiles'    => $field['max_file_uploads'],
				'forceDelete' => $field['force_delete'] ? true : false,
				'maxStatus'   => $field['max_status'],
			)
		);

		$field['multiple'] = true;

		return $field;
	}

	/**
	 * Get the attributes for a field.
	 *
	 * @param array $field Field parameters.
	 * @param mixed $value Meta value.
	 *
	 * @return array
	 */
	public static function get_attributes( $field, $value = null ) {
		$value = (array) $value;

		$attributes          = parent::get_attributes( $field, $value );
		$attributes['type']  = 'hidden';
		$attributes['name']  = $field['clone'] ? str_replace( '[]', '', $attributes['name'] ) : $attributes['name'];
		$attributes['id']    = false;
		$attributes['value'] = implode( ',', $value );

		// Add attachment details.
		$attachments = array();
		foreach ( $value as $media ) {
			$media = wp_prepare_attachment_for_js( $media );
			// Some themes/plugins add HTML, shortcodes to "compat" attrbute which break JSON validity.
			if ( isset( $media['compat'] ) ) {
				unset( $media['compat'] );
			}
			if ( ! empty( $media ) ) {
				$attachments[] = $media;
			}
		}
		$attachments                    = array_values( $attachments );
		$attributes['data-attachments'] = wp_json_encode( $attachments );

		return $attributes;
	}

	/**
	 * Get supported mime extensions.
	 *
	 * @return array
	 */
	protected static function get_mime_extensions() {
		$mime_types = wp_get_mime_types();
		$extensions = array();
		foreach ( $mime_types as $ext => $mime ) {
			$ext                 = explode( '|', $ext );
			$extensions[ $mime ] = $ext;

			$mime_parts = explode( '/', $mime );
			if ( empty( $extensions[ $mime_parts[0] ] ) ) {
				$extensions[ $mime_parts[0] ] = array();
			}
			$extensions[ $mime_parts[0] ]        = array_merge( $extensions[ $mime_parts[0] ], $ext );
			$extensions[ $mime_parts[0] . '/*' ] = $extensions[ $mime_parts[0] ];
		}

		return $extensions;
	}

	/**
	 * Get meta values to save.
	 *
	 * @param mixed $new     The submitted meta value.
	 * @param mixed $old     The existing meta value.
	 * @param int   $post_id The post ID.
	 * @param array $field   The field parameters.
	 *
	 * @return array|mixed
	 */
	public static function value( $new, $old, $post_id, $field ) {
		$new = RWMB_Helpers_Array::from_csv( $new );
		return array_filter( array_unique( array_map( 'absint', $new ) ) );
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
		$storage = $field['storage'];
		$storage->delete( $post_id, $field['id'] );
		parent::save( $new, array(), $post_id, $field );
	}

	/**
	 * Template for media item.
	 */
	public static function print_templates() {
		require_once RWMB_INC_DIR . 'templates/media.php';
	}
}
