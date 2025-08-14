/**
 * Quantity Controls Module
 *
 * Handles quantity controls for products and cart items
 *
 * @package Easy_Checkout
 * @version 2.0.0
 */

const EasyCheckoutQuantityControls = {
  /**
   * Initialize quantity controls
   */
  init: function () {
    this.bindEvents();
    this.initControls();
  },

  /**
   * Bind quantity control events
   */
  bindEvents: function () {
    const self = this;

    // Product quantity controls
    $(document).on("click", ".qty-btn", function (e) {
      e.preventDefault();
      self.handleProductQuantityChange($(this));
    });

    $(document).on("change", ".product-quantity", function (e) {
      self.handleQuantityInputChange($(this));
    });

    $(document).on("click", ".add-to-cart-btn", function (e) {
      e.preventDefault();
      self.addToCart($(this));
    });

    // Cart quantity controls are handled by CartManager
    // but we provide utility functions here
  },

  /**
   * Initialize quantity controls
   */
  initControls: function () {
    // Initialize product quantity controls
    $(".product-quantity").each(function () {
      const $input = $(this);
      const $item = $input.closest(".product-item");
      const $addBtn = $item.find(".add-to-cart-btn");

      if (parseInt($input.val()) > 0) {
        $addBtn.show();
      }
    });

    // Set up quantity input constraints
    $(".product-quantity").attr({
      min: 1,
      max: 999,
      step: 1,
    });
  },

  /**
   * Handle product quantity button clicks
   */
  handleProductQuantityChange: function ($btn) {
    const $item = $btn.closest(".product-item");
    const $input = $item.find(".product-quantity");
    const $addBtn = $item.find(".add-to-cart-btn");
    const isIncrease = $btn.hasClass("increase");

    let currentQty = parseInt($input.val()) || 0;
    let newQty;

    if (isIncrease) {
      newQty = Math.min(currentQty + 1, 999);
    } else {
      newQty = Math.max(currentQty - 1, 0);
    }

    $input.val(newQty);

    // Show/hide add to cart button
    if (newQty > 0) {
      $addBtn.show().text(`Add ${newQty} to Cart`);
    } else {
      $addBtn.hide();
    }

    // Update button states
    this.updateButtonStates($item, newQty);

    // Trigger change event
    $input.trigger("change");
  },

  /**
   * Handle quantity input changes
   */
  handleQuantityInputChange: function ($input) {
    const $item = $input.closest(".product-item");
    const $addBtn = $item.find(".add-to-cart-btn");
    let qty = parseInt($input.val()) || 0;

    // Validate quantity
    qty = Math.max(0, Math.min(qty, 999));
    $input.val(qty);

    // Show/hide add to cart button
    if (qty > 0) {
      $addBtn.show().text(`Add ${qty} to Cart`);
    } else {
      $addBtn.hide();
    }

    // Update button states
    this.updateButtonStates($item, qty);
  },

  /**
   * Update quantity button states
   */
  updateButtonStates: function ($item, qty) {
    const $decreaseBtn = $item.find(".qty-btn.decrease");
    const $increaseBtn = $item.find(".qty-btn.increase");

    // Disable decrease button if qty is 0
    $decreaseBtn.prop("disabled", qty <= 0);

    // Disable increase button if qty is at maximum
    $increaseBtn.prop("disabled", qty >= 999);
  },

  /**
   * Add product to cart
   */
  addToCart: function ($btn) {
    const $item = $btn.closest(".product-item");
    const $input = $item.find(".product-quantity");
    const productId = $btn.data("product-id");
    const quantity = parseInt($input.val()) || 1;

    if (quantity < 1) {
      EasyCheckoutNotifications.show("Please select a quantity", "error");
      return;
    }

    // Disable button and show loading
    $btn.prop("disabled", true).text("Adding...");

    $.ajax({
      url: easy_checkout_params.ajax_url,
      type: "POST",
      data: {
        action: "easy_checkout_add_to_cart",
        product_id: productId,
        quantity: quantity,
        nonce: easy_checkout_params.nonce,
      },
      success: function (response) {
        if (response.success) {
          // Update cart display if CartManager is available
          if (typeof EasyCheckoutCartManager !== "undefined") {
            EasyCheckoutCartManager.updateCartDisplay(response.data);
          }

          EasyCheckoutNotifications.show("Product added to cart", "success");

          // Reset quantity
          $input.val(0);
          $btn.hide();
        } else {
          EasyCheckoutNotifications.show(
            response.data || "Failed to add product to cart",
            "error"
          );
        }
      },
      error: function (xhr, status, error) {
        EasyCheckoutNotifications.show(
          "Failed to add product to cart",
          "error"
        );
        console.error("Add to cart error:", error);
      },
      complete: function () {
        $btn.prop("disabled", false).text("Add to Cart");
      },
    });
  },

  /**
   * Create quantity control HTML
   */
  createQuantityControl: function (options = {}) {
    const defaults = {
      value: 1,
      min: 1,
      max: 999,
      step: 1,
      class: "product-quantity",
      showButtons: true,
    };

    const settings = Object.assign(defaults, options);

    let html = '<div class="quantity-control">';

    if (settings.showButtons) {
      html += `<button type="button" class="qty-btn decrease" ${
        settings.value <= settings.min ? "disabled" : ""
      }>-</button>`;
    }

    html += `<input type="number" class="${settings.class}" value="${settings.value}" min="${settings.min}" max="${settings.max}" step="${settings.step}">`;

    if (settings.showButtons) {
      html += `<button type="button" class="qty-btn increase" ${
        settings.value >= settings.max ? "disabled" : ""
      }>+</button>`;
    }

    html += "</div>";

    return html;
  },

  /**
   * Animate quantity change
   */
  animateQuantityChange: function ($element) {
    $element.addClass("quantity-updated");
    setTimeout(() => {
      $element.removeClass("quantity-updated");
    }, 300);
  },

  /**
   * Validate quantity value
   */
  validateQuantity: function (value, min = 1, max = 999) {
    const qty = parseInt(value) || 0;
    return Math.max(min, Math.min(qty, max));
  },
};
