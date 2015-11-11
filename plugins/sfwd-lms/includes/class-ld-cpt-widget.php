<?php
/**
 * Registers widget for displaying a list of lessons for a course and tracks lesson progress.
 * 
 * @since 2.0.9
 * 
 * @package LearnDash\CPT
 */



if ( ! class_exists( 'SFWD_CPT_Widget' ) ) {

	/**
	 * Adds widget for displaying posts 
	 */
	class SFWD_CPT_Widget extends WP_Widget {

		protected $post_type;
		protected $post_name;
		protected $post_args;



		/**
		 * Set up post arguments for widget
		 * 
		 * @since 2.0.9
		 * 
		 * @param string $post_type
		 * @param string $post_name
		 * @param array  $args 		widget arguments
		 */
		public function __construct( $post_type, $post_name, $args = array() ) {
			$this->post_type = $post_type;
			$this->post_name = $post_name;

			if ( ! is_array( $args ) ) {
				$args = array();
			}

			if ( $post_type == 'sfwd-lessons' ) {
				$args['description'] = __( 'Displays a list of lessons for a course and tracks lesson progress.', 'learndash' );
			}

			if ( empty( $args['description'] ) ) {
				$args['description'] = sprintf( __( 'Displays a list of %s', 'learndash' ), $post_name );
			}

			if ( empty( $this->post_args) ) {
				$this->post_args = array( 'post_type' => $this->post_type, 'numberposts' => -1, 'order' => 'DESC', 'orderby' => 'date' );
			}

			parent::__construct( "{$post_type}-widget", $post_name, $args );
		}



		/**
		 * Displays widget
		 * 
		 * @since 2.0.9
		 * 
		 * @param  array $args     widget arguments
		 * @param  array $instance widget instance
		 * @return string          widget output
		 */
		public function widget( $args, $instance ) {

			extract( $args, EXTR_SKIP );

			/* Before Widget content */
			$buf = $before_widget;

			/**
			 * Filter widget title
			 * 
			 * @param string
			 */
			$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

			if ( ! empty( $title) ) {
				$buf .= $before_title . $title . $after_title;
			}

			$buf .= '<ul>';

			/* Display Widget Data */

			if ( $this->post_type == 'sfwd-lessons' ) {
				$course_id = learndash_get_course_id();

				if ( empty( $course_id ) || ! is_single() ) {
					return '';
				}

				$course_lessons_list          = $this->course_lessons_list( $course_id );
				$stripped_course_lessons_list = strip_tags( $course_lessons_list );

				if ( empty( $stripped_course_lessons_list ) ) {
					return '';
				}

				$buf .= $course_lessons_list;
			} else {
				$args = $this->post_args;

				$args['posts_per_page'] = $args['numberposts'];
				$args['wrapper']        = 'li';
				global $shortcode_tags, $post;

				if ( ! empty( $shortcode_tags[ $this->post_type ] ) ) {
					$buf .= call_user_func( $shortcode_tags[ $this->post_type ], $args, null, $this->post_type );
				}
			}

			/* After Widget content */
			$buf .= '</ul>' . $after_widget;

			echo $buf;

		}



		/**
		 * Sets up course lesson list HTML
		 * 
		 * @since 2.0.9
		 * 
		 * @param  int 		$course_id 	course id
		 * @return string   $html       output
		 */
		function course_lessons_list( $course_id ) {
			$course = get_post( $course_id );

			if ( empty( $course->ID) || $course_id != $course->ID ) {
				return '';
			}

			$html                  = '';
			$course_lesson_orderby = learndash_get_setting( $course_id, 'course_lesson_orderby' );
			$course_lesson_order   = learndash_get_setting( $course_id, 'course_lesson_order' );
			$lessons               = sfwd_lms_get_post_options( 'sfwd-lessons' );
			$orderby               = ( empty( $course_lesson_orderby)) ? $lessons['orderby'] : $course_lesson_orderby;
			$order                 = ( empty( $course_lesson_order)) ? $lessons['order'] : $course_lesson_order;
			$lessons               = wptexturize( do_shortcode( "[sfwd-lessons meta_key='course_id' meta_value='{$course_id}' order='{$order}' orderby='{$orderby}' posts_per_page='{$lessons['posts_per_page']}' wrapper='li']" ) );
			$html .= $lessons;
			return $html;
		}



		/**
		 * Handles widget updates in admin
		 * 
		 * @since 2.0.9
		 * 
		 * @param  array $new_instance
		 * @param  array $old_instance
		 * @return array $instance
		 */
		public function update( $new_instance, $old_instance ) {
			/* Updates widget title value */
			$instance = $old_instance;
			$instance['title'] = strip_tags( $new_instance['title'] );
			return $instance;
		}



		/**
		 * Display widget form in admin
		 * 
		 * @since 2.0.9
		 * 
		 * @param  array $instance widget instance
		 */
		public function form( $instance ) {
			if ( $instance ) {
				$title = esc_attr( $instance['title'] );
			} else {
				$title = $this->post_name;
			}

			?>
				<p>
					<label for="<?php echo $this->get_field_id( 'title' );?>"><?php _e( 'Title:', 'learndash' );?></label>
					<input class="widefat" id="<?php echo $this->get_field_id( 'title' );?>" name="<?php echo $this->get_field_name( 'title' );?>" type="text" value="<?php echo $title;?>" />
				</p>
			<?php
		}
	}
}
