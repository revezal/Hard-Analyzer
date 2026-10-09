/* =========================================================
   POPUP DE ENTRADA — Fran Mera
   Uso:  <link rel="stylesheet" href="css/popup.css">
         <script src="js/popup.js" data-site="https://www.franmera.com"></script>
   Se muestra 1 vez por sesión. Agrega ?intro a la URL para forzarlo.
   ========================================================= */
(() => {
  const me = document.currentScript;
  const SITE = me?.dataset.site || "index.html";
  const LISTEN = me?.dataset.listen || "#";
  const CUTOUT = "assets/fotos/fran-cutout.png";   // PNG sin fondo (ideal)
  const PHOTO = "assets/fotos/fran-03.jpg";        // respaldo si no hay recorte
  const LOGO = "assets/logo/logo-white.png";
  const KEY = "fm_intro_seen";

  const force = /[?&]intro\b/.test(location.search) || me?.dataset.always === "true";
  try { if (!force && sessionStorage.getItem(KEY)) return; } catch (e) {}

  const html = `
  <svg width="0" height="0" style="position:absolute" aria-hidden="true">
    <filter id="fmpopLiquid"><feTurbulence type="fractalNoise" baseFrequency="0.02 0.05" numOctaves="2" seed="4"/><feDisplacementMap in="SourceGraphic" scale="10"/></filter>
    <filter id="fmpopRough"><feTurbulence type="fractalNoise" baseFrequency="0.06" numOctaves="3"/><feDisplacementMap in="SourceGraphic" scale="30"/></filter>
  </svg>
  <span class="fmpop__splat fmpop__splat--1"></span>
  <span class="fmpop__splat fmpop__splat--2"></span>
  <span class="fmpop__splat fmpop__splat--3"></span>
  <button class="fmpop__close" aria-label="Cerrar">✕</button>
  <div class="fmpop__wrap">
    <div class="fmpop__phones" aria-hidden="true">
      <div class="fmpop__phone fmpop__phone--back">
        <div class="fmpop__dots"><i></i><i></i><i></i></div>
        <div class="fmpop__kelo">KELOKERA</div>
        <div class="fmpop__backcap">CHILDHOOD BOYS</div>
      </div>
      <div class="fmpop__phone fmpop__phone--front">
        <img class="fmpop__scribble" src="${LOGO}" alt="">
        <div class="fmpop__cut"><img alt=""></div>
        <span class="fmpop__sticker">IQQ · CL</span>
      </div>
    </div>
    <div class="fmpop__txt">
      <p class="fmpop__eyebrow">✦ Nuevo single · Kelokera</p>
      <h2 class="fmpop__name" id="fmpopTitle">Fran<br>Mera</h2>
      <p class="fmpop__desc">Cantante, compositor y productor de <b>Iquique, Chile</b>. Reggaetón desde los 14. Cofundador de <b>Childhood Boys</b>.</p>
      <div class="fmpop__actions">
        <a class="fmpop__btn fmpop__btn--main" href="${SITE}" data-enter>Entrar al sitio →</a>
        <a class="fmpop__btn fmpop__btn--ghost" href="${LISTEN}" target="_blank" rel="noopener">▶ Escuchar</a>
      </div>
    </div>
  </div>
  <p class="fmpop__label">A CHILDHOOD BOYS RECORD</p>`;

  const root = document.createElement("div");
  root.className = "fmpop";
  root.setAttribute("role", "dialog");
  root.setAttribute("aria-modal", "true");
  root.setAttribute("aria-labelledby", "fmpopTitle");
  root.innerHTML = html;

  // recorte > foto > nada
  const cut = root.querySelector(".fmpop__cut"), img = cut.querySelector("img");
  const tryImg = (src, cls, next) => { const t = new Image(); t.onload = () => { img.src = src; cut.classList.add(cls); }; t.onerror = next; t.src = src; };
  tryImg(CUTOUT, "is-cutout", () => tryImg(PHOTO, "is-photo", () => cut.remove()));

  const open = () => {
    document.body.appendChild(root);
    document.documentElement.style.overflow = "hidden";
    requestAnimationFrame(() => requestAnimationFrame(() => root.classList.add("is-open")));
    root.querySelector("[data-enter]").focus({ preventScroll: true });
  };
  const close = () => {
    try { sessionStorage.setItem(KEY, "1"); } catch (e) {}
    root.classList.add("is-closing"); root.classList.remove("is-open");
    document.documentElement.style.overflow = "";
    setTimeout(() => root.remove(), 700);
  };

  root.querySelector(".fmpop__close").addEventListener("click", close);
  root.addEventListener("click", e => { if (e.target === root) close(); });
  addEventListener("keydown", e => { if (e.key === "Escape") close(); });
  // si "Entrar" apunta a la misma página, solo cerramos
  root.querySelector("[data-enter]").addEventListener("click", e => {
    const url = new URL(SITE, location.href);
    if (url.pathname === location.pathname || SITE === "#") { e.preventDefault(); close(); }
  });

  document.readyState === "loading" ? addEventListener("DOMContentLoaded", open) : open();
})();
