/**
 * GSAP-powered motion, sitewide.
 *
 * Loaded after gsap.min.js, ScrollTrigger.min.js and main.js (see
 * partials/footer.php). Every call is guarded by a `gsap`/`ScrollTrigger`
 * feature-detect: if either vendor file is missing or fails to load, this
 * script does nothing and the page stays fully usable — .reveal elements
 * are simply shown (no animation) via the existing .reveal/.is-visible
 * CSS convention from base.css, instead of staying invisible forever.
 *
 * Three integrations only, per spec:
 *  (a) Hero entrance timeline (index.html only, on load).
 *  (b) Scroll reveals sitewide, replacing the old IntersectionObserver in
 *      main.js, with a staggered treatment for the two card grids
 *      (.grid--3 conciliación branch cards / .pillars arbitraje pillars)
 *      and a simple non-staggered reveal for standalone .reveal sections.
 *  (c) Parallax on the hero's decorative glow layer only (.hero__glow) —
 *      no other element anywhere on the site gets parallax.
 */
(function () {
  "use strict";

  /* Same prefers-reduced-motion pattern used in main.js, re-checked here
     since this file is a separate script scope. */
  var prefersReducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var gsapReady = typeof gsap !== "undefined" && typeof ScrollTrigger !== "undefined";

  if (gsapReady) {
    gsap.registerPlugin(ScrollTrigger);
  }

  var STAGGER_CAP = 8; // generic cap for staggered groups, even though no grid here exceeds 5

  /* ---------- (a) Hero entrance timeline (index.html only) ---------- */
  var hero = document.querySelector(".hero");

  if (hero) {
    var glow = hero.querySelector(".hero__glow");
    var kicker = hero.querySelector(".hero__eyebrow");
    var title = hero.querySelector(".hero__title");
    var lede = hero.querySelector(".hero__lede");
    var actions = hero.querySelector(".hero__actions");
    var portrait = hero.querySelector(".hero__portrait");

    /* Manual word-split for the h1 stagger — no SplitText (paid) plugin.
       Only worth doing when we're actually about to animate it. */
    if (title && gsapReady && !prefersReducedMotion) {
      var words = title.textContent.trim().split(/\s+/);
      title.textContent = "";
      words.forEach(function (word, i) {
        var span = document.createElement("span");
        span.className = "word";
        span.textContent = word + (i < words.length - 1 ? " " : "");
        title.appendChild(span);
      });
    }

    if (gsapReady && !prefersReducedMotion) {
      var heroTl = gsap.timeline({ defaults: { ease: "power2.out" } });

      if (kicker) {
        heroTl.from(kicker, { opacity: 0, y: 16, duration: 0.4 });
      }
      if (title) {
        var titleWords = title.querySelectorAll(".word");
        heroTl.from(
          titleWords.length ? titleWords : title,
          { opacity: 0, y: 20, duration: 0.55, stagger: 0.05 },
          "-=0.25"
        );
      }
      if (lede) {
        heroTl.from(lede, { opacity: 0, y: 16, duration: 0.45 }, "-=0.3");
      }
      if (actions && actions.children.length) {
        heroTl.from(actions.children, { opacity: 0, y: 14, duration: 0.4, stagger: 0.08 }, "-=0.25");
      }
      if (portrait) {
        heroTl.from(portrait, { opacity: 0, scale: 0.96, duration: 0.6, ease: "expo.out" }, "-=0.45");
      }
      /* Total runs ~0.6-0.9s end to end given the overlaps above. */
    }
    /* prefers-reduced-motion or no GSAP: elements are already in their
       final visible state by default (no CSS pre-hides them), so there is
       nothing further to do — the hero is correct with zero animation. */

    /* ---------- (c) Parallax on the hero glow layer only ---------- */
    if (glow && gsapReady && !prefersReducedMotion) {
      gsap.to(glow, {
        yPercent: 10,
        ease: "none",
        scrollTrigger: {
          trigger: hero,
          scrub: true,
        },
      });
    }
  }

  /* ---------- (b) Scroll reveals sitewide ---------- */
  var handledReveals = [];

  function showInstantly(els) {
    Array.prototype.forEach.call(els, function (el) {
      el.classList.add("is-visible");
    });
  }

  /* .reveal's own CSS transition (base.css) is meant for the showInstantly/
     is-visible fallback above. When GSAP drives the same opacity/transform
     properties directly it must not fight that CSS transition, so it's
     switched off per element right before GSAP takes over. */
  function disableCssTransition(els) {
    Array.prototype.forEach.call(els, function (el) {
      el.style.transition = "none";
    });
  }

  function revealGroup(container) {
    if (!container) return;
    var items = container.querySelectorAll(".reveal");
    if (!items.length) return;

    var list = Array.prototype.slice.call(items).slice(0, STAGGER_CAP);
    handledReveals = handledReveals.concat(list);

    if (gsapReady && !prefersReducedMotion) {
      disableCssTransition(list);
      gsap.fromTo(
        list,
        { opacity: 0, y: 24 },
        {
          opacity: 1,
          y: 0,
          duration: 0.5,
          stagger: 0.08,
          ease: "power2.out",
          scrollTrigger: { trigger: container, start: "top 85%" },
        }
      );
    } else {
      showInstantly(list);
    }
  }

  /* The two known card grids that should reveal as a staggered group
     rather than item-by-item. */
  revealGroup(document.querySelector(".grid--dividers"));
  revealGroup(document.querySelector(".pillars"));

  /* Every remaining standalone .reveal element (not already handled as
     part of a staggered group above) gets a simple, non-staggered reveal. */
  var remaining = Array.prototype.filter.call(document.querySelectorAll(".reveal"), function (el) {
    return handledReveals.indexOf(el) === -1;
  });

  if (gsapReady && !prefersReducedMotion) {
    disableCssTransition(remaining);
    remaining.forEach(function (el) {
      gsap.fromTo(
        el,
        { opacity: 0, y: 24 },
        {
          opacity: 1,
          y: 0,
          duration: 0.5,
          ease: "power2.out",
          scrollTrigger: { trigger: el, start: "top 85%" },
        }
      );
    });
  } else {
    showInstantly(remaining);
  }

  /* ---------- Card micro-lift hover (replaces the old shadow-pop hover
     on .branch-card / .pillar — see components.css step 2) ---------- */
  if (gsapReady) {
    var liftEls = document.querySelectorAll(".branch-card, .pillar");
    liftEls.forEach(function (el) {
      el.addEventListener("mouseenter", function () {
        gsap.to(el, { y: -2, duration: 0.2, ease: "power2.out" });
      });
      el.addEventListener("mouseleave", function () {
        gsap.to(el, { y: 0, duration: 0.2, ease: "power2.out" });
      });
    });
  }
})();
