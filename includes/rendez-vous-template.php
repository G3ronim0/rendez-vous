<?php
/**
 * Rendez Vous Template.
 *
 * Template functions
 *
 * @package Rendez Vous
 * @subpackage Template
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/** Type Filrer ***************************************************************/

function render_vous_type_filter() {
	if ( ! rendez_vous_has_types() ) {
		return;
	}

	$selected_type = '';

	if ( ! empty( $_REQUEST['type'] ) ) {
		$selected_type = sanitize_title( $_REQUEST['type'] );
	}
	?>

	<form id="rendez-vous-types-filter-form" action="">

		<select name="type">

			<option value="">---</option>

			<?php foreach ( rendez_vous()->types as $type ) : ?>

				<option value="<?php echo esc_attr( $type->slug ) ;?>" <?php selected( $selected_type, $type->slug );?>><?php echo esc_attr( $type->name ) ;?></option>

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
 * @package Rendez Vous
 * @subpackage Template
 *
 * @since Rendez Vous (1.0.0)
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
	 * Array of item ids to filter on.
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
	 * Include private Rendez Vous ?
	 *
	 * @access public
	 * @var bool
	 */
	public $show_private;

	/**
	 * The ID of the user to whom the displayed Rendez Vous belong.
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
	 * comma separated list of Rendez Vous ids or array.
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
	 * The direction to sort the results (ASC or DESC)
	 *
	 * @access public
	 * @var string
	 */
	public $sort_order;

	/**
	 * The type to filter the results with
	 *
	 * @access public
	 * @var string
	 */
	public $type;

	/**
	 * Constructor method.
	 *
	 * @since Rendez Vous (1.0.0)
	 */
	public function __construct( $args = array() ) {

		$defaults = array(
			'attendees' => array(), // one or more attendee ids
			'organizer'	=> false,   // the organize id of the rendez-vous
			'per_page'	=> 20,
			'page'		=> 1,
			'search'    => false,
			'exclude'	=> false,   // comma separated list or array of rendez-vous ids.
			'orderby' 	=> 'modified',
			'order'     => 'DESC',
			'page_arg'  => 'rpage',
			'group_id'  => false,
			'type'      => '',
			'no_cache'  => false,
		);

		// Parse arguments
		$r = bp_parse_args( $args, $defaults, 'rendez_vous_template_args' );

		// Set which pagination page
		if ( isset( $_GET[ $r['page_arg'] ] ) ) {
			$pag_page = intval( $_GET[ $r['page_arg'] ] );
		} else {
			$pag_page = $r['page'];
		}

		// Setup variables
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

		// Get the Rendez Vous
		$rendez_vouss      = rendez_vous_get_items( array(
			'attendees' => $this->attendees,
			'organizer'	=> $this->organizer,
			'per_page'	=> $this->pag_num,
			'page'		=> $this->pag_page,
			'search'    => $this->search_terms,
			'exclude'	=> $this->exclude,
			'orderby' 	=> $this->order_by,
			'order'     => $this->sort_order,
			'group_id'  => $this->group_id,
			'type'      => $this->type,
			'no_cache'  => $this->no_cache,
		) );

		// Setup the Rendez Vous to loop through
		$this->rendez_vouss            = $rendez_vouss['rendez_vous_items'];
		$this->total_rendez_vous_count = $rendez_vouss['total'];

		if ( empty( $this->rendez_vouss ) ) {
			$this->rendez_vous_count       = 0;
			$this->total_rendez_vous_count = 0;
		} else {
			$this->rendez_vous_count = count( $this->rendez_vouss );
		}

		if ( (int) $this->total_rendez_vous_count && (int) $this->pag_num ) {
			$add_args = array();

			if ( ! empty( $this->type ) ) {
				$add_args['type'] = $this->type;
			}

			$this->pag_links = paginate_links( array(
				'base'      => esc_url( add_query_arg( $this->page_arg, '%#%' ) ),
				'format'    => '',
				'total'     => ceil( (int) $this->total_rendez_vous_count / (int) $this->pag_num ),
				'current'   => $this->pag_page,
				'prev_text' => _x( '&larr;', 'rendez-vous pagination previous text', 'rendez-vous' ),
				'next_text' => _x( '&rarr;', 'rendez-vous pagination next text',     'rendez-vous' ),
				'mid_size'  => 1,
				'add_args'  => $add_args,
			) );

			// Remove first page from pagination
			$this->pag_links = str_replace( '?'      . $r['page_arg'] . '=1', '', $this->pag_links );
			$this->pag_links = str_replace( '&#038;' . $r['page_arg'] . '=1', '', $this->pag_links );
		}
	}

	/**
	 * Whether there are Rendez Vous available in the loop.
	 *
	 * @since Rendez Vous (1.0.0)
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
	 * @since Rendez Vous (1.0.0)
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
	 * @since Rendez Vous (1.0.0)
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
	 * @since Rendez Vous (1.0.0)
	 *
	 * @return bool True if there are more Rendez Vous to show,
	 *         otherwise false.
	 */
	public function rendez_vouss() {

		if ( $this->current_rendez_vous + 1 < $this->rendez_vous_count ) {
			return true;

		} elseif ( $this->current_rendez_vous + 1 == $this->rendez_vous_count ) {
			do_action( 'rendez_vouss_loop_end');

			$this->rewind_rendez_vouss();
		}

		$this->in_the_loop = false;
		return false;
	}

	/**
	 * Set up the current Rendez Vous inside the loop.
	 *
	 * @since Rendez Vous (1.0.0)
	 */
	public function the_rendez_vous() {
		$this->in_the_loop  = true;
		$this->rendez_vous = $this->next_rendez_vous();

		// loop has just started
		if ( 0 === $this->current_rendez_vous ) {
			do_action( 'rendez_vouss_loop_start' );
		}
	}
}

