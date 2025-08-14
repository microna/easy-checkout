<?php
/**
 * Plugin loader / bootstrap
 */

defined('ABSPATH') || exit;

class Easy_Checkout_Loader {
    /** @var Easy_Checkout_Assets */
    private $assets;
    /** @var Easy_Checkout_Cart_Controller */
    private $cart;
    /** @var Easy_Checkout_Checkout_Controller */
    private $checkout;
    /** @var Easy_Checkout_Payments_Controller */
    private $payments;
    /** @var Easy_Checkout_Validation_Controller */
    private $validation;
    /** @var Easy_Checkout_Admin */
    private $admin;

    public function __construct() {
        $this->assets   = new Easy_Checkout_Assets();
        $this->cart     = new Easy_Checkout_Cart_Controller();
        $this->checkout = new Easy_Checkout_Checkout_Controller();
        $this->payments   = new Easy_Checkout_Payments_Controller();
        $this->validation = new Easy_Checkout_Validation_Controller();
        if (is_admin()) {
            $this->admin = new Easy_Checkout_Admin();
        }
    }

    public function init() {
        $this->assets->register_hooks();
        $this->cart->register_hooks();
        $this->checkout->register_hooks();
        $this->payments->register_hooks();
        $this->validation->register_hooks();
    }
}

