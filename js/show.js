/* =========================================================
   FRAN MERA — loader Childhood Boys + banner "show" que avanza al deslizar
   Los cuadros del video (js/show-frames.js) se dibujan en un canvas según el scroll:
   al bajar (o arrastrar con el mouse) la cámara avanza y se acerca, aparecen las fichas
   y al final cierra con la tipografía pixel.
   ========================================================= */
(() => {
  const $ = s => document.querySelector(s);
  const FR = window.SHOW_FRAMES || [];
  const N = FR.length;
  const reduce = matchMedia("(prefers-reduced-motion: reduce)").matches;

  /* ---------- loader ---------- */
  const loader = $("#loader"), pctEl = $("#loadPct"), jelly = $("#jellyNoise");
  let loaded = 0, mainReady = !!window.__fmMainReady, done = false;
  const t0 = performance.now();
  addEventListener("fm:main-ready", () => { mainReady = true; check(); });

  (function wobble(t) { // las letras "respiran" como globos
    if (done) return;
    const k = t / 1000;
    jelly.setAttribute("baseFrequency", `${.008 + Math.sin(k * 1.7) * .003} ${.012 + Math.cos(k * 1.3) * .004}`);
    requestAnimationFrame(wobble);
  })(0);

  const imgs = FR.map(src => {
    const i = new Image();
    i.decoding = "async";
    i.onload = i.onerror = () => { loaded++; pctEl.textContent = Math.round(loaded / N * 100); check(); };
    i.src = src;
    return i;
  });
  if (!N) pctEl.textContent = 100;

  function check() {
    if (done || loaded < N || !mainReady) return;
    done = true;
    const wait = Math.max(0, 1600 - (performance.now() - t0)); // que el logo alcance a verse
    setTimeout(() => {
      loader.classList.add("is-leaving");                     // se infla antes de entrar
      setTimeout(() => loader.classList.add("is-done"), 650);
      draw(true);
    }, wait + 450);
  }

  /* ---------- banner show ---------- */
  const sec = $("#show"), stage = $("#showStage"), cv = $("#showCv"), cx = cv.getContext("2d");
  const shade = $("#showShade"), bar = $("#showBar"), frameEl = $("#showFrame"), hint = $("#showHint");
  const calls = [...document.querySelectorAll(".call")];
  const pxItems = [...document.querySelectorAll(".px__row span, .px__label")];
  const FIRST = Math.min(3, N - 1), // arranca en el letrero "Fran Mera" del camarín
        LAST = Math.max(FIRST, N - 13), CROWD = Math.min(N - 1, 103);
  let W = 0, H = 0, cur = FIRST, lastKey = "";

  function size() {
    const dpr = Math.min(2, devicePixelRatio || 1);
    W = stage.clientWidth; H = stage.clientHeight;
    cv.width = Math.round(W * dpr); cv.height = Math.round(H * dpr);
    cx.setTransform(dpr, 0, 0, dpr, 0, 0);
    lastKey = "";
  }
  addEventListener("resize", size); size();

  const clamp = (v, a = 0, b = 1) => Math.max(a, Math.min(b, v));
  const ease = t => t * t * (3 - 2 * t);
  const progress = () => { const r = sec.getBoundingClientRect(); return clamp(-r.top / (r.height - innerHeight)); };

  function draw(force) {
    const p = progress();
    // el video (sin el fundido a negro del inicio y del final) ocupa el 82% del recorrido;
    // en el cierre queda de fondo el cuadro del público con las manos arriba
    const target = p < .82 ? FIRST + clamp(p / .82) * (LAST - FIRST) : CROWD;
    cur = reduce || force || Math.abs(target - cur) > 20 ? target : cur + (target - cur) * .22;
    const idx = Math.round(cur);
    const zoom = 1 + .32 * ease(clamp(p / .82));               // la cámara se acerca mientras avanzas
    const key = idx + "|" + zoom.toFixed(3) + "|" + W + "x" + H;
    const img = imgs[idx];
    if (key !== lastKey && img && img.complete && img.naturalWidth) {
      lastKey = key;
      const s = Math.max(W / img.naturalWidth, H / img.naturalHeight) * zoom; // cubrir toda la pantalla
      const dw = img.naturalWidth * s, dh = img.naturalHeight * s;
      cx.drawImage(img, (W - dw) / 2, (H - dh) / 2 - (zoom - 1) * H * .08, dw, dh);
    }

    calls.forEach(c => c.classList.toggle("is-on", p >= +c.dataset.from && p < +c.dataset.to));
    const end = clamp((p - .8) / .14);                         // cierre con tipografía pixel
    shade.style.setProperty("--dark", (end * .5).toFixed(3));
    pxItems.forEach((el, i) => el.classList.toggle("on", end > 0 && end >= i / pxItems.length));
    hint.style.opacity = p > .02 ? 0 : 1;
    bar.style.width = p * 100 + "%";
    frameEl.textContent = `${String(idx + 1).padStart(3, "0")} / ${N}`;
  }
  (function loop() { draw(false); requestAnimationFrame(loop); })();

  /* arrastrar con el mouse también avanza (en celular el dedo ya desliza la página) */
  let drag = null;
  stage.addEventListener("pointerdown", e => {
    if (e.pointerType !== "mouse") return;
    drag = { x: e.clientX, y: e.clientY };
    stage.classList.add("is-drag");
    stage.setPointerCapture(e.pointerId);
  });
  stage.addEventListener("pointermove", e => {
    if (!drag) return;
    const d = (drag.y - e.clientY) + (drag.x - e.clientX);
    drag = { x: e.clientX, y: e.clientY };
    scrollBy({ top: d * 2.2, behavior: "instant" });
  });
  const stop = () => { drag = null; stage.classList.remove("is-drag"); };
  stage.addEventListener("pointerup", stop);
  stage.addEventListener("pointercancel", stop);
})();