/** The Loop ******************************************************************/

/**
 * Initialize the Rendez Vous loop.
 *
 * @package Rendez Vous
 * @subpackage Template
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_has_rendez_vouss( $args = array() ) {
	// init vars
	$organizer = false;
	$attendees = array();
	$type = '';

	// Get the user ID
	if ( bp_is_user() ) {
		if( bp_is_current_action( 'schedule' ) ) {
			$organizer = bp_displayed_user_id();
		} else if ( bp_is_current_action( 'attend' ) ) {
			$attendee_id = bp_is_my_profile() ? bp_loggedin_user_id() : bp_displayed_user_id();
			$attendees = array( $attendee_id );
		}

		if ( bp_is_current_component( rendez_vous()->get_component_slug() ) && ! empty( $_REQUEST['type'] ) ) {
			$type = sanitize_title( $_REQUEST['type'] );
		}
	}

	if ( bp_is_group() && bp_is_current_action( rendez_vous()->get_component_slug() ) && ! empty( $_REQUEST['type'] ) ) {
		$type = sanitize_title( $_REQUEST['type'] );
	}

	// Parse the args
	$r = bp_parse_args( $args, array(
		'attendees' => $attendees, // one or more attendee ids
		'organizer'	=> $organizer,   // the organize id of the rendez-vous
		'per_page'	=> 20,
		'page'		=> 1,
		'search'    => isset( $_REQUEST['s'] ) ? stripslashes( $_REQUEST['s'] ) : '',
		'exclude'	=> false,   // comma separated list or array of rendez-vous ids.
		'orderby' 	=> 'modified',
		'order'     => 'DESC',
		'page_arg'  => 'rpage',
		'type'      => $type,
	), 'rendez_vouss_has_args' );

	// Get the Rendez Vous
	$query_loop = new Rendez_Vous_Template( $r );

	// Setup the global query loop
	rendez_vous()->query_loop = $query_loop;

	return apply_filters( 'rendez_vous_has_rendez_vouss', $query_loop->has_rendez_vouss(), $query_loop );
}

/**
 * Get the Rendez Vous returned by the template loop.
 *
 * @since Rendez Vous (1.0.0)
 *
 * @return array List of Rendez Vous.
 */
function rendez_vous_the_rendez_vouss() {
	return rendez_vous()->query_loop->rendez_vouss();
}

/**
 * Get the current Rendez Vous object in the loop.
 *
 * @since Rendez Vous (1.0.0)
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
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_pagination_count() {
	echo rendez_vous_get_pagination_count();
}
	/**
	 * Return the pagination count for the current Rendez Vous loop.
	 *
	 * @since Rendez Vous (1.0.0)
	 *
	 * @return string HTML for the pagination count.
	 */
	function rendez_vous_get_pagination_count() {
		$query_loop = rendez_vous()->query_loop;
		$start_num  = intval( ( $query_loop->pag_page - 1 ) * $query_loop->pag_num ) + 1;
		$from_num   = bp_core_number_format( $start_num );
		$to_num     = bp_core_number_format( ( $start_num + ( $query_loop->pag_num - 1 ) > $query_loop->total_rendez_vous_count ) ? $query_loop->total_rendez_vous_count : $start_num + ( $query_loop->pag_num - 1 ) );
		$total      = bp_core_number_format( $query_loop->total_rendez_vous_count );
		$pag        = sprintf( _n( 'Viewing %1$s to %2$s (of %3$s rendez-vous)', 'Viewing %1$s to %2$s (of %3$s rendez-vous)', $total, 'rendez-vous' ), $from_num, $to_num, $total );

		return apply_filters( 'rendez_vous_get_pagination_count', $pag );
	}

/**
 * Output the pagination links for the current Rendez Vous loop.
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_pagination_links() {
	echo rendez_vous_get_pagination_links();
}
	/**
	 * Return the pagination links for the current Rendez Vous loop.
	 *
	 * @since Rendez Vous (1.0.0)
	 *
	 * @return string HTML for the pagination links.
	 */
	function rendez_vous_get_pagination_links() {
		return apply_filters( 'rendez_vous_get_pagination_links', rendez_vous()->query_loop->pag_links );
	}

