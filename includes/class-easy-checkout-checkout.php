<?php
/**
 * Checkout customization (templates, fields, shipping)
 */

defined('ABSPATH') || exit;

class Easy_Checkout_Checkout_Controller {
    public function register_hooks() {
        add_action('before_woocommerce_init', array($this, 'declare_hpos_compat'));
        add_action('init', array($this, 'load_textdomain'));

        add_action('woocommerce_checkout_init', array($this, 'on_checkout_init'));
        add_filter('woocommerce_locate_template', array($this, 'override_template'), 10, 3);

        add_action('woocommerce_checkout_process', array($this, 'ensure_shipping_method'));
        add_action('woocommerce_checkout_update_order_review', array($this, 'update_shipping_methods'));

        add_filter('woocommerce_checkout_fields', array($this, 'customize_fields'));
        add_action('woocommerce_checkout_update_order_meta', array($this, 'on_update_order_meta'));
    }

    public function declare_hpos_compat() {
        if (class_exists('Automattic\\WooCommerce\\Utilities\\FeaturesUtil')) {
            Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', EASY_CHECKOUT_FILE, true);
        }
    }

    public function load_textdomain() {
        load_plugin_textdomain('easy-checkout', false, dirname(plugin_basename(EASY_CHECKOUT_FILE)) . '/languages/');
    }

    public function on_checkout_init() {
        add_action('woocommerce_checkout_before_customer_details', function () {
            echo '<div class="custom-checkout-header">';
            echo '<h2>' . esc_html__('Secure Checkout', 'easy-checkout') . '</h2>';
            echo '<p class="checkout-progress">' . esc_html__('Step 2 of 3: Payment & Shipping', 'easy-checkout') . '</p>';
            echo '</div>';
        });
    }

    public function override_template($template, $template_name, $template_path) {
        if ($template_name === 'checkout/form-checkout.php') {
            $custom = EASY_CHECKOUT_PATH . 'templates/form-checkout.php';
            if (file_exists($custom)) {
                return $custom;
            }
        }
        return $template;
    }

    public function ensure_shipping_method() {
        $packages = WC()->shipping()->get_packages();
        if (empty($packages)) return;
        foreach ($packages as $i => $package) {
            $chosen = isset(WC()->session->chosen_shipping_methods[$i]) ? WC()->session->chosen_shipping_methods[$i] : '';
            if (empty($chosen)) {
                $rates = isset($package['rates']) ? $package['rates'] : array();
                if (!empty($rates)) {
                    $first = array_keys($rates)[0];
                    WC()->session->set('chosen_shipping_methods', array($i => $first));
                }
            }
        }
    }

    public function update_shipping_methods() {
        if (WC()->cart->needs_shipping()) {
            WC()->cart->calculate_shipping();
        }
    }

    public function customize_fields($fields) {
        $fields['billing']  = array();
        $fields['shipping'] = array();
        $fields['order']    = array();

        $fields['billing']['billing_first_name'] = array(
            'type'        => 'text',
            'label'       => __('Name', 'easy-checkout'),
            'required'    => true,
            'class'       => array('form-row-wide'),
            'priority'    => 10,
            'placeholder' => __('Enter your full name', 'easy-checkout'),
        );

        $fields['billing']['billing_address_1'] = array(
            'type'        => 'text',
            'label'       => __('Address', 'easy-checkout'),
            'required'    => true,
            'class'       => array('form-row-wide'),
            'priority'    => 20,
            'placeholder' => __('Enter your address', 'easy-checkout'),
        );

        $fields['billing']['billing_email'] = array(
            'type'        => 'email',
            'label'       => __('Email', 'easy-checkout'),
            'required'    => true,
            'class'       => array('form-row-wide'),
            'priority'    => 30,
            'placeholder' => __('Enter your email address', 'easy-checkout'),
            'validate'    => array('email'),
        );

        return $fields;
    }

    public function on_update_order_meta($order_id) {
        // Reserved for future custom field persistence
    }
}

