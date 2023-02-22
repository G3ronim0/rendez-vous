<?php
/**
 * Rendez Vous Classes.
 *
 * Editor & Crud Classes.
 *
 * @package Rendez_Vous
 * @subpackage Classes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rendez Vous Editor Class.
 *
 * This class is used to create the Rendez Vous.
 *
 * @since 1.0.0
 */
class Rendez_Vous_Editor {

	/**
	 * Settings array.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private static $settings = [];

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {}

	/**
	 * Set the settings.
	 *
	 * @since 1.0.0
	 *
	 * @param str $editor_id The Editor identifier.
	 * @param array $settings The Editor settings.
	 */
	public static function set( $editor_id, $settings ) {

		$set = bp_parse_args( $settings, [
			'component'       => 'rendez_vous',
			'status'          => 'public',
			'btn_caption'     => __( 'New Rendez Vous', 'rendez-vous' ),
			'btn_class'       => 'btn-rendez-vous',
			'action'          => 'rendez_vous_create',
			'group_id'        => null,
		], 'rendez_vous_editor_args' );

		self::$settings = array_merge( $set, [ 'rendez_vous_button_id' => '#' . $editor_id ] );

		return $set;

	}

	/**
	 * Display the button to launch the Editor.
	 *
	 * @since 1.0.0
	 *
	 * @param str $editor_id The Editor identifier.
	 * @param array $settings The Editor settings.
	 */
	public static function editor( $editor_id, $settings = [] ) {

		$set = self::set( $editor_id, $settings );

		$load_editor = apply_filters( 'rendez_vous_load_editor', bp_is_my_profile() );

		if ( current_user_can( 'publish_rendez_vouss' ) && ! empty( $load_editor ) ) {

			bp_button( [
				'id'                => 'create-' . $set['component'] . '-' . $set['status'],
				'component'         => 'rendez_vous',
				'must_be_logged_in' => true,
				'block_self'        => false,
				'wrapper_id'        => $editor_id,
				'wrapper_class'     => $set['btn_class'],
				'link_class'        => 'add-' . $set['status'],
				'link_href'         => '#',
				'link_title'        => $set['btn_caption'],
				'link_text'         => $set['btn_caption'],
			] );

		}

		self::launch( $editor_id );

	}

	/**
	 * Starts the editor.
	 *
	 * @since 1.0.0
	 *
	 * @param str $editor_id The Editor identifier.
	 */
	public static function launch( $editor_id ) {

		$args = self::$settings;

		// Time to enqueue script.
		rendez_vous_enqueue_editor( $args );

	}

}

/**
 * Rendez_Vous "CRUD" Class.
 *
 * @since 1.0.0
 */
class Rendez_Vous_Item {

	/**
	 * Item ID.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $id;

	/**
	 * Organizer ID.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $organizer;

	/**
	 * Item title.
	 *
	 * @since 1.0.0
	 * @var str
	 */
	public $title;

	/**
	 * Item venue.
	 *
	 * @since 1.0.0
	 * @var str
	 */
	public $venue;

	/**
	 * Item type.
	 *
	 * @since 1.0.0
	 * @var str
	 */
	public $type;

	/**
	 * Item description.
	 *
	 * @since 1.0.0
	 * @var str
	 */
	public $description;

	/**
	 * Item duration.
	 *
	 * @since 1.0.0
	 * @var str
	 */
	public $duration;

	/**
	 * Item privacy.
	 *
	 * @since 1.0.0
	 * @var str
	 */
	public $privacy;

	/**
	 * Item published status.
	 *
	 * @since 1.0.0
	 * @var str
	 */
	public $status;

	/**
	 * Item duration in days.
	 *
	 * @since 1.0.0
	 * @var str
	 */
	public $days;

	/**
	 * Item Attendees.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	public $attendees;

	/**
	 * Item report.
	 *
	 * @since 1.0.0
	 * @var str
	 */
	public $report;

	/**
	 * Item older date.
	 *
	 * @since 1.0.0
	 * @var str
	 */
	public $older_date;

	/**
	 * Item def date.
	 *
	 * @since 1.0.0
	 * @var str
	 */
	public $def_date;

	/**
	 * Item modified.
	 *
	 * @since 1.0.0
	 * @var str
	 */
	public $modified;

	/**
	 * Item Group ID.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $group_id;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id The numeric ID of the Rendez Vous.
	 */
	public function __construct( $id = 0 ) {

		if ( ! empty( $id ) ) {
			$this->id = $id;
			$this->populate();
		}

	}

