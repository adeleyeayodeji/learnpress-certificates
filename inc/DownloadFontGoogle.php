<?php
/**
 * Download fonts from Google Fonts API to local.
 *
 * @package LearnPress/Certificates
 * @version 1.0.0
 * @since 4.0.7
 */
namespace LearnPress\Certificates;

// Download all fonts from Google Fonts API to local.
use Exception;
use LP_WP_Filesystem;

class DownloadFontGoogle {

	protected $url;

	protected $filesystem;

	protected $font_folder = 'learn-press-cert/fonts';

	protected $content_fonts_load_via_api = '';

	public function __construct( $url ) {
		$this->url        = $url;
		$this->filesystem = LP_WP_Filesystem::instance();
	}

	public function get_styles() {
		$fonts  = $this->get_fonts_local();
		$styles = $this->content_fonts_load_via_api;
		if ( empty( $styles ) ) {
			return '';
		}

		// replace src google fonts to local fonts in $styles.
		foreach ( $fonts as $font ) {
			$styles = str_replace(
				$font['src'],
				$font['url'],
				$styles
			);
		}

		return $styles;
	}

	protected function get_fonts_local() {
		$fonts = $this->download_fonts_to_local();

		// Add file url to fonts.
		$fonts = array_map(
			function( $font ) {
				$font['url'] = $this->get_font_url( $font['name'], $font['folder'] );
				return $font;
			},
			$fonts
		);

		return $fonts;
	}

	protected function get_font_url( $font_file_name, $font_family_folder ) {
		$upload_dir = wp_upload_dir();
		$base_url   = untrailingslashit( get_site_url() );
		$url        = str_replace( $base_url, '', $upload_dir['baseurl'] );
		$fonts_dir  = $url . '/' . $this->font_folder;

		return $fonts_dir . '/' . $font_family_folder . '/' . $font_file_name;
	}

	public function download_fonts_to_local() {
		$fonts        = $this->get_files_from_contents();
		$stored       = array();
		$stored_names = array();

		// download fonts to folder fonts in wp_content.
		$upload_dir = wp_upload_dir();
		$fonts_dir  = $upload_dir['basedir'] . '/' . $this->font_folder;

		if ( ! file_exists( $fonts_dir ) ) {
			wp_mkdir_p( $fonts_dir );
		}

		foreach ( $fonts as $font ) {
			if ( in_array( $font['name'], $stored_names ) ) {
				continue;
			}

			// create folder font-family if not exists.
			$font_family_dir = $fonts_dir . '/' . $font['folder'];

			if ( ! file_exists( $font_family_dir ) ) {
				wp_mkdir_p( $font_family_dir );
			}

			$font_file_path = $font_family_dir . '/' . $font['name'];

			if ( ! file_exists( $font_file_path ) ) {
				// Get each font file content to download.
				$font_file_contents = $this->get_font_file_contents( $font['src'] );

				if ( $font_file_contents ) {
					$this->filesystem->put_contents( $font_file_path, $font_file_contents );
				}
			}

			// update file path.
			$font['path'] = $font_file_path;
			$stored[]     = $font;
		}

		return $stored;
	}

	private function get_font_file_contents( $font_src ) {
		$font_src = str_replace( 'https://', 'http://', $font_src );

		$response = wp_remote_get(
			$font_src,
			array(
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$contents = wp_remote_retrieve_body( $response );

		if ( is_wp_error( $contents ) || empty( $contents ) ) {
			return false;
		}

		return $contents;
	}

	/**
	 * Load content API font from url set to check on Google Fonts.
	 *
	 * @throws Exception
	 */
	public function get_files_from_contents(): array {
		$contents                         = $this->get_google_api_content_list_fonts();
		$this->content_fonts_load_via_api = $contents;

		// get all font-face in $contents.
		$found_valid = strpos( $contents, '@font-face' );
		if ( false === $found_valid ) {
			throw new Exception( 'No font-face found in contents from ' . $this->url );
		}
		$font_faces = explode( '@font-face', $contents );

		// remove first element.
		array_shift( $font_faces );

		$fonts = array();

		foreach ( $font_faces as $font_face ) {
			$fonts = array_merge( $fonts, $this->get_fonts_from_font_face( $font_face ) );
		}

		return $fonts;
	}

	private function get_fonts_from_font_face( $font_face ) {
		// get font-family.
		$pattern = '/font-family:\s*\'(.*)\';/i';
		preg_match( $pattern, $font_face, $matches );

		$font_family = $matches[1];

		// get font-weight.
		$pattern = '/font-weight:\s*(.*);/i';
		preg_match( $pattern, $font_face, $matches );

		$font_weight = $matches[1];

		// get font-style.
		$pattern = '/font-style:\s*(.*);/i';
		preg_match( $pattern, $font_face, $matches );

		$font_style = $matches[1];

		// get font-display.
		$pattern = '/font-display:\s*(.*);/i';
		preg_match( $pattern, $font_face, $matches );

		$font_display = $matches[1];

		// get src.
		$pattern = '/src:\s*url\((.*)\)\s*format\(\'(.*)\'\);/i';
		preg_match( $pattern, $font_face, $matches );

		$font_src    = $matches[1];
		$font_format = $matches[2];

		// font_name get file name in font_src.
		$file_name = basename( $font_src );

		$folder_name = explode( '/', $font_src );
		$folder_name = $folder_name[4];

		$font = array(
			'name'   => $file_name,
			// 'family' => $font_family,
			'folder' => $folder_name,
			// 'weight' => $font_weight,
			// 'style'  => $font_style,
			'src'    => $font_src,
			// 'format' => $font_format,
		);

		return array( $font );
	}

	protected function get_google_api_content_list_fonts() {
		$user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/603.3.8 (KHTML, like Gecko) Version/10.1.2 Safari/603.3.8';

		$response = wp_remote_get(
			$this->url,
			array(
				'timeout'    => 30,
				'user-agent' => $user_agent,
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$contents = wp_remote_retrieve_body( $response );

		if ( is_wp_error( $contents ) || empty( $contents ) ) {
			return false;
		}

		return $contents;
	}
}
