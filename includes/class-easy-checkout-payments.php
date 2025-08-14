<?php
/**
 * Payments controller
 *
 * Provides AJAX to fetch available payment methods
 */

defined('ABSPATH') || exit;

class Easy_Checkout_Payments_Controller {
    public function register_hooks() {
        add_action('wp_ajax_easy_checkout_get_payment_methods', array($this, 'ajax_get_payment_methods'));
        add_action('wp_ajax_nopriv_easy_checkout_get_payment_methods', array($this, 'ajax_get_payment_methods'));
    }

    public function ajax_get_payment_methods() {
        check_ajax_referer('custom_checkout_nonce', 'nonce');

        $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
        $payment_methods = array();

        if (!empty($available_gateways)) {
            foreach ($available_gateways as $gateway) {
                $payment_methods[] = array(
                    'id'               => $gateway->id,
                    'title'            => $gateway->get_title(),
                    'description'      => $gateway->get_description(),
                    'icon'             => $gateway->get_icon(),
                    'enabled'          => $gateway->is_available(),
                    'has_fields'       => $gateway->has_fields(),
                    'order_button_text'=> $gateway->order_button_text,
                );
            }
        }

        wp_send_json_success(array(
            'payment_methods' => $payment_methods,
            'has_methods'     => !empty($payment_methods),
        ));
    }
}

