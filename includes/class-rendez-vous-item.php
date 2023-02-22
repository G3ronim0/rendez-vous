<?php
/**
 * Rendez Vous Item class.
 *
 * The CRUD class for Rendez Vous Items.
 *
 * @package Rendez_Vous
 * @subpackage Classes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
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

			$this->def_date = get_post_meta( $rendez_vous->ID, '_rendez_vous_defdate', true );
			$this->modified = $rendez_vous->post_modified;
			$this->group_id = get_post_meta( $rendez_vous->ID, '_rendez_vous_group_id', true );

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
				'ID'           => $this->id,
				'post_author'  => $this->organizer,
				'post_title'   => $this->title,
				'post_type'    => 'rendez_vous',
				'post_excerpt' => $this->description,
				'post_status'  => ! empty( $this->privacy ) ? 'private' : $this->status,
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
				'post_author'  => $this->organizer,
				'post_title'   => $this->title,
				'post_type'    => 'rendez_vous',
				'post_excerpt' => $this->description,
				'post_status'  => 'draft',
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

			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
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
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				$query_args['meta_query'] = [ $group_query ];
			} else {
				$query_args['meta_query'][] = $group_query;
			}
		}

		if ( ! empty( $r['type'] ) ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
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
			'total'             => $rendez_vous_items->found_posts,
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
