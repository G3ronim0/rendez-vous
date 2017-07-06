<?php
/**
 * Rendez Vous Parts.
 *
 * Template parts used in the plugin
 *
 * @package Rendez Vous
 * @subpackage Parts
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Schedule screen title
 *
 * @package Rendez Vous
 * @subpackage Parts
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_schedule_title() {
	?>
	<ul id="rendez-vous-nav">
		<li><?php rendez_vous_editor( 'new-rendez-vous' ); ?></li>
		<li class="last"><?php render_vous_type_filter(); ?></li>
	</ul>
	<?php
}

/**
 * Schedule screen content
 *
 * @package Rendez Vous
 * @subpackage Parts
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_schedule_content() {
	rendez_vous_loop();
}

/**
 * Attend screen title
 *
 * @package Rendez Vous
 * @subpackage Parts
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_attend_title() {
	?>
	<ul id="rendez-vous-nav">
		<li class="last"><?php render_vous_type_filter(); ?></li>
	</ul>
	<?php
}

/**
 * Attend screen content
 *
 * @package Rendez Vous
 * @subpackage Parts
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_attend_content() {
	rendez_vous_loop();
}

/**
 * Loop part
 *
 * @package Rendez Vous
 * @subpackage Parts
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_loop() {
	$current_action = apply_filters( 'rendez_vous_current_action', bp_current_action() );
	?>
	<div class="rendez-vous <?php echo esc_attr( $current_action );?>">

		<?php do_action( "rendez-vous_{$current_action}_loop" ); ?>

		<?php if ( rendez_vous_has_rendez_vouss() ) : ?>

			<div id="pag-top" class="pagination no-ajax">

				<div class="pag-count" id="rendez-vous-<?php echo esc_attr( $current_action );?>-count-top">

					<?php rendez_vous_pagination_count(); ?>

				</div>

				<div class="pagination-links" id="rendez-vous-<?php echo esc_attr( $current_action );?>-pag-top">

					<?php rendez_vous_pagination_links(); ?>

				</div>

			</div>

			<?php do_action( "rendez_vous_before_{$current_action}_list" ); ?>

			<ul id="rendez-vous-list" class="item-list" role="main">

			<?php while ( rendez_vous_the_rendez_vouss() ) : rendez_vous_the_rendez_vous(); ?>

				<li <?php rendez_vous_class(); ?>>
					<div class="item-avatar">
						<a href="<?php rendez_vous_the_link(); ?>" title="<?php echo esc_attr( rendez_vous_get_the_title() );?>"><?php rendez_vous_avatar(); ?></a>
					</div>

					<div class="item">
						<div class="item-title"><a href="<?php rendez_vous_the_link(); ?>" title="<?php echo esc_attr( rendez_vous_get_the_title() );?>"><?php rendez_vous_the_title(); ?></a></div>
						<div class="item-meta"><span class="activity"><?php rendez_vous_last_modified(); ?></span></div>

						<?php if ( rendez_vous_has_description() ) : ?>
							<div class="item-desc"><?php rendez_vous_the_excerpt(); ?></div>
						<?php endif ; ?>

						<?php do_action( "rendez_vous_{$current_action}_item" ); ?>

						<?php do_action( 'rendez_vous_after_item_description' ); ?>

					</div>

					<div class="action">

						<?php do_action( "rendez_vous_{$current_action}_actions" ); ?>

						<div class="meta">

							<?php rendez_vous_the_status(); ?>

						</div>

					</div>

					<div class="clear"></div>
				</li>

			<?php endwhile; ?>

			</ul>

			<?php do_action( "rendez_vous_after_{$current_action}_list" ); ?>

			<div id="pag-bottom" class="pagination no-ajax">

				<div class="pag-count" id="rendez-vous-<?php echo esc_attr( $current_action );?>-count-bottom">

					<?php rendez_vous_pagination_count(); ?>

				</div>

				<div class="pagination-links" id="rendez-vous-<?php echo esc_attr( $current_action );?>-pag-bottom">

					<?php rendez_vous_pagination_links(); ?>

				</div>

			</div>

		<?php else: ?>

			<div id="message" class="info">
				<p><?php _e( 'There were no rendez-vous found.', 'rendez-vous' ); ?></p>
			</div>

		<?php endif; ?>

		<?php do_action( "rendez_vous_after_{$current_action}_loop" ); ?>

	</div>
	<?php
}

/**
 * Edit screen title
 *
 * @package Rendez Vous
 * @subpackage Parts
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_edit_title() {
	esc_html_e( 'Editing: ', 'rendez-vous' );
	rendez_vous_single_the_title();

	if ( rendez_vous_single_is_published() ) {
		bp_button( array(
			'id'                => 'view-rendez-vous',
			'component'         => 'rendez_vous',
			'must_be_logged_in' => true,
			'block_self'        => false,
			'wrapper_id'        => 'rendez-vous-view-btn',
			'wrapper_class'     => 'right',
			'link_class'        => 'view-rendez-vous',
			'link_href'         => rendez_vous_single_get_permalink(),
			'link_title'        => __( 'View', 'rendez-vous'),
			'link_text'         => __( 'View', 'rendez-vous')
		) );
	}
}

/**
 * Edit screen content
 *
 * @package Rendez Vous
 * @subpackage Parts
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_edit_content() {
	?>
	<form action="<?php echo esc_url( rendez_vous_single_the_form_action() );?>" method="post" id="rendez-vous-edit-form" class="standard-form">
		<p>
			<label for="rendez-vous-edit-title"><?php esc_html_e( 'Title', 'rendez-vous' ); ?></label>
			<input type="text" name="_rendez_vous_edit[title]" id="rendez-vous-edit-title" value="<?php rendez_vous_single_the_title() ;?>"/>
		</p>
		<p>
			<label for="rendez-vous-edit-description"><?php esc_html_e( 'Description', 'rendez-vous' ); ?></label>
			<textarea name="_rendez_vous_edit[description]" id="rendez-vous-edit-description"><?php rendez_vous_single_the_description() ;?></textarea>
		</p>
		<p>
			<label for="rendez-vous-edit-venue"><?php esc_html_e( 'Venue', 'rendez-vous' ); ?></label>
			<input type="text" name="_rendez_vous_edit[venue]" id="rendez-vous-edit-venue" value="<?php rendez_vous_single_the_venue() ;?>"/>
		</p>

		<?php if ( rendez_vous_has_types() ) : ?>

			<label for="rendez-vous-single-type"><?php esc_html_e( 'Type', 'rendez-vous' ); ?></label>
			<div id="rendez-vous-single-type">

				<?php rendez_vous_single_edit_the_type() ;?>

			</div>

		<?php endif; ?>

		<p>
			<label for="rendez-vous-edit-duration"><?php esc_html_e( 'Duration', 'rendez-vous' ); ?></label>
			<input type="text" placeholder="00:00" name="_rendez_vous_edit[duration]" id="rendez-vous-edit-duration" value="<?php rendez_vous_single_the_duration() ;?>" class="rdv-duree"/>
		</p>
		<p>
			<label for="rendez-vous-edit-status"><?php esc_html_e( 'Restrict this rendez-vous to the selected attendees', 'rendez-vous' ); ?>
				<input type="checkbox" name="_rendez_vous_edit[privacy]" id="rendez-vous-edit-privacy" <?php rendez_vous_single_the_privacy();?> value="1">
			</label>
		</p>

		<?php do_action( 'rendez_vous_edit_form_before_dates' ) ;?>

		<hr/>

		<h4><?php esc_html_e( 'Attendees', 'rendez-vous' ); ?></h4>

		<?php rendez_vous_single_the_dates( 'edit' );?>

		<?php do_action( 'rendez_vous_edit_form_after_dates' ) ;?>

		<?php if ( rendez_vous_single_can_report() ) :?>

			<p>
				<label for="rendez-vous-edit-report"><?php esc_html_e( 'Notes / Report', 'rendez-vous' ); ?></label>
				<div class="rendez-vous-report-wrapper">
					<?php rendez_vous_single_edit_report() ;?>
				</div>
			</p>

		<?php endif ;?>

		<hr/>

		<p>
			<label for="rendez-vous-custom-message"><?php esc_html_e( 'Send a custom message to attendees (restricted to once per day).', 'rendez-vous');?></label>
			<textarea name="_rendez_vous_edit[message]" id="rendez-vous-custom-message"></textarea>
		</p>

		<input type="hidden" value="<?php rendez_vous_single_the_id();?>" name="_rendez_vous_edit[id]"/>
		<input type="hidden" value="<?php rendez_vous_single_the_action( 'edit' ) ;?>" name="_rendez_vous_edit[action]"/>
		<?php wp_nonce_field( 'rendez_vous_update' ); ?>

		<?php rendez_vous_single_the_submit( 'edit' );?>
	</form>
	<?php
}

/**
 * Single screen title
 *
 * @package Rendez Vous
 * @subpackage Parts
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_single_title() {
	rendez_vous_single_the_title();

	if ( current_user_can( 'edit_rendez_vous', rendez_vous_single_get_the_id() ) ) {
		bp_button( array(
			'id'                => 'edit-rendez-vous',
			'component'         => 'rendez_vous',
			'must_be_logged_in' => true,
			'block_self'        => false,
			'wrapper_id'        => 'rendez-vous-edit-btn',
			'wrapper_class'     => 'right',
			'link_class'        => 'edit-rendez-vous',
			'link_href'         => rendez_vous_single_get_edit_link(),
			'link_title'        => __( 'Edit', 'rendez-vous'),
			'link_text'         => __( 'Edit', 'rendez-vous')
		) );
	}
}

/**
 * Single screen content
 *
 * @package Rendez Vous
 * @subpackage Parts
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_single_content() {
	// Make sure embed url are processed
	add_filter( 'embed_post_id', 'rendez_vous_single_get_the_id' );

	/**
	 * Allow injection before form.
	 *
	 * @since 1.4.3
	 */
	do_action( 'rendez_vous_single_content_before' );

	?>
	<form action="<?php echo esc_url( rendez_vous_single_the_form_action() );?>" method="post" id="rendez-vous-single-form" class="standard-form <?php echo esc_attr( rendez_vous_single_the_form_class() );?>">

		<div class="rendez-vous-item-meta">

			<label for="rendez-vous-single-description"><?php esc_html_e( 'Description', 'rendez-vous' ); ?></label>
			<div id="rendez-vous-single-description"><?php rendez_vous_single_the_description() ;?></div>

			<label for="rendez-vous-single-venue"><?php esc_html_e( 'Venue', 'rendez-vous' ); ?></label>
			<div id="rendez-vous-single-venue"><?php rendez_vous_single_the_venue() ;?></div>

			<?php if ( rendez_vous_single_has_type() ) : ?>

				<label for="rendez-vous-single-type"><?php esc_html_e( 'Type', 'rendez-vous' ); ?></label>
				<div id="rendez-vous-single-type"><?php rendez_vous_single_the_type() ;?></div>

			<?php endif; ?>

			<?php if ( rendez_vous_single_date_set() ) :?>

				<label for="rendez-vous-single-date"><?php esc_html_e( 'Fixed to:', 'rendez-vous' ) ;?></label>
				<div id="rendez-vous-single-date"><?php rendez_vous_single_the_date() ;?></div>

			<?php endif ;?>

			<label for="rendez-vous-single-duration"><?php esc_html_e( 'Duration (hours)', 'rendez-vous' ); ?></label>
			<div id="rendez-vous-single-duration"><?php rendez_vous_single_the_duration() ;?></div>

		</div>

		<hr/>

		<h4><?php esc_html_e( 'Attendees', 'rendez-vous' ); ?></h4>

		<?php rendez_vous_single_the_dates( 'single' );?>

		<?php if ( rendez_vous_single_has_report() ) :?>

			<hr/>

			<label for="rendez-vous-single-report"><?php esc_html_e( 'Notes/Report', 'rendez-vous' ); ?></label>
			<div id="rendez-vous-single-report"><?php rendez_vous_single_the_report() ;?></div>

		<?php endif ;?>

		<input type="hidden" value="<?php rendez_vous_single_the_id();?>" name="_rendez_vous_prefs[id]"/>
		<input type="hidden" value="<?php rendez_vous_single_the_action( 'single' ) ;?>" name="_rendez_vous_prefs[action]"/>
		<?php wp_nonce_field( 'rendez_vous_prefs' ); ?>

		<?php if ( ! rendez_vous_single_date_set() ) rendez_vous_single_the_submit( 'single' ) ;?>
	</form>
	<?php

	/**
	 * Allow injection after form.
	 *
	 * @since 1.4.3
	 */
	do_action( 'rendez_vous_single_content_after' );

	// Stop processing embeds
	remove_filter( 'embed_post_id', 'rendez_vous_single_get_the_id' );
}
