<?php
/**
 * Rendez Vous Upcoming Widget class.
 *
 * Upcoming Widget class.
 *
 * @package Rendez_Vous
 * @subpackage Classes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Rendez_Vous_Upcoming_Widget' ) ) :

	/**
	 * List the upcoming Rendez Vous for the logged-in User.
	 *
	 * @since 1.4.0
	 */
	class Rendez_Vous_Upcoming_Widget extends WP_Widget {

		/**
		 * Constructor.
		 *
		 * @since 1.4.0
		 */
		public function __construct() {

			$widget_ops = [ 'description' => __( 'List the upcoming Rendez Vous for the logged-in user.', 'rendez-vous' ) ];
			parent::__construct( false, $name = __( 'Upcoming Rendez Vous', 'rendez-vous' ), $widget_ops );

		}

		/**
		 * Register the widget.
		 *
		 * @since 1.4.0
		 */
		public static function register_widget() {
			register_widget( 'Rendez_Vous_Upcoming_Widget' );
		}

		/**
		 * Filter the query for this specific widget use.
		 *
		 * @since 1.4.0
		 *
		 * @param array $query_args The existing query args.
		 * @return array $query_args The modified query args.
		 */
		public function filter_rendez_vous_query( $query_args = [] ) {

			$upcoming_args = array_merge(
				$query_args,
				[
					'post_status' => [ 'private', 'publish' ],
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'meta_query'  => [
						'relation'         => 'AND',
						[
							'key'     => '_rendez_vous_attendees',
							'value'   => [ bp_loggedin_user_id() ],
							'compare' => 'IN',
						],
						'rendez_vous_date' => [
							'key'     => '_rendez_vous_defdate',
							'value'   => bp_core_current_time( true, 'timestamp' ),
							'compare' => '>=',
						],
					],
					'orderby'     => 'rendez_vous_date',
					'order'       => 'ASC',
				]
			);

			$allowed_keys = [
				'post_status'    => true,
				'post_type'      => true,
				//phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
				'posts_per_page' => true,
				'paged'          => true,
				'orderby'        => true,
				'order'          => true,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'meta_query'     => true,
			];

			return array_intersect_key( $upcoming_args, $allowed_keys );

		}

		/**
		 * Display the widget on front end.
		 *
		 * @since 1.4.0
		 *
		 * @param array $args The widget arguments.
		 * @param array $instance The widget instance.
		 */
		public function widget( $args = [], $instance = [] ) {

			// Display nothing if the current User is not set.
			if ( ! is_user_logged_in() ) {
				return;
			}

			// Default per_page is 5.
			$number = 5;

			// No Rendez Vous items to show? Stop.
			if ( ! empty( $instance['number'] ) ) {
				$number = (int) $instance['number'];
			}

			add_filter( 'rendez_vous_query_args', [ $this, 'filter_rendez_vous_query' ], 10, 1 );

			$has_rendez_vous = rendez_vous_has_rendez_vouss( [
				'per_page' => $number,
				'no_cache' => true,
			] );

			remove_filter( 'rendez_vous_query_args', [ $this, 'filter_rendez_vous_query' ], 10, 1 );

			// Display nothing if there are no upcoming Rendez Vous.
			if ( ! $has_rendez_vous ) {
				return;
			}

			// Default title is nothing.
			$title = '';

			if ( ! empty( $instance['title'] ) ) {
				$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
			}

			echo $args['before_widget'];

			if ( ! empty( $title ) ) {
				echo $args['before_title'] . $title . $args['after_title'];
			}

			?>
			<ul>

				<?php while ( rendez_vous_the_rendez_vouss() ) : ?>
					<?php rendez_vous_the_rendez_vous(); ?>

					<li>
						<a href="<?php rendez_vous_the_link(); ?>" title="<?php echo esc_attr( rendez_vous_get_the_title() ); ?>"><?php rendez_vous_the_title(); ?></a>
						<small class="time-to"><?php rendez_vous_time_to(); ?></small>
					</li>

				<?php endwhile; ?>

			</ul>
			<?php

			echo $args['after_widget'];

		}

		/**
		 * Update widget preferences.
		 *
		 * @since 1.4.0
		 *
		 * @param array $new_instance The old widget arguments.
		 * @param array $old_instance The new widget instance.
		 * @return array $instance The updated widget instance.
		 */
		public function update( $new_instance, $old_instance ) {

			$instance = [];

			if ( ! empty( $new_instance['title'] ) ) {
				$instance['title'] = wp_strip_all_tags( wp_unslash( $new_instance['title'] ) );
			}

			$instance['number'] = (int) $new_instance['number'];

			return $instance;

		}

		/**
		 * Display the form in Widgets Administration.
		 *
		 * @since 1.4.0
		 *
		 * @param array $instance The widget instance.
		 */
		public function form( $instance = [] ) {

			// Default to nothing.
			$title = '';
			if ( isset( $instance['title'] ) ) {
				$title = $instance['title'];
			}

			// Number default to 5.
			$number = 5;
			if ( ! empty( $instance['number'] ) ) {
				$number = absint( $instance['number'] );
			}

			?>
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Title:', 'rendez-vous' ); ?></label>
				<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $title ); ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php esc_html_e( 'Number of upcoming Rendez Vous to show:', 'rendez-vous' ); ?></label>
				<input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="text" value="<?php echo $number; ?>" size="3" />
			</p>
			<?php

		}

	}

endif;

add_action( 'bp_widgets_init', [ 'Rendez_Vous_Upcoming_Widget', 'register_widget' ], 10 );
