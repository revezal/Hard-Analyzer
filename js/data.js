/* =========================================================
   FRAN MERA — CONTENIDO EDITABLE
   Todo lo que cambia (fotos, single, letras, temas) vive aquí.
   Deja "src" vacío y el sitio genera un placeholder automático.
   ========================================================= */
window.FM = {
  /* ---------- NUEVO LANZAMIENTO (Hero + karaoke) ---------- */
  release: {
    title: "KELOKERA",
    artist: "FRAN MERA",
    label: "CHILDHOOD BOYS",
    date: "DISPONIBLE AHORA",
    bpm: 94,
    key: "A MIN",
    cover: "assets/fotos/kelokera-cover.jpg", // si no existe, se usa placeholder
    audio: "",            // ej: "assets/audio/kelokera-preview.mp3" (30s del coro). Si está vacío, el karaoke corre en modo demo.
    links: { spotify: "https://open.spotify.com/artist/78DhM8HSDph7IAi9CZAIfw", youtube: "#", apple: "#" },
    // t = segundo en que empieza la línea. PLACEHOLDER: reemplazar por la letra real con sus tiempos.
    lyrics: [
      { t: 0.0,  text: "Letra de ejemplo para el karaoke" },
      { t: 3.2,  text: "Cada palabra se ilumina al ritmo" },
      { t: 6.4,  text: "Desde Iquique pa' todo el planeta" },
      { t: 9.6,  text: "Childhood Boys en la casa" },
      { t: 12.8, text: "Reemplaza estas líneas con la letra real" },
      { t: 16.0, text: "y ajusta el tiempo de cada una" },
      { t: 19.2, text: "el sitio hace el resto solo" },
      { t: 22.4, text: "Fran Mera — nuevo single" },
      { t: 25.6, text: "ya disponible en todas las plataformas" },
      { t: 28.8, text: "" }
    ]
  },

  /* ---------- FOTOS DEL ÚLTIMO CONCIERTO (mix del hero) ---------- */
  // Sube las fotos con estos nombres a assets/fotos/. Si un archivo no existe, aparece un placeholder.
  // Los ítems con "video" se reproducen en el mix (mudos, en loop) y usan "poster" para la transición glitch.
  livePhotos: [
    { src: "assets/fotos/kelokera-4.jpg", label: "KELOKERA" },
    { src: "assets/fotos/live-01.jpg", label: "LIVE 01" },
    { video: "assets/video/kiki.mp4", poster: "assets/video/kiki.jpg", label: "KIKI" },
    { src: "assets/fotos/kelokera-2.jpg", label: "KELOKERA" },
    { src: "assets/fotos/live-02.jpg", label: "LIVE 02" },
    { video: "assets/video/kelokera.mp4", poster: "assets/video/kelokera.jpg", label: "KELOKERA" },
    { src: "assets/fotos/kelokera-6.jpg", label: "KELOKERA" }
  ],

  /* ---------- DISCOS: TEMAS MÁS ESCUCHADOS ---------- */
  // Fuente: Spotify → Popular (orden y reproducciones al 09-10-2026)
  topTracks: [
    { title: "OTRA COPA", time: "3:23", plays: "734.394", color: "#c8364a", cover: "", spotify: "https://open.spotify.com/artist/78DhM8HSDph7IAi9CZAIfw" },
    { title: "NA CONMIGO NA CONTIGO", time: "4:02", plays: "1.499.347", color: "#d9c3a5", cover: "", spotify: "https://open.spotify.com/artist/78DhM8HSDph7IAi9CZAIfw" },
    { title: "QUE RICO FUE", time: "2:38", plays: "211.715", color: "#2a5bd7", cover: "", spotify: "https://open.spotify.com/artist/78DhM8HSDph7IAi9CZAIfw" },
    { title: "DIME QUE FUE", time: "3:28", plays: "387.223", color: "#e0b9a8", cover: "", spotify: "https://open.spotify.com/artist/78DhM8HSDph7IAi9CZAIfw" },
    { title: "KELOKERA", time: "2:31", plays: "1.155", color: "#ff1a0e", cover: "", spotify: "https://open.spotify.com/artist/78DhM8HSDph7IAi9CZAIfw" }
  ],

  /* ---------- CHILDHOOD BOYS (discográfica) ---------- */
  label: {
    name: "CHILDHOOD BOYS",
    logo: "assets/logo/childhood-boys-gold.webp", // logo dorado (subir archivo). Si no existe, se usa versión tipográfica.
    tagline: "Sello y estudio nacido de sueños de infancia.",
    founders: [
      { name: "FRAN MERA", role: "Artista · Compositor · Productor", photo: "assets/fotos/kelokera-3.jpg" },
      { name: "DÍMELO YEPO", role: "Compositor · Productor", photo: "assets/fotos/yepo-01.jpg" }
    ],
    services: [
      { n: "01", title: "PRODUCCIÓN", text: "Beats y producción completa para artistas urbanos nacionales e internacionales." },
      { n: "02", title: "COMPOSICIÓN", text: "Topline, letras y desarrollo de canciones con identidad propia." },
      { n: "03", title: "ESTUDIO", text: "Grabación, dirección vocal, mezcla y master." },
      { n: "04", title: "DESARROLLO DE ARTISTA", text: "Sonido, imagen y estrategia de lanzamiento." }
    ],
    contact: "mailto:hola@childhoodboys.com"
  },

  /* ---------- REDES ---------- */
  socials: {
    instagram: "#",
    tiktok: "#",
    spotify: "https://open.spotify.com/artist/78DhM8HSDph7IAi9CZAIfw",
    youtube: "#",
    booking: "mailto:booking@franmera.com"
  }
};
