<?php
/**
 * Links de los correos:
 *   ?carrito=TOKEN     -> restaura el carrito (y el cupón) y lleva al checkout.
 *                         (?kac_recover=TOKEN se mantiene por compatibilidad con correos ya enviados).
 *   ?kac_unsub=TOKEN   -> da de baja al cliente de los recordatorios.
 */

defined( 'ABSPATH' ) || exit;

class KAC_Recovery {

	public static function init() {
		add_action( 'wp_loaded', array( __CLASS__, 'handle_request' ), 25 );
	}

	public static function recover_url( $row ) {
		return add_query_arg( 'carrito', $row->token, home_url( '/' ) );
	}

	public static function unsubscribe_url( $row ) {
		return add_query_arg( 'kac_unsub', $row->token, home_url( '/' ) );
	}

	public static function handle_request() {
		if ( is_admin() || wp_doing_ajax() ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- links firmados por token aleatorio.
		$token = '';
		if ( isset( $_GET['carrito'] ) ) {
			$token = sanitize_text_field( wp_unslash( $_GET['carrito'] ) );
		} elseif ( isset( $_GET['kac_recover'] ) ) {
			$token = sanitize_text_field( wp_unslash( $_GET['kac_recover'] ) );
		}

		if ( $token && KAC_DB::get_by_token( $token ) ) {
			if ( self::is_link_preview_bot() ) {
				self::render_preview( KAC_DB::get_by_token( $token ) );
			}
			self::recover( $token );
		} elseif ( isset( $_GET['kac_unsub'] ) && ! self::is_link_preview_bot() ) {
			self::unsubscribe( sanitize_text_field( wp_unslash( $_GET['kac_unsub'] ) ) );
		}
		// phpcs:enable
	}

	/**
	 * WhatsApp, Facebook, Gmail, etc. abren el link para armar la vista previa.
	 * Esa visita no debe restaurar el carrito ni contar como clic del cliente.
	 */
	private static function is_link_preview_bot() {
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) ) : '';
		if ( '' === $ua ) {
			return true;
		}
		foreach ( array( 'whatsapp', 'facebookexternalhit', 'facebot', 'telegrambot', 'twitterbot', 'slackbot', 'discordbot', 'linkedinbot', 'skypeuripreview', 'googleimageproxy', 'google-safety', 'googlebot', 'bingbot', 'applebot', 'bot/', 'crawler', 'spider', 'preview' ) as $needle ) {
			if ( false !== strpos( $ua, $needle ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Página mínima con etiquetas Open Graph: es lo que muestra WhatsApp en la tarjeta del link.
	 */
	private static function render_preview( $row ) {
		$items   = json_decode( (string) $row->cart_contents, true );
		$first   = is_array( $items ) && $items ? reset( $items ) : null;
		$product = $first ? wc_get_product( ! empty( $first['variation_id'] ) ? $first['variation_id'] : $first['product_id'] ) : null;
		$image   = $product ? wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_single' ) : '';
		if ( ! $image ) {
			$image = get_site_icon_url( 512 );
		}

		$title       = 'Tu carrito te espera en ' . get_bloginfo( 'name' );
		$description = $first ? KAC_WhatsApp::products_text( $row ) . ' · Retoma tu compra en un clic.' : 'Retoma tu compra en un clic.';

		nocache_headers();
		header( 'Content-Type: text/html; charset=utf-8' );
		echo '<!doctype html><html lang="es"><head><meta charset="utf-8">';
		echo '<title>' . esc_html( $title ) . '</title>';
		echo '<meta property="og:type" content="website">';
		echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">';
		echo '<meta property="og:title" content="' . esc_attr( $title ) . '">';
		echo '<meta property="og:description" content="' . esc_attr( $description ) . '">';
		if ( $image ) {
			echo '<meta property="og:image" content="' . esc_url( $image ) . '">';
		}
		echo '<meta name="robots" content="noindex,nofollow">';
		echo '</head><body></body></html>';
		exit;
	}

	private static function recover( $token ) {
		$row = KAC_DB::get_by_token( $token );

		if ( ! $row ) {
			wp_safe_redirect( wc_get_cart_url() );
			exit;
		}

		if ( in_array( $row->status, array( 'recovered', 'converted' ), true ) ) {
			wc_add_notice( 'Esta compra ya fue completada. ¡Gracias por comprar en Kigos!', 'notice' );
			wp_safe_redirect( wc_get_page_permalink( 'shop' ) );
			exit;
		}

		if ( ! WC()->cart && function_exists( 'wc_load_cart' ) ) {
			wc_load_cart();
		}
		if ( ! WC()->session || ! WC()->cart ) {
			wp_safe_redirect( home_url( '/' ) );
			exit;
		}

		WC()->session->set_customer_session_cookie( true );

		$items    = json_decode( (string) $row->cart_contents, true );
		$restored = 0;

		KAC_Tracker::$paused = true;
		WC()->cart->empty_cart();
		foreach ( (array) $items as $item ) {
			$product_id = isset( $item['product_id'] ) ? (int) $item['product_id'] : 0;
			$product    = wc_get_product( ! empty( $item['variation_id'] ) ? $item['variation_id'] : $product_id );
			if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
				continue;
			}
			$added = WC()->cart->add_to_cart(
				$product_id,
				max( 1, (float) $item['quantity'] ),
				(int) $item['variation_id'],
				isset( $item['variation'] ) ? (array) $item['variation'] : array()
			);
			if ( $added ) {
				$restored++;
			}
		}
		KAC_Tracker::$paused = false;

		WC()->session->set( 'kac_cart_id', (int) $row->id );
		WC()->session->set(
			'kac_customer',
			array(
				'email'      => $row->email,
				'first_name' => $row->first_name,
				'last_name'  => $row->last_name,
				'phone'      => $row->phone,
			)
		);

		// Prellenar datos del checkout.
		if ( WC()->customer ) {
			WC()->customer->set_billing_email( $row->email );
			if ( $row->first_name ) {
				WC()->customer->set_billing_first_name( $row->first_name );
			}
			if ( $row->last_name ) {
				WC()->customer->set_billing_last_name( $row->last_name );
			}
			if ( $row->phone ) {
				WC()->customer->set_billing_phone( $row->phone );
			}
			WC()->customer->save();
		}

		KAC_DB::update( $row->id, array( 'link_clicked' => 1 ), false );

		if ( $row->coupon_code && ! WC()->cart->has_discount( $row->coupon_code ) ) {
			WC()->cart->apply_coupon( $row->coupon_code );
		}

		if ( ! $restored ) {
			wc_add_notice( 'Algunos productos de tu carrito ya no están disponibles. ¡Revisa nuestra tienda!', 'notice' );
			wp_safe_redirect( wc_get_page_permalink( 'shop' ) );
			exit;
		}

		WC()->cart->calculate_totals();
		KAC_Tracker::sync_cart( true );

		wp_safe_redirect( wc_get_checkout_url() );
		exit;
	}

	private static function unsubscribe( $token ) {
		$row = KAC_DB::get_by_token( $token );
		if ( $row && in_array( $row->status, array( 'active', 'abandoned' ), true ) ) {
			KAC_DB::update( $row->id, array( 'status' => 'unsubscribed' ) );
		}
		if ( $row ) {
			self::add_to_blocklist( $row->email );
		}

		wp_die(
			'<h2>Listo, no te enviaremos más recordatorios de carrito.</h2><p><a href="' . esc_url( home_url( '/' ) ) . '">Volver a Kigos</a></p>',
			'Recordatorios desactivados',
			array( 'response' => 200 )
		);
	}

	public static function add_to_blocklist( $email ) {
		$list   = (array) get_option( 'kac_unsubscribed', array() );
		$email  = strtolower( trim( $email ) );
		$list[] = $email;
		update_option( 'kac_unsubscribed', array_values( array_unique( $list ) ), false );
	}

	public static function is_unsubscribed( $email ) {
		return in_array( strtolower( trim( $email ) ), (array) get_option( 'kac_unsubscribed', array() ), true );
	}
}
