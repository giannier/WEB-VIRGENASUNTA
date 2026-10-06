/**
 * "Libro de Reclamaciones" page wiring: toggle the guardian/tutor fieldset
 * for minors, and bind the form to the complaint-submit API endpoint.
 * Kept in an external file (not an inline <script>) so it complies with
 * the site's Content-Security-Policy (no inline script execution allowed).
 */
(function () {
  "use strict";

  var isMinor = document.getElementById("isMinor");
  var guardianFieldset = document.getElementById("guardianFieldset");
  var guardianName = document.getElementById("guardianName");
  var guardianDoc = document.getElementById("guardianDoc");

  if (isMinor && guardianFieldset && guardianName && guardianDoc) {
    isMinor.addEventListener("change", function () {
      guardianFieldset.hidden = !isMinor.checked;
      guardianName.required = isMinor.checked;
      guardianDoc.required = isMinor.checked;
    });
  }

  if (typeof window.vaBindForm === "function") {
    window.vaBindForm("complaintForm", { endpoint: "/api/complaint-submit.php" });
  }
})();
