<?php
/**
 * Rendez Vous Groups
 *
 * Groups component
 *
 * @package Rendez Vous
 * @subpackage Groups
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Rendez_Vous_Group' ) && class_exists( 'BP_Group_Extension' ) ) :
/**
 * Rendez Vous group class
 *
 * @package Rendez Vous
 * @subpackage Groups
 *
 * @since Rendez Vous (1.1.0)
 */
class Rendez_Vous_Group extends BP_Group_Extension {

	public $screen  = null;

	/**
	 * Constructor
	 *
	 * @package Rendez Vous
	 * @subpackage Groups
	 *
	 * @since Rendez Vous (1.1.0)
	 */
	public function __construct() {
		/**
		 * Init the Group Extension vars
		 */
		$this->init_vars();

		/**
		 * Add actions and filters to extend Rendez-vous
		 */
		$this->setup_hooks();
	}

	/** Group extension methods ***************************************************/

	/**
	 * Registers the Rendez-vous group extension and sets some globals
	 *
	 * @package Rendez Vous
	 * @subpackage Groups
	 *
	 * @since Rendez Vous (1.1.0)
	 *
	 * @uses buddypress()                         to get the BuddyPress instance
	 * @uses Rendez_Vous_Group->enable_nav_item() to display or not the Rendez-vous nav item for the group
	 * @uses BP_Group_Extension::init()
	 */
	public function init_vars() {
		$bp = buddypress();

		$args = array(
			'slug'              => rendez_vous()->get_component_slug(),
			'name'              => rendez_vous()->get_component_name(),
			'visibility'        => 'public',
			'nav_item_position' => 80,
			'enable_nav_item'   => $this->enable_nav_item(),
			'screens'           => array(
				'admin' => array(
					'enabled'          => true,
					'metabox_context'  => 'side',
					'metabox_priority' => 'core'
				),
				'create' => array(
					'position' => 80,
					'enabled'  => true,
				),
				'edit' => array(
					'position' => 80,
					'enabled'  => true,
				),
			)
		);

        parent::init( $args );
	}

	/**
	 * Loads Rendez-vous navigation if the group activated the extension
	 *
	 * @package Rendez Vous
	 * @subpackage Groups
	 *
	 * @since Rendez Vous (1.1.0)
	 *
	 * @uses   bp_get_current_group_id()             to get the group id
	 * @uses   Rendez_Vous_Group::group_get_option() to check if extension is active for the group.
	 * @return bool                                  true if the extension is active for the group, false otherwise
	 */
	public function enable_nav_item() {
		$group_id = bp_get_current_group_id();

		if ( empty( $group_id ) ){
			return false;
		}

		return (bool) self::group_get_option( $group_id, '_rendez_vous_group_activate', false );
	}

	/**
	 * The create screen method
	 *
	 * @package Rendez Vous
	 * @subpackage Groups
	 *
	 * @since Rendez Vous (1.1.0)
	 *
	 * @param  int $group_id the group ID
	 * @uses   bp_is_group_creation_step() to make sure it's the extension create step
	 * @uses   bp_get_new_group_id() to get the just created group ID
	 * @uses   Rendez_Vous_Group->edit_screen() to display the group extension settings form
	 */
	public function create_screen( $group_id = null ) {
		// Bail if not looking at this screen
		if ( ! bp_is_group_creation_step( $this->slug ) ) {
			return false;
		}

		// Check for possibly empty group_id
		if ( empty( $group_id ) ) {
			$group_id = bp_get_new_group_id();
		}

		return $this->edit_screen( $group_id );
	}

	/**
	 * The create screen save method
	 *
	 * @package Rendez Vous
	 * @subpackage Groups
	 *
	 * @since Rendez Vous (1.1.0)
	 *
	 * @param  int                                   $group_id the group ID
	 * @uses   bp_get_new_group_id()                 to get the just created group ID
	 * @uses   Rendez_Vous_Group->edit_screen_save() to save the group extension settings
	 */
	public function create_screen_save( $group_id = null ) {
		// Check for possibly empty group_id
		if ( empty( $group_id ) ) {
			$group_id = bp_get_new_group_id();
		}

		return $this->edit_screen_save( $group_id );
	}

