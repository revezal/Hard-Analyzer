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

  const clamp = (v, a = 0, b = 1) => Math.max(a, Math.min(b, v));
  function frame(t) {
    const r = sec.getBoundingClientRect();
    if (r.bottom > 0 && r.top < innerHeight) {
      const p = clamp(-r.top / (r.height - innerHeight));
      const W = innerWidth, H = innerHeight, mob = W < 700;
      const size = mob ? W * .42 : Math.min(H * .3, W * .16);
      const step = size * .62;                               // separación entre tarjetas en la diagonal
      const dx = step * (mob ? .55 : 1.1), dy = step * (mob ? 1.05 : .55);
      const shift = (p * 1.3 * N + t / 9000) % N;            // avanza con el scroll y un poco solo (bucle)
      cards.forEach((c, i) => {
        let k = ((i - shift) % N + N) % N - N / 2;           // posición en la diagonal (-N/2 .. N/2)
        const x = W / 2 + k * dx - size / 2, y = H * .56 + k * dy - size / 2;
        c.style.width = c.style.height = size + "px";
        c.style.transform = `translate(${x}px, ${y}px)`;
        c.style.zIndex = Math.round(100 + k * 10);
        c.style.opacity = Math.abs(k) > N / 2 - .8 ? 0 : 1;
      });
    }
    requestAnimationFrame(frame);
  }
  requestAnimationFrame(frame);
})();
