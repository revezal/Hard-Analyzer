# FRAN MERA — sitio oficial (prototipo v0.1)

Sitio estático (HTML + CSS + JS, sin dependencias). Dirección visual: póster grunge rojo de **KELOKERA**, logo psicodélico de Fran Mera y el universo dorado de **Childhood Boys** (la discográfica).

## Cómo verlo
```bash
python3 -m http.server 8000
# sitio:  http://localhost:8000/
# popup:  http://localhost:8000/popup.html
```

## Qué hay
| Sección | Qué hace |
|---|---|
| **Loader** | Logo con efecto líquido + barra de carga |
| **Hero** | Mix de fotos con transición glitch (cortes horizontales, flash de cámara, cortes rojos). Título KELOKERA con textura grunge y glitch al ritmo |
| **Consola / karaoke** | Vinilo que gira, waveform, letra palabra por palabra. Cuando suena, la foto cambia cada compás (según BPM) |
| **Discos** | Vinilo 3D: se arrastra (mouse/dedo) o se usan flechas para voltearlo; cada cara es un tema top |
| **Bio** | Iquique pa'l mundo, stats animados |
| **Childhood Boys** | Sección dorada con chispas: fundadores, servicios del estudio, CTA para artistas |
| **Booking** | Contacto + redes |
| **popup.html** | Popup de entrada estilo "sticker en teléfono" (separado del sitio, 1 vez por sesión) |

## Dónde se edita el contenido
Todo en **`js/data.js`**: single, letra con tiempos, fotos, temas top, Childhood Boys y redes.

### Fotos que faltan subir a `assets/fotos/`
| Archivo | Uso |
|---|---|
| `fran-01.jpg` … `fran-05.jpg` | Mix del hero (las 5 fotos de la sesión) |
| `live-01.jpg`, `live-02.jpg` | Fotos del último concierto |
| `kelokera-cover.jpg` | Portada del single (cuadrada) |
| `fran-cutout.png` | **Fran recortado sin fondo** para el popup (PNG transparente) |
| `yepo-01.jpg` | Foto de Dímelo Yepo |
| `assets/logo/childhood-boys-gold.png` | Logo dorado de Childhood Boys (PNG transparente) |

Si un archivo no existe, el sitio muestra un placeholder automático, así que nada se rompe.

## Próximos pasos
1. **Assets reales**: fotos en alta, audio preview de 30s de KELOKERA (`assets/audio/preview.mp3`) y la letra con tiempos.
2. **Links reales**: Spotify, YouTube, Apple Music, IG, TikTok, booking.
3. **Producción con IA** (ver abajo): recorte de Fran, loop de video para el hero y portadas para los discos.
4. **Integrar el popup** en `index.html` una vez aprobado (2 líneas: `css/popup.css` + `js/popup.js`).
5. **Deploy**: Vercel o Netlify conectado a este repo, y apuntar el dominio franmera.com.
6. **SEO**: metadatos, Open Graph con la portada, schema `MusicGroup`, Google Search Console.

## Assets con IA (Higgsfield / otras)
| Asset | Herramienta | Prompt base |
|---|---|---|
| Loop de video del hero (5–8s) | Higgsfield (image-to-video) desde `fran-01.jpg` | "slow push-in, hard flash strobe, red stage light sweeping, film grain, handheld concert camera, 24fps" |
| Recorte de Fran sin fondo | Photoshop / remove.bg / Canva | — |
| Portadas de los temas top | Higgsfield / Midjourney | "grunge risograph album cover, bold red and black, distressed print texture, latin urban, minimal" |
| Textura de chispas doradas | Higgsfield | "gold chrome sparkles on pure black, macro, glints, seamless loop" |
| Visualizer de KELOKERA | Higgsfield (motion) | "red ink bleeding on black paper, rhythmic pulses, 94 bpm" |

## Herramienta: comprimir videos
`tools/comprimir-video.sh` comprime videos (ej. 100 MB) a ~30 MB para mandarlos por chat.
```bash
./tools/comprimir-video.sh video.mp4          # crea video_30MB.mp4
./tools/comprimir-video.sh -s 25 *.mov        # varios videos, objetivo 25 MB
```
Requiere `ffmpeg` (`brew install ffmpeg` en Mac).
