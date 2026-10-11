/* =========================================================
   FRAN MERA — sección Instagram: el nombre gigante pasa detrás
   de un teléfono y, mientras deslizas, cambia la publicación.
   ========================================================= */
(() => {
  const P = (window.FM && window.FM.instagram) || [];
  const sec = document.getElementById("instagram"), screen = document.getElementById("igScreen"),
        word = document.getElementById("igWord"), bar = document.getElementById("igBar");
  if (!sec || !P.length) return;

  screen.innerHTML = P.map((p, i) => `
    <div class="post${i ? "" : " is-on"}">
      <div class="post__top"><img src="assets/fotos/fran-cb.jpg" alt=""><b>franmera</b><span>···</span></div>
      <div class="post__media">${p.video
        ? `<video src="${p.video}" poster="${p.poster}" muted loop autoplay playsinline></video>`
        : `<div style="background-image:url(${p.img})"></div>`}</div>
      <div class="post__actions">♡ &nbsp;💬 &nbsp;➤<span>🔖</span></div>
      <p class="post__likes">${p.likes} Me gusta</p>
      <p class="post__cap"><b>franmera</b> ${p.caption}</p>
      <div class="post__dots">${P.map((_, j) => `<i class="${j === i ? "on" : ""}"></i>`).join("")}</div>
    </div>`).join("");
  const posts = [...screen.children];
  let cur = 0;

  const clamp = (v, a = 0, b = 1) => Math.max(a, Math.min(b, v));
  function frame() {
    const r = sec.getBoundingClientRect();
    if (r.bottom > 0 && r.top < innerHeight) {
      const p = clamp(-r.top / (r.height - innerHeight));
      const travel = word.scrollWidth - innerWidth;
      word.style.transform = `translate(${-p * travel}px, -50%)`;     // el nombre corre detrás del teléfono
      const n = Math.min(P.length - 1, Math.floor(p * P.length));
      if (n !== cur) { posts[cur].classList.remove("is-on"); posts[n].classList.add("is-on"); cur = n; }
      bar.style.width = p * 100 + "%";
    }
    requestAnimationFrame(frame);
  }
  requestAnimationFrame(frame);
})();
