<?php
/**
 * Frontend Class
 * 
 * Handles frontend functionality
 * 
 * @package Easy_Checkout
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

class Easy_Checkout_Frontend {
    
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
        add_action('wp_head', array($this, 'add_inline_styles'));
        add_action('wp_footer', array($this, 'add_inline_scripts'));
        add_filter('body_class', array($this, 'add_body_class'));
    }
    
    /**
     * Add inline styles
     */
    public function add_inline_styles() {
        if (!is_checkout()) {
            return;
        }
        
        $options = get_option('easy_checkout_options', array());
        $primary_color = isset($options['primary_color']) ? $options['primary_color'] : '#007cba';
        
        ?>
        <style id="easy-checkout-inline-styles">
            .easy-checkout .quantity-btn {
                background-color: <?php echo esc_attr($primary_color); ?>;
            }
            .easy-checkout .quantity-btn:hover {
                background-color: <?php echo esc_attr($this->darken_color($primary_color, 20)); ?>;
            }
            .easy-checkout #proceed-to-checkout {
                background-color: <?php echo esc_attr($primary_color); ?>;
            }
            .easy-checkout #proceed-to-checkout:hover {
                background-color: <?php echo esc_attr($this->darken_color($primary_color, 10)); ?>;
            }
        </style>
        <?php
    }
    
    /**
     * Add inline scripts
     */
    public function add_inline_scripts() {
        if (!is_checkout()) {
            return;
        }
        
        ?>
        <script id="easy-checkout-inline-scripts">
            // Initialize Easy Checkout when DOM is ready
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof EasyCheckout !== 'undefined') {
                    console.log('Easy Checkout Frontend initialized');
                }
            });
        </script>
        <?php
    }
    
    /**
     * Add body class
     */
    public function add_body_class($classes) {
        if (is_checkout()) {
            $classes[] = 'easy-checkout-active';
        }
        
        return $classes;
    }
    
    /**
     * Darken a hex color
     * 
     * @param string $hex_color
     * @param int $percent
     * @return string
     */
    private function darken_color($hex_color, $percent) {
        $hex_color = ltrim($hex_color, '#');
        
        if (strlen($hex_color) == 3) {
            $hex_color = str_repeat(substr($hex_color, 0, 1), 2) . 
                        str_repeat(substr($hex_color, 1, 1), 2) . 
                        str_repeat(substr($hex_color, 2, 1), 2);
        }
        
        $color_parts = array_map('hexdec', str_split($hex_color, 2));
        
        foreach ($color_parts as &$color) {
            $color = max(0, min(255, $color - ($color * $percent / 100)));
        }
        
        return '#' . implode('', array_map(function($color) {
            return str_pad(dechex($color), 2, '0', STR_PAD_LEFT);
        }, $color_parts));
    }
    
    /**
     * Get frontend settings
     */
    public function get_frontend_settings() {
        $options = get_option('easy_checkout_options', array());
        
        return array(
            'primary_color' => isset($options['primary_color']) ? $options['primary_color'] : '#007cba',
            'enable_quantity_controls' => isset($options['enable_quantity_controls']) ? $options['enable_quantity_controls'] : 1,
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('custom_checkout_nonce'),
            'debug' => defined('WP_DEBUG') && WP_DEBUG,
            'version' => EASY_CHECKOUT_VERSION,
        );
    }
}