	/**
	 * Request an item ID.
	 *
	 * @since 1.0.0
	 */
	public function populate() {

		$rendez_vous = get_post( $this->id );

		if ( is_a( $rendez_vous, 'WP_Post' ) ) {

			$this->id          = $rendez_vous->ID;
			$this->organizer   = $rendez_vous->post_author;
			$this->title       = $rendez_vous->post_title;
			$this->venue       = get_post_meta( $rendez_vous->ID, '_rendez_vous_venue', true );
			$this->type        = rendez_vous_get_type( $rendez_vous->ID );
			$this->description = $rendez_vous->post_excerpt;
			$this->duration    = get_post_meta( $rendez_vous->ID, '_rendez_vous_duration', true );
			$this->privacy     = 'draft' == $rendez_vous->post_status ? get_post_meta( $rendez_vous->ID, '_rendez_vous_status', true ) : $rendez_vous->post_status;
			$this->status      = $rendez_vous->post_status;
			$this->days        = get_post_meta( $rendez_vous->ID, '_rendez_vous_days', true );
			$this->attendees   = get_post_meta( $this->id, '_rendez_vous_attendees' );
			$this->report      = $rendez_vous->post_content;
			$this->older_date  = false;

			if ( ! empty( $this->days ) ) {
				$timestamps = array_keys( $this->days );
				rsort( $timestamps );
				$this->older_date = date_i18n( 'Y-m-d H:i:s', $timestamps[0] );
			}

			$this->def_date    = get_post_meta( $rendez_vous->ID, '_rendez_vous_defdate', true );
			$this->modified    = $rendez_vous->post_modified;
			$this->group_id    = get_post_meta( $rendez_vous->ID, '_rendez_vous_group_id', true );

		}

	}

