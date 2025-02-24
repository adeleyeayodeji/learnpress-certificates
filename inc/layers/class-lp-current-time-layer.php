<?php

class LP_Certificate_Current_Time_Layer extends LP_Certificate_Datetime_Layer {
	public function __construct( $options ) {
		parent::__construct( $options );
	}

	public function apply( $data ) {
		$this->options['text'] = gmdate( $this->get_format(), current_time( 'timestamp' ) );
	}
}
