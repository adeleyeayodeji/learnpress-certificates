<?php

class LP_Certificates_Settings extends LP_Abstract_Settings_Page {
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id   = 'certificates';
		$this->text = __( 'Certificates', 'learnpress-certificates' );

		parent::__construct();
	}

	public function get_settings( $section = '', $tab = '' ) {
		$desc_google_font = '';

		return array(
			array(
				'title'   => __( 'Google Fonts', 'learnpress-certificates' ),
				'id'      => 'certificates[google_fonts]',
				'default' => 'no',
				'type'    => 'google-fonts',
				'desc'    => $desc_google_font
			),
			array(
				'name'    => __( 'Download certificate types', 'learnpress-certificates' ),
				'id'      => 'lp_cer_down_type',
				'type'    => 'radio',
				'options' => array(
					'image' => __( 'Image', 'learnpress-certificates' ),
					'pdf'   => __( 'PDF', 'learnpress-certificates' ),
				),
				'default' => 'image'
			),
			array(
				'name'    => __( 'Show certificate popup', 'learnpress-certificates' ),
				'id'      => 'lp_cer_show_popup',
				'type'    => 'yes-no',
				'default' => 'yes'
			),
			array(
				'name'    => __( 'Slug show link certificate of user', 'learnpress-certificates' ),
				'id'      => 'lp_cert_slug',
				'type'    => 'text',
				'default' => 'certificates'
			),
			array(
				'name'            => __( 'Social Sharing', 'learnpress-certificates' ),
				'id'              => 'certificates[socials]',
				'default'         => '',
				'type'            => 'checkbox_list',
				'options'         => array(
					'twitter'  => __( 'Twitter', 'learnpress-certificates' ),
					'facebook' => __( 'Facebook', 'learnpress-certificates' ),
				),
				'select_all_none' => false
			)
		);
	}
}

return new LP_Certificates_Settings();