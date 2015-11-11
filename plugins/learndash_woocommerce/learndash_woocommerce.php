<?php
/*
Plugin Name: LearnDash WooCommerce Integration
Plugin URI: http://www.learndash.com
Description: LearnDash WooCommerce Integration Plugin
Version: 1.2
Author: LearnDash
Author URI: http://www.learndash.com
*/

class learndash_woocommerce {
	public $debug = true;
	
	function __construct() {
		add_filter('product_type_selector', array($this, 'add_product_type'), 1, 2);
		add_action('woocommerce_product_options_general_product_data', array($this, 'render_course_selector'));
		add_action('admin_enqueue_scripts', array($this, 'add_scripts'));
		add_action( 'wp_enqueue_scripts', array( $this, 'add_front_scripts' ) );
		add_action('save_post', array($this, 'store_related_courses'), 1, 2);
		add_action('woocommerce_order_status_completed', array($this, 'send_receipt'), 10, 1);
		add_action('woocommerce_order_status_processing', array($this, 'send_receipt'), 10, 1);
	}

	function add_product_type($types, $product_type) {
		$types['course'] = __( 'Course', 'learndash');
		return $types;
	}

	function add_scripts(){
		wp_enqueue_script( 'ld_wc', plugins_url('/learndash_woocommerce.js', __FILE__), array('jquery') );
	}

	function add_front_scripts(){
		wp_enqueue_script( 'ld_wc_front', plugins_url('/front.js', __FILE__), array('jquery') );
	}

	function render_course_selector() {
		global $post;
		$courses = $this->list_courses();
		echo '<div class="options_group show_if_course">';

		$values = get_post_meta($post->ID, '_related_course', true);
		if(!$values)
			$values = array();

		woocommerce_wp_select(array(
			'id' => '_related_course[]',
			'label' => __('Related Courses', 'learndash'),
			'options' => $courses,
			'desc_tip' => true,
			'description' => __('You can select multiple courses to sell together holding the SHIFT key when clicking.', 'learndash')
		));

		echo '<script>ldRelatedCourses = ' . json_encode($values) . '</script>';

		echo '</div>';
	}

	function store_related_courses($id, $post){
		$related_courses = $_POST['_related_course'];
		if(isset($_POST['_related_course']))
			update_post_meta($id, '_related_course', $_POST['_related_course']);

	}

	function send_receipt($order_id){
		//if($new_status == 'processing' && $status != 'completed' || $new_status == 'completed' && $status == 'processing'){
		if($status != 'processing' && $status != 'completed') {
			$order = new WC_Order($order_id);
			$products = $order->get_items();

			foreach($products as $product){
				$courses_id = get_post_meta($product['product_id'], '_related_course', true);
				if($courses_id && is_array($courses_id)){
					foreach($courses_id as $cid)
					ld_update_course_access($order->customer_user, $cid);
				}
			}
		}
	}
	
	function debug($msg) {
		$original_log_errors = ini_get('log_errors');
		$original_error_log = ini_get('error_log');
		ini_set('log_errors', true);
		ini_set('error_log', dirname(__FILE__).DIRECTORY_SEPARATOR.'debug.log');
		
		global $ld_sf_processing_id;
		if(empty($ld_sf_processing_id))
		$ld_sf_processing_id	= time();
		
		if(isset($_GET['debug']) || $this->debug)
		error_log("[$ld_sf_processing_id] ".print_r($msg, true)); //Comment This line to stop logging debug messages.
		
		ini_set('log_errors', $original_log_errors);
		ini_set('error_log', $original_error_log);		
	}
	
	function list_courses() {
		global $post;
		$postid = $post->ID;
		query_posts( array( 'post_type' => 'sfwd-courses', 'posts_per_page' => -1 ) );
		$courses = array();
		while ( have_posts() ) {
			the_post(); 
			$courses[get_the_ID()] = get_the_title();
		}
		wp_reset_query();
		$post = get_post($postid);
		return $courses;
	}
}

new learndash_woocommerce();
