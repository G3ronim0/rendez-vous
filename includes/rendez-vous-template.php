<?php
/**
 * Rendez Vous Template.
 *
 * Template functions.
 *
 * @package Rendez_Vous
 * @subpackage Template
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Type Filter.
 *
 * @since 1.0.0
 */
function render_vous_type_filter() {

	if ( ! rendez_vous_has_types() ) {
		return;
	}

	$selected_type = '';

	if ( ! empty( $_REQUEST['type'] ) ) {
		$selected_type = sanitize_title( wp_unslash( $_REQUEST['type'] ) );
	}

	?>

	<form id="rendez-vous-types-filter-form" action="">

		<select name="type">
			<option value="">---</option>
			<?php foreach ( rendez_vous()->types as $type ) : ?>
				<option value="<?php echo esc_attr( $type->slug ); ?>" <?php selected( $selected_type, $type->slug ); ?>><?php echo esc_attr( $type->name ); ?></option>
			<?php endforeach; ?>
		</select>

		<input type="submit" value="<?php esc_attr_e( 'Filter', 'rendez-vous' ); ?>"/>

	</form>
	<?php

}

/** Main Loop *****************************************************************/

/**
 * The main Rendez Vous template loop class.
 *
 * @since 1.0.0
 */
class Rendez_Vous_Template {

	/**
	 * The loop iterator.
	 *
	 * @access public
	 * @var int
	 */
	public $current_rendez_vous = -1;

	/**
	 * The number of Rendez Vous returned by the paged query.
	 *
	 * @access public
	 * @var int
	 */
	public $current_rendez_vous_count;

	/**
	 * Total number of Rendez Vous matching the query.
	 *
	 * @access public
	 * @var int
	 */
	public $total_rendez_vous_count;

	/**
	 * Array of Rendez Vous located by the query.
	 *
	 * @access public
	 * @var array
	 */
	public $rendez_vouss;

	/**
	 * The Rendez Vou object currently being iterated on.
	 *
	 * @access public
	 * @var object
	 */
	public $rendez_vous;

	/**
	 * A flag for whether the loop is currently being iterated.
	 *
	 * @access public
	 * @var bool
	 */
	public $in_the_loop;

	/**
	 * Array of item IDs to filter on.
	 *
	 * @access public
	 * @var array
	 */
	public $item_ids;

	/**
	 * Component slug.
	 *
	 * @access public
	 * @var string
	 */
	public $component;

	/**
	 * Include private Rendez Vous?
	 *
	 * @access public
	 * @var bool
	 */
	public $show_private;

	/**
	 * The ID of the User to whom the displayed Rendez Vous belong.
	 *
	 * @access public
	 * @var int
	 */
	public $user_id;

	/**
	 * The page number being requested.
	 *
	 * @access public
	 * @var int
	 */
	public $pag_page;

	/**
	 * The number of items to display per page of results.
	 *
	 * @access public
	 * @var int
	 */
	public $pag_num;

	/**
	 * An HTML string containing pagination links.
	 *
	 * @access public
	 * @var string
	 */
	public $pag_links;

	/**
	 * A string to match against.
	 *
	 * @access public
	 * @var string
	 */
	public $search_terms;

	/**
	 * Comma separated list of Rendez Vous IDs or array.
	 *
	 * @access public
	 * @var array|string
	 */
	public $exclude;

	/**
	 * A database column to order the results by.
	 *
	 * @access public
	 * @var string
	 */
	public $order_by;

	/**
	 * The direction to sort the results - ASC or DESC.
	 *
	 * @access public
	 * @var string
	 */
	public $sort_order;

	/**
	 * The type to filter the results with.
	 *
	 * @access public
	 * @var string
	 */
	public $type;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args The arguments.
	 */
	public function __construct( $args = [] ) {

		$defaults = [
			'attendees' => [], // One or more Attendee IDs.
			'organizer' => false,   // The Organizer ID of the Rendez Vous.
			'per_page'  => 20,
			'page'      => 1,
			'search'    => false,
			'exclude'   => false,   // Comma separated list or array of Rendez Vous IDs.
			'orderby'   => 'modified',
			'order'     => 'DESC',
			'page_arg'  => 'rpage',
			'group_id'  => false,
			'type'      => '',
			'no_cache'  => false,
		];

		// Parse arguments.
		$r = bp_parse_args( $args, $defaults, 'rendez_vous_template_args' );

		// Set which pagination page.
		if ( isset( $_GET[ $r['page_arg'] ] ) ) {
			$pag_page = intval( $_GET[ $r['page_arg'] ] );
		} else {
			$pag_page = $r['page'];
		}

		// Setup variables.
		$this->pag_page     = $pag_page;
		$this->pag_num      = $r['per_page'];
		$this->attendees    = $r['attendees'];
		$this->organizer    = (int) $r['organizer'];
		$this->search_terms = $r['search'];
		$this->exclude      = $r['exclude'];
		$this->page_arg     = $r['page_arg'];
		$this->order_by     = $r['orderby'];
		$this->sort_order   = $r['order'];
		$this->group_id     = $r['group_id'];
		$this->type         = $r['type'];
		$this->no_cache     = $r['no_cache'];

		// Get the Rendez Vous.
		$rendez_vouss = rendez_vous_get_items( [
			'attendees' => $this->attendees,
			'organizer' => $this->organizer,
			'per_page'  => $this->pag_num,
			'page'      => $this->pag_page,
			'search'    => $this->search_terms,
			'exclude'   => $this->exclude,
			'orderby'   => $this->order_by,
			'order'     => $this->sort_order,
			'group_id'  => $this->group_id,
			'type'      => $this->type,
			'no_cache'  => $this->no_cache,
		] );

		// Setup the Rendez Vous to loop through.
		$this->rendez_vouss            = $rendez_vouss['rendez_vous_items'];
		$this->total_rendez_vous_count = $rendez_vouss['total'];

		if ( empty( $this->rendez_vouss ) ) {
			$this->rendez_vous_count       = 0;
			$this->total_rendez_vous_count = 0;
		} else {
			$this->rendez_vous_count = count( $this->rendez_vouss );
		}

		if ( (int) $this->total_rendez_vous_count && (int) $this->pag_num ) {
			$add_args = [];

			if ( ! empty( $this->type ) ) {
				$add_args['type'] = $this->type;
			}

			$this->pag_links = paginate_links( [
				'base'      => esc_url( add_query_arg( $this->page_arg, '%#%' ) ),
				'format'    => '',
				'total'     => ceil( (int) $this->total_rendez_vous_count / (int) $this->pag_num ),
				'current'   => $this->pag_page,
				'prev_text' => _x( '&larr;', 'Rendez Vous pagination previous text', 'rendez-vous' ),
				'next_text' => _x( '&rarr;', 'Rendez Vous pagination next text', 'rendez-vous' ),
				'mid_size'  => 1,
				'add_args'  => $add_args,
			] );

			// Remove first page from pagination.
			if ( ! empty( $this->pag_links ) ) {
				$this->pag_links = str_replace( '?' . $r['page_arg'] . '=1', '', $this->pag_links );
				$this->pag_links = str_replace( '&#038;' . $r['page_arg'] . '=1', '', $this->pag_links );
			}
		}

	}

