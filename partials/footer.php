</main>

<footer class="site-footer">
  <div class="container">
    <div class="footer__grid">
      <div>
        <!-- Reversed/knockout export: the client's transparent logo with its
             navy strokes recolored to white (gold ribbon left untouched),
             generated from contenido/virgen_asunta_transparente.png so it
             reads cleanly on the navy-dark footer with no background box. -->
        <picture>
          <source srcset="/assets/img/logo-wordmark-reversed.webp" type="image/webp">
          <img class="footer__logo-img" src="/assets/img/logo-wordmark-reversed.png" alt="Virgen Asunta - Centro de Conciliación y Arbitraje" width="1340" height="700" loading="lazy">
        </picture>
        <p class="footer__tagline">Centro de Conciliación y Arbitraje dedicado a resolver controversias familiares, laborales, comerciales, ambientales y de contrataciones con el Estado mediante el diálogo.</p>
      </div>

      <div>
        <p class="footer__heading">Navegación</p>
        <ul class="footer__list">
          <li><a href="/nosotros.html">Nosotros</a></li>
          <li><a href="/conciliacion-extrajudicial/">Conciliación Extrajudicial</a></li>
          <li><a href="/centro-arbitraje/">Centro de Arbitraje</a></li>
          <li><a href="/trabaja-con-nosotros.html">Trabaja con Nosotros</a></li>
          <li><a href="/contacto.html">Contacto</a></li>
        </ul>
      </div>

      <div>
        <p class="footer__heading">Contacto</p>
        <div class="footer__contact-item">
          <svg class="icon icon--on-dark" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s7-6.2 7-12a7 7 0 1 0-14 0c0 5.8 7 12 7 12Z" stroke-width="1.5" stroke-linejoin="round"/><circle cx="12" cy="9" r="2.5" stroke-width="1.5"/></svg>
          <span>Av. República de Chile 324, Oficina 601, Jesús María, Lima, Perú</span>
        </div>
        <div class="footer__contact-item">
          <svg class="icon icon--on-dark" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v14H4z" stroke-width="1.5" stroke-linejoin="round"/><path d="m4 6 8 7 8-7" stroke-width="1.5" stroke-linejoin="round"/></svg>
          <span><?= va_e(va_config()['contact_email'] ?? '{{EMAIL_CONTACTO}}') ?></span>
        </div>
        <a class="claims-badge" href="/libro-reclamaciones.html">
          <picture>
            <source srcset="/assets/img/libro-reclamaciones.webp" type="image/webp">
            <img src="/assets/img/libro-reclamaciones.png" alt="Libro de Reclamaciones" width="443" height="300" loading="lazy">
          </picture>
        </a>
      </div>
    </div>

    <div class="footer__bottom">
      <span>&copy; <?= date('Y') ?> Virgen Asunta - Centro de Conciliación y Arbitraje. Todos los derechos reservados.</span>
      <span class="text-muted">Diálogo que construye soluciones.</span>
    </div>
  </div>
</footer>

<!-- Self-hosted GSAP (MIT), never a CDN URL: gsap.min.js and ScrollTrigger.min.js
     under assets/js/vendor/, mirroring the earlier three.js self-hosting
     precedent. main.js and scroll-fx.js both feature-detect `gsap` /
     `ScrollTrigger` before using them, so the site stays fully usable even
     if a vendor file is missing or fails to load. -->
<script src="/assets/js/vendor/gsap.min.js" defer></script>
<script src="/assets/js/vendor/ScrollTrigger.min.js" defer></script>
<script src="/assets/js/main.js" defer></script>
<script src="/assets/js/scroll-fx.js" defer></script>
</body>
</html>
