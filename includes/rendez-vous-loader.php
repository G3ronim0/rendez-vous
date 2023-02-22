<?php
/**
 * Rendez Vous Loader.
 *
 * Loads the component.
 *
 * @package Rendez_Vous
 * @subpackage Component
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rendez_Vous_Component class.
 *
 * @since 1.0.0
 */
class Rendez_Vous_Component extends BP_Component {

	/**
	 * Constructor method.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$bp = buddypress();

		parent::start(
			'rendez_vous',
			rendez_vous()->get_component_name(),
			rendez_vous()->includes_dir
		);

		$this->includes();

		$bp->active_components[ $this->id ] = '1';

		// Only register the post type on the blog where BuddyPress is activated.
		if ( get_current_blog_id() == bp_get_root_blog_id() ) {
			add_action( 'init', [ &$this, 'register_post_types' ] );
		}

	}

	/**
	 * Include files.
	 *
	 * @since 1.0.0
	 *
	 * @param array $includes The array of includes. Unused.
	 */
	public function includes( $includes = [] ) {

		// Files to include.
		$includes = [
			'rendez-vous-filters.php',
			'rendez-vous-screens.php',
			'rendez-vous-editor.php',
			'class-rendez-vous-editor.php',
			'class-rendez-vous-item.php',
			'class-rendez-vous-widget.php',
			'rendez-vous-ajax.php',
			'rendez-vous-parts.php',
			'rendez-vous-template.php',
			'rendez-vous-functions.php',
		];

		if ( bp_is_active( 'notifications' ) ) {
			$includes[] = 'rendez-vous-notifications.php';
		}

		if ( bp_is_active( 'activity' ) ) {
			$includes[] = 'rendez-vous-activity.php';
		}

		if ( bp_is_active( 'groups' ) ) {
			$includes[] = 'rendez-vous-groups.php';
		}

		if ( is_admin() ) {
			$includes[] = 'rendez-vous-admin.php';
		}

		parent::includes( $includes );

	}

	/**
	 * Set up globals.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args The array of arguments. Unused.
	 */
	public function setup_globals( $args = [] ) {

		// Set up the $globals array to be passed along to parent::setup_globals().
		$args = [
			'slug'                  => rendez_vous()->get_component_slug(),
			'notification_callback' => 'rendez_vous_format_notifications',
			'search_string'         => __( 'Search Rendez Vous...', 'rendez-vous' ),
		];

		// Let BP_Component::setup_globals() do its work.
		parent::setup_globals( $args );

		/**
		 * Filter to change User's default subnav.
		 *
		 * @since 1.1.0
		 *
		 * @param string default subnav to use - "schedule" or "attend".
		 */
		$this->default_subnav = apply_filters( 'rendez_vous_member_default_subnav', rendez_vous()->get_schedule_slug() );

		$this->subnav_position = [
			'schedule' => 10,
			'attend'   => 20,
		];

		if ( rendez_vous()->get_attend_slug() == $this->default_subnav ) {
			$this->subnav_position['attend'] = 5;
		}

	}

	/**
	 * Set up navigation.
	 *
	 * @since 1.0.0
	 *
	 * @param array $main_nav The array of main nav items. Unused.
	 * @param array $sub_nav The array of sub nav items.
	 */
	public function setup_nav( $main_nav = [], $sub_nav = [] ) {

		// Add 'Rendez Vous' to the main navigation.
		$main_nav = [
			'name'                => rendez_vous()->get_component_name(),
			'slug'                => $this->slug,
			'position'            => 80,
			'screen_function'     => [ 'Rendez_Vous_Screens', 'public_screen' ],
			'default_subnav_slug' => $this->default_subnav,
		];

		// Stop if there is no User displayed or logged in.
		if ( ! is_user_logged_in() && ! bp_displayed_user_id() ) {
			return;
		}

		// Determine User to use.
		if ( bp_displayed_user_domain() ) {
			$user_domain = bp_displayed_user_domain();
		} elseif ( bp_loggedin_user_domain() ) {
			$user_domain = bp_loggedin_user_domain();
		} else {
			return;
		}

		$rendez_vous_link = trailingslashit( $user_domain . $this->slug );

		// Add a subnav item under the main Rendez Vous tab.
		$sub_nav[] = [
			'name'            => __( 'Schedule', 'rendez-vous' ),
			'slug'            => rendez_vous()->get_schedule_slug(),
			'parent_url'      => $rendez_vous_link,
			'parent_slug'     => $this->slug,
			'screen_function' => [ 'Rendez_Vous_Screens', 'schedule_screen' ],
			'position'        => $this->subnav_position['schedule'],
		];

		// Add a subnav item under the main Rendez Vous tab.
		$sub_nav[] = [
			'name'            => __( 'Attend', 'rendez-vous' ),
			'slug'            => rendez_vous()->get_attend_slug(),
			'parent_url'      => $rendez_vous_link,
			'parent_slug'     => $this->slug,
			'screen_function' => [ 'Rendez_Vous_Screens', 'attend_screen' ],
			'position'        => $this->subnav_position['attend'],
		];

		parent::setup_nav( $main_nav, $sub_nav );

	}

