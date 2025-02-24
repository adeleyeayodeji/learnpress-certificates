<?php

use LearnPress\Certificates\DownloadFontGoogle;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;

const LP_ADDON_CERTIFICATES_CERT_CPT      = 'lp_cert';
const LP_ADDON_CERTIFICATES_USER_CERT_CPT = 'lp_user_cert';
define( 'LP_ADDON_CERTIFICATES_PATH', dirname( LP_ADDON_CERTIFICATES_FILE ) );
const LP_ADDON_CERTIFICATES_TEMPLATE_DEFAULT = LP_ADDON_CERTIFICATES_PATH . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;

/**
 * Class LP_Addon_Certificates
 */
class LP_Addon_Certificates extends LP_Addon {
	/**
	 * @var string
	 */
	public $version = LP_ADDON_CERTIFICATES_VER;

	/**
	 * @var string
	 *
	 * LP Version
	 */
	public $require_version = LP_ADDON_CERTIFICATES_VER;

	/**
	 * Path file addon.
	 *
	 * @var string
	 */
	public $plugin_file = LP_ADDON_CERTIFICATES_FILE;

	public static $_PATH_FONTS = '';

	/**
	 * LP_Addon_Gradebook constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->_maybe_upgrade_data();

		LP_Request::register_ajax( 'cert-update-layer', array( $this, 'update_layer' ) );
		LP_Request::register_ajax( 'cert-update-layers', array( $this, 'update_layers' ) );
		LP_Request::register_ajax( 'cert-load-layer', array( $this, 'load_layer' ) );
		LP_Request::register_ajax( 'cert-remove-layer', array( $this, 'remove_layer' ) );
		LP_Request::register_ajax( 'cert-update-template', array( $this, 'update_template' ) );

		add_action( 'learn-press/rewrite/tags', array( $this, 'add_rewrite_tags' ) );
		add_filter( 'learn-press/rewrite/rules', array( $this, 'add_rewrite_rules' ), 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'add_script_data' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'wp_scripts' ) );
		add_action( 'template_include', array( $this, 'show_cert' ) );
		add_action( 'admin_head', array( $this, 'header_google_fonts' ) );
		//add_action( 'wp_head', array( $this, 'header_google_fonts' ) );
		add_action( 'wp_footer', array( $this, 'show_certificate_popup' ) );
		add_action( 'learn-press/user-course-finished', array( $this, 'update_user_certificate' ), 10, 3 );
		add_action( 'learn-press/update-settings/updated', array( $this, 'save_data_gg_fonts' ), 10, 1 );

		$this->add_class_template_certificate(); // It required for call LearnPress::instance()->template( 'certificate' );
		add_action( 'learn-press/course-buttons', LearnPress::instance()->template( 'certificate' )->func( 'button_certificate' ), 10 );
		add_action( 'learn-press/single-course/modern/section-right/buttons', [ $this, 'button_certificate' ], 10, 3 );
		add_action( 'learn-press/single-course/section-right/buttons', [ $this, 'button_certificate' ], 10, 3 );

		add_action( 'learnpress/addons/frontend_editor/enqueue_scripts', array( $this, 'admin_react_scripts' ) );
		add_action( 'learnpress_upsell/admin_enqueue_scripts', array( $this, 'admin_react_scripts' ) ); // for upsell

		// Filters
		add_filter( 'learn-press/profile-tabs', array( $this, 'profile_tabs' ) );
		add_filter( 'learn-press/admin/settings-tabs-array', array( $this, 'admin_settings' ) );

		// create folder learn-press-cert fonts
		$uploads  = wp_upload_dir();
		$cert_dir = $uploads['basedir'] . DIRECTORY_SEPARATOR . 'learn-press-cert' . DIRECTORY_SEPARATOR . 'fonts' . DIRECTORY_SEPARATOR;

		if ( ! file_exists( $cert_dir ) ) {
			wp_mkdir_p( $cert_dir );
		}

		self::$_PATH_FONTS = $cert_dir;
	}

	public function admin_react_scripts() {
		wp_enqueue_script( 'fabric' );
		wp_enqueue_script( 'certificates-js' );

		$localize_cer = array(
			'base_url'        => home_url(),
			'url_upload_cert' => home_url( 'upload' ),
			'url_ajax'        => admin_url( 'admin-ajax.php' ),
		);

		wp_localize_script( 'certificates-js', 'localize_lp_cer_js', $localize_cer );
	}

	/**
	 * Add to call LearnPress::instance()->template( 'certificate' )
	 *
	 * @return void
	 * @author Nhamdv <email@email.com>
	 */
	public function add_class_template_certificate() {
		if ( class_exists( 'LP_Template' ) ) {
			$lp_template = LP_Template::instance();

			if ( ! in_array( 'certificate', $lp_template->templates, true ) ) {
				$lp_template->templates['certificate'] = include_once LP_ADDON_CERTIFICATES_PATH . '/inc/class-lp-template-certificate.php';
			}
		}
	}

