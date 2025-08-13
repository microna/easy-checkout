<?php
/**
 * Custom Checkout form template - Simplified Version
 * templates/checkout/form-checkout.php
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_checkout_form', $checkout);

// If checkout registration is disabled and not logged in, the user cannot checkout.
if (!$checkout->is_registration_enabled() && $checkout->is_registration_required() && !is_user_logged_in()) {
    echo esc_html(apply_filters('woocommerce_checkout_must_be_logged_in_message', __('You must be logged in to checkout.', 'woocommerce')));
    return;
}
?>

<div class="easy-checkout-wrapper">
    <h2 class="checkout-title"><?php _e('Easy Checkout', 'easy-checkout'); ?></h2>

    <!-- Product Selection Section -->
    <?php if (WC()->cart->is_empty()) : ?>
    <div class="product-selection-section">
        <h3 class="section-title"><?php _e('Select Products', 'easy-checkout'); ?></h3>
        <div class="products-grid" id="products-grid">
            <?php
            // Get published products
            $products = wc_get_products(array(
                'status' => 'publish',
                'limit' => 12,
                'orderby' => 'popularity'
            ));
            
            foreach ($products as $product) :
                $product_id = $product->get_id();
                $product_name = $product->get_name();
                $product_price = $product->get_price_html();
                $product_image = $product->get_image('thumbnail');
                $add_to_cart_url = $product->add_to_cart_url();
            ?>
            <div class="product-item" data-product-id="<?php echo esc_attr($product_id); ?>">
                <div class="product-image">
                    <?php echo $product_image; ?>
                </div>
                <div class="product-details">
                    <h4 class="product-title"><?php echo esc_html($product_name); ?></h4>
                    <div class="product-price"><?php echo $product_price; ?></div>
                    <div class="quantity-controls">
                        <button type="button" class="qty-btn minus" data-action="decrease">-</button>
                        <input type="number" class="product-quantity" value="0" min="0" max="99"
                            data-product-id="<?php echo esc_attr($product_id); ?>">
                        <button type="button" class="qty-btn plus" data-action="increase">+</button>
                    </div>
                    <button type="button" class="add-to-cart-btn" data-product-id="<?php echo esc_attr($product_id); ?>"
                        style="display: none;">
                        <?php _e('Add to Cart', 'easy-checkout'); ?>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="cart-summary" id="cart-summary" style="display: none;">
            <h4><?php _e('Cart Summary', 'easy-checkout'); ?></h4>
            <div class="cart-items" id="cart-items"></div>
            <div class="cart-total" id="cart-total"></div>
            <button type="button" class="proceed-to-checkout-btn" id="proceed-to-checkout">
                <?php _e('Proceed to Checkout', 'easy-checkout'); ?>
            </button>
        </div>
    </div>
    <?php endif; ?>

    <form name="checkout" method="post" class="checkout woocommerce-checkout"
        <?php echo WC()->cart->is_empty() ? 'style="display: none;"' : ''; ?>
        action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data">

        <div class="checkout-container">
            <!-- Customer Details Section -->
            <div class="checkout-main">
                <?php if ($checkout->get_checkout_fields()) : ?>

                <div class="checkout-section customer-details">
                    <h3 class="section-title"><?php _e('Your Details', 'easy-checkout'); ?></h3>

                    <div class="customer-fields">
                        <?php
                            $fields = $checkout->get_checkout_fields('billing');
                            foreach ($fields as $key => $field) {
                                woocommerce_form_field($key, $field, $checkout->get_value($key));
                            }
                            ?>
                    </div>
                </div>

                <?php if (WC()->cart->needs_shipping() && WC()->cart->show_shipping()) : ?>
                <div class="checkout-section shipping-methods">
                    <h3 class="section-title"><?php _e('Shipping Method', 'easy-checkout'); ?></h3>

                    <div class="shipping-methods-wrapper">
                        <?php wc_cart_totals_shipping_html(); ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php endif; ?>
            </div>

            <!-- Order Summary Section -->
            <div class="checkout-sidebar">
                <div class="order-summary">
                    <h3 class="section-title"><?php _e('Order Summary', 'easy-checkout'); ?></h3>

                    <div class="order-details">
                        <?php woocommerce_order_review(); ?>
                    </div>

                    <!-- Payment Methods Section -->
                    <?php if (WC()->cart->needs_payment()) : ?>
                    <div class="checkout-section payment-methods">
                        <h3 class="section-title"><?php _e('Choose Payment Method', 'easy-checkout'); ?></h3>

                        <div class="payment-methods-wrapper">
                            <div id="payment" class="woocommerce-checkout-payment">
                                <?php
                                // Get available payment gateways
                                $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
                                
                                if (!empty($available_gateways)) {
                                    echo '<div class="payment-methods-list">';
                                    echo '<p class="payment-instruction">' . __('Please select your preferred payment method:', 'easy-checkout') . '</p>';
                                    
                                    // Display payment methods
                                    woocommerce_checkout_payment();
                                    
                                    echo '</div>';
                                } else {
                                    echo '<div class="no-payment-methods">';
                                    echo '<p>' . __('No payment methods are currently available. Please contact us for assistance.', 'easy-checkout') . '</p>';
                                    echo '</div>';
                                }
                                ?>
                            </div>

                            <div class="payment-security-notice">
                                <div class="security-icons">
                                    <span
                                        class="security-text"><?php _e('Your payment information is secure and encrypted', 'easy-checkout'); ?></span>
                                    <div class="security-badges">
                                        <span class="badge ssl"><?php _e('SSL Secure', 'easy-checkout'); ?></span>
                                        <span
                                            class="badge encrypted"><?php _e('256-bit Encryption', 'easy-checkout'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php do_action('woocommerce_checkout_after_order_review'); ?>
    </form>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Simple email validation
    $('#billing_email').on('blur', function() {
        var email = $(this).val();
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        var $field = $(this);
        var $wrapper = $field.closest('.form-row');

        // Remove previous validation classes
        $wrapper.removeClass('field-valid field-invalid');

        if (email && !emailRegex.test(email)) {
            $wrapper.addClass('field-invalid');
            if (!$wrapper.find('.field-error').length) {
                $wrapper.append(
                    '<span class="field-error"><?php _e('Please enter a valid email address.', 'easy-checkout'); ?></span>'
                );
            }
        } else if (email) {
            $wrapper.addClass('field-valid');
            $wrapper.find('.field-error').remove();
        }
    });

    // Form submission validation
    $('.checkout').on('submit', function(e) {
        var isValid = true;
        var firstError = null;

        // Check required fields
        $(this).find('[required]').each(function() {
            var $field = $(this);
            var $wrapper = $field.closest('.form-row');

            if (!$field.val().trim()) {
                isValid = false;
                $wrapper.addClass('field-invalid');

                if (!$wrapper.find('.field-error').length) {
                    $wrapper.append(
                        '<span class="field-error"><?php _e('This field is required.', 'easy-checkout'); ?></span>'
                    );
                }

                if (!firstError) {
                    firstError = $wrapper;
                }
            }
        });

        // Email validation
        var email = $('#billing_email').val();
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (email && !emailRegex.test(email)) {
            isValid = false;
            var $emailWrapper = $('#billing_email').closest('.form-row');
            $emailWrapper.addClass('field-invalid');

            if (!firstError) {
                firstError = $emailWrapper;
            }
        }

        if (!isValid) {
            e.preventDefault();

            if (firstError) {
                $('html, body').animate({
                    scrollTop: firstError.offset().top - 100
                }, 500);
            }

            return false;
        }
    });
});
</script>

<?php do_action('woocommerce_after_checkout_form', $checkout); ?>