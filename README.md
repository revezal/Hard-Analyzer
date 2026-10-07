# Kigos Carritos Abandonados (plugin WordPress / WooCommerce)

Sistema en PHP para **capturar carritos abandonados**, enviar una **secuencia automática de correos de recuperación** (con cupón opcional) y **medir cuánta plata se recupera**.

## ¿Qué hace?

1. **Captura el carrito** apenas conocemos el email del cliente:
   - Cliente logueado → se usa el email de su cuenta.
   - Invitado → se captura el email (y nombre/teléfono) mientras lo escribe en el checkout, sin que tenga que apretar "Pagar". Funciona con checkout clásico y checkout por bloques.
2. **Detecta el abandono**: si pasan X minutos (por defecto 60) sin actividad, el carrito queda como *Abandonado*.
3. **Envía hasta 3 correos** con la plantilla de WooCommerce (logo y colores de Kigos):
   | Correo | Cuándo (por defecto) | Contenido |
   |---|---|---|
   | 1 | 1 hora después | Recordatorio con fotos de los productos + botón "Terminar mi compra" |
   | 2 | 24 horas después | Ofrece ayuda / resolver dudas |
   | 3 | 72 horas después | Cupón único (5% por defecto, válido 3 días, solo para ese email) |
4. **Link mágico de recuperación**: el botón del correo rearma el carrito, aplica el cupón, prellena nombre/email/teléfono y lleva directo al checkout.
5. **Detiene la secuencia** si el cliente compra, vuelve a la tienda o se da de baja.
6. **Aviso al equipo + WhatsApp**: cuando un carrito queda abandonado les llega un correo a ustedes con nombre, teléfono, productos y total, y un botón verde **"Escribirle por WhatsApp"** que abre WhatsApp (o WhatsApp Business) con el mensaje ya escrito e incluye el link para retomar la compra. Ustedes lo revisan y lo envían. Se puede limitar a carritos con teléfono y/o sobre cierto monto (ej. solo hornos).
7. **Panel en WooCommerce → Carritos abandonados** con:
   - Carritos abandonados, valor perdido, carritos recuperados, **ingresos recuperados** y **tasa de recuperación**.
   - Listado con cliente, productos, total, estado, correos enviados, cupón, pedido y **link directo a WhatsApp** del cliente.
   - Exportar a CSV (abre bien en Excel, con acentos).
   - Configuración de tiempos, textos, cupón y envío de **correo de prueba**.
8. Cada pedido recuperado queda con una **nota interna** en el pedido ("Pedido recuperado desde carrito abandonado #...").

## Instalación

1. Comprime la carpeta `kigos-abandoned-cart` en un `.zip`.
2. WordPress → **Plugins → Añadir nuevo → Subir plugin** → elegir el zip → **Activar**.
3. Ir a **WooCommerce → Carritos abandonados → Configuración**, revisar textos y cupón.
4. Enviar un **correo de prueba** (necesita al menos un carrito capturado: entra a la tienda en incógnito, agrega un producto y escribe tu email en el checkout).

### Recomendado: cron real

WordPress solo ejecuta sus tareas programadas cuando alguien visita el sitio. Para que los correos salgan a la hora exacta, en el hosting (cPanel → Cron Jobs) agrega cada 5–15 minutos:

```
*/10 * * * * wget -q -O - https://kigos.cl/wp-cron.php?doing_wp_cron >/dev/null 2>&1
```

y en `wp-config.php`: `define( 'DISABLE_WP_CRON', true );`

### Recomendado: SMTP

Para que los correos no lleguen a spam, usa un plugin SMTP (WP Mail SMTP, FluentSMTP) conectado a Google Workspace, Brevo, etc.

## Variables para los textos

Correos: `{first_name}`, `{cart_total}`, `{coupon_code}`, `{coupon_amount}`, `{coupon_days}`, `{site_name}`

WhatsApp: `{first_name}`, `{main_product}`, `{products}`, `{cart_total}`, `{recover_url}`, `{coupon_code}`, `{site_name}`

## Privacidad

- Bajo el formulario del checkout se muestra un aviso (editable) de que el carrito se guarda para recordatorios.
- Todos los correos llevan link de **"Darme de baja"**; esos emails no vuelven a recibir recordatorios.
- Los registros se borran automáticamente después de N días (90 por defecto), salvo los recuperados (para las métricas).

## Estructura

```
kigos-abandoned-cart/
├── kigos-abandoned-cart.php        # Archivo principal del plugin
├── uninstall.php                   # Limpieza al eliminar el plugin
├── assets/js/kac-capture.js        # Captura del email en el checkout
└── includes/
    ├── class-kac-settings.php      # Opciones y valores por defecto
    ├── class-kac-db.php            # Tabla wp_kac_carts + métricas
    ├── class-kac-tracker.php       # Guarda carritos y los vincula a pedidos
    ├── class-kac-recovery.php      # Link de recuperación y baja
    ├── class-kac-mailer.php        # Correos + cupones automáticos + aviso al equipo
    ├── class-kac-whatsapp.php      # Links de WhatsApp con mensaje prellenado
    ├── class-kac-cron.php          # Proceso cada 15 min (abandono, envíos, limpieza)
    └── class-kac-admin.php         # Panel, configuración y exportación CSV
```

Requisitos: WordPress 6+, WooCommerce 7+, PHP 7.4+. Compatible con HPOS.
