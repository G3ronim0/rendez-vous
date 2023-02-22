<?php
/**
 * Rendez Vous Functions.
 *
 * Plugin functions
 *
 * @package Rendez_Vous
 * @subpackage Functions
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get a Rendez Vous.
 *
 * @since 1.0.0
 *
 * @param int $id The numeric ID of the Rendez Vous.
 * @return object $rendez_vous The Rendez Vous object.
 */
function rendez_vous_get_item( $id = 0 ) {

	if ( empty( $id ) ) {
		return false;
	}

	$rendez_vous = new Rendez_Vous_Item( $id );

	return apply_filters( 'rendez_vous_get_item', $rendez_vous );

}

/**
 * Get an array of Rendez Vous items.
 *
 * @since 1.0.0
 *
 * @param array $args The array of query args.
 * @return array $rendez_vouss The array of Rendez Vous objects.
 */
function rendez_vous_get_items( $args = [] ) {

	$defaults = [
		'attendees' => [], // One or more User IDs who may attend the Rendez Vous.
		'organizer' => false, // The Author ID of the Rendez Vous.
		'per_page'  => 20,
		'page'      => 1,
		'search'    => false,
		'exclude'   => false, // Comma separated list or array of Rendez Vous IDs.
		'orderby'   => 'modified',
		'order'     => 'DESC',
		'group_id'  => false,
		'type'      => '',
		'no_cache'  => false,
	];

	$r = bp_parse_args( $args, $defaults, 'rendez_vous_get_items_args' );

	if ( ! $r['no_cache'] ) {
		$rendez_vouss = wp_cache_get( 'rendez_vous_rendez_vouss', 'bp' );
	}

	if ( empty( $rendez_vouss ) ) {

		$rendez_vouss = Rendez_Vous_Item::get( [
			'attendees' => (array) $r['attendees'],
			'organizer' => (int) $r['organizer'],
			'per_page'  => $r['per_page'],
			'page'      => $r['page'],
			'search'    => $r['search'],
			'exclude'   => $r['exclude'],
			'orderby'   => $r['orderby'],
			'order'     => $r['order'],
			'group_id'  => $r['group_id'],
			'type'      => $r['type'],
		] );

		if ( ! $r['no_cache'] ) {
			wp_cache_set( 'rendez_vous_rendez_vouss', $rendez_vouss, 'bp' );
		}

	}

	return apply_filters_ref_array( 'rendez_vous_get_items', [ &$rendez_vouss, &$r ] );

}

/**
 * Launch the Rendez Vous Editor.
 *
 * @since 1.0.0
 *
 * @param str $editor_id The Editor identifier.
 * @param array $settings The Editor settings.
 */
function rendez_vous_editor( $editor_id, $settings = [] ) {
	Rendez_Vous_Editor::editor( $editor_id, $settings );
}

/**
 * Prepare the User data for js.
 *
 * @since 1.0.0
 *
 * @param WP_User $user The WordPress User object.
 * @return array $response The array of User data.
 */
function rendez_vous_prepare_user_for_js( $user ) {

	$avatar_args = [
		'item_id' => $user->ID,
		'object'  => 'user',
		'type'    => 'full',
		'width'   => 150,
		'height'  => 150,
		'html'    => false,
	];

	$response = [
		'id'     => (int) $user->ID,
		'name'   => $users->display_name,
		'avatar' => htmlspecialchars_decode( bp_core_fetch_avatar( $avatar_args ) ),
	];

	return apply_filters( 'rendez_vous_prepare_user_for_js', $response, $user );

}

/**
 * Prepare the term for js.
 *
 * @since 1.2.0
 *
 * @param WP_Term $term The WordPress Term object.
 * @return array $response The array of Term data.
 */
function rendez_vous_prepare_term_for_js( $term ) {

	$response = [
		'id'    => intval( $term->term_id ),
		'name'  => $term->name,
		'slug'  => $term->slug,
		'count' => intval( $term->count ),
	];

	return apply_filters( 'rendez_vous_prepare_term_for_js', $response, $term );

}

/**
 * Save a Rendez Vous.
 *
 * @since 1.0.0
 *
 * @param array $args The Rendez Vous data.
 * @return int|WP_Error $id The numeric ID of the Rendez Vous on success, error object on failure.
 */
