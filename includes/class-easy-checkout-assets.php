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

        // Core namespace and shared state
        wp_enqueue_script(
            'easy-checkout-core',
            EASY_CHECKOUT_URL . 'assets/js/core.js',
            array('jquery', 'wc-checkout'),
            EASY_CHECKOUT_VERSION,
            true
        );

        // Utilities
        wp_enqueue_script(
            'easy-checkout-notifications',
            EASY_CHECKOUT_URL . 'assets/js/notifications.js',
            array('easy-checkout-core'),
            EASY_CHECKOUT_VERSION,
            true
        );

        // Feature modules
        wp_enqueue_script(
            'easy-checkout-cart',
            EASY_CHECKOUT_URL . 'assets/js/cart.js',
            array('easy-checkout-notifications'),
            EASY_CHECKOUT_VERSION,
            true
        );

        wp_enqueue_script(
            'easy-checkout-validation',
            EASY_CHECKOUT_URL . 'assets/js/validation.js',
            array('easy-checkout-core'),
            EASY_CHECKOUT_VERSION,
            true
        );

        wp_enqueue_script(
            'easy-checkout-payment',
            EASY_CHECKOUT_URL . 'assets/js/payment.js',
            array('easy-checkout-core'),
            EASY_CHECKOUT_VERSION,
            true
        );

        // Localize data for AJAX to core so all modules can use it
        wp_localize_script('easy-checkout-core', 'custom_checkout_params', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('custom_checkout_nonce'),
        ));
    }
}

