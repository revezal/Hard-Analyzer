<?php
/**
 * Links de WhatsApp con mensaje prellenado (api.whatsapp.com) para que el equipo
 * contacte manualmente al cliente desde su propio WhatsApp / WhatsApp Business.
 */

defined( 'ABSPATH' ) || exit;

class KAC_WhatsApp {

	/**
	 * Normaliza un teléfono chileno a formato internacional sin "+" (ej. 56912345678).
	 */
	public static function normalize_phone( $phone ) {
		$digits = preg_replace( '/\D/', '', (string) $phone );
		$digits = ltrim( $digits, '0' );

		if ( 9 === strlen( $digits ) && '9' === $digits[0] ) {
			return '56' . $digits;          // 912345678.
		}
		if ( 8 === strlen( $digits ) ) {
			return '569' . $digits;         // 12345678 (sin el 9).
		}
		return $digits;                     // Ya viene con código de país.
	}

	public static function products_text( $row ) {
		$names = array();
		foreach ( (array) json_decode( (string) $row->cart_contents, true ) as $item ) {
			$names[] = $item['name'];
		}
		if ( count( $names ) > 1 ) {
			$last = array_pop( $names );
			return implode( ', ', $names ) . ' y ' . $last;
		}
		return $names ? $names[0] : 'productos';
	}

	/**
	 * Nombre del producto de mayor valor del carrito (ej. el horno, no el accesorio).
	 */
	public static function main_product( $row ) {
		$main = null;
		foreach ( (array) json_decode( (string) $row->cart_contents, true ) as $item ) {
			if ( ! $main || (float) $item['line_total'] > (float) $main['line_total'] ) {
				$main = $item;
			}
		}
		return $main ? $main['name'] : 'tus productos';
	}

	public static function message( $row ) {
		$first   = $row->first_name ? $row->first_name : '';
		$message = strtr(
			(string) KAC_Settings::get( 'wa_message' ),
			array(
				'{first_name}'  => $first,
				'{products}'     => self::products_text( $row ),
				'{main_product}' => self::main_product( $row ),
				'{cart_total}'  => wp_strip_all_tags( wc_price( $row->cart_total, array( 'currency' => $row->currency ) ) ),
				'{recover_url}' => KAC_Recovery::recover_url( $row ),
				'{coupon_code}' => $row->coupon_code,
				'{site_name}'   => get_bloginfo( 'name' ),
			)
		);
		// Si el cliente no dejó nombre: "Hola **!" -> "Hola!".
		return str_replace( array( ' **', '**' ), '', $message );
	}

	/**
	 * Usa api.whatsapp.com/send y no wa.me: la redirección de wa.me rompe los
	 * emojis (aparecen como �) en WhatsApp Web / Desktop.
	 *
	 * Al imprimirla en HTML usar esc_attr(), no esc_url(): esc_url() borra los
	 * saltos de línea (%0A) del mensaje.
	 *
	 * @return string URL o '' si el carrito no tiene teléfono.
	 */
	public static function link( $row ) {
		$phone = self::normalize_phone( $row->phone );
		if ( strlen( $phone ) < 8 ) {
			return '';
		}
		return 'https://api.whatsapp.com/send?phone=' . $phone . '&text=' . rawurlencode( self::message( $row ) );
	}
}
