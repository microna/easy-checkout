/* Cart interactions: add, remove, quantity update, and checkout refresh */
(function ($, EC) {
  const Cart = {
    init() {
      $(document).on("click", ".qty-btn", this.onProductCardQtyBtn.bind(this));
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
      // Support order review remove control
      $(document).on(
        "click",
        ".ec-remove-item",
        this.removeFromCart.bind(this)
      );

      // Order review inline controls
      $(document).on("click", ".ec-qty-btn", this.onLineItemQtyBtn.bind(this));
      $(document).on(
        "change",
        ".ec-cart-qty-input",
        this.onLineItemQtyInput.bind(this)
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

    // Product list/cart summary controls
    onProductCardQtyBtn(e) {
      e.preventDefault();
      const $btn = $(e.currentTarget);
      const $item = $btn.closest(".product-item");
      const $input = $item.find(".product-quantity");
      const $addBtn = $item.find(".add-to-cart-btn");
      const action = $btn.data("action");
      let qty = parseInt($input.val(), 10) || 0;
      qty =
        action === "increase" ? Math.min(qty + 1, 99) : Math.max(qty - 1, 0);
      $input.val(qty);
      qty > 0 ? $addBtn.show() : $addBtn.hide();
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
            if (response.data && response.data.cart_count === 0)
              $(".checkout.woocommerce-checkout").hide();
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

    // Order review inline quantity controls
    onLineItemQtyBtn(e) {
      e.preventDefault();
      const $btn = $(e.currentTarget);
      const $wrapper = $btn.closest(".ec-quantity-controls");
      const $input = $wrapper.find(".ec-cart-qty-input");
      const action = $btn.data("action");
      const key = $btn.data("cart-item-key");
      let qty = parseInt($input.val(), 10) || 1;
      const max = parseInt($wrapper.data("max-qty"), 10) || 99;
      if (action === "increase") qty = Math.min(qty + 1, max);
      if (action === "decrease") qty = Math.max(qty - 1, 1);
      $input.val(qty);
      this.updateQuantity(key, qty, $btn);
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
  };

  $(document).on("easy_checkout:ready", function () {
    Cart.init();
    Cart.load();
  });
})(jQuery, (window.EasyCheckout = window.EasyCheckout || {}));
