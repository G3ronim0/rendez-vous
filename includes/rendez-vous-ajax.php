<?php
/**
 * Rendez Vous AJAX.
 *
 * AJAX functions.
 *
 * @package Rendez_Vous
 * @subpackage Ajax
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the available Members.
 *
 * @since 1.0.0
 */
function rendez_vous_ajax_get_users() {

	check_ajax_referer( 'rendez-vous-editor' );

	$query_args = filter_input( INPUT_POST, 'query', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

	$args = bp_parse_args( $query_args, [
		'user_id'      => false,
		'type'         => 'alphabetical',
		'per_page'     => 20,
		'page'         => 1,
		'search_terms' => false,
		'member_type'  => false,
		'exclude'      => [ bp_loggedin_user_id() ], // We don't want the Organizer to be included in the Attendees.
	], 'rendez_vous_get_users' );

	if ( ! empty( $args['member_type'] ) && 'false' === $args['member_type'] ) {
		$args['member_type'] = false;
	}

	if ( ! empty( $args['group_id'] ) ) {
		// Get all type of Group Users.
		$args['group_role'] = [ 'admin', 'mod', 'member' ];

		$query = new BP_Group_Member_Query( $args );
	} else {
		$query = new BP_User_Query( $args );
	}

	$response = new stdClass();

	$response->meta = [
		'total_page' => 0,
		'current_page' => 0,
	];

	if ( empty( $query->results ) ) {
		wp_send_json_error( $response );
	}

	$users = array_map( 'rendez_vous_prepare_user_for_js', array_values( $query->results ) );
	$users = array_filter( $users );

	if ( ! empty( $args['per_page'] ) ) {
		$response->meta = [
			'total_page' => ceil( (int) $query->total_users / (int) $args['per_page'] ),
			'current_page' => (int) $args['page'],
		];
	}

	$response->items = $users;

	wp_send_json_success( $response );

}

add_action( 'wp_ajax_rendez_vous_get_users', 'rendez_vous_ajax_get_users' );

/**
 * Create a Rendez Vous in draft mode.
 *
 * @since 1.0.0
 */
function rendez_vous_ajax_create() {

	check_ajax_referer( 'rendez-vous-editor', 'nonce' );

	if ( ! bp_current_user_can( 'publish_rendez_vouss' ) ) {
		wp_send_json_error( __( 'You cannot create a Rendez Vous.', 'rendez-vous' ) );
	}

	// Init the create arguments.
	$args = [
		'title'       => '',
		'venue'       => '',
		'type'        => 0,
		'description' => '',
		'duration'    => '',
		'days'        => [],
		'attendees'   => [],
	];

	// First Attendees.
	$attendees = filter_input( INPUT_POST, 'attendees', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
	$attendees = array_map( 'absint', $attendees );
	if ( empty( $attendees ) ) {
		wp_send_json_error( __( 'No users were selected.', 'rendez-vous' ) );
	}

	// Add to create arguments.
	$args['attendees'] = $attendees;

	// Then fields.
	$desc = filter_input( INPUT_POST, 'desc', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
	if ( empty( $desc ) ) {
		wp_send_json_error( __( 'Please describe your Rendez Vous using the What tab.', 'rendez-vous' ) );
	} else {
		$fields = $desc;
	}

	$required_fields_missing = [];

	foreach ( $fields as $field ) {

		if ( 'required' == $field['class'] && empty( $field['value'] ) ) {
			$required_fields_missing[] = $field['label'];
		}

		// Add to create arguments.
		$args[ $field['id'] ] = $field['value'];

	}

	// Required fields are missing.
	if ( ! empty( $required_fields_missing ) ) {
		wp_send_json_error( __( 'Please make sure to fill all required fields.', 'rendez-vous' ) );
	}

	// Then dates.
	$maydates = filter_input( INPUT_POST, 'maydates', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
	if ( empty( $maydates ) ) {
		wp_send_json_error( __( 'Please define dates for your Rendez Vous using the When tab.', 'rendez-vous' ) );
	} else {
		$dates = $maydates;
	}

	$maydates = [];
	$maydates_errors = [];

	foreach ( $dates as $date ) {

		$timestamp = false;

		if ( ! empty( $date['hour1'] ) ) {

			if ( ! preg_match( '/^[0-2]?[0-9]:[0-5][0-9]$/', $date['hour1'] ) ) {
				$maydates_errors[] = $date['hour1'];
				continue;
			}

			$timestamp = strtotime( $date['mysql'] . ' ' . $date['hour1'] );
			$maydates[ $timestamp ] = [];
		}

		if ( ! empty( $date['hour2'] ) ) {

			if ( ! preg_match( '/^[0-2]?[0-9]:[0-5][0-9]$/', $date['hour2'] ) ) {
				$maydates_errors[] = $date['hour2'];
				continue;
			}

			$timestamp = strtotime( $date['mysql'] . ' ' . $date['hour2'] );
			$maydates[ $timestamp ] = [];
		}

		if ( ! empty( $date['hour3'] ) ) {

			if ( ! preg_match( '/^[0-2]?[0-9]:[0-5][0-9]$/', $date['hour3'] ) ) {
				$maydates_errors[] = $date['hour3'];
				continue;
			}

			$timestamp = strtotime( $date['mysql'] . ' ' . $date['hour3'] );
			$maydates[ $timestamp ] = [];
		}

	}

	// Check duration format.
	if ( ! empty( $args['duration'] ) && ! preg_match( '/^[0-2]?[0-9]:[0-5][0-9]$/', $args['duration'] ) ) {
		$maydates_errors[] = $args['duration'];
	}

	if ( ! empty( $maydates_errors ) ) {
		wp_send_json_error( __( 'Please make sure to respect the format HH:MM when defining time.', 'rendez-vous' ) );
	}

	if ( ! empty( $maydates ) ) {
		$args['days'] = $maydates;
	}

	if ( ! empty( $_POST['group_id'] ) ) {
		$args['group_id'] = absint( $_POST['group_id'] );
	}

	$rendez_vous_id = rendez_vous_save( $args );

	if ( empty( $rendez_vous_id ) ) {
		wp_send_json_error( __( 'The Rendez Vous was not created due to an error.', 'rendez-vous' ) );
	} else {
		// url to edit Rendez Vous screen.
		wp_send_json_success( esc_url_raw( rendez_vous_get_edit_link( $rendez_vous_id, bp_loggedin_user_id() ) ) );
	}

}

add_action( 'wp_ajax_create_rendez_vous', 'rendez_vous_ajax_create' );

/**
 * Insert a new Rendez Vous type.
 *
 * @since 1.2.0
 */
function rendez_vous_ajax_insert_term() {

	if ( ! isset( $_POST['rendez_vous_type_name'] ) ) {
		wp_send_json_error();
	}

	check_ajax_referer( 'rendez-vous-admin', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error();
	}

	if ( ! rendez_vous_taxonomy_exists( 'rendez_vous_type' ) ) {
		wp_send_json_error();
	}

	$term = esc_html( sanitize_text_field( wp_unslash( $_POST['rendez_vous_type_name'] ) ) );

	$inserted = rendez_vous_insert_term( $term );

	if ( empty( $inserted['term_id'] ) || is_wp_error( $inserted ) ) {
		wp_send_json_error();
	}

	$term = rendez_vous_prepare_term_for_js( rendez_vous_get_term( $inserted['term_id'] ) );

	if ( empty( $term ) ) {
		wp_send_json_error();
	}

	wp_send_json_success( $term );

}

add_action( 'wp_ajax_rendez_vous_insert_term', 'rendez_vous_ajax_insert_term' );

/**
 * Get all Rendez Vous types.
 *
 * @since 1.2.0
 */
function rendez_vous_ajax_get_terms() {

	check_ajax_referer( 'rendez-vous-admin', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error();
	}

	if ( ! rendez_vous_taxonomy_exists( 'rendez_vous_type' ) ) {
		wp_send_json_error();
	}

	$terms = rendez_vous_get_terms( [ 'hide_empty' => false ] );
	$terms = array_map( 'rendez_vous_prepare_term_for_js', array_values( $terms ) );
	$terms = array_filter( $terms );

	wp_send_json_success( $terms );

}

add_action( 'wp_ajax_rendez_vous_get_terms', 'rendez_vous_ajax_get_terms' );

/**
 * Delete a Rendez Vous types.
 *
 * @since 1.2.0
 */
function rendez_vous_ajax_delete_term() {

	if ( ! isset( $_POST['rendez_vous_type_id'] ) ) {
		wp_send_json_error();
	}

	check_ajax_referer( 'rendez-vous-admin', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error();
	}

	if ( ! rendez_vous_taxonomy_exists( 'rendez_vous_type' ) ) {
		wp_send_json_error();
	}

	$term_id = (int) sanitize_text_field( wp_unslash( $_POST['rendez_vous_type_id'] ) );

	$deleted = rendez_vous_delete_term( $term_id );

	if ( empty( $deleted ) || is_wp_error( $deleted ) ) {
		wp_send_json_error();
	}

	wp_send_json_success();

}

add_action( 'wp_ajax_rendez_vous_delete_term', 'rendez_vous_ajax_delete_term' );

/**
 * Update a Rendez Vous type.
 *
 * @since 1.2.0
 *
 * @todo Use a wrapper function for taxonomy_exists, wp_update_term making sure current blog is BuddyPress root blog.
 */
function rendez_vous_ajax_update_term() {

	if ( ! isset( $_POST['rendez_vous_type_id'] ) || empty( $_POST['rendez_vous_type_name'] ) ) {
		wp_send_json_error();
	}

	check_ajax_referer( 'rendez-vous-admin', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error();
	}

	if ( ! rendez_vous_taxonomy_exists( 'rendez_vous_type' ) ) {
		wp_send_json_error();
	}

	$term_id   = (int) sanitize_text_field( wp_unslash( $_POST['rendez_vous_type_id'] ) );
	$term_name = esc_html( sanitize_text_field( wp_unslash( $_POST['rendez_vous_type_name'] ) ) );

	$updated = rendez_vous_update_term( $term_id, [ 'name' => $term_name ] );

	if ( empty( $updated ) || is_wp_error( $updated ) ) {
		wp_send_json_error();
	}

	wp_send_json_success();

}

add_action( 'wp_ajax_rendez_vous_update_term', 'rendez_vous_ajax_update_term' );
