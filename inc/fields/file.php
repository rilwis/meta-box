<?php
/**
 * The file upload file which allows users to upload files via the default HTML <input type="file">.
 *
 * @package Meta Box
 */

/**
 * File field class which uses HTML <input type="file"> to upload file.
 */
class RWMB_File_Field extends RWMB_Field {
	/**
	 * Enqueue scripts and styles.
	 */
	public static function admin_enqueue_scripts() {
		wp_enqueue_style( 'rwmb-file', RWMB_CSS_URL . 'file.css', array(), RWMB_VER );
		wp_enqueue_script( 'rwmb-file', RWMB_JS_URL . 'file.js', array( 'jquery-ui-sortable' ), RWMB_VER, true );

		RWMB_Helpers_Field::localize_script_once(
			'rwmb-file',
			'rwmbFile',
			array(
				// Translators: %d is the number of files in singular form.
				'maxFileUploadsSingle' =>esc_html__( 'You may only upload maximum %d file', 'meta-box' ),
				// Translators: %d is the number of files in plural form.
				'maxFileUploadsPlural' =>esc_html__( 'You may only upload maximum %d files', 'meta-box' ),
			)
		);
	}

	/**
	 * Add custom actions.
	 */
	public static function add_actions() {
		add_action( 'post_edit_form_tag', array( __CLASS__, 'post_edit_form_tag' ) );
		add_action( 'wp_ajax_rwmb_delete_file', array( __CLASS__, 'ajax_delete_file' ) );
	}

	/**
	 * Add data encoding type for file uploading
	 */
	public static function post_edit_form_tag() {
		echo ' enctype="multipart/form-data"';
	}

	/**
	 * Ajax callback for deleting files.
	 */
	public static function ajax_delete_file() {
		$request = rwmb_request();

		$field_id = $request->filter_post( 'field_id', FILTER_SANITIZE_STRING );
		check_ajax_referer( "rwmb-delete-file_{$field_id}" );

		// Make sure the file to delete is in the custom field.
		$attachment  = $request->post( 'attachment_id' );
		$object_id   = $request->filter_post( 'object_id', FILTER_SANITIZE_STRING );
		$object_type = $request->filter_post( 'object_type', FILTER_SANITIZE_STRING );
		$field       = rwmb_get_field_settings( $field_id, array( 'object_type' => $object_type ), $object_id );
		$field_value = self::raw_meta( $object_id, $field );
		$field_value = $field['clone'] ? call_user_func_array( 'array_merge', $field_value ) : $field_value;
		if ( ! in_array( $attachment, $field_value ) ) {
			wp_send_json_error(esc_html__( 'Error: Invalid file', 'meta-box' ) );
		}

		// Delete the file.
		if ( is_numeric( $attachment ) ) {
			$result = wp_delete_attachment( $attachment );
		} else {
			$path   = str_replace( home_url( '/' ), trailingslashit( ABSPATH ), $attachment );
			$result = unlink( $path );
		}

		if ( $result ) {
			wp_send_json_success();
		}
		wp_send_json_error(esc_html__( 'Error: Cannot delete file', 'meta-box' ) );
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
		$meta      = array_filter( (array) $meta );
		$i18n_more = apply_filters( 'rwmb_file_add_string',esc_html_x( '+ Add new file', 'file upload', 'meta-box' ), $field );
		$html      = self::get_uploaded_files( $meta, $field );

		// Show form upload.
		$attributes          = self::get_attributes( $field, $meta );
		$attributes['type']  = 'file';
		$attributes['name']  = "{$field['input_name']}[]";
		$attributes['class'] = 'rwmb-file-input';

		/*
		 * Use JavaScript to toggle 'required' attribute, because:
		 * - Field might already have value (uploaded files).
		 * - Be able to detect when uploading multiple files.
		 */
		if ( $attributes['required'] ) {
			$attributes['data-required'] = 1;
			$attributes['required']      = false;
		}

		// Upload new files.
		$html .= sprintf(
			'<div class="rwmb-file-new"><input %s>',
			self::render_attributes( $attributes )
		);
		if ( 1 !== $field['max_file_uploads'] ) {
			$html .= sprintf(
				'<a class="rwmb-file-add" href="#"><strong>%s</strong></a>',
				$i18n_more
			);
		}
		$html .= '</div>';

		$html .= sprintf(
			'<input type="hidden" class="rwmb-file-index" name="%s" value="%s">',
			$field['index_name'],
			$field['input_name']
		);

		return $html;
	}

