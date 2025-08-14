/* Notifications utility */
(function ($, EC) {
  EC.notify = function (message, type) {
    type = type || "info";
    const notification = $(`
      <div class="easy-checkout-notification ${type}">
        ${message}
        <button class="notification-close" aria-label="Close">&times;</button>
      </div>
    `);

    $("body").append(notification);
    setTimeout(() => notification.addClass("show"), 50);
    setTimeout(() => {
      notification.removeClass("show");
      setTimeout(() => notification.remove(), 300);
    }, 5000);
    notification.on("click", ".notification-close", function () {
      notification.removeClass("show");
      setTimeout(() => notification.remove(), 300);
    });
  };
})(jQuery, (window.EasyCheckout = window.EasyCheckout || {}));
