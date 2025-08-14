<?php
/**
 * Validation controller
 *
 * Handles light-weight AJAX validations for checkout fields
 */

defined('ABSPATH') || exit;

class Easy_Checkout_Validation_Controller {
    public function register_hooks() {
        add_action('wp_ajax_validate_checkout_email', array($this, 'validate_email'));
        add_action('wp_ajax_nopriv_validate_checkout_email', array($this, 'validate_email'));
    }

    public function validate_email() {
        check_ajax_referer('custom_checkout_nonce', 'nonce');

        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        $response = array('success' => true);

        if (!is_email($email)) {
            $response['success'] = false;
            $response['message'] = __('Please enter a valid email address.', 'easy-checkout');
        }

        wp_send_json($response);
    }
}

