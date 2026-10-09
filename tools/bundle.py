"""Empaqueta una página HTML en un solo archivo: CSS, JS, imágenes y videos locales
quedan incrustados (data URI), así se puede abrir con doble clic en Safari.

Uso: python3 tools/bundle.py entrada.html salida.html
"""
import base64, mimetypes, os, re, sys

ASSET = re.compile(r"((?:\.\./)*assets/[A-Za-z0-9_\-/]+\.(?:png|jpe?g|webp|mp4|webm|mp3))")
mimetypes.add_type("video/webm", ".webm")


def data_uri(path):
    mime = mimetypes.guess_type(path)[0] or "application/octet-stream"
    with open(path, "rb") as f:
        return f"data:{mime};base64," + base64.b64encode(f.read()).decode()


def bundle(src, dst):
    base = os.path.dirname(os.path.abspath(src))
    html = open(src, encoding="utf-8").read()

    def local(href):
        return not re.match(r"^(https?:)?//", href)

    def css(m):
        href = m.group(1)
        if not local(href):
            return m.group(0)
        return "<style>\n" + open(os.path.join(base, href), encoding="utf-8").read() + "\n</style>"

    def js(m):
        attrs, href = m.group(1) + m.group(3), m.group(2)
        if not local(href):
            return m.group(0)
        code = open(os.path.join(base, href), encoding="utf-8").read().replace("</script", "<\\/script")
        return f"<script{attrs}>\n{code}\n</script>"

    html = re.sub(r'<link rel="stylesheet" href="([^"]+)">', css, html)
    html = re.sub(r'<script([^>]*?) src="([^"]+)"([^>]*)></script>', js, html)

    cache, missing = {}, set()

    def asset(m):
        rel = m.group(1)
        path = os.path.normpath(os.path.join(base, rel))
        if not os.path.exists(path):
            missing.add(rel)
            return rel
        if path not in cache:
            cache[path] = data_uri(path)
        return cache[path]

    html = ASSET.sub(asset, html)
    os.makedirs(os.path.dirname(os.path.abspath(dst)), exist_ok=True)
    with open(dst, "w", encoding="utf-8") as f:
        f.write(html)
    size = os.path.getsize(dst) / 1e6
    print(f"{dst}: {size:.1f} MB, {len(cache)} archivos incrustados" + (f", faltan: {sorted(missing)}" if missing else ""))


if __name__ == "__main__":
    bundle(sys.argv[1], sys.argv[2])
