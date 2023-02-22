<?php
/**
 * Rendez Vous Editor.
 *
 * Editor functions.
 *
 * @package Rendez_Vous
 * @subpackage Editor
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueues the Rendez Vous editor scripts, css, settings and strings.
 *
 * Inspired by wp_enqueue_media()
 *
 * @since 1.0.0
 *
 * @param array $args The arguments.
 */
function rendez_vous_enqueue_editor( $args = [] ) {

	// Enqueue me just once per page, please.
	if ( did_action( 'rendez_vous_enqueue_editor' ) ) {
		return;
	}

	$defaults = [
		'post'     => null,
		'user_id'  => bp_loggedin_user_id(),
		'callback' => null,
		'group_id' => null,
	];

	$args = wp_parse_args( $args, $defaults );

	/*
	 * We're going to pass the old thickbox media tabs to `media_upload_tabs`
	 * to ensure plugins will work. We will then unset those tabs.
	 */
	$tabs = [
		// Handler action suffix => tab label.
		'type'     => '',
		'type_url' => '',
		'gallery'  => '',
		'library'  => '',
	];

	$tabs = apply_filters( 'media_upload_tabs', $tabs );
	unset( $tabs['type'], $tabs['type_url'], $tabs['gallery'], $tabs['library'] );

	$props = [
		'link'  => bp_get_option( 'image_default_link_type' ), // DB default is 'file'.
		'align' => bp_get_option( 'image_default_align' ), // Empty default.
		'size'  => bp_get_option( 'image_default_size' ),  // Empty default.
	];

	$settings = [
		'tabs'         => $tabs,
		'tabUrl'       => esc_url( add_query_arg( [ 'chromeless' => true ], admin_url( 'admin-ajax.php' ) ) ),
		'mimeTypes'    => false,
		'captions'     => ! apply_filters( 'disable_captions', '' ),
		'nonce'        => [
			'sendToEditor' => wp_create_nonce( 'media-send-to-editor' ),
			'rendezvous'   => wp_create_nonce( 'rendez-vous-editor' ),
		],
		'post'         => [
			'id' => 0,
		],
		'defaultProps' => $props,
		'embedExts'    => false,
	];

	$post                 = null;
	$hier                 = null;
	$settings['user']     = intval( $args['user_id'] );
	$settings['group_id'] = intval( $args['group_id'] );

	if ( ! empty( $args['callback'] ) ) {
		$settings['callback'] = esc_url( $args['callback'] );
	}

	// Do we have Member types?
	$rendez_vous_member_types = [];
	$member_types             = bp_get_member_types( [], 'objects' );
	if ( ! empty( $member_types ) && is_array( $member_types ) ) {
		$rendez_vous_member_types['rdvMemberTypesAll'] = esc_html__( 'All member types', 'rendez-vous' );
		foreach ( $member_types as $type_key => $type ) {
			$rendez_vous_member_types['rdvMemberTypes'][] = [
				'type' => $type_key,
				'text' => esc_html( $type->labels['singular_name'] ),
			];
		}
	}

	if ( ! empty( $rendez_vous_member_types ) ) {
		$settings = array_merge( $settings, $rendez_vous_member_types );
	}

	$strings = [

		// Generic.
		'url'                   => __( 'URL', 'rendez-vous' ),
		'addMedia'              => __( 'Add Media', 'rendez-vous' ),
		'search'                => __( 'Search', 'rendez-vous' ),
		'select'                => __( 'Select', 'rendez-vous' ),
		'cancel'                => __( 'Cancel', 'rendez-vous' ),

		/*
		 * translators: This is a would-be plural string used in the media manager.
		 * If there is not a word you can use in your language to avoid issues with the
		 * lack of plural support here, turn it into "selected: %d" then translate it.
		 */
		'selected'              => __( '%d selected', 'rendez-vous' ),
		'dragInfo'              => __( 'Drag and drop to reorder images.', 'rendez-vous' ),

		// Upload.
		'uploadFilesTitle'      => __( 'Upload Files', 'rendez-vous' ),
		'uploadImagesTitle'     => __( 'Upload Images', 'rendez-vous' ),

		// Library.
		'mediaLibraryTitle'     => __( 'Media Library', 'rendez-vous' ),
		'insertMediaTitle'      => __( 'Insert Media', 'rendez-vous' ),
		'createNewGallery'      => __( 'Create a new gallery', 'rendez-vous' ),
		'returnToLibrary'       => __( '&#8592; Return to library', 'rendez-vous' ),
		'allMediaItems'         => __( 'All media items', 'rendez-vous' ),
		'noItemsFound'          => __( 'No items found.', 'rendez-vous' ),
		'insertIntoPost'        => $hier ? __( 'Insert into page', 'rendez-vous' ) : __( 'Insert into post', 'rendez-vous' ),
		'uploadedToThisPost'    => $hier ? __( 'Uploaded to this page', 'rendez-vous' ) : __( 'Uploaded to this post', 'rendez-vous' ),
		'warnDelete'            => __( "You are about to permanently delete this item.\n  'Cancel' to stop, 'OK' to delete.", 'rendez-vous' ),

		// From URL.
		'insertFromUrlTitle'    => __( 'Insert from URL', 'rendez-vous' ),

		// Featured Images.
		'setFeaturedImageTitle' => __( 'Set Featured Image', 'rendez-vous' ),
		'setFeaturedImage'      => __( 'Set featured image', 'rendez-vous' ),

		// Gallery.
		'createGalleryTitle'    => __( 'Create Gallery', 'rendez-vous' ),
		'editGalleryTitle'      => __( 'Edit Gallery', 'rendez-vous' ),
		'cancelGalleryTitle'    => __( '&#8592; Cancel Gallery', 'rendez-vous' ),
		'insertGallery'         => __( 'Insert gallery', 'rendez-vous' ),
		'updateGallery'         => __( 'Update gallery', 'rendez-vous' ),
		'addToGallery'          => __( 'Add to gallery', 'rendez-vous' ),
		'addToGalleryTitle'     => __( 'Add to Gallery', 'rendez-vous' ),
		'reverseOrder'          => __( 'Reverse order', 'rendez-vous' ),
	];

	$rendez_vous_strings = apply_filters( 'rendez_vous_view_strings', [
		// Rendez Vous.
		'rdvMainTitle'      => _x( 'Rendez Vous', 'Rendez Vous editor main title', 'rendez-vous' ),
		'whatTab'           => _x( 'What?', 'Rendez Vous editor tab what name', 'rendez-vous' ),
		'whenTab'           => _x( 'When?', 'Rendez Vous editor tab when name', 'rendez-vous' ),
		'whoTab'            => _x( 'Who?', 'Rendez Vous editor tab who name', 'rendez-vous' ),
		'rdvInsertBtn'      => __( 'Add to invites', 'rendez-vous' ),
		'rdvNextBtn'        => __( 'Next', 'rendez-vous' ),
		'rdvPrevBtn'        => __( 'Prev', 'rendez-vous' ),
		'rdvSrcPlaceHolder' => __( 'Search', 'rendez-vous' ),
		/* translators: %d: The number of Attendees to invite. */
		'invited'           => __( '%d to invite', 'rendez-vous' ),
		'removeInviteBtn'   => __( 'Remove Invite', 'rendez-vous' ),
		'saveButton'        => __( 'Save Rendez Vous', 'rendez-vous' ),
	] );

	// Use the filter at your own risk.
	$rendez_vous_fields = [
		'what' => apply_filters( 'rendez_vous_editor_core_fields', [
			[
				'id'          => 'title',
				'order'       => 0,
				'type'        => 'text',
				'placeholder' => esc_html__( 'What is this about?', 'rendez-vous' ),
				'label'       => esc_html__( 'Title', 'rendez-vous' ),
				'value'       => '',
				'tab'         => 'what',
				'class'       => 'required',
			],
			[
				'id'          => 'venue',
				'order'       => 10,
				'type'        => 'text',
				'placeholder' => esc_html__( 'Where?', 'rendez-vous' ),
				'label'       => esc_html__( 'Venue', 'rendez-vous' ),
				'value'       => '',
				'tab'         => 'what',
				'class'       => '',
			],
			[
				'id'          => 'description',
				'order'       => 20,
				'type'        => 'textarea',
				'placeholder' => esc_html__( 'Some details about this rendez-vous?', 'rendez-vous' ),
				'label'       => esc_html__( 'Description', 'rendez-vous' ),
				'value'       => '',
				'tab'         => 'what',
				'class'       => '',
			],
			[
				'id'          => 'duration',
				'order'       => 30,
				'type'        => 'duree',
				'placeholder' => '00:00',
				'label'       => esc_html__( 'Duration', 'rendez-vous' ),
				'value'       => '',
				'tab'         => 'what',
				'class'       => 'required',
			],
			[
				'id'          => 'privacy',
				'order'       => 40,
				'type'        => 'checkbox',
				'placeholder' => esc_html__( 'Restrict to the selected members of the Who? tab', 'rendez-vous' ),
				'label'       => esc_html__( 'Access', 'rendez-vous' ),
				'value'       => '0',
				'tab'         => 'what',
				'class'       => '',
			],
			[
				'id'          => 'utcoffset',
				'order'       => 50,
				'type'        => 'timezone',
				'placeholder' => '',
				'label'       => '',
				'value'       => '',
				'tab'         => 'what',
				'class'       => '',
			],
		] ),
	];

	// Do we have rendez-vous types?
	if ( rendez_vous_has_types() ) {
		$rendez_vous_types_choices     = [];
		$rendez_vous_types_placeholder = [];

		foreach ( rendez_vous()->types as $rendez_vous_type ) {
			$rendez_vous_types_choices[]     = $rendez_vous_type->term_id;
			$rendez_vous_types_placeholder[] = $rendez_vous_type->name;
		}

		// Set the rendez-voys types field arg.
		$rendez_vous_types_args = [
			'id'          => 'type',
			'order'       => 15,
			'type'        => 'selectbox',
			'placeholder' => $rendez_vous_types_placeholder,
			'label'       => esc_html__( 'Type', 'rendez-vous' ),
			'value'       => '',
			'tab'         => 'what',
			'class'       => '',
			'choices'     => $rendez_vous_types_choices,
		];

		// Merge with other rendez-vous fields.
		$rendez_vous_fields['what'] = array_merge( $rendez_vous_fields['what'], [ $rendez_vous_types_args ] );
	}

	/*
	 * Use 'rendez_vous_editor_extra_fields' to add custom fields, you should be able
	 * to save them using the 'rendez_vous_after_saved' action.
	 */
	$rendez_vous_extra_fields = apply_filters( 'rendez_vous_editor_extra_fields', [] );
	$rendez_vous_add_fields   = [];

	if ( ! empty( $rendez_vous_extra_fields ) && is_array( $rendez_vous_extra_fields ) ) {
		// Some IDs are restricted to the plugin usage.
		$restricted = [
			'title'       => true,
			'venue'       => true,
			'type'        => true,
			'description' => true,
			'duration'    => true,
			'privacy'     => true,
			'utcoffset'   => true,
		];

		foreach ( $rendez_vous_extra_fields as $rendez_vous_extra_field ) {
			// The ID is required and some IDs are restricted.
			if ( empty( $rendez_vous_extra_field['id'] ) || ! empty( $restricted[ $rendez_vous_extra_field['id'] ] ) ) {
				continue;
			}

			// Make sure all needed arguments have default values.
			$rendez_vous_add_fields[] = wp_parse_args( $rendez_vous_extra_field, [
				'id'          => '',
				'order'       => 60,
				'type'        => 'text',
				'placeholder' => '',
				'label'       => '',
				'value'       => '',
				'tab'         => 'what',
				'class'       => '',
			] );
		}
	}

	if ( ! empty( $rendez_vous_add_fields ) ) {
		$rendez_vous_fields['what'] = array_merge( $rendez_vous_fields['what'], $rendez_vous_add_fields );
	}

	// Sort by the order key.
	$rendez_vous_fields['what'] = bp_sort_by_key( $rendez_vous_fields['what'], 'order', 'num' );

	$rendez_vous_date_strings = [
		'daynames'    => [
			esc_html__( 'Sunday', 'rendez-vous' ),
			esc_html__( 'Monday', 'rendez-vous' ),
			esc_html__( 'Tuesday', 'rendez-vous' ),
			esc_html__( 'Wednesday', 'rendez-vous' ),
			esc_html__( 'Thursday', 'rendez-vous' ),
			esc_html__( 'Friday', 'rendez-vous' ),
			esc_html__( 'Saturday', 'rendez-vous' ),
		],
		'daynamesmin' => [
			esc_html__( 'Su', 'rendez-vous' ),
			esc_html__( 'Mo', 'rendez-vous' ),
			esc_html__( 'Tu', 'rendez-vous' ),
			esc_html__( 'We', 'rendez-vous' ),
			esc_html__( 'Th', 'rendez-vous' ),
			esc_html__( 'Fr', 'rendez-vous' ),
			esc_html__( 'Sa', 'rendez-vous' ),
		],
		'monthnames'  => [
			esc_html__( 'January', 'rendez-vous' ),
			esc_html__( 'February', 'rendez-vous' ),
			esc_html__( 'March', 'rendez-vous' ),
			esc_html__( 'April', 'rendez-vous' ),
			esc_html__( 'May', 'rendez-vous' ),
			esc_html__( 'June', 'rendez-vous' ),
			esc_html__( 'July', 'rendez-vous' ),
			esc_html__( 'August', 'rendez-vous' ),
			esc_html__( 'September', 'rendez-vous' ),
			esc_html__( 'October', 'rendez-vous' ),
			esc_html__( 'November', 'rendez-vous' ),
			esc_html__( 'December', 'rendez-vous' ),
		],
		'format'      => _x( 'mm/dd/yy', 'rendez-vous date format', 'rendez-vous' ),
		'firstday'    => intval( bp_get_option( 'start_of_week', 0 ) ),
		'alert'       => esc_html__( 'You already selected this date', 'rendez-vous' ),
	];

	$settings = apply_filters( 'media_view_settings', $settings, $post );
	$strings  = apply_filters( 'media_view_strings', $strings, $post );
	$strings  = array_merge( $strings, [
		'rendez_vous_strings'      => $rendez_vous_strings,
		'rendez_vous_fields'       => $rendez_vous_fields,
		'rendez_vous_date_strings' => $rendez_vous_date_strings,
	] );

	$strings['settings'] = $settings;

	wp_localize_script( 'rendez-vous-media-views', '_wpMediaViewsL10n', $strings );

	wp_enqueue_script( 'rendez-vous-modal' );
	wp_enqueue_style( 'rendez-vous-modal-style' );
	rendez_vous_plupload_settings();

	require_once ABSPATH . WPINC . '/media-template.php';
	add_action( 'admin_footer', 'wp_print_media_templates' );
	add_action( 'wp_footer', 'wp_print_media_templates' );

	do_action( 'rendez_vous_enqueue_editor' );

}

