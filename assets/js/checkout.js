/* Easy Checkout JavaScript - Simplified Version */

jQuery(document).ready(function ($) {
  "use strict";

  const EasyCheckout = {
    cart: {
      items: [],
      total: 0,
      count: 0,
    },

    init: function () {
      this.bindEvents();
      this.initValidation();
      this.initProductSelection();
      this.loadCartData();
    },

    bindEvents: function () {
      // Email validation on blur
      $("#billing_email").on("blur", this.validateEmail.bind(this));

      // Form submission validation (disabled to prevent conflicts with WooCommerce)
      // $(".checkout").on("submit", this.validateForm.bind(this));

      // Real-time field validation
      $(".checkout input[required]").on(
        "blur",
        this.validateRequiredField.bind(this)
      );

      // Clear error messages on focus
      $(".checkout input").on("focus", this.clearFieldError.bind(this));

      // Product selection events
      $(document).on("click", ".qty-btn", this.handleQuantityChange.bind(this));
      $(document).on(
        "change",
        ".product-quantity",
        this.handleQuantityInputChange.bind(this)
      );
      $(document).on("click", ".add-to-cart-btn", this.addToCart.bind(this));
      $(document).on(
        "click",
        ".remove-cart-item",
        this.removeFromCart.bind(this)
      );
      $(document).on(
        "click",
        "#proceed-to-checkout",
        this.proceedToCheckout.bind(this)
      );

      // Payment method events
      $(document).on(
        "change",
        'input[name="payment_method"]',
        this.handlePaymentMethodChange.bind(this)
      );
      // $(document).on("click", "#place_order", this.validatePayment.bind(this));
    },

    initProductSelection: function () {
      // Initialize quantity controls
      $(".product-quantity").each(function () {
        const $input = $(this);
        const $item = $input.closest(".product-item");
        const $addBtn = $item.find(".add-to-cart-btn");

        if (parseInt($input.val()) > 0) {
          $addBtn.show();
        }
      });
    },

    loadCartData: function () {
      if ($("#cart-summary").length === 0) return;

      $.ajax({
        url: custom_checkout_params.ajax_url,
        type: "POST",
        data: {
          action: "easy_checkout_get_cart",
          nonce: custom_checkout_params.nonce,
        },
        success: (response) => {
          if (response.success) {
            this.updateCartDisplay(response.data);
            if (!response.data.is_empty) {
              this.showCheckoutForm();
            }
          }
        },
        error: (xhr, status, error) => {
          console.error("Failed to load cart data:", error);
        },
      });
    },

    handleQuantityChange: function (e) {
      e.preventDefault();
      const $btn = $(e.target);
      const $item = $btn.closest(".product-item");
      const $input = $item.find(".product-quantity");
      const $addBtn = $item.find(".add-to-cart-btn");
      const action = $btn.data("action");
      let currentQty = parseInt($input.val()) || 0;

      if (action === "increase") {
        currentQty = Math.min(currentQty + 1, 99);
      } else if (action === "decrease") {
        currentQty = Math.max(currentQty - 1, 0);
      }

      $input.val(currentQty);

      if (currentQty > 0) {
        $addBtn.show();
      } else {
        $addBtn.hide();
      }
    },

    handleQuantityInputChange: function (e) {
      const $input = $(e.target);
      const $item = $input.closest(".product-item");
      const $addBtn = $item.find(".add-to-cart-btn");
      const qty = parseInt($input.val()) || 0;

      // Enforce min/max limits
      if (qty < 0) $input.val(0);
      if (qty > 99) $input.val(99);

      if (parseInt($input.val()) > 0) {
        $addBtn.show();
      } else {
        $addBtn.hide();
      }
    },

    addToCart: function (e) {
      e.preventDefault();
      const $btn = $(e.target);
      const productId = $btn.data("product-id");
      const $item = $btn.closest(".product-item");
      const $input = $item.find(".product-quantity");
      const quantity = parseInt($input.val()) || 1;

      if (quantity <= 0) {
        showNotification("Please select a quantity", "error");
        return;
      }

      $btn.prop("disabled", true).text("Adding...");

      $.ajax({
        url: custom_checkout_params.ajax_url,
        type: "POST",
        data: {
          action: "easy_checkout_add_to_cart",
          product_id: productId,
          quantity: quantity,
          nonce: custom_checkout_params.nonce,
        },
        success: (response) => {
          if (response.success) {
            this.updateCartDisplay(response.data);
            $input.val(0);
            $btn.hide().prop("disabled", false).text("Add to Cart");
            showNotification("Product added to cart!", "success");
          } else {
            showNotification(
              response.data || "Failed to add product to cart",
              "error"
            );
          }
        },
        error: (xhr, status, error) => {
          showNotification("Failed to add product to cart", "error");
          console.error("Add to cart error:", error);
        },
        complete: () => {
          $btn.prop("disabled", false).text("Add to Cart");
        },
      });
    },

    removeFromCart: function (e) {
      e.preventDefault();
      const $btn = $(e.target);
      const cartItemKey = $btn.data("cart-item-key");

      $btn.prop("disabled", true);

      $.ajax({
        url: custom_checkout_params.ajax_url,
        type: "POST",
        data: {
          action: "easy_checkout_remove_from_cart",
          cart_item_key: cartItemKey,
          nonce: custom_checkout_params.nonce,
        },
        success: (response) => {
          if (response.success) {
            this.updateCartDisplay(response.data);
            showNotification("Product removed from cart", "success");

            if (response.data.cart_count === 0) {
              this.hideCheckoutForm();
            }
          } else {
            showNotification(
              response.data || "Failed to remove product",
              "error"
            );
          }
        },
        error: (xhr, status, error) => {
          showNotification("Failed to remove product", "error");
          console.error("Remove from cart error:", error);
        },
        complete: () => {
          $btn.prop("disabled", false);
        },
      });
    },

    updateCartDisplay: function (cartData) {
      this.cart = cartData;
      const $cartSummary = $("#cart-summary");
      const $cartItems = $("#cart-items");
      const $cartTotal = $("#cart-total");

      if (cartData.cart_count > 0) {
        $cartSummary.show();

        // Update cart items
        let itemsHtml = "";
        cartData.cart_items.forEach((item) => {
          itemsHtml += `
              <div class="cart-item" data-cart-item-key="${item.key}">
                <div class="item-details">
                  <span class="item-name">${item.name}</span>
                  <span class="item-quantity">Qty: ${item.quantity}</span>
                </div>
                <div class="item-actions">
                  <span class="item-subtotal">${item.subtotal}</span>
                  <button type="button" class="remove-cart-item" data-cart-item-key="${item.key}">×</button>
                </div>
              </div>
            `;
        });
        $cartItems.html(itemsHtml);

        // Update total
        $cartTotal.html(`
            <div class="total-row">
              <span class="total-label">Total:</span>
              <span class="total-amount">${cartData.cart_total}</span>
            </div>
          `);
      } else {
        $cartSummary.hide();
      }
    },

    proceedToCheckout: function (e) {
      e.preventDefault();

      if (this.cart.cart_count === 0) {
        showNotification("Your cart is empty", "error");
        return;
      }

      this.showCheckoutForm();
    },

    showCheckoutForm: function () {
      $(".product-selection-section").hide();
      $(".checkout.woocommerce-checkout").show();

      // Scroll to checkout form
      $("html, body").animate(
        {
          scrollTop: $(".checkout.woocommerce-checkout").offset().top - 50,
        },
        500
      );
    },

    hideCheckoutForm: function () {
      $(".checkout.woocommerce-checkout").hide();
      $(".product-selection-section").show();
    },

    handlePaymentMethodChange: function (e) {
      const $radio = $(e.target);
      const paymentMethod = $radio.val();
      const $paymentBox = $(`.payment_box.payment_method_${paymentMethod}`);

      // Hide all payment boxes
      $(".payment_box").slideUp(300);

      // Show selected payment box
      if ($paymentBox.length) {
        $paymentBox.slideDown(300);
      }

      // Update place order button text if needed
      const orderButtonText = $radio.data("order_button_text");
      if (orderButtonText) {
        $("#place_order").text(orderButtonText);
      } else {
        $("#place_order").text("Place Order");
      }

      // Clear any previous payment errors
      this.clearPaymentErrors();
    },

    validatePayment: function (e) {
      const $selectedPayment = $('input[name="payment_method"]:checked');

      // Check if a payment method is selected
      if ($selectedPayment.length === 0) {
        e.preventDefault();
        this.showPaymentError("Please select a payment method.");
        this.scrollToPaymentMethods();
        return false;
      }

      // Validate payment method specific fields
      const paymentMethod = $selectedPayment.val();
      const $paymentBox = $(`.payment_box.payment_method_${paymentMethod}`);

      if ($paymentBox.is(":visible")) {
        const $requiredFields = $paymentBox.find(
          "input[required], select[required]"
        );
        let hasErrors = false;

        $requiredFields.each(function () {
          const $field = $(this);
          const value = $field.val().trim();

          if (!value) {
            hasErrors = true;
            $field.addClass("error");

            // Add field-specific error message
            if (!$field.siblings(".field-error").length) {
              $field.after(
                '<span class="field-error">This field is required.</span>'
              );
            }
          } else {
            $field.removeClass("error");
            $field.siblings(".field-error").remove();
          }
        });

        if (hasErrors) {
          e.preventDefault();
          this.showPaymentError("Please fill in all required payment fields.");
          this.scrollToPaymentMethods();
          return false;
        }
      }

      // Clear any payment errors if validation passed
      this.clearPaymentErrors();
    },

    showPaymentError: function (message) {
      const $paymentSection = $(".payment-methods");
      let $errorDiv = $paymentSection.find(".payment-error");

      if (!$errorDiv.length) {
        $errorDiv = $(
          '<div class="payment-error" style="background: #f8d7da; color: #721c24; padding: 12px; border: 1px solid #f5c6cb; border-radius: 4px; margin-bottom: 15px;"></div>'
        );
        $paymentSection.prepend($errorDiv);
      }

      $errorDiv.html(`<strong>Payment Error:</strong> ${message}`).show();

      // Auto-hide after 5 seconds
      setTimeout(() => {
        $errorDiv.fadeOut();
      }, 5000);
    },

    clearPaymentErrors: function () {
      $(".payment-error").remove();
      $(".payment_box .field-error").remove();
      $(".payment_box input.error, .payment_box select.error").removeClass(
        "error"
      );
    },

    scrollToPaymentMethods: function () {
      const $paymentSection = $(".payment-methods");
      if ($paymentSection.length) {
        $("html, body").animate(
          {
            scrollTop: $paymentSection.offset().top - 100,
          },
          500
        );
      }
    },

    initValidation: function () {
      // Add visual indicators to required fields
      $(".checkout input[required]").each(function () {
        const $field = $(this);
        const $label = $field.closest(".form-row").find("label");
        if (!$label.hasClass("required")) {
          $label.addClass("required");
        }
      });
    },

    validateEmail: function (e) {
      const $field = $(e.target);
      const email = $field.val().trim();
      const $wrapper = $field.closest(".form-row");

      // Clear previous validation
      $wrapper.removeClass("field-valid field-invalid");
      this.removeFieldError($field);

      if (email === "") {
        // Empty field - will be caught by required validation
        return;
      }

      if (this.isValidEmail(email)) {
        $wrapper.addClass("field-valid");
      } else {
        $wrapper.addClass("field-invalid");
        this.showFieldError($field, "Please enter a valid email address.");
      }
    },

    validateRequiredField: function (e) {
      const $field = $(e.target);
      const value = $field.val().trim();
      const $wrapper = $field.closest(".form-row");

      // Clear previous validation
      $wrapper.removeClass("field-valid field-invalid");
      this.removeFieldError($field);

      if (value === "") {
        $wrapper.addClass("field-invalid");
        this.showFieldError($field, "This field is required.");
      } else {
        $wrapper.addClass("field-valid");
      }
    },

    validateForm: function (e) {
      let isValid = true;
      let firstError = null;

      // Validate all required fields
      $(".checkout input[required]").each(function () {
        const $field = $(this);
        const value = $field.val().trim();
        const $wrapper = $field.closest(".form-row");

        if (value === "") {
          isValid = false;
          $wrapper.addClass("field-invalid");

          if (!$wrapper.find(".field-error").length) {
            EasyCheckout.showFieldError($field, "This field is required.");
          }

          if (!firstError) {
            firstError = $wrapper;
          }
        }
      });

      // Validate email format
      const $emailField = $("#billing_email");
      const email = $emailField.val().trim();
      const $emailWrapper = $emailField.closest(".form-row");

      if (email && !this.isValidEmail(email)) {
        isValid = false;
        $emailWrapper.addClass("field-invalid");

        if (!$emailWrapper.find(".field-error").length) {
          this.showFieldError(
            $emailField,
            "Please enter a valid email address."
          );
        }

        if (!firstError) {
          firstError = $emailWrapper;
        }
      }

      // If validation failed, prevent submission and scroll to first error
      if (!isValid) {
        e.preventDefault();

        if (firstError) {
          $("html, body").animate(
            {
              scrollTop: firstError.offset().top - 100,
            },
            500
          );
        }

        return false;
      }

      // Show loading state
      this.showLoadingState();
    },

    showFieldError: function ($field, message) {
      const $wrapper = $field.closest(".form-row");
      let $error = $wrapper.find(".field-error");

      if (!$error.length) {
        $error = $('<span class="field-error"></span>');
        $wrapper.append($error);
      }

      $error.text(message).show();
    },

    removeFieldError: function ($field) {
      $field.closest(".form-row").find(".field-error").remove();
    },

    clearFieldError: function (e) {
      const $field = $(e.target);
      const $wrapper = $field.closest(".form-row");

      $wrapper.removeClass("field-invalid");
      this.removeFieldError($field);
    },

    showLoadingState: function () {
      const $submitButton = $("#place_order");
      const $form = $(".checkout");

      $submitButton.prop("disabled", true).text("Processing Order...");
      $form.addClass("processing");
    },

    isValidEmail: function (email) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      return emailRegex.test(email);
    },
  };

  // Initialize when DOM is ready
  EasyCheckout.init();

  // Handle WooCommerce checkout errors
  $(document.body).on("checkout_error", function () {
    $(".checkout").removeClass("processing");
    $("#place_order").prop("disabled", false).text("Place Order");
  });

  // Handle successful checkout
  $(document.body).on("checkout_place_order_success", function () {
    $("#place_order").text("Order Placed Successfully!");
  });

  // Accessibility: Enter key navigation
  $(".checkout input").on("keydown", function (e) {
    if (e.keyCode === 13 && e.target.type !== "submit") {
      e.preventDefault();
      const $fields = $(".checkout input:visible");
      const currentIndex = $fields.index(this);
      const $nextField = $fields.eq(currentIndex + 1);

      if ($nextField.length) {
        $nextField.focus();
      } else {
        $("#place_order").focus();
      }
    }
  });
});

