/**
 * Notifications Module
 *
 * Handles user notifications and messages
 *
 * @package Easy_Checkout
 * @version 2.0.0
 */

const EasyCheckoutNotifications = {
  /**
   * Initialize notifications
   */
  init: function () {
    this.createContainer();
  },

  /**
   * Create notification container
   */
  createContainer: function () {
    if ($("#easy-checkout-notifications").length === 0) {
      $("body").append('<div id="easy-checkout-notifications"></div>');
    }
  },

  /**
   * Show notification
   */
  show: function (message, type = "info", duration = 4000) {
    this.createContainer();

    const $container = $("#easy-checkout-notifications");
    const notificationId = "notification-" + Date.now();

    const $notification = $(`
            <div id="${notificationId}" class="easy-checkout-notification ${type}">
                <div class="notification-content">
                    <span class="notification-message">${message}</span>
                    <button type="button" class="notification-close" data-notification="${notificationId}">×</button>
                </div>
            </div>
        `);

    $container.append($notification);

    // Animate in
    setTimeout(() => {
      $notification.addClass("show");
    }, 10);

    // Auto remove after duration
    if (duration > 0) {
      setTimeout(() => {
        this.hide(notificationId);
      }, duration);
    }

    // Bind close button
    $notification.find(".notification-close").on("click", () => {
      this.hide(notificationId);
    });

    return notificationId;
  },

  /**
   * Hide notification
   */
  hide: function (notificationId) {
    const $notification = $(`#${notificationId}`);

    if ($notification.length) {
      $notification.removeClass("show").addClass("hide");

      setTimeout(() => {
        $notification.remove();
      }, 300);
    }
  },

  /**
   * Show success message
   */
  success: function (message, duration = 4000) {
    return this.show(message, "success", duration);
  },

  /**
   * Show error message
   */
  error: function (message, duration = 6000) {
    return this.show(message, "error", duration);
  },

  /**
   * Show warning message
   */
  warning: function (message, duration = 5000) {
    return this.show(message, "warning", duration);
  },

  /**
   * Show info message
   */
  info: function (message, duration = 4000) {
    return this.show(message, "info", duration);
  },

  /**
   * Clear all notifications
   */
  clearAll: function () {
    $(".easy-checkout-notification").each(function () {
      const notificationId = $(this).attr("id");
      EasyCheckoutNotifications.hide(notificationId);
    });
  },

  /**
   * Show loading notification
   */
  loading: function (message = "Loading...") {
    return this.show(
      `
            <div class="loading-spinner"></div>
            <span>${message}</span>
        `,
      "loading",
      0
    );
  },
};
