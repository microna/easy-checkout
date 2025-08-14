<?php
/**
 * Plugin Name: Easy Checkout
 * Plugin URI: https://github.com/microna/easy-checkout
 * Description: Easy Checkout Plugin for better checkout experience
 * Author: Stas Shokarev
 * Author URI: https://github.com/microna/easy-checkout
 * Text Domain: easy-checkout
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * Requires Plugins: woocommerce
 */

// Prevent direct access
if (!defined('ABSPATH')) {
	return;
}

// WooCommerce dependency
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
	return;
}

// Core constants
if (!defined('EASY_CHECKOUT_FILE')) {
	define('EASY_CHECKOUT_FILE', __FILE__);
}
if (!defined('EASY_CHECKOUT_PATH')) {
	define('EASY_CHECKOUT_PATH', plugin_dir_path(__FILE__));
}
if (!defined('EASY_CHECKOUT_URL')) {
	define('EASY_CHECKOUT_URL', plugin_dir_url(__FILE__));
}
if (!defined('EASY_CHECKOUT_VERSION')) {
	define('EASY_CHECKOUT_VERSION', '2.0.0');
}

// Includes
require_once EASY_CHECKOUT_PATH . 'includes/class-easy-checkout-assets.php';
require_once EASY_CHECKOUT_PATH . 'includes/class-easy-checkout-cart.php';
require_once EASY_CHECKOUT_PATH . 'includes/class-easy-checkout-checkout.php';
require_once EASY_CHECKOUT_PATH . 'includes/class-easy-checkout-payments.php';
require_once EASY_CHECKOUT_PATH . 'includes/class-easy-checkout-validation.php';
require_once EASY_CHECKOUT_PATH . 'includes/class-easy-checkout-loader.php';
if (is_admin() && file_exists(EASY_CHECKOUT_PATH . 'admin/class-admin.php')) {
	require_once EASY_CHECKOUT_PATH . 'admin/class-admin.php';
}

// Activation / Deactivation
function easy_checkout_activate() {
	flush_rewrite_rules();
	add_option('easy_checkout_activation_notice', true);
}
function easy_checkout_deactivate() {
	flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'easy_checkout_activate');
register_deactivation_hook(__FILE__, 'easy_checkout_deactivate');

// Admin activation notice
function easy_checkout_show_activation_notice() {
	if (!current_user_can('manage_options')) return;
	if (get_option('easy_checkout_activation_notice')) {
		$checkout_page_id = get_option('woocommerce_checkout_page_id');
		$checkout_url = $checkout_page_id ? get_permalink($checkout_page_id) : (function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : '');
		echo '<div class="notice notice-success is-dismissible">';
		echo '<p><strong>Easy Checkout Plugin Activated!</strong></p>';
		echo '<p>Your existing WooCommerce checkout page now uses the Easy Checkout template.</p>';
		if ($checkout_url) {
			echo '<p><a href="' . esc_url($checkout_url) . '" target="_blank">View your enhanced checkout page</a></p>';
		}
		echo '<p><em>The plugin replaces the checkout template without creating new pages.</em></p>';
		echo '</div>';
		delete_option('easy_checkout_activation_notice');
	}
}
add_action('admin_notices', 'easy_checkout_show_activation_notice');

// Initialize modular controllers
add_action('plugins_loaded', function () {
	if (class_exists('Easy_Checkout_Loader')) {
		$loader = new Easy_Checkout_Loader();
		$loader->init();
	}
});

// End of file
?>