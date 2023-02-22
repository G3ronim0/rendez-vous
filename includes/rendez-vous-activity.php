<?php
/**
 * Rendez Vous Activity
 *
 * Activity functions
 *
 * @package Rendez_Vous
 * @subpackage Activity
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Displays a checkbox to allow the User to generate an activity.
 *
 * @since 1.0.0
 */
function rendez_vous_activity_edit_form() {

	?>
	<p>
		<label for="rendez-vous-edit-activity" class="normal">
			<input type="checkbox" id="rendez-vous-edit-activity" name="_rendez_vous_edit[activity]" value="1" <?php disabled( 1, rendez_vous_single_get_privacy() ); ?>> <?php esc_html_e( 'Record an activity for all members', 'rendez-vous' ); ?>
		</label>
	</p>
	<?php

}

add_action( 'rendez_vous_edit_form_after_dates', 'rendez_vous_activity_edit_form' );

/**
 * Register the activity actions.
 *
 * @since 1.0.0
 */
function rendez_vous_register_activity_actions() {

	$bp = buddypress();

	// Bail if activity is not active.
	if ( ! bp_is_active( 'activity' ) ) {
		return false;
	}

	bp_activity_set_action(
		$bp->rendez_vous->id,
		'new_rendez_vous',
		__( 'New Rendez Vous', 'rendez-vous' ),
		'rendez_vous_format_activity_action',
		__( 'New Rendez Vous', 'rendez-vous' ),
		[ 'activity', 'member' ]
	);

	bp_activity_set_action(
		$bp->rendez_vous->id,
		'updated_rendez_vous',
		__( 'Updated a Rendez Vous', 'rendez-vous' ),
		'rendez_vous_format_activity_action',
		__( 'Updated a Rendez Vous', 'rendez-vous' ),
		[ 'activity', 'member' ]
	);

	do_action( 'rendez_vous_register_activity_actions' );

}

add_action( 'bp_register_activity_actions', 'rendez_vous_register_activity_actions' );

/**
 * Format callback.
 *
 * @since 1.0.0
 *
 * @param str $action The action.
 * @param object $activity The Activity object.
 */
function rendez_vous_format_activity_action( $action, $activity ) {

	$rendez_vous_id = $activity->item_id;
	$organizer      = $activity->secondary_item_id;

	if ( $activity->component != buddypress()->rendez_vous->id ) {
		$rendez_vous_id = $activity->secondary_item_id;
		$organizer      = $activity->user_id;
	}

	$rendez_vous_url = rendez_vous_get_single_link( $rendez_vous_id, $organizer );

	$rendez_vous_title = bp_activity_get_meta( $activity->id, 'rendez_vous_title' );

	// Should only be empty at the time of Rendez Vous creation.
	if ( empty( $rendez_vous_title ) ) {

		$rendez_vous = rendez_vous_get_item( $rendez_vous_id );
		if ( is_a( $rendez_vous, 'Rendez_Vous_Item' ) ) {
			$rendez_vous_title = $rendez_vous->title;
			bp_activity_update_meta( $activity->id, 'rendez_vous_title', $rendez_vous_title );
		}

	}

	$rendez_vous_link = '<a href="' . esc_url( $rendez_vous_url ) . '">' . esc_html( $rendez_vous_title ) . '</a>';

	$user_link = bp_core_get_userlink( $activity->user_id );

	$action_part = __( 'scheduled a new', 'rendez-vous' );

	if ( 'updated_rendez_vous' == $activity->type ) {
		$action_part = __( 'updated a', 'rendez-vous' );
	}

	$action = sprintf(
		/* translators: 1: The link to the User, 2: The action, 3: The link to the Rendez Vous. */
		__( '%1$s %2$s Rendez Vous, %3$s', 'rendez-vous' ),
		$user_link,
		$action_part,
		$rendez_vous_link
	);

	return apply_filters( 'rendez_vous_format_activity_action', $action, $activity );

}

/**
 * Publishes a Rendez Vous Activity.
 *
 * @since 1.0.0
 *
 * @param int $id The Activity ID.
 * @param array $args The arguments.
 * @param bool $notify True if notifying, false otherwise.
 * @param object $activity The Activity object.
 */