	protected function _maybe_upgrade_data() {
		if ( ! ( version_compare( LP_ADDON_CERTIFICATES_VER, '3.0.0', '=' ) &&
				version_compare( get_option( 'certificates_db_version' ), '3.0.0', '<' ) ) ) {
			return;
		}

		global $wpdb;

		$query = $wpdb->prepare(
			"
			    SELECT meta_id AS id, meta_value AS layers
			    FROM {$wpdb->postmeta}
			    WHERE meta_key = %s
			",
			'_lp_cert_layers'
		);

		$certs = $wpdb->get_results( $query );
		if ( ! $certs ) {
			return;
		}

		$queue_items = array();

		foreach ( $certs as $cert ) {
			$layers = maybe_unserialize( $cert->layers );

			if ( ! $layers ) {
				continue;
			}

			foreach ( $layers as $k => $layer ) {
				settype( $layer, 'array' );
				if ( ! array_key_exists( 'variable', $layer ) ) {
					$layer['variable'] = $layer['text'];
				}
				$layers[ $k ] = $layer;
			}

			$wpdb->update(
				$wpdb->postmeta,
				array( 'meta_value' => serialize( $layers ) ),
				array( 'meta_id' => $cert->id ),
				array( '%s' ),
				array( '%d' )
			);
		}
	}

	public function show_certificate_popup() {
		$user_id = get_current_user_id();

		if ( learn_press_is_course() ) {
			$course_id = get_the_ID();

			$setting_show_cer_popup = LearnPress::instance()->settings()->get( 'lp_cer_show_popup', 'yes' );
			$cert_id                = LP_Certificate::get_course_certificate( $course_id );

			if ( $cert_id ) {
				$cert_key = LP_Certificate::get_cert_key( $user_id, $course_id, 0, false );

				if ( $cert_key ) {
					$certificate = LP_Certificate::get_cert_by_key( $cert_key );

					if ( is_a( $certificate, 'LP_User_Certificate' ) ) {
						$can_get_certificate = LP_Certificate::can_get_certificate( $course_id, $user_id );

						if ( $setting_show_cer_popup == 'yes' && $can_get_certificate['flag'] ) {
							if ( get_transient( 'lp-show-certificate-' . $user_id . '-' . $course_id ) ) {
								delete_transient( 'lp-show-certificate-' . $user_id . '-' . $course_id );
								echo '<input name="f_auto_show_cer_popup_first" value="1">';
							}

							LP_Addon_Certificates_Preload::$addon->get_template( 'popup.php', compact( 'certificate' ) );
						}
					}
				}
			}
		}
	}

	/**
	 * Update certificate data when user finished course
	 *
	 * @param int $course_id
	 * @param int $user_id
	 * @param int $course_item
	 */
	public function update_user_certificate( $course_id, $user_id, $course_item ) {
		$cert_id = LP_Certificate::get_course_certificate( $course_id );

		if ( $cert_id ) {
			$key = LP_Certificate::get_cert_key( $user_id, $course_id, $cert_id, false );
			set_transient( 'lp-show-certificate-' . $user_id . '-' . $course_id, $key );
		}
	}

	public function admin_settings( $tabs ) {
		$tabs['certificates'] = include_once LP_ADDON_CERTIFICATES_PATH . '/inc/class-lp-certificate-settings.php';

		return $tabs;
	}

	public function show_cert( $template ) {
		global $wp;
		$rewrite_rules = new WP_Rewrite();

		$match_rules = $rewrite_rules->rewrite_rules();

		if ( ! empty( $wp->query_vars['view-cert'] ) ) {
			$cert = LP_Certificate::get_cert_by_key( $wp->query_vars['view-cert'] );

			if ( $cert ) {
				LP_Addon_Certificates_Preload::$addon->get_template( 'single-certificate.php', compact( 'cert' ) );
				die();
			}

			learn_press_404_page();
		}

		return $template;
	}

