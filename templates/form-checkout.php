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
                        <?php woocommerce_order_review(); ?>
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