function rendez_vous_save( $args = [] ) {

	$r = bp_parse_args( $args, [
		'id'          => false,
		'organizer'   => bp_loggedin_user_id(),
		'title'       => '',
		'venue'       => '',
		'type'        => 0,
		'description' => '',
		'duration'    => '',
		'privacy'     => '',
		'status'      => 'draft',
		'days'        => [], // Example: [ 'timestamp' => [ Attendee IDs ] ].
		'attendees'   => [], // Attendees ID.
		'def_date'    => 0, // Timestamp.
		'report'      => '',
		'group_id'    => false,
	], 'rendez_vous_save_args' );

	if ( empty( $r['title'] ) || empty( $r['organizer'] ) ) {
		return false;
	}

	// Using rendez_vous.
	$rendez_vous = new Rendez_Vous_Item( $r['id'] );

	$rendez_vous->organizer   = (int) $r['organizer'];
	$rendez_vous->title       = $r['title'];
	$rendez_vous->venue       = $r['venue'];
	$rendez_vous->type        = (int) $r['type'];
	$rendez_vous->description = $r['description'];
	$rendez_vous->duration    = $r['duration'];
	$rendez_vous->privacy     = $r['privacy'];
	$rendez_vous->status      = $r['status'];
	$rendez_vous->attendees   = $r['attendees'];
	$rendez_vous->def_date    = $r['def_date'];
	$rendez_vous->report      = $r['report'];
	$rendez_vous->group_id    = $r['group_id'];

	// Allow Attendees to not attend.
	if ( 'draft' == $r['status'] && ! in_array( 'none', array_keys( $r['days'] ) ) ) {
		$r['days']['none'] = [];

		// Saving days the first time only.
		$rendez_vous->days = $r['days'];
	}

	do_action( 'rendez_vous_before_saved', $rendez_vous, $r );

	$id = $rendez_vous->save();

	do_action( 'rendez_vous_after_saved', $rendez_vous, $r );

	return $id;

}

/**
 * Delete a Rendez Vous.
 *
 * @since 1.0.0
 *
 * @param int $id The numeric ID of the Rendez Vous.
 * @return bool True if successfully deleted, false otherwise.
 */
function rendez_vous_delete_item( $id = 0 ) {

	if ( empty( $id ) ) {
		return false;
	}

	do_action( 'rendez_vous_before_delete', $id );

	$deleted = Rendez_Vous_Item::delete( $id );

	if ( ! empty( $deleted ) ) {
		do_action( 'rendez_vous_after_delete', $id, $deleted );
		return true;
	} else {
		return false;
	}

}

/**
 * Set caps.
 *
 * @since 1.0.0
 *
 * @return array $caps The array of capabilities.
 */
function rendez_vous_get_caps() {

	$caps = [
		'edit_posts'          => 'edit_rendez_vouss',
		'edit_others_posts'   => 'edit_others_rendez_vouss',
		'publish_posts'       => 'publish_rendez_vouss',
		'read_private_posts'  => 'read_private_rendez_vouss',
		'delete_posts'        => 'delete_rendez_vouss',
		'delete_others_posts' => 'delete_others_rendez_vouss',
	];

	return apply_filters( 'rendez_vous_get_caps', $caps );

}

/**
 * Display link.
 *
 * @since 1.0.0
 *
 * @param int $id The numeric ID of the Rendez Vous.
 * @param int $organizer_id The numeric ID of the Organizer.
 * @return str $link The link to the Rendez Vous view screen.
 */
function rendez_vous_get_single_link( $id = 0, $organizer_id = 0 ) {

	if ( empty( $id ) || empty( $organizer_id ) ) {
		return false;
	}

	$link = trailingslashit( bp_core_get_user_domain( $organizer_id ) . buddypress()->rendez_vous->slug . '/schedule' );
	$link = add_query_arg( [ 'rdv' => $id ], $link );

	return apply_filters( 'rendez_vous_get_single_link', $link, $id, $organizer_id );

}

/**
 * Edit link.
 *
 * @since 1.0.0
 *
 * @param int $id The numeric ID of the Rendez Vous.
 * @param int $organizer_id The numeric ID of the Organizer.
 * @return str $link The link to the Rendez Vous edit screen.
 */
function rendez_vous_get_edit_link( $id = 0, $organizer_id = 0 ) {

	if ( empty( $id ) || empty( $organizer_id ) ) {
		return false;
	}

	$link = trailingslashit( bp_core_get_user_domain( $organizer_id ) . buddypress()->rendez_vous->slug . '/schedule' );
	$link = add_query_arg( [
		'rdv'    => $id,
		'action' => 'edit',
	], $link );

	return apply_filters( 'rendez_vous_get_edit_link', $link, $id, $organizer_id );

}