	/**
	 * Save a Rendez Vous.
	 *
	 * @since 1.0.0
	 *
	 * @return int|WP_Error $result The numeric ID of the Rendez Vous, or error object on failure.
	 */
	public function save() {

		$this->id          = apply_filters_ref_array( 'rendez_vous_id_before_save', [ $this->id, &$this ] );
		$this->organizer   = apply_filters_ref_array( 'rendez_vous_organizer_before_save', [ $this->organizer, &$this ] );
		$this->title       = apply_filters_ref_array( 'rendez_vous_title_before_save', [ $this->title, &$this ] );
		$this->venue       = apply_filters_ref_array( 'rendez_vous_venue_before_save', [ $this->venue, &$this ] );
		$this->type        = apply_filters_ref_array( 'rendez_vous_type_before_save', [ $this->type, &$this ] );
		$this->description = apply_filters_ref_array( 'rendez_vous_description_before_save', [ $this->description, &$this ] );
		$this->duration    = apply_filters_ref_array( 'rendez_vous_duration_before_save', [ $this->duration, &$this ] );
		$this->privacy     = apply_filters_ref_array( 'rendez_vous_privacy_before_save', [ $this->privacy, &$this ] );
		$this->status      = apply_filters_ref_array( 'rendez_vous_status_before_save', [ $this->status, &$this ] );
		$this->days        = apply_filters_ref_array( 'rendez_vous_days_before_save', [ $this->days, &$this ] );
		$this->attendees   = apply_filters_ref_array( 'rendez_vous_attendees_before_save', [ $this->attendees, &$this ] );
		$this->report      = apply_filters_ref_array( 'rendez_vous_report_before_save', [ $this->report, &$this ] );
		$this->older_date  = apply_filters_ref_array( 'rendez_vous_older_date_before_save', [ $this->older_date, &$this ] );
		$this->def_date    = apply_filters_ref_array( 'rendez_vous_def_date_before_save', [ $this->def_date, &$this ] );
		$this->modified    = apply_filters_ref_array( 'rendez_vous_modified_before_save', [ $this->modified, &$this ] );
		$this->group_id    = apply_filters_ref_array( 'rendez_vous_group_id_before_save', [ $this->group_id, &$this ] );

		// Use this, not the filters above.
		do_action_ref_array( 'rendez_vous_before_save', [ &$this ] );

		if ( empty( $this->organizer ) || empty( $this->title ) ) {
			return false;
		}

		if ( empty( $this->status ) ) {
			$this->status = 'publish';
		}

		if ( $this->id ) {

			// Update.
			$wp_update_post_args = [
				'ID'             => $this->id,
				'post_author'    => $this->organizer,
				'post_title'     => $this->title,
				'post_type'      => 'rendez_vous',
				'post_excerpt'   => $this->description,
				'post_status'    => ! empty( $this->privacy ) ? 'private' : $this->status,
			];

			// The report is saved once the Rendez Vous date is past.
			if ( ! empty( $this->report ) ) {
				$wp_update_post_args['post_content'] = $this->report;
			}

			// reset privacy to get rid of the meta now the post has been published.
			$this->privacy  = '';
			$this->group_id = get_post_meta( $this->id, '_rendez_vous_group_id', true );

			$result = wp_update_post( $wp_update_post_args );

		} else {

			// Insert.
			$wp_insert_post_args = [
				'post_author'    => $this->organizer,
				'post_title'     => $this->title,
				'post_type'      => 'rendez_vous',
				'post_excerpt'   => $this->description,
				'post_status'    => 'draft',
			];

			$result = wp_insert_post( $wp_insert_post_args );

			// We only need to do that once.
			if ( $result ) {
				if ( ! empty( $this->days ) && is_array( $this->days ) ) {
					update_post_meta( $result, '_rendez_vous_days', $this->days );
				}

				// Group.
				if ( ! empty( $this->group_id ) ) {
					update_post_meta( $result, '_rendez_vous_group_id', $this->group_id );
				}
			}

		}

		// Saving meta values.
		if ( ! empty( $result ) ) {

			if ( ! empty( $this->venue ) ) {
				update_post_meta( $result, '_rendez_vous_venue', $this->venue );
			} else {
				delete_post_meta( $result, '_rendez_vous_venue' );
			}

			if ( ! empty( $this->duration ) ) {
				update_post_meta( $result, '_rendez_vous_duration', $this->duration );
			} else {
				delete_post_meta( $result, '_rendez_vous_duration' );
			}

			if ( ! empty( $this->privacy ) ) {
				update_post_meta( $result, '_rendez_vous_status', $this->privacy );
			} else {
				delete_post_meta( $result, '_rendez_vous_status' );
			}

			if ( ! empty( $this->def_date ) ) {
				update_post_meta( $result, '_rendez_vous_defdate', $this->def_date );
			} else {
				delete_post_meta( $result, '_rendez_vous_defdate' );
			}

			if ( ! empty( $this->attendees ) && is_array( $this->attendees ) ) {
				$this->attendees = array_map( 'absint', $this->attendees );

				$in_db = get_post_meta( $result, '_rendez_vous_attendees' );

				if ( empty( $in_db ) ) {

					foreach ( $this->attendees as $attendee ) {
						add_post_meta( $result, '_rendez_vous_attendees', absint( $attendee ) );
					}

				} else {

					$to_delete = array_diff( $in_db, $this->attendees );
					$to_add    = array_diff( $this->attendees, $in_db );

					if ( ! empty( $to_delete ) ) {
						// Delete item IDs.
						foreach ( $to_delete as $del_attendee ) {
							delete_post_meta( $result, '_rendez_vous_attendees', absint( $del_attendee ) );
							// Delete User's preferences.
							self::attendees_pref( $result, $del_attendee );
						}
					}

					if ( ! empty( $to_add ) ) {
						// Add item IDs.
						foreach ( $to_add as $add_attendee ) {
							add_post_meta( $result, '_rendez_vous_attendees', absint( $add_attendee ) );
						}
					}

				}

			} else {
				delete_post_meta( $result, '_rendez_vous_attendees' );
			}

			// Set Rendez Vous type.
			rendez_vous_set_type( $result, $this->type );

			do_action_ref_array( 'rendez_vous_after_meta_update', [ &$this ] );

		}

		do_action_ref_array( 'rendez_vous_after_save', [ &$this ] );

		return $result;

	}

	/**
	 * Set an Attendee's preferences.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id The numeric ID of the Rendez Vous.
	 * @param int $user_id The numeric ID of the WordPress User.
	 * @param array $prefs The Attendee preferences.
	 */
	public static function attendees_pref( $id = 0, $user_id = 0, $prefs = [] ) {

		if ( empty( $id ) || empty( $user_id ) ) {
			return false;
		}

		$days      = get_post_meta( $id, '_rendez_vous_days', true );
		$attendees = get_post_meta( $id, '_rendez_vous_attendees' );

		if ( empty( $days ) || ! is_array( $days ) ) {
			return false;
		}

		$check_days = array_keys( $days );

		foreach ( $check_days as $day ) {

			if ( ! in_array( $user_id, $days[ $day ] ) ) {

				// User has not set or didn't chose this day so far.
				if ( in_array( $day, $prefs ) ) {
					$days[ $day ] = array_merge( $days[ $day ], [ $user_id ] );
				}

			} else {

				// User choosed this day, remove it if not in prefs.
				if ( ! in_array( $day, $prefs ) ) {
					$days[ $day ] = array_diff( $days[ $day ], [ $user_id ] );
				}

			}

		}

		update_post_meta( $id, '_rendez_vous_days', $days );

		// We have a guest! Should only happen for public Rendez Vous.
		if ( ! in_array( $user_id, $attendees ) && ! empty( $prefs ) ) {
			add_post_meta( $id, '_rendez_vous_attendees', absint( $user_id ) );
		}

		return true;

	}

