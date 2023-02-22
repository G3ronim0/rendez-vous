<?php
/**
 * Rendez Vous Notifications.
 *
 * Screen and Email notification functions.
 *
 * @package Rendez_Vous
 * @subpackage Notifications
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Displays a checkbox to allow notifications to be sent.
 *
 * @since 1.0.0
 */
function rendez_vous_notifications_edit_form() {

	?>
	<p>
		<label for="rendez-vous-edit-notify" class="normal">
			<input type="checkbox" id="rendez-vous-edit-notify" name="_rendez_vous_edit[notify]" value="1" checked="true"> <?php esc_html_e( 'Notify attendees of changes about this rendez-vous', 'rendez-vous' ); ?>
		</label>
	</p>
	<?php

}

add_action( 'rendez_vous_edit_form_after_dates', 'rendez_vous_notifications_edit_form', 9 );

/**
 * Let the User customize email settings.
 *
 * @since 1.0.0
 */
function rendez_vous_screen_notification_settings() {

	$user_id = bp_displayed_user_id() ? bp_displayed_user_id() : bp_loggedin_user_id();

	$schedule_notifications = get_user_meta( $user_id, 'notification_rendez_vous_schedule', true );
	$attend_notifications   = get_user_meta( $user_id, 'notification_rendez_vous_attend', true );

	if ( empty( $schedule_notifications ) ) {
		$schedule_notifications = 'yes';
	}

	if ( empty( $attend_notifications ) ) {
		$attend_notifications = 'yes';
	}

	?>
	<table class="notification-settings" id="rendez-vous-notification-settings">

		<thead>
			<tr>
				<th class="icon"></th>
				<th class="title"><?php esc_html_e( 'Rendez Vous', 'rendez-vous' ); ?></th>
				<th class="yes"><?php esc_html_e( 'Yes', 'rendez-vous' ); ?></th>
				<th class="no"><?php esc_html_e( 'No', 'rendez-vous' ); ?></th>
			</tr>
		</thead>

		<tbody>
			<tr>
				<td></td>
				<td><?php esc_html_e( 'An attendee replyed about a Rendez Vous you scheduled', 'rendez-vous' ); ?></td>
				<td class="yes"><input type="radio" name="notifications[notification_rendez_vous_schedule]" value="yes" <?php checked( 'yes', $schedule_notifications ); ?>/></td>
				<td class="no"><input type="radio" name="notifications[notification_rendez_vous_schedule]" value="no" <?php checked( 'no', $schedule_notifications ); ?>/></td>
			</tr>
			<tr>
				<td></td>
				<td><?php esc_html_e( 'You are invited to participate in a Rendez Vous', 'rendez-vous' ); ?></td>
				<td class="yes"><input type="radio" name="notifications[notification_rendez_vous_attend]" value="yes" <?php checked( 'yes', $attend_notifications ); ?>/></td>
				<td class="no"><input type="radio" name="notifications[notification_rendez_vous_attend]" value="no" <?php checked( 'no', $attend_notifications ); ?>/></td>
			</tr>
			<?php do_action( 'rendez_vous_screen_notification_settings' ); ?>
		</tbody>

	</table>
	<?php

}

add_action( 'bp_notification_settings', 'rendez_vous_screen_notification_settings' );

/**
 * Send a screen notifications & email one when a Rendez Vous is published.
 *
 * @since 1.0.0
 * @since 1.4.0 Use the BP Emails.
 *
 * @param int $id The numeric ID of the Rendez Vous.
 * @param array $args The arguments.
 * @param bool $notify True when notifying Attendees, false otherwise.
 */