/**
 * Trick to make the media-views works without plupload loaded.
 *
 * @since 1.0.0
 *
 * @global $wp_scripts
 */
function rendez_vous_plupload_settings() {

	global $wp_scripts;

	$data = $wp_scripts->get_data( 'rendez-vous-plupload', 'data' );

	if ( $data && false !== strpos( $data, '_wpPluploadSettings' ) ) {
		return;
	}

	$settings = [
		'defaults'      => [],
		'browser'       => [
			'mobile'    => false,
			'supported' => false,
		],
		'limitExceeded' => false,
	];

	$script = 'var _wpPluploadSettings = ' . wp_json_encode( $settings ) . ';';

	if ( $data ) {
		$script = "$data\n$script";
	}

	$wp_scripts->add_data( 'rendez-vous-plupload', 'data', $script );

}


/**
 * The template needed for the Rendez Vous editor.
 *
 * @since 1.0.0
 */
function rendezvous_media_templates() {

	?>
	<script type="text/html" id="tmpl-what">
		<# if ( 'text' === data.type  ) { #>
			<p>
				<label for="{{data.id}}">{{data.label}}</label>
				<input type="text" id="{{data.id}}" placeholder="{{data.placeholder}}" value="{{data.value}}" class="rdv-input-what {{data.class}}"/>
			</p>
		<# } else if ( 'time' === data.type ) { #>
			<p>
				<label for="{{data.id}}">{{data.label}}</label>
				<input type="time" id="{{data.id}}" placeholder="{{data.placeholder}}" value="{{data.value}}" class="rdv-input-what {{data.class}}"/>
			</p>
		<# } else if ( 'duree' === data.type ) { #>
			<p>
				<label for="{{data.id}}">{{data.label}}</label>
				<input type="text" id="{{data.id}}" placeholder="{{data.placeholder}}" value="{{data.value}}" class="rdv-input-what duree {{data.class}}"/>
			</p>
		<# } else if ( 'checkbox' === data.type ) { #>
			<p>
				<label for="{{data.id}}">{{data.label}} </label>
				<input type="checkbox" id="{{data.id}}" value="1" class="rdv-check-what {{data.class}}" <# if ( data.value == 1 ) { #>checked<# } #>/> {{data.placeholder}}
			</p>
		<# } else if ( 'timezone' === data.type || 'hidden' === data.type ) { #>
				<input type="hidden" id="{{data.id}}" value="{{data.value}}" class="rdv-hidden-what"/>
		<# } else if ( 'textarea' === data.type ) { #>
			<p>
				<label for="{{data.id}}">{{data.label}}</label>
				<textarea id="{{data.id}}" placeholder="{{data.placeholder}}" class="rdv-input-what {{data.class}}">{{data.value}}</textarea>
			</p>

		<# } else if ( 'selectbox' === data.type ) { #>

			<# if ( typeof data.placeholder == 'object' && typeof data.choices == 'object' ) { #>

				<p>
					<label for="{{data.id}}">{{data.label}} </label>
					<select id="{{data.id}}" class="rdv-select-what">
						<option value="">---</option>
						<# for ( i in data.placeholder ) { #>
							<option value="{{data.choices[i]}}" <# if ( data.value == data.choices[i] ) { #>selected<# } #>>{{data.placeholder[i]}}</option>
						<# } #>
					</select>
				</p>

			<# } #>

		<# } else { #>
			<strong>Oops</strong>
		<# } #>
	</script>

	<script type="text/html" id="tmpl-when">
			<# if ( 1 === data.intro  ) { #>
				<div class="use-calendar">
					<h3 class="calendar-instructions"><?php esc_html_e( 'Use the calendar in the right sidebar to add dates', 'rendez-vous' ); ?></h3>
				</div>
			<# } else { #>
				<fieldset>
					<legend class="dayth">
						<a href="#" class="trashday"><span data-id="{{data.id}}"></span></a> <strong>{{data.day}}</strong>
					</legend>
					<div class="daytd">
						<label for="{{data.id}}-hour1"><?php esc_html_e( 'Define 1 to 3 hours for this day, please respect the format HH:MM', 'rendez-vous' ); ?></label>
						<input type="time" value="{{data.hour1}}" id="{{data.id}}-hour1" placeholder="00:00" class="rdv-input-when">&nbsp;
						<input type="time" value="{{data.hour2}}" id="{{data.id}}-hour2" placeholder="00:00" class="rdv-input-when">&nbsp;
						<input type="time" value="{{data.hour3}}" id="{{data.id}}-hour3" placeholder="00:00" class="rdv-input-when">&nbsp;
					</div>
				</fieldset>
			<# } #>
	</script>

	<script type="text/html" id="tmpl-rendez-vous">
			<# if ( 1 === data.notfound  ) { #>
				<div id="rendez-vous-error"><p><?php esc_html_e( 'No users found', 'rendez-vous' ); ?></p></div>
			<# } else { #>
				<div id="user-{{ data.id }}" class="attachment-preview user type-image" data-id="{{ data.id }}">
					<div class="thumbnail">
						<div class="avatar">
							<img src="{{data.avatar}}" draggable="false" />
						</div>
						<div class="displayname">
							<strong>{{data.name}}</strong>
						</div>
					</div>
				</div>
				<a id="user-check-{{ data.id }}" class="check" href="#" title="<?php esc_html_e( 'Deselect', 'rendez-vous' ); ?>" data-id="{{ data.id }}"><div class="media-modal-icon"></div></a>
			<# } #>
	</script>

	<script type="text/html" id="tmpl-user-selection">
		<div class="selection-info">
			<span class="count"></span>
			<# if ( data.clearable ) { #>
				<a class="clear-selection" href="#"><?php esc_html_e( 'Clear', 'rendez-vous' ); ?></a>
			<# } #>
		</div>
		<div class="selection-view">
			<ul></ul>
		</div>
	</script>
	<?php

}

add_action( 'print_media_templates', 'rendezvous_media_templates' );
