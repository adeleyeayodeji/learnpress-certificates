<?php

/**
 * Class LP_Certificate_Layer
 */
class LP_Certificate_Layer {
	/**
	 * @var null
	 */
	public $options = null;

	/**
	 * LP_Certificate_Layer constructor.
	 *
	 * @param $options
	 */
	public function __construct( $options ) {
		$this->options = wp_parse_args(
			$options,
			array(
				'name' => uniqid()
			)
		);
	}

	/**
	 * Get name of layer.
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->options['name'];
	}

	public function apply( $data ) {
		$this->options['text'] = ! empty( $this->options['variable'] ) ? $this->options['variable'] : ( isset( $this->options['text'] ) ? $this->options['text'] : '' );
	}

	/**
	 * Default layer's options.
	 *
	 * @return array
	 */
	public function get_options() {
		$font_element = array();
		$font_ttf     = array();

		$font_element = array(
			'name'        => 'fontFamily',
			'type'        => 'font',
			'title'       => __( 'Font', 'learnpress-certificates' ),
			'std'         => '',
			'google_font' => true
		);

		$fields = array( $font_element );
		if ( ! empty( $font_ttf ) ) {
			$fields = array_merge( $fields, array( $font_ttf ) );
		}

		$fields = array_merge( $fields,
			array(
				array(
					'name'  => 'fontSize',
					'type'  => 'slider',
					'title' => __( 'Font size', 'learnpress-certificates' ),
					'std'   => '',
					'min'   => 8,
					'max'   => 512
				),
				array(
					'name'    => 'fontStyle',
					'type'    => 'select',
					'title'   => __( 'Font style', 'learnpress-certificates' ),
					'std'     => '',
					'options' => array(
						''        => __( 'Normal', 'learnpress-certificates' ),
						'italic'  => __( 'Italic', 'learnpress-certificates' ),
						'oblique' => __( 'Oblique', 'learnpress-certificates' )
					)
				),
				array(
					'name'    => 'fontWeight',
					'type'    => 'select',
					'title'   => __( 'Font weight', 'learnpress-certificates' ),
					'std'     => '',
					'options' => array(
						''     => __( 'Normal', 'learnpress-certificates' ),
						'bold' => __( 'Bold', 'learnpress-certificates' )
					)
				),
				array(
					'name'    => 'textDecoration',
					'type'    => 'select',
					'title'   => __( 'Text decoration', 'learnpress-certificates' ),
					'std'     => '',
					'options' => array(
						''             => __( 'Normal', 'learnpress-certificates' ),
						'underline'    => __( 'Underline', 'learnpress-certificates' ),
						'overline'     => __( 'Overline', 'learnpress-certificates' ),
						'line-through' => __( 'Line-through', 'learnpress-certificates' )
					)
				),
				array(
					'name'  => 'fill',
					'type'  => 'color',
					'title' => __( 'Color', 'learnpress-certificates' ),
					'std'   => ''
				),
				array(
					'name'    => 'originX',
					'type'    => 'select',
					'title'   => __( 'Text align', 'learnpress-certificates' ),
					'options' => array(
						'left'   => __( 'Left', 'learnpress-certificates' ),
						'center' => __( 'Center', 'learnpress-certificates' ),
						'right'  => __( 'Right', 'learnpress-certificates' )
					),
					'std'     => ''
				),
				array(
					'name'    => 'originY',
					'type'    => 'select',
					'title'   => __( 'Text vertical align', 'learnpress-certificates' ),
					'options' => array(
						'top'    => __( 'Top', 'learnpress-certificates' ),
						'center' => __( 'Middle', 'learnpress-certificates' ),
						'bottom' => __( 'Bottom', 'learnpress-certificates' )
					),
					'std'     => ''
				),
				array(
					'name'  => 'top',
					'type'  => 'number',
					'title' => __( 'Top', 'learnpress-certificates' ),
					'std'   => '',
				),
				array(
					'name'  => 'left',
					'type'  => 'number',
					'title' => __( 'Left', 'learnpress-certificates' ),
					'std'   => '',
				),
				array(
					'name'  => 'angle',
					'type'  => 'slider',
					'title' => __( 'Angle', 'learnpress-certificates' ),
					'std'   => '',
					'min'   => 0,
					'max'   => 360
				),
				array(
					'name'  => 'scaleX',
					'type'  => 'slider',
					'title' => __( 'Scale X', 'learnpress-certificates' ),
					'std'   => '',
					'min'   => - 50,
					'max'   => 50,
					'step'  => 0.1
				),
				array(
					'name'  => 'scaleY',
					'type'  => 'slider',
					'title' => __( 'Scale Y', 'learnpress-certificates' ),
					'std'   => '',
					'min'   => - 50,
					'max'   => 50,
					'step'  => 0.1
				)
			)
		);

		if ( get_class( $this ) === 'LP_Certificate_Layer' ) {
			array_unshift( $fields, array(
				'name'  => 'variable',
				'type'  => 'text',
				'title' => __( 'Custom Text', 'learnpress-certificates' ),
				'std'   => ''
			) );
		}

		$fields = apply_filters( 'learn-press/certificates/fields', $fields, $this );

		foreach ( $fields as $k => $field ) {
			$name = $field['name'];

			if ( array_key_exists( $name, $this->options ) ) {
				$fields[ $k ]['std'] = $this->options[ $name ];
			}
		}

		return $fields;
	}

	public function __toString() {
		return LP_Helper::json_encode( $this->options );
	}
}