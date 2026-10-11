/* =========================================================
   FRAN MERA — videoclip KELOKERA que se cierra en un círculo
   Al bajar, el video (pantalla completa) se cierra como un iris hasta
   convertirse en el círculo de KELOKERA dentro de la fila de canciones,
   que luego avanza de lado. Click en un círculo → año y reproducciones.
   ========================================================= */
(() => {
  const $ = s => document.querySelector(s);
  const S = (window.FM && window.FM.songs) || [];
  const sec = $("#video"), stage = $("#kvStage"), track = $("#kvTrack"), vid = $("#kvVideo"), intro = $("#kvIntro"), line = $("#kvLine");
  if (!sec || !S.length) return;

  /* ---- armar la fila: grandes/medianos solos, chicos de a dos en columna ---- */
  const size = it => it.big ? "big" : it.small ? "small" : "mid";
  const bubble = it => {
    const song = it.type === "song";
    const bg = it.img || it.cover;
    const info = song
      ? `<b>${it.title}</b><span>Año <em>${it.year || "—"}</em></span><span>${it.plays ? (/^\d/.test(it.plays) ? it.plays + " reproducciones" : it.plays) : "Videoclip"}</span>`
      : `<b>en vivo</b><span>Fran Mera</span>`;
    return `<button class="bub bub--${size(it)} ${song ? "bub--song" : "bub--moment"}" ${it.video ? 'data-video' : ""}
      style="--c:${it.color || "#222"};${bg ? `background-image:url(${bg})` : ""}" aria-label="${song ? it.title : "Foto en vivo"}">
      ${song && !bg ? `<span class="bub__t">${it.title}</span>` : ""}<span class="bub__info">${info}</span></button>`;
  };
  let html = "", i = 0;
  while (i < S.length) {
    if (size(S[i]) === "small" && S[i + 1] && size(S[i + 1]) === "small") { html += `<div class="kv__col">${bubble(S[i])}${bubble(S[i + 1])}</div>`; i += 2; }
    else { html += `<div class="kv__col">${bubble(S[i])}</div>`; i++; }
  }
  track.innerHTML = html;
  const slot = track.querySelector("[data-video]");
  // la info del círculo de KELOKERA se dibuja encima del video (que tapa al círculo)
  const vinfo = document.createElement("div");
  vinfo.className = "kv__vinfo";
  if (slot) { vinfo.innerHTML = slot.querySelector(".bub__info").innerHTML; stage.appendChild(vinfo); }

  /* ---- click: el círculo se abre en rectángulo con el año y las reproducciones ---- */
  track.addEventListener("click", e => {
    const b = e.target.closest(".bub"); if (!b) return;
    const open = !b.classList.contains("is-open");
    track.querySelectorAll(".bub.is-open").forEach(x => x.classList.remove("is-open"));
    if (open) { b.style.setProperty("--w", b.offsetHeight + "px"); b.classList.add("is-open"); }
  });

  /* ---- scroll ---- */
  const clamp = (v, a = 0, b = 1) => Math.max(a, Math.min(b, v));
  const ease = t => t * t * (3 - 2 * t);
  const lerp = (a, b, t) => a + (b - a) * t;

  function frame() {
    const r = sec.getBoundingClientRect();
    if (r.bottom > 0 && r.top < innerHeight) {
      const p = clamp(-r.top / (r.height - innerHeight));
      const W = stage.clientWidth, H = stage.clientHeight;
      const close = ease(clamp((p - .1) / .28));   // el iris se cierra
      const slide = clamp((p - .38) / .62);         // la fila avanza

      // posición de la fila: primero KELOKERA al centro, después avanza hasta el final
      const tw = track.scrollWidth;
      const sc = slot ? slot.offsetLeft + slot.offsetParent.offsetLeft + slot.offsetWidth / 2 : tw / 2;
      const x0 = W / 2 - sc, x1 = Math.min(x0, W - tw);
      track.style.transform = `translateX(${lerp(x0, x1, slide)}px)`;

      // el video se recorta hacia el rectángulo del círculo de KELOKERA
      if (slot) {
        const sr = stage.getBoundingClientRect(), b = slot.getBoundingClientRect();
        const rad = slot.classList.contains("is-open") ? 14 : b.width / 2;
        const top = lerp(0, b.top - sr.top, close), left = lerp(0, b.left - sr.left, close);
        const right = lerp(0, sr.right - b.right, close), bottom = lerp(0, sr.bottom - b.bottom, close);
        vid.style.clipPath = `inset(${top}px ${right}px ${bottom}px ${left}px round ${lerp(0, rad, close)}px)`;
        const open = slot.classList.contains("is-open") && close > .99;
        vinfo.classList.toggle("is-on", open);
        if (open) Object.assign(vinfo.style, { left: b.left - sr.left + "px", top: b.bottom - sr.top + "px", width: b.width + "px" });
      }
      stage.style.setProperty("--show", close.toFixed(3));
      stage.style.setProperty("--intro", (1 - clamp(close * 2.5)).toFixed(3));
      intro.classList.toggle("is-gone", close > .3);
      line.style.width = p * 100 + "%";
    }
    requestAnimationFrame(frame);
  }
  requestAnimationFrame(frame);
})();