function rendez_vous_published_notification( $id = 0, $args = [], $notify = 0 ) {

	$bp = buddypress();

	if ( empty( $id ) || empty( $notify ) ) {
		return;
	}

	$rendez_vous = rendez_vous_get_item( $id );
	$attendees   = $rendez_vous->attendees;
	if ( empty( $attendees ) ) {
		return;
	}

	// Remove the Organizer.
	if ( in_array( $rendez_vous->organizer, $attendees ) ) {
		$attendees = array_diff( $attendees, [ $rendez_vous->organizer ] );
	}

	$organizer_name      = bp_core_get_user_displayname( $rendez_vous->organizer );
	$rendez_vous_link    = rendez_vous_get_single_link( $id, $rendez_vous->organizer );
	$rendez_vous_content = stripslashes( $rendez_vous->title ) . "\n\n" . stripslashes( $rendez_vous->description );
	$organizer_name      = stripslashes( $organizer_name );

	// Append the custom message, if any.
	if ( ! empty( $args['message'] ) ) {
		$rendez_vous_content .= "\n\n" . stripslashes( $args['message'] );
	}

	$rendez_vous_content = wp_kses( $rendez_vous_content, [] );

	// Set the BP Email tokens.
	$bp_email_args = [
		'tokens' => [
			'organizer.name'     => $organizer_name,
			'rendezvous.content' => $rendez_vous_content,
			'rendezvous.url'     => esc_url( $rendez_vous_link ),
			'rendezvous.title'   => $rendez_vous->title,
		],
	];

	// This way we'll have all needed Users data at one and we will avoid spam Users.
	$users = bp_core_get_users( [
		'type'    => 'alphabetical',
		'include' => implode( ',', $attendees ),
	] );

	foreach ( $users['users'] as $attendee ) {

		// Screen Notification.
		bp_notifications_add_notification( [
			'user_id'           => $attendee->ID,
			'item_id'           => $id,
			'secondary_item_id' => $rendez_vous->organizer,
			'component_name'    => $bp->rendez_vous->id,
			'component_action'  => 'rendez_vous_attend',
		] );

		// Sending Emails.
		if ( 'no' == get_user_meta( $attendee->ID, 'notification_rendez_vous_attend', true ) ) {
			continue;
		}

		// Send the Email.
		bp_send_email( 'rendez-vous-new-item', (int) $attendee->ID, $bp_email_args );

	}

}

add_action( 'rendez_vous_after_publish', 'rendez_vous_published_notification', 10, 3 );

/**
 * Help to check if a notification needs to be send.
 *
 * @since 1.0.0
 *
 * @param array $args The arguments.
 * @param bool $notify True when notifying Attendees, false otherwise.
 */
function rendez_vous_maybe_notify_updates( $args = [], $notify = 0 ) {

	$rendez_vous = rendez_vous();

	if ( empty( $notify ) || empty( $args ) ) {
		return;
	}

	$rendez_vous->item = rendez_vous_get_item( absint( $args['id'] ) );

	if ( empty( $rendez_vous->item ) ) {
		return;
	}

	if ( empty( $rendez_vous->item->def_date ) && ! empty( $args['def_date'] ) ) {
		$rendez_vous->item->date_fixed = $args['def_date'];
	}

	if ( empty( $rendez_vous->item->report ) && ! empty( $args['report'] ) ) {
		$rendez_vous->item->report_created = 1;
	}

}

add_action( 'rendez_vous_before_update', 'rendez_vous_maybe_notify_updates', 10, 2 );

/**
 * Send a screen notifications & email one when a Rendez Vous is updated.
 *
 * Date set or report created.
 *
 * @since 1.0.0
 * @since 1.4.0 Use the BP Emails.
 *
 * @param int $id The numeric ID of the Rendez Vous.
 * @param array $args The arguments.
 * @param bool $notify True when notifying Attendees, false otherwise.
 */
