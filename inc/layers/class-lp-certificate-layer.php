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
				'name' => uniqid(),
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
			'title'       => esc_html__( 'Font', 'learnpress-certificates' ),
			'std'         => '',
			'google_font' => true,
		);

		$fields = array( $font_element );
		if ( ! empty( $font_ttf ) ) {
			$fields = array_merge( $fields, array( $font_ttf ) );
		}

		$fields = array_merge(
			$fields,
			array(
				array(
					'name'  => 'fontSize',
					'type'  => 'slider',
					'title' => esc_html__( 'Font size', 'learnpress-certificates' ),
					'std'   => '',
					'min'   => 8,
					'max'   => 512,
				),
				array(
					'name'    => 'fontStyle',
					'type'    => 'select',
					'title'   => esc_html__( 'Font style', 'learnpress-certificates' ),
					'std'     => '',
					'options' => array(
						''        => esc_html__( 'Normal', 'learnpress-certificates' ),
						'italic'  => esc_html__( 'Italic', 'learnpress-certificates' ),
						'oblique' => esc_html__( 'Oblique', 'learnpress-certificates' ),
					),
				),
				array(
					'name'    => 'fontWeight',
					'type'    => 'select',
					'title'   => esc_html__( 'Font weight', 'learnpress-certificates' ),
					'std'     => '',
					'options' => array(
						''     => esc_html__( 'Normal', 'learnpress-certificates' ),
						'bold' => esc_html__( 'Bold', 'learnpress-certificates' ),
					),
				),
				array(
					'name'    => 'textDecoration',
					'type'    => 'select',
					'title'   => esc_html__( 'Text decoration', 'learnpress-certificates' ),
					'std'     => '',
					'options' => array(
						''             => esc_html__( 'Normal', 'learnpress-certificates' ),
						'underline'    => esc_html__( 'Underline', 'learnpress-certificates' ),
						'overline'     => esc_html__( 'Overline', 'learnpress-certificates' ),
						'line-through' => esc_html__( 'Line-through', 'learnpress-certificates' ),
					),
				),
				array(
					'name'  => 'fill',
					'type'  => 'color',
					'title' => esc_html__( 'Color', 'learnpress-certificates' ),
					'std'   => '',
				),
				array(
					'name'    => 'originX',
					'type'    => 'select',
					'title'   => esc_html__( 'Text align', 'learnpress-certificates' ),
					'options' => array(
						'left'   => esc_html__( 'Left', 'learnpress-certificates' ),
						'center' => esc_html__( 'Center', 'learnpress-certificates' ),
						'right'  => esc_html__( 'Right', 'learnpress-certificates' ),
					),
					'std'     => '',
				),
				array(
					'name'    => 'originY',
					'type'    => 'select',
					'title'   => esc_html__( 'Text vertical align', 'learnpress-certificates' ),
					'options' => array(
						'top'    => esc_html__( 'Top', 'learnpress-certificates' ),
						'center' => esc_html__( 'Middle', 'learnpress-certificates' ),
						'bottom' => esc_html__( 'Bottom', 'learnpress-certificates' ),
					),
					'std'     => '',
				),
				array(
					'name'  => 'top',
					'type'  => 'number',
					'title' => esc_html__( 'Top', 'learnpress-certificates' ),
					'std'   => '',
				),
				array(
					'name'  => 'left',
					'type'  => 'number',
					'title' => esc_html__( 'Left', 'learnpress-certificates' ),
					'std'   => '',
				),
				array(
					'name'  => 'angle',
					'type'  => 'slider',
					'title' => esc_html__( 'Angle', 'learnpress-certificates' ),
					'std'   => '',
					'min'   => 0,
					'max'   => 360,
				),
				array(
					'name'  => 'scaleX',
					'type'  => 'slider',
					'title' => esc_html__( 'Scale X', 'learnpress-certificates' ),
					'std'   => '',
					'min'   => - 50,
					'max'   => 50,
					'step'  => 0.1,
				),
				array(
					'name'  => 'scaleY',
					'type'  => 'slider',
					'title' => esc_html__( 'Scale Y', 'learnpress-certificates' ),
					'std'   => '',
					'min'   => - 50,
					'max'   => 50,
					'step'  => 0.1,
				),
			)
		);

		if ( get_class( $this ) === 'LP_Certificate_Layer' ) {
			array_unshift(
				$fields,
				array(
					'name'  => 'variable',
					'type'  => 'text',
					'title' => esc_html__( 'Custom Text', 'learnpress-certificates' ),
					'std'   => '',
				)
			);
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
