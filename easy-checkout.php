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
    exit;
}

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

class Easy_Checkout {
    
    private $plugin_path;
    private $plugin_url;
    
    public function __construct() {
        $this->plugin_path = plugin_dir_path(__FILE__);
        $this->plugin_url = plugin_dir_url(__FILE__);
        
        // Declare HPOS compatibility
        add_action('before_woocommerce_init', array($this, 'declare_hpos_compatibility'));
        
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Hook into WooCommerce
        add_action('woocommerce_checkout_init', array($this, 'replace_checkout_template'));
        add_filter('woocommerce_locate_template', array($this, 'custom_checkout_template'), 10, 3);
        
        // Ensure shipping methods are available
        add_action('woocommerce_checkout_process', array($this, 'ensure_shipping_method'));
        add_action('woocommerce_checkout_update_order_review', array($this, 'update_shipping_methods'));
        
        // No redirect needed - we override the template directly
        
        // Admin notices
        add_action('admin_notices', array($this, 'show_activation_notice'));
        
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Declare compatibility with WooCommerce High-Performance Order Storage (HPOS)
     */
    public function declare_hpos_compatibility() {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        }
    }
    
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('easy-checkout', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
    
    public function enqueue_scripts() {
        if (is_checkout() && !is_wc_endpoint_url()) {
            wp_enqueue_style(
                'custom-checkout-style',
                $this->plugin_url . 'assets/css/checkout.css',
                array(),
                '1.0.0'
            );
            
            wp_enqueue_script(
                'custom-checkout-script',
                $this->plugin_url . 'assets/js/checkout.js',
                array('jquery', 'wc-checkout'),
                '1.0.0',
                true
            );
            
            // Localize script for AJAX
            wp_localize_script('custom-checkout-script', 'custom_checkout_params', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('custom_checkout_nonce')
            ));
        }
    }
    
    public function replace_checkout_template() {
        // This will be called when checkout is initialized
        add_action('woocommerce_checkout_before_customer_details', array($this, 'add_custom_checkout_header'));
    }
    
