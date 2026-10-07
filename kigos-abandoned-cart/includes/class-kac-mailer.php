<?php
/**
 * Construye y envía los correos de recuperación usando la plantilla de WooCommerce.
 */

defined( 'ABSPATH' ) || exit;

class KAC_Mailer {

	/**
	 * Envía el correo número $step para el carrito $row.
	 *
	 * @param object $row     Fila de la tabla kac_carts.
	 * @param int    $step    1..3.
	 * @param string $to      Destinatario alternativo (para pruebas).
	 * @param bool   $preview Si es prueba: no crea cupón real.
	 * @return bool
	 */
	public static function send( $row, $step, $to = '', $preview = false ) {
		$steps = KAC_Settings::email_steps();
		if ( ! isset( $steps[ $step ] ) ) {
			return false;
		}
		$conf = $steps[ $step ];

		$coupon = '';
		if ( $conf['coupon'] ) {
			$coupon = $preview ? 'VUELVE-EJEMPLO' : self::ensure_coupon( $row );
		}

		$vars = self::vars( $row, $coupon );

		$subject = strtr( $conf['subject'], $vars );
		$heading = strtr( $conf['heading'], $vars );
		$body    = strtr( $conf['body'], $vars );

		$mailer  = WC()->mailer();
		$content = self::render( $row, $body, $coupon );
		$message = $mailer->wrap_message( $heading, $content );

		$from_name  = KAC_Settings::get( 'from_name' );
		$from_email = KAC_Settings::get( 'from_email' );
		$name_cb    = function ( $v ) use ( $from_name ) {
			return $from_name ? $from_name : $v;
		};
		$email_cb   = function ( $v ) use ( $from_email ) {
			return is_email( $from_email ) ? $from_email : $v;
		};
		add_filter( 'woocommerce_email_from_name', $name_cb, 99 );
		add_filter( 'woocommerce_email_from_address', $email_cb, 99 );

		$sent = self::deliver( $to ? $to : $row->email, $subject, $message );

		remove_filter( 'woocommerce_email_from_name', $name_cb, 99 );
		remove_filter( 'woocommerce_email_from_address', $email_cb, 99 );

		return (bool) $sent;
	}

	private static function vars( $row, $coupon ) {
		$type   = KAC_Settings::get( 'coupon_type' );
		$amount = (float) KAC_Settings::get( 'coupon_amount' );

		return array(
			'{first_name}'    => $row->first_name ? $row->first_name : ucfirst( strtok( $row->email, '@.+_' ) ),
			'{cart_total}'    => wp_strip_all_tags( wc_price( $row->cart_total, array( 'currency' => $row->currency ) ) ),
			'{coupon_code}'   => $coupon,
			'{coupon_amount}' => 'percent' === $type ? $amount . '%' : wp_strip_all_tags( wc_price( $amount ) ),
			'{coupon_days}'   => (int) KAC_Settings::get( 'coupon_days' ),
			'{site_name}'     => get_bloginfo( 'name' ),
		);
	}

	private static function render( $row, $body, $coupon ) {
		$items = json_decode( (string) $row->cart_contents, true );

		ob_start();
		echo wp_kses_post( wpautop( $body ) );
		?>
		<table cellspacing="0" cellpadding="8" border="0" style="width:100%;border-collapse:collapse;margin:20px 0;">
			<?php foreach ( (array) $items as $item ) : ?>
				<?php
				$product = wc_get_product( ! empty( $item['variation_id'] ) ? $item['variation_id'] : $item['product_id'] );
				$image   = $product ? wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ) : '';
				$name    = $product ? $product->get_name() : $item['name'];
				?>
				<tr style="border-bottom:1px solid #eee;">
					<td style="width:70px;">
						<?php if ( $image ) : ?>
							<img src="<?php echo esc_url( $image ); ?>" width="64" height="64" alt="" style="border-radius:6px;display:block;" />
						<?php endif; ?>
					</td>
					<td><?php echo esc_html( $name ); ?> &times; <?php echo esc_html( wc_stock_amount( $item['quantity'] ) ); ?></td>
					<td style="text-align:right;white-space:nowrap;"><?php echo wp_kses_post( wc_price( $item['line_total'], array( 'currency' => $row->currency ) ) ); ?></td>
				</tr>
			<?php endforeach; ?>
			<tr>
				<td></td>
				<td style="text-align:right;"><strong>Total</strong></td>
				<td style="text-align:right;white-space:nowrap;"><strong><?php echo wp_kses_post( wc_price( $row->cart_total, array( 'currency' => $row->currency ) ) ); ?></strong></td>
			</tr>
		</table>

		<?php if ( $coupon ) : ?>
			<p style="text-align:center;margin:20px 0;">
				<span style="display:inline-block;border:2px dashed #111;padding:10px 20px;font-size:20px;font-weight:bold;letter-spacing:2px;"><?php echo esc_html( $coupon ); ?></span><br />
				<small>Se aplica automáticamente al usar el botón.</small>
			</p>
		<?php endif; ?>

		<p style="text-align:center;margin:30px 0;">
			<a href="<?php echo esc_url( KAC_Recovery::recover_url( $row ) ); ?>" style="background:#111;color:#fff;padding:14px 28px;border-radius:30px;text-decoration:none;font-weight:bold;display:inline-block;">Terminar mi compra</a>
		</p>

