<?php
/**
 * Tabla propia de carritos y helpers de acceso.
 *
 * Estados:
 *   active       -> el cliente tiene un carrito con email conocido y sigue activo.
 *   abandoned    -> sin actividad por más de "abandon_minutes"; entra a la secuencia de correos.
 *   recovered    -> compró después de que el carrito fue abandonado (ingreso recuperado).
 *   converted    -> compró sin llegar a abandonarlo (venta normal).
 *   unsubscribed -> pidió no recibir más correos.
 */

defined( 'ABSPATH' ) || exit;

class KAC_DB {

	const DB_VERSION = '1.1.0';

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'kac_carts';
	}

	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = self::table();
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			session_key VARCHAR(100) NOT NULL DEFAULT '',
			user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			email VARCHAR(190) NOT NULL DEFAULT '',
			first_name VARCHAR(100) NOT NULL DEFAULT '',
			last_name VARCHAR(100) NOT NULL DEFAULT '',
			phone VARCHAR(50) NOT NULL DEFAULT '',
			cart_contents LONGTEXT NULL,
			cart_total DECIMAL(19,4) NOT NULL DEFAULT 0,
			currency VARCHAR(10) NOT NULL DEFAULT '',
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			emails_sent TINYINT UNSIGNED NOT NULL DEFAULT 0,
			last_email_at DATETIME NULL,
			link_clicked TINYINT(1) NOT NULL DEFAULT 0,
			team_notified TINYINT(1) NOT NULL DEFAULT 0,
			token VARCHAR(64) NOT NULL DEFAULT '',
			coupon_code VARCHAR(100) NOT NULL DEFAULT '',
			order_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			order_total DECIMAL(19,4) NOT NULL DEFAULT 0,
			abandoned_at DATETIME NULL,
			recovered_at DATETIME NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY session_key (session_key),
			KEY email (email),
			KEY status (status),
			KEY token (token),
			KEY updated_at (updated_at)
		) {$charset};";

		dbDelta( $sql );
		update_option( 'kac_db_version', self::DB_VERSION );
	}

	public static function maybe_upgrade() {
		if ( get_option( 'kac_db_version' ) !== self::DB_VERSION ) {
			self::install();
		}
	}

	public static function now() {
		return current_time( 'mysql', true ); // UTC.
	}

	public static function get( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ) );
	}

	public static function get_by_token( $token ) {
		global $wpdb;
		if ( ! is_string( $token ) || ! preg_match( '/^[a-f0-9]{16,64}$/', $token ) ) {
			return null;
		}
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE token = %s', $token ) );
	}

	/**
	 * Último carrito "abierto" (active/abandoned) asociado a un email.
	 */
	public static function get_open_by_email( $email ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . self::table() . " WHERE email = %s AND status IN ('active','abandoned') ORDER BY updated_at DESC LIMIT 1",
				$email
			)
		);
	}

	public static function insert( array $data ) {
		global $wpdb;
		$now                = self::now();
		$data['created_at'] = $now;
		$data['updated_at'] = $now;
		if ( empty( $data['token'] ) ) {
			$data['token'] = bin2hex( random_bytes( 8 ) ); // 16 caracteres: link corto para WhatsApp.
		}
		$wpdb->insert( self::table(), $data );
		return (int) $wpdb->insert_id;
	}

	public static function update( $id, array $data, $touch = true ) {
		global $wpdb;
		if ( $touch ) {
			$data['updated_at'] = self::now();
		}
		return $wpdb->update( self::table(), $data, array( 'id' => (int) $id ) );
	}

	public static function delete( $id ) {
		global $wpdb;
		return $wpdb->delete( self::table(), array( 'id' => (int) $id ) );
	}

	/**
	 * Métricas para el panel.
	 *
	 * @param string $since Fecha UTC (Y-m-d H:i:s) desde la cual contar.
	 */
	public static function stats( $since ) {
		global $wpdb;
		$t = self::table();

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT status, COUNT(*) AS qty, SUM(cart_total) AS cart_sum, SUM(order_total) AS order_sum
				 FROM {$t} WHERE created_at >= %s GROUP BY status",
				$since
			),
			OBJECT_K
		);

		$get = function ( $status, $field ) use ( $rows ) {
			return isset( $rows[ $status ] ) ? (float) $rows[ $status ]->$field : 0;
		};

		$abandoned_open  = $get( 'abandoned', 'qty' ) + $get( 'unsubscribed', 'qty' );
		$recovered       = $get( 'recovered', 'qty' );
		$total_abandoned = $abandoned_open + $recovered;

		return array(
			'active'          => (int) $get( 'active', 'qty' ),
			'abandoned'       => (int) $abandoned_open,
			'abandoned_value' => $get( 'abandoned', 'cart_sum' ) + $get( 'unsubscribed', 'cart_sum' ),
			'recovered'       => (int) $recovered,
			'recovered_value' => $get( 'recovered', 'order_sum' ),
			'converted'       => (int) $get( 'converted', 'qty' ),
			'recovery_rate'   => $total_abandoned > 0 ? round( $recovered / $total_abandoned * 100, 1 ) : 0,
		);
	}
}