	/**
	 * Get HTML for uploaded files.
	 *
	 * @param array $files List of uploaded files.
	 * @param array $field Field parameters.
	 * @return string
	 */
	protected static function get_uploaded_files( $files, $field ) {
		$reorder_nonce = wp_create_nonce( "rwmb-reorder-files_{$field['id']}" );
		$delete_nonce  = wp_create_nonce( "rwmb-delete-file_{$field['id']}" );
		$output        = '';

		foreach ( (array) $files as $k => $file ) {
			// Ignore deleted files (if users accidentally deleted files or uses `force_delete` without saving post).
			if ( get_attached_file( $file ) || $field['upload_dir'] ) {
				$output .= self::call( $field, 'file_html', $file, $k );
			}
		}

		return sprintf(
			'<ul class="rwmb-uploaded" data-field_id="%s" data-delete_nonce="%s" data-reorder_nonce="%s" data-force_delete="%s" data-max_file_uploads="%s" data-mime_type="%s">%s</ul>',
			$field['id'],
			$delete_nonce,
			$reorder_nonce,
			$field['force_delete'] ? 1 : 0,
			$field['max_file_uploads'],
			$field['mime_type'],
			$output
		);
	}

	/**
	 * Get HTML for uploaded file.
	 *
	 * @param int   $file  Attachment (file) ID.
	 * @param int   $index File index.
	 * @param array $field Field data.
	 * @return string
	 */
	protected static function file_html( $file, $index, $field ) {
		$i18n_delete = apply_filters( 'rwmb_file_delete_string',esc_html_x( 'Delete', 'file upload', 'meta-box' ) );
		$i18n_edit   = apply_filters( 'rwmb_file_edit_string',esc_html_x( 'Edit', 'file upload', 'meta-box' ) );
		$attributes  = self::get_attributes( $field, $file );

		if ( ! $file ) {
			return;
		}

		if ( $field['upload_dir'] ) {
			$data = self::file_info_custom_dir( $file, $field );
		} else {
			$data      = array(
				'icon'      => wp_get_attachment_image( $file, array( 60, 60 ), true ),
				'name'      => basename( get_attached_file( $file ) ),
				'url'       => wp_get_attachment_url( $file ),
				'title'     => get_the_title( $file ),
				'edit_link' => '',
			);
			$edit_link = get_edit_post_link( $file );
			if ( $edit_link ) {
				$data['edit_link'] = sprintf( '<a href="%s" class="rwmb-file-edit" target="_blank"><span class="dashicons dashicons-edit"></span>%s</a>', $edit_link, $i18n_edit );
			}
		}

		return sprintf(
			'<li class="rwmb-file">
				<div class="rwmb-file-icon"><a href="%s" target="_blank">%s</a></div>
				<div class="rwmb-file-info">
					<a href="%s" target="_blank" class="rwmb-file-title">%s</a>
					<p class="rwmb-file-name">%s</p>
					<p class="rwmb-file-actions">
						%s
						<a href="#" class="rwmb-file-delete" data-attachment_id="%s"><span class="dashicons dashicons-no-alt"></span>%s</a>
					</p>
				</div>
				<input type="hidden" name="%s[%s]" value="%s">
			</li>',
			$data['url'],
			$data['icon'],
			$data['url'],
			$data['title'],
			$data['name'],
			$data['edit_link'],
			$file,
			$i18n_delete,
			$attributes['name'],
			$index,
			$file
		);
	}

