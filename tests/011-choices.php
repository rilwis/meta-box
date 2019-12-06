<?php
add_filter( 'rwmb_meta_boxes', 'your_prefix_choice_demo' );
function your_prefix_choice_demo( $meta_boxes ) {
	$prefix       = '';
	$meta_boxes[] = array(
		'title'  =>esc_html__( 'Hierarchical Options', 'your-prefix' ),
		'fields' => array(
			array(
				'id'      => $prefix . 'checkbox_list',
				'name'    =>esc_html__( 'Flat Checkbox', 'your-prefix' ),
				'type'    => 'checkbox_list',
				// Old options syntax
				'options' => array(
					'option1' => 'Option 1',
					'option2' => 'Option 2',
					'option3' => 'Option 3',
				),
			),

			array(
				'id'       => $prefix . 'checkbox_tree',
				'name'     =>esc_html__( 'Hierarchical Checkbox', 'your-prefix' ),
				'type'     => 'checkbox_list',
				// New options syntax.  Supports hierarchical options with addition of parent
				'options'  => array(
					array(
						'value' => 'option1',
						'label' => 'Option 1',
					),
					array(
						'value' => 'option2',
						'label' => 'Option 2',
					),
					array(
						'value' => 'option3',
						'label' => 'Option 3',
					),
					array(
						'value'  => 'suboption11',
						'label'  => 'SubOption 1',
						'parent' => 'option1',
					),
					array(
						'value'  => 'suboption12',
						'label'  => 'SubOption 2',
						'parent' => 'option1',
					),
					array(
						'value'  => 'suboption13',
						'label'  => 'SubOption 3',
						'parent' => 'option2',
					),
				),
				'collapse' => true,
				'flatten'  => false,
			),

			array(
				'id'      => $prefix . 'select',
				'name'    =>esc_html__( 'Hierarchical select', 'your-prefix' ),
				'type'    => 'select',
				'options' => array(
					array(
						'value' => 'option1',
						'label' => 'Option 1',
					),
					array(
						'value' => 'option2',
						'label' => 'Option 2',
					),
					array(
						'value' => 'option3',
						'label' => 'Option 3',
					),
					array(
						'value'  => 'suboption11',
						'label'  => 'SubOption 1',
						'parent' => 'option1',
					),
					array(
						'value'  => 'suboption12',
						'label'  => 'SubOption 2',
						'parent' => 'option1',
					),
					array(
						'value'  => 'suboption13',
						'label'  => 'SubOption 3',
						'parent' => 'option2',
					),
				),
				'flatten' => false,
			),
		),
	);
	return $meta_boxes;
}