	/**
	 * Register tab with Profile
	 */
	public function profile_tabs( $tabs ) {
		$tabs['certificates'] = array(
			'title'    => esc_html__( 'Certificates', 'learnpress-certificates' ),
			'slug'     => LearnPress::instance()->settings()->get( 'lp_cert_slug', 'certificates' ),
			'callback' => array( $this, 'profile_certificates' ),
			'icon'     => '<i class="fas fa-certificate"></i>',
			'priority' => 12,
		);

		return $tabs;
	}

	public function profile_certificates() {
		$profile = learn_press_get_profile();

		global $wp;
		if ( ! empty( $wp->query_vars['act'] ) && ! empty( $wp->query_vars['cert-id'] ) ) {
			$key         = $wp->query_vars['cert-id'];
			$certificate = LP_Certificate::get_cert_by_key( $key );

			if ( $certificate ) {
				if ( $certificate->get_id() ) {
					LP_Addon_Certificates_Preload::$addon->get_template( 'details.php', array( 'certificate' => $certificate ) );
				}
			}
		} else { ?>
			<h3 class="profile-heading"><?php esc_html_e( 'Certificates', 'learnpress-certificates' ); ?></h3>
			<div class="learnpress-certificates-profile">
				<input type="hidden" name="userID" value="<?php echo $profile->get_user()->get_id(); ?>">
				<ul class="lp-skeleton-animation">
					<li style="width: 100%; height: 20px"></li>
					<li style="width: 100%; height: 20px"></li>
					<li style="width: 100%; height: 20px"></li>
					<li style="width: 100%; height: 20px"></li>
					<li style="width: 100%; height: 20px"></li>
					<li style="width: 100%; height: 20px"></li>
					<li style="width: 100%; height: 20px"></li>
				</ul>
			</div>
			<?php
			//$certificates = LP_Certificate::get_user_certificates( $profile->get_user()->get_id() );
			//LP_Addon_Certificates_Preload::$addon->get_template( 'list-certificates.php', array( 'certificates' => $certificates ) );
		}
	}

	public function remove_layer() {
		$id          = LP_Request::get_int( 'id' );
		$certificate = new LP_Certificate( $id );
		$certificate->remove_layer( LP_Request::get_string( 'layer' ) );
	}

	/**
	 * Load layer options
	 */
	public function load_layer() {
		$id          = LP_Request::get_int( 'id' );
		$certificate = new LP_Certificate( $id );

		if ( ! $certificate->get_id() ) {
			return;
		}

		$layer_id = LP_Request::get_string( 'layer' );

		$certificate->layer_options( $layer_id );
		die();
	}

	/**
	 * Ajax update layer options
	 */
	public function update_layer() {
		$layer = LP_Request::get_array( 'layer' );
		if ( ! $layer ) {
			return;
		}

		if ( empty( $layer['name'] ) ) {
			$layer['name'] = uniqid();
		}

		$id = LP_Request::get_int( 'id' );

		if ( get_post_type( $id ) !== LP_ADDON_CERTIFICATES_CERT_CPT ) {
			return;
		}

		$layers = get_post_meta( $id, '_lp_cert_layers', true );

		if ( ! $layers ) {
			$layers = array( $layer['name'] => $layer );
		} else {
			if ( ! is_array( $layers ) ) {
				settype( $layers, 'array' );
			}
			$_layers = array();
			$found   = false;
			foreach ( $layers as $_layer ) {
				if ( is_object( $_layer ) ) {
					$_layer = (array) $_layer;
				}
				if ( empty( $_layer['name'] ) ) {
					$_layer['name'] = uniqid();
				}
				if ( $_layer['name'] == $layer['name'] ) {
					$_layers[ $_layer['name'] ] = $layer;
					$found                      = true;
				} else {
					$_layers[ $_layer['name'] ] = $_layer;
				}
			}
			if ( ! $found ) {
				$_layers[ $layer['name'] ] = $layer;
			}
			$layers = $_layers;
		}

		$rs_update_layers = update_post_meta( $id, '_lp_cert_layers', $layers );

		if ( 'yes' === LP_Request::get_string( 'load-settings' ) ) {
			$id          = LP_Request::get_int( 'id' );
			$certificate = new LP_Certificate( $id );
			$certificate->layer_options( $layer['name'] );
		}

		die();
	}

