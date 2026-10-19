<?php
/**
 * Captura de carritos: guarda el carrito cuando conocemos el email del cliente
 * (usuario logueado o email escrito en el checkout) y lo vincula al pedido.
 */

defined( 'ABSPATH' ) || exit;

class KAC_Tracker {

	/** Pausa el tracking (p. ej. mientras restauramos un carrito). */
	public static $paused = false;

	/** Estados de pedido que consideramos "pagado / venta cerrada". */
	const PAID_STATUSES = array( 'processing', 'completed', 'on-hold' );

	public static function init() {
		if ( ! KAC_Settings::get( 'enabled' ) ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'wp_ajax_kac_capture', array( __CLASS__, 'ajax_capture' ) );
		add_action( 'wp_ajax_nopriv_kac_capture', array( __CLASS__, 'ajax_capture' ) );
		add_action( 'woocommerce_after_checkout_billing_form', array( __CLASS__, 'consent_notice' ) );

		add_action( 'woocommerce_cart_updated', array( __CLASS__, 'sync_cart' ), 20 );

		// Vincular pedido con carrito (checkout clásico y checkout por bloques).
		add_action( 'woocommerce_checkout_order_created', array( __CLASS__, 'link_order' ) );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( __CLASS__, 'link_order' ) );

		// Marcar como recuperado / convertido cuando el pedido se paga.
		foreach ( self::PAID_STATUSES as $status ) {
			add_action( 'woocommerce_order_status_' . $status, array( __CLASS__, 'mark_paid' ) );
		}
		add_action( 'woocommerce_payment_complete', array( __CLASS__, 'mark_paid' ) );