function rendez_vous_updated_notification( $id = 0, $args = [], $notify = 0 ) {

	$bp  = buddypress();
	$rdv = rendez_vous();

	if ( empty( $id ) || empty( $notify ) || empty( $rdv->item ) || $id != $rdv->item->id || empty( $args['message'] ) ) {
		return;
	}

	$has_updated = ! empty( $rdv->item->date_fixed ) || ! empty( $rdv->item->report_created ) ? true : false;

	if ( empty( $has_updated ) && empty( $args['message'] ) ) {
		return;
	}

	// Only allow 1 message per day.
	if ( empty( $has_updated ) && 1 == get_transient( 'rendez_vous_last_message_' . $id ) ) {
		return;
	}

	$rendez_vous = $rdv->item;
	$attendees   = $rendez_vous->attendees;
	if ( empty( $attendees ) ) {
		return;
	}

	// Remove the Organizer.
	if ( in_array( $rendez_vous->organizer, $attendees ) ) {
		$attendees = array_diff( $attendees, [ $rendez_vous->organizer ] );
	}

	$organizer_name   = bp_core_get_user_displayname( $rendez_vous->organizer );
	$rendez_vous_link = rendez_vous_get_single_link( $id, $rendez_vous->organizer );

	$rendez_vous_content = stripslashes( $rendez_vous->title );
	$rdv_updated_action  = false;
	$component_action    = false;

	if ( ! empty( $rdv->item->date_fixed ) ) {
		if ( is_numeric( $rdv->item->date_fixed ) ) {
			/* translators: 1: The date, 2: The time. */
			$rendez_vous_content .= "\n\n" . sprintf( __( 'Date fixed to %1$s at %2$s', 'rendez-vous' ),
				date_i18n( get_option( 'date_format' ), $rdv->item->date_fixed ),
				date_i18n( get_option( 'time_format' ), $rdv->item->date_fixed )
			);
			/* translators: %s: The URL of the calendar file. */
			$rendez_vous_content .= "\n\n" . sprintf( __( 'Download the <a href="%s">Calendar file</a>', 'rendez-vous' ), rendez_vous_get_ical_link( $id, $rendez_vous->organizer ) );
		} else {
			/* translators: %s: The date. */
			$rendez_vous_content .= "\n\n" . sprintf( __( 'Date fixed to %s', 'rendez-vous' ),
				esc_html( $rdv->item->date_fixed )
			);
		}

		$rdv_updated_action = __( 'fixed the date', 'rendez-vous' );
		$component_action   = 'rendez_vous_fixed';
	} elseif ( ! empty( $rdv->item->report_created ) ) {
		$rdv_updated_action = __( 'created the report', 'rendez-vous' );
		$component_action   = 'rendez_vous_report';
	} elseif ( ! empty( $args['message'] ) ) {
		$rdv_updated_action = __( 'sent a message', 'rendez-vous' );
		$component_action   = 'rendez_vous_message';
		set_transient( 'rendez_vous_last_message_' . $id, 1, 24 * HOUR_IN_SECONDS );
	}

	$organizer_name = stripslashes( $organizer_name );

	// Append the custom message, if any.
	if ( ! empty( $args['message'] ) ) {
		$rendez_vous_content .= "\n\n" . stripslashes( $args['message'] );
	}

	// Allow the link tag so that the calendar link can be inserted into the email content.
	$rendez_vous_content = wp_kses( $rendez_vous_content, [ 'a' => [ 'href' => true ] ] );

	// Set the BP Email tokens.
	$bp_email_args = [
		'tokens' => [
			'organizer.name'     => $organizer_name,
			'rendezvous.action'  => $rdv_updated_action,
			'rendezvous.content' => $rendez_vous_content,
			'rendezvous.url'     => esc_url( $rendez_vous_link ),
			'rendezvous.title'   => $rendez_vous->title,
		],
	];

	// This way we'll have all needed Users data at one and we will avoid spam Users.
	$users = bp_core_get_users( [
		'type'    => 'alphabetical',
		'include' => implode( ',', $attendees ),
	] );

	foreach ( $users['users'] as $attendee ) {

		if ( 'rendez_vous_message' != $component_action ) {
			// Screen Notification.
			bp_notifications_add_notification( [
				'user_id'           => $attendee->ID,
				'item_id'           => $id,
				'secondary_item_id' => $rendez_vous->organizer,
				'component_name'    => $bp->rendez_vous->id,
				'component_action'  => $component_action,
			] );
		}

		// Sending Emails.
		if ( 'no' == get_user_meta( $attendee->ID, 'notification_rendez_vous_attend', true ) ) {
			continue;
		}

		// Send the Email.
		bp_send_email( 'rendez-vous-item-edited', (int) $attendee->ID, $bp_email_args );

	}

}

add_action( 'rendez_vous_after_update', 'rendez_vous_updated_notification', 10, 3 );

