<?php

class LP_Certificates_Settings extends LP_Abstract_Settings_Page {
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id   = 'certificates';
		$this->text = esc_html__( 'Certificates', 'learnpress-certificates' );

		parent::__construct();
	}

	public function get_settings( $section = '', $tab = '' ) {
		return $this->setting_v4();
	}

	public function setting_v4() {
		$desc_google_font = '';

		$settings = array(
			array(
				'type' => 'title',
			),
			array(
				'name'        => esc_html__( 'Google Fonts', 'learnpress-certificates' ),
				'desc'        => esc_html__(
					'Font families separated by |, eg: Open Sans|Roboto.',
					'learnpress-certificates'
				),
				'placeholder' => esc_html__( 'Font family', 'learnpress-certificates' ),
				'id'          => 'certificates[google_fonts][families]',
				'type'        => 'text',
				'default'     => '',
			),
			array(
				'name'        => '',
				'desc'        => esc_html__(
					'Font subsets separated by comma, eg: greek,latin.',
					'learnpress-certificates'
				),
				'placeholder' => esc_html__( 'Subset', 'learnpress-certificates' ),
				'id'          => 'certificates[google_fonts][subsets]',
				'type'        => 'text',
				'default'     => '',
			),
			array(
				'name'    => esc_html__( 'Download certificate types', 'learnpress-certificates' ),
				'id'      => 'lp_cer_down_type',
				'type'    => 'radio',
				'options' => array(
					'image' => esc_html__( 'Image', 'learnpress-certificates' ),
					'pdf'   => esc_html__( 'PDF', 'learnpress-certificates' ),
				),
				'default' => 'image',
			),
			array(
				'name'    => esc_html__( 'Show certificate popup', 'learnpress-certificates' ),
				'desc'    => esc_html__( 'Show certificate popup', 'learnpress-certificates' ),
				'id'      => 'lp_cer_show_popup',
				'type'    => 'checkbox',
				'default' => 'yes',
			),
			array(
				'name'    => esc_html__( 'Slug show link certificate of user', 'learnpress-certificates' ),
				'id'      => 'lp_cert_slug',
				'type'    => 'text',
				'default' => 'certificates',
			),
			array(
				'title'         => esc_html__( 'Social Sharing', 'learnpress-certificates' ),
				'id'            => 'certificates[socials_twitter]',
				'default'       => 'no',
				'type'          => 'checkbox',
				'checkboxgroup' => 'start',
				'desc'          => esc_html__( 'Twitter', 'learnpress-certificates' ),
			),
			array(
				'id'            => 'certificates[socials_facebook]',
				'default'       => 'no',
				'type'          => 'checkbox',
				'checkboxgroup' => 'end',
				'desc'          => esc_html__( 'Facebook', 'learnpress-certificates' ),
			),
			array(
				'name'              => esc_html__( 'Certificate per page', 'learnpress-certificates' ),
				'id'                => 'lp_cert_per_page',
				'type'              => 'number',
				'default'           => 10,
				'custom_attributes' => array(
					'min' => 0,
				),
				'desc'              => esc_html__( 'The total number of displayed certificates per page. If set 0 then show all.', 'learnpress-certificates' ),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'lp_profile_general',
			),
		);

		return apply_filters( 'learnpress/certificate/settings', $settings );
	}
}

return new LP_Certificates_Settings();
