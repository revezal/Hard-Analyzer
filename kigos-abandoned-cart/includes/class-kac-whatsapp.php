<?php
/**
 * Links de WhatsApp con mensaje prellenado (wa.me) para que el equipo
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

	public static function message( $row ) {
		$first = $row->first_name ? $row->first_name : '';
		return strtr(
			(string) KAC_Settings::get( 'wa_message' ),
			array(
				'{first_name}'  => $first,
				'{products}'    => self::products_text( $row ),
				'{cart_total}'  => wp_strip_all_tags( wc_price( $row->cart_total, array( 'currency' => $row->currency ) ) ),
				'{recover_url}' => KAC_Recovery::recover_url( $row ),
				'{coupon_code}' => $row->coupon_code,
				'{site_name}'   => get_bloginfo( 'name' ),
			)
		);
	}

	/**
	 * @return string URL wa.me o '' si el carrito no tiene teléfono.
	 */
	public static function link( $row ) {
		$phone = self::normalize_phone( $row->phone );
		if ( strlen( $phone ) < 8 ) {
			return '';
		}
		return 'https://wa.me/' . $phone . '?text=' . rawurlencode( self::message( $row ) );
	}
}