    public function custom_checkout_template($template, $template_name, $template_path) {
        // Replace the checkout form template
        if ($template_name == 'checkout/form-checkout.php') {
            $custom_template = $this->plugin_path . 'templates/form-checkout.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        return $template;
    }
    
    public function add_custom_checkout_header() {
        echo '<div class="custom-checkout-header">';
        echo '<h2>' . __('Secure Checkout', 'easy-checkout') . '</h2>';
        echo '<p class="checkout-progress">' . __('Step 2 of 3: Payment & Shipping', 'easy-checkout') . '</p>';
        echo '</div>';
    }
    
    public function activate() {
        // Just flush rewrite rules and show activation notice
        // No need to create pages - we'll override the template directly
        flush_rewrite_rules();
        add_option('easy_checkout_activation_notice', true);
    }
    
    public function deactivate() {
        // Just flush rewrite rules on deactivation
        // No need to restore pages - we only override templates
        flush_rewrite_rules();
    }
    
    public function show_activation_notice() {
        if (get_option('easy_checkout_activation_notice')) {
            $checkout_page_id = get_option('woocommerce_checkout_page_id');
            $checkout_url = $checkout_page_id ? get_permalink($checkout_page_id) : wc_get_checkout_url();
            
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>Easy Checkout Plugin Activated!</strong></p>';
            echo '<p>Your existing WooCommerce checkout page now uses the Easy Checkout template.</p>';
            if ($checkout_url) {
                echo '<p><a href="' . esc_url($checkout_url) . '" target="_blank">View your enhanced checkout page</a></p>';
            }
            echo '<p><em>The plugin replaces the checkout template without creating new pages.</em></p>';
            echo '</div>';
            
            // Remove the notice after showing it once
            delete_option('easy_checkout_activation_notice');
        }
    }
    
    /**
     * Ensure shipping method is selected when required
     */
    public function ensure_shipping_method() {
        $packages = WC()->shipping()->get_packages();
        
        if (empty($packages)) {
            return;
        }
        
        foreach ($packages as $i => $package) {
            $chosen_method = isset(WC()->session->chosen_shipping_methods[$i]) ? WC()->session->chosen_shipping_methods[$i] : '';
            
            if (empty($chosen_method)) {
                $available_methods = $package['rates'];
                
                if (!empty($available_methods)) {
                    // Auto-select first available shipping method
                    $first_method = array_keys($available_methods)[0];
                    WC()->session->set('chosen_shipping_methods', array($i => $first_method));
                }
            }
        }
    }
    
    /**
     * Update shipping methods when checkout is updated
     */
    public function update_shipping_methods() {
        if (WC()->cart->needs_shipping()) {
            WC()->cart->calculate_shipping();
        }
    }
}

// Initialize the plugin
new Easy_Checkout();

// Additional helper functions
function custom_checkout_get_template_part($slug, $name = '') {
    $template = '';
    
    if ($name) {
        $template = locate_template(array("{$slug}-{$name}.php", "custom-checkout/{$slug}-{$name}.php"));
    }
    
    if (!$template && $name && file_exists(plugin_dir_path(__FILE__) . "templates/{$slug}-{$name}.php")) {
        $template = plugin_dir_path(__FILE__) . "templates/{$slug}-{$name}.php";
    }
    
    if (!$template) {
        $template = locate_template(array("{$slug}.php", "custom-checkout/{$slug}.php"));
    }
    
    if (!$template && file_exists(plugin_dir_path(__FILE__) . "templates/{$slug}.php")) {
        $template = plugin_dir_path(__FILE__) . "templates/{$slug}.php";
    }
    
    if ($template) {
        load_template($template, false);
    }
}

// AJAX handlers for email validation
add_action('wp_ajax_validate_checkout_email', 'handle_email_validation');
add_action('wp_ajax_nopriv_validate_checkout_email', 'handle_email_validation');

function handle_email_validation() {
    check_ajax_referer('custom_checkout_nonce', 'nonce');
    
    $response = array('success' => true);
    $email = sanitize_email($_POST['email']);
    
    if (!is_email($email)) {
        $response['success'] = false;
        $response['message'] = __('Please enter a valid email address.', 'easy-checkout');
    }
    
    wp_send_json($response);
}

// AJAX handlers for cart management
add_action('wp_ajax_easy_checkout_add_to_cart', 'easy_checkout_add_to_cart');
add_action('wp_ajax_nopriv_easy_checkout_add_to_cart', 'easy_checkout_add_to_cart');

function easy_checkout_add_to_cart() {
    check_ajax_referer('custom_checkout_nonce', 'nonce');
    
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    
    if ($product_id <= 0 || $quantity <= 0) {
        wp_send_json_error(__('Invalid product or quantity.', 'easy-checkout'));
    }
    
    $product = wc_get_product($product_id);
    if (!$product || !$product->is_purchasable()) {
        wp_send_json_error(__('This product cannot be purchased.', 'easy-checkout'));
    }
    
    // Add to cart
    $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity);
    
    if ($cart_item_key) {
        // Get updated cart 
        $cart_data = array(
            'cart_count' => WC()->cart->get_cart_contents_count(),
            'cart_total' => WC()->cart->get_cart_total(),
            'cart_subtotal' => WC()->cart->get_cart_subtotal(),
            'cart_items' => array()
        );
        
        // Get cart items for display
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $cart_data['cart_items'][] = array(
                'key' => $cart_item_key,
                'product_id' => $cart_item['product_id'],
                'name' => $product->get_name(),
                'quantity' => $cart_item['quantity'],
                'price' => wc_price($product->get_price()),
                'subtotal' => wc_price($cart_item['line_subtotal'])
            );
        }
        
        wp_send_json_success($cart_data);
    } else {
        wp_send_json_error(__('Failed to add product to cart.', 'easy-checkout'));
    }
}

add_action('wp_ajax_easy_checkout_remove_from_cart', 'easy_checkout_remove_from_cart');
add_action('wp_ajax_nopriv_easy_checkout_remove_from_cart', 'easy_checkout_remove_from_cart');

function easy_checkout_remove_from_cart() {
    check_ajax_referer('custom_checkout_nonce', 'nonce');
    
    $cart_item_key = sanitize_text_field($_POST['cart_item_key']);
    
    if (empty($cart_item_key)) {
        wp_send_json_error(__('Invalid cart item.', 'easy-checkout'));
    }
    
    $removed = WC()->cart->remove_cart_item($cart_item_key);
    
    if ($removed) {
        // Get updated cart data
        $cart_data = array(
            'cart_count' => WC()->cart->get_cart_contents_count(),
            'cart_total' => WC()->cart->get_cart_total(),
            'cart_subtotal' => WC()->cart->get_cart_subtotal(),
            'cart_items' => array()
        );
        
        // Get cart items for display
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $cart_data['cart_items'][] = array(
                'key' => $cart_item_key,
                'product_id' => $cart_item['product_id'],
                'name' => $product->get_name(),
                'quantity' => $cart_item['quantity'],
                'price' => wc_price($product->get_price()),
                'subtotal' => wc_price($cart_item['line_subtotal'])
            );
        }
        
        wp_send_json_success($cart_data);
    } else {
        wp_send_json_error(__('Failed to remove product from cart.', 'easy-checkout'));
    }
}

