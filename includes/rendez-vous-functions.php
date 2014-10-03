<?php
/**
 * Rendez Vous Functions.
 *
 * Plugin functions
 *
 * @package Rendez Vous
 * @subpackage Functions
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Get a rendez-vous
 *
 * @package Rendez Vous
 * @subpackage Functions
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_get_item( $id = 0 ) {
	if ( empty( $id ) )
		return false;

	$rendez_vous = new Rendez_Vous_Item( $id );

	return apply_filters( 'rendez_vous_get_item', $rendez_vous );
}

/**
 * Get rendez-vouss
 *
 * @package Rendez Vous
 * @subpackage Functions
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_get_items( $args = array() ) {
	$defaults = array(
		'attendees'       => array(), // one or more user ids who may attend to the rendez vous
		'organizer'	      => false,   // the author id of the rendez vous
		'per_page'	      => 20,
		'page'		      => 1,
		'search'          => false,
		'exclude'		  => false,   // comma separated list or array of rendez vous ids.
		'orderby' 		  => 'modified',
		'order'           => 'DESC',
		'group_id'        => false,
	);

	$r = bp_parse_args( $args, $defaults, 'rendez_vous_get_items_args' );

	$rendez_vouss = wp_cache_get( 'rendez_vous_rendez_vouss', 'bp' );

	if ( empty( $rendez_vouss ) ) {
		$rendez_vouss = Rendez_Vous_Item::get( array(
			'attendees'       => (array) $r['attendees'],
			'organizer'	      => (int) $r['organizer'],
			'per_page'	      => $r['per_page'],
			'page'		      => $r['page'],
			'search'          => $r['search'],
			'exclude'		  => $r['exclude'],
			'orderby' 		  => $r['orderby'],
			'order'           => $r['order'],
			'group_id'        => $r['group_id'],
		) );

		wp_cache_set( 'rendez_vous_rendez_vouss', $rendez_vouss, 'bp' );
	}

	return apply_filters_ref_array( 'rendez_vous_get_items', array( &$rendez_vouss, &$r ) );
}

/**
 * Launch the Rendez Vous Editor
 *
 * @package Rendez Vous
 * @subpackage Functions
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_editor( $editor_id, $settings = array() ) {
	Rendez_Vous_Editor::editor( $editor_id, $settings );
}

/**
 * Prepare the user for js
 *
 * @package Rendez Vous
 * @subpackage Functions
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_prepare_user_for_js( $users ) {

	$response = array(
		'id'           => intval( $users->ID ),
		'name'         => $users->display_name,
		'avatar'       => htmlspecialchars_decode( bp_core_fetch_avatar( array(
				'item_id' => $users->ID,
				'object'  => 'user',
				'type'    => 'full',
				'width'   => 150,
				'height'  => 150,
				'html'    => false
			)
		) ),
	);

	return apply_filters( 'rendez_vous_prepare_user_for_js', $response, $users );
}

/**
 * Save a Rendez Vous
 *
 * @package Rendez Vous
 * @subpackage Functions
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_save( $args = array() ) {

	$r = bp_parse_args( $args, array(
		'id'          => false,
		'organizer'   => bp_loggedin_user_id(),
		'title'       => '',
		'venue'       => '',
		'description' => '',
		'duration'    => '',
		'privacy'     => '',
		'status'      => 'draft',
		'days'        => array(),   // array( 'timestamp' => array( attendees id ) )
		'attendees'   => array(),	// Attendees id
		'def_date'    => 0, 	    // timestamp
		'report'      => '',
		'group_id'    => false,
	), 'rendez_vous_save_args' );

	if ( empty( $r['title'] ) || empty( $r['organizer'] ) ) {
		return false;
	}

	// Using rendez_vous
	$rendez_vous = new Rendez_Vous_Item( $r['id'] );

	$rendez_vous->organizer   = $r['organizer'];
	$rendez_vous->title       = $r['title'];
	$rendez_vous->venue       = $r['venue'];
	$rendez_vous->description = $r['description'];
	$rendez_vous->duration    = $r['duration'];
	$rendez_vous->privacy     = $r['privacy'];
	$rendez_vous->status      = $r['status'];
	$rendez_vous->attendees   = $r['attendees'];
	$rendez_vous->def_date    = $r['def_date'];
	$rendez_vous->report      = $r['report'];
	$rendez_vous->group_id    = $r['group_id'];

	// Allow attendees to not attend !
	if ( 'draft' == $r['status'] && ! in_array( 'none', array_keys( $r['days'] ) ) ) {
		$r['days']['none'] = array();

		// Saving days the first time only
		$rendez_vous->days    = $r['days'];
	}

	do_action( 'rendez_vous_before_saved', $rendez_vous, $r );

	$id = $rendez_vous->save();

	do_action( 'rendez_vous_after_saved', $rendez_vous, $r );

	return $id;
}

/**
 * Delete a rendez-vous
 *
 * @package Rendez Vous
 * @subpackage Functions
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_delete_item( $id = 0 ) {
	if ( empty( $id ) )
		return false;

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
 * Set caps
 *
 * @package Rendez Vous
 * @subpackage Functions
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_get_caps() {
	return apply_filters( 'rendez_vous_get_caps', array (
		'edit_posts'          => 'edit_rendez_vouss',
		'edit_others_posts'   => 'edit_others_rendez_vouss',
		'publish_posts'       => 'publish_rendez_vouss',
		'read_private_posts'  => 'read_private_rendez_vouss',
		'delete_posts'        => 'delete_rendez_vouss',
		'delete_others_posts' => 'delete_others_rendez_vouss'
	) );
}

/**
 * Display link
 *
 * @package Rendez Vous
 * @subpackage Functions
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_get_single_link( $id = 0, $organizer_id = 0 ) {
	if ( empty( $id ) || empty( $organizer_id ) )
		return false;

	$link = trailingslashit( bp_core_get_user_domain( $organizer_id ) . buddypress()->rendez_vous->slug );
	$link = add_query_arg( array( 'rdv' => $id ), $link );

	return apply_filters( 'rendez_vous_get_single_link', $link, $id, $organizer_id );
}

/**
 * Edit link
 *
 * @package Rendez Vous
 * @subpackage Functions
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_get_edit_link( $id = 0, $organizer_id = 0 ) {
	if ( empty( $id ) || empty( $organizer_id ) )
		return false;

	$link = trailingslashit( bp_core_get_user_domain( $organizer_id ) . buddypress()->rendez_vous->slug );
	$link = add_query_arg( array( 'rdv' => $id, 'action' => 'edit' ), $link );

	return apply_filters( 'rendez_vous_get_edit_link', $link, $id, $organizer_id );
}

/**
 * Delete link
 *
 * @package Rendez Vous
 * @subpackage Functions
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_get_delete_link( $id = 0, $organizer_id = 0 ) {
	if ( empty( $id ) || empty( $organizer_id ) )
		return false;

	$link = trailingslashit( bp_core_get_user_domain( $organizer_id ) . buddypress()->rendez_vous->slug );
	$link = add_query_arg( array( 'rdv' => $id, 'action' => 'delete' ), $link );
	$link = wp_nonce_url( $link, 'rendez_vous_delete' );

	return apply_filters( 'rendez_vous_get_delete_link', $link, $id, $organizer_id );
}

/**
 * Mayb run upgrate routines
 *
 * @package Rendez Vous
 * @subpackage Functions
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_maybe_upgrade() {
	if ( get_current_blog_id() == bp_get_root_blog_id() ) {

		$db_version = bp_get_option( 'rendez-vous-version', 0 );

		if ( version_compare( rendez_vous()->version, $db_version, '>' ) ) {
			// run some routines..
			do_action( 'rendez_vous_upgrade' );

			// Update db version
			bp_update_option( 'rendez-vous-version', rendez_vous()->version );
		}
	}
}
