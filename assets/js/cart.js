/* Cart interactions: add, remove, quantity update, and checkout refresh */
(function ($, EC) {
  const Cart = {
    init() {
      $(document).on(
        "change",
        ".product-quantity",
        this.onProductCardQtyInput.bind(this)
      );
      $(document).on("click", ".add-to-cart-btn", this.addToCart.bind(this));
      $(document).on(
        "click",
        ".remove-cart-item",
        this.removeFromCart.bind(this)
      );
      $(document).on(
        "click",
        ".ec-remove-item",
        this.removeFromCart.bind(this)
      );

      $(document).on(
        "change blur",
        ".ec-cart-qty-input",
        this.onLineItemQtyInput.bind(this)
      );

      $(document).on(
        "input",
        ".ec-cart-qty-input",
        this.onLineItemQtyInputRealtime.bind(this)
      );
    },

    load() {
      if ($("#cart-summary").length === 0) return;
      EC.utils.ajax({
        data: {
          action: "easy_checkout_get_cart",
          nonce: custom_checkout_params.nonce,
        },
        success: (response) => {
          if (response && response.success) {
            EC.state.cart = response.data;
            if (!response.data.is_empty)
              $(".checkout.woocommerce-checkout").show();
          }
        },
      });
    },

    onProductCardQtyInput(e) {
      const $input = $(e.currentTarget);
      const $item = $input.closest(".product-item");
      const $addBtn = $item.find(".add-to-cart-btn");
      let qty = parseInt($input.val(), 10) || 0;
      if (qty < 0) qty = 0;
      if (qty > 99) qty = 99;
      $input.val(qty);
      qty > 0 ? $addBtn.show() : $addBtn.hide();
    },

    addToCart(e) {
      e.preventDefault();
      const $btn = $(e.currentTarget);
      const productId = $btn.data("product-id");
      const $item = $btn.closest(".product-item");
      const $input = $item.find(".product-quantity");
      const quantity = parseInt($input.val(), 10) || 1;
      if (quantity <= 0) return EC.notify("Please select a quantity", "error");
      $btn.prop("disabled", true).text("Adding...");
      EC.utils.ajax({
        data: {
          action: "easy_checkout_add_to_cart",
          product_id: productId,
          quantity,
          nonce: custom_checkout_params.nonce,
        },
        success: (response) => {
          if (response && response.success) {
            EC.notify("Product added to cart!", "success");
            $input.val(0);
            $btn.hide();

            if ($(".easy-checkout-cart-section").is(":hidden")) {
              $(".easy-checkout-cart-section").fadeIn(300);
            }

            $(document.body).trigger("update_checkout");
          } else {
            EC.notify(
              (response && response.data) || "Failed to add product to cart",
              "error"
            );
          }
        },
        complete: () => $btn.prop("disabled", false).text("Add to Cart"),
        error: () => EC.notify("Failed to add product to cart", "error"),
      });
    },

    removeFromCart(e) {
      e.preventDefault();
      let $btn = $(e.currentTarget);
      if (!$btn.data("cart-item-key")) {
        $btn = $btn.closest("[data-cart-item-key]");
      }
      const cartItemKey = $btn.data("cart-item-key");
      $btn.prop("disabled", true);
      EC.utils.ajax({
        data: {
          action: "easy_checkout_remove_from_cart",
          cart_item_key: cartItemKey,
          nonce: custom_checkout_params.nonce,
        },
        success: (response) => {
          if (response && response.success) {
            EC.notify("Product removed from cart", "success");
            if (response.data && response.data.cart_count === 0) {
              $(".checkout.woocommerce-checkout").hide();
              $(".easy-checkout-cart-section").fadeOut(300);
            }
            $(document.body).trigger("update_checkout");
          } else {
            EC.notify(
              (response && response.data) || "Failed to remove product",
              "error"
            );
          }
        },
        complete: () => $btn.prop("disabled", false),
        error: () => EC.notify("Failed to remove product", "error"),
      });
    },

    onLineItemQtyInput(e) {
      const $input = $(e.currentTarget);
      const key = $input.data("cart-item-key");
      let qty = parseInt($input.val(), 10) || 1;
      const max =
        parseInt($input.closest(".ec-quantity-controls").data("max-qty"), 10) ||
        99;

      if (qty < 1) qty = 1;
      if (qty > max) qty = max;
      $input.val(qty);

      this.updateQuantity(key, qty, $input);
    },

    onLineItemQtyInputRealtime(e) {
      const $input = $(e.currentTarget);

      clearTimeout($input.data("update-timeout"));

      const timeout = setTimeout(() => {
        this.onLineItemQtyInput(e);
      }, 1000); 

      $input.data("update-timeout", timeout);
    },
    updateQuantity(cartItemKey, quantity, $el) {
      if (!cartItemKey) return;
      $el.prop("disabled", true);
      EC.utils.ajax({
        data: {
          action: "easy_checkout_update_cart_quantity",
          cart_item_key: cartItemKey,
          quantity,
          nonce: custom_checkout_params.nonce,
        },
        success: (response) => {
          if (response && response.success) {
            EC.notify("Cart updated", "success");

            if (response.data && response.data.cart_count === 0) {
              $(".easy-checkout-cart-section").fadeOut(300);
            } else {
              this.updateCartTotals();
            }

            $(document.body).trigger("update_checkout");
          } else {
            EC.notify(
              (response && response.data) || "Failed to update cart",
              "error"
            );
          }
        },
        complete: () => $el.prop("disabled", false),
        error: () => EC.notify("Failed to update cart", "error"),
      });
    },

    updateCartTotals() {
      EC.utils.ajax({
        data: {
          action: "easy_checkout_get_cart",
          nonce: custom_checkout_params.nonce,
        },
        success: (response) => {
          if (response && response.success && response.data) {
            const cartData = response.data;

            cartData.cart_items.forEach((item) => {
              const $itemRow = $(
                `.cart-review-item[data-cart-item-key="${item.key}"]`
              );
              if ($itemRow.length) {
                $itemRow.find(".total-amount").html(item.subtotal);
                $itemRow.find(".ec-cart-qty-input").val(item.quantity);
              }
            });

            $(".cart-summary-totals .subtotal-row .amount").html(
              cartData.cart_subtotal
            );
            $(".cart-summary-totals .total-row .amount").html(
              cartData.cart_total
            );

            // Update simple order summary if it exists
            $(".summary-totals .summary-row:first-child .value").text(
              cartData.cart_count
            );
            $(".order-summary-simple .subtotal-row .value").html(
              cartData.cart_subtotal
            );
            $(".order-summary-simple .total-row .value").html(
              cartData.cart_total
            );
          }
        },
        error: () => {
          setTimeout(() => {
            location.reload();
          }, 1000);
        },
      });
    },
  };

  $(document).on("easy_checkout:ready", function () {
    Cart.init();
    Cart.load();
  });
})(jQuery, (window.EasyCheckout = window.EasyCheckout || {}));
