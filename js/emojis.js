/* Emojis que saltan mientras deslizas (uno cada ~260 px de scroll) */
(() => {
  if (matchMedia("(prefers-reduced-motion: reduce)").matches) return;
  const E = ["🔥", "🎤", "💿", "⚡", "🖤", "✨", "🎧", "💸", "🍷", "❤️‍🔥", "🌙", "👑"];
  let last = scrollY, acc = 0;
  addEventListener("scroll", () => {
    acc += Math.abs(scrollY - last); last = scrollY;
    if (acc < 260) return;
    acc = 0;
    const el = document.createElement("span");
    el.className = "emo";
    el.textContent = E[Math.floor(Math.random() * E.length)];
    const side = Math.random() < .5 ? Math.random() * 18 : 82 + Math.random() * 14; // en los bordes, sin tapar el centro
    el.style.cssText = `left:${side}vw;top:${25 + Math.random() * 55}vh;--s:${26 + Math.random() * 26}px;` +
      `--r0:${-40 + Math.random() * 80}deg;--r1:${-15 + Math.random() * 30}deg;--r2:${-30 + Math.random() * 60}deg`;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 1700);
  }, { passive: true });
})();
