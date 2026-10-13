<?php
/**
 * KIGOS — Página "Contáctanos" (shortcode [kigos_contacto])
 * ------------------------------------------------------------------
 * Cómo instalar (WPCode):
 *   WPCode → Add Snippet → "Add Your Custom Code" → tipo: PHP Snippet
 *   → pegar TODO este archivo (sin la etiqueta <?php de la línea 1 si WPCode la agrega sola)
 *   → Insertion: "Auto Insert" / Location: "Run Everywhere" → Activar.
 *
 * Cómo usar en la página Contáctanos:
 *   Editar la página → borrar el contenido actual → agregar un bloque/widget "Shortcode" con:
 *
 *   [kigos_contacto form_id="123" whatsapp="56912345678" video="https://youtu.be/XXXX"]
 *
 *   form_id   = ID del formulario Contact Form 7 que ya usan (Contacto → Formularios de contacto)
 *   whatsapp  = número con código país, sin "+" ni espacios (ej: 56912345678)
 *   video     = link de YouTube/Vimeo, o URL de un .mp4 subido a Medios
 *   poster    = (opcional) imagen de portada del video si es .mp4
 *   formato   = "vertical" (9:16, tipo reel) u "horizontal" (16:9). Por defecto vertical.
 *   direccion, horario, maps = textos/link para "Cómo llegar"
 *
 * Diseño según Brand Guidelines Kigos 2023:
 *   Grafito #262626 · Amarillo #f8df67 · Títulos Bebas Neue · Párrafos tipografía del tema (Futura)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', function () {
	wp_register_style( 'kigos-bebas', 'https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap', array(), null );
} );

add_shortcode( 'kigos_contacto', function ( $atts ) {
	$a = shortcode_atts( array(
		'form_id'   => '',
		'whatsapp'  => '56900000000',
		'mensaje'   => 'Hola Kigos 👋 Quiero agendar una visita a la tienda.',
		'video'     => '',
		'poster'    => '',
		'formato'   => 'vertical',
		'direccion' => 'Núcleo Los Trapenses · Cerro Pan de Azúcar 10445, Lo Barnechea',
		'horario'   => 'Lunes a viernes · previa reserva',
		'maps'      => 'https://www.google.com/maps/search/?api=1&query=Cerro+Pan+de+Az%C3%BAcar+10445+Lo+Barnechea',
		'email'     => 'contacto@kigos.cl',
	), $atts, 'kigos_contacto' );

	wp_enqueue_style( 'kigos-bebas' );

	$wa_url  = 'https://wa.me/' . preg_replace( '/\D/', '', $a['whatsapp'] ) . '?text=' . rawurlencode( $a['mensaje'] );
	$ratio   = ( 'horizontal' === $a['formato'] ) ? 'is-horizontal' : 'is-vertical';

	// --- Video: YouTube / Vimeo (embed) o archivo .mp4 (etiqueta video) ---
	$video_html = '';
	if ( $a['video'] ) {
		if ( preg_match( '/\.(mp4|webm|mov)(\?.*)?$/i', $a['video'] ) ) {
			$video_html = sprintf(
				'<video controls playsinline preload="metadata"%s><source src="%s" type="video/mp4">Tu navegador no soporta video.</video>',
				$a['poster'] ? ' poster="' . esc_url( $a['poster'] ) . '"' : '',
				esc_url( $a['video'] )
			);
		} else {
			$embed = wp_oembed_get( $a['video'] );
			$video_html = $embed ? $embed : '';
		}
	}
	if ( ! $video_html ) {
		$video_html = '<div class="kc-video__placeholder">Video "Cómo llegar"<br><small>Agrega el link en el atributo video=""</small></div>';
	}

	// --- Formulario: el mismo Contact Form 7 actual ---
	$form_html = $a['form_id']
		? do_shortcode( '[contact-form-7 id="' . esc_attr( $a['form_id'] ) . '"]' )
		: '<p class="kc-note">Falta el atributo form_id con el ID del formulario Contact Form 7.</p>';

	$wa_icon = '<svg viewBox="0 0 32 32" aria-hidden="true" width="22" height="22"><path fill="currentColor" d="M16 3C8.8 3 3 8.7 3 15.8c0 2.5.7 4.9 2 7L3 29l6.4-2c2 1.1 4.3 1.7 6.6 1.7 7.2 0 13-5.7 13-12.8S23.2 3 16 3zm0 23.4c-2.1 0-4.1-.6-5.9-1.7l-.4-.3-3.8 1.2 1.2-3.7-.3-.4a10.5 10.5 0 0 1-1.7-5.7C5.1 10 10 5.3 16 5.3S26.9 10 26.9 15.8 22 26.4 16 26.4zm6-7.8c-.3-.2-1.9-1-2.2-1s-.5-.2-.7.2-.8 1-1 1.2-.4.2-.7.1a8.8 8.8 0 0 1-4.4-3.8c-.3-.6.3-.5.9-1.7.1-.2 0-.4 0-.5l-1-2.4c-.3-.6-.5-.5-.7-.5h-.6a1.2 1.2 0 0 0-.9.4c-.3.3-1.1 1.1-1.1 2.7s1.2 3.2 1.3 3.4c.2.2 2.3 3.5 5.6 4.9 2.1.9 2.9.9 4 .8.6-.1 1.9-.8 2.2-1.5.3-.8.3-1.4.2-1.5-.1-.2-.3-.3-.6-.4z"/></svg>';

	ob_start();
	?>
<style>
/* ===== Página completa: ocultar sidebar (CATEGORIES / RECENT POSTS / INSTAGRAM) ===== */
.main-page-wrapper:has(.kigos-contacto) .sidebar-container { display: none !important; }
.main-page-wrapper:has(.kigos-contacto) .site-content { flex: 0 0 100% !important; max-width: 100% !important; width: 100% !important; }

