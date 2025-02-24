<?php

use LearnPress\Certificates\AdminCourseCertificates;
use LearnPress\Helpers\Template;
use LearnPress\TemplateHooks\TemplateAJAX;

/**
 * Class LP_Certificates_Post_Type
 *
 * Manage post type for certificates
 *
 * @since 3.0.0
 */
class LP_Certificates_Post_Type extends LP_Abstract_Post_Type {
	/**
	 * Type of post
	 *
	 * @var string
	 */
	protected $_post_type = LP_ADDON_CERTIFICATES_CERT_CPT;

	/**
	 * @var bool|LP_Addon_Certificates
	 */
	//public $factory = null;

	/**
	 * LP_Certificates_Post_Type constructor.
	 */
	public function __construct() {
		parent::__construct( $this->_post_type );
		add_action( 'init', array( $this, 'register_post_type' ), 1000 );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'edit_form_after_editor', array( $this, 'cert_editor' ) );
		/*add_action(
			'manage_' . LP_ADDON_CERTIFICATES_CERT_CPT . '_posts_custom_column',
			array( $this, 'columns_content' ),
			10,
			2
		);*/
		add_action( 'manage_' . LP_COURSE_CPT . '_posts_custom_column', array( $this, 'columns_content' ), 10, 2 );
		// add_action( 'save_post', array( $this, 'lp_cert_save_settings' ), 10, 2 );

		add_filter( 'manage_edit-' . LP_COURSE_CPT . '_columns', array( $this, 'columns_head' ) );
		// add_filter( 'manage_edit-' . LP_ADDON_CERTIFICATES_CERT_CPT . '_columns', array( $this, 'columns_head' ) );
		add_filter( 'post_row_actions', array( $this, 'row_actions' ), 10, 2 );
		//add_filter( 'learn-press/get-course-order', array( $this, 'lp_cert_get_order' ), 10, 3 );
		//add_filter( 'learn_press_add_cart_item', array( $this, 'lp_cert_get_price' ), 10, 1 );
		add_filter(
			'learn-press/review-order/cart-item-subtotal',
			array( $this, 'lp_cert_cart_item_subtotal' ),
			10,
			3
		);
		// change title item cart if is not course in page checkout.
		add_filter( 'learn-press/review-order/cart-item-name', array( $this, 'lp_cert_cart_item_name' ), 10, 3 );

		// calculate subtotal by item type in cart
		add_filter( 'learnpress/cart/calculate_sub_total/item_type_lp_cert', array(
			$this,
			'lp_cert_cart_calculate_subtotal'
		), 10, 2 );

		// Metabox course tab.
		add_filter(
			'learnpress/course/metabox/tabs',
			function ( $tabs ) {
				$tabs['certificates'] = array(
					'label'    => esc_html__( 'Certificates', 'learnpress-certificates' ),
					'icon'     => 'dashicons-welcome-learn-more',
					'target'   => 'certificate-browser',
					'priority' => 60,
				);

				return $tabs;
			}
		);

		add_action( 'lp_course_data_setting_tab_content', array( $this, 'display_certificates' ) );

		LP_Request::register_ajax( 'update-course-certificate', array( $this, 'update_course_certificate' ) );

		// add data item to cart by session.
		add_filter( 'learn-press/get-cart-item-from-session/item_type_lp_cert', array(
			$this,
			'lp_cert_cart_get_item_form_session'
		), 10, 2 );
		// add data item type in page checkout.
		add_filter( 'learn-press/review-order/cart-item-product', array(
			$this,
			'lp_cert_review_order_cart_item_cer'
		), 10, 2 );
		// change link item cart if is not course in page checkout.
		add_filter( 'learn-press/review-order/cart-item-link', array(
			$this,
			'lp_cert_review_order_cart_item_link'
		), 10, 2 );

