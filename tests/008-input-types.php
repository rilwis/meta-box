<?php
add_filter(
	'rwmb_meta_boxes',
	function ( $meta_boxes ) {
		$meta_boxes[] = [
			'title'  => 'Existing fields',
			'fields' => [
				[
					'name' => 'Checkbox',
					'id'   => 'checkbox',
					'type' => 'checkbox',
				],
				[
					'name' => 'Hidden',
					'id'   => 'hidden',
					'type' => 'hidden',
				],
				[
					'name' => 'Number',
					'id'   => 'number',
					'type' => 'number',
				],
				[
					'name' => 'Range',
					'id'   => 'range',
					'type' => 'range',
				],
				[
					'name' => 'Text',
					'id'   => 'text',
					'type' => 'text',
				],
				[
					'name' => 'Color',
					'id'   => 'color',
					'type' => 'color',
				],
				[
					'name' => 'Datetime',
					'id'   => 'datetime',
					'type' => 'datetime',
				],
				[
					'name' => 'Date',
					'id'   => 'date',
					'type' => 'date',
				],
				[
					'name' => 'Time',
					'id'   => 'time',
					'type' => 'time',
				],
				[
					'name' => 'Email',
					'id'   => 'email',
					'type' => 'email',
				],
				[
					'name'    => 'Fieldset Text',
					'id'      => 'fieldset_text',
					'type'    => 'fieldset_text',
					'options' => array(
						'name'    =>esc_html__( 'Name', 'your-prefix' ),
						'address' =>esc_html__( 'Address', 'your-prefix' ),
						'email'   =>esc_html__( 'Email', 'your-prefix' ),
					),
				],
				[
					'name' => 'Key Value',
					'id'   => 'key_value',
					'type' => 'key_value',
				],
				[
					'name' => 'URL',
					'id'   => 'url',
					'type' => 'url',
				],
				[
					'name' => 'oEmbed',
					'id'   => 'oembed',
					'type' => 'oembed',
				],
				[
					'name' => 'Password',
					'id'   => 'password',
					'type' => 'password',
				],
			],
		];
		// http://html5doctor.com/html5-forms-input-types/
		$meta_boxes[] = [
			'title'  => 'HTML5 new input fields',
			'fields' => [
				[
					'name' => 'Tel',
					'id'   => 'tel',
					'type' => 'tel',
				],
				[
					'name' => 'Search',
					'id'   => 'search',
					'type' => 'search',
				],
				[
					'name' => 'Month',
					'id'   => 'month',
					'type' => 'month',
				],
				[
					'name' => 'Week',
					'id'   => 'week',
					'type' => 'week',
				],
				[
					'name' => 'Datetime Local',
					'id'   => 'datetime-local',
					'type' => 'datetime-local',
				],
			],
		];
		return $meta_boxes;
	}
);
