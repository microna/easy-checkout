<?php
/**
 * Assets manager
 *
 * Enqueues styles and scripts for the checkout page
 */

defined('ABSPATH') || exit;

class Easy_Checkout_Assets {
    public function register_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function enqueue_assets() {
        if (!function_exists('is_checkout') || !is_checkout() || is_wc_endpoint_url()) {
            return;
        }

        wp_enqueue_style(
            'easy-checkout-style',
            EASY_CHECKOUT_URL . 'assets/css/checkout.css',
            array(),
            EASY_CHECKOUT_VERSION
        );

        wp_enqueue_script(
            'easy-checkout-script',
            EASY_CHECKOUT_URL . 'assets/js/checkout.js',
            array('jquery', 'wc-checkout'),
            EASY_CHECKOUT_VERSION,
            true
        );

        wp_localize_script('easy-checkout-script', 'custom_checkout_params', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('custom_checkout_nonce'),
        ));
    }
}

