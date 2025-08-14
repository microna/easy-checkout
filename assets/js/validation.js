/* Checkout field validation */
(function ($, EC) {
  const Validation = {
    init() {
      $(document).on("blur", "#billing_email", this.onEmailBlur.bind(this));
      $(document).on(
        "blur",
        ".checkout input[required]",
        this.onRequiredBlur.bind(this)
      );
      $(document).on("focus", ".checkout input", this.onFieldFocus.bind(this));
    },
    onEmailBlur(e) {
      const $field = $(e.currentTarget);
      const email = $field.val().trim();
      const $wrap = $field.closest(".form-row");
      $wrap.removeClass("field-valid field-invalid");
      this.removeError($field);
      if (!email) return;
      if (this.isEmail(email)) {
        $wrap.addClass("field-valid");
      } else {
        $wrap.addClass("field-invalid");
        this.showError($field, "Please enter a valid email address.");
      }
    },
    onRequiredBlur(e) {
      const $field = $(e.currentTarget);
      const value = ($field.val() || "").trim();
      const $wrap = $field.closest(".form-row");
      $wrap.removeClass("field-valid field-invalid");
      this.removeError($field);
      if (!value) {
        $wrap.addClass("field-invalid");
        this.showError($field, "This field is required.");
      } else {
        $wrap.addClass("field-valid");
      }
    },
    onFieldFocus(e) {
      const $field = $(e.currentTarget);
      const $wrap = $field.closest(".form-row");
      $wrap.removeClass("field-invalid");
      this.removeError($field);
    },
    isEmail(email) {
      const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      return re.test(email);
    },
    showError($field, text) {
      const $wrap = $field.closest(".form-row");
      let $err = $wrap.find(".field-error");
      if (!$err.length)
        $err = $('<span class="field-error"></span>').appendTo($wrap);
      $err.text(text).show();
    },
    removeError($field) {
      $field.closest(".form-row").find(".field-error").remove();
    },
  };

  $(document).on("easy_checkout:ready", function () {
    Validation.init();
  });
})(jQuery, (window.EasyCheckout = window.EasyCheckout || {}));