	/**
	 * Set up the component entries in the WordPress Admin Bar.
	 *
	 * @since 1.0.0
	 *
	 * @param array $wp_admin_nav The array of WordPress admin nav items.
	 */
	public function setup_admin_bar( $wp_admin_nav = [] ) {

		$bp = buddypress();

		// Menus for logged in User.
		if ( is_user_logged_in() ) {

			// Setup the logged in User variables.
			$user_domain      = bp_loggedin_user_domain();
			$rendez_vous_link = trailingslashit( $user_domain . $this->slug );

			// Add the "Example" sub menu.
			$wp_admin_nav[0] = [
				'parent' => $bp->my_account_menu_id,
				'id'     => 'my-account-' . $this->id,
				'title'  => __( 'Rendez Vous', 'rendez-vous' ),
				'href'   => trailingslashit( $rendez_vous_link ),
			];

			// Personal.
			$wp_admin_nav[ $this->subnav_position['schedule'] ] = [
				'parent' => 'my-account-' . $this->id,
				'id'     => 'my-account-' . $this->id . '-schedule',
				'title'  => __( 'Schedule', 'rendez-vous' ),
				'href'   => trailingslashit( $rendez_vous_link . rendez_vous()->get_schedule_slug() ),
			];

			// Screen two.
			$wp_admin_nav[ $this->subnav_position['attend'] ] = [
				'parent' => 'my-account-' . $this->id,
				'id'     => 'my-account-' . $this->id . '-attend',
				'title'  => __( 'Attend', 'rendez-vous' ),
				'href'   => trailingslashit( $rendez_vous_link . rendez_vous()->get_attend_slug() ),
			];

			// Sort WP Admin Nav.
			ksort( $wp_admin_nav );
		}

		parent::setup_admin_bar( $wp_admin_nav );

	}

	/**
	 * Register the rendez_vous post type.
	 *
	 * @since 1.0.0
	 */
	public function register_post_types() {

		// Set up some labels for the post type.
		$rdv_labels = [
			'name'               => __( 'Rendez Vous', 'rendez-vous' ),
			'singular'           => _x( 'Rendez Vous', 'Rendez Vous singular', 'rendez-vous' ),
			'menu_name'          => _x( 'Rendez Vous', 'Rendez Vous menu name', 'rendez-vous' ),
			'all_items'          => _x( 'All Rendez Vous', 'Rendez Vous all items', 'rendez-vous' ),
			'singular_name'      => _x( 'Rendez Vous', 'Rendez Vous singular name', 'rendez-vous' ),
			'add_new'            => _x( 'Add New Rendez Vous', 'Rendez Vous add new', 'rendez-vous' ),
			'edit_item'          => _x( 'Edit Rendez Vous', 'Rendez Vous edit item', 'rendez-vous' ),
			'new_item'           => _x( 'New Rendez Vous', 'Rendez Vous new item', 'rendez-vous' ),
			'view_item'          => _x( 'View Rendez Vous', 'Rendez Vous view item', 'rendez-vous' ),
			'search_items'       => _x( 'Search Rendez Vous', 'Rendez Vous search items', 'rendez-vous' ),
			'not_found'          => _x( 'No Rendez Vous Found', 'Rendez Vous not found', 'rendez-vous' ),
			'not_found_in_trash' => _x( 'No Rendez Vous Found in Trash', 'Rendez Vous not found in trash', 'rendez-vous' ),
		];

		$rdv_args = [
			'label'             => _x( 'Rendez Vous', 'Rendez Vous label', 'rendez-vous' ),
			'labels'            => $rdv_labels,
			'public'            => false,
			'rewrite'           => false,
			'show_ui'           => false,
			'show_in_admin_bar' => false,
			'show_in_nav_menus' => false,
			'capabilities'      => rendez_vous_get_caps(),
			'capability_type'   => [ 'rendez_vous', 'rendez_vouss' ],
			'delete_with_user'  => true,
			'supports'          => [ 'title', 'author' ],
		];

		// Register the post type for attachements.
		register_post_type( 'rendez_vous', $rdv_args );

		parent::register_post_types();

	}

	/**
	 * Register the rendez_vous types taxonomy.
	 *
	 * @since 1.2.0
	 */
	public function register_taxonomies() {

		// Register the taxonomy.
		register_taxonomy( 'rendez_vous_type', 'rendez_vous', [
			'public' => false,
		] );

	}

}

/**
 * Loads Rendez Vous component into the $bp global.
 *
 * @since 1.0.0
 */
function rendez_vous_load_component() {

	$bp = buddypress();

	$bp->rendez_vous = new Rendez_Vous_Component();

}