	/**
	 * Group extension settings form
	 *
	 * Used in Group Administration, Edit and Create screens
	 *
	 * @package Rendez Vous
	 * @subpackage Groups
	 *
	 * @since Rendez Vous (1.1.0)
	 *
	 * @param  int                                   $group_id the group ID
	 * @uses   is_admin()                            to check if we're in WP Administration
	 * @uses   checked()                             to add a checked attribute to checkbox if needed
	 * @uses   Rendez_Vous_Group::group_get_option() to get the needed group metas.
	 * @uses   bp_is_group_admin_page()              to check if the group edit screen is displayed
	 * @uses   wp_nonce_field()                      to add a security token to check upon once submitted
	 * @return string                                html output
	 */
	public function edit_screen( $group_id = null ) {
		if ( empty( $group_id ) ) {
			$group_id = bp_get_current_group_id();
		}

		$is_admin = is_admin();

		if ( ! $is_admin ) : ?>

			<h4><?php printf( esc_html__( '%s group settings', 'rendez-vous' ), $this->name ); ?></h4>

		<?php endif; ?>

		<fieldset>

			<?php if ( $is_admin ) : ?>

				<legend class="screen-reader-text"><?php printf( esc_html__( '%s group settings', 'rendez-vous' ), $this->name ); ?></legend>

			<?php endif; ?>

			<div class="field-group">
				<div class="checkbox">
					<label>
						<label for="_rendez_vous_group_activate">
							<input type="checkbox" id="_rendez_vous_group_activate" name="_rendez_vous_group_activate" value="1" <?php checked( self::group_get_option( $group_id, '_rendez_vous_group_activate', false ) )?>>
								<?php printf( __( 'Activate %s.', 'rendez-vous' ), $this->name );?>
							</input>
						</label>
					</label>
				</div>
			</div>

			<?php if ( bp_is_group_admin_page() ) : ?>
				<input type="submit" name="save" value="<?php _e( 'Save', 'rendez-vous' );?>" />
			<?php endif; ?>

		</fieldset>

		<?php
		wp_nonce_field( 'groups_settings_save_' . $this->slug, 'rendez_vous_group_admin' );
	}


	/**
	 * Save the settings for the current the group
	 *
	 * @package Rendez Vous
	 * @subpackage Groups
	 *
	 * @since Rendez Vous (1.1.0)
	 *
	 * @param int                       $group_id the group id we save settings for
	 * @uses  check_admin_referer()     to check the request was made on the site
	 * @uses  bp_get_current_group_id() to get the group id
	 * @uses  wp_parse_args()           to merge args with defaults
	 * @uses  groups_update_groupmeta() to set the extension option
	 * @uses  bp_is_group_admin_page()  to check the group edit screen is displayed
	 * @uses  bp_core_add_message()     to give a feedback to the user
	 * @uses  bp_core_redirect()        to safely redirect the user
	 * @uses  bp_get_group_permalink()  to build the group permalink
	 */
	public function edit_screen_save( $group_id = null ) {

		if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
			return false;
		}

		check_admin_referer( 'groups_settings_save_' . $this->slug, 'rendez_vous_group_admin' );

		if ( empty( $group_id ) ) {
			$group_id = bp_get_current_group_id();
		}

		$settings = array(
			'_rendez_vous_group_activate' => 0,
		);

		if ( ! empty( $_POST['_rendez_vous_group_activate'] ) ) {
			$s = wp_parse_args( $_POST, $settings );

			$settings = array_intersect_key(
				array_map( 'absint', $s ),
				$settings
			);
		}

		// Save group settings
		foreach ( $settings as $meta_key => $meta_value ) {
			groups_update_groupmeta( $group_id, $meta_key, $meta_value );
		}