	/**
	 * Ajax update layer options
	 */
	public function update_layers() {
		$layers = LP_Request::get_array( 'layers' );

		if ( ! $layers ) {
			return;
		}

		$id = LP_Request::get_int( 'id' );

		if ( get_post_type( $id ) !== LP_ADDON_CERTIFICATES_CERT_CPT ) {
			return;
		}

		update_post_meta( $id, '_lp_cert_layers', $layers );

		die();
	}

	/**
	 * Ajax update template
	 */
	public function update_template() {
		$id       = LP_Request::get_int( 'id' );
		$template = LP_Request::get_string( 'template' );

		// Check if link same domain, remove domain name.
		$template = str_replace( home_url(), '', $template );
		if ( $id ) {
			update_post_meta( $id, '_lp_cert_template', $template );
		}
	}

	/**
	 * Add rewrite tags for single certificate, profile certificate
	 *
	 * @return void
	 */
	public function add_rewrite_tags( $tags = array() ) {
		return array_merge(
			$tags,
			array(
				'%cert-id%'   => '(.*)',
				'%act%'       => '(.*)',
				'%view-cert%' => '(.*)',
			)
		);
	}

	/**
	 * Add rewrite rules for single certificate, profile certificate
	 *
	 * @param $rules
	 *
	 * @return array
	 */
	public function add_rewrite_rules( $rules ) {
		$profile_id            = learn_press_get_page_id( 'profile' );
		$slug_page_single_cert = urldecode( LP_Settings::get_option( 'lp_cert_slug', 'certificates' ) );
		$profile_slug          = get_post_field( 'post_name', $profile_id );

		$rules['profile'][ LP_ADDON_CERTIFICATES_CERT_CPT ]     = array(
			"^{$profile_slug}/([^/]*)/?({$slug_page_single_cert})/?$" =>
				'index.php?page_id=' . $profile_id . '&user=$matches[1]&view=$matches[2]',
		);
		$rules[ LP_ADDON_CERTIFICATES_CERT_CPT ]['single_view'] = array(
			'^' . $slug_page_single_cert . '/([^/]*)/?$' =>
				'index.php?view-cert=$matches[1]',
		);

		return $rules;
	}

	/**
	 * Include files
	 */
	protected function _includes() {
		include_once LP_ADDON_CERTIFICATES_PATH . '/inc/class-lp-certificate-database.php';
		include_once LP_ADDON_CERTIFICATES_PATH . '/inc/class-lp-certificate-filter.php';
		include_once LP_ADDON_CERTIFICATES_PATH . '/inc/class-lp-certificate-post-type.php';
		include_once LP_ADDON_CERTIFICATES_PATH . '/inc/class-lp-certificate.php';
		include_once LP_ADDON_CERTIFICATES_PATH . '/inc/class-lp-user-certificate.php';
		include_once LP_ADDON_CERTIFICATES_PATH . '/inc/layers/class-lp-certificate-layer.php';
		include_once LP_ADDON_CERTIFICATES_PATH . '/inc/layers/_datetime.php';
		include_once LP_ADDON_CERTIFICATES_PATH . '/inc/layers/class-lp-course-name-layer.php';
		include_once LP_ADDON_CERTIFICATES_PATH . '/inc/layers/class-lp-student-name-layer.php';
		include_once LP_ADDON_CERTIFICATES_PATH . '/inc/class-lp-certificate-ajax.php';
		include_once LP_ADDON_CERTIFICATES_PATH . '/inc/class-lp-certificate-order.php';
		include_once LP_ADDON_CERTIFICATES_PATH . '/inc/class-lp-certificate-product-woo.php';
		include_once LP_ADDON_CERTIFICATES_PATH . '/inc/class-lp-certificate-woo.php';
		include_once LP_ADDON_CERTIFICATES_PATH . '/inc/functions.php';
		include_once LP_ADDON_CERTIFICATES_PATH . '/inc/rest-api/class-lp-rest-admin-certificate-controller.php';
	}

	public function wp_scripts() {
		$this->enqueue_assets();
	}

