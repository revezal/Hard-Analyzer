/* =========================================================
   FRAN MERA — efectos de la segunda mitad:
   1) Los hermanos: las letras de CHILDHOOD BOYS se separan y aparece la historia.
   2) Final: KELOKERA a pantalla completa con karaoke; al seguir bajando, redes y manager.
   ========================================================= */
(() => {
  const clamp = (v, a = 0, b = 1) => Math.max(a, Math.min(b, v));
  const ease = t => t * t * (3 - 2 * t);
  const prog = el => { const r = el.getBoundingClientRect(); return { p: clamp(-r.top / (r.height - innerHeight)), vis: r.bottom > 0 && r.top < innerHeight }; };

  /* ---- 1. el logo Childhood Boys (letras reales) se desarma y aparece la historia ---- */
  const cbx = document.getElementById("childhood"), logo = document.getElementById("cbxLogo"), info = document.getElementById("cbxInfo");
  const letters = (window.CB_LETTERS || []).map((L, i) => {
    const img = document.createElement("img");
    img.src = L.src; img.alt = "";
    Object.assign(img.style, { left: L.x + "%", top: L.y + "%", width: L.w + "%", height: L.h + "%", animationDelay: (-i * .17) + "s" });
    logo && logo.appendChild(img);
    const cx = L.x + L.w / 2 - 50, cy = L.y + L.h / 2 - 50, a = Math.atan2(cy, cx) + (Math.random() - .5) * .9;
    img._v = { x: Math.cos(a), y: Math.sin(a), r: (Math.random() - .5) * 160, d: .8 + Math.random() * .6 };
    return img;
  });

  /* ---- burbuja de entrada al final ---- */
  const finCb = document.getElementById("finCb"), finBlack = document.getElementById("finBlack");

  /* ---- 2. final ---- */
  const fin = document.getElementById("booking"), end = document.getElementById("finEnd"), hero = document.getElementById("finHero");
  const ops = [...document.querySelectorAll(".op__a")];

  function frame() {
    if (cbx) {
      const { p, vis } = prog(cbx);
      if (vis) {
        const t = ease(clamp((p - .1) / .5));
        const R = Math.hypot(innerWidth, innerHeight) * .6;
        logo.classList.toggle("is-open", t > 0);
        letters.forEach(img => {
          const v = img._v;
          img.style.transform = `translate(${v.x * t * R * v.d}px, ${v.y * t * R * v.d}px) rotate(${v.r * t}deg) scale(${1 + t * .5})`;
          img.style.opacity = 1 - clamp((t - .6) / .4);
        });
        const q = ease(clamp((p - .3) / .35));
        info.style.opacity = q; info.style.transform = `scale(${.9 + q * .1})`;
        info.style.pointerEvents = q > .5 ? "auto" : "none";
      }
    }
    if (fin) {
      const { p, vis } = prog(fin);
      if (vis) {
        const W = innerWidth, H = innerHeight, full = Math.hypot(W, H) / 2 + 2, small = Math.min(W, H) * .14;
        const close = ease(clamp(p / .14));                      // Childhood Boys se cierra en burbuja
        const open = ease(clamp((p - .18) / .16));               // por el círculo se abre KELOKERA
        finCb.style.clipPath = `circle(${full - (full - small) * close}px at 50% 50%)`;
        finCb.style.opacity = 1 - clamp((p - .14) / .04);
        const hole = p < .14 ? 0 : small + (full - small) * open;
        finBlack.style.webkitMaskImage = finBlack.style.maskImage = `radial-gradient(circle at 50% 50%, transparent ${hole}px, #000 ${hole + 1}px)`;
        ops.forEach((o, i) => o.classList.toggle("is-on", p > .3 + i * .06));   // los artistas aparecen uno por uno
        const e = ease(clamp((p - .72) / .18));                  // aparecen redes y manager
        end.style.opacity = e; end.style.pointerEvents = e > .5 ? "auto" : "none";
        hero.style.setProperty("--fade", e.toFixed(3));
        document.body.classList.toggle("fin-end", e > .3);
      }
    }
    requestAnimationFrame(frame);
  }
  requestAnimationFrame(frame);
})();
