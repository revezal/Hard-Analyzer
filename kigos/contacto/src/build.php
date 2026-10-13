<?php
// Genera contactanos.html (versión HTML independiente) a partir del snippet PHP.
// Uso: php kigos/contacto/src/build.php kigos/contacto/kigos-contacto-wpcode.php > kigos/contacto/contactanos.html
define('ABSPATH', '/');
$GLOBALS['sc'] = [];
function add_action(){} function wp_register_style(){} function wp_enqueue_style(){}
function add_shortcode($t,$cb){ $GLOBALS['sc'][$t]=$cb; }
function shortcode_atts($d,$a){ return array_merge($d, array_intersect_key((array)$a,$d)); }
function esc_url($u){ return htmlspecialchars($u, ENT_QUOTES); }
function esc_attr($u){ return htmlspecialchars($u, ENT_QUOTES); }
function esc_html($u){ return htmlspecialchars($u, ENT_QUOTES); }
function wp_oembed_get($u){ return false; }
function do_shortcode($s){ return file_get_contents(__DIR__.'/form.html'); }
require $argv[1];
$body = $GLOBALS['sc']['kigos_contacto']([]);
$shell = file_get_contents(__DIR__.'/shell.html');
echo str_replace('<!--CONTENIDO-->', $body, $shell);