	/**
	 * JS Settings
	 */
	public function add_script_data() {
		$this->enqueue_assets();
		global $post;

		if ( LP_ADDON_CERTIFICATES_CERT_CPT !== get_post_type() || LP_Request::get_string( 'post_type' ) == LP_ADDON_CERTIFICATES_CERT_CPT ) {
			return;
		}

		$certificate = new LP_Certificate( $post->ID );
		$assets      = LP_Admin_Assets::instance();

		$assets->add_script_data(
			'certificates',
			array(
				'id'          => $certificate->get_id(),
				'layers'      => $certificate->get_raw_layers(),
				'template'    => $certificate->get_template(),
				'preview'     => $certificate->get_preview(),
				'systemFonts' => LP_Certificate::system_fonts(),
				'i18n'        => array(
					'confirm_remove_layer' => __( 'Delete this layer?', 'learnpress-certificates' ),
				),
			)
		);
	}

	/**
	 * Default fields.
	 *
	 * @return array
	 */
	public static function get_fields() {
		return apply_filters(
			'certificates/fields',
			array(
				array(
					'name'  => 'course-name',
					'icon'  => 'dashicons-welcome-learn-more',
					'title' => __( 'Course name', 'learnpress-certificates' ),
				),
				array(
					'name'  => 'student-name',
					'icon'  => 'dashicons-admin-users',
					'title' => __( 'Student name', 'learnpress-certificates' ),
				),
				array(
					'name'  => 'course-start-date',
					'icon'  => 'dashicons-calendar-alt',
					'title' => __( 'Course start date', 'learnpress-certificates' ),
				),
				array(
					'name'  => 'course-end-date',
					'icon'  => 'dashicons-calendar-alt',
					'title' => __( 'Course end date', 'learnpress-certificates' ),
				),
				array(
					'name'  => 'current-time',
					'icon'  => 'dashicons-clock',
					'title' => __( 'Current time', 'learnpress-certificates' ),
				),
				array(
					'name'  => 'verified-link',
					'icon'  => 'dashicons-yes',
					'title' => __( 'QR code', 'learnpress-certificates' ),
				),
				array(
					'name'  => 'custom',
					'icon'  => 'dashicons-smiley',
					'title' => __( 'Custom', 'learnpress-certificates' ),
				),
			)
		);
	}

	/**
	 * Enqueue asstes
	 */
	protected function enqueue_assets() {
		$min = '.min';
		$ver = $this->version;
		if ( LP_Debug::is_debug() ) {
			$min = '';
			$ver = uniqid();
		}

		$localize_cer = array(
			'base_url'        => home_url(),
			'url_upload_cert' => home_url( 'upload' ),
			'url_ajax'        => admin_url( 'admin-ajax.php' ),
			'i18n'            => array(
				'loading' => __( 'Loading', 'learnpress-certificates' ),
			),
		);

		$ids_screen_valid  = array( 'lp_course', 'lp_cert' );
		$id_current_screen = '';

		if ( function_exists( 'get_current_screen' ) && get_current_screen() ) {
			$id_current_screen = get_current_screen()->id;
		}

		// Todo: rewrite code use class-lp-assets standard
		wp_register_script(
			'fabric',
			$this->get_plugin_url( 'assets/src/js/fabric.min.js' ),
			array(),
			'1.4.13',
			[
				'strategy' => 'defer',
			]
		);
		wp_register_script(
			'certificates-js',
			$this->get_plugin_url( "assets/dist/js/frontend/certificates{$min}.js" ),
			array( 'jquery', 'wp-api-fetch', 'fabric' ),
			$ver,
			[ 'strategy' => 'defer' ]
		);

		if ( is_admin() ) {
			wp_register_script(
				'md5',
				$this->get_plugin_url( 'assets/src/js/md5.js' ),
				array(),
				false,
				[
					'in_footer' => true,
					'strategy'  => 'defer',
				]
			);
			wp_register_script(
				'certificates',
				$this->get_plugin_url( "assets/dist/js/backend/admin.certificates{$min}.js" ),
				array(
					'fabric',
					'jquery',
					'wp-util',
					'jquery-ui-draggable',
					'jquery-ui-droppable',
					'vue-libs',
					'certificates-js',
				),
				$ver,
				[
					'in_footer' => true,
					'strategy'  => 'defer',
				]
			);
			wp_register_style(
				'admin-certificates-css',
				$this->get_plugin_url( "assets/dist/css/admin.certificates{$min}.css" ),
				array(),
				$ver
			);

			if ( $id_current_screen == 'edit-lp_course' ) {
				wp_enqueue_style( 'admin-certificates-css' );
			}

			if ( $id_current_screen == 'lp_course' ) {
				wp_enqueue_style( 'admin-certificates-css' );
				wp_enqueue_script( 'certificates' );
			}

			if ( $id_current_screen == 'lp_cert' ) {
				wp_enqueue_style( 'wp-color-picker' );
				wp_enqueue_script( 'wp-color-picker' );
				wp_enqueue_style( 'admin-certificates-css' );
				wp_enqueue_script( 'md5' );
				wp_enqueue_media();
				wp_enqueue_script( 'certificates' );
			}

			wp_localize_script( 'certificates-js', 'localize_lp_cer_js', $localize_cer );
			wp_localize_script( 'certificates', 'localize_lp_cer_js', $localize_cer );
		} else {
			wp_register_script(
				'pdfjs',
				$this->get_plugin_url( 'assets/src/js/pdf.js' ),
				[],
				'1.5.3',
				[
					'strategy' => 'defer',
				]
			);
			wp_register_script(
				'downloadjs',
				$this->get_plugin_url( 'assets/src/js/download.min.js' ),
				array(),
				'4.2',
				[
					'strategy' => 'defer',
				]
			);
			wp_register_style(
				'certificates-css',
				$this->get_plugin_url( "assets/dist/css/certificates{$min}.css" ),
				array(),
				$ver
			);
			wp_register_script(
				'certificate-profile-js',
				$this->get_plugin_url( "assets/dist/js/frontend/profile.certificates{$min}.js" ),
				array( 'wp-api-fetch' ),
				$ver,
				[
					'strategy' => 'defer',
				]
			);

			wp_localize_script( 'certificates-js', 'localize_lp_cer_js', $localize_cer );

			$this->checkLoadSourceAssetsFrontend();
		}
	}