		if ( bp_is_group_admin_page() || is_admin() ) {

			// Only redirect on Manage screen
			if ( bp_is_group_admin_page() ) {
				bp_core_add_message( __( 'Settings saved successfully', 'rendez-vous' ) );
				bp_core_redirect( bp_get_group_permalink( buddypress()->groups->current_group ) . 'admin/' . $this->slug );
			}
		}
	}

	/**
	 * Adds a Meta Box in Group's Administration screen
	 *
	 * @package Rendez Vous
	 * @subpackage Groups
	 *
	 * @since Rendez Vous (1.1.0)
	 *
	 * @param  int                              $group_id  the group id
	 * @uses   Rendez_Vous_Group->edit_screen() to display the group extension settings form
	 */
	public function admin_screen( $group_id = null ) {
		$this->edit_screen( $group_id );
	}

	/**
	 * Saves the group settings (set in the Meta Box of the Group's Administration screen)
	 *
	 * @package Rendez Vous
	 * @subpackage Groups
	 *
	 * @since Rendez Vous (1.1.0)
	 *
	 * @param  int                                   $group_id  the group id
	 * @uses   Rendez_Vous_Group->edit_screen_save() to save the group extension settings
	 */
	public function admin_screen_save( $group_id = null ) {
		$this->edit_screen_save( $group_id );
	}

	/**
	 * Perform actions about rendez-vous (insert/edit/delete/save prefs)
	 *
	 * @package Rendez Vous
	 * @subpackage Groups
	 *
	 * @since Rendez Vous (1.1.0)
	 *
	 * @uses  Rendez_Vous_Group->is_rendez_vous()   Checks whether we're on a rendez-vous page of a group
	 * @uses  rendez_vous()                         to get the plugin's instance
	 * @uses  rendez_vous_handle_actions()          to insert/edit/delete/save prefs about a rendez-vous
	 * @uses  bp_get_current_group_id()             to get the group id
	 * @uses  Rendez_Vous_Group::group_get_option() to get the needed group metas.
	 * @uses  groups_is_user_member()               to check the organizer is still a member of the group
	 * @uses  delete_post_meta()                    to remove a rendez-vous from a group
	 * @uses  rendez_vous_get_single_link()         to get the rendez-vous link
	 * @uses  bp_core_add_message()                 to give a feedback to the user
	 * @uses  do_action()                           call 'rendez_vous_groups_component_deactivated' or
	 *                                                   'rendez_vous_groups_member_removed' to perform custom actions
	 * @uses  bp_core_redirect()                    to safely redirect the user
	 * @uses  bp_is_current_component()             to check for a BuddyPress component
	 * @uses  bp_current_item()                     to make sure a group item is requested
	 * @uses  bp_do_404()                           to set the WP Query to a 404.
	 */
	public function group_handle_screens() {
		if ( $this->is_rendez_vous() ) {

			$rendez_vous = rendez_vous();

			$this->screen                 = rendez_vous_handle_actions();
			$rendez_vous->screens->screen = $this->screen;
			$group_id                     = bp_get_current_group_id();

			/**
			 * Should we remove the rendez-vous from the group ?
			 *
			 * Although, this is already handled in Rendez_Vous_Group->group_rendez_vous_link()
			 * an invited user can click on an email he received where the link is a group rendez-vous link.
			 * @see rendez_vous_published_notification()
			 *
			 * Not checking if notifications are active, because there's also an edge case when the activity
			 * has not been deleted yet and the user clicks on the activity link.
			 */
			if ( 'single' == $this->screen && ! empty( $rendez_vous->item->id ) ) {

				$message = $action = false;

				// The group doesn't support rendez-vous anymore
				if ( ! self::group_get_option( $group_id, '_rendez_vous_group_activate', false ) ) {
					$message = __( 'The Group, the rendez-vous was attached to, does not support rendez-vous anymore', 'rendez-vous' );
					$action  = 'rendez_vous_groups_component_deactivated';

				// The organizer was removed or left the group
				} else if ( ! groups_is_user_member( $rendez_vous->item->organizer, $group_id ) ) {
					$message = sprintf( __( '%s is not a member of the group, the rendez-vous was attached to, anymore. As a result, the rendez-vous was removed from the group.', 'rendez-vous' ), bp_core_get_user_displayname( $rendez_vous->item->organizer ) );
					$action  = 'rendez_vous_groups_member_removed';
				}

				// Bail if everything is ok.
				if ( empty( $message ) ) {
					return;
				}

				// Delete the rendez-vous group id meta
				delete_post_meta( $rendez_vous->item->id, '_rendez_vous_group_id' );
				$redirect = rendez_vous_get_single_link( $rendez_vous->item->id, $rendez_vous->item->organizer );
				bp_core_add_message( $message, 'error' );

				// fire an action to deal with group activities
				do_action( $action, $rendez_vous->item->id, $rendez_vous->item );

				// Redirect to organizer's rendez-vous page
				bp_core_redirect( $redirect );
			}
		} else if ( bp_is_current_component( 'groups' ) && bp_is_current_action( $this->slug ) && bp_current_item() ) {
			bp_do_404();
			return;
		}
	}

	/**
	 * Loads needed Rendez-vous template parts
	 *
	 * @package Rendez Vous
	 * @subpackage Groups
	 *
	 * @since Rendez Vous (1.1.0)
	 *
	 * @uses   rendez_vous_edit_title()     to output the title in edit context
	 * @uses   rendez_vous_edit_content()   to output the form to edit the rendez-vous
	 * @uses   rendez_vous_single_title()   to output the title in display context
	 * @uses   rendez_vous_single_content() to output the rendez-vous content
	 * @uses   rendez_vous_editor()         to load the rendez-vous BackBone editor
	 * @uses   bp_get_current_group_id()    to get the current group id
	 * @uses   rendez_vous_loop()           to output the rendez-vous for the group
	 * @return string                       html output
	 */
	public function display( $group_id = null ) {
		if ( ! empty( $this->screen ) )  {
			if ( 'edit' == $this->screen ) {
				?>
				<h1><?php rendez_vous_edit_title();?></h1>
				<?php rendez_vous_edit_content();
			} else if ( 'single' ==  $this->screen ) {
				?>
				<h1><?php rendez_vous_single_title();?></h1>
				<?php rendez_vous_single_content();
			}
		} else {
			if ( empty( $group_id ) ) {
				$group_id = bp_get_current_group_id();
			}
			?>
			<h3>
				<ul id="rendez-vous-nav">
					<li><?php rendez_vous_editor( 'new-rendez-vous', array( 'group_id' => $group_id ) ); ?></li>
					<li class="last"><?php render_vous_type_filter(); ?></li>
				</ul>
			</h3>
			<?php rendez_vous_loop();
		}
	}

	/**
	 * We do not use group widgets
	 *
	 * @package Rendez Vous
	 * @subpackage Groups
	 *
	 * @since Rendez Vous (1.1.0)
	 *
	 * @return boolean false
	 */
	public function widget_display() {
		return false;
	}

	/**
	 * Gets the group meta, use default if meta value is not set
	 *
	 * @package Rendez Vous
	 * @subpackage Groups
	 *
	 * @since Rendez Vous (1.1.0)
	 *
	 * @param  int                    $group_id the group ID
	 * @param  string                 $option   meta key
	 * @param  mixed                  $default  the default value to fallback with
	 * @uses   groups_get_groupmeta() to get the meta value
	 * @uses   apply_filters()        call "rendez_vous_groups_option{$option}" to override the group meta value
	 * @return mixed                  the meta value
	 */
	public static function group_get_option( $group_id = 0, $option = '', $default = '' ) {
		if ( empty( $group_id ) || empty( $option ) ) {
			return false;
		}

		$group_option = groups_get_groupmeta( $group_id, $option );

		if ( '' === $group_option ) {
			$group_option = $default;
		}

		/**
		 * @param   mixed $group_option the meta value
		 * @param   int   $group_id     the group ID
		 */
		return apply_filters( "rendez_vous_groups_option{$option}", $group_option, $group_id );
	}

	/**
	 * Checks whether we're on a rendez-vous page of a group
	 *
	 * @package Rendez Vous
	 * @subpackage Groups
	 *
	 * @since Rendez Vous (1.1.0)
	 *
	 * @param  bool                   $retval
	 * @uses   bp_is_group()          to check we're in a group
	 * @uses   bp_is_current_action() to be sure we're on a rendez-vous group page
	 * @return bool                   true if on rendez-vous page of a group, false otherwise
	 */
	public function is_rendez_vous( $retval = false ) {
		if ( bp_is_group() && bp_is_current_action( $this->slug ) ) {
			$retval = true;
		}

		return $retval;
	}

	/**
	 * Update the last activity of the group when a rendez-vous attached to it is saved
	 *
	 * @package Rendez Vous
	 * @subpackage Groups
	 *
	 * @since Rendez Vous (1.1.0)
	 *
	 * @param  Rendez_Vous_Item              $rendez_vous the rendez-vous object
	 * @uses   groups_update_last_activity() to update group's latest activity
	 */
	public function group_last_activity( $rendez_vous = null ) {
		if ( empty( $rendez_vous->group_id ) ) {
			return;
		}

		// Update group's latest activity
		groups_update_last_activity( $rendez_vous->group_id );
	}

	/**
	 * Map rendez-vous caps for the group's context
	 *
	 * @package Rendez Vous
	 * @subpackage Groups
	 *
	 * @since Rendez Vous (1.1.0)
	 *
	 * @param  array                      $caps Capabilities for meta capability
	 * @param  string                     $cap Capability name
	 * @param  int                        $user_id User id
	 * @param  mixed                      $args Arguments
	 * @uses   bp_is_group()              to make sure the user is displaying a group
	 * @uses   groups_get_current_group() to get the current group object
	 * @uses   groups_is_user_member()    to check if the user is a member of the group
	 * @uses   groups_is_user_admin()     to check if the user is an admin of the group
	 * @return array                      Actual capabilities for meta capability
	 */
	public function map_meta_caps( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {
		if ( ! bp_is_group() || empty( $user_id ) ) {
			return $caps;
		}

		$group = groups_get_current_group();

		switch ( $cap ) {
			case 'publish_rendez_vouss' :
				if ( ! empty( $group->id ) && groups_is_user_member( $user_id, $group->id ) ) {
					$caps = array( 'exist' );
				}

				break;

			case 'subscribe_rendez_vous' :
				if ( groups_is_user_member( $user_id, $group->id ) ) {
					$caps = array( 'exist' );
				} else {
					$caps = array( 'manage_options' );
				}

				break;

			// Group Admins have full powers
			case 'read_private_rendez_vouss'  :
			case 'edit_rendez_vouss'          :
			case 'edit_others_rendez_vouss'   :
			case 'edit_rendez_vous'           :
			case 'delete_rendez_vous'         :
			case 'delete_rendez_vouss'        :
			case 'delete_others_rendez_vouss' :

				if ( ! in_array( 'exist', $caps ) && groups_is_user_admin( $user_id, $group->id ) ) {
					$caps = array( 'exist' );
				}

				break;
		}

		return $caps;
	}

	/**
	 * Appends the group args to rendez-vous loop arguments
	 *
	 * @package Rendez Vous
	 * @subpackage Groups
	 *
	 * @since Rendez Vous (1.1.0)
	 *
	 * @param  array                     $args the rendez-vous loop arguments
	 * @uses   bp_is_group()             to make sure the user is displaying a group
	 * @uses   bp_get_current_group_id() to get the current group id
	 * @return array                     the rendez-vous loop arguments
	 */
	public function append_group_args( $args = array() ) {
		// if in a group's single item
		if ( bp_is_group() ) {
			$args['group_id'] = bp_get_current_group_id();
		}

		// If viewing a single member
		if ( bp_is_user() ) {

			/**
			 * Use this filter to show all displayed user's rendez-vous no matter if they are attached to an hidden group
			 * eg: add_filter( 'rendez_vous_member_hide_hidden', '__return_false' );
			 *
			 * To respect the hidden group visibility, by default, a member not viewing his profile will be returned false
			 * avoiding him to see the displayed member's rendez-vous attached to an hidden group
			 *
			 * @param bool false if a user is viewing his profile or an admin is viewing any user profile, true otherwise
			 */
			$hide_hidden = apply_filters( 'rendez_vous_member_hide_hidden', (bool) ! bp_is_my_profile() && ! bp_current_user_can( 'bp_moderate' ) );

			if ( ! empty( $hide_hidden ) ) {
				$args['exclude'] = self::get_hidden_rendez_vous();
			}
		}

		return $args;
	}

	/**
	 * Gets the user's rendez-vous that are attached to an hidden group
	 *
	 * As, it's not possible to do a mix of 'AND' and 'OR' relation with WP_Meta_Queries,
	 * we are using the exclude args of the rendez-vous loop to exclude the rendez-vous
	 * ids that are attached to an hidden group.
	 *
	 * @package Rendez Vous
	 * @subpackage Groups
	 *
	 * @since Rendez Vous (1.1.0)
	 *
	 * @param   int                    $user_id the user id
	 * @global  $wpdb
	 * @uses    buddypress()           to get BuddyPress main instance
	 * @uses    bp_displayed_user_id() to get the user id of the displayed profile
	 * @return  array                  the list of rendez-vous to hide for the user
	 */
	public static function get_hidden_rendez_vous( $user_id = 0 ) {
		global $wpdb;
		$bp = buddypress();

		if ( empty( $user_id ) ) {
			$user_id = bp_displayed_user_id();
		}

		if ( empty( $user_id ) ) {
			return array();
		}

		// BP_Groups_Member::get_group_ids does not suit the need
		$user_hidden_groups = $wpdb->prepare( "SELECT DISTINCT m.group_id FROM {$bp->groups->table_name_members} m LEFT JOIN {$bp->groups->table_name} g ON ( g.id = m.group_id ) WHERE g.status = 'hidden' AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0", $user_id );

		// Get the rendez-vous attached to an hidden group of the user.
		$hidden_rendez_vous = "SELECT pm.post_id FROM {$wpdb->postmeta} pm WHERE pm.meta_key = '_rendez_vous_group_id' AND pm.meta_value IN ( {$user_hidden_groups} )";
		$hide = $wpdb->get_col( $hidden_rendez_vous );

		return $hide;
	}

	/**
	 * Set the current action
	 *
	 * @package Rendez Vous
	 * @subpackage Groups
	 *
	 * @since Rendez Vous (1.1.0)
	 *
	 * @param  string        $action the action
	 * @uses   bp_is_group() to make sure the user is displaying a group
	 * @return string        $action the action
	 */
	public function group_current_action( $action = '' ) {
		if ( ! bp_is_group() ) {
			return $action;
		}

		if ( empty( $_GET ) ) {
			$action = 'schedule';
		}

		return $action;
	}

	/**
	 * Make sure the organizer id remains the same in case a rendez-vous
	 * is edited by a group admin or a site admin
	 *
	 * @package Rendez Vous
	 * @subpackage Groups
	 *
	 * @since Rendez Vous (1.1.0)
	 *
	 * @param  int              $organizer_id the organizer id
	 * @param  array            $args         the rendez-vous 'save' arguments
	 * @uses   bp_is_group()    to make sure the user is displaying a group
	 * @uses   get_post_field() to get a specific field for a post type object
	 * @return int              the organizer id
	 */
	public function group_edit_get_organizer_id( $organizer_id = 0, $args = array() ) {
		if ( ! bp_is_group() || empty( $args['id'] ) ) {
			return $organizer_id;
		}

		$rendez_vous_id = intval( $args['id'] );
		$author = get_post_field( 'post_author', $rendez_vous_id );

		if ( empty( $author ) ) {
			return $organizer_id;
		}

		return $author;
	}

	/**
	 * Builds the rendez-vous link in the group's context
	 *
	 * @package Rendez Vous
	 * @subpackage Groups
	 *
	 * @since Rendez Vous (1.1.0)
	 *
	 * @param  int                                   $id         the rendez-vous id
	 * @param  int                                   $organizer  the organizer id
	 * @uses   get_post_meta()                       to get the group, the rendez-vous is attached to
	 * @uses   Rendez_Vous_Group::group_get_option() to get the needed group metas.
	 * @uses   groups_is_user_member()               to check the organizer is still a member of the group
	 * @uses   delete_post_meta()                    to remove a rendez-vous from a group
	 * @uses   do_action()                           call 'rendez_vous_groups_component_deactivated' or
	 *                                                    'rendez_vous_groups_member_removed' to perform custom actions
	 * @uses   groups_get_current_group()            to get the current group object
	 * @uses   groups_get_group()                    to get a group using a group ID
	 * @uses   bp_get_group_permalink()              to get the group's permalink
	 * @return string                                the permalink to the rendez-vous in a group
	 */
	public function group_rendez_vous_link( $id = 0, $organizer = 0 ) {
		$link = $action = false;

		if ( empty( $id ) || empty( $organizer ) ) {
			return $link;
		}

		$group_id = get_post_meta( $id, '_rendez_vous_group_id', true );

		if ( empty( $group_id ) ) {
			return $link;
		}

		if ( ! self::group_get_option( $group_id, '_rendez_vous_group_activate', false ) ) {
			$action = 'rendez_vous_groups_component_deactivated';
		} else if ( ! groups_is_user_member( $organizer, $group_id ) ) {
			$action = 'rendez_vous_groups_member_removed';
		}

		/**
		 * If the group does not support rendez-vous or
		 * the organizer is not a member of the group anymore
		 * Remove post meta & activities to be sure the organize
		 * can always access to his rendez-vous.
		 */
		if ( ! empty( $action ) ) {
			delete_post_meta( $id, '_rendez_vous_group_id' );
			do_action( $action, $id, get_post( $id ) );
			return $link;
		}

		// Everything is ok, build the group rendez-vous link
		$group = groups_get_current_group();

		if ( empty( $group->id ) || $group_id == $group->id ) {
			$group = groups_get_group( array( 'group_id' => $group_id, 'populate_extras' => false ) );

			$link = trailingslashit( bp_get_group_permalink( $group ) . $this->slug );
		}

		return $link;
	}

	/**
	 * Returns the rendez-vous edit link in the group's context
	 *
	 * @package Rendez Vous
	 * @subpackage Groups
	 *
	 * @since Rendez Vous (1.1.0)
	 *
	 * @param  string                                      $link  the rendez-vous edit link
	 * @param  int                                         $id        the rendez-vous id
	 * @param  int                                         $organizer the organizer id
	 * @uses   Rendez_Vous_Group->group_rendez_vous_link() to build the rendez-vous link for a group.
	 * @uses   add_query_arg()                             to a add query vars to an url
	 * @return string                                      the rendez-vous edit link
	 */
	public function group_edit_link( $link = '', $id = 0, $organizer = 0 ) {
		if ( empty( $id ) ) {
			return $link;
		}

		$group_link = $this->group_rendez_vous_link( $id, $organizer );

		if ( empty( $group_link ) ) {
			return $link;
		}

		$link = add_query_arg(
			array( 'rdv' => $id, 'action' => 'edit' ),
			$group_link
		);

		return $link;
	}

	/**
	 * Returns the rendez-vous link in the group's context
	 *
	 * @package Rendez Vous
	 * @subpackage Groups
	 *
	 * @since Rendez Vous (1.1.0)
	 *
	 * @param  string                                      $link  the rendez-vous link
	 * @param  int                                         $id        the rendez-vous id
	 * @param  int                                         $organizer the organizer id
	 * @uses   Rendez_Vous_Group->group_rendez_vous_link() to build the rendez-vous link for a group.
	 * @uses   add_query_arg()                             to a add query vars to an url
	 * @return string                                      the rendez-vous link
	 */
	public function group_view_link( $link = '', $id = 0, $organizer = 0 ) {
		if ( empty( $id ) ) {
			return $link;
		}

		$group_link = $this->group_rendez_vous_link( $id, $organizer );

		if ( empty( $group_link ) ) {
			return $link;
		}

		$link = add_query_arg(
			array( 'rdv' => $id ),
			$group_link
		);

		return $link;
	}

	/**
	 * Returns the rendez-vous delete link in the group's context
	 *
	 * @package Rendez Vous
	 * @subpackage Groups
	 *
	 * @since Rendez Vous (1.1.0)
	 *
	 * @param  string                                      $link  the rendez-vous delete link
	 * @param  int                                         $id        the rendez-vous id
	 * @param  int                                         $organizer the organizer id
	 * @uses   Rendez_Vous_Group->group_rendez_vous_link() to build the rendez-vous link for a group.
	 * @uses   add_query_arg()                             to a add query vars to an url
	 * @uses   wp_nonce_url()                              to add a security token to check upon once the link clicked
	 * @return string                                      the rendez-vous delete link
	 */
	public function group_delete_link( $link = '', $id = 0, $organizer = 0 ) {
		if ( empty( $id ) ) {
			return $link;
		}

		$group_link = $this->group_rendez_vous_link( $id, $organizer );

		if ( empty( $group_link ) ) {
			return $link;
		}

		$link = add_query_arg( array( 'rdv' => $id, 'action' => 'delete' ), $group_link );
		$link = wp_nonce_url( $link, 'rendez_vous_delete' );

		return $link;
	}

	/**
	 * Builds the rendez-vous edit form action
	 *
	 * @package Rendez Vous
	 * @subpackage Groups
	 *
	 * @since Rendez Vous (1.1.0)
	 *
	 * @param  string                     $action         the form action
	 * @param  int                        $rendez_vous_id the rendez-vous id
	 * @uses   bp_is_group()              to make sure the user is displaying a group
	 * @uses   groups_get_current_group() to get the current group object
	 * @uses   bp_get_group_permalink()   to get the group's permalink
	 * @return string                     the form action
	 */
	public function group_form_action( $action = '', $rendez_vous_id = 0 ) {
		if ( ! bp_is_group() ) {
			return $action;
		}

		$group = groups_get_current_group();

		return trailingslashit( bp_get_group_permalink( $group ) . $this->slug );
	}

	/**
	 * Returns the activity args for a rendez-vous saved within a group
	 *
	 * @package Rendez Vous
	 * @subpackage Groups
	 *
	 * @since Rendez Vous (1.1.0)
	 *
	 * @param  array                      $args the activity arguments
	 * @uses   bp_is_group()              to make sure the user is displaying a group
	 * @uses   esc_url()                  to sanitize the url
	 * @uses   groups_get_current_group() to get the current group object
	 * @uses   bp_get_group_permalink()   to get the group's permalink
	 * @uses   esc_html()                 to sanitize output
	 * @uses   buddypress()               to get BuddyPress instance
	 * @return array                      the activity arguments
	 */
	public function group_activity_save_args( $args = array() ) {
		if ( ! bp_is_group() || empty( $args['action'] ) ) {
			return $args;
		}

		$group = groups_get_current_group();

		$group_link = '<a href="' . esc_url( bp_get_group_permalink( $group ) ) . '">' . esc_html( $group->name ) . '</a>';

		$action         = $args['action'] . ' ' . sprintf( __( 'in %s', 'rendez-vous' ), $group_link );
		$rendez_vous_id = $args['item_id'];
		$hide_sitewide  = false;

		if ( 'public' != $group->status ) {
			$hide_sitewide = true;
		}

		$args = array_merge( $args, array(
			'action'            => $action,
			'component'         => buddypress()->groups->id,
			'item_id'           => $group->id,
			'secondary_item_id' => $rendez_vous_id,
			'hide_sitewide'     => $hide_sitewide,
		) );

		return $args;
	}

	/**
	 * Returns the activity delete arguments for a rendez-vous removed from a group
	 *
	 * @package Rendez Vous
	 * @subpackage Groups
	 *
	 * @since Rendez Vous (1.1.0)
	 *
	 * @param  array                      $args the activity delete arguments
	 * @uses   bp_is_group()              to make sure the user is displaying a group
	 * @uses   groups_get_current_group() to get the current group object
	 * @uses   buddypress()               to get BuddyPress instance
	 * @return array                      the activity delete arguments
	 */
	public function group_activity_delete_args( $args = array() ) {
		if ( ! bp_is_group() || empty( $args['item_id'] ) ) {
			return $args;
		}

		$group = groups_get_current_group();
		$rendez_vous_id = $args['item_id'];

		$args = array(
			'item_id'           => $group->id,
			'secondary_item_id' => $rendez_vous_id,
			'component'         => buddypress()->groups->id,
		);

		return $args;
	}

	/**
	 * Format the activity action for the rendez-vous attached to a group
	 *
	 * @package Rendez Vous
	 * @subpackage Groups
	 *
	 * @since Rendez Vous (1.1.0)
	 *
	 * @param  string                     $action   the activity action string
	 * @param  BP_Activity_Activity       $activity the activity object
	 * @uses   buddypress()               to get BuddyPress instance
	 * @uses   esc_url()                  to sanitize the url
	 * @uses   groups_get_current_group() to get the current group object
	 * @uses   bp_get_group_permalink()   to get the group's permalink
	 * @uses   esc_html()                 to sanitize output
	 * @return string                     the activity action string
	 */
	public function format_activity_action( $action = '', $activity = null ) {
		// Bail if not a rendez vous activity posted in a group
		if ( buddypress()->groups->id != $activity->component || empty( $action ) ) {
			return $action;
		}

		$group = groups_get_group( array(
			'group_id'        => $activity->item_id,
			'populate_extras' => false,
		) );

		if ( empty( $group ) ) {
			return $action;
		}

		$group_link = '<a href="' . esc_url( bp_get_group_permalink( $group ) ) . '">' . esc_html( $group->name ) . '</a>';

		$action .= ' ' . sprintf( __( 'in %s', 'rendez-vous' ), $group_link );
		return $action;
	}

	/**
	 * Returns the rendez-vous avatar in the group's context
	 *
	 * @package Rendez Vous
	 * @subpackage Groups
	 *
	 * @since Rendez Vous (1.1.0)
	 *
	 * @param  string                                $output         the avatar for the rendez-vous
	 * @param  int                                   $rendez_vous_id the rendez-vous id
	 * @uses   bp_is_group()                         to make sure the user is displaying a group
	 * @uses   get_post_meta()                       to get the group id the rendez-vous is attached to
	 * @uses   Rendez_Vous_Group::group_get_option() to get the needed group metas.
	 * @uses   bp_core_fetch_avatar()                to get the group's avatar
	 * @return string                                the avatar for the rendez-vous
	 */
	public function group_rendez_vous_avatar( $output = '', $rendez_vous_id = 0 ) {
		if ( empty( $rendez_vous_id ) || bp_is_group() ) {
			return $output;
		}

		$group_id = get_post_meta( $rendez_vous_id, '_rendez_vous_group_id', true );

		if ( ! empty( $group_id ) && self::group_get_option( $group_id, '_rendez_vous_group_activate', false ) ) {
			$output = '<div class="rendez-vous-avatar">';
			$output .= bp_core_fetch_avatar( array(
				'item_id' => $group_id,
				'object'  => 'group',
				'type'    => 'thumb',
			) );
			$output .= '</div>';
		}

		return $output;
	}

	/**
	 * Returns the rendez-vous status in the group's context
	 *
	 * @package Rendez Vous
	 * @subpackage Groups
	 *
	 * @since Rendez Vous (1.1.0)
	 *
	 * @param  string          $status             the rendez-vous status
	 * @param  int             $rendez_vous_id     the rendez-vous id
	 * @param  string          $rendez_vous_status the rendez-vous post type object status
	 * @uses   get_post_meta() to get the group id the rendez-vous is attached to
	 * @return string          the rendez-vous status
	 */
	public function group_rendez_vous_status( $status = '', $rendez_vous_id = 0, $rendez_vous_status = '' ) {
		if ( empty( $rendez_vous_id ) || empty( $rendez_vous_status ) ) {
			return $status;
		}

		if ( 'publish' == $rendez_vous_status ) {
			$group_id = get_post_meta( $rendez_vous_id, '_rendez_vous_group_id', true );

			if ( ! empty( $group_id ) ) {
				$status = __( 'All group members', 'rendez-vous' );
			}
		}

		return $status;
	}

	/**
	 * Set up group's hooks
	 *
	 * @package Rendez Vous
	 * @subpackage Groups
	 *
	 * @since Rendez Vous (1.1.0)
	 *
	 * @uses  add_action() to perform custom actions at key points
	 * @uses  add_filter() to override rendez-vous key vars
	 */
	public function setup_hooks() {
		add_action( 'bp_screens',                                 array( $this, 'group_handle_screens' ),        2     );
		add_action( 'rendez_vous_after_saved',                    array( $this, 'group_last_activity' ),         10, 1 );
		add_filter( 'rendez_vous_load_scripts',                   array( $this, 'is_rendez_vous' ),              10, 1 );
		add_filter( 'rendez_vous_load_editor',                    array( $this, 'is_rendez_vous' ),              10, 1 );
		add_filter( 'rendez_vous_map_meta_caps',                  array( $this, 'map_meta_caps' ),               10, 4 );
		add_filter( 'rendez_vous_current_action',                 array( $this, 'group_current_action' ),        10, 1 );
		add_filter( 'rendez_vous_edit_action_organizer_id',       array( $this, 'group_edit_get_organizer_id' ), 10, 2 );
		add_filter( 'bp_before_rendez_vouss_has_args_parse_args', array( $this, 'append_group_args' ),           10, 1 );
		add_filter( 'rendez_vous_get_edit_link',                  array( $this, 'group_edit_link' ),             10, 3 );
		add_filter( 'rendez_vous_get_single_link',                array( $this, 'group_view_link' ),             10, 3 );
		add_filter( 'rendez_vous_get_delete_link',                array( $this, 'group_delete_link' ),           10, 3 );
		add_filter( 'rendez_vous_single_the_form_action',         array( $this, 'group_form_action' ),           10, 2 );
		add_filter( 'rendez_vous_published_activity_args',        array( $this, 'group_activity_save_args' ),    10, 1 );
		add_filter( 'rendez_vous_updated_activity_args',          array( $this, 'group_activity_save_args' ),    10, 1 );
		add_filter( 'rendez_vous_delete_item_activities_args',    array( $this, 'group_activity_delete_args' ),  10, 1 );
		add_filter( 'rendez_vous_format_activity_action',         array( $this, 'format_activity_action' ),      10, 3 );
		add_filter( 'rendez_vous_get_avatar',                     array( $this, 'group_rendez_vous_avatar' ),    10, 2 );
		add_filter( 'rendez_vous_get_the_status',                 array( $this, 'group_rendez_vous_status' ),    10, 3 );
	}
}

