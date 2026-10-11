/* =========================================================
   FRAN MERA — efectos de la segunda mitad:
   1) Los hermanos: las letras de CHILDHOOD BOYS se separan y aparece la historia.
   2) Final: KELOKERA a pantalla completa con karaoke; al seguir bajando, redes y manager.
   ========================================================= */
(() => {
  const clamp = (v, a = 0, b = 1) => Math.max(a, Math.min(b, v));
  const ease = t => t * t * (3 - 2 * t);
  const prog = el => { const r = el.getBoundingClientRect(); return { p: clamp(-r.top / (r.height - innerHeight)), vis: r.bottom > 0 && r.top < innerHeight }; };

  /* ---- 1. letras que se separan ---- */
  const bros = document.getElementById("hermanos"), word = document.getElementById("brosWord");
  const photo = document.getElementById("brosPhoto"), story = document.getElementById("brosStory");
  const letters = [];
  if (word) {
    "CHILDHOOD BOYS".split(" ").forEach((w, wi) => {
      const row = document.createElement("div"); row.className = "bros__row";
      [...w].forEach(ch => {
        const s = document.createElement("span"); s.textContent = ch;
        const a = Math.random() * Math.PI * 2, d = 0.6 + Math.random() * 0.7;
        s._v = { x: Math.cos(a) * d, y: Math.sin(a) * d, r: (Math.random() - .5) * 220 };
        row.appendChild(s); letters.push(s);
      });
      word.appendChild(row);
    });
  }

  /* ---- 2. final ---- */
  const fin = document.getElementById("kelokera"), end = document.getElementById("finEnd"), hero = document.getElementById("finHero");
  let started = false;

  function frame() {
    if (bros) {
      const { p, vis } = prog(bros);
      if (vis) {
        const t = ease(clamp((p - .08) / .5));
        letters.forEach(s => {
          const v = s._v;
          s.style.transform = `translate(${v.x * t * innerWidth * .6}px, ${v.y * t * innerHeight * .7}px) rotate(${v.r * t}deg)`;
          s.style.opacity = 1 - clamp((t - .55) / .45);
        });
        const q = ease(clamp((p - .25) / .4));
        photo.style.transform = `scale(${.55 + q * .45})`; photo.style.opacity = q;
        story.classList.toggle("is-on", p > .55);
      }
    }
    if (fin) {
      const { p, vis } = prog(fin);
      if (vis) {
        if (!started && p > .02) { started = true; const b = document.getElementById("playBtn"); if (b && !document.getElementById("lyric").classList.contains("is-playing")) b.click(); const c = document.querySelector(".ytcard"); if (c) c.classList.remove("is-open"); }
        const e = ease(clamp((p - .55) / .3));               // aparecen redes y manager
        end.style.opacity = e; end.style.pointerEvents = e > .5 ? "auto" : "none";
        hero.style.setProperty("--fade", e.toFixed(3));
        document.body.classList.toggle("fin-end", e > .3);
      }
    }
    requestAnimationFrame(frame);
  }
  requestAnimationFrame(frame);
})();