	/**
	 * Get file data uploaded to custom directory.
	 *
	 * @param string $file  URL to uploaded file.
	 * @param array  $field Field settings.
	 * @return string
	 */
	protected static function file_info_custom_dir( $file, $field ) {
		$path     = wp_normalize_path( trailingslashit( $field['upload_dir'] ) . basename( $file ) );
		$ext      = pathinfo( $path, PATHINFO_EXTENSION );
		$icon_url = wp_mime_type_icon( wp_ext2type( $ext ) );
		$data     = array(
			'icon'      => '<img width="48" height="64" src="' . esc_url( $icon_url ) . '" alt="">',
			'name'      => basename( $path ),
			'path'      => $path,
			'url'       => $file,
			'title'     => preg_replace( '/\.[^.]+$/', '', basename( $path ) ),
			'edit_link' => '',
		);
		return $data;
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
		$input = isset( $field['index'] ) ? $field['index'] : $field['input_name'];

		// @codingStandardsIgnoreLine
		if ( empty( $input ) || empty( $_FILES[ $input ] ) ) {
			return $new;
		}

		$new = array_filter( (array) $new );

		$count = self::transform( $input );
		for ( $i = 0; $i <= $count; $i ++ ) {
			$attachment = self::handle_upload( "{$input}_{$i}", $post_id, $field );
			if ( $attachment && ! is_wp_error( $attachment ) ) {
				$new[] = $attachment;
			}
		}

		return $new;
	}

	/**
	 * Get meta values to save for cloneable fields.
	 *
	 * @param array $new         The submitted meta value.
	 * @param array $old         The existing meta value.
	 * @param int   $object_id   The object ID.
	 * @param array $field       The field settings.
	 * @param array $data_source Data source. Either $_POST or custom array. Used in group to get uploaded files.
	 *
	 * @return mixed
	 */
	public static function clone_value( $new, $old, $object_id, $field, $data_source = null ) {
		if ( ! $data_source ) {
			// @codingStandardsIgnoreLine
			$data_source = $_POST;
		}

		// @codingStandardsIgnoreLine
		$indexes = isset( $data_source[ "_index_{$field['id']}" ] ) ? $data_source[ "_index_{$field['id']}" ] : array();
		foreach ( $indexes as $key => $index ) {
			$field['index'] = $index;

			$old_value   = isset( $old[ $key ] ) ? $old[ $key ] : array();
			$value       = isset( $new[ $key ] ) ? $new[ $key ] : array();
			$value       = self::value( $value, $old_value, $object_id, $field );
			$new[ $key ] = self::filter( 'sanitize', $value, $field, $old_value, $object_id );
		}

		return $new;
	}

	/**
	 * Handle file upload.
	 * Consider upload to Media Library or custom folder.
	 *
	 * @param string $file_id File ID in $_FILES when uploading.
	 * @param int    $post_id Post ID.
	 * @param array  $field   Field settings.
	 *
	 * @return \WP_Error|int|string WP_Error if has error, attachment ID if upload in Media Library, URL to file if upload to custom folder.
	 */
	protected static function handle_upload( $file_id, $post_id, $field ) {
		return $field['upload_dir'] ? self::handle_upload_custom_dir( $file_id, $field ) : media_handle_upload( $file_id, $post_id );
	}