	/**
	 * Whether there are Rendez Vous available in the loop.
	 *
	 * @since 1.0.0
	 *
	 * @see rendez_vous_has_rendez_vouss()
	 *
	 * @return bool True if there are items in the loop, otherwise false.
	 */
	public function has_rendez_vouss() {

		if ( $this->rendez_vous_count ) {
			return true;
		}

		return false;

	}

	/**
	 * Set up the next Rendez Vous and iterate index.
	 *
	 * @since 1.0.0
	 *
	 * @return object The next Rendez Vous to iterate over.
	 */
	public function next_rendez_vous() {

		$this->current_rendez_vous++;

		$this->rendez_vous = $this->rendez_vouss[ $this->current_rendez_vous ];

		return $this->rendez_vous;

	}

	/**
	 * Rewind the Rendez Vous and reset Rendez Vous index.
	 *
	 * @since 1.0.0
	 */
	public function rewind_rendez_vouss() {

		$this->current_rendez_vous = -1;

		if ( $this->rendez_vous_count > 0 ) {
			$this->rendez_vous = $this->rendez_vouss[0];
		}

	}

	/**
	 * Whether there are Rendez Vous left in the loop to iterate over.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if there are more Rendez Vous to show, otherwise false.
	 */
	public function rendez_vouss() {

		if ( $this->current_rendez_vous + 1 < $this->rendez_vous_count ) {
			return true;

		} elseif ( $this->current_rendez_vous + 1 == $this->rendez_vous_count ) {
			do_action( 'rendez_vouss_loop_end' );

			$this->rewind_rendez_vouss();
		}

		$this->in_the_loop = false;

		return false;

	}

	/**
	 * Set up the current Rendez Vous inside the loop.
	 *
	 * @since 1.0.0
	 */
	public function the_rendez_vous() {

		$this->in_the_loop = true;
		$this->rendez_vous = $this->next_rendez_vous();

		// Loop has just started.
		if ( 0 === $this->current_rendez_vous ) {
			do_action( 'rendez_vouss_loop_start' );
		}

	}

}

/** The Loop ******************************************************************/

/**
 * Initialize the Rendez Vous loop.
 *
 * @since 1.0.0
 *
 * @param array $args The arguments.
 * @return bool $has_rendez_vouss True if there are Rendez Vous to display, false otherwise.
 */
function rendez_vous_has_rendez_vouss( $args = [] ) {

	// Init vars.
	$organizer = false;
	$attendees = [];
	$type      = '';

	// Get the User ID.
	if ( bp_is_user() ) {
		if ( bp_is_current_action( 'schedule' ) ) {
			$organizer = bp_displayed_user_id();
		} elseif ( bp_is_current_action( 'attend' ) ) {
			$attendee_id = bp_is_my_profile() ? bp_loggedin_user_id() : bp_displayed_user_id();
			$attendees   = [ $attendee_id ];
		}

		if ( bp_is_current_component( rendez_vous()->get_component_slug() ) && ! empty( $_REQUEST['type'] ) ) {
			$type = sanitize_title( wp_unslash( $_REQUEST['type'] ) );
		}
	}

	if ( bp_is_group() && bp_is_current_action( rendez_vous()->get_component_slug() ) && ! empty( $_REQUEST['type'] ) ) {
		$type = sanitize_title( wp_unslash( $_REQUEST['type'] ) );
	}

	// Parse the args.
	$r = bp_parse_args( $args, [
		'attendees' => $attendees, // One or more Attendee IDs.
		'organizer' => $organizer,   // The Organizer ID of the Rendez Vous.
		'per_page'  => 20,
		'page'      => 1,
		'search'    => isset( $_REQUEST['s'] ) ? wp_unslash( $_REQUEST['s'] ) : '',
		'exclude'   => false,   // Comma separated list or array of Rendez Vous IDs.
		'orderby'   => 'modified',
		'order'     => 'DESC',
		'page_arg'  => 'rpage',
		'type'      => $type,
	], 'rendez_vouss_has_args' );

	// Get the Rendez Vous.
	$query_loop = new Rendez_Vous_Template( $r );

	// Setup the global query loop.
	rendez_vous()->query_loop = $query_loop;

	return apply_filters( 'rendez_vous_has_rendez_vouss', $query_loop->has_rendez_vouss(), $query_loop );

}

/**
 * Get the Rendez Vous returned by the template loop.
 *
 * @since 1.0.0
 *
 * @return array List of Rendez Vous.
 */
function rendez_vous_the_rendez_vouss() {
	return rendez_vous()->query_loop->rendez_vouss();
}

/**
 * Get the current Rendez Vous object in the loop.
 *
 * @since 1.0.0
 *
 * @return object The current Rendez Vou within the loop.
 */
function rendez_vous_the_rendez_vous() {
	return rendez_vous()->query_loop->the_rendez_vous();
}

/** Loop Output ***************************************************************/

/**
 * Output the pagination count for the current Rendez Vous loop.
 *
 * @since 1.0.0
 */
function rendez_vous_pagination_count() {
	echo rendez_vous_get_pagination_count();
}

/**
 * Return the pagination count for the current Rendez Vous loop.
 *
 * @since 1.0.0
 *
 * @return string $pag The HTML for the pagination count.
 */
