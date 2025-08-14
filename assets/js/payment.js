/* Payment method interactions */
(function ($, EC) {
  const Payment = {
    init() {
      $(document).on(
        "change",
        'input[name="payment_method"]',
        this.onMethodChange.bind(this)
      );
    },
    onMethodChange(e) {
      const $radio = $(e.currentTarget);
      const method = $radio.val();
      const $box = $(`.payment_box.payment_method_${method}`);
      $(".payment_box").slideUp(300);
      if ($box.length) $box.slideDown(300);
      const text = $radio.data("order_button_text");
      $("#place_order").text(text || "Place Order");
      this.clearErrors();
    },
    clearErrors() {
      $(".payment-error").remove();
      $(".payment_box .field-error").remove();
      $(".payment_box input.error, .payment_box select.error").removeClass(
        "error"
      );
    },
  };

  $(document).on("easy_checkout:ready", function () {
    Payment.init();
  });
})(jQuery, (window.EasyCheckout = window.EasyCheckout || {}));