/**
 * Send a screen notifications & email one when an Attendee set his preferences.
 *
 * @since 1.0.0
 * @since 1.4.0 Use the BP Emails.
 *
 * @param array $args The arguments.
 * @param int $attendee_id The numeric ID of the Attendee.
 * @param object $rendez_vous The Rendez Vous object.
 */
function rendez_vous_notify_organizer( $args = [], $attendee_id = 0, $rendez_vous = null ) {

	$bp = buddypress();

	if ( empty( $attendee_id ) || empty( $rendez_vous ) || $attendee_id == $rendez_vous->organizer ) {
		return;
	}

	// Screen Notification.
	bp_notifications_add_notification( [
		'user_id'           => $rendez_vous->organizer,
		'item_id'           => $rendez_vous->id,
		'secondary_item_id' => $attendee_id,
		'component_name'    => $bp->rendez_vous->id,
		'component_action'  => 'rendez_vous_schedule',
	] );

	// Sending Emails.
	if ( 'no' == get_user_meta( $rendez_vous->organizer, 'notification_rendez_vous_schedule', true ) ) {
		return;
	}

	$rendez_vous_link    = rendez_vous_get_single_link( $rendez_vous->id, $rendez_vous->organizer );
	$rendez_vous_title   = stripslashes( $rendez_vous->title );
	$rendez_vous_content = wp_kses( $rendez_vous_title, [] );

	$attendee_name = bp_core_get_user_displayname( $attendee_id );
	$attendee_name = stripslashes( $attendee_name );

	// Send the Email.
	bp_send_email( 'rendez-vous-preference-set', (int) $rendez_vous->organizer, [
		'tokens' => [
			'attendee.name'      => $attendee_name,
			'rendezvous.content' => $rendez_vous_content,
			'rendezvous.url'     => esc_url( $rendez_vous_link ),
			'rendezvous.title'   => $rendez_vous_title,
		],
	] );

}

add_action( 'rendez_vous_after_attendee_prefs', 'rendez_vous_notify_organizer', 10, 3 );

/**
 * Mark screen notifications.
 *
 * @since 1.0.0
 */
function rendez_vous_remove_all_attend_notifications() {

	if ( ! isset( $_GET['n'] ) ) {
		return;
	}

	$bp = buddypress();

	// Would be nice if BuddyPress allowed marking notifications for an array of actions.
	bp_notifications_mark_notifications_by_type( bp_loggedin_user_id(), buddypress()->rendez_vous->id, 'rendez_vous_attend' );
	bp_notifications_mark_notifications_by_type( bp_loggedin_user_id(), buddypress()->rendez_vous->id, 'rendez_vous_fixed' );
	bp_notifications_mark_notifications_by_type( bp_loggedin_user_id(), buddypress()->rendez_vous->id, 'rendez_vous_report' );

}

add_action( 'rendez_vous_attend', 'rendez_vous_remove_all_attend_notifications' );

/**
 * Mark screen notifications.
 *
 * @since 1.0.0
 */
function rendez_vous_remove_current_attend_notifications() {

	if ( ! isset( $_GET['rdv'] ) || ! isset( $_GET['n'] ) ) {
		return;
	}

	$rdv = isset( $_GET['rdv'] ) ? sanitize_text_field( wp_unslash( $_GET['rdv'] ) ) : '';

	$bp = buddypress();

	// Removing all for the current User & current Rendez Vous.
	bp_notifications_mark_notifications_by_item_id( bp_loggedin_user_id(), $rdv, buddypress()->rendez_vous->id, false );

}

add_action( 'rendez_vous_single_screen', 'rendez_vous_remove_current_attend_notifications' );

/**
 * Mark notifications.
 *
 * @since 1.0.0
 */
function rendez_vous_remove_all_schedule_notifications() {

	if ( ! isset( $_GET['n'] ) ) {
		return;
	}

	$bp = buddypress();

	// Would be nice if BuddyPress allowed marking notifications for an array of actions...
	bp_notifications_mark_notifications_by_type( bp_loggedin_user_id(), buddypress()->rendez_vous->id, 'rendez_vous_schedule' );

}

add_action( 'rendez_vous_schedule', 'rendez_vous_remove_all_schedule_notifications' );