function rendez_vous_get_pagination_count() {

	$query_loop = rendez_vous()->query_loop;
	$start_num  = intval( ( $query_loop->pag_page - 1 ) * $query_loop->pag_num ) + 1;
	$from_num   = bp_core_number_format( $start_num );
	$to_num     = bp_core_number_format( ( $start_num + ( $query_loop->pag_num - 1 ) > $query_loop->total_rendez_vous_count ) ? $query_loop->total_rendez_vous_count : $start_num + ( $query_loop->pag_num - 1 ) );
	$total      = bp_core_number_format( $query_loop->total_rendez_vous_count );

	$pag = sprintf(
		/* translators: 1: The from number, 3: The to number, 3: The total number. */
		_n( 'Viewing %1$s to %2$s (of %3$s Rendez Vous)', 'Viewing %1$s to %2$s (of %3$s Rendez Vous)', $total, 'rendez-vous' ),
		$from_num,
		$to_num,
		$total
	);

	return apply_filters( 'rendez_vous_get_pagination_count', $pag );

}

/**
 * Output the pagination links for the current Rendez Vous loop.
 *
 * @since 1.0.0
 */
function rendez_vous_pagination_links() {
	echo rendez_vous_get_pagination_links();
}

/**
 * Return the pagination links for the current Rendez Vous loop.
 *
 * @since 1.0.0
 *
 * @return string HTML for the pagination links.
 */
function rendez_vous_get_pagination_links() {
	return apply_filters( 'rendez_vous_get_pagination_links', rendez_vous()->query_loop->pag_links );
}

/**
 * Output the ID of the Rendez Vous currently being iterated on.
 *
 * @since 1.0.0
 */
function rendez_vous_the_rendez_vous_id() {
	echo rendez_vous_get_the_rendez_vous_id();
}

/**
 * Return the ID of the Rendez Vous currently being iterated on.
 *
 * @since 1.0.0
 *
 * @return int ID of the current Rendez Vous.
 */
function rendez_vous_get_the_rendez_vous_id() {
	return apply_filters( 'rendez_vous_get_the_rendez_vous_id', rendez_vous()->query_loop->rendez_vous->ID );
}

/**
 * Output the class of the Rendez Vous row.
 *
 * @since 1.0.0
 */
function rendez_vous_class() {
	echo rendez_vous_get_class();
}

/**
 * Return the class of the Rendez Vous row.
 *
 * @since 1.0.0
 *
 * @return string $retval The class of the Rendez Vous row.
 */
function rendez_vous_get_class() {

	$rendez_vous = rendez_vous()->query_loop->rendez_vous;
	$classes     = [];

	// Rendez Vou status - inherit, private.
	$classes[] = esc_attr( $rendez_vous->post_status );

	$classes = apply_filters( 'rendez_vous_get_class', $classes );
	$classes = array_merge( $classes, [] );
	$retval  = 'class="' . join( ' ', $classes ) . '"';

	return $retval;

}

/**
 * Output the "avatar" of the Rendez Vous row.
 *
 * @since 1.0.0
 */
function rendez_vous_avatar() {
	echo rendez_vous_get_avatar();
}

/**
 * Return the "avatar" of the Rendez Vous row.
 *
 * @since 1.0.0
 *
 * @return string $output The "avatar" of the Rendez Vous row.
 */
function rendez_vous_get_avatar() {

	$output = '<div class="rendez-vous-avatar icon-' . rendez_vous()->query_loop->rendez_vous->post_status . '"></div>';

	return apply_filters( 'rendez_vous_get_avatar', $output, rendez_vous()->query_loop->rendez_vous->ID );

}

/**
 * Output the title of the Rendez Vous.
 *
 * @since 1.0.0
 */
function rendez_vous_the_title() {
	echo rendez_vous_get_the_title();
}

/**
 * Return the title of the Rendez Vous.
 *
 * @since 1.0.0
 *
 * @return string $post_title The title of the Rendez Vous.
 */
function rendez_vous_get_the_title() {
	return apply_filters( 'rendez_vous_get_the_title', rendez_vous()->query_loop->rendez_vous->post_title );
}

/**
 * Output the link of the Rendez Vous.
 *
 * @since 1.0.0
 */
function rendez_vous_the_link() {
	echo esc_url( rendez_vous_get_the_link() );
}

/**
 * Return the link of the Rendez Vous.
 *
 * @since 1.0.0
 *
 * @return string $link The link of the Rendez Vous.
 */
function rendez_vous_get_the_link() {

	$user_can = true;
	$link     = rendez_vous_get_single_link( rendez_vous()->query_loop->rendez_vous->ID, rendez_vous()->query_loop->rendez_vous->post_author );

	switch ( rendez_vous()->query_loop->rendez_vous->post_status ) {
		case 'private':
			$user_can = current_user_can( 'read_private_rendez_vouss', rendez_vous_get_the_rendez_vous_id() );
			break;

		case 'draft':
			$user_can = current_user_can( 'edit_rendez_vous', rendez_vous_get_the_rendez_vous_id() );
			$link     = rendez_vous_get_edit_link( rendez_vous()->query_loop->rendez_vous->ID, rendez_vous()->query_loop->rendez_vous->post_author );
			break;
	}

	if ( empty( $user_can ) ) {
		return '#noaccess';
	}

	return apply_filters( 'rendez_vous_get_the_link', $link );

}

/**
 * Output the date of the Rendez Vous.
 *
 * @since 1.0.0
 */
function rendez_vous_last_modified() {
	echo rendez_vous_get_last_modified();
}

/**
 * Return the date of the Rendez Vous.
 *
 * @since 1.0.0
 *
 * @return str $last_modified The date of the Rendez Vous.
 */
function rendez_vous_get_last_modified() {

	$last_modified = sprintf(
		/* translators: %s: The date of the Rendez Vous. */
		__( 'Modified %s', 'rendez-vous' ),
		bp_core_time_since( rendez_vous()->query_loop->rendez_vous->post_modified_gmt )
	);

	return apply_filters( 'rendez_vous_get_last_modified', $last_modified );

}

/**
 * Output the time till Rendez Vous happens.
 *
 * @since 1.4.0
 */
function rendez_vous_time_to() {
	echo rendez_vous_get_time_to();
}

/**
 * Return the time until the Rendez Vous happens.
 *
 * @since 1.4.0
 *
 * @return str $time_to The time until the Rendez Vous.
 */
