<?php

/**
 * Plugin Name: LearnPress - Certificates
 * Plugin URI: https://thimpress.com/product/certificates-add-on-for-learnpress/
 * Description: Create certificates for online courses.
 * Author: ThimPress
 * Version: 3.1.7
 * Author URI: http://thimpress.com
 * Tags: learnpress, lms
 * Text Domain: learnpress-certificates
 * Domain Path: /languages/
 */

defined('ABSPATH') || exit;

define('LP_ADDON_CERTIFICATES_FILE', __FILE__);
define('LP_ADDON_CERTIFICATES_VERSION_REQUIRES', '3.0.9');
define('LP_ADDON_CERTIFICATES_VER', '3.1.7');
define('LP_ADDON_CERTIFICATES_DEBUG', 1);
define('LP_CERTIFICATE_FOLDER_NAME', str_replace(array('/', basename(__FILE__)), "", plugin_basename(__FILE__)));

/**
 * Class LP_Addon_Certificates_Preload
 */
class LP_Addon_Certificates_Preload
{

	/**
	 * LP_Addon_Certificates_Preload constructor.
	 */
	public function __construct()
	{
		add_action('learn-press/ready', array($this, 'load'));
		add_action('admin_notices', array($this, 'admin_notices'));
	}

	/**
	 * Load addon
	 */
	public function load()
	{
		LP_Addon::load('LP_Addon_Certificates', 'inc/load.php', __FILE__);
		remove_action('admin_notices', array($this, 'admin_notices'));
	}

	/**
	 * Admin notice
	 */
	public function admin_notices()
	{
?>
		<div class="error">
			<p><?php echo wp_kses(
					sprintf(
						__('<strong>%s</strong> addon version %s requires %s version %s or higher is <strong>installed</strong> and <strong>activated</strong>.', 'learnpress-certificates'),
						__('LearnPress Certificates', 'learnpress-certificates'),
						LP_ADDON_CERTIFICATES_VER,
						sprintf('<a href="%s" target="_blank"><strong>%s</strong></a>', admin_url('plugin-install.php?tab=search&type=term&s=learnpress'), __('LearnPress', 'learnpress-certificates')),
						LP_ADDON_CERTIFICATES_VERSION_REQUIRES
					),
					array(
						'a'      => array(
							'href'  => array(),
							'blank' => array()
						),
						'strong' => array()
					)
				); ?>
			</p>
		</div>
<?php
	}
}

new LP_Addon_Certificates_Preload();