		// El cupón de recuperación exige el mínimo de productos también al pagar.
		add_filter( 'woocommerce_coupon_is_valid', array( __CLASS__, 'validate_coupon_items' ), 10, 2 );
	}

	/**
	 * @param bool      $valid
	 * @param WC_Coupon $coupon
	 */
	public static function validate_coupon_items( $valid, $coupon ) {
		$min = (int) $coupon->get_meta( '_kac_min_items' );
		if ( ! $valid || $min <= 1 || ! WC()->cart ) {
			return $valid;
		}
		if ( KAC_Settings::count_items( self::serialize_cart() ) < $min ) {
			throw new Exception( sprintf( 'Este cupón es válido para compras de al menos %d productos.', $min ) );
		}
		return $valid;
	}

	public static function enqueue() {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
			return;
		}
		wp_enqueue_script( 'kac-capture', KAC_URL . 'assets/js/kac-capture.js', array(), KAC_VERSION, true );
		wp_localize_script(
			'kac-capture',
			'KAC',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'kac_capture' ),
			)
		);
	}

	public static function consent_notice() {
		$text = KAC_Settings::get( 'consent_text' );
		if ( $text ) {
			echo '<p class="kac-consent" style="font-size:12px;opacity:.7;margin-top:6px;">' . esc_html( $text ) . '</p>';
		}
	}

	/**
	 * AJAX: recibe email / nombre / teléfono escritos en el checkout.
	 */
	public static function ajax_capture() {
		check_ajax_referer( 'kac_capture', 'nonce' );

		if ( ! WC()->session ) {
			wp_send_json_error();
		}

		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'reason' => 'invalid_email' ) );
		}

		$customer = array(
			'email'      => $email,
			'first_name' => isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '',
			'last_name'  => isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '',
			'phone'      => isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '',
		);

		WC()->session->set( 'kac_customer', $customer );
		self::sync_cart( true );

		wp_send_json_success();
	}

	/**
	 * Datos del cliente: primero lo capturado en checkout, si no el usuario logueado.
	 */
	public static function customer_data() {
		$data = WC()->session ? (array) WC()->session->get( 'kac_customer', array() ) : array();

		if ( empty( $data['email'] ) && is_user_logged_in() ) {
			$user = wp_get_current_user();
			$data = array(
				'email'      => get_user_meta( $user->ID, 'billing_email', true ) ?: $user->user_email,
				'first_name' => get_user_meta( $user->ID, 'billing_first_name', true ) ?: $user->first_name,
				'last_name'  => get_user_meta( $user->ID, 'billing_last_name', true ) ?: $user->last_name,
				'phone'      => get_user_meta( $user->ID, 'billing_phone', true ),
			);
		}

		return wp_parse_args(
			$data,
			array(
				'email'      => '',
				'first_name' => '',
				'last_name'  => '',
				'phone'      => '',
			)
		);
	}

	/**
	 * Snapshot del carrito actual en formato restaurable.
	 */
	public static function serialize_cart() {
		$items = array();
		foreach ( WC()->cart->get_cart() as $item ) {
			$product = $item['data'];
			$items[] = array(
				'product_id'   => (int) $item['product_id'],
				'variation_id' => (int) $item['variation_id'],
				'variation'    => (array) $item['variation'],
				'quantity'     => (float) $item['quantity'],
				'name'         => $product ? $product->get_name() : '',
				'line_total'   => (float) $item['line_total'] + (float) $item['line_tax'],
			);
		}
		return $items;
	}

	/**
	 * Guarda / actualiza el carrito de la sesión actual.
	 *
	 * @param bool $force Ignora el control de escrituras repetidas.
	 */
	public static function sync_cart( $force = false ) {
		if ( self::$paused || ! function_exists( 'WC' ) || ! WC()->cart || ! WC()->session ) {
			return;
		}
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		$session = WC()->session;
		$cart_id = (int) $session->get( 'kac_cart_id' );
		$row     = $cart_id ? KAC_DB::get( $cart_id ) : null;

		if ( $row && ! in_array( $row->status, array( 'active', 'abandoned' ), true ) ) {
			$row = null; // Ya cerrado: un nuevo carrito empieza un nuevo registro.
			$session->set( 'kac_cart_id', null );
		}

		// Carrito vacío: borrar el registro si aún no se convirtió en pedido.
		if ( WC()->cart->is_empty() ) {
			if ( $row && ! (int) $row->order_id ) {
				KAC_DB::delete( $row->id );
			}
			$session->set( 'kac_cart_id', null );
			$session->set( 'kac_hash', null );
			return;
		}

		$customer = self::customer_data();
		if ( ! is_email( $customer['email'] ) ) {
			return; // Sin email no podemos recuperar nada.
		}

		$contents = self::serialize_cart();
		$total    = (float) WC()->cart->get_cart_contents_total() + (float) WC()->cart->get_cart_contents_tax();

		$min = (float) KAC_Settings::get( 'min_cart_total' );
		if ( $min > 0 && $total < $min ) {
			return;
		}

		// Evitar escribir en BD en cada carga de página si nada cambió (máx. cada 5 min).
		$hash = md5( wp_json_encode( array( $contents, $customer ) ) );
		$last = (int) $session->get( 'kac_last_write' );
		if ( ! $force && $row && 'active' === $row->status && $session->get( 'kac_hash' ) === $hash && ( time() - $last ) < 300 ) {
			return;
		}

		// Mismo email en otro dispositivo: reutilizamos su carrito abierto.
		if ( ! $row ) {
			$row = KAC_DB::get_open_by_email( $customer['email'] );
			if ( $row && (int) $row->order_id ) {
				$row = null;
			}
		}

		$data = array(
			'session_key'   => (string) $session->get_customer_id(),
			'user_id'       => get_current_user_id(),
			'email'         => $customer['email'],
			'first_name'    => $customer['first_name'],
			'last_name'     => $customer['last_name'],
			'phone'         => $customer['phone'],
			'cart_contents' => wp_json_encode( $contents ),
			'cart_total'    => $total,
			'currency'      => get_woocommerce_currency(),
			'status'        => 'active', // Si estaba abandonado y volvió, se reactiva (la secuencia se pausa).
		);

		if ( $row ) {
			KAC_DB::update( $row->id, $data );
			$cart_id = (int) $row->id;
		} else {
			$cart_id = KAC_DB::insert( $data );
		}

		$session->set( 'kac_cart_id', $cart_id );
		$session->set( 'kac_hash', $hash );
		$session->set( 'kac_last_write', time() );
	}

	/**
	 * Al crear el pedido, guardamos la relación carrito <-> pedido.
	 *
	 * @param WC_Order $order
	 */
	public static function link_order( $order ) {
		if ( ! $order instanceof WC_Order || ! WC()->session ) {
			return;
		}

		self::sync_cart( true );
		$cart_id = (int) WC()->session->get( 'kac_cart_id' );
		if ( ! $cart_id ) {
			return;
		}

		$order->update_meta_data( '_kac_cart_id', $cart_id );
		$order->save_meta_data();
		KAC_DB::update( $cart_id, array( 'order_id' => $order->get_id() ), false );
	}

	/**
	 * Pedido pagado: el carrito pasa a "recovered" (si había sido abandonado)
	 * o a "converted" (venta normal sin abandono).
	 *
	 * @param int $order_id
	 */
	public static function mark_paid( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$cart_id = (int) $order->get_meta( '_kac_cart_id' );
		$row     = $cart_id ? KAC_DB::get( $cart_id ) : null;

		if ( ! $row ) {
			$row = KAC_DB::get_open_by_email( $order->get_billing_email() );
		}
		if ( ! $row || ! in_array( $row->status, array( 'active', 'abandoned' ), true ) ) {
			return;
		}

		$was_abandoned = ! empty( $row->abandoned_at ) || (int) $row->emails_sent > 0 || (int) $row->link_clicked;

		KAC_DB::update(
			$row->id,
			array(
				'status'       => $was_abandoned ? 'recovered' : 'converted',
				'order_id'     => $order->get_id(),
				'order_total'  => (float) $order->get_total(),
				'recovered_at' => KAC_DB::now(),
			)
		);

		if ( $was_abandoned ) {
			$order->add_order_note(
				sprintf( 'Pedido recuperado desde carrito abandonado #%d (correos enviados: %d).', $row->id, (int) $row->emails_sent )
			);
		}

		if ( WC()->session && (int) WC()->session->get( 'kac_cart_id' ) === (int) $row->id ) {
			WC()->session->set( 'kac_cart_id', null );
			WC()->session->set( 'kac_hash', null );
		}
	}
}