function rendez_vous_get_time_to() {

	add_filter( 'bp_core_time_since_ago_text', 'rendez_vous_set_time_to_text', 10, 1 );

	$time_to = bp_core_time_since( bp_core_current_time( false ), get_post_meta( rendez_vous()->query_loop->rendez_vous->ID, '_rendez_vous_defdate', true ) );

	remove_filter( 'bp_core_time_since_ago_text', 'rendez_vous_set_time_to_text', 10, 1 );

	/* translators: %s: The time until the Rendez Vous. */
	$time_to = sprintf( __( 'starts in %s', 'rendez-vous' ), $time_to );

	return apply_filters( 'rendez_vous_get_time_to', $time_to );

}

/**
 * Remove the 'ago' part of the BuddyPress human time diff function.
 *
 * @since 1.4.0
 *
 * @param str $time_since_text The time since text.
 * @return str The modified time since text.
 */
function rendez_vous_set_time_to_text( $time_since_text = '' ) {

	// TODO: Fix this.
	/* translators: %s: The time to wait until the Rendez Vous. */
	return _x( '%s', 'Used to output the time to wait till the Rendez Vous', 'rendez-vous' );

}

/**
 * Check whether the Rendez Vous has a description.
 *
 * @since 1.0.0
 *
 * @return bool $user_can Whether the Rendez Vous has a description.
 */
function rendez_vous_has_description() {

	$user_can = ! empty( rendez_vous()->query_loop->rendez_vous->post_excerpt );

	switch ( rendez_vous()->query_loop->rendez_vous->post_status ) {
		case 'private':
			$user_can = current_user_can( 'read_private_rendez_vouss', rendez_vous_get_the_rendez_vous_id() );
			break;

		case 'draft':
			$user_can = current_user_can( 'edit_rendez_vouss', rendez_vous_get_the_rendez_vous_id() );
			break;
	}

	return $user_can;

}

/**
 * Output the Rendez Vous type.
 *
 * @since 1.4.0
 */
function rendez_vous_the_type() {
	echo rendez_vous_get_the_type();
}

add_action( 'rendez_vous_after_item_description', 'rendez_vous_the_type' );

/**
 * Gets the Rendez Vous type.
 *
 * @since 1.4.0
 *
 * @return str $output The type of Rendez Vous.
 */
function rendez_vous_get_the_type() {

	if ( ! rendez_vous_has_types() ) {
		return false;
	}

	$types = rendez_vous_get_type( rendez_vous_get_the_rendez_vous_id() );

	if ( empty( $types ) ) {
		return false;
	}

	$type_names = wp_list_pluck( $types, 'name' );
	$type_name  = array_pop( $type_names );

	$type_slugs = wp_list_pluck( $types, 'slug' );
	$type_slug  = array_pop( $type_slugs );

	$output = sprintf( '<div class="item-desc"><a href="?type=%s" title="%s" class="rendez-vous-type">%s</a></div>',
		esc_attr( $type_slug ),
		esc_attr__( 'Filter Rendez Vous having this type', 'rendez-vous' ),
		esc_html( $type_name )
	);

	return apply_filters( 'rendez_vous_get_the_type', $output, $type_name, $type_slug );

}

/**
 * Output the description of the Rendez Vous.
 *
 * @since 1.0.0
 */
function rendez_vous_the_excerpt() {
	echo rendez_vous_get_the_excerpt();
}

/**
 * Return the description of the Rendez Vous.
 *
 * @since 1.0.0
 *
 * @return str $excerpt The description of the Rendez Vous.
 */
function rendez_vous_get_the_excerpt() {

	$excerpt = bp_create_excerpt( rendez_vous()->query_loop->rendez_vous->post_excerpt );

	return apply_filters( 'rendez_vous_get_the_excerpt', $excerpt );

}

/**
 * Output the status (draft/private/public) of the Rendez Vous.
 *
 * @since 1.0.0
 */
function rendez_vous_the_status() {
	echo rendez_vous_get_the_status();
}

/**
 * Return the status of the Rendez Vous.
 *
 * @since 1.0.0
 *
 * @return str $status The status of the Rendez Vous.
 */
function rendez_vous_get_the_status() {

	$status      = __( 'All members', 'rendez-vous' );
	$rendez_vous = rendez_vous()->query_loop->rendez_vous;

	if ( 'private' == $rendez_vous->post_status ) {
		$status = __( 'Restricted', 'rendez-vous' );
	} elseif ( 'draft' == $rendez_vous->post_status ) {
		$status = __( 'Draft', 'rendez-vous' );
	}

	return apply_filters( 'rendez_vous_get_the_status', $status, $rendez_vous->ID, $rendez_vous->post_status );

}

/**
 * Output the User's action for the Rendez Vous.
 *
 * @since 1.0.0
 */
function rendez_vous_the_user_actions() {
	echo rendez_vous_get_the_user_actions();
}

add_action( 'rendez_vous_schedule_actions', 'rendez_vous_the_user_actions' );
add_action( 'rendez_vous_attend_actions', 'rendez_vous_the_user_actions' );

/**
 * Return the User's action for the Rendez Vous.
 *
 * @since 1.0.0
 *
 * @return str $view The User's action for the Rendez Vous.
 */
function rendez_vous_get_the_user_actions() {

	$rendez_vous_id = rendez_vous()->query_loop->rendez_vous->ID;
	$user_id        = rendez_vous()->query_loop->rendez_vous->post_author;

	$edit = false;
	$view = false;

	$status = rendez_vous()->query_loop->rendez_vous->post_status;

	if ( 'draft' != $status ) {

		$user_can = 'private' == $status ? current_user_can( 'read_private_rendez_vouss', $rendez_vous_id ) : current_user_can( 'read' );

		if ( ! empty( $user_can ) ) {
			$view_link = rendez_vous_get_single_link( $rendez_vous_id, $user_id );
			$view      = '<a href="' . esc_url( $view_link ) . '" class="button view-rendez-vous bp-primary-action" id="view-rendez-vous-' . $rendez_vous_id . ' ">' . _x( 'View', 'Rendez Vous view link', 'rendez-vous' ) . '</a>';
		}
	}

	$current_action = apply_filters( 'rendez_vous_current_action', bp_current_action() );

	if ( current_user_can( 'edit_rendez_vous', $rendez_vous_id ) && 'schedule' == $current_action ) {
		$edit_link = rendez_vous_get_edit_link( $rendez_vous_id, $user_id );
		$edit      = '<a href="' . esc_url( $edit_link ) . '" class="button edit-rendez-vous bp-primary-action" id="edit-rendez-vous-' . $rendez_vous_id . ' ">' . _x( 'Edit', 'Rendez Vous edit link', 'rendez-vous' ) . '</a>';
	}

	// Filter and return the HTML button.
	return apply_filters( 'rendez_vous_get_the_user_actions', $view . $edit, $view, $edit );

}

