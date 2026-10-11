#!/usr/bin/env bash
# comprimir-video.sh — comprime videos a un tamaño objetivo (por defecto 30 MB)
#
# Uso:
#   ./comprimir-video.sh video.mp4                 # deja video_30MB.mp4 al lado
#   ./comprimir-video.sh -s 25 video.mov           # objetivo 25 MB
#   ./comprimir-video.sh *.mp4                     # varios videos de una vez
#
# Requiere ffmpeg (incluye ffprobe):
#   Mac:      brew install ffmpeg
#   Ubuntu:   sudo apt install ffmpeg
#   Windows:  winget install ffmpeg   (y correr el script desde Git Bash o WSL)

set -euo pipefail

TARGET_MB=30
AUDIO_KBPS=96

usage() {
  sed -n '2,13p' "$0" | sed 's/^# \{0,1\}//'
  exit "${1:-0}"
}

while getopts "s:h" opt; do
  case "$opt" in
    s) TARGET_MB="$OPTARG" ;;
    h) usage 0 ;;
    *) usage 1 ;;
  esac
done
shift $((OPTIND - 1))
[ $# -ge 1 ] || usage 1

for bin in ffmpeg ffprobe; do
  command -v "$bin" >/dev/null || { echo "Falta $bin. Instálalo primero (ver --help)."; exit 1; }
done

file_size() { wc -c < "$1" | tr -d ' '; }
mb() { awk -v b="$1" 'BEGIN { printf "%.1f", b / 1048576 }'; }

compress() {
  local in="$1"
  [ -f "$in" ] || { echo "✗ No existe: $in"; return 1; }

  local base="${in%.*}"
  local out="${base}_${TARGET_MB}MB.mp4"
  local size; size=$(file_size "$in")
  local target_bytes=$((TARGET_MB * 1048576))

  echo "▶ $in  ($(mb "$size") MB)"

  if [ "$size" -le "$target_bytes" ]; then
    echo "  Ya pesa menos de ${TARGET_MB} MB, no hace falta comprimir."
    return 0
  fi

  local duration
  duration=$(ffprobe -v error -show_entries format=duration -of csv=p=0 "$in")
  if [ -z "$duration" ] || [ "$duration" = "N/A" ]; then
    echo "  ✗ No pude leer la duración del video."; return 1
  fi

  # Bitrate total que cabe en el objetivo (con 5% de margen para el contenedor)
  local total_kbps video_kbps
  total_kbps=$(awk -v t="$target_bytes" -v d="$duration" 'BEGIN { printf "%d", t * 8 * 0.95 / d / 1000 }')
  video_kbps=$((total_kbps - AUDIO_KBPS))
  if [ "$video_kbps" -lt 150 ]; then
    echo "  ✗ El video es demasiado largo para caber en ${TARGET_MB} MB con calidad aceptable."
    echo "    Prueba con un objetivo mayor (-s) o recórtalo."
    return 1
  fi

  # Bajar resolución cuando hay poco bitrate: se ve mucho mejor que pixelado a 4K
  local max_h
  if   [ "$video_kbps" -ge 4000 ]; then max_h=1080
  elif [ "$video_kbps" -ge 1500 ]; then max_h=720
  elif [ "$video_kbps" -ge 700 ];  then max_h=540
  else max_h=480; fi
  # Escala el lado corto a max_h (sirve para videos verticales y horizontales), sin agrandar
  local vf="scale='if(gt(iw,ih),-2,min(${max_h},iw))':'if(gt(iw,ih),min(${max_h},ih),-2)'"

  echo "  Duración: $(printf '%.0f' "$duration") s → video ${video_kbps} kbps, audio ${AUDIO_KBPS} kbps, máx ${max_h}p"

  local passlog; passlog=$(mktemp -t ffpass.XXXXXX)
  local null_out=/dev/null
  case "$(uname -s)" in MINGW*|MSYS*|CYGWIN*) null_out=NUL ;; esac

  echo "  Pasada 1/2…"
  ffmpeg -hide_banner -loglevel error -stats -y -i "$in" \
    -vf "$vf" -c:v libx264 -preset medium -b:v "${video_kbps}k" \
    -pass 1 -passlogfile "$passlog" -an -f mp4 "$null_out"

  echo "  Pasada 2/2…"
  ffmpeg -hide_banner -loglevel error -stats -y -i "$in" \
    -vf "$vf" -c:v libx264 -preset medium -b:v "${video_kbps}k" \
    -maxrate "$((video_kbps * 3 / 2))k" -bufsize "$((video_kbps * 2))k" \
    -pass 2 -passlogfile "$passlog" -pix_fmt yuv420p \
    -c:a aac -b:a "${AUDIO_KBPS}k" -ac 2 \
    -movflags +faststart "$out"

  rm -f "${passlog}"*

  local new_size; new_size=$(file_size "$out")
  echo "  ✓ Listo: $out  ($(mb "$new_size") MB)"
  if [ "$new_size" -gt "$target_bytes" ]; then
    echo "  ⚠ Quedó un poco sobre ${TARGET_MB} MB. Vuelve a correrlo con -s $((TARGET_MB - 3))."
  fi
}

status=0
for f in "$@"; do
  compress "$f" || status=1
  echo
done
exit $status
