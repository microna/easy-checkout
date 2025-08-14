/* Easy Checkout Core Namespace */
window.EasyCheckout = window.EasyCheckout || {
  state: {
    cart: { items: [], total: 0, count: 0 },
  },
  utils: {
    ajax: function (payload) {
      return jQuery.ajax(
        Object.assign(
          {
            url: (window.custom_checkout_params || {}).ajax_url,
            type: "POST",
          },
          payload
        )
      );
    },
  },
};

// DOM Ready bootstrap hook — modules can listen to this
jQuery(function () {
  jQuery(document).trigger("easy_checkout:ready");
});