	/**
	 * Download google font to local
	 *
	 * @param $lp_submenu_settings
	 *
	 * @return void
	 * @version 1.0.0
	 * @since 4.0.7
	 */
	public function save_data_gg_fonts( $lp_submenu_settings ) {
		try {
			if ( empty( $_POST['learn_press_certificates'] ) ) {
				return;
			}

			if ( empty( $_POST['learn_press_certificates']['google_fonts'] ) ) {
				return;
			}

			$gg_fonts_families_old = LP_Settings::instance()->get( 'certificates.google_fonts.families', '' );
			$gg_fonts_families_new = $_POST['learn_press_certificates']['google_fonts']['families'] ?? '';
			if ( empty( $gg_fonts_families_new ) ) {
				LP_Settings::update_option( 'cert_gg_fonts', '' );
			}

			if ( $gg_fonts_families_old === $gg_fonts_families_new ) {
				return;
			}

			require_once LP_ADDON_CERTIFICATES_PATH . '/inc/DownloadFontGoogle.php';
			$url_font     = 'https://fonts.googleapis.com/css?family=' . $gg_fonts_families_new . '&display=swap';
			$downloader   = new DownloadFontGoogle( $url_font );
			$content_font = $downloader->get_styles();
			LP_Settings::update_option( 'cert_gg_fonts', $content_font );
		} catch ( Throwable $e ) {
			LP_Settings::update_option( 'cert_gg_fonts', '' );
			error_log( $e->getMessage() );
		}
	}

	/**
	 * Load fonts google
	 *
	 * @since 3.0.0
	 * @version 3.0.1
	 */
	public function header_google_fonts() {
		$font_gg = LP_Settings::get_option( 'cert_gg_fonts', '' );
		if ( ! empty( $font_gg ) ) {
			echo '<style id="lp-certificates-fonts-gg">' . $font_gg . '</style>';
		}
	}

	/*
	public function admin_view( $view, $args = '' ) {
		learn_press_admin_view( $view, wp_parse_args( $args, array( 'plugin_file' => LP_ADDON_CERTIFICATES_FILE ) ) );
	}*/

