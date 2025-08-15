<?php
/**
 * Cart AJAX and line-item UI
 */

defined('ABSPATH') || exit;

class Easy_Checkout_Cart_Controller {
    public function register_hooks() {
        // Inject controls in checkout order-review
        add_filter('woocommerce_cart_item_name', array($this, 'render_item_controls'), 10, 3);

        // AJAX endpoints
        add_action('wp_ajax_easy_checkout_add_to_cart', array($this, 'ajax_add_to_cart'));
        add_action('wp_ajax_nopriv_easy_checkout_add_to_cart', array($this, 'ajax_add_to_cart'));

        add_action('wp_ajax_easy_checkout_update_cart_quantity', array($this, 'ajax_update_quantity'));
        add_action('wp_ajax_nopriv_easy_checkout_update_cart_quantity', array($this, 'ajax_update_quantity'));

        add_action('wp_ajax_easy_checkout_remove_from_cart', array($this, 'ajax_remove_item'));
        add_action('wp_ajax_nopriv_easy_checkout_remove_from_cart', array($this, 'ajax_remove_item'));

        add_action('wp_ajax_easy_checkout_get_cart', array($this, 'ajax_get_cart'));
        add_action('wp_ajax_nopriv_easy_checkout_get_cart', array($this, 'ajax_get_cart'));
    }

    public function render_item_controls($product_name, $cart_item, $cart_item_key) {
        if (!function_exists('is_checkout') || !is_checkout() || is_wc_endpoint_url()) {
            return $product_name;
        }

        $quantity = isset($cart_item['quantity']) ? (int) $cart_item['quantity'] : 1;
        $product  = isset($cart_item['data']) ? $cart_item['data'] : null;
        $max_qty  = $product && is_object($product) && method_exists($product, 'get_max_purchase_quantity')
            ? (int) $product->get_max_purchase_quantity()
            : 99;
        if ($max_qty <= 0) {
            $max_qty = $quantity; // Prevent increasing if max unknown/zero
        }

        $controls  = '<div class="ec-quantity-controls" data-cart-item-key="' . esc_attr($cart_item_key) . '" data-max-qty="' . esc_attr($max_qty) . '">';
        $controls .= '<input type="number" class="ec-cart-qty-input" data-cart-item-key="' . esc_attr($cart_item_key) . '" min="1" max="' . esc_attr($max_qty) . '" value="' . esc_attr($quantity) . '">';
        $controls .= '<button type="button" class="ec-remove-item" data-cart-item-key="' . esc_attr($cart_item_key) . '" aria-label="' . esc_attr__('Remove item', 'easy-checkout') . '"><span class="remove-icon">&times;</span></button>';
        $controls .= '</div>';

        return $product_name . $controls;
    }

    public function ajax_add_to_cart() {
        check_ajax_referer('custom_checkout_nonce', 'nonce');
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $quantity   = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
        if ($product_id <= 0 || $quantity <= 0) {
            wp_send_json_error(__('Invalid product or quantity.', 'easy-checkout'));
        }
        $product = wc_get_product($product_id);
        if (!$product || !$product->is_purchasable()) {
            wp_send_json_error(__('This product cannot be purchased.', 'easy-checkout'));
        }
        // Stock/max purchase guard
        if (method_exists($product, 'get_max_purchase_quantity')) {
            $max_allowed = (int) $product->get_max_purchase_quantity();
            if ($max_allowed > 0 && $quantity > $max_allowed) {
                wp_send_json_error(sprintf(__('Only %d units available in stock.', 'easy-checkout'), $max_allowed));
            }
        }
        $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity);
        if (!$cart_item_key) {
            wp_send_json_error(__('Failed to add product to cart.', 'easy-checkout'));
        }
        wp_send_json_success($this->serialize_cart());
    }

    public function ajax_update_quantity() {
        check_ajax_referer('custom_checkout_nonce', 'nonce');
        $cart_item_key = isset($_POST['cart_item_key']) ? sanitize_text_field(wp_unslash($_POST['cart_item_key'])) : '';
        $quantity      = isset($_POST['quantity']) ? intval($_POST['quantity']) : -1;
        if (empty($cart_item_key) || $quantity < 0) {
            wp_send_json_error(__('Invalid cart item or quantity.', 'easy-checkout'));
        }
        // Enforce stock/max purchase limits
        $cart = WC()->cart->get_cart();
        if (isset($cart[$cart_item_key])) {
            $product = isset($cart[$cart_item_key]['data']) ? $cart[$cart_item_key]['data'] : null;
            if ($product && method_exists($product, 'get_max_purchase_quantity')) {
                $max_allowed = (int) $product->get_max_purchase_quantity();
                if ($max_allowed > 0 && $quantity > $max_allowed) {
                    wp_send_json_error(sprintf(__('Only %d units available in stock.', 'easy-checkout'), $max_allowed));
                }
            }
        }
        $result = $quantity === 0
            ? WC()->cart->remove_cart_item($cart_item_key)
            : WC()->cart->set_quantity($cart_item_key, $quantity);

        if (!$result) {
            wp_send_json_error(__('Failed to update cart.', 'easy-checkout'));
        }
        WC()->cart->calculate_totals();
        wp_send_json_success(array(
            'cart_count' => WC()->cart->get_cart_contents_count(),
            'cart_total' => WC()->cart->get_cart_total(),
        ));
    }

    public function ajax_remove_item() {
        check_ajax_referer('custom_checkout_nonce', 'nonce');
        $cart_item_key = isset($_POST['cart_item_key']) ? sanitize_text_field(wp_unslash($_POST['cart_item_key'])) : '';
        if (empty($cart_item_key)) {
            wp_send_json_error(__('Invalid cart item.', 'easy-checkout'));
        }
        $removed = WC()->cart->remove_cart_item($cart_item_key);
        if (!$removed) {
            wp_send_json_error(__('Failed to remove product from cart.', 'easy-checkout'));
        }
        wp_send_json_success($this->serialize_cart());
    }

    public function ajax_get_cart() {
        check_ajax_referer('custom_checkout_nonce', 'nonce');
        wp_send_json_success($this->serialize_cart());
    }

    private function serialize_cart() {
        $cart_data = array(
            'cart_count'   => WC()->cart->get_cart_contents_count(),
            'cart_total'   => WC()->cart->get_cart_total(),
            'cart_subtotal'=> WC()->cart->get_cart_subtotal(),
            'cart_items'   => array(),
            'is_empty'     => WC()->cart->is_empty(),
        );
        foreach (WC()->cart->get_cart() as $key => $item) {
            $product = $item['data'];
            $cart_data['cart_items'][] = array(
                'key'        => $key,
                'product_id' => $item['product_id'],
                'name'       => $product ? $product->get_name() : '',
                'quantity'   => isset($item['quantity']) ? (int) $item['quantity'] : 0,
                'price'      => $product ? wc_price($product->get_price()) : '',
                'subtotal'   => isset($item['line_subtotal']) ? wc_price($item['line_subtotal']) : '',
            );
        }
        return $cart_data;
    }
}