/** Single Output ***************************************************************/

/**
 * Output the edit form action for the Rendez Vous.
 *
 * @since 1.0.0
 *
 * @return str $action The edit form action for the Rendez Vous.
 */
function rendez_vous_single_the_form_action() {

	$action = trailingslashit( bp_core_get_user_domain( rendez_vous()->item->organizer ) . buddypress()->rendez_vous->slug . '/schedule' );

	return apply_filters( 'rendez_vous_single_the_form_action', $action, rendez_vous()->item );

}

/**
 * Output the edit form class for the Rendez Vous.
 *
 * @since 1.3.4
 *
 * @return str $class The edit form class for the Rendez Vous.
 */
function rendez_vous_single_the_form_class() {

	// Init classes.
	$classes = [];

	// Does this Rendez Vous have a term?
	if ( isset( rendez_vous()->item->type ) && is_array( rendez_vous()->item->type ) ) {
		foreach ( rendez_vous()->item->type as $type ) {
			$classes[] = 'rendez-vous-' . $type->slug;
			$classes[] = 'rendez-vous-' . $type->term_id;
		}
	}

	// Crunch them.
	$class = implode( ' ', $classes );

	return apply_filters( 'rendez_vous_single_the_form_class', $class, rendez_vous()->item );

}

/**
 * Output the ID of the Rendez Vous.
 *
 * @since 1.0.0
 */
function rendez_vous_single_the_id() {
	echo rendez_vous_single_get_the_id();
}

/**
 * Return the ID of the Rendez Vous.
 *
 * @since 1.0.0
 *
 * @return int $id The ID of the Rendez Vous.
 */
function rendez_vous_single_get_the_id() {
	return apply_filters( 'rendez_vous_single_get_the_id', rendez_vous()->item->id );
}

/**
 * Output the title of the Rendez Vous.
 *
 * @since 1.0.0
 */
function rendez_vous_single_the_title() {
	echo rendez_vous_single_get_the_title();
}

/**
 * Return the title of the Rendez Vous.
 *
 * @since 1.0.0
 *
 * @return str $title The title of the Rendez Vous.
 */
function rendez_vous_single_get_the_title() {
	return apply_filters( 'rendez_vous_single_get_the_title', rendez_vous()->item->title );
}

/**
 * Output the permalink of the Rendez Vous.
 *
 * @since 1.0.0
 */
function rendez_vous_single_permalink() {
	echo rendez_vous_single_get_permalink();
}

/**
 * Gets the permalink of the Rendez Vous.
 *
 * @since 1.0.0
 *
 * @return str $link The permalink of the Rendez Vous.
 */
function rendez_vous_single_get_permalink() {

	$id        = rendez_vous_single_get_the_id();
	$organizer = rendez_vous()->item->organizer;
	$link      = rendez_vous_get_single_link( $id, $organizer );

	return apply_filters( 'rendez_vous_single_get_permalink', $link, $id, $organizer );

}

/**
 * Output the edit link of the Rendez Vous.
 *
 * @since 1.0.0
 */
function rendez_vous_single_edit_link() {
	echo rendez_vous_single_get_edit_link();
}

/**
 * Gets the edit link of the Rendez Vous.
 *
 * @since 1.0.0
 *
 * @return str $link The edit link of the Rendez Vous.
 */
function rendez_vous_single_get_edit_link() {

	$id        = rendez_vous_single_get_the_id();
	$organizer = rendez_vous()->item->organizer;
	$link      = rendez_vous_get_edit_link( $id, $organizer );

	return apply_filters( 'rendez_vous_single_get_edit_link', $link, $id, $organizer );

}

/**
 * Output the description of the Rendez Vous.
 *
 * @since 1.0.0
 */
function rendez_vous_single_the_description() {
	echo rendez_vous_single_get_the_description();
}

/**
 * Return the description of the Rendez Vous.
 *
 * @since 1.0.0
 *
 * @return str $description The description of the Rendez Vous.
 */
function rendez_vous_single_get_the_description() {

	$screen = ! empty( rendez_vous()->screens->screen ) ? rendez_vous()->screens->screen : 'single';

	return apply_filters( "rendez_vous_{$screen}_get_the_description", rendez_vous()->item->description );

}

/**
 * Output the venue of the Rendez Vous.
 *
 * @since 1.0.0
 */
function rendez_vous_single_the_venue() {
	echo rendez_vous_single_get_the_venue();
}

/**
 * Return the venue of the Rendez Vous.
 *
 * @since 1.0.0
 *
 * @return str $venue The venue of the Rendez Vous.
 */
function rendez_vous_single_get_the_venue() {
	return apply_filters( 'rendez_vous_single_get_the_venue', rendez_vous()->item->venue );
}

/**
 * Check if the current Rendez Vous has a type.
 *
 * @since 1.2.0
 *
 * @return bool Whether the current Rendez Vous has a type or not.
 */
function rendez_vous_single_has_type() {
	return (bool) apply_filters( 'rendez_vous_single_has_type', rendez_vous_has_types( rendez_vous()->item ), rendez_vous()->item );
}

/**
 * Output the type of the Rendez Vous.
 *
 * @since 1.2.0
 */
function rendez_vous_single_the_type() {
	echo rendez_vous_single_get_the_type();
}

/**
 * Return the type of the Rendez Vous.
 *
 * @since 1.2.0
 *
 * @return str $type The type of the Rendez Vous.
 */
function rendez_vous_single_get_the_type() {

	$type = '';
	if ( ! empty( rendez_vous()->item->type ) ) {
		$types = wp_list_pluck( rendez_vous()->item->type, 'name' );
		$type  = array_pop( $types );
	}

	return apply_filters( 'rendez_vous_single_get_the_type', $type, rendez_vous()->item->type );

}