	public function checkLoadSourceAssetsFrontend() {
		$flag        = false;
		$url_current = LP_Helper::getUrlCurrent();

		/*** Check is page Profile certificate */
		if ( LP_Page_Controller::is_page_profile() ) {
			wp_enqueue_script( 'certificate-profile-js' );
			$flag = true;
		}

		/*** Check is page course */
		if ( LP_PAGE_SINGLE_COURSE === LP_Page_Controller::page_current() ) {
			$flag = true;
		}

		/*** Check is single certificate */
		/*$str_valid_page_single_cert  = home_url( $slug_page_single_cert ) . '/.*';
		$pattern_is_page_single_cert = "@{$str_valid_page_single_cert}@";
		preg_match( $pattern_is_page_single_cert, $url_current, $match_p_single_cert );

		if ( ! empty( $match_p_single_cert ) ) {
			$flag = true;
		}*/

		$flag = apply_filters( 'learn-press/cert-check-load-assets-frontend', $flag );

		/*** Check is Frontend editor - case Frontend editor = 3.1.1 */
		if ( is_plugin_active( 'learnpress-frontend-editor/learnpress-frontend-editor.php' )
			&& class_exists( LP_Addon_Frontend_Editor::class )
			&& LP_ADDON_FRONTEND_EDITOR_VER == '3.1.0' ) {
			$frontend_editor      = new LP_Addon_Frontend_Editor();
			$slug_frontend_editor = $frontend_editor->get_root_slug();

			$str_valid_page_frontend_editor  = '.*/' . $slug_frontend_editor . '/edit-post/.*';
			$pattern_is_page_frontend_editor = "@{$str_valid_page_frontend_editor}@";

			preg_match( $pattern_is_page_frontend_editor, $url_current, $match_p_frontend_editor );

			if ( ! empty( $match_p_frontend_editor ) ) {
				$flag = true;
			}
		}

		if ( $flag ) {
			//wp_enqueue_style( 'fontawesome-css' );
			wp_enqueue_style( 'certificates-css' );

			wp_enqueue_script( 'pdfjs' );
			wp_enqueue_script( 'downloadjs' );
			wp_enqueue_script( 'certificates-js' );
		}
	}

	/**
	 * Get link certificate background by course
	 *
	 * @param int $course_id
	 *
	 * @return string
	 * @since 4.1.0
	 * @version 1.0.0
	 */
	public static function get_link_cert_bg_by_course( int $course_id ): string {
		$cert_bg_img = get_post_meta( $course_id, '_lp_cert_template', true );
		if ( empty( $cert_bg_img ) ) {
			return '';
		}

		// If link is full path: https://domain.com/wp-content/uploads/2021/01/abc.jpg
		$pattern = '#^https?://.*#';
		if ( preg_match( $pattern, $cert_bg_img ) ) {
			$link_cert_bg = $cert_bg_img;
		} else { // Else link is relative path: /wp-content/uploads/2021/01/abc.jpg
			$link_cert_bg = untrailingslashit( site_url() ) . '/' . $cert_bg_img;
		}

		return $link_cert_bg;
	}

	/**
	 * Display button certificate in single course
	 *
	 * @param $courseModel CourseModel
	 * @param $userModel UserModel|false
	 *
	 * @return void
	 * @since 4.1.3
	 * @version 1.0.1
	 */
	public function button_certificate( $section, $courseModel, $userModel ) {
		try {
			if ( ! $courseModel instanceof CourseModel || ! $userModel instanceof UserModel ) {
				return $section;
			}

			$cert_id = $courseModel->get_meta_value_by_key( '_lp_cert', 0 );
			$cert    = get_post( $cert_id );
			if ( empty( $cert ) || $cert->post_type != 'lp_cert' || $cert->post_status != 'publish' ) {
				return $section;
			}

			$user_id      = $userModel->get_id();
			$course_id    = $courseModel->get_id();
			$certificate  = new LP_User_Certificate( $user_id, $course_id, $cert_id );
			$can_get_cert = LP_Certificate::can_get_certificate( $course_id, $user_id );

			ob_start();
			if ( $can_get_cert['flag'] ) {
				LP_Addon_Certificates_Preload::$addon->get_template( 'view-button.php', compact( 'certificate' ) );
			} elseif ( $can_get_cert['reason'] == 'not_buy' ) {
				learn_press_certificate_buy_button( $courseModel );
			}
			$button = ob_get_clean();

			$section = Template::insert_value_to_position_array(
				$section,
				'before',
				'wrapper_end',
				'btn_certificate',
				$button
			);
		} catch ( Exception $e ) {
			error_log( $e->getFile() . ': ' . $e->getMessage() );
		}

		return $section;
	}
}
