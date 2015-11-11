<?php
/**
 * WooCommerce Jetpack Checkout Custom Fields
 *
 * The WooCommerce Jetpack Checkout Custom Fields class.
 *
 * @version 2.2.7
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Checkout_Custom_Fields' ) ) :

class WCJ_Checkout_Custom_Fields extends WCJ_Module {

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->id         = 'checkout_custom_fields';
		$this->short_desc = __( 'Checkout Custom Fields', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add custom fields to WooCommerce checkout page.', 'woocommerce-jetpack' );
		parent::__construct();

		if ( $this->is_enabled() ) {

			add_filter( 'woocommerce_checkout_fields',                          array( $this, 'add_custom_checkout_fields' ), PHP_INT_MAX );

			add_action( 'woocommerce_admin_order_data_after_billing_address',   array( $this, 'add_custom_billing_fields_to_admin_order_display' ), PHP_INT_MAX );
			add_action( 'woocommerce_admin_order_data_after_shipping_address',  array( $this, 'add_custom_shipping_fields_to_admin_order_display' ), PHP_INT_MAX );
//			add_action( 'woocommerce_admin_order_data_after_order_details',   array( $this, 'add_custom_order_and_account_fields_to_admin_order_display' ), PHP_INT_MAX );
			add_action( 'woocommerce_admin_order_data_after_shipping_address',  array( $this, 'add_custom_order_and_account_fields_to_admin_order_display' ), PHP_INT_MAX );

			add_action( 'woocommerce_order_details_after_order_table',          array( $this, 'add_custom_billing_fields_to_admin_order_display' ), PHP_INT_MAX );
			add_action( 'woocommerce_order_details_after_order_table',          array( $this, 'add_custom_shipping_fields_to_admin_order_display' ), PHP_INT_MAX );
			add_action( 'woocommerce_order_details_after_order_table',          array( $this, 'add_custom_order_and_account_fields_to_admin_order_display' ), PHP_INT_MAX );

			add_action( 'woocommerce_email_after_order_table',                  array( $this, 'add_custom_fields_to_emails' ), PHP_INT_MAX, 2 );

			add_filter( 'woo_ce_order_fields',                                  array( $this, 'add_custom_fields_to_store_exporter' ) );
			add_filter( 'woo_ce_order',                                         array( $this, 'add_custom_fields_to_store_exporter_order' ), PHP_INT_MAX, 2 );

			add_action( 'woocommerce_checkout_update_order_meta',               array( $this, 'update_custom_checkout_fields_order_meta' ) );

			add_action( 'wp_enqueue_scripts',                                   array( $this, 'enqueue_scripts' ) );
			add_action( 'wp_head',                                              array( $this, 'add_datepicker_script' ) );

//			add_action( 'woocommerce_order_formatted_shipping_address',         array( $this, 'add_custom_shipping_fields_to_formatted_address' ), PHP_INT_MAX, 2 );
		}
	}

	/**
	 * add_custom_fields_to_admin_emails.
	 */
	public function add_custom_fields_to_emails( $order, $sent_to_admin ) {
		if ( ( $sent_to_admin && 'yes' === get_option( 'wcj_checkout_custom_fields_email_all_to_admin' ) ) ||
		     ( ! $sent_to_admin && 'yes' === get_option( 'wcj_checkout_custom_fields_email_all_to_customer' ) ) ) {
				$this->add_custom_billing_fields_to_admin_order_display( $order );
				$this->add_custom_shipping_fields_to_admin_order_display( $order );
				$this->add_custom_order_and_account_fields_to_admin_order_display( $order );
		}
	}

	/**
	 * add_custom_fields_to_store_exporter_order.
	 *
	 * since 2.2.7
	 */
	public function add_custom_fields_to_store_exporter_order( $order, $order_id ) {

		for ( $i = 1; $i <= apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_checkout_custom_fields_total_number', 1 ) ); $i++ ) {
			if ( 'yes' === get_option( 'wcj_checkout_custom_field_enabled_' . $i ) ) {

				$the_section = get_option( 'wcj_checkout_custom_field_section_' . $i );
				$the_key = 'wcj_checkout_field_' . $i;
				$the_field = $the_section . '_' . $the_key;
				$the_value = get_post_meta( $order_id, '_' . $the_field, true );
				$the_value = ( isset( $the_value['value'] ) ) ? $the_value['value'] : $the_value;

				$order->$the_field = $the_value;
			}
		}

		return $order;
	}

	/**
	 * add_custom_fields_to_store_exporter.
	 */
	public function add_custom_fields_to_store_exporter( $fields ) {
		for ( $i = 1; $i <= apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_checkout_custom_fields_total_number', 1 ) ); $i++ ) {
			if ( 'yes' === get_option( 'wcj_checkout_custom_field_enabled_' . $i ) ) {

				$the_section = get_option( 'wcj_checkout_custom_field_section_' . $i );
				$the_key = 'wcj_checkout_field_' . $i;

				$fields[] = array(
					'name'  => $the_section . '_' . $the_key,
					'label' => get_option( 'wcj_checkout_custom_field_label_' . $i ),
				);
			}
		}
        return $fields;
	}

	/**
	 * enqueue_scripts.
	 */
	public function enqueue_scripts() {
		wp_enqueue_script('jquery-ui-datepicker');
		wp_enqueue_style( 'jquery-ui-css', '//ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );
	}

	/**
	 * Convert the php date format string to a js date format
	 *
	 * https://gist.github.com/clubduece/4053820
	 */
	public function date_format_php_to_js( $php_date_format ) {
		$date_formats_php_to_js = array(
			'F j, Y' => 'MM dd, yy',
			'Y/m/d'  => 'yy/mm/dd',
			'm/d/Y'  => 'mm/dd/yy',
			'd/m/Y'  => 'dd/mm/yy',
		);
		return isset( $date_formats_php_to_js[ $php_date_format ] ) ? $date_formats_php_to_js[ $php_date_format ] : 'MM dd, yy';
	}

	/**
	 * add_datepicker_script.
	 */
	public function add_datepicker_script() {
		?>
		<script>
		jQuery(document).ready(function() {
		 jQuery('input[display=\'date\']').datepicker({
		 dateFormat : '<?php echo $this->date_format_php_to_js( get_option( 'date_format' ) ); ?>'
		 });
		});
		</script>
		<?php
	}

	/**
	 * add_custom_shipping_fields_to_formatted_address.
	 *
	public function add_custom_shipping_fields_to_formatted_address( $fields, $order ) {
		for ( $i = 1; $i <= apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_checkout_custom_fields_total_number', 1 ) ); $i++ ) {
			//if ( 'yes' === get_option( 'wcj_checkout_custom_field_enabled_' . $i ) ) {
				$the_section = get_option( 'wcj_checkout_custom_field_section_' . $i );
				if ( 'shipping' === $the_section ) {
					$option_name = $the_section . '_' . 'wcj_checkout_field_' . $i;
					$fields[ $option_name ] = get_post_meta( $order->id, '_' . $option_name, true );
				}
			//}
		}
		return $fields;
	}

	/**
	 * update_custom_checkout_fields_order_meta.
	 */
	public function update_custom_checkout_fields_order_meta( $order_id ) {
		for ( $i = 1; $i <= apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_checkout_custom_fields_total_number', 1 ) ); $i++ ) {
			if ( 'yes' === get_option( 'wcj_checkout_custom_field_enabled_' . $i ) ) {
				$the_section = get_option( 'wcj_checkout_custom_field_section_' . $i );
				$option_name = $the_section . '_' . 'wcj_checkout_field_' . $i;
				if ( ! empty( $_POST[ $option_name ] ) ) {
//					update_post_meta( $order_id, '_' . $option_name, sanitize_text_field( $_POST[ $option_name ] ) );
					update_post_meta(
						$order_id,
						'_' . $option_name,
						array(
							'value'		=> sanitize_text_field( $_POST[ $option_name ] ),
							'label'		=> get_option( 'wcj_checkout_custom_field_label_' . $i ),
							'section'	=> get_option( 'wcj_checkout_custom_field_section_' . $i ),
						)
					);
				}
			}
		}
	}

	/**
	 * add_custom_billing_fields_to_admin_order_display.
	 */
	public function add_custom_billing_fields_to_admin_order_display( $order ) {
		$this->add_custom_fields_to_admin_order_display( $order, 'billing' );
	}

	/**
	 * add_custom_shipping_fields_to_admin_order_display.
	 */
	public function add_custom_shipping_fields_to_admin_order_display( $order ) {
		$this->add_custom_fields_to_admin_order_display( $order, 'shipping' );
	}

	/**
	 * add_custom_order_and_account_fields_to_admin_order_display.
	 */
	public function add_custom_order_and_account_fields_to_admin_order_display( $order ) {
		$this->add_custom_fields_to_admin_order_display( $order, 'order' );
		$this->add_custom_fields_to_admin_order_display( $order, 'account' );
	}

	/**
	 * add_custom_fields_to_admin_order_display.
	 */
	public function add_custom_fields_to_admin_order_display( $order, $section ) {

		$post_meta = get_post_meta( $order->id );//, $post_meta_name, false );
		foreach( $post_meta as $key => $values ) {
//			$value = unserialize( $values[0] );
			$value = maybe_unserialize( $values[0] );
//			foreach( $values as $value ) {
				if ( isset( $value['section'] ) && $section === $value['section'] ) {
					if ( isset( $value['value'] ) && '' != $value['value']  ) {
						$the_label = $value['label'];
						if ( '' != $the_label )
							$the_label = '<strong>' . $the_label . ':</strong> ';
						echo '<p>' . $the_label . $value['value'] . '</p>';
					}
				}
//			}
		}
	}

	/**
	 * add_custom_checkout_fields.
	 */
	public function add_custom_checkout_fields( $fields ) {

	for ( $i = 1; $i <= apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_checkout_custom_fields_total_number', 1 ) ); $i++ ) {

			if ( 'yes' === get_option( 'wcj_checkout_custom_field_enabled_' . $i ) ) {

				$categories_in = get_option( 'wcj_checkout_custom_field_categories_in_' . $i );

				if ( ! empty( $categories_in ) ) {
					$do_skip = true;
					foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
						$product_categories = get_the_terms( $values['product_id'], 'product_cat' );
						if ( empty( $product_categories ) ) continue;
						foreach( $product_categories as $product_category ) {
//							if ( in_array( $product_category->term_id, $cats_in ) ) {
							if ( in_array( $product_category->term_id, $categories_in ) ) {
								$do_skip = false;
								break;
							}
						}
						if ( ! $do_skip ) break;
					}
					if ( $do_skip ) continue;
				}

				$the_type = get_option( 'wcj_checkout_custom_field_type_' . $i );
				$custom_attributes = array();
				if ( 'datepicker' === $the_type ) {
					$the_type = 'text';
					$custom_attributes['display'] = 'date';
				}
				$the_section = get_option( 'wcj_checkout_custom_field_section_' . $i );
				$the_key = 'wcj_checkout_field_' . $i;

				$fields[ $the_section ][ $the_section . '_' . $the_key ] =
					array(
						'type'              => $the_type,
						'label'             => get_option( 'wcj_checkout_custom_field_label_' . $i ),
						'placeholder'       => get_option( 'wcj_checkout_custom_field_placeholder_' . $i ),
						'required'          => ( 'yes' === get_option( 'wcj_checkout_custom_field_required_' . $i ) ) ? true : false,
						'custom_attributes' => $custom_attributes,
						'clear'             => ( 'yes' === get_option( 'wcj_checkout_custom_field_clear_' . $i ) ) ? true : false,
						'class'             => array( get_option( 'wcj_checkout_custom_field_class_' . $i ), ),
					);
			}
		}
		return $fields;
	}

	/**
	 * get_settings.
	 */
    public function get_settings() {

		$settings = array(

			array(
				'title'    => __( 'Checkout Custom Fields Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'desc'     => '',//__( 'This section lets you add custom checkout fields.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_custom_fields_options',
			),

			array(
				'title'     => __( 'Add All Fields to Admin Emails', 'woocommerce-jetpack' ),
				'desc'      => __( 'Enable', 'woocommerce-jetpack' ),
				'id'        => 'wcj_checkout_custom_fields_email_all_to_admin',
				'default'   => 'yes',
				'type'      => 'checkbox',
			),

			array(
				'title'     => __( 'Add All Fields to Customers Emails', 'woocommerce-jetpack' ),
				'desc'      => __( 'Enable', 'woocommerce-jetpack' ),
				'id'        => 'wcj_checkout_custom_fields_email_all_to_customer',
				'default'   => 'yes',
				'type'      => 'checkbox',
			),

			array( 'type'  => 'sectionend', 'id' => 'wcj_checkout_custom_fields_options' ),

			array(
				'title'    => __( 'The Fields', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_checkout_custom_fields_individual_options',
			),

			array(
				'title'     => __( 'Custom Fields Number', 'woocommerce-jetpack' ),
				'desc_tip'  => __( 'Click "Save changes" after you change this number.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_checkout_custom_fields_total_number',
				'default'   => 1,
				'type'      => 'custom_number',
				'desc'      => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes'
				            => array_merge(
								is_array( apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ) ) ? apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ) : array(),
								array(
									'step' => '1',
									'min'  => '1',
								)
							),
				'css'       => 'width:100px;',
			),
		);

		$product_cats = array();
		$product_categories = get_terms( 'product_cat', 'orderby=name&hide_empty=0' );
		if ( ! empty( $product_categories ) && ! is_wp_error( $product_categories ) ){
			foreach ( $product_categories as $product_category ) {
				$product_cats[ $product_category->term_id ] = $product_category->name;
			}
		}

		for ( $i = 1; $i <= apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_checkout_custom_fields_total_number', 1 ) ); $i++ ) {
			$settings = array_merge( $settings,
				array(
					array(
						'title'     => __( 'Custom Field', 'woocommerce-jetpack' ) . ' #' . $i,
						'desc'      => __( 'enabled', 'woocommerce-jetpack' ),
						'id'        => 'wcj_checkout_custom_field_enabled_' . $i,
						'default'   => 'no',
						'type'      => 'checkbox',
					),
					array(
						'title'     => '',
						'desc'      => __( 'type', 'woocommerce-jetpack' ),
						'id'        => 'wcj_checkout_custom_field_type_' . $i,
						'default'   => 'text',
						'type'      => 'select',
						'options'     => array(
							'text'       => __( 'Text', 'woocommerce-jetpack' ),
							'textarea'   => __( 'Textarea', 'woocommerce-jetpack' ),
							//'number'   => __( 'Number', 'woocommerce-jetpack' ),
							'datepicker' => __( 'Datepicker', 'woocommerce-jetpack' ),
							'checkbox'   => __( 'Checkbox', 'woocommerce-jetpack' ),
							//'select'   => __( 'Select', 'woocommerce-jetpack' ),
							'password'   => __( 'Password', 'woocommerce-jetpack' ),
						),
						'css'       => 'width:200px;',
					),
					array(
						'title'     => '',
						'desc'      => __( 'required', 'woocommerce-jetpack' ),
						'id'        => 'wcj_checkout_custom_field_required_' . $i,
						'default'   => 'no',
						'type'      => 'checkbox',
					),
					array(
						'title'     => '',
						'desc'      => __( 'label', 'woocommerce-jetpack' ),
						'id'        => 'wcj_checkout_custom_field_label_' . $i,
						'default'   => '',
						'type'      => 'textarea',
						'css'       => 'width:200px;',
					),
					/*array(
						'title'     => '',
						'desc'      => __( 'for datepicker: min days', 'woocommerce-jetpack' ),
						'id'        => 'wcj_checkout_custom_field_datepicker_mindays_' . $i,
						'default'   => 0,
						'type'      => 'number',
					),
					array(
						'title'     => '',
						'desc'      => __( 'for datepicker: max days', 'woocommerce-jetpack' ),
						'id'        => 'wcj_checkout_custom_field_datepicker_maxdays_' . $i,
						'default'   => 0,
						'type'      => 'number',
					),*/
					array(
						'title'     => '',
						'desc'      => __( 'placeholder', 'woocommerce-jetpack' ),
						'id'        => 'wcj_checkout_custom_field_placeholder_' . $i,
						'default'   => '',
						'type'      => 'textarea',
						'css'       => 'width:200px;',
					),

					array(
						'title'        => '',
						'desc'        => __( 'section', 'woocommerce-jetpack' ),
						'id'           => 'wcj_checkout_custom_field_section_' . $i,
						'default'      => 'billing',
						'type'      => 'select',
						'options'     => array(
							'billing'   => __( 'Billing', 'woocommerce-jetpack' ),
							'shipping'  => __( 'Shipping', 'woocommerce-jetpack' ),
							'order'     => __( 'Order Notes', 'woocommerce-jetpack' ),
							'account'   => __( 'Account', 'woocommerce-jetpack' ),
						),
						'css'       => 'width:200px;',
					),

					array(
						'title'     => '',
						'desc'      => __( 'class', 'woocommerce-jetpack' ),
						'id'        => 'wcj_checkout_custom_field_class_' . $i,
						'default'   => 'form-row-wide',
						'type'      => 'select',
						'options'     => array(
							'form-row-wide'  => __( 'Wide', 'woocommerce-jetpack' ),
							'form-row-first' => __( 'First', 'woocommerce-jetpack' ),
							'form-row-last'  => __( 'Last', 'woocommerce-jetpack' ),
						),
						'css'       => 'width:200px;',
					),

					array(
						'title'     => '',
						'desc'      => __( 'clear', 'woocommerce-jetpack' ),
						'id'        => 'wcj_checkout_custom_field_clear_' . $i,
						'default'   => 'yes',
						'type'      => 'checkbox',
					),

					/* array(
						'title'     => '',
						'desc'      => __( 'categories', 'woocommerce-jetpack' ),
						'desc_tip'  => __( 'Comma separated list of product categories IDs', 'woocommerce-jetpack' ),
						'id'        => 'wcj_checkout_custom_field_cats_in_' . $i,
						'default'   => '',
						'css'       => 'width:400px;',
						'type'      => 'text',
					), */

					array(
						'title'     => '',
						'desc'      => __( 'categories', 'woocommerce-jetpack' ),
						'desc_tip'  => __( '', 'woocommerce-jetpack' ),
						'id'        => 'wcj_checkout_custom_field_categories_in_' . $i,
						'default'   => '',
						'type'      => 'multiselect',
						'class'     => 'chosen_select',
						'css'       => 'width: 450px;',
						'options'   => $product_cats,
					),

					/**
					array(
						'title'     => '',
						'desc'      => __( 'position', 'woocommerce-jetpack' ),
						'id'        => 'wcj_checkout_custom_field_position_' . $i,
						'default'   => 20,
						'type'      => 'number',
					),
					/**/
				)
			);
		}

		$settings[] = array( 'type'  => 'sectionend', 'id' => 'wcj_checkout_custom_fields_individual_options' );

		return $this->add_enable_module_setting( $settings );
	}

}

endif;

return new WCJ_Checkout_Custom_Fields();