.kigos-contacto {
	--kc-dark: #262626;
	--kc-yellow: #f8df67;
	--kc-cream: #f6f4ee;
	--kc-line: #e4e1d8;
	--kc-display: 'Bebas Neue', 'Alte Haas Grotesk', Impact, sans-serif;
	color: var(--kc-dark);
	font-family: inherit; /* Futura del tema */
	line-height: 1.6;
}
.kigos-contacto *, .kigos-contacto *::before, .kigos-contacto *::after { box-sizing: border-box; }
.kc-wrap { max-width: 1200px; margin: 0 auto; padding: 0 16px; }
.kc-eyebrow { display: inline-block; font-size: 13px; letter-spacing: .18em; text-transform: uppercase; margin-bottom: 12px; opacity: .75; }
.kc-title { font-family: var(--kc-display); font-weight: 400; font-size: clamp(40px, 7vw, 76px); line-height: .95; letter-spacing: .01em; margin: 0 0 16px; color: inherit; text-transform: uppercase; }
.kc-title em { font-style: normal; color: var(--kc-yellow); }
.kc-lead { font-size: 17px; max-width: 520px; margin: 0 0 28px; }

/* ===== 1. Bloque WhatsApp ===== */
.kc-agenda {
	background: var(--kc-dark); color: #fff;
	width: 100vw; margin-left: calc(50% - 50vw); /* banda a todo el ancho */
	padding: 64px 0;
}
.kc-agenda .kc-wrap { display: grid; gap: 32px; align-items: center; }
.kc-btn-wa {
	display: inline-flex; align-items: center; gap: 12px;
	background: var(--kc-yellow); color: var(--kc-dark) !important;
	font-family: var(--kc-display); font-size: 26px; letter-spacing: .04em; text-transform: uppercase;
	padding: 18px 32px; border-radius: 999px; text-decoration: none !important;
	box-shadow: 0 8px 24px rgba(248, 223, 103, .25);
	transition: transform .2s ease, box-shadow .2s ease;
}
.kc-btn-wa:hover { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(248, 223, 103, .4); }
.kc-btn-wa:focus-visible { outline: 3px solid #fff; outline-offset: 3px; }
.kc-agenda__info { list-style: none; margin: 0; padding: 0; display: grid; gap: 16px; }
.kc-agenda__info li { border-left: 3px solid var(--kc-yellow); padding: 4px 0 4px 16px; }
.kc-agenda__info strong { display: block; font-family: var(--kc-display); font-weight: 400; font-size: 22px; letter-spacing: .04em; color: var(--kc-yellow); }
.kc-agenda__info a { color: #fff; }

/* ===== 2. Cómo llegar ===== */
.kc-llegar { padding: 72px 0; }
.kc-llegar .kc-wrap { display: grid; gap: 40px; align-items: center; }
.kc-video { position: relative; width: 100%; border-radius: 16px; overflow: hidden; background: var(--kc-dark); box-shadow: 0 20px 50px rgba(0,0,0,.18); }
.kc-video.is-vertical { aspect-ratio: 9 / 16; max-width: 400px; margin: 0 auto; }
.kc-video.is-horizontal { aspect-ratio: 16 / 9; }
.kc-video iframe, .kc-video video { position: absolute; inset: 0; width: 100% !important; height: 100% !important; border: 0; object-fit: cover; }
.kc-video__placeholder { position: absolute; inset: 0; display: grid; place-content: center; text-align: center; color: var(--kc-yellow); font-family: var(--kc-display); font-size: 28px; padding: 16px; }
.kc-video__placeholder small { font-family: inherit; font-size: 13px; color: #fff; opacity: .7; }
.kc-steps { counter-reset: kc; list-style: none; padding: 0; margin: 0 0 28px; display: grid; gap: 14px; }
.kc-steps li { counter-increment: kc; display: grid; grid-template-columns: 40px 1fr; gap: 12px; align-items: start; }
.kc-steps li::before { content: counter(kc); width: 40px; height: 40px; border-radius: 50%; background: var(--kc-yellow); color: var(--kc-dark); font-family: var(--kc-display); font-size: 22px; display: grid; place-items: center; }
.kc-btn-line { display: inline-flex; align-items: center; gap: 8px; border: 2px solid var(--kc-dark); color: var(--kc-dark) !important; padding: 12px 24px; border-radius: 999px; text-decoration: none !important; font-weight: 600; transition: background .2s, color .2s; }
.kc-btn-line:hover { background: var(--kc-dark); color: var(--kc-yellow) !important; }

/* ===== 3. Formulario (Contact Form 7) ===== */
.kc-form {
	background: var(--kc-cream);
	width: 100vw; margin-left: calc(50% - 50vw);
	padding: 72px 0 88px;
}
.kc-form__card { max-width: 820px; margin: 0 auto; background: #fff; border-radius: 16px; padding: 32px 20px; box-shadow: 0 10px 30px rgba(0,0,0,.06); }
.kc-form .kc-title { text-align: center; }
.kc-form .kc-lead { text-align: center; margin-left: auto; margin-right: auto; }
.kc-form .wpcf7 label { display: block; font-size: 14px; font-weight: 600; margin-bottom: 6px; }
.kc-form .wpcf7 input[type="text"],
.kc-form .wpcf7 input[type="email"],
.kc-form .wpcf7 input[type="tel"],
.kc-form .wpcf7 textarea {
	width: 100%; border: 1.5px solid var(--kc-line); border-radius: 10px; padding: 14px 16px; font-size: 16px; background: #fff; transition: border-color .2s, box-shadow .2s;
}
.kc-form .wpcf7 textarea { min-height: 160px; }
.kc-form .wpcf7 input:focus, .kc-form .wpcf7 textarea:focus { border-color: var(--kc-dark); box-shadow: 0 0 0 3px rgba(248, 223, 103, .6); outline: none; }
.kc-form .wpcf7 p { margin: 0 0 18px; }
.kc-form .wpcf7 input[type="submit"] {
	width: 100%; background: var(--kc-dark); color: var(--kc-yellow); border: 0; border-radius: 999px; padding: 16px 32px;
	font-family: var(--kc-display); font-size: 24px; letter-spacing: .06em; cursor: pointer; transition: transform .2s;
}
.kc-form .wpcf7 input[type="submit"]:hover { transform: translateY(-2px); }

/* ===== Botón flotante móvil ===== */
.kc-fab { position: fixed; right: 16px; bottom: 16px; z-index: 999; width: 58px; height: 58px; border-radius: 50%; background: var(--kc-yellow); color: var(--kc-dark) !important; display: grid; place-items: center; box-shadow: 0 6px 20px rgba(0,0,0,.25); }
.kc-fab svg { width: 30px; height: 30px; }

/* ===== Responsive ===== */
@media (min-width: 768px) {
	.kc-form__card { padding: 48px; }
	.kc-form .wpcf7 .kc-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0 20px; }
}
@media (min-width: 1024px) {
	.kc-agenda { padding: 88px 0; }
	.kc-agenda .kc-wrap { grid-template-columns: 1.3fr 1fr; gap: 64px; }
	.kc-llegar { padding: 96px 0; }
	.kc-llegar .kc-wrap { grid-template-columns: 1fr 1fr; gap: 72px; }
	.kc-fab { display: none; }
}
</style>

<div class="kigos-contacto">

	<!-- 1. AGENDA POR WHATSAPP -->
	<section class="kc-agenda" aria-labelledby="kc-agenda-title">
		<div class="kc-wrap">
			<div>
				<span class="kc-eyebrow">Showroom Kigos</span>
				<h2 id="kc-agenda-title" class="kc-title">Ven a conocer los hornos <em>en persona</em></h2>
				<p class="kc-lead">Te mostramos los hornos Ooni funcionando, resolvemos tus dudas y te ayudamos a elegir el ideal para ti. Agenda tu visita por WhatsApp.</p>
				<a class="kc-btn-wa" href="<?php echo esc_url( $wa_url ); ?>" target="_blank" rel="noopener">
					<?php echo $wa_icon; // phpcs:ignore ?> Escríbenos para agendar
				</a>
			</div>
			<ul class="kc-agenda__info">
				<li><strong>Dirección</strong><?php echo esc_html( $a['direccion'] ); ?></li>
				<li><strong>Horario</strong><?php echo esc_html( $a['horario'] ); ?></li>
				<li><strong>Email</strong><a href="mailto:<?php echo esc_attr( $a['email'] ); ?>"><?php echo esc_html( $a['email'] ); ?></a></li>
			</ul>
		</div>
	</section>

	<!-- 2. CÓMO LLEGAR (VIDEO) -->
	<section class="kc-llegar" aria-labelledby="kc-llegar-title">
		<div class="kc-wrap">
			<div class="kc-video <?php echo esc_attr( $ratio ); ?>">
				<?php echo $video_html; // phpcs:ignore ?>
			</div>
			<div>
				<span class="kc-eyebrow">Paso a paso</span>
				<h2 id="kc-llegar-title" class="kc-title">Cómo llegar a nuestra tienda</h2>
				<ol class="kc-steps">
					<li>Agenda tu visita por WhatsApp y te confirmamos el horario.</li>
					<li>Mira el video: te mostramos el acceso y dónde estacionar.</li>
					<li>Llega, prende el horno con nosotros y prueba una pizza 🍕</li>
				</ol>
				<a class="kc-btn-line" href="<?php echo esc_url( $a['maps'] ); ?>" target="_blank" rel="noopener">Abrir en Google Maps →</a>
			</div>
		</div>
	</section>

	<!-- 3. FORMULARIO -->
	<section class="kc-form" aria-labelledby="kc-form-title">
		<div class="kc-wrap">
			<h2 id="kc-form-title" class="kc-title">¿Necesitas otra cosa?</h2>
			<p class="kc-lead">Servicio técnico, despachos, cotizaciones o cualquier duda: déjanos tu mensaje y te respondemos a la brevedad.</p>
			<div class="kc-form__card">
				<?php echo $form_html; // phpcs:ignore ?>
			</div>
		</div>
	</section>

	<!-- Botón flotante WhatsApp (solo móvil/tablet) -->
	<a class="kc-fab" href="<?php echo esc_url( $wa_url ); ?>" target="_blank" rel="noopener" aria-label="Escríbenos por WhatsApp para agendar"><?php echo $wa_icon; // phpcs:ignore ?></a>
</div>
	<?php
	return ob_get_clean();
} );