/**
 * Output the selectbox to choose type for the Rendez Vous.
 *
 * @since 1.2.0
 */
function rendez_vous_single_edit_the_type() {
	echo rendez_vous_single_edit_get_the_type();
}

/**
 * Return the selectbox to choose type for the Rendez Vous.
 *
 * @since 1.2.0
 *
 * @return str $output The selectbox to choose type for the Rendez Vous.
 */
function rendez_vous_single_edit_get_the_type() {

	$rdv = rendez_vous();

	if ( empty( $rdv->types ) ) {
		$types      = rendez_vous_get_terms( [ 'hide_empty' => false ] );
		$rdv->types = $types;
	} else {
		$types = $rdv->types;
	}

	$output = '<select name="_rendez_vous_edit[type]"><option value="">---</option>';

	$selected_type = 0;
	if ( ! empty( rendez_vous()->item->type ) ) {
		$selected_types = wp_list_pluck( rendez_vous()->item->type, 'term_id' );
		$selected_type  = array_pop( $selected_types );
	}

	foreach ( $types as $type ) {
		$output .= '<option value="' . intval( $type->term_id ) . '" ' . selected( $type->term_id, $selected_type, false ) . '>' . esc_attr( $type->name ) . '</option>';
	}

	$output .= '</select>';

	return apply_filters( 'rendez_vous_single_edit_get_the_type', $output, $selected_type, $types, rendez_vous()->item );

}

/**
 * Output the duration of the Rendez Vous.
 *
 * @since 1.0.0
 */
function rendez_vous_single_the_duration() {
	echo rendez_vous_single_get_the_duration();
}

/**
 * Return the duration of the Rendez Vous.
 *
 * @since 1.0.0
 *
 * @return str $duration The duration of the Rendez Vous.
 */
function rendez_vous_single_get_the_duration() {
	return apply_filters( 'rendez_vous_single_get_the_duration', rendez_vous()->item->duration );
}

/**
 * Output the privacy of the Rendez Vous.
 *
 * @since 1.0.0
 */
function rendez_vous_single_the_privacy() {
	echo rendez_vous_single_get_the_privacy();
}

/**
 * Gets the published status of the Rendez Vous.
 *
 * @since 1.0.0
 *
 * @return str $status The Rendez Vous status.
 */
function rendez_vous_single_is_published() {
	return 'draft' != rendez_vous()->item->status;
}

/**
 * Gets the privacy of the Rendez Vous.
 *
 * @since 1.0.0
 *
 * @return str $retval The Rendez Vous privacy status.
 */
function rendez_vous_single_get_privacy() {

	$privacy = 'draft' == rendez_vous()->item->status ? rendez_vous()->item->privacy : rendez_vous()->item->status;

	$retval = 0;
	if ( in_array( $privacy, [ 1, 'private' ] ) ) {
		$retval = 1;
	}

	return apply_filters( 'rendez_vous_single_get_privacy', $retval );

}

/**
 * Gets the privacy of the Rendez Vous.
 *
 * Weird wrapper for "rendez_vous_single_get_privacy()".
 *
 * @since 1.0.0
 *
 * @return str $privacy The Rendez Vous privacy status.
 */
function rendez_vous_single_get_the_privacy() {

	$privacy = rendez_vous_single_get_privacy();

	return apply_filters( 'rendez_vous_single_get_the_privacy', checked( 1, $privacy, false ) );

}

/**
 * Output the Users prefs for the Rendez Vous.
 *
 * @since 1.0.0
 *
 * @param str $view The type of view.
 */
function rendez_vous_single_the_dates( $view = 'single' ) {
	echo rendez_vous_single_get_the_dates( $view );
}

/**
 * Gets the Users prefs for the Rendez Vous.
 *
 * @since 1.0.0
 *
 * @param str $view The type of view.
 * @return str $output The output.
 */
