<?php

class Kwfy_Wc_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/kwfy-wc-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/kwfy-wc-admin.js', array( 'jquery' ), $this->version, false );

	}

	public function init_kiwify_gateway() {
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;

		require_once 'class-kwfy-wc-gateway.php';

		add_filter( 'woocommerce_payment_gateways', array( $this, 'add_kiwify_gateway' ) );
	}

	public function add_kiwify_gateway( $methods ) {
		$methods[] = 'KwfyWCGateway';
		return $methods;
	}

	public function register_api_routes() {
		register_rest_route('kwfy-wc/v1', '/create-order', array(
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => array($this, 'create_order_callback'),
			'permission_callback' => function () {
				return true;
			}
		));
	}

	public function create_order_callback($request) {
		$params = $request->get_json_params();

		$email = isset($params['Customer']) && isset($params['Customer']['email']) && is_email($params['Customer']['email']) ? $params['Customer']['email'] : null;
		
		if (!isset($params['Product']) || !$email) {
			return new WP_Error(
				'missing_data',
				__('Required data is missing or the email is invalid.', 'kwfy-wc'),
				array('status' => 400)
			);
		}

		$fname = $params['Customer']['first_name'];
		$lname = str_replace( $fname . ' ', '', $params['Customer']['full_name'] );
		
		$user = get_user_by('email', $params['Customer']['email']);
		if (!$user) {

			$data = array(
				'user_email'           => $email,
				'user_login'           => $email,
				'user_pass'	           => 123,
				'show_admin_bar_front' => 'false',
				'first_name'           => $fname,
				'last_name'            => $lname,
				'role'                 => 'customer',
				//'user_activation_key'  => $code
			);

			$user_id = wp_insert_user( $data );

			if (is_wp_error($user_id)) {
				return new WP_Error(
					'user_creation_failed',
					$user_id->get_error_message(),
					array('status' => 500)
				);
			}

			wp_send_new_user_notifications($user_id, 'user');
		} else {
			$user_id = $user->ID;
		}
	
		$order = wc_create_order();
		$order->set_customer_id($user_id);
		$order->set_customer_ip_address( $params['Customer']['ip'] ?? WC_Geolocation::get_ip_address() );
	
		$product_id = 29;
		$_product = wc_get_product($product_id);
		if (!$_product) {
			return new WP_Error(
				'invalid_product',
				__('The product does not exist.', 'kwfy-wc'),
				array('status' => 404)
			);
		}
	
		$order->add_product($_product, 1);
		$order->set_payment_method('kiwify');

		if ( isset($params['TrackingParameters']) && !empty(array_filter($params['TrackingParameters'], function ($a) { return $a !== null;})) ) {
			$order->add_meta_data('_wc_order_attribution_source_type', 'utm');
			foreach ( $params['TrackingParameters'] as $key => $value ) {
				$order->add_meta_data('_wc_order_attribution_' . $key, $value);
			}
		}

		$order->save_meta_data();
		$order->set_status('wc-completed', __( 'Order initiated on the Kiwify platform.', 'kwfy-wc' ));
		$order->calculate_totals();
		$order_id = $order->save();
	
		if (!$order_id) {
			return new WP_Error(
				'order_creation_failed',
				__('Failed to create order.', 'kwfy-wc'),
				array('status' => 500)
			);
		}
	
		return new WP_REST_Response(
			array('order_id' => $order_id),
			200
		);
	}

	public function custom_authenticate( $user, $username, $password ) {
		if (!is_wp_error($user)) {
			$user = get_userdata($user->ID);
			if ( $user->user_activation_key ) {
				$error = new WP_Error();
				$error->add( 403, __( 'You have not yet activated your account.', 'kwfy-wc' ) );
				return $error;
			}
			return $user;
		}
	}

	public function custom_new_user_email( $wp_new_user_notification_email, $user, $blogname ) {
		$key = get_password_reset_key( $user );

		if ( is_wp_error( $key ) ) {
			return $key;
		}

		$email_style = "style='background-color:#f7f7f7;border-radius:6px;border:1px solid #ccc;color:#333;max-width: 280px;margin:20px auto;padding: 20px;'";

		$activate = wc_lostpassword_url() . '?key=' . $key . '&id=' . $user->ID;
		
		$message = "<div $email_style>";
		$message .= "<p>" . sprintf( __( 'Welcome to %s!', 'kwfy-wc' ), $blogname ) . "</p>";
		$message .= "<p>" . __( 'To get started, click the button below to set your password.', 'kwfy-wc' ) . "</p>";
		$message .= "<a href='" . $activate . "'>" . __( 'Click here to activate your account', 'kwfy-wc' ) . "</a>";
		$message .= "</div>";
	
		$to = $user->user_email;
		$subject = sprintf( __( 'Welcome to %s!' ), $blogname );
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
	
		$wp_new_user_notification_email['to'] = $to;
		$wp_new_user_notification_email['message'] = $message;
		$wp_new_user_notification_email['subject'] = $subject;
		$wp_new_user_notification_email['headers'] = $headers;
	
		return $wp_new_user_notification_email;
	}

}