/**
 * Output the ID of the Rendez Vous currently being iterated on.
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_the_rendez_vous_id() {
	echo rendez_vous_get_the_rendez_vous_id();
}
	/**
	 * Return the ID of the Rendez Vous currently being iterated on.
	 *
	 * @since Rendez Vous (1.0.0)
	 *
	 * @return int ID of the current Rendez Vous.
	 */
	function rendez_vous_get_the_rendez_vous_id() {
		return apply_filters( 'rendez_vous_get_the_rendez_vous_id', rendez_vous()->query_loop->rendez_vous->ID );
	}

/**
 * Output the class of the Rendez Vous row.
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_class() {
	echo rendez_vous_get_class();
}

	/**
	 * Return the class of the Rendez Vous row.
	 *
	 * @since Rendez Vous (1.0.0)
	 */
	function rendez_vous_get_class() {
		$rendez_vous = rendez_vous()->query_loop->rendez_vous;
		$classes      = array();

		// Rendez Vou status - inherit, private.
		$classes[] = esc_attr( $rendez_vous->post_status );

		$classes = apply_filters( 'rendez_vous_get_class', $classes );
		$classes = array_merge( $classes, array() );
		$retval = 'class="' . join( ' ', $classes ) . '"';

		return $retval;
	}

/**
 * Output the "avatar" of the Rendez Vous row.
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_avatar() {
	echo rendez_vous_get_avatar();
}

	/**
	 * Return the "avatar" of the Rendez Vous row.
	 *
	 * @since Rendez Vous (1.0.0)
	 */
	function rendez_vous_get_avatar() {
		$output = '<div class="rendez-vous-avatar icon-' . rendez_vous()->query_loop->rendez_vous->post_status . '"></div>';

		return apply_filters( 'rendez_vous_get_avatar', $output, rendez_vous()->query_loop->rendez_vous->ID );
	}

/**
 * Output the title of the Rendez Vous.
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_the_title() {
	echo rendez_vous_get_the_title();
}

	/**
	 * Return the title of the Rendez Vous.
	 *
	 * @since Rendez Vous (1.0.0)
 	 */
	function rendez_vous_get_the_title() {
		return apply_filters( 'rendez_vous_get_the_title', rendez_vous()->query_loop->rendez_vous->post_title );
	}