/**
 * Delete link.
 *
 * @since 1.0.0
 *
 * @param int $id The numeric ID of the Rendez Vous.
 * @param int $organizer_id The numeric ID of the Organizer.
 * @return str $link The link to delete a Rendez Vous.
 */
function rendez_vous_get_delete_link( $id = 0, $organizer_id = 0 ) {

	if ( empty( $id ) || empty( $organizer_id ) ) {
		return false;
	}

	$link = trailingslashit( bp_core_get_user_domain( $organizer_id ) . buddypress()->rendez_vous->slug . '/schedule' );
	$link = add_query_arg( [
		'rdv'    => $id,
		'action' => 'delete',
	], $link );
	$link = wp_nonce_url( $link, 'rendez_vous_delete' );

	return apply_filters( 'rendez_vous_get_delete_link', $link, $id, $organizer_id );

}

/**
 * Get an iCal Link.
 *
 * @since 1.1.0
 *
 * @param int $id The numeric ID of the Rendez Vous.
 * @param int $organizer_id The Author ID of the Rendez Vous.
 * @return string $link The iCal link.
 */
function rendez_vous_get_ical_link( $id = 0, $organizer_id = 0 ) {

	if ( empty( $id ) || empty( $organizer_id ) ) {
		return false;
	}

	$link = trailingslashit( bp_core_get_user_domain( $organizer_id ) . buddypress()->rendez_vous->slug . '/schedule/ical/' . $id );

	return apply_filters( 'rendez_vous_get_ical_link', $link, $id, $organizer_id );

}

/**
 * Handle Rendez Vous actions in Group/Member contexts.
 *
 * @since 1.1.0
 *
 * @return string $screen The Rendez Vous screen ID.
 */
