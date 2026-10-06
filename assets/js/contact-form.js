/**
 * "Contacto" page form wiring. Kept in an external file (rather than an
 * inline <script>) so it complies with the site's Content-Security-Policy,
 * which does not allow inline script execution.
 */
(function () {
  "use strict";

  // This file is loaded with `defer`, same as main.js, so the DOM is already
  // parsed by the time this runs — no DOMContentLoaded wrapper needed.
  var form = document.getElementById("contactForm");
  if (!form || typeof window.vaBindForm !== "function") return;

  var contactEmail = form.dataset.contactEmail || "";

  window.vaBindForm("contactForm", {
    mailto: function (formEl) {
      var data = new FormData(formEl);
      var subject = encodeURIComponent("Solicitud de información — " + data.get("subject"));
      var body = encodeURIComponent(
        "Nombre: " + data.get("name") + "\n" +
        "Teléfono: " + data.get("phone") + "\n" +
        "Correo: " + data.get("email") + "\n\n" +
        "Mensaje:\n" + data.get("message")
      );
      return "mailto:" + contactEmail + "?subject=" + subject + "&body=" + body;
    },
  });
})();