function rendez_vous_single_get_the_dates( $view = 'single' ) {

	// First add Organizer.
	$all_attendees = (array) rendez_vous()->item->attendees;

	if ( ! in_array( rendez_vous()->item->organizer, $all_attendees ) ) {
		$all_attendees = array_merge( [ rendez_vous()->item->organizer ], $all_attendees );
	}

	// Then remove current_user as we want him to be in last position.
	if ( 'edit' != $view ) {
		if ( ! rendez_vous_single_date_set() && bp_loggedin_user_id() ) {
			$attendees = array_diff( $all_attendees, [ bp_loggedin_user_id() ] );
		} else {
			$attendees = $all_attendees;
		}
	} else {
		$attendees = $all_attendees;
	}

	$days = rendez_vous()->item->days;

	if ( empty( $days ) ) {
		return false;
	}

	ksort( $days );
	$header = array_keys( $days );

	$output  = '<table id="rendez-vous-attendees-prefs">';
	$output .= '<thead>';
	$output .= '<tr><th>&nbsp;</th>';

	foreach ( $header as $date ) {
		$output .= '<th class="rendez-vous-date">';

		// Init col header.
		$col_header = '';

		if ( is_long( $date ) ) {
			$col_header .= '<div class="date">' . date_i18n( get_option( 'date_format' ), $date ) . '</div>';
			$col_header .= '<div class="time">' . date_i18n( get_option( 'time_format' ), $date ) . '</div>';
		} else {
			$col_header .= '<div class="none">' . esc_html__( 'None', 'rendez-vous' ) . '</div>';
		}

		/**
		 * Filter the date header to allow overrides.
		 *
		 * What we really want is to insert the event title here.
		 *
		 * @since 1.4.3
		 *
		 * @param str $col_header The HTML for the column header.
		 * @param str $date The UNIX timestamp.
		 * @return str $col_header The HTML for the column header.
		 */
		$output .= apply_filters( 'rendez_vous_single_get_the_dates_header', $col_header, $date );

		$output .= '</th>';
	}

	$output .= '</tr></thead>';
	$output .= '<tbody>';

	// Rows.
	foreach ( $attendees as $attendee ) {
		$user_link = trailingslashit( bp_core_get_user_domain( $attendee ) );
		$user_name = bp_core_get_user_displayname( $attendee );
		$tr_class  = $attendee == bp_loggedin_user_id() ? 'edited' : false;

		$output .= '<tr class="' . $tr_class . '"><td>';

		if ( 'edit' == $view ) {
			// Make sure the Organizer is not removed from Attendees.
			if ( $attendee == rendez_vous()->item->organizer ) {
				$output .= '<input type="hidden" name="_rendez_vous_edit[attendees][]" value="' . $attendee . '"/>';
			} else {
				$output .= '<input type="checkbox" name="_rendez_vous_edit[attendees][]" value="' . $attendee . '" checked="true"/>&nbsp;';
			}
		}

		$output .= '<a href="' . esc_url( $user_link ) . '" title="' . esc_attr( $user_name ) . '">' . bp_core_fetch_avatar(
			[
				'object'  => 'user',
				'item_id' => $attendee,
				'type'    => 'thumb',
				'class'   => 'mini',
				'width'   => 20,
				'height'  => 20,
			]
		) . ' ' . $user_name . '</a></td>';

		foreach ( $header as $date ) {
			$class = in_array( $attendee, $days[ $date ] ) ? 'active' : 'inactive';
			if ( 'none' == $date ) {
				$class .= ' impossible';
			}
			$output .= '<td class="' . $class . '">&nbsp;</td>';
		}
		$output .= '</tr>';
	}

	$ending_rows = [
		'total' => '<td>' . esc_html__( 'Total', 'rendez-vous' ) . '</td>',
	];

	if ( 'edit' != $view ) {
		$ending_rows['editable_row'] = '<td><a href="' . esc_url( bp_loggedin_user_domain() ) . '" title="' . esc_attr( bp_get_loggedin_user_username() ) . '">' . bp_core_fetch_avatar(
			[
				'object'  => 'user',
				'item_id' => bp_loggedin_user_id(),
				'type'    => 'thumb',
				'class'   => 'mini',
				'width'   => 20,
				'height'  => 20,
			]
		) . ' ' . esc_html( bp_get_loggedin_user_fullname() ) . '</a></td>';
	// Set definitive date.
	} else {
		$ending_rows['editable_row'] = '<td id="rendez-vous-set">' . esc_html__( 'Set date', 'rendez-vous' ) . '</td>';
	}

	foreach ( $header as $date ) {
		$checked               = checked( true, in_array( bp_loggedin_user_id(), $days[ $date ] ), false );
		$ending_rows['total'] .= '<td><strong>' . count( $days[ $date ] ) . '</strong></td>';

		// Let the User set his prefs.
		if ( 'edit' != $view ) {
			$class = false;

			if ( 'none' == $date ) {
				$class = ' class="none-resets-cb"';
			}

			$ending_rows['editable_row'] .= '<td><input type="checkbox" name="_rendez_vous_prefs[days][' . bp_loggedin_user_id() . '][]" value="' . $date . '" ' . $checked . $class . '/></td>';
			// Let the Organizer choose the definitive date.
		} else {
			$def_date = ! empty( rendez_vous()->item->def_date ) ? rendez_vous()->item->def_date : false;

			if ( 'none' != $date ) {
				$ending_rows['editable_row'] .= '<td><input type="radio" name="_rendez_vous_edit[def_date]" value="' . $date . '" ' . checked( $date, $def_date, false ) . '/></td>';
			} else {
				$ending_rows['editable_row'] .= '<td></td>';
			}
		}
	}

	if ( 'edit' != $view ) {
		// Date is set, changes cannot be done anymore.
		if ( ! rendez_vous_single_date_set() ) {
			if ( 'private' == rendez_vous()->item->privacy ) {
				// If private, display the row only if current User is an Attendee or the Author.
				if ( bp_loggedin_user_id() == rendez_vous()->item->organizer || in_array( bp_loggedin_user_id(), $all_attendees ) ) {
					$output .= '<tr class="edited">' . $ending_rows['editable_row'] . '</tr>';
				}

			} else {
				if ( current_user_can( 'subscribe_rendez_vous' ) ) {
					$output .= '<tr class="edited">' . $ending_rows['editable_row'] . '</tr>';
				}
			}
			// Display totals.
			$output .= '<tr>' . $ending_rows['total'] . '</tr>';
		}
	} else {
		// Display totals.
		$output .= '<tr>' . $ending_rows['total'] . '</tr>';
		// Display the radio to set the date.
		if ( 'draft' != rendez_vous()->item->status ) {
			$output .= '<tr>' . $ending_rows['editable_row'] . '</tr>';
		}
	}

	/**
	 * Allow extra rows to be added to the table.
	 *
	 * @since 1.4.3
	 *
	 * @param str $output The output as it currently stands.
	 * @param array $header The array of dates.
	 * @param str $view The output mode ('view' or 'edit').
	 * @return str $output The modified output.
	 */
	$output = apply_filters( 'rendez_vous_single_get_the_dates_rows_after', $output, $header, $view );

	$output .= '</tbody>';
	$output .= '</table>';

	if ( ! is_user_logged_in() && 'publish' == rendez_vous()->item->status && ! rendez_vous_single_date_set() ) {
		$output .= '<div id="message" class="info"><p>' . __( 'If you want to set your preferences for this Rendez Vous, please log in.', 'rendez-vous' ) . '</p></div>';
	}

	return apply_filters( 'rendez_vous_single_get_the_dates', $output, $view );

}

/**
 * A report may be created for the Rendez Vous.
 *
 * @since 1.0.0
 *
 * @return bool $can_report True if a report may be created for the Rendez Vous.
 */
function rendez_vous_single_can_report() {

	if ( empty( rendez_vous()->item->def_date ) ) {
		return false;
	}

	if ( rendez_vous()->item->def_date > strtotime( current_time( 'mysql' ) ) ) {
		return false;
	}

	return true;

}

/**
 * Output the report editor for the Rendez Vous.
 *
 * @since 1.0.0
 */
