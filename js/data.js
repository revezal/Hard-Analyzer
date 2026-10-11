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
    youtube: { id: "RKv2vyqATI0", chorusStart: 0, chorusLength: 51 },
    // Coro principal. Una línea por compás a 94 BPM (aprox.). Con el audio real se ajusta cada tiempo exacto.
    lyrics: [
      { t:  0.00, text: "Kelokera" },
      { t:  2.55, text: "Que una nena como tú" },
      { t:  5.11, text: "Sume con esa actitud" },
      { t:  7.66, text: "Tan putera" },
      { t: 10.21, text: "Si nos vieran" },
      { t: 12.77, text: "Lo que hicimos a poca luz" },
      { t: 15.32, text: "Cuando estuvimos desnu'" },
      { t: 17.87, text: "En la arena" },
      { t: 20.43, text: "Nos perdemos" },
      { t: 22.98, text: "De la fase" },
      { t: 25.53, text: "Que tu ta' fuera" },
      { t: 28.09, text: "De clase" },
      { t: 30.64, text: "Quiero que bien" },
      { t: 33.19, text: "Tu la pases" },
      { t: 35.74, text: "Y que revivamos este calentón" },
      { t: 38.30, text: "Bailando reggaeton" },
      { t: 40.85, text: "Perreando eres salvaje" },
      { t: 43.40, text: "Como Julio Voltio" },
      { t: 45.96, text: "Tremendo voltaje" },
      { t: 48.51, text: "" }
    ]
  },

  /* ---------- FOTOS DEL ÚLTIMO CONCIERTO (mix del hero) ---------- */
  // Sube las fotos con estos nombres a assets/fotos/. Si un archivo no existe, aparece un placeholder.
  // Los ítems con "video" se reproducen en el mix (mudos, en loop) y usan "poster" para la transición glitch.
  // Fondo de la sección KELOKERA: el videoclip oficial (último video enviado)
  livePhotos: [
    { video: "assets/video/kelokera-videoclip.mp4", poster: "assets/video/kelokera-videoclip.jpg", label: "KELOKERA" }
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

  /* ---------- EN VIVO: cascada diagonal de tarjetas que avanza al deslizar ---------- */
  // type: "photo" (img), "text" (líneas grandes), "color" (bloque de color con texto), "empty" (espacio para foto pendiente).
  // Para agregar fotos: reemplazar cualquier { type: "empty" } por { type: "photo", img: "assets/fotos/....jpg" }.
  moments: [
    { type: "text", lines: ["EN", "VIVO", "2026", "IQQ"], tone: "grey" },
    { type: "photo", img: "assets/fotos/live-04.jpg" },
    { type: "text", lines: ["Beéle", "Eladio Carrión", "Feid", "Telonero", "Chile", "Público", "Escenario", "Backstage"], tone: "black", small: true },
    { type: "photo", img: "assets/fotos/fran-cb.jpg" },
    { type: "empty" },
    { type: "color", label: "Kelo", tone: "red" },
    { type: "photo", img: "assets/fotos/live-01.jpg" },
    { type: "color", label: "Childhood", tone: "gold", tilt: true },
    { type: "empty" },
    { type: "photo", img: "assets/fotos/live-03.jpg" },
    { type: "color", label: "@franmera", tone: "blue" },
    { type: "photo", img: "assets/fotos/live-02.jpg" },
    { type: "empty" },
    { type: "photo", img: "assets/fotos/yepo-01.jpg" },
    { type: "empty" }
  ],

  /* ---------- INSTAGRAM: publicaciones que van pasando en el teléfono (contenido de ejemplo) ---------- */
  instagram: [
    { img: "assets/fotos/live-04.jpg", caption: "Gracias por tanto 🖤 abriendo para Beéle", likes: "—" },
    { video: "assets/video/kelokera-videoclip.mp4", poster: "assets/video/kelokera-videoclip.jpg", caption: "KELOKERA ya disponible 🔴 link en la bio", likes: "—" },
    { img: "assets/fotos/fran-cb.jpg", caption: "Backstage", likes: "—" },
    { img: "assets/covers/kelokera.jpg", caption: "Nuevo single. Childhood Boys", likes: "—" },
    { img: "assets/fotos/live-01.jpg", caption: "Esto recién empieza", likes: "—" },
    { img: "assets/fotos/kelokera-2.jpg", caption: "Rodaje del videoclip", likes: "—" }
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