/**
 * Output the link of the Rendez Vous.
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_the_link() {
	echo esc_url( rendez_vous_get_the_link() );
}

	/**
	 * Return the link of the Rendez Vous.
	 *
	 * @since Rendez Vous (1.0.0)
 	 */
	function rendez_vous_get_the_link() {
		$user_can = true;
		$link = rendez_vous_get_single_link( rendez_vous()->query_loop->rendez_vous->ID, rendez_vous()->query_loop->rendez_vous->post_author );

		switch( rendez_vous()->query_loop->rendez_vous->post_status ) {
			case 'private':
				$user_can = current_user_can( 'read_private_rendez_vouss', rendez_vous_get_the_rendez_vous_id() );
			break;

			case 'draft':
				$user_can = current_user_can( 'edit_rendez_vous', rendez_vous_get_the_rendez_vous_id() );
				$link = rendez_vous_get_edit_link( rendez_vous()->query_loop->rendez_vous->ID, rendez_vous()->query_loop->rendez_vous->post_author );
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
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_last_modified() {
	echo rendez_vous_get_last_modified();
}

	/**
	 * Return the date of the Rendez Vous.
	 *
	 * @since Rendez Vous (1.0.0)
 	 */
	function rendez_vous_get_last_modified() {
		$last_modified = bp_core_time_since( rendez_vous()->query_loop->rendez_vous->post_modified_gmt );
		return apply_filters( 'rendez_vous_get_last_modified', sprintf( __( 'Modified %s', 'rendez-vous' ), $last_modified ) );
	}

/**
 * Output the time till rendez-vous happens.
 *
 * @since 1.4.0
 */
function rendez_vous_time_to() {
	echo rendez_vous_get_time_to();
}

	/**
	 * Return the time till rendez-vous happens.
	 *
	 * @since 1.4.0
 	 */
	function rendez_vous_get_time_to() {
		add_filter( 'bp_core_time_since_ago_text', 'rendez_vous_set_time_to_text', 10, 1 );

		$time_to = bp_core_time_since( bp_core_current_time( false ), get_post_meta( rendez_vous()->query_loop->rendez_vous->ID, '_rendez_vous_defdate', true ) );

		remove_filter( 'bp_core_time_since_ago_text', 'rendez_vous_set_time_to_text', 10, 1 );

		return apply_filters( 'rendez_vous_get_time_to', sprintf( __( 'starts in %s', 'rendez-vous' ), $time_to ) );
	}

	/**
	 * Remove the 'ago' part of the BuddyPress human time diff function
	 *
	 * @since 1.4.0
 	 */
	function rendez_vous_set_time_to_text( $time_since_text = '' ) {
		return _x( '%s', 'Used to output the time to wait till the rendez-vous', 'rendez-vous' );
	}

/**
 * Check whether the Rendez Vous has a description.
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_has_description() {
	$user_can = ! empty( rendez_vous()->query_loop->rendez_vous->post_excerpt );

	switch( rendez_vous()->query_loop->rendez_vous->post_status ) {
		case 'private':
			$user_can = current_user_can( 'read_private_rendez_vouss', rendez_vous_get_the_rendez_vous_id() );
		break;

		case 'draft':
			$user_can = current_user_can( 'edit_rendez_vouss', rendez_vous_get_the_rendez_vous_id() );
		break;
	}

	return $user_can;
}

function rendez_vous_the_type() {
	echo rendez_vous_get_the_type();
}
add_action( 'rendez_vous_after_item_description', 'rendez_vous_the_type' );

	function rendez_vous_get_the_type() {
		if ( ! rendez_vous_has_types() ) {
			return false;
		}

		$types = rendez_vous_get_type( rendez_vous_get_the_rendez_vous_id() );

		if ( empty( $types ) ) {
			return false;
		}

		$type_names = wp_list_pluck( $types, 'name' );
		$type_name = array_pop( $type_names );

		$type_slugs = wp_list_pluck( $types, 'slug' );
		$type_slug = array_pop( $type_slugs );

		$output = sprintf( '<div class="item-desc"><a href="?type=%s" title="%s" class="rendez-vous-type">%s</a></div>',
			esc_attr( $type_slug ),
			esc_attr__( 'Filter rendez-vous having this type', 'rendez-vous' ),
			esc_html( $type_name )
		);

		return apply_filters( 'rendez_vous_get_the_type', $output, $type_name, $type_slug );
	}

/**
 * Output the description of the Rendez Vous.
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_the_excerpt() {
	echo rendez_vous_get_the_excerpt();
}

	/**
	 * Return the description of the Rendez Vous.
	 *
	 * @since Rendez Vous (1.0.0)
 	 */
	function rendez_vous_get_the_excerpt() {
		$excerpt = bp_create_excerpt( rendez_vous()->query_loop->rendez_vous->post_excerpt );

		return apply_filters( 'rendez_vous_get_the_excerpt', $excerpt );
	}

/**
 * Output the status (draft/private/public) of the Rendez Vous.
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_the_status() {
	echo rendez_vous_get_the_status();
}

	/**
	 * Return the status of the Rendez Vous.
	 *
	 * @since Rendez Vous (1.0.0)
 	 */
	function rendez_vous_get_the_status() {
		$status = __( 'All members', 'rendez-vous' );
		$rendez_vous = rendez_vous()->query_loop->rendez_vous;

		if ( 'private' == $rendez_vous->post_status ) {
			$status = __( 'Restricted', 'rendez-vous' );
		} else if ( 'draft' == $rendez_vous->post_status ) {
			$status = __( 'Draft', 'rendez-vous' );
		}

		return apply_filters( 'rendez_vous_get_the_status', $status, $rendez_vous->ID, $rendez_vous->post_status );
	}

/**
 * Output the user's action for the Rendez Vous.
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_the_user_actions() {
	echo rendez_vous_get_the_user_actions();
}
add_action( 'rendez_vous_schedule_actions', 'rendez_vous_the_user_actions' );
add_action( 'rendez_vous_attend_actions', 'rendez_vous_the_user_actions' );

	/**
	 * Return the user's action for the Rendez Vous.
	 *
	 * @since Rendez Vous (1.0.0)
 	 */
	function rendez_vous_get_the_user_actions() {
		$rendez_vous_id = rendez_vous()->query_loop->rendez_vous->ID;
		$user_id = rendez_vous()->query_loop->rendez_vous->post_author;

		$edit = $view = false;

		$status = rendez_vous()->query_loop->rendez_vous->post_status;

		if ( 'draft' != $status ) {

			$user_can = 'private' == $status ? current_user_can( 'read_private_rendez_vouss', $rendez_vous_id ) : current_user_can( 'read' );

			if ( ! empty( $user_can ) ) {
				$view_link = rendez_vous_get_single_link( $rendez_vous_id, $user_id );
				$view = '<a href="'. esc_url( $view_link ) .'" class="button view-rendez-vous bp-primary-action" id="view-rendez-vous-'. $rendez_vous_id .' ">' . _x( 'View', 'rendez-vous view link', 'rendez-vous' ) . '</a>';
			}
		}

		$current_action = apply_filters( 'rendez_vous_current_action', bp_current_action() );

		if ( current_user_can( 'edit_rendez_vous', $rendez_vous_id ) && 'schedule' == $current_action ) {
			$edit_link = rendez_vous_get_edit_link( $rendez_vous_id, $user_id );
			$edit = '<a href="'. esc_url( $edit_link ) .'" class="button edit-rendez-vous bp-primary-action" id="edit-rendez-vous-'. $rendez_vous_id .' ">' . _x( 'Edit', 'rendez-vous edit link', 'rendez-vous' ) . '</a>';
		}

		// Filter and return the HTML button
		return apply_filters( 'rendez_vous_get_the_user_actions', $view . $edit, $view, $edit );
	}

/** Single Output ***************************************************************/

/**
 * Output the edit form action for the Rendez Vous.
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_single_the_form_action() {
	$action = trailingslashit( bp_core_get_user_domain( rendez_vous()->item->organizer ) . buddypress()->rendez_vous->slug . '/schedule' );
	return apply_filters( 'rendez_vous_single_the_form_action', $action, rendez_vous()->item );
}

/**
 * Output the edit form class for the Rendez Vous.
 *
 * @since Rendez Vous (1.3.4)
 */
function rendez_vous_single_the_form_class() {

	// init classes
	$classes = array();

	// does this Rendez Vous have a term?
	if ( isset( rendez_vous()->item->type ) AND is_array( rendez_vous()->item->type ) ) {
		foreach( rendez_vous()->item->type AS $type ) {
			$classes[] = 'rendez-vous-' . $type->slug;
			$classes[] = 'rendez-vous-' . $type->term_id;
		}
	}

	// crunch them
	$class = implode( ' ', $classes );

	return apply_filters( 'rendez_vous_single_the_form_class', $class, rendez_vous()->item );
}

/**
 * Output the ID of the Rendez Vous.
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_single_the_id() {
	echo rendez_vous_single_get_the_id();
}

	/**
	 * Return the ID of the Rendez Vous.
	 *
	 * @since Rendez Vous (1.0.0)
	 */
	function rendez_vous_single_get_the_id(){
		return apply_filters( 'rendez_vous_single_get_the_id', rendez_vous()->item->id );
	}

/**
 * Output the title of the Rendez Vous.
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_single_the_title() {
	echo rendez_vous_single_get_the_title();
}

	/**
	 * Return the title of the Rendez Vous.
	 *
	 * @since Rendez Vous (1.0.0)
	 */
	function rendez_vous_single_get_the_title() {
		return apply_filters( 'rendez_vous_single_get_the_title', rendez_vous()->item->title );
	}

/**
 * Output the permalink of the Rendez Vous.
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_single_permalink() {
	echo rendez_vous_single_get_permalink();
}

	function rendez_vous_single_get_permalink() {
		$id = rendez_vous_single_get_the_id();
		$organizer = rendez_vous()->item->organizer;
		$link = rendez_vous_get_single_link( $id, $organizer );

		return apply_filters( 'rendez_vous_single_get_permalink', $link, $id, $organizer );
	}

/**
 * Output the edit link of the Rendez Vous.
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_single_edit_link() {
	echo rendez_vous_single_get_edit_link();
}

	function rendez_vous_single_get_edit_link() {
		$id = rendez_vous_single_get_the_id();
		$organizer = rendez_vous()->item->organizer;
		$link = rendez_vous_get_edit_link( $id, $organizer );

		return apply_filters( 'rendez_vous_single_get_edit_link', $link, $id, $organizer );
	}

/**
 * Output the description of the Rendez Vous.
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_single_the_description() {
	echo rendez_vous_single_get_the_description();
}

	/**
	 * Return the description of the Rendez Vous.
	 *
	 * @since Rendez Vous (1.0.0)
	 */
	function rendez_vous_single_get_the_description() {
		$screen = ! empty( rendez_vous()->screens->screen ) ? rendez_vous()->screens->screen : 'single';

		return apply_filters( "rendez_vous_{$screen}_get_the_description", rendez_vous()->item->description );
	}

/**
 * Output the venue of the Rendez Vous.
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_single_the_venue() {
	echo rendez_vous_single_get_the_venue();
}

	/**
	 * Return the venue of the Rendez Vous.
	 *
	 * @since Rendez Vous (1.0.0)
	 */
	function rendez_vous_single_get_the_venue() {
		return apply_filters( 'rendez_vous_single_get_the_venue', rendez_vous()->item->venue );
	}

/**
 * Check if the current Rendez Vous has a type.
 *
 * @since Rendez Vous (1.2.0)
 */
function rendez_vous_single_has_type() {
	return (bool) apply_filters( 'rendez_vous_single_has_type', rendez_vous_has_types( rendez_vous()->item ), rendez_vous()->item );
}

/**
 * Output the type of the Rendez Vous.
 *
 * @since Rendez Vous (1.2.0)
 */
function rendez_vous_single_the_type() {
	echo rendez_vous_single_get_the_type();
}

	/**
	 * Return the type of the Rendez Vous.
	 *
	 * @since Rendez Vous (1.2.0)
	 */
	function rendez_vous_single_get_the_type() {
		$type = '';

		if ( ! empty( rendez_vous()->item->type ) ) {
			$types = wp_list_pluck( rendez_vous()->item->type, 'name' );
			$type = array_pop( $types );
		}

		return apply_filters( 'rendez_vous_single_get_the_type', $type, rendez_vous()->item->type );
	}

/**
 * Output the selectbox to choose type for the Rendez Vous.
 *
 * @since Rendez Vous (1.2.0)
 */
function rendez_vous_single_edit_the_type() {
	echo rendez_vous_single_edit_get_the_type();
}

	/**
	 * Return the selectbox to choose type for the Rendez Vous.
	 *
	 * @since Rendez Vous (1.2.0)
	 */
	function rendez_vous_single_edit_get_the_type() {
		$rdv = rendez_vous();

		if ( empty( $rdv->types ) ) {
			$types = rendez_vous_get_terms( array( 'hide_empty' => false ) );
			$rdv->types = $types;
		} else {
			$types = $rdv->types;
		}

		$output = '<select name="_rendez_vous_edit[type]"><option value="">---</option>';

		$selected_type = 0;

		if ( ! empty( rendez_vous()->item->type ) ) {
			$selected_types = wp_list_pluck( rendez_vous()->item->type, 'term_id' );
			$selected_type = array_pop( $selected_types );
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
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_single_the_duration() {
	echo rendez_vous_single_get_the_duration();
}

	/**
	 * Return the duration of the Rendez Vous.
	 *
	 * @since Rendez Vous (1.0.0)
	 */
	function rendez_vous_single_get_the_duration() {
		return apply_filters( 'rendez_vous_single_get_the_duration', rendez_vous()->item->duration );
	}

/**
 * Output the privacy of the Rendez Vous.
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_single_the_privacy() {
	echo rendez_vous_single_get_the_privacy();
}

	function rendez_vous_single_is_published() {
		return 'draft' != rendez_vous()->item->status;
	}

	function rendez_vous_single_get_privacy() {
		$privacy = 'draft' == rendez_vous()->item->status ? rendez_vous()->item->privacy : rendez_vous()->item->status;

		$retval = 0;

		if ( in_array( $privacy, array( 1, 'private' ) ) )
			$retval = 1;

		return apply_filters( 'rendez_vous_single_get_privacy', $retval );
	}

	function rendez_vous_single_get_the_privacy() {
		$privacy = rendez_vous_single_get_privacy();
		return apply_filters( 'rendez_vous_single_get_the_privacy', checked( 1, $privacy, false ) );
	}

/**
 * Output the users prefs for the Rendez Vous.
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_single_the_dates( $view = 'single' ) {
	echo rendez_vous_single_get_the_dates( $view );
}

	function rendez_vous_single_get_the_dates( $view = 'single' ) {
		// First add organizer
		$all_attendees = (array) rendez_vous()->item->attendees;

		if ( ! in_array( rendez_vous()->item->organizer, $all_attendees ) )
			$all_attendees = array_merge( array( rendez_vous()->item->organizer ), $all_attendees );

		// Then remove current_user as we want him to be in last position
		if ( 'edit' != $view ) {
			if( ! rendez_vous_single_date_set() && bp_loggedin_user_id() )
				$attendees = array_diff( $all_attendees, array( bp_loggedin_user_id() ) );
			else
				$attendees = $all_attendees;
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

			// init col header
			$col_header = '';

			if ( is_long( $date ) ) {
				$col_header .= '<div class="date">' . date_i18n( get_option('date_format'), $date ) . '</div>';
				$col_header .= '<div class="time">' . date_i18n( get_option('time_format'), $date ) . '</div>';
			} else {
				$col_header .= '<div class="none">' . esc_html__( 'None', 'rendez-vous') . '</div>';
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

		//rows
		foreach ( $attendees as $attendee ) {
			$user_link = trailingslashit( bp_core_get_user_domain( $attendee ) );
			$user_name = bp_core_get_user_displayname( $attendee );
			$tr_class = $attendee == bp_loggedin_user_id() ? 'edited' : false;

			$output .= '<tr class="'. $tr_class .'"><td>';

			if ( 'edit' == $view ) {
				// Make sure the organizer is not removed from attendees
				if ( $attendee == rendez_vous()->item->organizer ) {
					$output .= '<input type="hidden" name="_rendez_vous_edit[attendees][]" value="' . $attendee . '"/>';
				} else {
					$output .= '<input type="checkbox" name="_rendez_vous_edit[attendees][]" value="' . $attendee . '" checked="true"/>&nbsp;';
				}
			}

			$output .= '<a href="' . esc_url( $user_link ) . '" title="' . esc_attr( $user_name ) . '">' . bp_core_fetch_avatar(
				array(
					'object'  => 'user',
					'item_id' => $attendee,
					'type'    => 'thumb',
					'class'   => 'mini',
					'width'   => 20,
					'height'  => 20
				)
			) . ' ' . $user_name . '</a></td>';

			foreach( $header as $date ) {
				$class = in_array( $attendee, $days[ $date ] ) ? 'active' : 'inactive';
				if ( 'none' == $date ) {
					$class .= ' impossible';
				}
				$output .= '<td class="'.$class.'">&nbsp;</td>';
			}
			$output .= '</tr>';
		}

		$ending_rows = array(
			'total'        => '<td>' . esc_html__( 'Total', 'rendez-vous') . '</td>',
		);

		if ( 'edit' != $view ) {
			$ending_rows['editable_row'] = '<td><a href="' . esc_url( bp_loggedin_user_domain() ) . '" title="' . esc_attr( bp_get_loggedin_user_username() ) . '">' . bp_core_fetch_avatar(
				array(
					'object'  => 'user',
					'item_id' => bp_loggedin_user_id(),
					'type'    => 'thumb',
					'class'   => 'mini',
					'width'   => 20,
					'height'  => 20
				)
			) . ' ' . esc_html( bp_get_loggedin_user_fullname() ) . '</a></td>';
		// Set definitive date
		} else {
			$ending_rows['editable_row'] = '<td id="rendez-vous-set">' . esc_html__( 'Set date', 'rendez-vous' ) . '</td>';
		}

		foreach ( $header as $date ) {
			$checked = checked( true, in_array( bp_loggedin_user_id(), $days[ $date ] ), false );
			$ending_rows['total']        .= '<td><strong>' . count( $days[ $date ] ) . '</strong></td>';

			// Let the user set his prefs
			if ( 'edit' != $view ){
				$class = false;

				if ( 'none' == $date )
					$class = ' class="none-resets-cb"';

				$ending_rows['editable_row'] .= '<td><input type="checkbox" name="_rendez_vous_prefs[days]['. bp_loggedin_user_id() .'][]" value="'. $date .'" '. $checked . $class . '/></td>';
			// Let the organizer choose the definitive date
			} else {
				$def_date = ! empty( rendez_vous()->item->def_date ) ? rendez_vous()->item->def_date : false;

				if ( 'none' != $date )
					$ending_rows['editable_row'] .= '<td><input type="radio" name="_rendez_vous_edit[def_date]" value="'. $date .'" '. checked( $date, $def_date, false ) . '/></td>';
				else
					$ending_rows['editable_row'] .= '<td></td>';
			}
		}

		if ( 'edit' != $view ) {
			// Date is set, changes cannot be done anymore
			if ( ! rendez_vous_single_date_set() ) {
				if ( 'private' == rendez_vous()->item->privacy ) {
					// If private, display the row only if current user is an attendee or the author
					if ( bp_loggedin_user_id() == rendez_vous()->item->organizer || in_array( bp_loggedin_user_id(), $all_attendees ) ) {
						$output .= '<tr class="edited">' . $ending_rows['editable_row'] . '</tr>';
					}

				} else {
					if ( current_user_can( 'subscribe_rendez_vous' ) ) {
						$output .= '<tr class="edited">' . $ending_rows['editable_row'] . '</tr>';
					}
				}
				// Display totals
				$output .= '<tr>' . $ending_rows['total'] . '</tr>';
				}
		} else {
			// Display totals
			$output .= '<tr>' . $ending_rows['total'] . '</tr>';
			// Display the radio to set the date
			if ( 'draft' != rendez_vous()->item->status ) {
				$output .= '<tr>' . $ending_rows['editable_row'] . '</tr>';
			}
		}

		/**
		 * Allow extra rows to be added to the table.
		 *
		 * @since Rendez Vous (1.4.3)
		 *
		 * @param str $output The output as it currently stands.
		 * @param str $view The output mode ('view' or 'edit').
		 * @return str $output The modified output.
		 */
		$output = apply_filters( 'rendez_vous_single_get_the_dates_rows_after', $output, $view );

		$output .= '</tbody>';
		$output .= '</table>';

		if ( ! is_user_logged_in() && 'publish' == rendez_vous()->item->status && ! rendez_vous_single_date_set() ) {
			$output .= '<div id="message" class="info"><p>' . __( 'If you want to set your preferences about this rendez-vous, please log in.', 'rendez-vous' ) . '</p></div>';
		}

		return apply_filters( 'rendez_vous_single_get_the_dates', $output, $view );
	}

/**
 * A report may be created for the Rendez Vous.
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_single_can_report() {
	if ( empty( rendez_vous()->item->def_date ) )
		return false;

	if ( rendez_vous()->item->def_date > strtotime( current_time( 'mysql' ) ) )
		return false;

	return true;
}

/**
 * Output the report editor for the Rendez Vous.
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_single_edit_report() {
	// add some filters, inspired by bbPress
	add_filter( 'tiny_mce_plugins',   'rendez_vous_tiny_mce_plugins'   );
	add_filter( 'teeny_mce_plugins',  'rendez_vous_tiny_mce_plugins'   );
	add_filter( 'teeny_mce_buttons',  'rendez_vous_teeny_mce_buttons'  );
	add_filter( 'quicktags_settings', 'rendez_vous_quicktags_settings' );

	wp_editor( rendez_vous()->item->report, 'rendez-vous-edit-report', array(
		'textarea_name'     => '_rendez_vous_edit[report]',
		'media_buttons'     => false,
		'textarea_rows'     => 12,
		'tinymce'           => apply_filters( 'rendez_vous_single_edit_report_tinymce', false ),
		'teeny'             => true,
		'quicktags'         => true,
		'dfw'               => false,
	) );

	// remove the filters, inspired by bbPress
	remove_filter( 'tiny_mce_plugins',   'rendez_vous_tiny_mce_plugins'   );
	remove_filter( 'teeny_mce_plugins',  'rendez_vous_tiny_mce_plugins'   );
	remove_filter( 'teeny_mce_buttons',  'rendez_vous_teeny_mce_buttons'  );
	remove_filter( 'quicktags_settings', 'rendez_vous_quicktags_settings' );
}

/**
 * Report for the Rendez Vous exists ?
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_single_has_report() {
	return ! empty( rendez_vous()->item->report );
}

/**
 * Output the report of the Rendez Vous.
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_single_the_report() {
	echo rendez_vous_single_get_the_report();
}

	function rendez_vous_single_get_the_report() {
		return apply_filters( 'rendez_vous_single_get_the_report', rendez_vous()->item->report );
	}

/**
 * Is the date of the Rendez Vous set ?
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_single_date_set() {
	return ! empty( rendez_vous()->item->def_date );
}

/**
 * Output the date of the Rendez Vous.
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_single_the_date() {
	echo rendez_vous_single_get_the_date();
}
	function rendez_vous_single_get_the_date() {
		$date_set = rendez_vous()->item->def_date;

		if ( empty( $date_set ) ) {
			return false;
		}

		if ( ! is_numeric( $date_set ) ) {
			return esc_html( $date_set );
		}

		$date = '<span class="date" data-timestamp="' .$date_set. '">' . date_i18n( get_option('date_format'), $date_set ) . '</span>';
		$time = '<span class="time" data-timestamp="' .$date_set. '">' . date_i18n( get_option('time_format'), $date_set ) . '</span>';

		$output = sprintf( __( '%s at %s', 'rendez-vous' ), $date, $time );

		return apply_filters( 'rendez_vous_single_get_the_date', $output, rendez_vous()->item );
	}

/**
 * Output the actions for the Rendez Vous.
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_single_the_action( $view = 'single' ) {
	echo rendez_vous_single_get_the_action( $view );
}

	function rendez_vous_single_get_the_action( $view = 'single' ) {
		$action = 'choose';

		if ( 'edit' == $view ) {
			$action = 'update';

			if ( 'draft' == rendez_vous()->item->status )
				$action = 'publish';

		}

		return apply_filters( 'rendez_vous_single_get_the_action', $action, $view );
	}

/**
 * Output the submits for the Rendez Vous.
 *
 * @since Rendez Vous (1.0.0)
 */
function rendez_vous_single_the_submit( $view = 'single' ) {
	if ( ! bp_loggedin_user_id() )
		return;

	if ( 'edit' == $view ) {
		$caption = 'draft' == rendez_vous()->item->status ? __( 'Publish Rendez-vous', 'rendez-vous' ) : __( 'Edit Rendez-vous', 'rendez-vous' );

		if ( current_user_can( 'delete_rendez_vous', rendez_vous()->item->id ) ) :
			$delete_link = rendez_vous_get_delete_link( rendez_vous()->item->id, rendez_vous()->item->organizer );

			if ( ! empty( $delete_link) ): ?>

			<a href="<?php echo esc_url( $delete_link ) ;?>" class="button delete-rendez-vous bp-secondary-action" id="delete-rendez-vous-<?php echo rendez_vous()->item->id ;?>"><?php esc_html_e( 'Cancel Rendez-vous', 'rendez-vous' ) ;?></a>

			<?php endif;

		endif;

		if ( current_user_can( 'edit_rendez_vous', rendez_vous()->item->id ) ) :?>
			<input type="submit" name="_rendez_vous_edit[submit]" id="rendez-vous-edit-submit" value="<?php echo esc_attr( $caption ); ?>" class="bp-primary-action"/>
		<?php endif;
	} else if ( current_user_can( 'subscribe_rendez_vous' ) ) {

		if ( 'publish' != rendez_vous()->item->status && ! in_array( bp_loggedin_user_id(), rendez_vous()->item->attendees ) && bp_loggedin_user_id() != rendez_vous()->item->organizer ) {
			return;
		}

		?>
		<input type="submit" name="_rendez_vous_prefs[submit]" id="rendez-vous-prefs-submit" value="<?php echo esc_attr( __( 'Save preferences', 'rendez-vous' ) ); ?>" class="bp-primary-action"/>
		<?php

		if ( 'edit' != $view && current_user_can( 'edit_rendez_vous', rendez_vous()->item->id ) && empty( rendez_vous()->item->def_date ) ) {
			?>
			<a href="<?php echo esc_url( rendez_vous_get_edit_link( rendez_vous()->item->id ,rendez_vous()->item->organizer ) ) ;?>#rendez-vous-set" class="button bp-secondary-action last"><?php esc_html_e( 'Set the date', 'rendez-vous' ) ;?></a>
			<div class="clear"></div>
			<?php
		}
	}
}
