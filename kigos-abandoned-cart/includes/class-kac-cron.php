<?php
/**
 * Proceso programado (cada 15 minutos):
 *   1. Marca como abandonados los carritos sin actividad.
 *   2. Envía el siguiente correo de la secuencia a los que corresponda.
 *   3. Limpia registros antiguos.
 */

defined( 'ABSPATH' ) || exit;

class KAC_Cron {

	const BATCH = 50;

	public static function init() {
		add_filter( 'cron_schedules', array( __CLASS__, 'schedules' ) );
		add_action( KAC_CRON_HOOK, array( __CLASS__, 'run' ) );

		if ( ! wp_next_scheduled( KAC_CRON_HOOK ) ) {
			self::schedule();
		}
	}

	public static function schedules( $schedules ) {
		$schedules['kac_every_15_minutes'] = array(
			'interval' => 15 * MINUTE_IN_SECONDS,
			'display'  => 'Cada 15 minutos (Kigos Carritos)',
		);
		return $schedules;
	}

	public static function schedule() {
		add_filter( 'cron_schedules', array( __CLASS__, 'schedules' ) );
		if ( ! wp_next_scheduled( KAC_CRON_HOOK ) ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS, 'kac_every_15_minutes', KAC_CRON_HOOK );
		}
	}

	public static function unschedule() {
		wp_clear_scheduled_hook( KAC_CRON_HOOK );
	}

	/** Contadores de la última ejecución (se muestran en el panel). */
	private static $stats = array();

	/**
	 * @param string $trigger 'cron' o 'manual'.
	 * @return array|null Resumen de lo que se hizo, o null si no corrió.
	 */
	public static function run( $trigger = 'cron' ) {
		if ( ! KAC_Settings::get( 'enabled' ) || ! function_exists( 'WC' ) ) {
			return null;
		}

		// Evita ejecuciones simultáneas.
		if ( get_transient( 'kac_cron_lock' ) ) {
			return null;
		}
		set_transient( 'kac_cron_lock', 1, 10 * MINUTE_IN_SECONDS );

		self::$stats = array(
			'abandoned'   => 0,
			'team_sent'   => 0,
			'team_failed' => 0,
			'mail_sent'   => 0,
			'mail_failed' => 0,
			'waiting'     => 0,
		);

		self::$stats['abandoned'] = (int) self::mark_abandoned();
		self::notify_team();
		self::send_due_emails();
		self::cleanup();

		delete_transient( 'kac_cron_lock' );

		$result = array(
			'time'    => time(),
			'trigger' => is_string( $trigger ) ? $trigger : 'cron',
			'stats'   => self::$stats,
		);
		update_option( 'kac_last_run', $result, false );

		return $result;
	}

	private static function mark_abandoned() {
		global $wpdb;
		$minutes = max( 5, (int) KAC_Settings::get( 'abandon_minutes' ) );
		$cutoff  = gmdate( 'Y-m-d H:i:s', time() - $minutes * MINUTE_IN_SECONDS );

		return $wpdb->query(
			$wpdb->prepare(
				'UPDATE ' . KAC_DB::table() . " SET status = 'abandoned', abandoned_at = %s
				 WHERE status = 'active' AND updated_at < %s",
				KAC_DB::now(),
				$cutoff
			)
		);
	}

	/**
	 * Avisa por correo al equipo de cada carrito recién abandonado,
	 * con el botón para escribirle al cliente por WhatsApp.
	 */
	private static function notify_team() {
		global $wpdb;

		if ( ! KAC_Settings::get( 'notify_enabled' ) ) {
			return;
		}

		$sql = 'SELECT * FROM ' . KAC_DB::table() . " WHERE status = 'abandoned' AND team_notified = 0 AND cart_total >= %f";
		if ( KAC_Settings::get( 'notify_phone_only' ) ) {
			$sql .= " AND phone <> ''";
		}
		$rows = $wpdb->get_results( $wpdb->prepare( $sql . ' ORDER BY abandoned_at ASC LIMIT %d', (float) KAC_Settings::get( 'notify_min_total' ), self::BATCH ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		foreach ( $rows as $row ) {
			if ( KAC_Recovery::is_unsubscribed( $row->email ) ) {
				continue;
			}
			if ( KAC_Mailer::notify_team( $row ) ) {
				KAC_DB::update( $row->id, array( 'team_notified' => 1 ), false );
				self::$stats['team_sent']++;
			} else {
				self::$stats['team_failed']++;
			}
		}
	}

	private static function send_due_emails() {
		global $wpdb;

		$steps = KAC_Settings::email_steps();
		if ( ! $steps ) {
			return;
		}
		$last_step = max( array_keys( $steps ) );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . KAC_DB::table() . " WHERE status = 'abandoned' AND emails_sent < %d ORDER BY abandoned_at ASC LIMIT %d",
				$last_step,
				self::BATCH
			)
		);

		$now = time();

		foreach ( $rows as $row ) {
			// ¿Se pagó el pedido por otra vía (p. ej. webhook tardío)?
			if ( $row->order_id ) {
				$order = wc_get_order( $row->order_id );
				if ( $order && $order->has_status( KAC_Tracker::PAID_STATUSES ) ) {
					KAC_Tracker::mark_paid( $order->get_id() );
					continue;
				}
			}

			if ( KAC_Recovery::is_unsubscribed( $row->email ) ) {
				KAC_DB::update( $row->id, array( 'status' => 'unsubscribed' ), false );
				continue;
			}

			// Siguiente paso habilitado después del último enviado.
			$next = null;
			foreach ( $steps as $n => $conf ) {
				if ( $n > (int) $row->emails_sent ) {
					$next = $n;
					break;
				}
			}
			if ( ! $next ) {
				continue;
			}

			$abandoned_at = strtotime( $row->abandoned_at . ' UTC' );
			$due_at       = $abandoned_at + (int) round( $steps[ $next ]['delay'] * HOUR_IN_SECONDS );

			// Nunca dos correos al mismo cliente con menos de 1 hora de diferencia.
			$last_email = $row->last_email_at ? strtotime( $row->last_email_at . ' UTC' ) : 0;

			if ( $now < $due_at || ( $now - $last_email ) < HOUR_IN_SECONDS ) {
				self::$stats['waiting']++;
				continue;
			}

			if ( KAC_Mailer::send( $row, $next ) ) {
				KAC_DB::update(
					$row->id,
					array(
						'emails_sent'   => $next,
						'last_email_at' => KAC_DB::now(),
					),
					false
				);
				self::$stats['mail_sent']++;
			} else {
				self::$stats['mail_failed']++;
			}
		}
	}

	private static function cleanup() {
		global $wpdb;
		$days = max( 7, (int) KAC_Settings::get( 'retention_days' ) );
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM ' . KAC_DB::table() . " WHERE status IN ('active','abandoned','converted','unsubscribed') AND updated_at < %s",
				gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS )
			)
		);
	}
}
