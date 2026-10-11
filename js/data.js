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
    links: { spotify: "https://open.spotify.com/artist/78DhM8HSDph7IAi9CZAIfw", youtube: "https://youtu.be/RKv2vyqATI0", apple: "#" },
    // Videoclip oficial: al tocar KELOKERA suena el coro desde YouTube (mini reproductor) y la letra cae en el afiche.
    // chorusStart = segundo del video donde empieza el coro principal → AJUSTAR con el minuto exacto.
    youtube: { id: "RKv2vyqATI0", chorusStart: 0, chorusLength: 42 },
    // Coro principal. t = segundo dentro del coro en que entra cada línea (aprox.; ajustar escuchando el tema).
    lyrics: [
      { t:   0.0, text: "Kelokera" },
      { t:   1.6, text: "Que una nena como tú" },
      { t:   4.0, text: "Sume con esa actitud" },
      { t:   6.4, text: "Tan putera" },
      { t:   8.0, text: "Si nos vieran" },
      { t:  10.4, text: "Lo que hicimos a poca luz" },
      { t:  12.8, text: "Cuando estuvimos desnu'" },
      { t:  15.2, text: "En la arena" },
      { t:  17.6, text: "Nos perdemos" },
      { t:  19.2, text: "De la fase" },
      { t:  21.6, text: "Que tu ta' fuera" },
      { t:  24.0, text: "De clase" },
      { t:  25.6, text: "Quiero que bien" },
      { t:  28.0, text: "Tu la pases" },
      { t:  30.4, text: "Y que revivamos este calentón" },
      { t:  32.8, text: "Bailando reggaeton" },
      { t:  34.4, text: "Perreando eres salvaje" },
      { t:  36.8, text: "Como Julio Voltio" },
      { t:  39.2, text: "Tremendo voltaje" },
      { t:  40.8, text: "" }
    ]
  },

  /* ---------- FOTOS DEL ÚLTIMO CONCIERTO (mix del hero) ---------- */
  // Sube las fotos con estos nombres a assets/fotos/. Si un archivo no existe, aparece un placeholder.
  // Los ítems con "video" se reproducen en el mix (mudos, en loop) y usan "poster" para la transición glitch.
  livePhotos: [
    { src: "assets/fotos/kelokera-cover.jpg", label: "KELOKERA" },
    { src: "assets/fotos/kelokera-4.jpg", label: "KELOKERA" },
    { src: "assets/fotos/live-01.jpg", label: "LIVE 01" },
    { video: "assets/video/kiki.mp4", poster: "assets/video/kiki.jpg", label: "KIKI" },
    { src: "assets/fotos/kelokera-2.jpg", label: "KELOKERA" },
    { src: "assets/fotos/live-02.jpg", label: "LIVE 02" },
    { video: "assets/video/kelokera-videoclip.mp4", poster: "assets/video/kelokera-videoclip.jpg", label: "KELOKERA" },
    { src: "assets/fotos/kelokera-6.jpg", label: "KELOKERA" }
  ],

  /* ---------- DISCOS: TEMAS MÁS ESCUCHADOS ---------- */
  // Fuente: Spotify → Popular (orden y reproducciones al 09-10-2026)
  topTracks: [
    { title: "OTRA COPA", time: "3:23", plays: "734.394", color: "#c8364a", cover: "", spotify: "https://open.spotify.com/artist/78DhM8HSDph7IAi9CZAIfw" },
    { title: "NA CONMIGO NA CONTIGO", time: "4:02", plays: "1.499.347", color: "#d9c3a5", cover: "", spotify: "https://open.spotify.com/artist/78DhM8HSDph7IAi9CZAIfw" },
    { title: "QUE RICO FUE", time: "2:38", plays: "211.715", color: "#2a5bd7", cover: "", spotify: "https://open.spotify.com/artist/78DhM8HSDph7IAi9CZAIfw" },
    { title: "DIME QUE FUE", time: "3:28", plays: "387.223", color: "#e0b9a8", cover: "", spotify: "https://open.spotify.com/artist/78DhM8HSDph7IAi9CZAIfw" },
    { title: "KELOKERA", time: "2:31", plays: "1.155", color: "#ff1a0e", cover: "assets/fotos/kelokera-cover.jpg", spotify: "https://open.spotify.com/artist/78DhM8HSDph7IAi9CZAIfw" }
  ],

  /* ---------- CANCIONES: portadas que aparecen cuando el videoclip se cierra en círculo ---------- */
  // Click en una portada → año y reproducciones. year: "" hasta tener el dato.
  // Dime Que Fue usa la misma portada que Na Conmigo Na Contigo (así aparece en Spotify).
  songs: [
    { type: "song", title: "OTRA COPA", feat: "con Baby Jey", year: "", plays: "734.394", color: "#c8364a", cover: "assets/covers/otra-copa.jpg" },
    { type: "song", title: "NA CONMIGO NA CONTIGO", year: "", plays: "1.499.347", color: "#d9c3a5", cover: "assets/covers/na-conmigo-na-contigo.jpg", big: true },
    { type: "song", title: "KELOKERA", year: "2026", plays: "Nuevo", color: "#ff1a0e", cover: "assets/covers/kelokera.jpg", video: true, big: true },
    { type: "song", title: "QUE RICO FUE", year: "", plays: "211.715", color: "#2a5bd7", cover: "assets/covers/que-rico-fue.jpg" },
    { type: "song", title: "DIME QUE FUE", year: "", plays: "387.223", color: "#e0b9a8", cover: "assets/covers/na-conmigo-na-contigo.jpg", small: true },
    { type: "song", title: "E.S.A", feat: "Schuster x Fran Mera", year: "", plays: "", color: "#7fd6f0", cover: "assets/covers/esa.jpg", small: true }
  ],

  /* ---------- EN VIVO: fotos que se abren en gigante hacia abajo con la historia de cada momento ---------- */
  // PLACEHOLDERS: completar día, fecha, lugar y el texto de cómo lo vivió Fran.
  moments: [
    { img: "assets/fotos/live-01.jpg", title: "Abriendo para Beéle", day: "[día]", date: "[fecha]", place: "[lugar] · Chile",
      what: "Telonero del show de Beéle frente a un recinto lleno.", how: "[Cómo lo vivió Fran: completar con sus palabras]" },
    { img: "assets/fotos/live-02.jpg", title: "Antes del show", day: "[día]", date: "[fecha]", place: "[lugar] · Chile",
      what: "Prueba de sonido con el recinto todavía vacío.", how: "[Completar]" },
    { img: "assets/fotos/live-04.jpg", title: "La gente", day: "[día]", date: "[fecha]", place: "[lugar] · Chile",
      what: "Show en vivo con su nombre en todas las pantallas.", how: "[Completar]" },
    { img: "assets/fotos/live-03.jpg", title: "@franmera", day: "[día]", date: "[fecha]", place: "[lugar] · Chile",
      what: "En vivo, de frente al público.", how: "[Completar]" }
  ],

  /* ---------- CHILDHOOD BOYS (discográfica) ---------- */
  label: {
    name: "CHILDHOOD BOYS",
    logo: "assets/logo/childhood-boys-gold.webp", // logo dorado (subir archivo). Si no existe, se usa versión tipográfica.
    tagline: "Sello y estudio nacido de sueños de infancia.",
    founders: [
      { name: "FRAN MERA", role: "Artista · Compositor · Productor", photo: "assets/fotos/fran-cb.jpg" },
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
    youtube: "https://youtu.be/RKv2vyqATI0",
    booking: "mailto:booking@franmera.com"
  }
};