	/**
	 * The selection query.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args The arguments to customize the query.
	 * @return array $query The query results.
	 */
	public static function get( $args = [] ) {

		$defaults = [
			'attendees' => [], // One or more User IDs who may attend to the Rendez Vous.
			'organizer' => false, // The Author ID of the Rendez Vous.
			'per_page'  => 20,
			'page'      => 1,
			'search'    => false,
			'exclude'   => false, // Comma separated list or array of Rendez Vous IDs.
			'orderby'   => 'modified',
			'order'     => 'DESC',
			'group_id'  => false,
		];

		$r = bp_parse_args( $args, $defaults, 'rendez_vous_get_query_args' );

		$rendez_vous_status = [ 'publish', 'private' ];

		$draft_status = apply_filters( 'rendez_vous_get_query_draft_status', bp_is_my_profile() );

		if ( $draft_status || bp_current_user_can( 'bp_moderate' ) ) {
			$rendez_vous_status[] = 'draft';
		}

		$query_args = [
			'post_status'    => $rendez_vous_status,
			'post_type'      => 'rendez_vous',
			'posts_per_page' => $r['per_page'],
			'paged'          => $r['page'],
			'orderby'        => $r['orderby'],
			'order'          => $r['order'],
		];

		if ( ! empty( $r['organizer'] ) ) {
			$query_args['author'] = $r['organizer'];
		}

		if ( ! empty( $r['exclude'] ) ) {
			$exclude = $r['exclude'];

			if ( ! is_array( $exclude ) ) {
				$exclude = explode( ',', $exclude );
			}

			$query_args['post__not_in'] = $exclude;
		}

		// Component is defined, we can zoom on specific IDs.
		if ( ! empty( $r['attendees'] ) ) {
			// We really want an array.
			$attendees = (array) $r['attendees'];

			$query_args['meta_query'] = [
				[
					'key'     => '_rendez_vous_attendees',
					'value'   => $attendees,
					'compare' => 'IN',
				],
			];
		}

		if ( ! empty( $r['group_id'] ) ) {
			$group_query = [
				'key'     => '_rendez_vous_group_id',
				'value'   => $r['group_id'],
				'compare' => '=',
			];

			if ( empty( $query_args['meta_query'] ) ) {
				$query_args['meta_query'] = [ $group_query ];
			} else {
				$query_args['meta_query'][] = $group_query;
			}
		}

		if ( ! empty( $r['type'] ) ) {
			$query_args['tax_query'] = [
				[
					'field'    => 'slug',
					'taxonomy' => 'rendez_vous_type',
					'terms'    => $r['type'],
				],
			];
		}

		$rendez_vous_items = new WP_Query( apply_filters( 'rendez_vous_query_args', $query_args ) );

		return [
			'rendez_vous_items' => $rendez_vous_items->posts,
			'total' => $rendez_vous_items->found_posts,
		];

	}

	/**
	 * Delete a Rendez Vous.
	 *
	 * @since 1.0.0
	 *
	 * @param int $rendez_vous_id The numeric ID of the Rendez Vous to delete.
	 * @return int|WP_Error $deleted The numeric ID of the deleted Rendez Vous, or error object on failure.
	 */
	public static function delete( $rendez_vous_id = 0 ) {

		if ( empty( $rendez_vous_id ) ) {
			return false;
		}

		$deleted = wp_delete_post( $rendez_vous_id, true );

		return $deleted;

	}

}