	/**
	 * Transform $_FILES from $_FILES['field']['key']['index'] to $_FILES['field_index']['key'].
	 *
	 * @param string $input_name The field input name.
	 *
	 * @return int The number of uploaded files.
	 */
	protected static function transform( $input_name ) {
		// @codingStandardsIgnoreStart
		foreach ( $_FILES[ $input_name ] as $key => $list ) {
			foreach ( $list as $index => $value ) {
				$file_key = "{$input_name}_{$index}";
				if ( ! isset( $_FILES[ $file_key ] ) ) {
					$_FILES[ $file_key ] = array();
				}
				$_FILES[ $file_key ][ $key ] = $value;
			}
		}

		return count( $_FILES[ $input_name ]['name'] );
		// @codingStandardsIgnoreEnd
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
				'std'              => array(),
				'force_delete'     => false,
				'max_file_uploads' => 0,
				'mime_type'        => '',
				'upload_dir'       => '',
			)
		);

		$field['multiple']   = true;
		$field['input_name'] = "_file_{$field['id']}";
		$field['index_name'] = "_index_{$field['id']}";

		return $field;
	}

	/**
	 * Get the field value. Return meaningful info of the files.
	 *
	 * @param  array    $field   Field parameters.
	 * @param  array    $args    Not used for this field.
	 * @param  int|null $post_id Post ID. null for current post. Optional.
	 *
	 * @return mixed Full info of uploaded files
	 */
	public static function get_value( $field, $args = array(), $post_id = null ) {
		$value = parent::get_value( $field, $args, $post_id );
		if ( ! $field['clone'] ) {
			$value = self::call( 'files_info', $field, $value, $args );
		} else {
			$return = array();
			foreach ( $value as $subvalue ) {
				$return[] = self::call( 'files_info', $field, $subvalue, $args );
			}
			$value = $return;
		}
		if ( isset( $args['limit'] ) ) {
			$value = array_slice( $value, 0, intval( $args['limit'] ) );
		}
		return $value;
	}

	/**
	 * Get uploaded files information.
	 *
	 * @param array $field Field parameters.
	 * @param array $files Files IDs.
	 * @param array $args  Additional arguments (for image size).
	 * @return array
	 */
	public static function files_info( $field, $files, $args ) {
		$return = array();
		foreach ( (array) $files as $file ) {
			$info = self::call( $field, 'file_info', $file, $args );
			if ( $info ) {
				$return[ $file ] = $info;
			}
		}
		return $return;
	}

	/**
	 * Get uploaded file information.
	 *
	 * @param int   $file  Attachment file ID (post ID). Required.
	 * @param array $args  Array of arguments (for size).
	 * @param array $field Field settings.
	 *
	 * @return array|bool False if file not found. Array of (id, name, path, url) on success.
	 */
	public static function file_info( $file, $args = array(), $field = array() ) {
		if ( $field['upload_dir'] ) {
			return self::file_info_custom_dir( $file, $field );
		}

		$path = get_attached_file( $file );
		if ( ! $path ) {
			return false;
		}

		return wp_parse_args(
			array(
				'ID'    => $file,
				'name'  => basename( $path ),
				'path'  => $path,
				'url'   => wp_get_attachment_url( $file ),
				'title' => get_the_title( $file ),
			),
			wp_get_attachment_metadata( $file )
		);
	}

	/**
	 * Format a single value for the helper functions. Sub-fields should overwrite this method if necessary.
	 *
	 * @param array    $field   Field parameters.
	 * @param array    $value   The value.
	 * @param array    $args    Additional arguments. Rarely used. See specific fields for details.
	 * @param int|null $post_id Post ID. null for current post. Optional.
	 *
	 * @return string
	 */
	public static function format_single_value( $field, $value, $args, $post_id ) {
		return sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( $value['url'] ), esc_html( $value['title'] ) );
	}

	/**
	 * Handle upload for files in custom directory.
	 *
	 * @param string $file_id File ID in $_FILES when uploading.
	 * @param array  $field   Field settings.
	 *
	 * @return string URL to uploaded file.
	 */
	public static function handle_upload_custom_dir( $file_id, $field ) {
		// @codingStandardsIgnoreStart
		if ( empty( $_FILES[ $file_id ] ) ) {
			return;
		}
		$file = $_FILES[ $file_id ];
		// @codingStandardsIgnoreEnd

		// Use a closure to filter upload directory. Requires PHP >= 5.3.0.
		$filter_upload_dir = function( $uploads ) use ( $field ) {
			$uploads['path']    = $field['upload_dir'];
			$uploads['url']     = self::convert_path_to_url( $field['upload_dir'] );
			$uploads['subdir']  = '';
			$uploads['basedir'] = $field['upload_dir'];

			return $uploads;
		};

		// Make sure upload dir is inside WordPress.
		$upload_dir = wp_normalize_path( untrailingslashit( $field['upload_dir'] ) );
		$root       = wp_normalize_path( untrailingslashit( ABSPATH ) );
		if ( 0 !== strpos( $upload_dir, $root ) ) {
			return;
		}

		// Let WordPress handle upload to the custom directory.
		add_filter( 'upload_dir', $filter_upload_dir );
		$file_info = wp_handle_upload( $file, array( 'test_form' => false ) );
		remove_filter( 'upload_dir', $filter_upload_dir );

		return empty( $file_info['url'] ) ? null : $file_info['url'];
	}

	/**
	 * Convert a path to an URL.
	 *
	 * @param string $path Full path to a file or a directory.
	 * @return string URL to the file or directory.
	 */
	public static function convert_path_to_url( $path ) {
		$path          = wp_normalize_path( untrailingslashit( $path ) );
		$root          = wp_normalize_path( untrailingslashit( ABSPATH ) );
		$relative_path = str_replace( $root, '', $path );

		return home_url( $relative_path );
	}
}