// Simple notification system for user feedback
function showNotification(message, type = "info") {
  const notification = jQuery(`
          <div class="easy-checkout-notification ${type}">
              ${message}
              <button class="notification-close">&times;</button>
          </div>
      `);

  jQuery("body").append(notification);

  setTimeout(() => {
    notification.addClass("show");
  }, 100);

  // Auto hide after 5 seconds
  setTimeout(() => {
    notification.removeClass("show");
    setTimeout(() => notification.remove(), 300);
  }, 5000);

  // Manual close
  notification.find(".notification-close").on("click", function () {
    notification.removeClass("show");
    setTimeout(() => notification.remove(), 300);
  });
}

// Add notification styles
jQuery(document).ready(function ($) {
  if (!$("#easy-checkout-notification-styles").length) {
    $('<style id="easy-checkout-notification-styles">')
      .html(
        `
              .easy-checkout-notification {
                  position: fixed;
                  top: 20px;
                  right: 20px;
                  background: #fff;
                  border-left: 4px solid #0073aa;
                  padding: 15px 20px;
                  border-radius: 0 6px 6px 0;
                  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
                  z-index: 10000;
                  transform: translateX(400px);
                  transition: transform 0.3s ease;
                  max-width: 350px;
                  font-size: 14px;
                  line-height: 1.4;
              }
              
              .easy-checkout-notification.show {
                  transform: translateX(0);
              }
              
              .easy-checkout-notification.error {
                  border-left-color: #e74c3c;
              }
              
              .easy-checkout-notification.success {
                  border-left-color: #27ae60;
              }
              
              .notification-close {
                  background: none;
                  border: none;
                  font-size: 18px;
                  cursor: pointer;
                  float: right;
                  margin-left: 10px;
                  color: #999;
                  padding: 0;
                  line-height: 1;
              }
              
              .notification-close:hover {
                  color: #333;
              }
          `
      )
      .appendTo("head");
  }
});
