/**
 * Sitewide UX helpers: mobile nav toggle, scroll reveals, generic form
 * client-side helpers used by contacto.html and libro-reclamaciones.html.
 */
(function () {
  "use strict";

  var prefersReducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  /* ---------- View-only PDF frames ----------
     Disables the right-click menu on the "resolución" document frames
     (trabaja-con-nosotros.html) as a good-faith deterrent against the
     obvious "save as" path. This is NOT real DRM — a determined visitor
     can still screenshot the page or use devtools; there is no way to
     truly prevent download of anything rendered in a browser. The PDF
     itself is also served with its native viewer toolbar hidden
     (#toolbar=0 in the iframe src) in browsers that support it. */
  document.querySelectorAll(".pdf-frame").forEach(function (frame) {
    frame.addEventListener("contextmenu", function (event) {
      event.preventDefault();
    });
  });

  /* ---------- Page loader ----------
     Hides the full-logo splash (see partials/header.php + components.css)
     once the page has fully loaded (images included), with a minimum
     visible duration so it reads as a deliberate moment rather than a
     flash on fast/cached loads. Reduced motion skips straight to hidden —
     the CSS transition is also disabled in that case, so this is instant. */
  var pageLoader = document.getElementById("pageLoader");
  if (pageLoader) {
    if (prefersReducedMotion) {
      pageLoader.classList.add("page-loader--hidden");
    } else {
      var loaderShownAt = Date.now();
      var MIN_LOADER_MS = 1800;
      var hidePageLoader = function () {
        var wait = Math.max(0, MIN_LOADER_MS - (Date.now() - loaderShownAt));
        window.setTimeout(function () {
          pageLoader.classList.add("page-loader--hidden");
        }, wait);
      };
      if (document.readyState === "complete") {
        hidePageLoader();
      } else {
        window.addEventListener("load", hidePageLoader);
      }
    }
  }

  /* ---------- Scrolled header (color invert + logo swap) ----------
     Adds .is-scrolled to .site-header past a small threshold; the actual
     color/logo swap is pure CSS (see components.css), this just tracks
     scroll position. Threshold avoids flicker right at the very top. */
  var siteHeader = document.querySelector(".site-header");
  if (siteHeader) {
    var SCROLL_THRESHOLD = 40;
    var updateHeaderScrolled = function () {
      siteHeader.classList.toggle("is-scrolled", window.scrollY > SCROLL_THRESHOLD);
    };
    updateHeaderScrolled();
    window.addEventListener("scroll", updateHeaderScrolled, { passive: true });
  }

  /* ---------- Mobile nav toggle ---------- */
  var navToggle = document.getElementById("navToggle");
  var navMobile = document.getElementById("navMobile");

  if (navToggle && navMobile) {
    var closeMobileNav = function () {
      navToggle.setAttribute("aria-expanded", "false");
      navMobile.classList.remove("is-open");
      document.body.classList.remove("no-scroll");
    };

    navToggle.addEventListener("click", function () {
      var expanded = navToggle.getAttribute("aria-expanded") === "true";
      navToggle.setAttribute("aria-expanded", String(!expanded));
      navMobile.classList.toggle("is-open", !expanded);
      document.body.classList.toggle("no-scroll", !expanded);
    });

    navMobile.addEventListener("click", function (event) {
      if (event.target.tagName === "A" && !event.target.closest(".nav__submenu")) {
        closeMobileNav();
      }
    });

    /* Force-close the mobile panel when the viewport crosses into the
       desktop breakpoint (resize, rotation, devtools), so a menu left open
       never gets stuck visible under the desktop layout. */
    var desktopMedia = window.matchMedia("(min-width: 960px)");
    desktopMedia.addEventListener("change", function (event) {
      if (event.matches) {
        closeMobileNav();
      }
    });
  }

  /* ---------- Scroll reveals ----------
     Powered by GSAP + ScrollTrigger now (see scroll-fx.js), which is loaded
     right after this file. That script re-checks prefers-reduced-motion
     itself and, if GSAP/ScrollTrigger ever fail to load, falls back to
     simply marking every .reveal element visible with no animation — so
     .reveal never needs an IntersectionObserver fallback here. */

  /* ---------- Generic form validation + fetch submit helper ---------- */
  window.vaBindForm = function (formId, options) {
    var form = document.getElementById(formId);
    if (!form) return;

    var statusEl = form.querySelector("[data-form-status]");
    options = options || {};

    function setStatus(type, message) {
      if (!statusEl) return;
      statusEl.textContent = message;
      statusEl.className = "form__status is-visible form__status--" + type;
      statusEl.setAttribute("role", type === "error" ? "alert" : "status");
      statusEl.scrollIntoView({ behavior: prefersReducedMotion ? "auto" : "smooth", block: "center" });
    }

    function clearFieldError(field) {
      var wrap = field.closest(".field");
      if (wrap) wrap.classList.remove("field--error");
    }

    function setFieldError(field) {
      var wrap = field.closest(".field");
      if (wrap) wrap.classList.add("field--error");
    }

    form.querySelectorAll("input, textarea, select").forEach(function (field) {
      field.addEventListener("input", function () {
        clearFieldError(field);
      });
    });

    form.addEventListener("submit", function (event) {
      event.preventDefault();

      var valid = true;
      form.querySelectorAll("[required]").forEach(function (field) {
        if (!field.checkValidity()) {
          valid = false;
          setFieldError(field);
        }
      });

      if (!valid) {
        setStatus("error", "Por favor completa correctamente los campos marcados en rojo.");
        return;
      }

      var submitBtn = form.querySelector('[type="submit"]');
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.dataset.originalText = submitBtn.textContent;
        submitBtn.textContent = "Enviando...";
      }

      if (options.endpoint) {
        fetch(options.endpoint, {
          method: "POST",
          body: new FormData(form),
          headers: { "X-Requested-With": "XMLHttpRequest" },
        })
          .then(function (res) {
            return res.json().catch(function () {
              return { success: false, message: "Respuesta inválida del servidor." };
            });
          })
          .then(function (data) {
            if (data.success) {
              setStatus("success", data.message || "Tu solicitud fue enviada correctamente.");
              form.reset();
            } else {
              setStatus("error", data.message || "No se pudo enviar el formulario. Intenta nuevamente.");
            }
          })
          .catch(function () {
            setStatus("error", "Ocurrió un error de conexión. Intenta nuevamente en unos minutos.");
          })
          .finally(function () {
            if (submitBtn) {
              submitBtn.disabled = false;
              submitBtn.textContent = submitBtn.dataset.originalText || "Enviar";
            }
          });
      } else if (options.mailto) {
        setStatus("success", "Abriendo tu cliente de correo para completar el envío...");
        window.location.href = options.mailto(form);
      }
    });
  };
})();
