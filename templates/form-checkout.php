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
    <h2 class="checkout-title"><?php _e('Checkout', 'easy-checkout'); ?></h2>

    <!-- Cart Review Section (Separate) -->
    <?php if (!WC()->cart->is_empty()) : ?>
    <div class="easy-checkout-cart-section">
        <h3 class="section-title"><?php _e('Review Your Items', 'easy-checkout'); ?></h3>
        
        <div class="cart-items-wrapper">
            <?php
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                $product = $cart_item['data'];
                if (!$product || !$product->exists()) {
                    continue;
                }
                
                $product_name = $product->get_name();
                $quantity = $cart_item['quantity'];
                $product_price = wc_price($product->get_price());
                $line_total = wc_price($cart_item['line_total']);
                $product_image = $product->get_image('thumbnail');
                
                // Get max quantity
                $max_qty = $product && is_object($product) && method_exists($product, 'get_max_purchase_quantity')
                    ? (int) $product->get_max_purchase_quantity()
                    : 99;
                if ($max_qty <= 0) {
                    $max_qty = $quantity;
                }
                ?>
                <div class="cart-review-item" data-cart-item-key="<?php echo esc_attr($cart_item_key); ?>">
                    <div class="item-image">
                        <?php echo $product_image; ?>
                    </div>
                    <div class="item-details">
                        <div class="item-name"><?php echo esc_html($product_name); ?></div>
                        <div class="item-price"><?php echo $product_price; ?> <?php _e('each', 'easy-checkout'); ?></div>
                        <div class="item-quantity-controls">
                            <label for="qty-<?php echo esc_attr($cart_item_key); ?>"><?php _e('Quantity:', 'easy-checkout'); ?></label>
                            <div class="ec-quantity-controls" data-cart-item-key="<?php echo esc_attr($cart_item_key); ?>" data-max-qty="<?php echo esc_attr($max_qty); ?>">
                                <input type="number" 
                                       id="qty-<?php echo esc_attr($cart_item_key); ?>"
                                       class="ec-cart-qty-input" 
                                       data-cart-item-key="<?php echo esc_attr($cart_item_key); ?>" 
                                       min="1" 
                                       max="<?php echo esc_attr($max_qty); ?>" 
                                       value="<?php echo esc_attr($quantity); ?>">
                                <button type="button" class="ec-remove-item" data-cart-item-key="<?php echo esc_attr($cart_item_key); ?>" aria-label="<?php esc_attr_e('Remove item', 'easy-checkout'); ?>">
                                    <span class="remove-icon">&times;</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="item-total">
                        <div class="total-label"><?php _e('Subtotal', 'easy-checkout'); ?></div>
                        <div class="total-amount"><?php echo $line_total; ?></div>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
        
        <div class="cart-summary-totals">
            <div class="totals-row subtotal-row">
                <span class="label"><?php _e('Subtotal:', 'easy-checkout'); ?></span>
                <span class="amount"><?php echo WC()->cart->get_cart_subtotal(); ?></span>
            </div>
            <?php if (WC()->cart->get_cart_tax()) : ?>
            <div class="totals-row tax-row">
                <span class="label"><?php _e('Tax:', 'easy-checkout'); ?></span>
                <span class="amount"><?php echo wc_price(WC()->cart->get_cart_tax()); ?></span>
            </div>
            <?php endif; ?>
            <div class="totals-row total-row">
                <span class="label"><?php _e('Total:', 'easy-checkout'); ?></span>
                <span class="amount"><?php echo WC()->cart->get_total(); ?></span>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <form name="checkout" method="post" class="checkout woocommerce-checkout"
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

                <?php endif; ?>
            </div>

            <!-- Order Summary Section -->
            <div class="checkout-sidebar">
                <div class="order-summary">
                    <h3 class="section-title"><?php _e('Order Summary', 'easy-checkout'); ?></h3>

                    <div class="order-details">
                        <?php 
                        // Simple order summary without quantity controls (since they're above)
                        if (!WC()->cart->is_empty()) : ?>
                            <div class="order-summary-simple">
                                <div class="summary-totals">
                                    <div class="summary-row">
                                        <span class="label"><?php _e('Items:', 'easy-checkout'); ?></span>
                                        <span class="value"><?php echo WC()->cart->get_cart_contents_count(); ?></span>
                                    </div>
                                    <div class="summary-row">
                                        <span class="label"><?php _e('Subtotal:', 'easy-checkout'); ?></span>
                                        <span class="value"><?php echo WC()->cart->get_cart_subtotal(); ?></span>
                                    </div>
                                    <?php if (WC()->cart->get_cart_tax()) : ?>
                                    <div class="summary-row">
                                        <span class="label"><?php _e('Tax:', 'easy-checkout'); ?></span>
                                        <span class="value"><?php echo wc_price(WC()->cart->get_cart_tax()); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <div class="summary-row total-row">
                                        <span class="label"><?php _e('Total:', 'easy-checkout'); ?></span>
                                        <span class="value"><?php echo WC()->cart->get_total(); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php else : ?>
                            <p class="woocommerce-info"><?php _e('Your cart is currently empty.', 'easy-checkout'); ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Payment Methods Section -->
                    <?php if (WC()->cart->needs_payment()) : ?>
                    <div class="payment-section">
                        <h3 class="section-title"><?php _e('Payment Method', 'easy-checkout'); ?></h3>

                        <div id="payment" class="woocommerce-checkout-payment">
                            <?php woocommerce_checkout_payment(); ?>
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