function rendez_vous_single_edit_report() {

	// Add some filters, inspired by bbPress.
	add_filter( 'tiny_mce_plugins', 'rendez_vous_tiny_mce_plugins' );
	add_filter( 'teeny_mce_plugins', 'rendez_vous_tiny_mce_plugins' );
	add_filter( 'teeny_mce_buttons', 'rendez_vous_teeny_mce_buttons' );
	add_filter( 'quicktags_settings', 'rendez_vous_quicktags_settings' );

	wp_editor( rendez_vous()->item->report, 'rendez-vous-edit-report', [
		'textarea_name' => '_rendez_vous_edit[report]',
		'media_buttons' => false,
		'textarea_rows' => 12,
		'tinymce'       => apply_filters( 'rendez_vous_single_edit_report_tinymce', false ),
		'teeny'         => true,
		'quicktags'     => true,
		'dfw'           => false,
	] );

	// Remove the filters, inspired by bbPress.
	remove_filter( 'tiny_mce_plugins', 'rendez_vous_tiny_mce_plugins' );
	remove_filter( 'teeny_mce_plugins', 'rendez_vous_tiny_mce_plugins' );
	remove_filter( 'teeny_mce_buttons', 'rendez_vous_teeny_mce_buttons' );
	remove_filter( 'quicktags_settings', 'rendez_vous_quicktags_settings' );

}

/**
 * Report for the Rendez Vous exists.
 *
 * @since 1.0.0
 *
 * @return bool $report True if a report for the Rendez Vous exists.
 */
function rendez_vous_single_has_report() {
	return ! empty( rendez_vous()->item->report );
}

/**
 * Output the report of the Rendez Vous.
 *
 * @since 1.0.0
 */
function rendez_vous_single_the_report() {
	echo rendez_vous_single_get_the_report();
}

/**
 * Gets the report of the Rendez Vous.
 *
 * @since 1.0.0
 *
 * @return str $report The report.
 */
function rendez_vous_single_get_the_report() {
	return apply_filters( 'rendez_vous_single_get_the_report', rendez_vous()->item->report );
}

/**
 * Is the date of the Rendez Vous set?
 *
 * @since 1.0.0
 *
 * @return bool $def_date True if the date of the Rendez Vous is set.
 */
function rendez_vous_single_date_set() {
	return ! empty( rendez_vous()->item->def_date );
}

/**
 * Output the date of the Rendez Vous.
 *
 * @since 1.0.0
 */
function rendez_vous_single_the_date() {
	echo rendez_vous_single_get_the_date();
}

/**
 * Gets the date of the Rendez Vous.
 *
 * @since 1.0.0
 *
 * @return str $output The date.
 */
function rendez_vous_single_get_the_date() {

	$date_set = rendez_vous()->item->def_date;
	if ( empty( $date_set ) ) {
		return false;
	}

	if ( ! is_numeric( $date_set ) ) {
		return esc_html( $date_set );
	}

	$date = '<span class="date" data-timestamp="' . $date_set . '">' . date_i18n( get_option( 'date_format' ), $date_set ) . '</span>';
	$time = '<span class="time" data-timestamp="' . $date_set . '">' . date_i18n( get_option( 'time_format' ), $date_set ) . '</span>';

	/* translators: 1: The date, 2: The time. */
	$output = sprintf( __( '%1$s at %2$s', 'rendez-vous' ), $date, $time );

	return apply_filters( 'rendez_vous_single_get_the_date', $output, rendez_vous()->item );

}

/**
 * Output the action for the Rendez Vous.
 *
 * @since 1.0.0
 *
 * @param str $view The type of view.
 */
function rendez_vous_single_the_action( $view = 'single' ) {
	echo rendez_vous_single_get_the_action( $view );
}

/**
 * Gets the action of the Rendez Vous.
 *
 * @since 1.0.0
 *
 * @param str $view The type of view.
 * @return str $action The action.
 */
function rendez_vous_single_get_the_action( $view = 'single' ) {

	$action = 'choose';
	if ( 'edit' == $view ) {
		$action = 'update';
		if ( 'draft' == rendez_vous()->item->status ) {
			$action = 'publish';
		}
	}

	return apply_filters( 'rendez_vous_single_get_the_action', $action, $view );

}

/**
 * Output the submits for the Rendez Vous.
 *
 * @since 1.0.0
 *
 * @param str $view The type of view.
 */
function rendez_vous_single_the_submit( $view = 'single' ) {

	if ( ! bp_loggedin_user_id() ) {
		return;
	}

	if ( 'edit' == $view ) {

		$caption = 'draft' == rendez_vous()->item->status ? __( 'Publish Rendez Vous', 'rendez-vous' ) : __( 'Edit Rendez Vous', 'rendez-vous' );

		if ( current_user_can( 'delete_rendez_vous', rendez_vous()->item->id ) ) :
			$delete_link = rendez_vous_get_delete_link( rendez_vous()->item->id, rendez_vous()->item->organizer );
			if ( ! empty( $delete_link ) ) :
				?>
				<a href="<?php echo esc_url( $delete_link ); ?>" class="button delete-rendez-vous bp-secondary-action" id="delete-rendez-vous-<?php echo rendez_vous()->item->id; ?>"><?php esc_html_e( 'Cancel Rendez Vous', 'rendez-vous' ); ?></a>
				<?php
			endif;
		endif;

		if ( current_user_can( 'edit_rendez_vous', rendez_vous()->item->id ) ) :
			?>
			<input type="submit" name="_rendez_vous_edit[submit]" id="rendez-vous-edit-submit" value="<?php echo esc_attr( $caption ); ?>" class="bp-primary-action"/>
			<?php
		endif;

	} elseif ( current_user_can( 'subscribe_rendez_vous' ) ) {

		if ( 'publish' != rendez_vous()->item->status && ! in_array( bp_loggedin_user_id(), rendez_vous()->item->attendees ) && bp_loggedin_user_id() != rendez_vous()->item->organizer ) {
			return;
		}

		?>
		<input type="submit" name="_rendez_vous_prefs[submit]" id="rendez-vous-prefs-submit" value="<?php echo esc_attr( __( 'Save preferences', 'rendez-vous' ) ); ?>" class="bp-primary-action"/>
		<?php

		if ( 'edit' != $view && current_user_can( 'edit_rendez_vous', rendez_vous()->item->id ) && empty( rendez_vous()->item->def_date ) ) {
			?>
			<a href="<?php echo esc_url( rendez_vous_get_edit_link( rendez_vous()->item->id, rendez_vous()->item->organizer ) ); ?>#rendez-vous-set" class="button bp-secondary-action last"><?php esc_html_e( 'Set the date', 'rendez-vous' ); ?></a>
			<div class="clear"></div>
			<?php
		}

	}

}
