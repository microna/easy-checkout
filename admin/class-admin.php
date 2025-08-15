<?php
/**
 * Admin Class
 * 
 * Handles admin functionality
 * 
 * @package Easy_Checkout
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

class Easy_Checkout_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Easy Checkout', 'easy-checkout'),
            __('Easy Checkout', 'easy-checkout'),
            'manage_options',
            'easy-checkout',
            array($this, 'admin_page'),
            'dashicons-cart',
            56
        );
        
        add_submenu_page(
            'easy-checkout',
            __('Settings', 'easy-checkout'),
            __('Settings', 'easy-checkout'),
            'manage_options',
            'easy-checkout-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        ?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <div class="easy-checkout-admin-content">
        <div class="postbox">
            <h2 class="hndle"><?php _e('Easy Checkout Overview', 'easy-checkout'); ?></h2>
            <div class="inside">
                <p><?php _e('Easy Checkout simplifies your WooCommerce checkout process with advanced cart management and quantity controls.', 'easy-checkout'); ?>
                </p>

                <h3><?php _e('Features', 'easy-checkout'); ?></h3>
                <ul>
                    <li><?php _e('Simplified checkout form with only essential fields', 'easy-checkout'); ?></li>
                    <li><?php _e('Real-time cart quantity controls', 'easy-checkout'); ?></li>
                    <li><?php _e('AJAX-powered cart updates', 'easy-checkout'); ?></li>
                    <li><?php _e('Mobile-responsive design', 'easy-checkout'); ?></li>
                    <li><?php _e('HPOS (High-Performance Order Storage) compatible', 'easy-checkout'); ?></li>
                </ul>

                <p>
                    <a href="<?php echo admin_url('admin.php?page=easy-checkout-settings'); ?>"
                        class="button button-primary">
                        <?php _e('Configure Settings', 'easy-checkout'); ?>
                    </a>
                </p>
            </div>
        </div>

        <div class="postbox">
            <h2 class="hndle"><?php _e('System Status', 'easy-checkout'); ?></h2>
            <div class="inside">
                <?php $this->display_system_status(); ?>
            </div>
        </div>
    </div>
</div>
<?php
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        ?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <form method="post" action="options.php">
        <?php
                settings_fields('easy_checkout_settings');
                do_settings_sections('easy_checkout_settings');
                submit_button();
                ?>
    </form>
</div>
<?php
    }
    
    /**
     * Initialize settings
     */
    public function init_settings() {
        register_setting(
            'easy_checkout_settings',
            'easy_checkout_options',
            array($this, 'sanitize_settings')
        );
        
        add_settings_section(
            'easy_checkout_general',
            __('General Settings', 'easy-checkout'),
            array($this, 'general_section_callback'),
            'easy_checkout_settings'
        );
        
        add_settings_field(
            'primary_color',
            __('Primary Color', 'easy-checkout'),
            array($this, 'color_field_callback'),
            'easy_checkout_settings',
            'easy_checkout_general',
            array('field' => 'primary_color')
        );
        
        add_settings_field(
            'enable_quantity_controls',
            __('Enable Quantity Controls', 'easy-checkout'),
            array($this, 'checkbox_field_callback'),
            'easy_checkout_settings',
            'easy_checkout_general',
            array('field' => 'enable_quantity_controls')
        );
    }
    
    /**
     * General section callback
     */
    public function general_section_callback() {
        echo '<p>' . __('Configure the general settings for Easy Checkout.', 'easy-checkout') . '</p>';
    }
    
    /**
     * Color field callback
     */
    public function color_field_callback($args) {
        $options = get_option('easy_checkout_options');
        $value = isset($options[$args['field']]) ? $options[$args['field']] : '#007cba';
        echo '<input type="color" name="easy_checkout_options[' . $args['field'] . ']" value="' . esc_attr($value) . '" />';
    }
    
    /**
     * Checkbox field callback
     */
    public function checkbox_field_callback($args) {
        $options = get_option('easy_checkout_options');
        $checked = isset($options[$args['field']]) ? $options[$args['field']] : 1;
        echo '<input type="checkbox" name="easy_checkout_options[' . $args['field'] . ']" value="1" ' . checked(1, $checked, false) . ' />';
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        if (isset($input['primary_color'])) {
            $sanitized['primary_color'] = sanitize_hex_color($input['primary_color']);
        }
        
        if (isset($input['enable_quantity_controls'])) {
            $sanitized['enable_quantity_controls'] = 1;
        } else {
            $sanitized['enable_quantity_controls'] = 0;
        }
        
        return $sanitized;
    }
    
    /**
     * Display system status
     */
    private function display_system_status() {
        $status = array(
            'WordPress' => get_bloginfo('version'),
            'WooCommerce' => class_exists('WooCommerce') ? WC()->version : __('Not installed', 'easy-checkout'),
            'PHP' => PHP_VERSION,
            'Easy Checkout' => EASY_CHECKOUT_VERSION,
        );
        
        echo '<table class="widefat">';
        echo '<thead><tr><th>' . __('Component', 'easy-checkout') . '</th><th>' . __('Version', 'easy-checkout') . '</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($status as $component => $version) {
            echo '<tr>';
            echo '<td>' . esc_html($component) . '</td>';
            echo '<td>' . esc_html($version) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
    }
}