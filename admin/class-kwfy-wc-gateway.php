<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KwfyWCGateway extends WC_Payment_Gateway {

    public function __construct() {
        $this->id = 'kiwify';
        $this->icon = '';
        $this->has_fields = false;
        $this->method_title = __( 'Kiwify', 'kwfy-wc' );
        $this->method_description = __( 'Kiwify payment gateway integration for WooCommerce.', 'kwfy-wc' );

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option( 'title' );
        $this->description = $this->get_option( 'description' );

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'title' => array(
                'title'       => __( 'Title', 'kwfy-wc' ),
                'type'        => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'kwfy-wc' ),
                'default'     => __( 'Kiwify', 'kwfy-wc' ),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __( 'Description', 'kwfy-wc' ),
                'type'        => 'textarea',
                'description' => __( 'Payment method description that the customer will see on your checkout.', 'kwfy-wc' ),
                'default'     => __( 'Pay with Kiwify.', 'kwfy-wc' ),
            ),
        );
    }

    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );

        $order->update_status( 'on-hold', __( 'Awaiting Kiwify payment', 'kwfy-wc' ) );

        wc_reduce_stock_levels( $order_id );
        WC()->cart->empty_cart();

        return array(
            'result'   => 'success',
            'redirect' => $this->get_return_url( $order ),
        );
    }
}
