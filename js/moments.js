/* =========================================================
   FRAN MERA — "En vivo": cada foto parte como un círculo arriba
   y se abre en gigante hacia abajo; al abrirse aparece la historia
   del momento (día, fecha, lugar, qué hizo y cómo lo vivió).
   ========================================================= */
(() => {
  const M = (window.FM && window.FM.moments) || [];
  const list = document.getElementById("liveList");
  if (!list || !M.length) return;

  list.innerHTML = M.map((m, i) => `
    <article class="mo">
      <div class="mo__s">
        <div class="mo__img" style="background-image:url(${m.img})"></div>
        <p class="mo__num mono">${String(i + 1).padStart(2, "0")} / ${String(M.length).padStart(2, "0")}</p>
        <div class="mo__card">
          <p class="mono mo__when">${m.day} · ${m.date}</p>
          <h3 class="display mo__title">${m.title}.</h3>
          <dl class="mo__facts">
            <div><dt class="mono">Lugar</dt><dd>${m.place}</dd></div>
            <div><dt class="mono">Qué hizo</dt><dd>${m.what}</dd></div>
            <div><dt class="mono">Cómo lo vivió</dt><dd>${m.how}</dd></div>
          </dl>
        </div>
      </div>
    </article>`).join("");

  const items = [...list.querySelectorAll(".mo")].map(el => ({ el, img: el.querySelector(".mo__img"), card: el.querySelector(".mo__card") }));
  const clamp = (v, a = 0, b = 1) => Math.max(a, Math.min(b, v));
  const ease = t => 1 - Math.pow(1 - t, 3);
  const lerp = (a, b, t) => a + (b - a) * t;

  function frame() {
    const W = innerWidth, H = innerHeight;
    items.forEach(({ el, img, card }) => {
      const r = el.getBoundingClientRect();
      if (r.bottom < 0 || r.top > H) return;
      const p = clamp(-r.top / (r.height - H));
      const o = ease(clamp(p / .55));                         // se abre
      const d = Math.min(W, H) * .26, top0 = H * .16;         // círculo inicial arriba al centro
      const t = lerp(top0, 0, o), side = lerp((W - d) / 2, 0, o), bottom = lerp(H - top0 - d, 0, o);
      img.style.clipPath = `inset(${t}px ${side}px ${bottom}px ${side}px round ${lerp(d / 2, 0, o)}px)`;
      img.style.transform = `scale(${lerp(1.15, 1, o)})`;
      card.classList.toggle("is-on", o > .82);
    });
    requestAnimationFrame(frame);
  }
  requestAnimationFrame(frame);
})();
