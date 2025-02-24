<?php

class LP_Certificate_Datetime_Layer extends LP_Certificate_Layer {
	protected $_added_field = false;

	public function __construct( $options ) {
		parent::__construct( $options );

		add_filter( 'learn-press/certificates/fields', array( $this, 'add_field' ), 10, 2 );
	}

	/**
	 * @param array                $_options
	 * @param LP_Certificate_Layer $layer
	 *
	 * @return array
	 */
	public function add_field( $_options, $layer ) {
		if ( ! $this->_added_field && ( $layer->get_name() === $this->get_name() ) ) {
			$options    = array( $_options[0] );
			$options[1] = array(
				'name'  => 'formatDate',
				'type'  => 'text',
				'title' => esc_html__( 'Format', 'learnpress-certificates' ),
				'std'   => '',
			);

			for ( $i = 1, $n = sizeof( $_options ); $i < $n; $i ++ ) {
				$options[] = $_options[ $i ];
			}

			$_options           = $options;
			$this->_added_field = true;
		}

		return $_options;
	}

	public function get_format() {
		return ! empty( $this->options['formatDate'] ) ? $this->options['formatDate'] : get_option( 'date_format', 'd/m/Y' );
	}
}