/**
 * Format screen notifications.
 *
 * @since 1.0.0
 *
 * @param str $action The action being performed.
 * @param int $item_id The numeric ID of the primary notification item.
 * @param int $secondary_item_id The numeric ID of the secondary notification item.
 * @param int $total_items The total number of items.
 * @param str $format The output format.
 * @return str $return The formatted notification.
 */
function rendez_vous_format_notifications( $action, $item_id, $secondary_item_id, $total_items, $format = 'string' ) {

	$bp = buddypress();

	switch ( $action ) {

		case 'rendez_vous_schedule':

			if ( (int) $total_items > 1 ) {
				$rendez_vous_link = add_query_arg(
					[ 'n' => $total_items ],
					trailingslashit( bp_loggedin_user_domain() . $bp->rendez_vous->slug . '/schedule' )
				);
				$title            = __( 'Rendez Vous preferences updated', 'rendez-vous' );
				/* translators: %d: The number of items. */
				$text   = sprintf( __( '%d Rendez Vous preferences updated', 'rendez-vous' ), (int) $total_items );
				$filter = 'rendez_vous_multiple_userset_notification';
			} else {
				$rendez_vous_link = add_query_arg(
					[ 'n' => 1 ],
					rendez_vous_get_single_link( $item_id, bp_loggedin_user_id() )
				);
				$user_fullname    = bp_core_get_user_displayname( $secondary_item_id, false );
				$title            = __( 'View the Rendez Vous', 'rendez-vous' );
				/* translators: %s: The name of the User. */
				$text   = sprintf( __( '%s set his preferences about a Rendez Vous', 'rendez-vous' ), $user_fullname );
				$filter = 'rendez_vous_single_userset_notification';
			}

			break;

		case 'rendez_vous_attend':

			if ( (int) $total_items > 1 ) {
				$rendez_vous_link = add_query_arg(
					[ 'n' => $total_items ],
					trailingslashit( bp_loggedin_user_domain() . $bp->rendez_vous->slug . '/attend' )
				);
				$title            = __( 'Rendez Vous scheduled', 'rendez-vous' );
				/* translators: %d: The number of items. */
				$text   = sprintf( __( '%d Rendez Vous scheduled', 'rendez-vous' ), (int) $total_items );
				$filter = 'rendez_vous_multiple_attend_notification';
			} else {
				$rendez_vous_link = add_query_arg(
					[ 'n' => 1 ],
					rendez_vous_get_single_link( $item_id, $secondary_item_id )
				);
				$user_fullname    = bp_core_get_user_displayname( $secondary_item_id, false );
				$title            = __( 'View the Rendez Vous', 'rendez-vous' );
				/* translators: %s: The name of the User. */
				$text   = sprintf( __( '%s scheduled a Rendez Vous', 'rendez-vous' ), $user_fullname );
				$filter = 'rendez_vous_single_attend_notification';
			}

			break;

		case 'rendez_vous_fixed':

			if ( (int) $total_items > 1 ) {
				$rendez_vous_link = add_query_arg(
					[ 'n' => $total_items ],
					trailingslashit( bp_loggedin_user_domain() . $bp->rendez_vous->slug . '/attend' )
				);
				$title            = __( 'Rendez Vous fixed', 'rendez-vous' );
				/* translators: %d: The number of items. */
				$text   = sprintf( __( '%d Rendez Vous fixed', 'rendez-vous' ), (int) $total_items );
				$filter = 'rendez_vous_multiple_fixed_notification';
			} else {
				$rendez_vous_link = add_query_arg(
					[ 'n' => 1 ],
					rendez_vous_get_single_link( $item_id, $secondary_item_id )
				);
				$user_fullname    = bp_core_get_user_displayname( $secondary_item_id, false );
				$title            = __( 'View the Rendez Vous', 'rendez-vous' );
				/* translators: %s: The name of the User. */
				$text   = sprintf( __( '%s fixed a Rendez Vous', 'rendez-vous' ), $user_fullname );
				$filter = 'rendez_vous_single_fixed_notification';
			}

			break;

		case 'rendez_vous_report':

			if ( (int) $total_items > 1 ) {
				$rendez_vous_link = add_query_arg(
					[ 'n' => $total_items ],
					trailingslashit( bp_loggedin_user_domain() . $bp->rendez_vous->slug . '/attend' )
				);
				$title            = __( 'Rendez Vous report created', 'rendez-vous' );
				/* translators: %d: The number of items. */
				$text   = sprintf( __( '%d Rendez Vous reports created', 'rendez-vous' ), (int) $total_items );
				$filter = 'rendez_vous_multiple_report_notification';
			} else {
				$rendez_vous_link = add_query_arg(
					[ 'n' => 1 ],
					rendez_vous_get_single_link( $item_id, $secondary_item_id )
				);
				$user_fullname    = bp_core_get_user_displayname( $secondary_item_id, false );
				$title            = __( 'View the Rendez Vous', 'rendez-vous' );
				/* translators: %s: The name of the User. */
				$text   = sprintf( __( '%s created a report for a Rendez Vous', 'rendez-vous' ), $user_fullname );
				$filter = 'rendez_vous_single_report_notification';
			}

			break;

	}

	/*
	 * If on notifications read screen remove the n arguments to
	 * avoid re-running the mark notification function.
	 */
	if ( bp_is_user_notifications() && bp_is_current_action( 'read' ) ) {
		$rendez_vous_link = remove_query_arg( 'n', $rendez_vous_link );
	}

	if ( 'string' == $format ) {
		$return = apply_filters( $filter, '<a href="' . esc_url( $rendez_vous_link ) . '" title="' . esc_attr( $title ) . '">' . esc_html( $text ) . '</a>', $rendez_vous_link, (int) $total_items, $item_id, $secondary_item_id );
	} else {
		$return = apply_filters( $filter, [
			'text' => $text,
			'link' => esc_url( $rendez_vous_link ),
		], $rendez_vous_link, (int) $total_items, $item_id, $secondary_item_id );
	}

	do_action( 'rendez_vous_format_notifications', $action, $item_id, $secondary_item_id, $total_items );

	return $return;

}