function rendez_vous_handle_actions() {

	$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : false;
	$screen = '';

	// Edit template.
	if ( ! empty( $_GET['action'] ) && 'edit' == $_GET['action'] && ! empty( $_GET['rdv'] ) ) {

		$redirect = remove_query_arg( [ 'rdv', 'action', 'n' ], wp_get_referer() );

		$rendez_vous_id = absint( $_GET['rdv'] );

		$rendez_vous = rendez_vous_get_item( $rendez_vous_id );

		if ( empty( $rendez_vous ) || ! current_user_can( 'edit_rendez_vous', $rendez_vous_id ) ) {
			bp_core_add_message( __( 'Rendez Vous could not be found', 'rendez-vous' ), 'error' );
			bp_core_redirect( $redirect );
		}

		if ( 'draft' == $rendez_vous->status ) {
			bp_core_add_message( __( 'Your Rendez Vous is in draft mode, check informations and publish!', 'rendez-vous' ) );
		}

		rendez_vous()->item = $rendez_vous;

		$screen = 'edit';

		do_action( 'rendez_vous_edit_screen' );

	}

	// Display single.
	if ( ! empty( $_GET['rdv'] ) && ( empty( $action ) || ! in_array( $action, [ 'edit', 'delete' ] ) ) ) {

		$redirect = remove_query_arg( [ 'rdv', 'n', 'action' ], wp_get_referer() );

		$rendez_vous_id = absint( $_GET['rdv'] );

		$rendez_vous = rendez_vous_get_item( $rendez_vous_id );

		if ( is_null( $rendez_vous->organizer ) ) {
			bp_core_add_message( __( 'The Rendez Vous was not found.', 'rendez-vous' ), 'error' );
			bp_core_redirect( $redirect );
		}

		// Public Rendez Vous can be seen by anybody.
		$has_access = true;

		if ( 'private' == $rendez_vous->status ) {
			$has_access = current_user_can( 'read_private_rendez_vouss', $rendez_vous_id );
		}

		if ( empty( $rendez_vous ) || empty( $has_access ) || 'draft' == $rendez_vous->status ) {
			bp_core_add_message( __( 'You do not have access to this Rendez Vous', 'rendez-vous' ), 'error' );
			bp_core_redirect( $redirect );
		}

		rendez_vous()->item = $rendez_vous;

		$screen = 'single';

		do_action( 'rendez_vous_single_screen' );

	}

	// Publish & Updates.
	if ( ! empty( $_POST['_rendez_vous_edit'] ) && ! empty( $_POST['_rendez_vous_edit']['id'] ) ) {

		check_admin_referer( 'rendez_vous_update' );

		$redirect = remove_query_arg( [ 'rdv', 'n', 'action' ], wp_get_referer() );

		if ( ! current_user_can( 'edit_rendez_vous', absint( $_POST['_rendez_vous_edit']['id'] ) ) ) {
			bp_core_add_message( __( 'Editing this Rendez Vous is not allowed.', 'rendez-vous' ), 'error' );
			bp_core_redirect( $redirect );
		}

		$args      = [];
		$edit_data = filter_input( INPUT_POST, '_rendez_vous_edit', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		$action    = isset( $_POST['_rendez_vous_edit']['action'] ) ? sanitize_key( wp_unslash( $_POST['_rendez_vous_edit']['action'] ) ) : '';
		$args      = array_diff_key( $edit_data, [
			'action' => 0,
			'submit' => 0,
		] );

		$args['status'] = 'publish';

		// Make sure the Organizer doesn't change if Rendez Vous is edited by someone else.
		if ( ! bp_is_my_profile() ) {
			$args['organizer'] = apply_filters( 'rendez_vous_edit_action_organizer_id', bp_displayed_user_id(), $args );
		}

		$notify   = ! empty( $_POST['_rendez_vous_edit']['notify'] ) ? 1 : 0;
		$activity = ! empty( $_POST['_rendez_vous_edit']['activity'] ) && empty( $args['privacy'] ) ? 1 : 0;

		do_action( "rendez_vous_before_{$action}", $args, $notify, $activity );

		$id = rendez_vous_save( $args );

		if ( empty( $id ) ) {
			bp_core_add_message( __( 'Editing this Rendez Vous failed.', 'rendez-vous' ), 'error' );
		} else {
			bp_core_add_message( __( 'Rendez Vous successfully edited.', 'rendez-vous' ) );
			$redirect = add_query_arg( 'rdv', $id, $redirect );

			// Rendez Vous is edited or published, let's handle notifications & activity.
			do_action( "rendez_vous_after_{$action}", $id, $args, $notify, $activity );
		}

		// Finally redirect.
		bp_core_redirect( $redirect );

	}

	// Set User preferences.
	if ( ! empty( $_POST['_rendez_vous_prefs'] ) && ! empty( $_POST['_rendez_vous_prefs']['id'] ) ) {

		check_admin_referer( 'rendez_vous_prefs' );

		$redirect = remove_query_arg( [ 'n', 'action' ], wp_get_referer() );

		$rendez_vous_id = absint( $_POST['_rendez_vous_prefs']['id'] );
		$rendez_vous    = rendez_vous_get_item( $rendez_vous_id );

		$attendee_id = bp_loggedin_user_id();

		$has_access = $attendee_id;

		if ( ! empty( $has_access ) && 'private' == $rendez_vous->status ) {
			$has_access = current_user_can( 'read_private_rendez_vouss', $rendez_vous_id );
		}

		if ( empty( $has_access ) ) {
			bp_core_add_message( __( 'You do not have access to this Rendez Vous', 'rendez-vous' ), 'error' );
			bp_core_redirect( $redirect );
		}

		// Get days.
		$args = filter_input( INPUT_POST, '_rendez_vous_prefs', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		if ( ! empty( $args['days'][ $attendee_id ] ) ) {
			$args['days'] = $args['days'][ $attendee_id ];
		} else {
			$args['days'] = [];
		}

		do_action( 'rendez_vous_before_attendee_prefs', $args );

		if ( ! Rendez_Vous_Item::attendees_pref( $rendez_vous_id, $attendee_id, $args['days'] ) ) {
			bp_core_add_message( __( 'Saving your preferences failed.', 'rendez-vous' ), 'error' );
		} else {
			bp_core_add_message( __( 'Preferences successfully saved.', 'rendez-vous' ) );

			// Let's handle notifications to the Organizer.
			do_action( 'rendez_vous_after_attendee_prefs', $args, $attendee_id, $rendez_vous );
		}

		// Finally redirect.
		bp_core_redirect( $redirect );

	}

	// Delete.
	if ( ! empty( $_GET['action'] ) && 'delete' == $_GET['action'] && ! empty( $_GET['rdv'] ) ) {

		check_admin_referer( 'rendez_vous_delete' );

		$redirect = remove_query_arg( [ 'rdv', 'action', 'n' ], wp_get_referer() );

		$rendez_vous_id = absint( $_GET['rdv'] );

		if ( empty( $rendez_vous_id ) || ! current_user_can( 'delete_rendez_vous', $rendez_vous_id ) ) {
			bp_core_add_message( __( 'Rendez Vous could not be found', 'rendez-vous' ), 'error' );
			bp_core_redirect( $redirect );
		}

		$deleted = rendez_vous_delete_item( $rendez_vous_id );

		if ( ! empty( $deleted ) ) {
			bp_core_add_message( __( 'Rendez Vous successfully cancelled.', 'rendez-vous' ) );
		} else {
			bp_core_add_message( __( 'Rendez Vous could not be cancelled', 'rendez-vous' ), 'error' );
		}

		// Finally redirect.
		bp_core_redirect( $redirect );

	}

	return $screen;

}

/**
 * Generates an iCal file using the Rendez Vous data.
 *
 * @since 1.1.0
 *
 * @return string calendar file.
 */
function rendez_vous_download_ical() {

	$ical_page = [
		'is'  => (bool) bp_is_current_action( 'schedule' ) && 'ical' == bp_action_variable( 0 ),
		'rdv' => (int) bp_action_variable( 1 ),
	];

	apply_filters( 'rendez_vous_download_ical', (array) $ical_page );

	if ( empty( $ical_page['is'] ) ) {
		return;
	}

	$redirect    = wp_get_referer();
	$user_attend = trailingslashit( bp_loggedin_user_domain() . buddypress()->rendez_vous->slug . '/attend' );

	if ( empty( $ical_page['rdv'] ) ) {
		bp_core_add_message( __( 'The Rendez Vous was not found.', 'rendez-vous' ), 'error' );
		bp_core_redirect( $redirect );
	}

	$rendez_vous = rendez_vous_get_item( $ical_page['rdv'] );

	// Redirect the User to the login form.
	if ( ! is_user_logged_in() ) {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		bp_core_no_access( [ 'redirect' => $request_uri ] );
		return;
	}

	// Redirect if no Rendez Vous found.
	if ( empty( $rendez_vous->organizer ) || empty( $rendez_vous->attendees ) ) {
		bp_core_add_message( __( 'The Rendez Vous was not found.', 'rendez-vous' ), 'error' );
		bp_core_redirect( $user_attend );
	}

	// Redirect if not an Attendee.
	if ( $rendez_vous->organizer != bp_loggedin_user_id() && ! in_array( bp_loggedin_user_id(), $rendez_vous->attendees ) ) {
		bp_core_add_message( __( 'You are not attending this Rendez Vous.', 'rendez-vous' ), 'error' );
		bp_core_redirect( $user_attend );
	}

	// Redirect if def date is not set.
	if ( empty( $rendez_vous->def_date ) ) {
		bp_core_add_message( __( 'the Rendez Vous is not set yet.', 'rendez-vous' ), 'error' );
		bp_core_redirect( $redirect );
	}

	$hourminutes = explode( ':', $rendez_vous->duration );

	// Redirect if can't use the duration.
	if ( ! is_array( $hourminutes ) && count( $hourminutes ) < 2 ) {
		bp_core_add_message( __( 'the duration is not set the right way.', 'rendez-vous' ), 'error' );
		bp_core_redirect( $redirect );
	}

	$minutes  = intval( $hourminutes[1] ) + ( intval( $hourminutes[0] ) * 60 );
	$end_date = strtotime( '+' . $minutes . ' minutes', $rendez_vous->def_date );

	// Dates are stored as UTC althought values are local, we need to reconvert.
	$date_start = date_i18n( 'Y-m-d H:i:s', $rendez_vous->def_date, true );
	$date_end   = date_i18n( 'Y-m-d H:i:s', $end_date, true );

	$tz_string = get_option( 'timezone_string' );

	if ( ! empty( $tz_string ) ) {
		date_default_timezone_set( $tz_string );
	}

	status_header( 200 );
	header( 'Cache-Control: cache, must-revalidate' );
	header( 'Pragma: public' );
	header( 'Content-Description: File Transfer' );
	header( 'Content-Disposition: attachment; filename=rendez_vous_' . $rendez_vous->id . '.ics' );
	header( 'Content-Type: text/calendar' );

	echo "BEGIN:VCALENDAR\n";
	echo "VERSION:2.0\n";
	echo "PRODID:-//hacksw/handcal//NONSGML v1.0//EN\n";
	echo "CALSCALE:GREGORIAN\n";
	echo "BEGIN:VEVENT\n";
	echo 'DTEND:' . gmdate( 'Ymd\THis\Z', strtotime( $date_end ) ) . "\n";
	echo 'UID:' . uniqid() . "\n";
	echo 'DTSTAMP:' . gmdate( 'Ymd\THis\Z', time() ) . "\n";
	echo 'LOCATION:' . esc_html( preg_replace( '/([\,;])/', '\\\$1', $rendez_vous->venue ) ) . "\n";
	echo 'DESCRIPTION:' . esc_html( preg_replace( '/([\,;])/', '\\\$1', $rendez_vous->description ) ) . "\n";
	echo 'URL;VALUE=URI:' . esc_url( rendez_vous_get_single_link( $rendez_vous->id, $rendez_vous->organizer ) ) . "\n";
	echo 'SUMMARY:' . esc_html( preg_replace( '/([\,;])/', '\\\$1', $rendez_vous->title ) ) . "\n";
	echo 'DTSTART:' . gmdate( 'Ymd\THis\Z', strtotime( $date_start ) ) . "\n";
	echo "END:VEVENT\n";
	echo "END:VCALENDAR\n";

	exit();

}
add_action( 'bp_actions', 'rendez_vous_download_ical' );

/**
 * Check whether types have been created.
 *
 * @since 1.2.0
 *
 * @param int|Rendez_Vous_Item $rendez_vous ID or object for the Rendez Vous.
 * @return bool $retval Whether the taxonomy exists.
 */
function rendez_vous_has_types( $rendez_vous = null ) {

	$rdv = rendez_vous();

	if ( empty( $rdv->types ) ) {
		$types      = rendez_vous_get_terms( [ 'hide_empty' => false ] );
		$rdv->types = $types;
	} else {
		$types = $rdv->types;
	}

	if ( empty( $types ) ) {
		return false;
	}

	$retval = true;

	if ( ! empty( $rendez_vous ) ) {
		if ( ! is_a( $rendez_vous, 'Rendez_Vous_Item' ) ) {
			$rendez_vous = rendez_vous_get_item( $rendez_vous );
		}

		$retval = ! empty( $rendez_vous->type );
	}

	return $retval;

}

/**
 * Set type for a Rendez Vous.
 *
 * @since 1.2.0
 *
 * @see bp_set_object_terms()
 *
 * @param int $rendez_vous_id The numeric ID of the Rendez Vous.
 * @param string $type The Rendez Vous type.
 * @return array|WP_Error Array of term taxonomy IDs.
 */
function rendez_vous_set_type( $rendez_vous_id, $type ) {

	if ( ! empty( $type ) && ! rendez_vous_term_exists( $type ) ) {
		return false;
	}

	$retval = bp_set_object_terms( $rendez_vous_id, $type, 'rendez_vous_type' );

	// Clear cache.
	if ( ! is_wp_error( $retval ) ) {
		wp_cache_delete( $rendez_vous_id, 'rendez_vous_type' );
		do_action( 'rendez_vous_set_type', $rendez_vous_id, $type );
	}

	return $retval;

}

/**
 * Get type for a Rendez Vous.
 *
 * @since 1.2.0
 *
 * @param int $rendez_vous_id The numeric ID of the Rendez Vous.
 * @return array|WP_Error The requested term data or empty array if no terms found. WP_Error if any of the taxonomies don't exist.
 */
function rendez_vous_get_type( $rendez_vous_id ) {

	$types = wp_cache_get( $rendez_vous_id, 'rendez_vous_type' );

	if ( false === $types ) {
		$types = bp_get_object_terms( $rendez_vous_id, 'rendez_vous_type' );

		if ( ! is_wp_error( $types ) ) {
			wp_cache_set( $rendez_vous_id, $types, 'rendez_vous_type' );
		}
	}

	return apply_filters( 'rendez_vous_get_type', $types, $rendez_vous_id );

}

/** WP Taxonomy wrapper functions **/

/**
 * Check taxonomy exists on BuddyPress root blog.
 *
 * @since 1.2.0
 *
 * @param string $taxonomy The name of taxonomy object.
 * @return bool $retval Whether the taxonomy exists.
 */
function rendez_vous_taxonomy_exists( $taxonomy ) {

	if ( ! bp_is_root_blog() ) {
		switch_to_blog( bp_get_root_blog_id() );
	}

	$retval = taxonomy_exists( $taxonomy );

	restore_current_blog();

	return $retval;

}

/**
 * Check a type exists on BuddyPress root blog.
 *
 * @since 1.2.0
 *
 * @param int|string $term The term to check.
 * @param string $taxonomy The taxonomy to check.
 * @return bool $retval Whether the taxonomy exists.
 */
function rendez_vous_term_exists( $term, $taxonomy = 'rendez_vous_type' ) {

	if ( ! bp_is_root_blog() ) {
		switch_to_blog( bp_get_root_blog_id() );
	}

	$retval = term_exists( $term, $taxonomy );

	restore_current_blog();

	return $retval;

}

/**
 * Get terms for the Rendez Vous type taxonomy.
 *
 * @since 1.2.0
 *
 * @param array|string $args The arguments.
 * @param string|array $taxonomies The Taxonomy name or list of Taxonomy names.
 * @return array|WP_Error List of Term Objects and their children, or WP_Error if any of $taxonomies do not exist.
 */
function rendez_vous_get_terms( $args = '', $taxonomies = 'rendez_vous_type' ) {

	if ( ! bp_is_root_blog() ) {
		switch_to_blog( bp_get_root_blog_id() );
	}

	$retval = get_terms( $taxonomies, $args );

	restore_current_blog();

	return $retval;

}

/**
 * Get a term for the Rendez Vous type taxonomy.
 *
 * @since 1.2.0
 *
 * @param int|object $term If integer, will get from database. If object will apply filters and return $term.
 * @param string $output Constant OBJECT, ARRAY_A, or ARRAY_N.
 * @param string $filter Optional, default is raw or no WordPress defined filter will applied.
 * @param string $taxonomy Taxonomy name that $term is part of.
 * @return mixed|null|WP_Error Term Row from database. Will return null if $term is empty. If taxonomy does not exist then WP_Error will be returned.
 */
function rendez_vous_get_term( $term, $output = OBJECT, $filter = 'raw', $taxonomy = 'rendez_vous_type' ) {

	if ( ! bp_is_root_blog() ) {
		switch_to_blog( bp_get_root_blog_id() );
	}

	$retval = get_term( $term, $taxonomy, $output, $filter );

	restore_current_blog();

	return $retval;

}

/**
 * Insert a term for the Rendez Vous type taxonomy.
 *
 * @since 1.2.0
 *
 * @param string $term The term to add.
 * @param array|string $args The arguments.
 * @param string $taxonomy The taxonomy to which to add the term.
 * @return array|WP_Error An array containing the `term_id` and `term_taxonomy_id`, or WP_Error otherwise.
 */
function rendez_vous_insert_term( $term, $args = [], $taxonomy = 'rendez_vous_type' ) {

	if ( ! bp_is_root_blog() ) {
		switch_to_blog( bp_get_root_blog_id() );
	}

	$retval = wp_insert_term( $term, $taxonomy, $args );

	restore_current_blog();

	return $retval;

}

/**
 * Update a term for the Rendez Vous type taxonomy.
 *
 * @since 1.2.0
 *
 * @param int $term_id The ID of the term.
 * @param array|string $args Overwrite term field values.
 * @param string $taxonomy The taxonomy to which to update the term.
 * @return array|WP_Error Returns Term ID and Taxonomy Term ID.
 */
function rendez_vous_update_term( $term_id, $args = [], $taxonomy = 'rendez_vous_type' ) {

	if ( ! bp_is_root_blog() ) {
		switch_to_blog( bp_get_root_blog_id() );
	}

	$retval = wp_update_term( $term_id, $taxonomy, $args );

	restore_current_blog();

	return $retval;

}

/**
 * Delete a term for the Rendez Vous type taxonomy.
 *
 * @since 1.2.0
 *
 * @param int $term_id The ID of the term.
 * @param array|string $args Optional. Change 'default' term ID and override found term IDs.
 * @param string $taxonomy The taxonomy to which to update the term.
 * @return bool|WP_Error Returns false if not term; true if completes delete action.
 */
function rendez_vous_delete_term( $term_id, $args = [], $taxonomy = 'rendez_vous_type' ) {
	if ( ! bp_is_root_blog() ) {
		switch_to_blog( bp_get_root_blog_id() );
	}

	$retval = wp_delete_term( $term_id, $taxonomy, $args );

	restore_current_blog();

	return $retval;
}
