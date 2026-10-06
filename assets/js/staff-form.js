/**
 * "Trabaja con Nosotros" staff-application form wiring. Same mailto
 * pattern as contact-form.js — external file, not inline, per CSP.
 */
(function () {
  "use strict";

  var form = document.getElementById("staffForm");
  if (!form || typeof window.vaBindForm !== "function") return;

  var staffEmail = form.dataset.staffEmail || "";

  window.vaBindForm("staffForm", {
    mailto: function (formEl) {
      var data = new FormData(formEl);
      var subject = encodeURIComponent("Postulación al staff — " + data.get("area"));
      var body = encodeURIComponent(
        "Nombre: " + data.get("name") + "\n" +
        "DNI: " + data.get("dni") + "\n" +
        "Teléfono: " + data.get("phone") + "\n" +
        "Correo: " + data.get("email") + "\n" +
        "Profesión / Especialidad: " + data.get("profession") + "\n" +
        "Área de interés: " + data.get("area") + "\n\n" +
        "Carta de presentación:\n" + data.get("message")
      );
      return "mailto:" + staffEmail + "?subject=" + subject + "&body=" + body;
    },
  });
})();