function rendez_vous_published_activity( $id = 0, $args = [], $notify = false, $activity = false ) {

	if ( empty( $id ) || empty( $activity ) ) {
		return;
	}

	$rendez_vous     = rendez_vous_get_item( $id );
	$rendez_vous_url = rendez_vous_get_single_link( $id, $rendez_vous->organizer );

	$rendez_vous_link = '<a href="' . esc_url( $rendez_vous_url ) . '">' . esc_html( $rendez_vous->title ) . '</a>';

	$user_link = bp_core_get_userlink( $rendez_vous->organizer );

	$action_part = __( 'scheduled a new', 'rendez-vous' );

	$action = sprintf(
		/* translators: 1: The link to the User, 2: The action, 3: The link to the Rendez Vous. */
		__( '%1$s %2$s Rendez Vous, %3$s', 'rendez-vous' ),
		$user_link,
		$action_part,
		$rendez_vous_link
	);

	$content = false;

	if ( ! empty( $rendez_vous->description ) ) {
		$content = bp_create_excerpt( $rendez_vous->description );
	}

	$activity_id = bp_activity_add( apply_filters( 'rendez_vous_published_activity_args', [
		'action'            => $action,
		'content'           => $content,
		'component'         => buddypress()->rendez_vous->id,
		'type'              => 'new_rendez_vous',
		'primary_link'      => $rendez_vous_url,
		'user_id'           => $rendez_vous->organizer,
		'item_id'           => $rendez_vous->id,
		'secondary_item_id' => $rendez_vous->organizer,
	] ) );

	if ( ! empty( $activity_id ) ) {
		bp_activity_update_meta( $activity_id, 'rendez_vous_title', $rendez_vous->title );
	}

	return true;

}

add_action( 'rendez_vous_after_publish', 'rendez_vous_published_activity', 10, 4 );

/**
 * Activity updated.
 *
 * @since 1.0.0
 *
 * @param int $id The Activity ID.
 * @param array $args The arguments.
 * @param bool $notify True if notifying, false otherwise.
 * @param object $activity The Activity object.
 */
function rendez_vous_updated_activity( $id = 0, $args = [], $notify = false, $activity = false ) {

	if ( empty( $id ) || empty( $activity ) ) {
		return;
	}

	$rdv = rendez_vous();

	if ( empty( $rdv->item->id ) ) {
		$rendez_vous = rendez_vous_get_item( $id );
	} else {
		$rendez_vous = $rdv->item;
	}

	$rendez_vous_url = rendez_vous_get_single_link( $id, $rendez_vous->organizer );

	$rendez_vous_link = '<a href="' . esc_url( $rendez_vous_url ) . '">' . esc_html( $rendez_vous->title ) . '</a>';

	$user_link = bp_core_get_userlink( $rendez_vous->organizer );

	$action_part = __( 'updated a', 'rendez-vous' );

	$action = sprintf(
		/* translators: 1: The link to the User, 2: The action, 3: The link to the Rendez Vous. */
		__( '%1$s %2$s Rendez Vous, %3$s', 'rendez-vous' ),
		$user_link,
		$action_part,
		$rendez_vous_link
	);

	$activity_id = bp_activity_add( apply_filters( 'rendez_vous_updated_activity_args', [
		'action'            => $action,
		'component'         => buddypress()->rendez_vous->id,
		'type'              => 'updated_rendez_vous',
		'primary_link'      => $rendez_vous_url,
		'user_id'           => $rendez_vous->organizer,
		'item_id'           => $rendez_vous->id,
		'secondary_item_id' => $rendez_vous->organizer,
	] ) );

	if ( ! empty( $activity_id ) ) {
		bp_activity_update_meta( $activity_id, 'rendez_vous_title', $rendez_vous->title );
	}

	return true;

}

add_action( 'rendez_vous_after_update', 'rendez_vous_updated_activity', 11, 4 );

/**
 * Deletes activities of a cancelled Rendez Vous.
 *
 * @since 1.0.0
 *
 * @param int $rendez_vous_id The Rendez Vous ID.
 * @param object $rendez_vous The Rendez Vous object.
 */
function rendez_vous_delete_item_activities( $rendez_vous_id = 0, $rendez_vous = null ) {

	if ( empty( $rendez_vous_id ) ) {
		return;
	}

	$rendez_vous_status = 'publish';

	if ( is_a( $rendez_vous, 'WP_Post' ) ) {
		$rendez_vous_status = $rendez_vous->post_status;

	} elseif ( is_a( $rendez_vous, 'Rendez_Vous_Item' ) ) {
		$rendez_vous_status = $rendez_vous->status;
	}

	// No need to delete activities in case of drafts.
	if ( ! empty( $rendez_vous ) && 'draft' == $rendez_vous_status ) {
		return;
	}

	$types = [ 'new_rendez_vous', 'updated_rendez_vous' ];
	$args  = apply_filters( 'rendez_vous_delete_item_activities_args', [
		'item_id'   => $rendez_vous_id,
		'component' => buddypress()->rendez_vous->id,
	] );

	foreach ( $types as $type ) {
		$args['type'] = $type;
		bp_activity_delete_by_item_id( $args );
	}

}

add_action( 'rendez_vous_after_delete', 'rendez_vous_delete_item_activities', 10, 2 );
add_action( 'rendez_vous_groups_component_deactivated', 'rendez_vous_delete_item_activities', 10, 2 );
add_action( 'rendez_vous_groups_member_removed', 'rendez_vous_delete_item_activities', 10, 2 );