/**
 * Deletes notifications for a cancelled Rendez Vous.
 *
 * @since 1.0.0
 *
 * @param int $rendez_vous_id The numeric ID of the Rendez Vous.
 * @param object $rendez_vous The Rendez Vous object.
 */
function rendez_vous_delete_item_notifications( $rendez_vous_id = 0, $rendez_vous = null ) {

	if ( empty( $rendez_vous_id ) ) {
		return;
	}

	// No need to delete activities in case of drafts.
	if ( ! empty( $rendez_vous ) && 'draft' == $rendez_vous->post_status ) {
		return;
	}

	// Delete all notifications.
	BP_Notifications_Notification::delete( [
		'item_id'        => $rendez_vous_id,
		'component_name' => buddypress()->rendez_vous->id,
	] );

}

add_action( 'rendez_vous_after_delete', 'rendez_vous_delete_item_notifications', 9, 2 );

/**
 * Remove a User's notification data.
 *
 * @since 1.0.0
 *
 * @param int $user_id The numeric ID of the WordPress User.
 */
function rendez_vous_remove_all_user_notifications( $user_id = 0 ) {

	if ( empty( $user_id ) ) {
		return false;
	}

	// Delete all notifications User sent to others.
	BP_Notifications_Notification::delete( [
		'secondary_item_id' => $user_id,
		'component_name'    => buddypress()->rendez_vous->id,
	] );

}

add_action( 'wpmu_delete_user', 'rendez_vous_remove_all_user_notifications', 10, 1 );
add_action( 'delete_user', 'rendez_vous_remove_all_user_notifications', 10, 1 );

/**
 * Get email templates.
 *
 * @since 1.4.0
 *
 * @return array An associative array containing the email type and the email template data.
 */