		<p style="text-align:center;font-size:11px;color:#999;">
			¿No quieres recibir estos recordatorios? <a href="<?php echo esc_url( KAC_Recovery::unsubscribe_url( $row ) ); ?>" style="color:#999;">Darme de baja</a>
		</p>
		<?php
		return ob_get_clean();
	}

	/**
	 * Envía con el mailer de WooCommerce y guarda el último error de wp_mail
	 * para mostrarlo en el panel.
	 */
	private static function deliver( $to, $subject, $message ) {
		$error   = null;
		$catcher = function ( $wp_error ) use ( &$error ) {
			$error = $wp_error instanceof WP_Error ? $wp_error->get_error_message() : 'Error desconocido';
		};
		add_action( 'wp_mail_failed', $catcher );
		$sent = (bool) WC()->mailer()->send( $to, $subject, $message, array( 'Content-Type: text/html; charset=UTF-8' ) );
		remove_action( 'wp_mail_failed', $catcher );

		if ( ! $sent ) {
			update_option(
				'kac_last_mail_error',
				array(
					'time'    => time(),
					'to'      => $to,
					'message' => $error ? $error : 'wp_mail devolvió falso (revisa la configuración de correo/SMTP del sitio).',
				),
				false
			);
		}
		return $sent;
	}

	/**
	 * Aviso interno al equipo: un carrito quedó abandonado, con botón para
	 * abrir WhatsApp con el mensaje ya escrito.
	 *
	 * @param object $row
	 * @param string $to Destinatario alternativo (para pruebas).
	 * @return bool
	 */
	public static function notify_team( $row, $to = '' ) {
		if ( ! $to ) {
			$to = KAC_Settings::get( 'notify_email' );
			$to = $to ? $to : get_option( 'admin_email' ); // Lista separada por comas, ya validada al guardar.
		}

		$wa_link = KAC_WhatsApp::link( $row );
		$total   = wp_strip_all_tags( wc_price( $row->cart_total, array( 'currency' => $row->currency ) ) );
		$name    = trim( $row->first_name . ' ' . $row->last_name );
		$subject = sprintf( '🛒 Carrito abandonado: %s – %s', $name ? $name : $row->email, $total );

		ob_start();
		?>
		<p><strong><?php echo esc_html( $name ? $name : 'Cliente sin nombre' ); ?></strong> dejó su carrito sin terminar la compra.</p>
		<table cellspacing="0" cellpadding="6" border="0" style="width:100%;margin-bottom:12px;">
			<tr><td style="width:110px;color:#777;">Email</td><td><?php echo esc_html( $row->email ); ?></td></tr>
			<tr><td style="color:#777;">Teléfono</td><td><?php echo esc_html( $row->phone ? $row->phone : '—' ); ?></td></tr>
			<tr><td style="color:#777;">Productos</td><td><?php echo esc_html( KAC_WhatsApp::products_text( $row ) ); ?></td></tr>
			<tr><td style="color:#777;">Total</td><td><strong><?php echo esc_html( $total ); ?></strong></td></tr>
		</table>

		<?php if ( $wa_link ) : ?>
			<p style="text-align:center;margin:24px 0;">
				<a href="<?php echo esc_url( $wa_link ); ?>" style="background:#25D366;color:#fff;padding:14px 28px;border-radius:30px;text-decoration:none;font-weight:bold;display:inline-block;">Escribirle por WhatsApp</a>
			</p>
			<p style="font-size:12px;color:#777;">Mensaje que se abrirá (lo puedes editar antes de enviar):</p>
			<blockquote style="border-left:3px solid #25D366;margin:0;padding:8px 12px;color:#444;"><?php echo esc_html( KAC_WhatsApp::message( $row ) ); ?></blockquote>
		<?php else : ?>
			<p>El cliente no dejó teléfono. Recibirá los correos automáticos de recuperación.</p>
		<?php endif; ?>

		<p style="font-size:12px;color:#777;margin-top:24px;">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . KAC_Admin::SLUG ) ); ?>">Ver todos los carritos abandonados</a>
		</p>
		<?php
		$content = ob_get_clean();

		return self::deliver( $to, $subject, WC()->mailer()->wrap_message( 'Carrito abandonado', $content ) );
	}

	/**
	 * Crea (una sola vez por carrito) un cupón de uso único restringido al email del cliente.
	 */
	public static function ensure_coupon( $row ) {
		if ( $row->coupon_code ) {
			return $row->coupon_code;
		}

		$code = 'VUELVE-' . strtoupper( wp_generate_password( 6, false, false ) );

		$coupon = new WC_Coupon();
		$coupon->set_code( $code );
		$coupon->set_discount_type( 'fixed_cart' === KAC_Settings::get( 'coupon_type' ) ? 'fixed_cart' : 'percent' );
		$coupon->set_amount( (float) KAC_Settings::get( 'coupon_amount' ) );
		$coupon->set_individual_use( true );
		$coupon->set_usage_limit( 1 );
		$coupon->set_usage_limit_per_user( 1 );
		$coupon->set_email_restrictions( array( $row->email ) );
		$coupon->set_date_expires( time() + DAY_IN_SECONDS * max( 1, (int) KAC_Settings::get( 'coupon_days' ) ) );
		$min = (float) KAC_Settings::get( 'coupon_min_total' );
		if ( $min > 0 ) {
			$coupon->set_minimum_amount( $min );
		}
		$coupon->set_description( sprintf( 'Generado automáticamente - carrito abandonado #%d', $row->id ) );
		$coupon->save();

		KAC_DB::update( $row->id, array( 'coupon_code' => $code ), false );
		$row->coupon_code = $code;

		return $code;
	}
}
