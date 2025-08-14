/**
 * Cart Manager Module
 *
 * Handles cart operations and updates
 *
 * @package Easy_Checkout
 * @version 2.0.0
 */

const EasyCheckoutCartManager = {
  /**
   * Cart data
   */
  cart: {
    items: [],
    total: 0,
    count: 0,
  },

  /**
   * Initialize cart manager
   */
  init: function () {
    console.log("CartManager: Initializing...");
    this.bindEvents();
    this.loadCartData();
    this.hookCheckoutUpdates();
    console.log("CartManager: Initialized");
  },

  /**
   * Bind cart events
   */
  bindEvents: function () {
    const self = this;
    console.log("CartManager: Binding events...");

    // Enhanced event binding with multiple selectors
    const removeSelectors =
      ".remove-cart-item, .remove-item, button[onclick*='easyCheckoutRemoveItem']";
    const increaseSelectors =
      ".increase-qty, .qty-increase, button[onclick*='easyCheckoutIncreaseQuantity']";
    const decreaseSelectors =
      ".decrease-qty, .qty-decrease, button[onclick*='easyCheckoutDecreaseQuantity']";

    // Cart item removal - multiple selectors
    $(document).on("click", removeSelectors, function (e) {
      console.log("CartManager: Remove button clicked (enhanced binding)");
      e.preventDefault();
      e.stopPropagation();

      let cartItemKey = $(this).data("cart-item-key");
      if (!cartItemKey) {
        // Try to extract from onclick attribute
        const onclick = $(this).attr("onclick");
        if (onclick) {
          const match = onclick.match(
            /easyCheckoutRemoveItem\(['"]([^'"]+)['"]\)/
          );
          if (match) {
            cartItemKey = match[1];
          }
        }
      }

      if (cartItemKey) {
        self.removeItem(cartItemKey);
      } else {
        console.error("No cart item key found for remove button");
      }
    });

    // Quantity controls - increase
    $(document).on("click", increaseSelectors, function (e) {
      console.log(
        "CartManager: Increase quantity button clicked (enhanced binding)"
      );
      e.preventDefault();
      e.stopPropagation();

      let cartItemKey = $(this).data("cart-item-key");
      if (!cartItemKey) {
        // Try to extract from onclick attribute
        const onclick = $(this).attr("onclick");
        if (onclick) {
          const match = onclick.match(
            /easyCheckoutIncreaseQuantity\(['"]([^'"]+)['"]\)/
          );
          if (match) {
            cartItemKey = match[1];
          }
        }
      }

      if (cartItemKey) {
        self.updateQuantity(cartItemKey, 1);
      } else {
        console.error("No cart item key found for increase button");
      }
    });

    // Quantity controls - decrease
    $(document).on("click", decreaseSelectors, function (e) {
      console.log(
        "CartManager: Decrease quantity button clicked (enhanced binding)"
      );
      e.preventDefault();
      e.stopPropagation();

      let cartItemKey = $(this).data("cart-item-key");
      if (!cartItemKey) {
        // Try to extract from onclick attribute
        const onclick = $(this).attr("onclick");
        if (onclick) {
          const match = onclick.match(
            /easyCheckoutDecreaseQuantity\(['"]([^'"]+)['"]\)/
          );
          if (match) {
            cartItemKey = match[1];
          }
        }
      }

      if (cartItemKey) {
        self.updateQuantity(cartItemKey, -1);
      } else {
        console.error("No cart item key found for decrease button");
      }
    });

    // Proceed to checkout
    $(document).on("click", "#proceed-to-checkout", function (e) {
      e.preventDefault();
      self.proceedToCheckout();
    });
  },

  /**
   * Load cart data
   */
  loadCartData: function () {
    const self = this;

    if ($("#cart-summary").length === 0) return;

    $.ajax({
      url: easy_checkout_params.ajax_url,
      type: "POST",
      data: {
        action: "easy_checkout_get_cart",
        nonce: easy_checkout_params.nonce,
      },
      success: function (response) {
        if (response.success) {
          self.updateCartDisplay(response.data);
          if (!response.data.is_empty) {
            self.showCheckoutForm();
          }
        }
      },
      error: function (xhr, status, error) {
        console.error("Failed to load cart data:", error);
      },
    });
  },

  /**
   * Update item quantity
   */
  updateQuantity: function (cartItemKey, change) {
    const self = this;

    // Find current quantity
    const cartItem = this.cart.cart_items.find(
      (item) => item.key === cartItemKey
    );
    if (!cartItem) return;

    const newQuantity = Math.max(1, cartItem.quantity + change);

    // Disable buttons
    const $increaseBtn = $(
      `.increase-qty[data-cart-item-key="${cartItemKey}"]`
    );
    const $decreaseBtn = $(
      `.decrease-qty[data-cart-item-key="${cartItemKey}"]`
    );

    $increaseBtn.prop("disabled", true);
    $decreaseBtn.prop("disabled", true);

    $.ajax({
      url: easy_checkout_params.ajax_url,
      type: "POST",
      data: {
        action: "easy_checkout_update_cart_quantity",
        cart_item_key: cartItemKey,
        quantity: newQuantity,
        nonce: easy_checkout_params.nonce,
      },
      success: function (response) {
        if (response.success) {
          self.updateCartDisplay(response.data);
          self.refreshCheckoutReview();
          EasyCheckoutNotifications.show(
            easy_checkout_params.i18n.cart_updated,
            "success"
          );
        } else {
          EasyCheckoutNotifications.show(
            response.data || easy_checkout_params.i18n.update_failed,
            "error"
          );
        }
      },
      error: function (xhr, status, error) {
        EasyCheckoutNotifications.show(
          easy_checkout_params.i18n.update_failed,
          "error"
        );
        console.error("Update quantity error:", error);
      },
      complete: function () {
        $increaseBtn.prop("disabled", false);
        $decreaseBtn.prop("disabled", false);
      },
    });
  },

  /**
   * Remove item from cart
   */
  removeItem: function (cartItemKey) {
    const self = this;
    const $btn = $(`.remove-cart-item[data-cart-item-key="${cartItemKey}"]`);

    $btn.prop("disabled", true);

    $.ajax({
      url: easy_checkout_params.ajax_url,
      type: "POST",
      data: {
        action: "easy_checkout_remove_from_cart",
        cart_item_key: cartItemKey,
        nonce: easy_checkout_params.nonce,
      },
      success: function (response) {
        if (response.success) {
          self.updateCartDisplay(response.data);
          self.refreshCheckoutReview();
          EasyCheckoutNotifications.show(
            easy_checkout_params.i18n.item_removed,
            "success"
          );

          if (response.data.cart_count === 0) {
            self.hideCheckoutForm();
          }
        } else {
          EasyCheckoutNotifications.show(
            response.data || easy_checkout_params.i18n.remove_failed,
            "error"
          );
        }
      },
      error: function (xhr, status, error) {
        EasyCheckoutNotifications.show(
          easy_checkout_params.i18n.remove_failed,
          "error"
        );
        console.error("Remove from cart error:", error);
      },
      complete: function () {
        $btn.prop("disabled", false);
      },
    });
  },

  /**
   * Update cart display
   */
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
                            <div class="item-quantity-controls">
                                <button type="button" class="quantity-btn decrease-qty" data-cart-item-key="${
                                  item.key
                                }" ${
          item.quantity <= 1 ? "disabled" : ""
        }>-</button>
                                <span class="item-quantity">${
                                  item.quantity
                                }</span>
                                <button type="button" class="quantity-btn increase-qty" data-cart-item-key="${
                                  item.key
                                }">+</button>
                            </div>
                        </div>
                        <div class="item-actions">
                            <span class="item-subtotal">${item.subtotal}</span>
                            <button type="button" class="remove-cart-item" data-cart-item-key="${
                              item.key
                            }" title="Remove item">
                                <span class="remove-icon">×</span>
                            </button>
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

  /**
   * Refresh checkout review
   */
  refreshCheckoutReview: function () {
    if (typeof wc_checkout_params !== "undefined") {
      $("body").trigger("update_checkout");
    }
  },

  /**
   * Proceed to checkout
   */
  proceedToCheckout: function () {
    if (this.cart.cart_count === 0) {
      EasyCheckoutNotifications.show(
        easy_checkout_params.i18n.cart_empty,
        "error"
      );
      return;
    }

    this.showCheckoutForm();
  },

  /**
   * Show checkout form
   */
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

  /**
   * Hide checkout form
   */
  hideCheckoutForm: function () {
    $(".checkout.woocommerce-checkout").hide();
    $(".product-selection-section").show();
  },

  /**
   * Hook into WooCommerce checkout updates
   */
  hookCheckoutUpdates: function () {
    const self = this;

    // Prevent WooCommerce from replacing our custom order review
    $(document.body).on("updated_checkout", function () {
      console.log(
        "CartManager: Checkout updated, preserving custom order review"
      );
      // Re-render our custom order review after WooCommerce updates
      self.preserveCustomOrderReview();
    });

    // Also hook into checkout form updates
    $(document).on("checkout_error", function () {
      console.log(
        "CartManager: Checkout error, preserving custom order review"
      );
      self.preserveCustomOrderReview();
    });
  },

  /**
   * Preserve our custom order review after WooCommerce updates
   */
  preserveCustomOrderReview: function () {
    // If WooCommerce has replaced our custom order review, we need to restore it
    const orderReviewTable = $(".woocommerce-checkout-review-order-table");

    if (
      orderReviewTable.length &&
      !orderReviewTable.find(".quantity-controls").length
    ) {
      console.log(
        "CartManager: Custom order review was replaced, restoring..."
      );
      // Trigger a custom event to re-render our order review
      this.restoreCustomOrderReview();
    }
  },

  /**
   * Restore custom order review
   */
  restoreCustomOrderReview: function () {
    // Make an AJAX call to get our custom order review HTML
    $.ajax({
      url: easy_checkout_params.ajax_url,
      type: "POST",
      data: {
        action: "easy_checkout_get_custom_order_review",
        nonce: easy_checkout_params.nonce,
      },
      success: function (response) {
        if (response.success && response.data.html) {
          $(".woocommerce-checkout-review-order-table").replaceWith(
            response.data.html
          );
          console.log("CartManager: Custom order review restored");
        }
      },
    });
  },
};