if ( ! class_exists( 'Rendez_Vous_Upcoming_Widget' ) ) :

	/**
	 * List the upcoming Rendez Vous for the logged-in User.
	 *
	 * @since 1.4.0
	 */
	class Rendez_Vous_Upcoming_Widget extends WP_Widget {

		/**
		 * Constructor.
		 *
		 * @since 1.4.0
		 */
		public function __construct() {

			$widget_ops = [ 'description' => __( 'List the upcoming Rendez Vous for the logged-in user.', 'rendez-vous' ) ];
			parent::__construct( false, $name = __( 'Upcoming Rendez Vous', 'rendez-vous' ), $widget_ops );

		}

		/**
		 * Register the widget.
		 *
		 * @since 1.4.0
		 */
		public static function register_widget() {
			register_widget( 'Rendez_Vous_Upcoming_Widget' );
		}

		/**
		 * Filter the query for this specific widget use.
		 *
		 * @since 1.4.0
		 *
		 * @param array $query_args The existing query args.
		 * @return array $query_args The modified query args.
		 */
		public function filter_rendez_vous_query( $query_args = [] ) {

			$upcoming_args = array_merge(
				$query_args,
				[
					'post_status' => [ 'private', 'publish' ],
					'meta_query'  => [
						'relation' => 'AND',
						[
							'key'     => '_rendez_vous_attendees',
							'value'   => [ bp_loggedin_user_id() ],
							'compare' => 'IN',
						],
						'rendez_vous_date' => [
							'key'     => '_rendez_vous_defdate',
							'value'   => bp_core_current_time( true, 'timestamp' ),
							'compare' => '>=',
						],
					],
					'orderby' => 'rendez_vous_date',
					'order'   => 'ASC',
				]
			);

			$allowed_keys = [
				'post_status'    => true,
				'post_type'      => true,
				'posts_per_page' => true,
				'paged'          => true,
				'orderby'        => true,
				'order'          => true,
				'meta_query'     => true,
			];

			return array_intersect_key( $upcoming_args, $allowed_keys );

		}

		/**
		 * Display the widget on front end.
		 *
		 * @since 1.4.0
		 *
		 * @param array $args The widget arguments.
		 * @param array $instance The widget instance.
		 */
		public function widget( $args = [], $instance = [] ) {

			// Display nothing if the current User is not set.
			if ( ! is_user_logged_in() ) {
				return;
			}

			// Default per_page is 5.
			$number = 5;

			// No Rendez Vous items to show? Stop.
			if ( ! empty( $instance['number'] ) ) {
				$number = (int) $instance['number'];
			}

			add_filter( 'rendez_vous_query_args', [ $this, 'filter_rendez_vous_query' ], 10, 1 );

			$has_rendez_vous = rendez_vous_has_rendez_vouss( [
				'per_page' => $number,
				'no_cache' => true,
			] );

			remove_filter( 'rendez_vous_query_args', [ $this, 'filter_rendez_vous_query' ], 10, 1 );

			// Display nothing if there are no upcoming Rendez Vous.
			if ( ! $has_rendez_vous ) {
				return;
			}

			// Default title is nothing.
			$title = '';

			if ( ! empty( $instance['title'] ) ) {
				$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
			}

			echo $args['before_widget'];

			if ( ! empty( $title ) ) {
				echo $args['before_title'] . $title . $args['after_title'];
			}

			?>
			<ul>

				<?php while ( rendez_vous_the_rendez_vouss() ) : ?>
					<?php rendez_vous_the_rendez_vous(); ?>

					<li>
						<a href="<?php rendez_vous_the_link(); ?>" title="<?php echo esc_attr( rendez_vous_get_the_title() ); ?>"><?php rendez_vous_the_title(); ?></a>
						<small class="time-to"><?php rendez_vous_time_to(); ?></small>
					</li>

				<?php endwhile; ?>

			</ul>
			<?php

			echo $args['after_widget'];

		}

		/**
		 * Update widget preferences.
		 *
		 * @since 1.4.0
		 *
		 * @param array $new_instance The old widget arguments.
		 * @param array $old_instance The new widget instance.
		 * @return array $instance The updated widget instance.
		 */
		public function update( $new_instance, $old_instance ) {

			$instance = [];

			if ( ! empty( $new_instance['title'] ) ) {
				$instance['title'] = wp_strip_all_tags( wp_unslash( $new_instance['title'] ) );
			}

			$instance['number'] = (int) $new_instance['number'];

			return $instance;

		}

		/**
		 * Display the form in Widgets Administration.
		 *
		 * @since 1.4.0
		 *
		 * @param array $instance The widget instance.
		 */
		public function form( $instance = [] ) {

			// Default to nothing.
			$title = '';
			if ( isset( $instance['title'] ) ) {
				$title = $instance['title'];
			}

			// Number default to 5.
			$number = 5;
			if ( ! empty( $instance['number'] ) ) {
				$number = absint( $instance['number'] );
			}

			?>
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Title:', 'rendez-vous' ); ?></label>
				<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $title ); ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php esc_html_e( 'Number of upcoming Rendez Vous to show:', 'rendez-vous' ); ?></label>
				<input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="text" value="<?php echo $number; ?>" size="3" />
			</p>
			<?php

		}

	}

endif;

add_action( 'bp_widgets_init', [ 'Rendez_Vous_Upcoming_Widget', 'register_widget' ], 10 );
