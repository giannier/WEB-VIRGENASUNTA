/**
 * Generic accessible tabs widget, used on trabaja-con-nosotros.html.
 * Supports more than one .tabs block per page; each is wired independently.
 * External file (not inline) per the site's CSP, same reason as
 * contact-form.js.
 */
(function () {
  "use strict";

  document.querySelectorAll("[data-tabs]").forEach(function (tabs) {
    var buttons = Array.prototype.slice.call(tabs.querySelectorAll('[role="tab"]'));
    var panels = Array.prototype.slice.call(tabs.querySelectorAll('[role="tabpanel"]'));

    function activate(targetId, moveFocus) {
      buttons.forEach(function (btn) {
        var isActive = btn.getAttribute("aria-controls") === targetId;
        btn.setAttribute("aria-selected", String(isActive));
        btn.tabIndex = isActive ? 0 : -1;
        if (isActive && moveFocus) btn.focus();
      });
      panels.forEach(function (panel) {
        panel.hidden = panel.id !== targetId;
      });
    }

    buttons.forEach(function (btn, index) {
      btn.addEventListener("click", function () {
        activate(btn.getAttribute("aria-controls"), false);
      });

      btn.addEventListener("keydown", function (event) {
        var newIndex = null;
        if (event.key === "ArrowRight" || event.key === "ArrowDown") {
          newIndex = (index + 1) % buttons.length;
        } else if (event.key === "ArrowLeft" || event.key === "ArrowUp") {
          newIndex = (index - 1 + buttons.length) % buttons.length;
        } else if (event.key === "Home") {
          newIndex = 0;
        } else if (event.key === "End") {
          newIndex = buttons.length - 1;
        }
        if (newIndex !== null) {
          event.preventDefault();
          activate(buttons[newIndex].getAttribute("aria-controls"), true);
        }
      });
    });
  });
})();
