<?php
/**
 * Opciones del plugin con valores por defecto.
 */

defined( 'ABSPATH' ) || exit;

class KAC_Settings {

	const OPTION = 'kac_settings';

	public static function defaults() {
		return array(
			'enabled'          => 1,
			// Minutos sin actividad para considerar un carrito como abandonado.
			'abandon_minutes'  => 60,
			// Correo 1: horas después de abandonado.
			'email1_enabled'   => 1,
			'email1_delay'     => 1,
			'email1_subject'   => '{first_name}, dejaste algo en tu carrito 🍕',
			'email1_heading'   => '¿Se te olvidó algo?',
			'email1_body'      => "Hola {first_name},\n\nNotamos que dejaste productos en tu carrito de Kigos. Los guardamos para ti, así puedes terminar tu compra cuando quieras.",
			'email1_coupon'    => 0,
			// Correo 2.
			'email2_enabled'   => 1,
			'email2_delay'     => 24,
			'email2_subject'   => 'Tu carrito te sigue esperando, {first_name}',
			'email2_heading'   => 'Tu horno te está esperando',
			'email2_body'      => "Hola {first_name},\n\n¿Tienes dudas sobre tu compra? Responde este correo o escríbenos por Instagram y te ayudamos a elegir. Tu carrito sigue guardado.",
			'email2_coupon'    => 0,
			// Correo 3 (último, con cupón).
			'email3_enabled'   => 1,
			'email3_delay'     => 72,
			'email3_subject'   => '{first_name}, un regalo para terminar tu compra 🎁',
			'email3_heading'   => 'Último aviso + descuento',
			'email3_body'      => "Hola {first_name},\n\nTe dejamos un cupón exclusivo para que completes tu compra: {coupon_code} ({coupon_amount} de descuento). Válido por {coupon_days} días.",
			'email3_coupon'    => 1,
			// Cupón.
			'coupon_type'      => 'percent', // percent | fixed_cart.
			'coupon_amount'    => 5,
			'coupon_days'      => 3,
			'coupon_min_total' => 0,
			// Aviso interno al equipo para contactar por WhatsApp.
			'notify_enabled'   => 1,
			'notify_email'     => '',
			'notify_min_total' => 0,
			'notify_phone_only'=> 1,
			'wa_message'       => "Hola *{first_name}*! 🍕\nTe saludamos de Kigos Chile, vimos que quedó pendiente *{main_product}* en tu carrito. ¿Te quedó alguna duda con el producto, el despacho o el pago? Te ayudamos por aquí 🙌\n\nTu carrito quedó guardado, puedes retomarlo acá:\n{recover_url}\n\nEquipo Kigos",
			// Otros.
			'min_cart_total'   => 0,
			'from_name'        => '',
			'from_email'       => '',
			'retention_days'   => 90,
			'consent_text'     => 'Guardamos tu carrito para recordarte tu compra. Puedes darte de baja cuando quieras.',
		);
	}

	public static function all() {
		$saved = get_option( self::OPTION, array() );
		$all   = wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
		$defaults = self::defaults();
		foreach ( $all as $key => $value ) {
			if ( ! is_string( $value ) ) {
				continue;
			}
			// Los emojis se guardan como entidades (&#x1f355;) para no romperse en bases utf8.
			if ( false !== strpos( $value, '&#' ) ) {
				$value       = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
				$all[ $key ] = $value;
			}
			// Textos que quedaron con emojis rotos (guardados antes de la v1.3): volver al texto por defecto.
			if ( isset( $defaults[ $key ] ) && self::is_broken( $value ) ) {
				$all[ $key ] = $defaults[ $key ];
			}
		}
		// Mensaje de WhatsApp antiguo (v1.1): reemplazarlo por el nuevo formato.
		if ( false !== strpos( $all['wa_message'], 'Te escribimos de Kigos' ) ) {
			$all['wa_message'] = $defaults['wa_message'];
		}
		return $all;
	}

	/**
	 * Texto con bytes inválidos, el carácter de reemplazo (�) o un emoji convertido en "?".
	 */
	private static function is_broken( $text ) {
		if ( function_exists( 'mb_check_encoding' ) && ! mb_check_encoding( $text, 'UTF-8' ) ) {
			return true;
		}
		return false !== strpos( $text, "\u{FFFD}" ) || (bool) preg_match( '/[a-z0-9,.!] \?(\s|$)/iu', $text );
	}

	public static function get( $key ) {
		$all = self::all();
		return isset( $all[ $key ] ) ? $all[ $key ] : null;
	}

	/**
	 * Pasos de la secuencia de correos activos, ordenados.
	 *
	 * @return array<int,array> índice = número de correo (1..3).
	 */
	public static function email_steps() {
		$s     = self::all();
		$steps = array();
		for ( $i = 1; $i <= 3; $i++ ) {
			if ( empty( $s[ "email{$i}_enabled" ] ) ) {
				continue;
			}
			$steps[ $i ] = array(
				'delay'   => max( 0, (float) $s[ "email{$i}_delay" ] ),
				'subject' => $s[ "email{$i}_subject" ],
				'heading' => $s[ "email{$i}_heading" ],
				'body'    => $s[ "email{$i}_body" ],
				'coupon'  => ! empty( $s[ "email{$i}_coupon" ] ),
			);
		}
		return $steps;
	}
}
