/* =========================================================
   FRAN MERA — interacción
   ========================================================= */
(() => {
  const D = window.FM;
  const $ = (s, r = document) => r.querySelector(s);
  const reduce = matchMedia("(prefers-reduced-motion: reduce)").matches;

  /* ---------- imágenes con placeholder automático ---------- */
  function placeholder(label, tint = "#ff1a0e", w = 900, h = 1200) {
    const c = document.createElement("canvas");
    c.width = w; c.height = h;
    const x = c.getContext("2d");
    const g = x.createRadialGradient(w * .45, h * .35, 20, w * .5, h * .5, h * .8);
    g.addColorStop(0, tint); g.addColorStop(.45, "#1a0806"); g.addColorStop(1, "#000");
    x.fillStyle = g; x.fillRect(0, 0, w, h);
    // silueta simple para que el mix se sienta "persona en escenario"
    x.fillStyle = "rgba(0,0,0,.75)";
    x.beginPath(); x.ellipse(w * .5, h * .42, w * .11, h * .085, 0, 0, Math.PI * 2); x.fill();
    x.beginPath(); x.moveTo(w * .22, h); x.quadraticCurveTo(w * .25, h * .55, w * .5, h * .53);
    x.quadraticCurveTo(w * .75, h * .55, w * .78, h); x.fill();
    const img = x.getImageData(0, 0, w, h), d = img.data;
    for (let i = 0; i < d.length; i += 4) { const n = (Math.random() - .5) * 50; d[i] += n; d[i + 1] += n; d[i + 2] += n; }
    x.putImageData(img, 0, 0);
    x.fillStyle = "rgba(239,233,223,.55)"; x.font = "700 28px 'Space Mono', monospace";
    x.fillText(`[ ${label} — SUBIR FOTO ]`, 40, h - 48);
    return c.toDataURL("image/jpeg", .8);
  }
  function resolveImg(src, label, tint) {
    return new Promise(res => {
      if (!src) return res(placeholder(label, tint));
      const i = new Image();
      i.onload = () => res(src);
      i.onerror = () => res(placeholder(label, tint));
      i.src = src;
    });
  }

  /* ---------- cursor ---------- */
  const cur = $(".cursor");
  addEventListener("pointermove", e => { cur.style.left = e.clientX + "px"; cur.style.top = e.clientY + "px"; });
  document.addEventListener("pointerover", e => cur.classList.toggle("is-hot", !!e.target.closest("a,button,.disc-scene")));

  /* ---------- contenido estático ---------- */
  const R = D.release;
  $("#deckTitle").textContent = R.title;
  $("#heroWord").textContent = R.title; $("#heroWord").dataset.text = R.title;
  $("#deckLinks").innerHTML = Object.entries(R.links).map(([k, v]) => `<a href="${v}" target="_blank" rel="noopener">${k}</a>`).join("");
  const mq = Array(2).fill([`${R.title} — DISPONIBLE AHORA`, "✦", "FRAN MERA", "✦", "A CHILDHOOD BOYS RECORD", "✦", "IQUIQUE · CHILE", "✦"].map(t => `<span>${t}</span>`).join("")).join("");
  $("#marquee").innerHTML = mq;
  $("#socials").innerHTML = Object.entries(D.socials).filter(([k]) => k !== "booking").map(([k, v]) => `<a href="${v}" target="_blank" rel="noopener">${k}</a>`).join("");
  $("#bookingLink").href = D.socials.booking;
  $("#year").textContent = new Date().getFullYear();

  /* ---------- loader ---------- */
  const photosP = Promise.all(D.livePhotos.map((p, i) => resolveImg(p.poster || p.src, p.label, i % 2 ? "#5a1a10" : "#ff1a0e")));
  const coverP = resolveImg(R.cover, R.title, "#ff1a0e");

  // logo líquido animado
  const noise = $("#liquidNoise");
  let lt = 0;
  (function liquid() { lt += .006; noise.setAttribute("baseFrequency", `${.012 + Math.sin(lt) * .004} ${.03 + Math.cos(lt * 1.3) * .01}`); if (!reduce) requestAnimationFrame(liquid); })();

  Promise.all([photosP, coverP, Promise.race([document.fonts ? document.fonts.ready : null, new Promise(r => setTimeout(r, 2500))])]).then(([photos, cover]) => {
    // el loader (logo Childhood Boys) lo cierra js/show.js cuando también cargan los cuadros del show
    window.__fmMainReady = true;
    dispatchEvent(new Event("fm:main-ready"));
    initStage(photos);
    $("#bioPhoto").style.backgroundImage = "url(assets/fotos/live-04.jpg)";
  });

  /* =========================================================
     STAGE: mix glitch de fotos del concierto
     ========================================================= */
  let stageNext = () => {};
  function initStage(photos) {
    const stage = $("#stage"), base = $("#stageBase"), flash = $("#stageFlash"), label = $("#stageLabel");
    let i = 0;
    base.style.backgroundImage = `url(${photos[0]})`;
    label.textContent = D.livePhotos[0].label;

    // clips de video dentro del mix: un <video> por clip, debajo de la foto base
    const vids = D.livePhotos.map(p => {
      if (!p.video) return null;
      const v = document.createElement("video");
      Object.assign(v, { muted: true, loop: true, playsInline: true, preload: "metadata", poster: p.poster || "" });
      v.innerHTML = `<source src="${p.video.replace(/\.mp4$/, ".webm")}" type="video/webm"><source src="${p.video}" type="video/mp4">`;
      v.className = "stage__video";
      v.style.visibility = "hidden";
      stage.insertBefore(v, base);
      return v;
    });
    const showSlide = n => {
      const v = vids[n];
      vids.forEach(x => { if (x && x !== v) { x.pause(); x.style.visibility = "hidden"; } });
      if (v) v.style.visibility = "visible";
      base.style.opacity = v ? 0 : 1;
      if (v) { v.currentTime = 0; v.play().catch(() => (base.style.opacity = 1)); }
    };

    stageNext = () => {
      i = (i + 1) % photos.length;
      const url = photos[i];
      if (reduce) { base.style.backgroundImage = `url(${url})`; return; }
      const n = 7 + Math.floor(Math.random() * 5);
      const slices = [];
      for (let s = 0; s < n; s++) {
        const el = document.createElement("div");
        el.className = "stage__slice" + (Math.random() < .25 ? " is-red" : "");
        el.style.backgroundImage = `url(${url})`;
        const top = (s / n) * 100, bot = 100 - ((s + 1) / n) * 100;
        el.style.clipPath = `inset(${top}% 0 ${bot}% 0)`;
        const off = (Math.random() - .5) * 40;
        el.animate([
          { transform: `translateX(${off}%) scaleY(1.02)`, opacity: .2 },
          { transform: `translateX(${-off * .2}%)`, opacity: 1, offset: .6 },
          { transform: "translateX(0)", opacity: 1 }
        ], { duration: 380 + Math.random() * 260, easing: "cubic-bezier(.2,.8,.2,1)", delay: s * 18, fill: "forwards" });
        stage.insertBefore(el, flash);
        slices.push(el);
      }
      flash.classList.remove("is-on"); void flash.offsetWidth; flash.classList.add("is-on");
      glitchWord();
      setTimeout(() => { base.style.backgroundImage = `url(${url})`; label.textContent = D.livePhotos[i].label; showSlide(i); slices.forEach(s => s.remove()); }, 900);
    };

    // auto-mix cuando no está sonando (cuando suena, va al beat)
    setInterval(() => { if (!playing) stageNext(); }, 2800);

    // parallax suave
    addEventListener("pointermove", e => {
      const x = (e.clientX / innerWidth - .5) * -20, y = (e.clientY / innerHeight - .5) * -14;
      base.style.transform = `translate(${x}px, ${y}px) scale(1.04)`;
    });

    const clock = $("#stageClock"), t0 = Date.now();
    setInterval(() => {
      const s = Math.floor((Date.now() - t0) / 1000);
      clock.textContent = [s / 3600, (s / 60) % 60, s % 60].map(v => String(Math.floor(v)).padStart(2, "0")).join(":");
    }, 1000);
  }
  function glitchWord() {
    const w = $("#heroWord");
    w.classList.remove("is-glitch"); void w.offsetWidth; w.classList.add("is-glitch");
  }

  /* =========================================================
     KARAOKE + PLAYER
     ========================================================= */
  const audio = $("#audio");
  const hasAudio = !!R.audio;
  if (hasAudio) audio.src = R.audio;
  const lines = R.lyrics;
  const demoDur = lines[lines.length - 1].t + 1.2;
  let playing = false, demoT = 0, last = 0, curLine = -1, lastBeat = -1;

  /* ---- coro desde YouTube (mini reproductor) ---- */
  const YTC = R.youtube || null;
  const ytUrl = YTC ? `https://youtu.be/${YTC.id}${YTC.chorusStart ? "?t=" + YTC.chorusStart : ""}` : "#";
  let yt = null, ytReady = false;
  const card = document.createElement("aside");
  card.className = "ytcard"; card.setAttribute("aria-label", "Reproductor del coro de Kelokera");
  card.innerHTML = `<div class="ytcard__media"><div id="ytPlayer"></div><img class="ytcard__poster" src="assets/video/kelokera.jpg" alt=""></div>
    <div class="ytcard__info"><span class="mono">● Sonando · coro</span><b>${R.title}</b><span class="mono muted">Fran Mera</span>
    <a class="ytcard__cta mono" href="${ytUrl}" target="_blank" rel="noopener">Ver video completo ↗</a></div>
    <button class="ytcard__close" aria-label="Cerrar">✕</button>`;
  document.body.appendChild(card);
  card.querySelector(".ytcard__close").addEventListener("click", () => { setPlaying(false); card.classList.remove("is-open"); });
  if (YTC) {
    // el reproductor se prepara al cargar la página, para que el click pueda darle play con sonido
    window.onYouTubeIframeAPIReady = () => {
      yt = new YT.Player("ytPlayer", {
        width: "100%", height: "100%", videoId: YTC.id,
        playerVars: { start: YTC.chorusStart, playsinline: 1, rel: 0, controls: 0, modestbranding: 1 },
        events: { onReady: () => { ytReady = true; card.classList.add("has-video"); } }
      });
    };
    const tag = document.createElement("script");
    tag.src = "https://www.youtube.com/iframe_api";
    document.head.appendChild(tag);
  }
  const useYT = () => ytReady && yt && yt.getCurrentTime;

  const dur = () => useYT() ? YTC.chorusLength : (hasAudio && audio.duration) || demoDur;
  const now = () => useYT() ? Math.max(0, yt.getCurrentTime() - YTC.chorusStart) : hasAudio ? audio.currentTime : demoT;
  const fmt = s => `${Math.floor(s / 60)}:${String(Math.floor(s % 60)).padStart(2, "0")}`;
  audio.addEventListener("loadedmetadata", () => $("#tDur").textContent = fmt(dur()));
  $("#tDur").textContent = fmt(YTC ? YTC.chorusLength : demoDur);

  function setPlaying(v) {
    playing = v;
    $("#lyric").classList.toggle("is-playing", v);
    $("#playBtn").setAttribute("aria-label", v ? "Pausar" : "Reproducir");
    if (v) card.classList.add("is-open"), card.classList.remove("is-end");
    if (useYT()) {
      if (v) { if (now() >= YTC.chorusLength - .5) yt.seekTo(YTC.chorusStart, true); yt.unMute(); yt.playVideo(); }
      else yt.pauseVideo();
    } else if (hasAudio) v ? audio.play().catch(() => setPlaying(false)) : audio.pause();
    last = performance.now();
  }
  function chorusEnd() { setPlaying(false); demoT = 0; card.classList.add("is-end"); } // termina el coro → invita al video
  $("#playBtn").addEventListener("click", () => setPlaying(!playing));
  document.querySelectorAll("[data-play]").forEach(a => a.addEventListener("click", () => setPlaying(true)));
  audio.addEventListener("ended", () => setPlaying(false));

  /* afiche: cada palabra es una línea que se ajusta al ancho del afiche */
  const poster = $("#poster"), stack = $("#stack");
  let words = [];
  function fitStack() {
    const W = stack.clientWidth, H = poster.clientHeight * .9;
    if (!W || !words.length) return;
    const inner = words.map(w => w.firstChild);
    // 1) tamaño que llena el ancho, con tope para que las palabras cortas no se coman el afiche
    const cap = H / (words.length * .94) * 1.5;
    let sizes = inner.map(i => { i.style.transform = "none"; i.parentNode.style.fontSize = "100px"; return Math.min(cap, 100 * W / Math.max(1, i.offsetWidth)); });
    // 2) si el total no cabe en alto, se reduce todo parejo
    const k = Math.min(1, H / sizes.reduce((a, b) => a + b * .94, 0));
    sizes = sizes.map(v => v * k);
    // 3) cada línea se estira a lo ancho hasta tocar los bordes (look afiche)
    words.forEach((w, i) => { w.style.fontSize = sizes[i] + "px"; });
    inner.forEach(i => { i.style.transform = `scaleX(${W / Math.max(1, i.offsetWidth)})`; });
  }
  function renderLine(n) {
    const txt = (lines[n]?.text || "").trim();
    stack.innerHTML = txt ? txt.split(/\s+/).map(w => `<span class="pw"><i>${w}</i></span>`).join("") : "";
    words = [...stack.children];
    fitStack();
    requestAnimationFrame(fitStack);
  }
  addEventListener("resize", fitStack);
  document.fonts.ready.then(fitStack);
  renderLine(0);

  function updateKaraoke(t) {
    let n = 0;
    for (let i = 0; i < lines.length; i++) if (t >= lines[i].t) n = i;
    if (n !== curLine) { curLine = n; renderLine(n); }
    const start = lines[n].t, end = lines[n + 1]?.t ?? dur();
    const p = Math.min(1, (t - start) / ((end - start) * .8));
    // las palabras aparecen a medida que se cantan (solo mientras suena)
    words.forEach((w, i) => w.classList.toggle("on", playing && p >= i / words.length));
  }

  function loop(ts) {
    const dt = (ts - last) / 1000; last = ts;
    if (playing && !useYT() && !hasAudio) { demoT += dt; if (demoT >= demoDur) chorusEnd(); }
    const t = now();
    if (playing && useYT() && t >= YTC.chorusLength) { yt.pauseVideo(); chorusEnd(); }
    updateKaraoke(t);
    $("#tCur").textContent = fmt(t);
    if (playing) {
      // cambio de foto cada 4 beats (1 compás)
      const beat = Math.floor(t / (60 / R.bpm));
      if (beat !== lastBeat) { lastBeat = beat; if (beat % 4 === 0) stageNext(); else if (beat % 2 === 0) glitchWord(); }
    }
    requestAnimationFrame(loop);
  }
  requestAnimationFrame(t => { last = t; loop(t); });

  /* =========================================================
     DISCOS: voltear para cambiar de tema
     ========================================================= */
  const T = D.topTracks;
  const disc = $("#disc"), faceA = $("#faceA"), faceB = $("#faceB"), scene = $("#discScene");
  let idx = 0, angle = 0;
  const wrap = i => (i + T.length) % T.length;

  function faceHTML(i) {
    const t = T[i];
    const label = t.cover ? `style="--c:${t.color};background-image:url(${t.cover})"` : `style="--c:${t.color}"`;
    return `<div class="vinyl"><span class="vinyl__rank">#${String(i + 1).padStart(2, "0")} MÁS ESCUCHADO</span>
      <div class="vinyl__spin"><div class="vinyl__label" ${label}>${t.cover ? "" : `<b>${t.title}</b><small>FRAN MERA · ${t.time}</small>`}</div></div></div>`;
  }
  const visibleA = a => Math.round(a / 180) % 2 === 0;
  const hiddenFace = a => visibleA(a) ? faceB : faceA;
  function setAngle(a, snap) {
    disc.classList.toggle("is-snapping", !!snap);
    disc.style.transform = `rotateX(8deg) rotateY(${a}deg)`;
  }
  function caption(i) {
    const c = $("#discCaption");
    $("#capTitle").textContent = T[i].title;
    $("#capMeta").textContent = `#${String(i + 1).padStart(2, "0")} · ${T[i].time} · ${T[i].plays} REPRODUCCIONES`;
    $("#capLink").href = T[i].spotify;
    c.classList.remove("is-swap"); void c.offsetWidth; c.classList.add("is-swap");
    document.querySelectorAll("#tracklist button").forEach((b, j) => b.classList.toggle("is-active", j === i));
  }
  function flipTo(target, dir) {
    if (target === idx) return;
    hiddenFace(angle).innerHTML = faceHTML(target);
    angle = Math.round(angle / 180) * 180 + (dir > 0 ? -180 : 180);
    idx = target;
    setAngle(angle, true);
    caption(idx);
  }

  $("#tracklist").innerHTML = T.map((t, i) => `<li><button data-i="${i}">${String(i + 1).padStart(2, "0")} ${t.title}</button></li>`).join("");
  $("#tracklist").addEventListener("click", e => {
    const b = e.target.closest("button"); if (!b) return;
    const j = +b.dataset.i; flipTo(j, j > idx ? 1 : -1);
  });
  $("#nextDisc").addEventListener("click", () => flipTo(wrap(idx + 1), 1));
  $("#prevDisc").addEventListener("click", () => flipTo(wrap(idx - 1), -1));
  addEventListener("keydown", e => {
    if (!scene.matches(":hover") && document.activeElement?.closest(".discos") == null) return;
    if (e.key === "ArrowRight") flipTo(wrap(idx + 1), 1);
    if (e.key === "ArrowLeft") flipTo(wrap(idx - 1), -1);
  });

  // arrastre
  let drag = null;
  scene.addEventListener("pointerdown", e => {
    angle = Math.round(angle / 180) * 180;
    drag = { x: e.clientX, a0: angle, dir: 0 };
    scene.setPointerCapture(e.pointerId);
  });
  scene.addEventListener("pointermove", e => {
    if (!drag) return;
    const dx = e.clientX - drag.x;
    const dir = dx < 0 ? 1 : -1;
    if (dir !== drag.dir && Math.abs(dx) > 4) { drag.dir = dir; hiddenFace(drag.a0).innerHTML = faceHTML(wrap(idx + dir)); }
    angle = drag.a0 + Math.max(-200, Math.min(200, dx * .6));
    setAngle(angle, false);
  });
  const end = () => {
    if (!drag) return;
    const moved = angle - drag.a0;
    if (Math.abs(moved) > 55) { idx = wrap(idx + drag.dir); angle = drag.a0 + (moved < 0 ? -180 : 180); caption(idx); }
    else angle = drag.a0;
    setAngle(angle, true);
    drag = null;
  };
  scene.addEventListener("pointerup", end);
  scene.addEventListener("pointercancel", end);

  faceA.innerHTML = faceHTML(0);
  setAngle(0);
  caption(0);

  /* =========================================================
     CHILDHOOD BOYS
     ========================================================= */
  const L = D.label;
  $("#cbTagline").textContent = L.tagline;
  $("#cbContact").href = L.contact;
  if (L.logo) {
    const i = new Image();
    i.onload = () => { $("#cbLogo").innerHTML = ""; i.alt = L.name; $("#cbLogo").appendChild(i); };
    i.src = L.logo;
  }
  Promise.all(L.founders.map(f => resolveImg(f.photo, f.name, "#f5b92e"))).then(urls => {
    $("#cbFounders").innerHTML = L.founders.map((f, i) => `
      <article class="founder reveal"><div class="founder__img" style="background-image:url(${urls[i]})"></div>
      <div class="founder__txt"><b>${f.name}</b><span class="mono">${f.role}</span></div></article>`).join("");
    observeReveals();
  });
  $("#cbServices").innerHTML = L.services.map(s => `<div class="service"><span class="mono">${s.n}</span><h3>${s.title}</h3><p>${s.text}</p></div>`).join("");
  $("#sparkles").innerHTML = Array.from({ length: 26 }, () =>
    `<i class="spark" style="left:${Math.random() * 100}%;top:${Math.random() * 100}%;--d:${2 + Math.random() * 3}s;--dl:${Math.random() * 3}s;transform:scale(${.5 + Math.random()})"></i>`).join("");

  /* ---------- reveals + contador ---------- */
  const io = new IntersectionObserver(es => es.forEach(e => {
    if (!e.isIntersecting) return;
    e.target.classList.add("is-in");
    e.target.querySelectorAll("[data-count]").forEach(el => {
      const to = +el.dataset.count, t0 = performance.now();
      (function step(t) { const p = Math.min(1, (t - t0) / 1200); el.textContent = Math.round(to * p); if (p < 1) requestAnimationFrame(step); })(t0);
    });
    io.unobserve(e.target);
  }), { threshold: .2 });
  function observeReveals() { document.querySelectorAll(".reveal:not(.is-in)").forEach(el => io.observe(el)); }
  observeReveals();
})();
