/**
 * Form Validator Module
 *
 * Handles form validation for checkout
 *
 * @package Easy_Checkout
 * @version 2.0.0
 */

const EasyCheckoutFormValidator = {
  /**
   * Initialize form validator
   */
  init: function () {
    this.bindEvents();
    this.initValidation();
  },

  /**
   * Bind validation events
   */
  bindEvents: function () {
    const self = this;

    // Email validation on blur
    $("#billing_email").on("blur", function () {
      self.validateEmail($(this));
    });

    // Real-time field validation
    $(".checkout input[required]").on("blur", function () {
      self.validateRequiredField($(this));
    });

    // Clear error messages on focus
    $(".checkout input").on("focus", function () {
      self.clearFieldError($(this));
    });

    // Form submission validation
    $(".checkout").on("submit", function (e) {
      if (!self.validateForm()) {
        e.preventDefault();
        return false;
      }
    });
  },

  /**
   * Initialize validation indicators
   */
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

  /**
   * Validate email field
   */
  validateEmail: function ($field) {
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
      return true;
    } else {
      $wrapper.addClass("field-invalid");
      this.showFieldError($field, easy_checkout_params.i18n.invalid_email);
      return false;
    }
  },

  /**
   * Validate required field
   */
  validateRequiredField: function ($field) {
    const value = $field.val().trim();
    const $wrapper = $field.closest(".form-row");

    // Clear previous validation
    $wrapper.removeClass("field-valid field-invalid");
    this.removeFieldError($field);

    if (value === "") {
      $wrapper.addClass("field-invalid");
      this.showFieldError($field, easy_checkout_params.i18n.required_field);
      return false;
    } else {
      $wrapper.addClass("field-valid");
      return true;
    }
  },

  /**
   * Validate entire form
   */
  validateForm: function () {
    let isValid = true;
    let firstError = null;

    // Validate all required fields
    $(".checkout input[required]").each((index, element) => {
      const $field = $(element);
      const value = $field.val().trim();
      const $wrapper = $field.closest(".form-row");

      if (value === "") {
        isValid = false;
        $wrapper.addClass("field-invalid");

        if (!$wrapper.find(".field-error").length) {
          this.showFieldError($field, easy_checkout_params.i18n.required_field);
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
          easy_checkout_params.i18n.invalid_email
        );
      }

      if (!firstError) {
        firstError = $emailWrapper;
      }
    }

    // Scroll to first error
    if (!isValid && firstError) {
      $("html, body").animate(
        {
          scrollTop: firstError.offset().top - 100,
        },
        500
      );
    }

    return isValid;
  },

  /**
   * Check if email is valid
   */
  isValidEmail: function (email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  },

  /**
   * Show field error
   */
  showFieldError: function ($field, message) {
    const $wrapper = $field.closest(".form-row");
    this.removeFieldError($field);

    const $error = $('<span class="field-error"></span>').text(message);
    $wrapper.append($error);

    // Add shake animation
    $wrapper.addClass("shake");
    setTimeout(() => {
      $wrapper.removeClass("shake");
    }, 500);
  },

  /**
   * Remove field error
   */
  removeFieldError: function ($field) {
    const $wrapper = $field.closest(".form-row");
    $wrapper.find(".field-error").remove();
  },

  /**
   * Clear field error
   */
  clearFieldError: function ($field) {
    const $wrapper = $field.closest(".form-row");
    $wrapper.removeClass("field-invalid field-valid");
    this.removeFieldError($field);
  },

  /**
   * Validate field on server
   */
  validateEmailOnServer: function (email) {
    return new Promise((resolve, reject) => {
      $.ajax({
        url: easy_checkout_params.ajax_url,
        type: "POST",
        data: {
          action: "validate_checkout_email",
          email: email,
          nonce: easy_checkout_params.nonce,
        },
        success: function (response) {
          if (response.success) {
            resolve(true);
          } else {
            reject(response.data);
          }
        },
        error: function (xhr, status, error) {
          reject("Server error occurred");
        },
      });
    });
  },

  /**
   * Reset form validation
   */
  resetValidation: function () {
    $(".checkout .form-row").removeClass("field-valid field-invalid");
    $(".checkout .field-error").remove();
  },
};