		add_filter( 'lp/rest/ajax/allow_callback', [ $this, 'allow_callback' ] );
	}

	public function allow_callback( $callbacks ) {
		include_once LP_ADDON_CERTIFICATES_PATH . '/inc/admin/AdminCourseCertificates.php';
		$callbacks[] = AdminCourseCertificates::class . ':render_certificates';

		return $callbacks;
	}

	/**
	 * @param array $link
	 * @param array $cart_item : value by cart id;
	 * change link item cart if is not course.
	 */
	public function lp_cert_review_order_cart_item_link( $link, $cart_item ) {

		if ( ! empty( $cart_item['course_id'] ) ) {
			$link = get_the_permalink( $cart_item['course_id'] );
		}

		return $link;
	}

	/**
	 * @param array $item
	 * @param array $cart_item : value by cart id;
	 * show item cart in page checkout
	 */
	public function lp_cert_review_order_cart_item_cer( $item, $cart_item ) {

		if ( ! empty( $cart_item['course_id'] ) ) {
			$item = learn_press_get_course( $cart_item['course_id'] );
		}

		return $item;
	}

	/**
	 * @param array $data
	 * @param array $values : value by cart id;
	 * add data item to cart by session
	 */
	public function lp_cert_cart_get_item_form_session( $data, $values ) {
		if ( get_post_type( $values['item_id'] ) == LP_ADDON_CERTIFICATES_CERT_CPT ) {
			$data = array_merge( $values, array( 'data' => get_post( $values['item_id'] ) ) );
		}

		return $data;
	}

	/**
	 * @param int $subtotal
	 * @param array $cart_item
	 * calculate subtotal by item type in cart
	 */
	public function lp_cert_cart_calculate_subtotal( $subtotal, $cart_item ) {
		if ( get_post_type( $cart_item['item_id'] ) == LP_ADDON_CERTIFICATES_CERT_CPT ) {
			$price_cert = get_post_meta( $cart_item['item_id'], '_lp_certificate_price', true );
			$subtotal   = $price_cert * $cart_item['quantity'];
		}

		return $subtotal;
	}

	/**
	 * @param array $title
	 * @param array $cart_item : value by cart id;
	 * @param array $cart_item_key
	 * change title item cart if is not course.
	 */
	public function lp_cert_cart_item_name( $title, $cart_item, $cart_item_key ) {
		if ( get_post_type( $cart_item['item_id'] ) == LP_ADDON_CERTIFICATES_CERT_CPT ) {

			$title_course = '';
			if ( isset( $cart_item['course_id'] ) ) {
				$title_course = get_the_title( $cart_item['course_id'] );
			}

			$title = sprintf(
				'%s %s - %s %s',
				__( 'Certificate:', 'learnpress-certificates' ),
				get_the_title( $cart_item['item_id'] ),
				__( 'Course:', 'learnpress-certificates' ),
				$title_course
			);
		}

		return $title;
	}

	public function lp_cert_cart_item_subtotal( $subtotal, $cart_item, $cart_item_key ) {
		if ( get_post_type( $cart_item['item_id'] ) == LP_ADDON_CERTIFICATES_CERT_CPT ) {
			$price_cert = get_post_meta( $cart_item['item_id'], '_lp_certificate_price', true );
			$row_price  = $price_cert * $cart_item['quantity'];

			return learn_press_format_price( $row_price, true );
		}

		return $subtotal;
	}

	/**
	 * Save price for certificate
	 *
	 * @param int $post_id
	 * @param WP_Post $post
	 *
	 * @return void
	 */
	public function save( int $post_id, WP_Post $post ) {
		// Return if the user doesn't have edit permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		// Verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times.
		if ( ! isset( $_POST['_lp_certificate_price'] ) || ! wp_verify_nonce(
				$_POST['certificates_fields'],
				'lp-cert-settings-backend'
			) ) {
			return;
		}
		// Now that we're authenticated, time to save the data.
		// This sanitizes the data from the field and saves it into an array $events_meta.
		$certs_meta['_lp_certificate_price'] = (float) esc_html( $_POST['_lp_certificate_price'] );
		foreach ( $certs_meta as $key => $value ) :
			// Don't store custom data twice
			if ( 'revision' === $post->post_type ) {
				return;
			}
			if ( get_post_meta( $post_id, $key, false ) ) {
				// If the custom field already has a value, update it.
				update_post_meta( $post_id, $key, $value );
			} else {
				// If the custom field doesn't have a value, add it.
				add_post_meta( $post_id, $key, $value );
			}
		endforeach;
	}

	public function update_course_certificate( $post ) {
		$res = new LP_REST_Response();

		try {
			$course_id = LP_Request::get_param( 'course_id', 0, 'int', 'post' );
			$cert_id   = LP_Request::get_param( 'cert_id', 0, 'int', 'post' );

			if ( $cert_id ) {
				update_post_meta( $course_id, '_lp_cert', $cert_id );
			} else {
				delete_post_meta( $course_id, '_lp_cert' );
			}

			$res->status  = 'success';
			$res->message = __( 'Updated success', 'learnpress-certificates' );
		} catch ( Exception $e ) {
			$res->message = $e->getMessage();
		}

		wp_send_json( $res );
	}

	/**
	 * Show list certificates
	 *
	 * @param $post
	 *
	 * @return void
	 * @since 3.0.0
	 * @version 1.0.1
	 */
	public function display_certificates( $post ) {
		//LP_Addon_Certificates_Preload::$addon->admin_view( 'course-certificates.php' );
		include_once LP_ADDON_CERTIFICATES_PATH . '/inc/admin/AdminCourseCertificates.php';
		$args = [
			'course_id' => $post->ID,
			'paged'     => 1
		];
		/** @uses AdminCourseCertificates::render_certificates() */
		$callBack = [
			'class'  => AdminCourseCertificates::class,
			'method' => 'render_certificates'
		];

		echo Template::instance()->nest_elements(
			[
				'<div id="certificate-browser" class="theme-browser lp-meta-box-course-panels">' => '</div>'
			],
			TemplateAJAX::load_content_via_ajax( $args, $callBack )
		);
	}

	/**
	 * Register Certificate post type
	 *
	 * @since 3.0.0
	 */
	public function register_post_type() {
		//$this->factory = LP_Addon_Certificates_Preload::$addon;

		register_post_type(
			LP_ADDON_CERTIFICATES_CERT_CPT,
			array(
				'labels'             => array(
					'name'          => __( 'Certificate', 'learnpress-certificates' ),
					'menu_name'     => __( 'Certificates', 'learnpress-certificates' ),
					'singular_name' => __( 'Certificate', 'learnpress-certificates' ),
					'add_new_item'  => __( 'Add New Certificate', 'learnpress-certificates' ),
					'edit_item'     => __( 'Edit Certificate', 'learnpress-certificates' ),
					'all_items'     => __( 'Certificates', 'learnpress-certificates' ),
				),
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'has_archive'        => false,
				'capability_type'    => LP_COURSE_CPT,
				'map_meta_cap'       => true,
				'show_in_menu'       => 'learn_press',
				'show_in_admin_bar'  => true,
				'show_in_nav_menus'  => true,
				'supports'           => array(
					'title',
					'excerpt',
					'author',
				),
				'rewrite'            => array( 'slug' => 'certificate' ),
			)
		);

		register_post_type(
			LP_ADDON_CERTIFICATES_USER_CERT_CPT,
			array(
				'labels'             => array(
					'name'          => __( 'User Certificate', 'learnpress-certificates' ),
					'menu_name'     => __( 'User Certificates', 'learnpress-certificates' ),
					'singular_name' => __( 'User Certificate', 'learnpress-certificates' ),
					'add_new_item'  => __( 'Add New Certificate', 'learnpress-certificates' ),
					'edit_item'     => __( 'Edit Certificate', 'learnpress-certificates' ),
					'all_items'     => __( 'User Certificates', 'learnpress-certificates' ),
				),
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => false,
				'has_archive'        => false,
				'capability_type'    => LP_COURSE_CPT,
				'map_meta_cap'       => true,
				'show_in_menu'       => false,
				'show_in_admin_bar'  => false,
				'show_in_nav_menus'  => false,
				'supports'           => array(
					'title',
					'author',
				),
				'rewrite'            => array( 'slug' => 'user-certificate' ),
			)
		);
	}

	/**
	 * Certificates row actions.
	 *
	 * @param array $actions
	 * @param WP_Post $post
	 *
	 * @return mixed
	 */
	public function row_actions( $actions, $post ) {
		// check for your post type
		if ( LP_ADDON_CERTIFICATES_CERT_CPT == $post->post_type ) {
			$actions['export_cert'] = sprintf(
				'<a href="%s">%s</a>',
				admin_url( 'edit.php?post_type=lp_cert&export=' . $post->ID ),
				__( 'Export', 'learnpress-certificates' )
			);
		}

		return $actions;
	}

	/**
	 * Add column to custom post type.
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	public function columns_head( $columns ) {
		switch ( get_post_type() ) {
			case LP_ADDON_CERTIFICATES_CERT_CPT:
				$columns['courses'] = __( 'Courses', 'learnpress-certificates' );
				break;
			case LP_COURSE_CPT:
				$columns['certificate'] = __( 'Certificate', 'learnpress-certificates' );

		}

		return $columns;
	}

	/**
	 * Custom column content.
	 *
	 * @param string $column
	 * @param int $post_id
	 */
	public function columns_content( $column, $post_id = 0 ) {
		global $wpdb;
		switch ( $column ) {
			case 'certificate':
				if ( get_post_type( $post_id ) == LP_ADDON_CERTIFICATES_CERT_CPT ) {
					$certificate_id = $post_id;
				} else {
					$certificate_id = get_post_meta( $post_id, '_lp_cert', true );
				}

				$certificate = new LP_Certificate( $certificate_id );

				if ( $certificate->get_id() ) {
					$preview = $certificate->get_preview();
					if ( $preview ) {
						echo '<div class="course-cert-preview">';
						echo sprintf(
							'<a href="%s"><img src="%s" alt="%s" /></a>',
							get_edit_post_link( $certificate_id ),
							$preview,
							$certificate->get_name()
						);
						echo '</div>';
					}
				} else {
					_e( '-', 'learnpress-certificates' );
				}
				break;

			case 'courses':
				if ( get_post_type() !== LP_ADDON_CERTIFICATES_CERT_CPT ) {
					return;
				}

				$output = '';
				$query  = $wpdb->prepare(
					"
					SELECT p.ID, p.post_title
					FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s
					INNER JOIN {$wpdb->posts} c ON c.ID = pm.meta_value AND pm.meta_key = %s
				    WHERE c.ID = %d
				",
					'_lp_cert',
					'_lp_cert',
					$post_id
				);

				$courses = $wpdb->get_results( $query );
				if ( $courses ) {
					$links = array();
					foreach ( $courses as $course ) {
						$links[] = sprintf(
							'<a href="%s">%s</a>',
							get_edit_post_link( $course->ID ),
							$course->post_title
						);
					}
					$output = join( ' | ', $links );
				}
				if ( $output ) {
					echo $output;
				} else {
					_e( 'Unassigned', 'learnpress-certificates' );
				}
		}
	}

	/**
	 * Remove unused meta boxes
	 */
	public function remove_meta_boxes() {
		remove_meta_box( 'authordiv', LP_ADDON_CERTIFICATES_CERT_CPT, 'normal' );
	}

	/**
	 * Add meta box to certificate screen
	 */
	public function add_meta_boxes() {
		$this->remove_meta_boxes();

		if ( in_array( get_post_status(), array( 'auto-draft' ) ) ) {
			return;
		}

		add_meta_box(
			'certificates-layers',
			esc_html__( 'Layers', 'learnpress-certificates' ),
			array( $this, 'layers' ),
			LP_ADDON_CERTIFICATES_CERT_CPT,
			'side'
		);
		add_meta_box(
			'certificates-options',
			esc_html__( 'Layer Options', 'learnpress-certificates' ),
			array( $this, 'layer_options' ),
			LP_ADDON_CERTIFICATES_CERT_CPT,
			'side'
		);
		add_meta_box(
			'lp_certificate_settings',
			esc_html__( 'Certificate Settings', 'learnpress-certificates' ),
			array( $this, 'lp_certificate_settings' ),
			LP_ADDON_CERTIFICATES_CERT_CPT,
			'normal',
			'low'
		);
	}

	public function lp_certificate_settings() {
		LP_Addon_Certificates_Preload::$addon->admin_view( 'advanced-settings.php' );
	}

	public function layers() {
		LP_Addon_Certificates_Preload::$addon->admin_view( 'box-layers.php' );
	}

	public function layer_options() {
		LP_Addon_Certificates_Preload::$addon->admin_view( 'box-layer-options.php' );
	}

	public function cert_editor() {
		if ( get_post_type() !== LP_ADDON_CERTIFICATES_CERT_CPT ) {
			return;
		}
		LP_Addon_Certificates_Preload::$addon->admin_view( 'cert-editor.php' );
	}
}

return new LP_Certificates_Post_Type();