add_action('wp_ajax_easy_checkout_get_cart', 'easy_checkout_get_cart');
add_action('wp_ajax_nopriv_easy_checkout_get_cart', 'easy_checkout_get_cart');

function easy_checkout_get_cart() {
    check_ajax_referer('custom_checkout_nonce', 'nonce');
    
    $cart_data = array(
        'cart_count' => WC()->cart->get_cart_contents_count(),
        'cart_total' => WC()->cart->get_cart_total(),
        'cart_subtotal' => WC()->cart->get_cart_subtotal(),
        'cart_items' => array(),
        'is_empty' => WC()->cart->is_empty()
    );
    
    // Get cart items for display
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        $cart_data['cart_items'][] = array(
            'key' => $cart_item_key,
            'product_id' => $cart_item['product_id'],
            'name' => $product->get_name(),
            'quantity' => $cart_item['quantity'],
            'price' => wc_price($product->get_price()),
            'subtotal' => wc_price($cart_item['line_subtotal'])
        );
    }
    
    wp_send_json_success($cart_data);
}

add_action('wp_ajax_easy_checkout_get_payment_methods', 'easy_checkout_get_payment_methods');
add_action('wp_ajax_nopriv_easy_checkout_get_payment_methods', 'easy_checkout_get_payment_methods');

function easy_checkout_get_payment_methods() {
    check_ajax_referer('custom_checkout_nonce', 'nonce');
    
    // Get available payment gateways
    $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
    $payment_methods = array();
    
    if (!empty($available_gateways)) {
        foreach ($available_gateways as $gateway) {
            $payment_methods[] = array(
                'id' => $gateway->id,
                'title' => $gateway->get_title(),
                'description' => $gateway->get_description(),
                'icon' => $gateway->get_icon(),
                'enabled' => $gateway->is_available(),
                'has_fields' => $gateway->has_fields(),
                'order_button_text' => $gateway->order_button_text
            );
        }
    }
    
    wp_send_json_success(array(
        'payment_methods' => $payment_methods,
        'has_methods' => !empty($payment_methods)
    ));
}

// Custom checkout fields - simplified version
add_filter('woocommerce_checkout_fields', 'customize_checkout_fields');

function customize_checkout_fields($fields) {
    // Remove all fields except the ones we need
    $fields['billing'] = array();
    $fields['shipping'] = array();
    $fields['order'] = array();
    
    // Add only required fields: Name, Address, Email
    $fields['billing']['billing_first_name'] = array(
        'type'        => 'text',
        'label'       => __('Name', 'easy-checkout'),
        'required'    => true,
        'class'       => array('form-row-wide'),
        'priority'    => 10,
        'placeholder' => __('Enter your full name', 'easy-checkout')
    );
    
    $fields['billing']['billing_address_1'] = array(
        'type'        => 'text',
        'label'       => __('Address', 'easy-checkout'),
        'required'    => true,
        'class'       => array('form-row-wide'),
        'priority'    => 20,
        'placeholder' => __('Enter your address', 'easy-checkout')
    );
    
    $fields['billing']['billing_email'] = array(
        'type'        => 'email',
        'label'       => __('Email', 'easy-checkout'),
        'required'    => true,
        'class'       => array('form-row-wide'),
        'priority'    => 30,
        'placeholder' => __('Enter your email address', 'easy-checkout'),
        'validate'    => array('email')
    );
    
    return $fields;
}

// Save custom fields with HPOS compatibility
add_action('woocommerce_checkout_update_order_meta', 'save_custom_checkout_fields');

function save_custom_checkout_fields($order_id) {
    // Fields are automatically saved by WooCommerce
    // This function is kept for any future custom field additions
}

// Remove the display admin function since we don't have custom fields to display
// Display custom fields in admin with HPOS compatibility
// add_action('woocommerce_admin_order_data_after_billing_address', 'display_custom_checkout_fields_admin');
?>