<?php
/**
 * Rendez Vous Groups.
 *
 * Groups component.
 *
 * @package Rendez_Vous
 * @subpackage Groups
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Rendez_Vous_Group' ) && class_exists( 'BP_Group_Extension' ) ) :

	/**
	 * Rendez Vous Group class.
	 *
	 * @since 1.1.0
	 */
	class Rendez_Vous_Group extends BP_Group_Extension {

		/**
		 * Screen identifier.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		public $screen = null;

		/**
		 * Constructor.
		 *
		 * @since 1.1.0
		 */
		public function __construct() {

			// Init the Group Extension vars.
			$this->init_vars();

			// Add actions and filters to extend Rendez Vous.
			$this->setup_hooks();

		}

		/** Group extension methods ***************************************************/

		/**
		 * Registers the Rendez Vous Group extension and sets some globals.
		 *
		 * @since 1.1.0
		 */
		public function init_vars() {

			$bp = buddypress();

			$args = [
				'slug'              => rendez_vous()->get_component_slug(),
				'name'              => rendez_vous()->get_component_name(),
				'visibility'        => 'public',
				'nav_item_position' => 80,
				'enable_nav_item'   => $this->enable_nav_item(),
				'screens'           => [
					'admin' => [
						'enabled'          => true,
						'metabox_context'  => 'side',
						'metabox_priority' => 'core',
					],
					'create' => [
						'position' => 80,
						'enabled'  => true,
					],
					'edit' => [
						'position' => 80,
						'enabled'  => true,
					],
				],
			];

			parent::init( $args );

		}

		/**
		 * Set up Group's hooks.
		 *
		 * @since 1.1.0
		 */
		public function setup_hooks() {

			add_action( 'bp_screens', [ $this, 'group_handle_screens' ], 2 );
			add_action( 'rendez_vous_after_saved', [ $this, 'group_last_activity' ], 10, 1 );
			add_filter( 'rendez_vous_load_scripts', [ $this, 'is_rendez_vous' ], 10, 1 );
			add_filter( 'rendez_vous_load_editor', [ $this, 'is_rendez_vous' ], 10, 1 );
			add_filter( 'rendez_vous_map_meta_caps', [ $this, 'map_meta_caps' ], 10, 4 );
			add_filter( 'rendez_vous_current_action', [ $this, 'group_current_action' ], 10, 1 );
			add_filter( 'rendez_vous_edit_action_organizer_id', [ $this, 'group_edit_get_organizer_id' ], 10, 2 );
			add_filter( 'bp_before_rendez_vouss_has_args_parse_args', [ $this, 'append_group_args' ], 10, 1 );
			add_filter( 'rendez_vous_get_edit_link', [ $this, 'group_edit_link' ], 10, 3 );
			add_filter( 'rendez_vous_get_single_link', [ $this, 'group_view_link' ], 10, 3 );
			add_filter( 'rendez_vous_get_delete_link', [ $this, 'group_delete_link' ], 10, 3 );
			add_filter( 'rendez_vous_single_the_form_action', [ $this, 'group_form_action' ], 10, 2 );
			add_filter( 'rendez_vous_published_activity_args', [ $this, 'group_activity_save_args' ], 10, 1 );
			add_filter( 'rendez_vous_updated_activity_args', [ $this, 'group_activity_save_args' ], 10, 1 );
			add_filter( 'rendez_vous_delete_item_activities_args', [ $this, 'group_activity_delete_args' ], 10, 1 );
			add_filter( 'rendez_vous_format_activity_action', [ $this, 'format_activity_action' ], 10, 3 );
			add_filter( 'rendez_vous_get_avatar', [ $this, 'group_rendez_vous_avatar' ], 10, 2 );
			add_filter( 'rendez_vous_get_the_status', [ $this, 'group_rendez_vous_status' ], 10, 3 );

		}

		/**
		 * Loads Rendez Vous navigation if the Group activated the extension.
		 *
		 * @since 1.1.0
		 *
		 * @return bool True if the extension is active for the Group, false otherwise.
		 */
		public function enable_nav_item() {

			$group_id = bp_get_current_group_id();

			if ( empty( $group_id ) ) {
				return false;
			}

			return (bool) self::group_get_option( $group_id, '_rendez_vous_group_activate', false );

		}

		/**
		 * The create screen method.
		 *
		 * @since 1.1.0
		 *
		 * @param int $group_id The Group ID.
		 */
		public function create_screen( $group_id = null ) {

			// Bail if not looking at this screen.
			if ( ! bp_is_group_creation_step( $this->slug ) ) {
				return false;
			}

			// Check for possibly empty Group ID.
			if ( empty( $group_id ) ) {
				$group_id = bp_get_new_group_id();
			}

			return $this->edit_screen( $group_id );

		}

		/**
		 * The create screen save method.
		 *
		 * @since 1.1.0
		 *
		 * @param int $group_id The Group ID.
		 */
		public function create_screen_save( $group_id = null ) {

			// Check for possibly empty Group ID.
			if ( empty( $group_id ) ) {
				$group_id = bp_get_new_group_id();
			}

			return $this->edit_screen_save( $group_id );

		}

		/**
		 * Group extension settings form.
		 *
		 * Used in Group Administration, Edit and Create screens.
		 *
		 * @since 1.1.0
		 *
		 * @param int $group_id The Group ID.
		 */
		public function edit_screen( $group_id = null ) {

			if ( empty( $group_id ) ) {
				$group_id = bp_get_current_group_id();
			}

			$is_admin = is_admin();

			?>

			<?php if ( ! $is_admin ) : ?>
				<?php /* translators: %s: The name of the Rendez Vous. */ ?>
				<h4><?php printf( esc_html__( '%s group settings', 'rendez-vous' ), $this->name ); ?></h4>
			<?php endif; ?>

			<fieldset>

				<?php if ( $is_admin ) : ?>
					<?php /* translators: %s: The name of the Rendez Vous. */ ?>
					<legend class="screen-reader-text"><?php printf( esc_html__( '%s group settings', 'rendez-vous' ), $this->name ); ?></legend>
				<?php endif; ?>

				<?php do_action( 'rendez_vous_group_edit_screen_before', $group_id ); ?>

				<div class="field-group">
					<div class="checkbox">
						<label>
							<label for="_rendez_vous_group_activate">
								<input type="checkbox" id="_rendez_vous_group_activate" name="_rendez_vous_group_activate" value="1" <?php checked( self::group_get_option( $group_id, '_rendez_vous_group_activate', false ) ); ?>>
									<?php /* translators: %s: The name of the Rendez Vous. */ ?>
									<?php printf( __( 'Activate %s.', 'rendez-vous' ), $this->name ); ?>
								</input>
							</label>
						</label>
					</div>
				</div>

				<?php do_action( 'rendez_vous_group_edit_screen_after', $group_id ); ?>

				<?php if ( bp_is_group_admin_page() ) : ?>
					<input type="submit" name="save" value="<?php esc_atr_e( 'Save', 'rendez-vous' ); ?>" />
				<?php endif; ?>

			</fieldset>

			<?php

			wp_nonce_field( 'groups_settings_save_' . $this->slug, 'rendez_vous_group_admin' );

		}


		/**
		 * Save the settings for the current the Group.
		 *
		 * @since 1.1.0
		 *
		 * @param int $group_id The Group ID we save settings for.
		 */
		public function edit_screen_save( $group_id = null ) {

			if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
				return false;
			}

			check_admin_referer( 'groups_settings_save_' . $this->slug, 'rendez_vous_group_admin' );

			if ( empty( $group_id ) ) {
				$group_id = bp_get_current_group_id();
			}

			$settings = [
				'_rendez_vous_group_activate' => 0,
			];

			if ( ! empty( $_POST['_rendez_vous_group_activate'] ) ) {
				$s = wp_parse_args( $_POST, $settings );
				$settings = array_intersect_key(
					array_map( 'absint', $s ),
					$settings
				);
			}

			// Save Group settings.
			foreach ( $settings as $meta_key => $meta_value ) {
				groups_update_groupmeta( $group_id, $meta_key, $meta_value );
			}

			/**
			 * Broadcast end of save procedure.
			 *
			 * @since 1.4.3
			 *
			 * @param int $group_id The numeric ID of the Group.
			 * @param array $settings The Rendez Vous settings.
			 */
			do_action( 'rendez_vous_group_edit_screen_save', $group_id, $settings );

			if ( bp_is_group_admin_page() || is_admin() ) {

				// Only redirect on Manage screen.
				if ( bp_is_group_admin_page() ) {
					bp_core_add_message( __( 'Settings saved successfully', 'rendez-vous' ) );
					bp_core_redirect( bp_get_group_permalink( buddypress()->groups->current_group ) . 'admin/' . $this->slug );
				}
			}

		}

		/**
		 * Adds a Meta Box in Group's Administration screen.
		 *
		 * @since 1.1.0
		 *
		 * @param int $group_id The Group ID.
		 */
		public function admin_screen( $group_id = null ) {
			$this->edit_screen( $group_id );
		}

		/**
		 * Saves the Group settings - set in the Meta Box of the Group's Administration screen.
		 *
		 * @since 1.1.0
		 *
		 * @param int $group_id The Group ID.
		 */
		public function admin_screen_save( $group_id = null ) {
			$this->edit_screen_save( $group_id );
		}

		/**
		 * Perform actions about Rendez Vous (insert/edit/delete/save prefs).
		 *
		 * @since 1.1.0
		 */
		public function group_handle_screens() {

			if ( $this->is_rendez_vous() ) {

				$rendez_vous = rendez_vous();

				$this->screen                 = rendez_vous_handle_actions();
				$rendez_vous->screens->screen = $this->screen;
				$group_id                     = bp_get_current_group_id();

				/*
				 * Should we remove the Rendez Vous from the Group?
				 *
				 * Although, this is already handled in Rendez_Vous_Group->group_rendez_vous_link()
				 * an invited User can click on an email he received where the link is a Group Rendez Vous link.
				 *
				 * @see rendez_vous_published_notification()
				 *
				 * Not checking if notifications are active, because there's also an edge case when the activity
				 * has not been deleted yet and the User clicks on the activity link.
				 */
				if ( 'single' == $this->screen && ! empty( $rendez_vous->item->id ) ) {

					$message = false;
					$action = false;

					// The Group doesn't support Rendez Vous anymore.
					if ( ! self::group_get_option( $group_id, '_rendez_vous_group_activate', false ) ) {
						$message = __( 'The Group that the Rendez Vous was attached to does not support Rendez Vous anymore', 'rendez-vous' );
						$action  = 'rendez_vous_groups_component_deactivated';

					// The Organizer was removed or left the Group.
					} elseif ( ! groups_is_user_member( $rendez_vous->item->organizer, $group_id ) ) {
						$message = sprintf(
							/* translators: %s: The name of the Rendez Vous Organizer. */
							__( '%s is no longer a member of the group that the Rendez Vous was attached to. As a result, the Rendez Vous was removed from the group.', 'rendez-vous' ),
							bp_core_get_user_displayname( $rendez_vous->item->organizer )
						);
						$action = 'rendez_vous_groups_member_removed';
					}

					// Bail if everything is ok.
					if ( empty( $message ) ) {
						return;
					}

					// Delete the Rendez Vous Group ID meta.
					delete_post_meta( $rendez_vous->item->id, '_rendez_vous_group_id' );
					$redirect = rendez_vous_get_single_link( $rendez_vous->item->id, $rendez_vous->item->organizer );
					bp_core_add_message( $message, 'error' );

					// fire an action to deal with Group activities.
					do_action( $action, $rendez_vous->item->id, $rendez_vous->item );

					// Redirect to Organizer's Rendez Vous page.
					bp_core_redirect( $redirect );
				}

			} elseif ( bp_is_current_component( 'groups' ) && bp_is_current_action( $this->slug ) && bp_current_item() ) {

				bp_do_404();
				return;

			}

		}

		/**
		 * Loads needed Rendez Vous template parts.
		 *
		 * @since 1.1.0
		 *
		 * @param int $group_id The Group ID.
		 */
		public function display( $group_id = null ) {

			if ( ! empty( $this->screen ) ) {

				if ( 'edit' == $this->screen ) {
					?>
					<h1><?php rendez_vous_edit_title(); ?></h1>
					<?php
					rendez_vous_edit_content();
				} elseif ( 'single' == $this->screen ) {
					?>
					<h1><?php rendez_vous_single_title(); ?></h1>
					<?php
					rendez_vous_single_content();
				}

			} else {

				if ( empty( $group_id ) ) {
					$group_id = bp_get_current_group_id();
				}
				?>
				<h3>
					<ul id="rendez-vous-nav">
						<li><?php rendez_vous_editor( 'new-rendez-vous', [ 'group_id' => $group_id ] ); ?></li>
						<li class="last"><?php render_vous_type_filter(); ?></li>
					</ul>
				</h3>
				<?php

				rendez_vous_loop();

			}

		}

		/**
		 * We do not use Group widgets.
		 *
		 * @since 1.1.0
		 *
		 * @return boolean Always false.
		 */
		public function widget_display() {
			return false;
		}

		/**
		 * Gets the Group meta, use default if meta value is not set.
		 *
		 * @since 1.1.0
		 *
		 * @param int $group_id The Group ID.
		 * @param string $option The meta key.
		 * @param mixed $default The default value to fallback with.
		 * @return mixed The meta value.
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
			 * Filters the Rendez Vous option.
			 *
			 * @since 1.1.0
			 *
			 * @param mixed $group_option The meta value.
			 * @param int $group_id The Group ID.
			 */
			return apply_filters( "rendez_vous_groups_option{$option}", $group_option, $group_id );

		}

		/**
		 * Checks whether we're on a Rendez Vous page of a Group.
		 *
		 * @since 1.1.0
		 *
		 * @param bool $retval The existing return value.
		 * @return bool $retval True if on Rendez Vous page of a Group, false otherwise.
		 */
		public function is_rendez_vous( $retval = false ) {

			if ( bp_is_group() && bp_is_current_action( $this->slug ) ) {
				$retval = true;
			}

			return $retval;

		}

		/**
		 * Update the last activity of the Group when a Rendez Vous attached to it is saved.
		 *
		 * @since 1.1.0
		 *
		 * @param Rendez_Vous_Item $rendez_vous The Rendez Vous object.
		 */
		public function group_last_activity( $rendez_vous = null ) {

			if ( empty( $rendez_vous->group_id ) ) {
				return;
			}

			// Update Group's latest activity.
			groups_update_last_activity( $rendez_vous->group_id );

		}

		/**
		 * Map Rendez Vous caps for the Group's context.
		 *
		 * @since 1.1.0
		 *
		 * @param array $caps Capabilities for meta capability.
		 * @param string $cap Capability name.
		 * @param int $user_id User ID.
		 * @param mixed $args Arguments.
		 * @return array Actual capabilities for meta capability.
		 */
		public function map_meta_caps( $caps = [], $cap = '', $user_id = 0, $args = [] ) {

			if ( ! bp_is_group() || empty( $user_id ) ) {
				return $caps;
			}

			$group = groups_get_current_group();

			switch ( $cap ) {
				case 'publish_rendez_vouss':
					if ( ! empty( $group->id ) && groups_is_user_member( $user_id, $group->id ) ) {
						$caps = [ 'exist' ];
					}

					break;

				case 'subscribe_rendez_vous':
					if ( groups_is_user_member( $user_id, $group->id ) ) {
						$caps = [ 'exist' ];
					} else {
						$caps = [ 'manage_options' ];
					}

					break;

				// Group Admins have full powers.
				case 'read_private_rendez_vouss':
				case 'edit_rendez_vouss':
				case 'edit_others_rendez_vouss':
				case 'edit_rendez_vous':
				case 'delete_rendez_vous':
				case 'delete_rendez_vouss':
				case 'delete_others_rendez_vouss':

					if ( ! in_array( 'exist', $caps ) && groups_is_user_admin( $user_id, $group->id ) ) {
						$caps = [ 'exist' ];
					}

					break;
			}

			return $caps;

		}

		/**
		 * Appends the Group args to Rendez Vous loop arguments.
		 *
		 * @since 1.1.0
		 *
		 * @param array$args The Rendez Vous loop arguments.
		 * @return array The Rendez Vous loop arguments.
		 */
		public function append_group_args( $args = [] ) {

			// if in a Group's single item.
			if ( bp_is_group() ) {
				$args['group_id'] = bp_get_current_group_id();
			}

			// If viewing a single Member.
			if ( bp_is_user() ) {

				/**
				 * Filters the displayed User's Rendez Vous.
				 *
				 * Use this filter to show all displayed User's Rendez Vous no matter if
				 * they are attached to an hidden Group, e.g.
				 *
				 * add_filter( 'rendez_vous_member_hide_hidden', '__return_false' );
				 *
				 * To respect the hidden Group visibility, by default, a Member not
				 * viewing his profile will be returned false avoiding him to see the
				 * displayed Member's Rendez Vous attached to an hidden Group.
				 *
				 * @param bool False if a User is viewing his profile or an admin is viewing any User profile, true otherwise.
				 */
				$hide_hidden = apply_filters( 'rendez_vous_member_hide_hidden', (bool) ! bp_is_my_profile() && ! bp_current_user_can( 'bp_moderate' ) );

				if ( ! empty( $hide_hidden ) ) {
					$args['exclude'] = self::get_hidden_rendez_vous();
				}
			}

			return $args;

		}

		/**
		 * Gets the User's Rendez Vous that are attached to an hidden Group.
		 *
		 * As, it's not possible to do a mix of 'AND' and 'OR' relation with WP_Meta_Queries,
		 * we are using the exclude args of the Rendez Vous loop to exclude the Rendez Vous
		 * IDs that are attached to an hidden Group.
		 *
		 * @since 1.1.0
		 *
		 * @param int $user_id The User ID.
		 * @return array The list of Rendez Vous to hide for the User.
		 */
		public static function get_hidden_rendez_vous( $user_id = 0 ) {

			global $wpdb;
			$bp = buddypress();

			if ( empty( $user_id ) ) {
				$user_id = bp_displayed_user_id();
			}

			if ( empty( $user_id ) ) {
				return [];
			}

			// BP_Groups_Member::get_group_ids does not suit the need.
			$user_hidden_groups = $wpdb->prepare( "SELECT DISTINCT m.group_id FROM {$bp->groups->table_name_members} m LEFT JOIN {$bp->groups->table_name} g ON ( g.id = m.group_id ) WHERE g.status = 'hidden' AND m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0", $user_id );

			// Get the Rendez Vous attached to an hidden Group of the User.
			$hidden_rendez_vous = "SELECT pm.post_id FROM {$wpdb->postmeta} pm WHERE pm.meta_key = '_rendez_vous_group_id' AND pm.meta_value IN ( {$user_hidden_groups} )";
			$hide = $wpdb->get_col( $hidden_rendez_vous );

			return $hide;

		}

		/**
		 * Set the current action.
		 *
		 * @since 1.1.0
		 *
		 * @param string $action The action.
		 * @return string $action The modified action.
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
		 * Gets the Organizer ID.
		 *
		 * Helps to make sure the Organizer ID remains the same in case a Rendez Vous
		 * is edited by a Group admin or a site admin
		 *
		 * @since 1.1.0
		 *
		 * @param int $organizer_id The Organizer ID.
		 * @param array $args The Rendez Vous 'save' arguments.
		 * @return int The Organizer ID.
		 */
		public function group_edit_get_organizer_id( $organizer_id = 0, $args = [] ) {

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
		 * Builds the Rendez Vous link in the Group's context.
		 *
		 * @since 1.1.0
		 *
		 * @param int $id The Rendez Vous ID.
		 * @param int $organizer The Organizer ID.
		 * @return string The permalink to the Rendez Vous in a Group.
		 */
		public function group_rendez_vous_link( $id = 0, $organizer = 0 ) {

			$link = false;
			$action = false;

			if ( empty( $id ) || empty( $organizer ) ) {
				return $link;
			}

			$group_id = get_post_meta( $id, '_rendez_vous_group_id', true );

			if ( empty( $group_id ) ) {
				return $link;
			}

			if ( ! self::group_get_option( $group_id, '_rendez_vous_group_activate', false ) ) {
				$action = 'rendez_vous_groups_component_deactivated';
			} elseif ( ! groups_is_user_member( $organizer, $group_id ) ) {
				$action = 'rendez_vous_groups_member_removed';
			}

			/**
			 * If the Group does not support Rendez Vous or
			 * the Organizer is not a Member of the Group any more,
			 * remove post meta & activities to be sure the Organizer
			 * can always access their Rendez Vous.
			 */
			if ( ! empty( $action ) ) {
				delete_post_meta( $id, '_rendez_vous_group_id' );
				do_action( $action, $id, get_post( $id ) );
				return $link;
			}

			// Everything is ok, build the Group Rendez Vous link.
			$group = groups_get_current_group();

			if ( empty( $group->id ) || $group_id == $group->id ) {
				$group = groups_get_group( [
					'group_id' => $group_id,
					'populate_extras' => false,
				] );

				$link = trailingslashit( bp_get_group_permalink( $group ) . $this->slug );
			}

			return $link;

		}

		/**
		 * Returns the Rendez Vous edit link in the Group's context.
		 *
		 * @since 1.1.0
		 *
		 * @param string $link The Rendez Vous edit link.
		 * @param int $id The Rendez Vous ID.
		 * @param int $organizer The Organizer ID.
		 * @return string The Rendez Vous edit link.
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
				[
					'rdv' => $id,
					'action' => 'edit',
				],
				$group_link
			);

			return $link;

		}

		/**
		 * Returns the Rendez Vous link in the Group's context.
		 *
		 * @since 1.1.0
		 *
		 * @param string $link The Rendez Vous link.
		 * @param int $id The Rendez Vous ID.
		 * @param int $organizer The Organizer ID.
		 * @return string The Rendez Vous link.
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
				[ 'rdv' => $id ],
				$group_link
			);

			return $link;

		}

		/**
		 * Returns the Rendez Vous delete link in the Group's context.
		 *
		 * @since 1.1.0
		 *
		 * @param string $link The Rendez Vous delete link.
		 * @param int $id The Rendez Vous ID.
		 * @param int $organizer The Organizer ID.
		 * @return string The Rendez Vous delete link.
		 */
		public function group_delete_link( $link = '', $id = 0, $organizer = 0 ) {

			if ( empty( $id ) ) {
				return $link;
			}

			$group_link = $this->group_rendez_vous_link( $id, $organizer );
			if ( empty( $group_link ) ) {
				return $link;
			}

			$link = add_query_arg( [
				'rdv' => $id,
				'action' => 'delete',
			], $group_link );
			$link = wp_nonce_url( $link, 'rendez_vous_delete' );

			return $link;

		}

		/**
		 * Builds the Rendez Vous edit form action.
		 *
		 * @since 1.1.0
		 *
		 * @param string $action The form action.
		 * @param int $rendez_vous_id The Rendez Vous ID.
		 * @return string The form action.
		 */
		public function group_form_action( $action = '', $rendez_vous_id = 0 ) {

			if ( ! bp_is_group() ) {
				return $action;
			}

			$group = groups_get_current_group();

			return trailingslashit( bp_get_group_permalink( $group ) . $this->slug );

		}

		/**
		 * Returns the activity args for a Rendez Vous saved within a Group.
		 *
		 * @since 1.1.0
		 *
		 * @param array $args The activity arguments.
		 * @return array The activity arguments.
		 */
		public function group_activity_save_args( $args = [] ) {

			if ( ! bp_is_group() || empty( $args['action'] ) ) {
				return $args;
			}

			$group = groups_get_current_group();

			$group_link = '<a href="' . esc_url( bp_get_group_permalink( $group ) ) . '">' . esc_html( $group->name ) . '</a>';

			/* translators: %s: The link to the Group. */
			$action         = $args['action'] . ' ' . sprintf( __( 'in %s', 'rendez-vous' ), $group_link );
			$rendez_vous_id = $args['item_id'];
			$hide_sitewide  = false;

			if ( 'public' != $group->status ) {
				$hide_sitewide = true;
			}

			$args = array_merge( $args, [
				'action'            => $action,
				'component'         => buddypress()->groups->id,
				'item_id'           => $group->id,
				'secondary_item_id' => $rendez_vous_id,
				'hide_sitewide'     => $hide_sitewide,
			] );

			return $args;

		}

		/**
		 * Returns the activity delete arguments for a Rendez Vous removed from a Group.
		 *
		 * @since 1.1.0
		 *
		 * @param  array $args The activity delete arguments.
		 * @return array The activity delete arguments.
		 */
		public function group_activity_delete_args( $args = [] ) {

			if ( ! bp_is_group() || empty( $args['item_id'] ) ) {
				return $args;
			}

			$group = groups_get_current_group();
			$rendez_vous_id = $args['item_id'];

			$args = [
				'item_id'           => $group->id,
				'secondary_item_id' => $rendez_vous_id,
				'component'         => buddypress()->groups->id,
			];

			return $args;

		}

		/**
		 * Format the activity action for the Rendez Vous attached to a Group.
		 *
		 * @since 1.1.0
		 *
		 * @param string $action The activity action string.
		 * @param BP_Activity_Activity $activity The activity object.
		 * @return string The activity action string.
		 */
		public function format_activity_action( $action = '', $activity = null ) {

			// Bail if not a Rendez Vous activity posted in a Group.
			if ( buddypress()->groups->id != $activity->component || empty( $action ) ) {
				return $action;
			}

			$group = groups_get_group( [
				'group_id'        => $activity->item_id,
				'populate_extras' => false,
			] );

			if ( empty( $group ) ) {
				return $action;
			}

			$group_link = '<a href="' . esc_url( bp_get_group_permalink( $group ) ) . '">' . esc_html( $group->name ) . '</a>';

			/* translators: %s: The link to the Group. */
			$action .= ' ' . sprintf( __( 'in %s', 'rendez-vous' ), $group_link );

			return $action;

		}

		/**
		 * Returns the Rendez Vous avatar in the Group's context.
		 *
		 * @since 1.1.0
		 *
		 * @param string $output The avatar for the Rendez Vous.
		 * @param int $rendez_vous_id The Rendez Vous ID.
		 * @return string The avatar for the Rendez Vous.
		 */
		public function group_rendez_vous_avatar( $output = '', $rendez_vous_id = 0 ) {

			if ( empty( $rendez_vous_id ) || bp_is_group() ) {
				return $output;
			}

			$group_id = get_post_meta( $rendez_vous_id, '_rendez_vous_group_id', true );

			if ( ! empty( $group_id ) && self::group_get_option( $group_id, '_rendez_vous_group_activate', false ) ) {
				$output = '<div class="rendez-vous-avatar">';
				$output .= bp_core_fetch_avatar( [
					'item_id' => $group_id,
					'object'  => 'group',
					'type'    => 'thumb',
				] );
				$output .= '</div>';
			}

			return $output;

		}

		/**
		 * Returns the Rendez Vous status in the Group's context.
		 *
		 * @since 1.1.0
		 *
		 * @param string $status The Rendez Vous status.
		 * @param int $rendez_vous_id The Rendez Vous ID.
		 * @param string $rendez_vous_status The Rendez Vous post type object status.
		 * @return string The Rendez Vous status.
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

	}

endif;

/**
 * Registers the Rendez Vous Group's component.
 *
 * @since 1.1.0
 */
function rendez_vous_register_group_extension() {
	bp_register_group_extension( 'Rendez_Vous_Group' );
}

add_action( 'bp_init', 'rendez_vous_register_group_extension' );

/**
 * Register the Group's activity actions for the Rendez Vous.
 *
 * @since 1.1.0
 */
function rendez_vous_groups_activity_actions() {

	$bp = buddypress();

	// Bail if activity is not active.
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
			__( 'New Rendez Vous in a group', 'rendez-vous' ),
			'rendez_vous_format_activity_action',
			__( 'New Rendez Vous', 'rendez-vous' ),
			[ 'group', 'member_groups' ]
		);

		bp_activity_set_action(
			$bp->groups->id,
			'updated_rendez_vous',
			__( 'Updated a Rendez Vous in a group', 'rendez-vous' ),
			'rendez_vous_format_activity_action',
			__( 'Updated a Rendez Vous', 'rendez-vous' ),
			[ 'group', 'member_groups' ]
		);
	}

}

add_action( 'rendez_vous_register_activity_actions', 'rendez_vous_groups_activity_actions', 20 );
