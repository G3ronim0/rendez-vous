<?php
/**
 * Rendez Vous Editor class.
 *
 * Editor class used to create the Rendez Vous.
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
			'component'   => 'rendez_vous',
			'status'      => 'public',
			'btn_caption' => __( 'New Rendez Vous', 'rendez-vous' ),
			'btn_class'   => 'btn-rendez-vous',
			'action'      => 'rendez_vous_create',
			'group_id'    => null,
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
