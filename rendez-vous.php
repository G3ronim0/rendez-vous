<?php
/**
 * Plugin Name: Rendez Vous
 * Plugin URI: https://sadler-jerome.fr/tag/rendez-vous
 * Description: Rendez Vous is a BuddyPress plugin to schedule appointments with your buddies
 * Version: 1.4.3
 * Author: G3ronim0
 * Author URI: https://sadler-jerome.fr
 * Text Domain: rendez-vous
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path: /languages/
 * GitHub Plugin URI: https://github.com/G3ronim0/rendez-vous
 *
 * @wordpress-plugin
 * @package Rendez_Vous
 * @author G3ronim0 https://twitter.com/G3r0nimo
 * @license GPL-2.0+
 * @link https://sadler-jerome.fr
 * @copyright 2014 G3ronim0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'Rendez_Vous' ) ) :

	/**
	 * Main Rendez Vous Class
	 *
	 * @since 1.0.0
	 */
	class Rendez_Vous {

		/**
		 * Instance of this class.
		 *
		 * @since 1.0.0
		 *
		 * @var object
		 */
		protected static $instance = null;

		/**
		 * Required BuddyPress version for the plugin.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		public static $required_bp_version = '2.5.0';

		/**
		 * BuddyPress config.
		 *
		 * @since 1.0.0
		 *
		 * @var array
		 */
		public static $bp_config = [];

		/**
		 * Initialize the plugin.
		 *
		 * @since 1.0.0
		 */
		private function __construct() {

			$this->setup_globals();
			$this->includes();
			$this->setup_hooks();

		}

		/**
		 * Return an instance of this class.
		 *
		 * @since 1.0.0
		 *
		 * @return    object    A single instance of this class.
		 */
		public static function start() {

			// If the single instance hasn't been set, set it now.
			if ( null == self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;

		}

		/**
		 * Sets some globals for the plugin.
		 *
		 * @since 1.0.0
		 */
		private function setup_globals() {

			// Define a global that will hold the current version number.
			$this->version = '1.4.3';

			// Define a global to get the textdomain of your plugin.
			$this->domain = 'rendez-vous';

			$this->file     = __FILE__;
			$this->basename = plugin_basename( $this->file );

			// Define a global that we can use to construct file paths throughout the component.
			$this->plugin_dir = plugin_dir_path( $this->file );

			// Define a global that we can use to construct file paths starting from the includes directory.
			$this->includes_dir = trailingslashit( $this->plugin_dir . 'includes' );

			// Define a global that we can use to construct file paths starting from the includes directory.
			$this->lang_dir = trailingslashit( $this->plugin_dir . 'languages' );

			$this->plugin_url   = plugin_dir_url( $this->file );
			$this->includes_url = trailingslashit( $this->plugin_url . 'includes' );

			// Define a global that we can use to construct url to the javascript scripts needed by the component.
			$this->plugin_js = trailingslashit( $this->includes_url . 'js' );

			// Define a global that we can use to construct url to the css needed by the component.
			$this->plugin_css = trailingslashit( $this->includes_url . 'css' );

		}

		/**
		 * Include the component's loader.
		 *
		 * @since 1.0.0
		 */
		private function includes() {

			if ( self::bail() ) {
				return;
			}

			require $this->includes_dir . 'rendez-vous-loader.php';

		}

		/**
		 * Sets the key hooks to add an action or a filter to
		 *
		 * @since 1.0.0
		 */
		private function setup_hooks() {

			if ( ! self::bail() ) {

				// Load the component.
				add_action( 'bp_loaded', 'rendez_vous_load_component' );

				// Enqueue the needed script and css files.
				add_action( 'bp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

				// Loads the languages.
				add_action( 'bp_loaded', [ $this, 'load_textdomain' ] );

			} else {

				// Display a warning message in network admin or admin.
				add_action( self::$bp_config['network_active'] ? 'network_admin_notices' : 'admin_notices', [ $this, 'warning' ] );

			}

		}

		/**
		 * Display a warning message to admin
		 *
		 * @since 1.0.0
		 */
		public function warning() {

			$warnings = [];

			if ( ! self::version_check() ) {
				/* translators: %s The required version of BuddyPress. */
				$warnings[] = sprintf( __( 'Rendez Vous requires at least version %s of BuddyPress.', 'rendez-vous' ), self::$required_bp_version );
			}

			if ( ! empty( self::$bp_config ) ) {
				$config = self::$bp_config;
			} else {
				$config = self::config_check();
			}

			if ( ! bp_core_do_network_admin() && ! $config['blog_status'] ) {
				$warnings[] = __( 'Rendez Vous requires to be activated on the blog where BuddyPress is activated.', 'rendez-vous' );
			}

			if ( bp_core_do_network_admin() && ! $config['network_status'] ) {
				$warnings[] = __( 'Rendez Vous and BuddyPress need to share the same network configuration.', 'rendez-vous' );
			}

			if ( ! empty( $warnings ) ) :
				?>
				<div id="message" class="error">
					<?php foreach ( $warnings as $warning ) : ?>
						<p><?php echo esc_html( $warning ); ?></p>
					<?php endforeach; ?>
				</div>
				<?php
			endif;

		}

		/**
		 * Enqueue scripts if your component is loaded
		 *
		 * @since 1.0.0
		 */
		public function enqueue_scripts() {

			$load_scripts = apply_filters( 'rendez_vous_load_scripts', bp_is_current_component( 'rendez_vous' ) );

			if ( empty( $load_scripts ) ) {
				return;
			}

			$suffix = SCRIPT_DEBUG ? '' : '.min';

			wp_register_script( 'rendez-vous-plupload', includes_url( "js/plupload/wp-plupload$suffix.js" ), [], $this->version, 1 );
			wp_localize_script( 'rendez-vous-plupload', 'pluploadL10n', [] );
			wp_register_script( 'rendez-vous-media-views', includes_url( "js/media-views$suffix.js" ), [ 'utils', 'media-models', 'rendez-vous-plupload', 'jquery-ui-sortable' ], $this->version, 1 );
			wp_register_script( 'rendez-vous-media-editor', includes_url( "js/media-editor$suffix.js" ), [ 'shortcode', 'rendez-vous-media-views' ], $this->version, 1 );
			wp_register_script( 'rendez-vous-modal', $this->plugin_js . "rendez-vous-backbone$suffix.js", [ 'rendez-vous-media-editor', 'jquery-ui-datepicker' ], $this->version, 1 );

			// Allow themes to override modal style.
			$modal_style = apply_filters( 'rendez_vous_modal_css', $this->plugin_css . "rendezvous-editor$suffix.css", $suffix );
			wp_register_style( 'rendez-vous-modal-style', $modal_style, [ 'media-views' ], $this->version );

			// Allow themes to override global style.
			$global_style = apply_filters(
				'rendez_vous_global_css',
				[
					'style' => $this->plugin_css . "rendezvous$suffix.css",
					'deps'  => [ 'dashicons' ],
				],
				$suffix
			);

			wp_enqueue_style( 'rendez-vous-style', $global_style['style'], (array) $global_style['deps'], $this->version );
			wp_enqueue_script( 'rendez-vous-script', $this->plugin_js . "rendezvous$suffix.js", [ 'jquery' ], $this->version, 1 );
			wp_localize_script( 'rendez-vous-script', 'rendez_vous_vars', [
				'confirm'  => esc_html__( 'Are you sure you want to cancel this Rendez Vous?', 'rendez-vous' ),
				'noaccess' => esc_html__( 'This Rendez Vous is restricted and you have not been invited to it.', 'rendez-vous' ),
			] );

		}

		/** Utilities *****************************************************************************/

		/**
		 * Checks BuddyPress version
		 *
		 * @since 1.0.0
		 */
		public static function version_check() {

			// Taking no risks.
			if ( ! defined( 'BP_VERSION' ) ) {
				return false;
			}

			return version_compare( BP_VERSION, self::$required_bp_version, '>=' );

		}

		/**
		 * Checks if your plugin's config is similar to BuddyPress
		 *
		 * @since 1.0.0
		 */
		public static function config_check() {

			/*
			 * blog_status    : true if your plugin is activated on the same blog.
			 * network_active : true when your plugin is activated on the network.
			 * network_status : BuddyPress & your plugin share the same network status.
			 */
			self::$bp_config = [
				'blog_status'    => false,
				'network_active' => false,
				'network_status' => true,
			];

			if ( get_current_blog_id() == bp_get_root_blog_id() ) {
				self::$bp_config['blog_status'] = true;
			}

			$network_plugins = get_site_option( 'active_sitewide_plugins', [] );

			// No Network plugins.
			if ( empty( $network_plugins ) ) {
				return self::$bp_config;
			}

			$rendez_vous_basename = plugin_basename( __FILE__ );

			// Looking for BuddyPress and your plugin.
			$check = [ buddypress()->basename, $rendez_vous_basename ];

			// Are they active on the network?
			$network_active = array_diff( $check, array_keys( $network_plugins ) );

			// If result is 1, your plugin is network activated
			// and not BuddyPress or vice & versa. Config is not ok.
			if ( count( $network_active ) == 1 ) {
			self::$bp_config['network_status'] = false;
			}

			// We need to know if the plugin is network activated to choose the right
			// notice ( admin or network_admin ) to display the warning message.
			self::$bp_config['network_active'] = isset( $network_plugins[ $rendez_vous_basename ] );

			return self::$bp_config;

		}

		/**
		 * Bail if BuddyPress config is different than this plugin.
		 *
		 * @since 1.0.0
		 */
		public static function bail() {

			$retval = false;

			$config = self::config_check();

			if ( ! self::version_check() || ! $config['blog_status'] || ! $config['network_status'] ) {
			$retval = true;
			}

			return $retval;

		}

		/**
		 * Loads the translation files.
		 *
		 * @since 1.0.0
		 *
		 * @uses get_locale() to get the language of WordPress config.
		 * @uses load_texdomain() to load the translation if any is available for the language.
		 * @uses load_plugin_textdomain() to load the translation if any is available for the language.
		 */
		public function load_textdomain() {

			// Traditional WordPress plugin locale filter.
			$locale = apply_filters( 'plugin_locale', get_locale(), $this->domain );
			$mofile = sprintf( '%1$s-%2$s.mo', $this->domain, $locale );

			// Setup paths to a Rendez Vous subfolder in WP LANG DIR.
			$mofile_global = WP_LANG_DIR . '/rendez-vous/' . $mofile;

			// Look in global /wp-content/languages/rendez-vous folder.
			if ( ! load_textdomain( $this->domain, $mofile_global ) ) {

				/*
				 * Look in local folders:
				 * "/wp-content/plugins/rendez-vous/languages/" or
				 * "/wp-content/languages/plugins/"
				 */
				load_plugin_textdomain( $this->domain, false, basename( $this->plugin_dir ) . '/languages' );

			}

		}

		/**
		 * Get the component name of the plugin
		 *
		 * @since 1.2.0
		 *
		 * @uses apply_filters() call 'rendez_vous_get_component_name' to override default component name.
		 */
		public static function get_component_name() {
			return apply_filters( 'rendez_vous_get_component_name', __( 'Rendez Vous', 'rendez-vous' ) );
		}

		/**
		 * Get the component slug of the plugin.
		 *
		 * @since 1.2.0
		 *
		 * @uses apply_filters() call 'rendez_vous_get_component_slug' to override default component slug.
		 */
		public static function get_component_slug() {

			// Defining the slug in this way makes it possible for site admins to override it.
			if ( ! defined( 'RENDEZ_VOUS_SLUG' ) ) {
				define( 'RENDEZ_VOUS_SLUG', 'rendez-vous' );
			}

			return RENDEZ_VOUS_SLUG;

		}

		/**
		 * Get the schedule slug of the component.
		 *
		 * @since 1.2.0
		 *
		 * @uses apply_filters() call 'rendez_vous_get_schedule_slug' to override default schedule slug.
		 */
		public static function get_schedule_slug() {
			return 'schedule';
		}

		/**
		 * Get the attend slug of the component.
		 *
		 * @since 1.2.0
		 *
		 * @uses apply_filters() call 'rendez_vous_get_attend_slug' to override default attend slug.
		 */
		public static function get_attend_slug() {
			return 'attend';
		}

	}

endif;

/**
 * BuddyPress is loaded and initialized, let's start.
 *
 * @since 1.0.0
 */
function rendez_vous() {
	return Rendez_Vous::start();
}

add_action( 'bp_include', 'rendez_vous' );
