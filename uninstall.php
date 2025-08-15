<?php
/**
 * Uninstall handler for Easy Checkout
 */

// Exit if uninstall not called from WordPress
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Option keys used by the plugin
$option_keys = array(
    'easy_checkout_options',
    'easy_checkout_activation_notice',
);

// Delete options (single site)
foreach ($option_keys as $key) {
    delete_option($key);
}

// Also delete network options if multisite
if (is_multisite()) {
    foreach ($option_keys as $key) {
        delete_site_option($key);
    }
}