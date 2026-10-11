/* =========================================================
   FRAN MERA — "En vivo": tarjetas cuadradas en diagonal que
   avanzan en bucle mientras deslizas (de arriba-izquierda a abajo-derecha).
   ========================================================= */
(() => {
  const M = (window.FM && window.FM.moments) || [];
  const sec = document.getElementById("envivo"), deck = document.getElementById("casDeck");
  if (!sec || !deck || !M.length) return;

  deck.innerHTML = M.map(m => {
    if (m.type === "photo") return `<div class="cc cc--photo" style="background-image:url(${m.img})"></div>`;
    if (m.type === "text") return `<div class="cc cc--text cc--${m.tone}${m.small ? " cc--small" : ""}">${m.lines.map(l => `<span>${l}</span>`).join("")}</div>`;
    if (m.type === "color") return `<div class="cc cc--color cc--${m.tone}${m.tilt ? " cc--tilt" : ""}"><b>${m.label}</b></div>`;
    return `<div class="cc cc--empty"><span class="mono">✕ ◷</span><em class="mono">Foto</em></div>`;
  }).join("");
  const cards = [...deck.children], N = cards.length;
  const closer = document.createElement("div"); closer.className = "cas__close"; deck.parentNode.appendChild(closer);
  const lead = cards.findIndex(c => c.classList.contains("cc--text"));   // la tarjeta que queda sola al final

  const clamp = (v, a = 0, b = 1) => Math.max(a, Math.min(b, v));
  const ease = t => t * t * (3 - 2 * t);
  function frame(t) {
    const r = sec.getBoundingClientRect();
    if (r.bottom > 0 && r.top < innerHeight) {
      const p = clamp(-r.top / (r.height - innerHeight));
      const W = innerWidth, H = innerHeight, mob = W < 700;
      const size = mob ? W * .42 : Math.min(H * .3, W * .16);
      const step = size * .62;                               // separación entre tarjetas en la diagonal
      const dx = step * (mob ? .55 : 1.1), dy = step * (mob ? 1.05 : .55);
      const run = clamp(p / .72);                            // tramo de cascada
      const join = ease(clamp((p - .72) / .14));             // se juntan en un solo cuadrado
      const grow = ease(clamp((p - .88) / .12));             // el cuadrado crece y lleva a Instagram
      const shift = (run * 1.3 * N + (join ? 0 : t / 9000)) % N;
      const cx = W / 2 - size / 2, cy = H / 2 - size / 2;
      cards.forEach((c, i) => {
        let k = ((i - shift) % N + N) % N - N / 2;
        const x0 = W / 2 + k * dx - size / 2, y0 = H * .56 + k * dy - size / 2;
        const x = x0 + (cx - x0) * join, y = y0 + (cy - y0) * join;
        c.style.width = c.style.height = size + "px";
        c.style.transform = `translate(${x}px, ${y}px)`;
        c.style.zIndex = i === lead && join > 0 ? 999 : Math.round(100 + k * 10);
        const edge = Math.abs(k) > N / 2 - .8 ? 0 : 1;
        c.style.opacity = i === lead ? 1 : edge * (1 - join);
      });
      const cover = Math.hypot(W, H) / size * 1.05;
      closer.style.width = closer.style.height = size + "px";
      closer.style.margin = `${-size / 2}px 0 0 ${-size / 2}px`;
      closer.style.opacity = grow > 0 ? 1 : 0;
      closer.style.transform = `scale(${grow > 0 ? .98 + grow * (cover - .98) : 1})`;
      closer.style.borderRadius = (1 - grow) * 4 + "px";
    }
    requestAnimationFrame(frame);
  }
  requestAnimationFrame(frame);
})();
