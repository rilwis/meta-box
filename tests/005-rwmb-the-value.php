<?php
/**
 * This file test the helper function rwmb_the_value with the optimization in "helper" branch since 4.8.7
 */
global $test;
add_filter(
	'rwmb_meta_boxes',
	function ( $meta_boxes ) {
		global $test;
		$prefix = '';
		$test   = array(
			'title'  =>esc_html__( 'Test helper functions', 'your-prefix' ),
			'fields' => array(
				array(
					'id'   => 'checkbox',
					'name' =>esc_html__( 'Checkbox', 'your-prefix' ),
					'type' => 'checkbox',
				),
				array(
					'id'   => 'color',
					'name' =>esc_html__( 'Color', 'your-prefix' ),
					'type' => 'color',
				),
				array(
					'id'    => 'color2',
					'name'  =>esc_html__( 'Color clone', 'your-prefix' ),
					'type'  => 'color',
					'clone' => true,
				),

				// Object choice
				array(
					'name'       =>esc_html__( 'Post', 'your-prefix' ),
					'id'         => 'post',
					'type'       => 'post',
					'post_type'  => 'post',
					'field_type' => 'select_advanced',
				),
				array(
					'name'       =>esc_html__( 'Post Multiple', 'your-prefix' ),
					'id'         => 'post2',
					'type'       => 'post',
					'multiple'   => true,
					'post_type'  => 'post',
					'field_type' => 'select_advanced',
				),
				array(
					'name'       =>esc_html__( 'Post Clone', 'your-prefix' ),
					'id'         => 'post3',
					'type'       => 'post',
					'clone'      => true,
					'post_type'  => 'post',
					'field_type' => 'select_advanced',
				),
				array(
					'name'       =>esc_html__( 'Post Clone and Multiple', 'your-prefix' ),
					'id'         => 'post4',
					'type'       => 'post',
					'clone'      => true,
					'multiple'   => true,
					'post_type'  => 'post',
					'field_type' => 'select_advanced',
				),

				// Checkbox list
				array(
					'name'    => esc_html__( 'Checkbox list', 'your-prefix' ),
					'id'      => "{$prefix}checkbox_list",
					'type'    => 'checkbox_list',
					'options' => array(
						'value1' => esc_html__( 'Label1', 'your-prefix' ),
						'value2' => esc_html__( 'Label2', 'your-prefix' ),
					),
				),
				array(
					'name'    => esc_html__( 'Checkbox list clone', 'your-prefix' ),
					'id'      => "{$prefix}checkbox_list2",
					'type'    => 'checkbox_list',
					'clone'   => true,
					'options' => array(
						'value1' => esc_html__( 'Label1', 'your-prefix' ),
						'value2' => esc_html__( 'Label2', 'your-prefix' ),
					),
				),

				// Radio
				array(
					'name'    =>esc_html__( 'Radio', 'your-prefix' ),
					'id'      => 'radio',
					'type'    => 'radio',
					'options' => array(
						'value1' =>esc_html__( 'Label1', 'your-prefix' ),
						'value2' =>esc_html__( 'Label2', 'your-prefix' ),
					),
				),
				array(
					'name'    =>esc_html__( 'Radio clone', 'your-prefix' ),
					'id'      => 'radio2',
					'type'    => 'radio',
					'clone'   => true,
					'options' => array(
						'value1' =>esc_html__( 'Label1', 'your-prefix' ),
						'value2' =>esc_html__( 'Label2', 'your-prefix' ),
					),
				),

				// oembed
				array(
					'id'   => 'oembed',
					'name' =>esc_html__( 'oEmbed', 'your-prefix' ),
					'type' => 'oembed',
				),
				array(
					'id'    => 'oembed2',
					'name'  =>esc_html__( 'oEmbed clone', 'your-prefix' ),
					'type'  => 'oembed',
					'clone' => true,
				),

				// Image select
				array(
					'id'      => 'image_select',
					'name'    =>esc_html__( 'Image Select', 'your-prefix' ),
					'type'    => 'image_select',
					'options' => array(
						'left'  => 'http://placehold.it/90x90&text=Left',
						'right' => 'http://placehold.it/90x90&text=Right',
						'none'  => 'http://placehold.it/90x90&text=None',
					),
				),
				array(
					'id'       => 'image_select2',
					'name'     =>esc_html__( 'Image Select Multiple', 'your-prefix' ),
					'type'     => 'image_select',
					'options'  => array(
						'left'  => 'http://placehold.it/90x90&text=Left',
						'right' => 'http://placehold.it/90x90&text=Right',
						'none'  => 'http://placehold.it/90x90&text=None',
					),
					'multiple' => true,
				),
				array(
					'id'      => 'image_select3',
					'name'    =>esc_html__( 'Image Select Clone', 'your-prefix' ),
					'type'    => 'image_select',
					'options' => array(
						'left'  => 'http://placehold.it/90x90&text=Left',
						'right' => 'http://placehold.it/90x90&text=Right',
						'none'  => 'http://placehold.it/90x90&text=None',
					),
					'clone'   => true,
				),

				// Autocomplete
				array(
					'name'    => esc_html__( 'Autocomplete', 'your-prefix' ),
					'id'      => "{$prefix}autocomplete",
					'type'    => 'autocomplete',
					'options' => array(
						'value1' => esc_html__( 'Label1', 'your-prefix' ),
						'value2' => esc_html__( 'Label2', 'your-prefix' ),
					),
				),

				// Key-value
				array(
					'id'   => 'key_value',
					'name' =>esc_html__( 'Key Value', 'your-prefix' ),
					'type' => 'key_value',
					'desc' =>esc_html__( 'Add more additional info below:', 'your-prefix' ),
				),

				// Fieldset text
				array(
					'id'      => 'fieldset_text',
					'name'    =>esc_html__( 'Fieldset Text', 'your-prefix' ),
					'type'    => 'fieldset_text',
					'desc'    =>esc_html__( 'Please enter following details:', 'your-prefix' ),
					'options' => array(
						'name'    =>esc_html__( 'Name', 'your-prefix' ),
						'address' =>esc_html__( 'Address', 'your-prefix' ),
						'email'   =>esc_html__( 'Email', 'your-prefix' ),
					),
				),
				array(
					'id'      => 'fieldset_text2',
					'name'    =>esc_html__( 'Fieldset Text Clone', 'your-prefix' ),
					'type'    => 'fieldset_text',
					'desc'    =>esc_html__( 'Please enter following details:', 'your-prefix' ),
					'options' => array(
						'name'    =>esc_html__( 'Name', 'your-prefix' ),
						'address' =>esc_html__( 'Address', 'your-prefix' ),
						'email'   =>esc_html__( 'Email', 'your-prefix' ),
					),
					'clone'   => true,
				),

				// Text list
				array(
					'id'      => 'text_list',
					'name'    =>esc_html__( 'Text List', 'your-prefix' ),
					'type'    => 'text_list',
					'options' => array(
						'John Smith'      =>esc_html__( 'Name', 'your-prefix' ),
						'name@domain.com' =>esc_html__( 'Email', 'your-prefix' ),
					),
				),
				array(
					'id'      => 'text_list2',
					'name'    =>esc_html__( 'Text List Clone', 'your-prefix' ),
					'type'    => 'text_list',
					'options' => array(
						'John Smith'      =>esc_html__( 'Name', 'your-prefix' ),
						'name@domain.com' =>esc_html__( 'Email', 'your-prefix' ),
					),
					'clone'   => true,
				),

				// File
				array(
					'id'   => 'file',
					'name' =>esc_html__( 'File', 'your-prefix' ),
					'type' => 'file',
				),
				array(
					'id'   => 'fileadv',
					'name' =>esc_html__( 'File Advanced', 'your-prefix' ),
					'type' => 'file_advanced',
				),
				array(
					'id'    => 'fileadv2',
					'name'  =>esc_html__( 'File Advanced Clone', 'your-prefix' ),
					'type'  => 'file_advanced',
					'clone' => true,
				),

				// Image
				array(
					'id'   => 'image',
					'name' =>esc_html__( 'Image', 'your-prefix' ),
					'type' => 'image',
				),
				array(
					'id'   => 'image_advanced',
					'name' =>esc_html__( 'Image Advanced', 'your-prefix' ),
					'type' => 'image_advanced',
				),
				array(
					'id'    => 'image_advanced2',
					'name'  =>esc_html__( 'Image Advanced Clone', 'your-prefix' ),
					'type'  => 'image_advanced',
					'clone' => true,
				),
			),
		);
		$meta_boxes[] = $test;
		return $meta_boxes;
	}
);

add_filter(
	'the_content',
	function ( $content ) {
		global $test;
		$output = '';
		foreach ( $test['fields'] as $field ) {
			$output .= '<p><strong>Field ' . $field['name'] . ':</strong> ' . rwmb_the_value( $field['id'], '', null, false ) . '</p>';
		}
		return $output . $content;
	}
);