endif ;

/**
 * Registers the rendez-vous group's component
 *
 * @package Rendez Vous
 * @subpackage Groups
 *
 * @since Rendez Vous (1.1.0)
 *
 * @uses bp_register_group_extension() to register the group extension
 */
function rendez_vous_register_group_extension() {
	bp_register_group_extension( 'Rendez_Vous_Group' );
}
add_action( 'bp_init', 'rendez_vous_register_group_extension' );

/**
 * Register the group's activity actions for the rendez-vous
 *
 * @package Rendez Vous
 * @subpackage Groups
 *
 * @since Rendez Vous (1.1.0)
 *
 * @uses   buddypress()             to get BuddyPress instance
 * @uses   bp_is_active()           to check the activity component is active
 * @uses   bp_activity_set_action() to register the activity actions
 */
function rendez_vous_groups_activity_actions() {
	$bp = buddypress();

	// Bail if activity is not active
	if ( ! bp_is_active( 'activity' ) ) {
		return false;
	}

	$register = true;

	if ( bp_is_group() && ! Rendez_Vous_Group::group_get_option( bp_get_current_group_id(), '_rendez_vous_group_activate', false ) ) {
		$register = false;
	}

	if ( $register ) {
		bp_activity_set_action(
			$bp->groups->id,
			'new_rendez_vous',
			__( 'New rendez-vous in a group', 'rendez-vous' ),
			'rendez_vous_format_activity_action',
			__( 'New rendez-vous', 'rendez-vous' ),
			array( 'group', 'member_groups' )
		);

		bp_activity_set_action(
			$bp->groups->id,
			'updated_rendez_vous',
			__( 'Updated a rendez-vous in a group', 'rendez-vous' ),
			'rendez_vous_format_activity_action',
			__( 'Updated a rendez-vous', 'rendez-vous' ),
			array( 'group', 'member_groups' )
		);
	}
}
add_action( 'rendez_vous_register_activity_actions', 'rendez_vous_groups_activity_actions', 20 );
