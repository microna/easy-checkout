<?php
/**
 * Fired when the plugin is uninstalled.
 * 
 * This file handles the complete cleanup of the Easy Checkout plugin,
 * including removing custom pages, options, and restoring WooCommerce settings.
 */

// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check if WooCommerce is active before proceeding
if (!class_exists('WooCommerce')) {
    return;
}

/**
 * Clean up Easy Checkout plugin data
 */
class Easy_Checkout_Uninstaller {
    
    public static function uninstall() {
        // Restore original WooCommerce checkout page
        self::restore_original_checkout_page();
        
        // Remove custom checkout page
        self::remove_custom_checkout_page();
        
        // Clean up plugin options
        self::cleanup_plugin_options();
        
        // Clean up user meta (if any custom user data was stored)
        self::cleanup_user_meta();
        
        // Clean up transients and cache
        self::cleanup_transients();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Restore the original WooCommerce checkout page
     */
    private static function restore_original_checkout_page() {
        $original_checkout_page_id = get_option('easy_checkout_original_page_id');
        
        if ($original_checkout_page_id) {
            // Restore the original checkout page in WooCommerce settings
            update_option('woocommerce_checkout_page_id', $original_checkout_page_id);
            
            // Log the restoration for debugging
            error_log('Easy Checkout: Restored original checkout page ID: ' . $original_checkout_page_id);
        }
    }
    
    /**
     * Remove the custom checkout page created by the plugin
     */
    private static function remove_custom_checkout_page() {
        $custom_page_id = get_option('easy_checkout_custom_page_id');
        
        if ($custom_page_id) {
            // Get the page to verify it's our custom page
            $page = get_post($custom_page_id);
            
            if ($page && get_post_meta($custom_page_id, '_easy_checkout_custom_page', true)) {
                // This is confirmed to be our custom page, safe to delete
                wp_delete_post($custom_page_id, true); // true = force delete (bypass trash)
                
                // Log the deletion for debugging
                error_log('Easy Checkout: Deleted custom checkout page ID: ' . $custom_page_id);
            }
        }
    }
    
    /**
     * Clean up all plugin-specific options
     */
    private static function cleanup_plugin_options() {
        $options_to_delete = array(
            // Plugin-specific options
            'easy_checkout_original_page_id',
            'easy_checkout_custom_page_id',
            'easy_checkout_activation_notice',
            
            // Any settings that might have been added
            'easy_checkout_settings',
            'easy_checkout_version',
            'easy_checkout_db_version',
            
            // Cache and temporary options
            'easy_checkout_cache_cleared',
            'easy_checkout_last_updated',
        );
        
        foreach ($options_to_delete as $option) {
            delete_option($option);
        }
        
        // Also clean up any options that might have been created with prefixes
        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                'easy_checkout_%'
            )
        );
        
        error_log('Easy Checkout: Cleaned up plugin options');
    }
    
    /**
     * Clean up any user meta data related to the plugin
     */
    private static function cleanup_user_meta() {
        global $wpdb;
        
        // Remove any user meta keys that start with 'easy_checkout_'
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
                'easy_checkout_%'
            )
        );
        
        error_log('Easy Checkout: Cleaned up user meta data');
    }
    
    /**
     * Clean up transients and cached data
     */
    private static function cleanup_transients() {
        global $wpdb;
        
        // Remove transients related to the plugin
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_easy_checkout_%',
                '_transient_timeout_easy_checkout_%'
            )
        );
        
        // Clean up any object cache
        wp_cache_flush();
        
        error_log('Easy Checkout: Cleaned up transients and cache');
    }
    
    /**
     * Optional: Clean up custom database tables (if any were created)
     * Uncomment and modify if you added custom tables
     */
    /*
    private static function cleanup_custom_tables() {
        global $wpdb;
        
        // Example: Drop custom table if it exists
        // $table_name = $wpdb->prefix . 'easy_checkout_data';
        // $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
        
        error_log('Easy Checkout: Cleaned up custom database tables');
    }
    */
    
    /**
     * Optional: Export user data before deletion (GDPR compliance)
     * Uncomment if you need to provide data export functionality
     */
    /*
    private static function export_user_data() {
        // Create a backup of user data before deletion
        $export_data = array(
            'options' => array(),
            'user_meta' => array(),
            'timestamp' => current_time('mysql')
        );
        
        // Store export data temporarily or email to admin
        // Implementation depends on your requirements
        
        error_log('Easy Checkout: User data exported before uninstall');
    }
    */
}

// Execute the uninstall process
Easy_Checkout_Uninstaller::uninstall();

// Final cleanup message
error_log('Easy Checkout: Plugin uninstall completed successfully');