function rendez_vous_get_emails() {

	return apply_filters( 'rendez_vous_get_emails', [
		'rendez-vous-new-item'       => [
			'description'  => __( 'A member invited members to a new Rendez Vous', 'rendez-vous' ),
			'term_id'      => 0,
			'post_title'   => __( '[{{{site.name}}}] {{organizer.name}} invited you to a new Rendez Vous', 'rendez-vous' ),
			'post_content' => __( "{{organizer.name}} is scheduling a new Rendez Vous: {{{rendezvous.content}}}\n\nTo help him fix the date, please log in and visit: <a href=\"{{{rendezvous.url}}}\">{{rendezvous.title}}</a>.", 'rendez-vous' ),
			'post_excerpt' => __( "{{organizer.name}} is scheduling a new Rendez Vous: {{rendezvous.content}}\n\nTo help him fix the date, please log in and visit: \n\n{{{rendezvous.url}}}.", 'rendez-vous' ),
		],
		'rendez-vous-item-edited'    => [
			'description'  => __( 'A member updated a Rendez Vous', 'rendez-vous' ),
			'term_id'      => 0,
			'post_title'   => __( '[{{{site.name}}}] {{organizer.name}} updated a Rendez Vous', 'rendez-vous' ),
			'post_content' => __( "{{organizer.name}} {{rendezvous.action}} for the Rendez Vous: {{{rendezvous.content}}}\n\nTo view details, log in and visit: <a href=\"{{{rendezvous.url}}}\">{{rendezvous.title}}</a>.", 'rendez-vous' ),
			'post_excerpt' => __( "{{organizer.name}} {{rendezvous.action}} for the Rendez Vous: {{rendezvous.content}}\n\nTo view details, log in and visit: \n\n{{{rendezvous.url}}}.", 'rendez-vous' ),
		],
		'rendez-vous-preference-set' => [
			'description'  => __( 'A member selected date(s) for a Rendez Vous', 'rendez-vous' ),
			'term_id'      => 0,
			'post_title'   => __( '[{{{site.name}}}] {{attendee.name}} selected date(s) for a Rendez Vous', 'rendez-vous' ),
			'post_content' => __( "{{attendee.name}} set their preferences for the Rendez Vous: {{{rendezvous.content}}}\n\nTo view details, log in and visit: <a href=\"{{{rendezvous.url}}}\">{{rendezvous.title}}</a>.", 'rendez-vous' ),
			'post_excerpt' => __( "{{attendee.name}} set their preferences for the Rendez Vous: {{rendezvous.content}}\n\nTo view details, log in and visit: \n\n{{{rendezvous.url}}}.", 'rendez-vous' ),
		],
	] );

}

/**
 * Install/Reinstall email templates for the plugin's notifications.
 *
 * @since 1.4.0
 */
function rendez_vous_install_emails() {

	$switched = false;

	// Switch to the root blog, where the email posts live.
	if ( ! bp_is_root_blog() ) {
		switch_to_blog( bp_get_root_blog_id() );
		$switched = true;
	}

	// Get Emails.
	$email_types = rendez_vous_get_emails();

	// Set email types.
	foreach ( $email_types as $email_term => $term_args ) {

		if ( term_exists( $email_term, bp_get_email_tax_type() ) ) {
			$email_type = get_term_by( 'slug', $email_term, bp_get_email_tax_type() );

			$email_types[ $email_term ]['term_id'] = $email_type->term_id;
		} else {
			$term = wp_insert_term( $email_term, bp_get_email_tax_type(), [
				'description' => $term_args['description'],
			] );

			$email_types[ $email_term ]['term_id'] = $term['term_id'];
		}

		// Insert Email templates if needed.
		if ( ! empty( $email_types[ $email_term ]['term_id'] ) && ! is_a( bp_get_email( $email_term ), 'BP_Email' ) ) {
			wp_insert_post( [
				'post_status'  => 'publish',
				'post_type'    => bp_get_email_post_type(),
				'post_title'   => $email_types[ $email_term ]['post_title'],
				'post_content' => $email_types[ $email_term ]['post_content'],
				'post_excerpt' => $email_types[ $email_term ]['post_excerpt'],
				'tax_input'    => [
					bp_get_email_tax_type() => [ $email_types[ $email_term ]['term_id'] ],
				],
			] );
		}

	}

	if ( $switched ) {
		restore_current_blog();
	}

}

add_action( 'bp_core_install_emails', 'rendez_vous_install_emails' );
