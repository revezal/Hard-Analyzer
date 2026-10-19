<?php
/**
 * Panel en WooCommerce > Carritos abandonados: métricas, listado y configuración.
 */

defined( 'ABSPATH' ) || exit;

class KAC_Admin {

	const SLUG = 'kac-abandoned-carts';

	const STATUS_LABELS = array(
		'active'       => 'Activo',
		'abandoned'    => 'Abandonado',
		'recovered'    => 'Recuperado',
		'converted'    => 'Compra directa',
		'unsubscribed' => 'Dado de baja',
	);

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 60 );
		add_action( 'admin_post_kac_save_settings', array( __CLASS__, 'save_settings' ) );
		add_action( 'admin_post_kac_delete_cart', array( __CLASS__, 'delete_cart' ) );
		add_action( 'admin_post_kac_send_test', array( __CLASS__, 'send_test' ) );
		add_action( 'admin_post_kac_run_now', array( __CLASS__, 'run_now' ) );
		add_action( 'admin_post_kac_export', array( __CLASS__, 'export_csv' ) );
	}

	public static function menu() {
		add_submenu_page(
			'woocommerce',
			'Carritos abandonados',
			'Carritos abandonados',
			'manage_woocommerce',
			self::SLUG,
			array( __CLASS__, 'render' )
		);
	}

	private static function url( $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => self::SLUG ), $args ), admin_url( 'admin.php' ) );
	}

	private static function check( $action ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( 'Sin permisos.' );
		}
		check_admin_referer( $action );
	}

	public static function render() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'carts';
		?>
		<div class="wrap">
			<h1>Carritos abandonados</h1>
			<?php self::notices(); ?>
			<nav class="nav-tab-wrapper">
				<a href="<?php echo esc_url( self::url() ); ?>" class="nav-tab <?php echo 'carts' === $tab ? 'nav-tab-active' : ''; ?>">Resumen</a>
				<a href="<?php echo esc_url( self::url( array( 'tab' => 'settings' ) ) ); ?>" class="nav-tab <?php echo 'settings' === $tab ? 'nav-tab-active' : ''; ?>">Configuración</a>
			</nav>
			<?php
			if ( 'settings' === $tab ) {
				self::render_settings();
			} else {
				self::render_carts();
			}
			?>
		</div>
		<?php
	}

	private static function notices() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$map = array(
			'saved'      => array( 'success', 'Configuración guardada.' ),
			'deleted'    => array( 'success', 'Carrito eliminado.' ),
			'test_ok'    => array( 'success', 'Correo de prueba enviado.' ),
			'test_fail'  => array( 'error', 'No se pudo enviar el correo de prueba (revisa el SMTP del sitio).' ),
			'test_empty' => array( 'warning', 'Aún no hay carritos capturados para armar la vista previa.' ),
			'ran'        => array( 'success', 'Proceso ejecutado. Revisa el resultado en "Estado del sistema".' ),
			'ran_off'    => array( 'error', 'El proceso no corrió: revisa que "Activar sistema" esté marcado en Configuración.' ),
		);
		$msg = isset( $_GET['kac_msg'] ) ? sanitize_key( $_GET['kac_msg'] ) : '';
		if ( isset( $map[ $msg ] ) ) {
			list( $type, $text ) = $map[ $msg ];
			printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( $type ), esc_html( $text ) );
		}
		// phpcs:enable
	}

	private static function render_carts() {
		global $wpdb;

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$days   = isset( $_GET['days'] ) ? max( 1, (int) $_GET['days'] ) : 30;
		$status = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '';
		$paged  = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
		// phpcs:enable

		$since = gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS );
		$stats = KAC_DB::stats( $since );

		self::render_status();

		$per_page = 25;
		$where    = $wpdb->prepare( 'WHERE created_at >= %s', $since );
		if ( $status && isset( self::STATUS_LABELS[ $status ] ) ) {
			$where .= $wpdb->prepare( ' AND status = %s', $status );
		}
		$table = KAC_DB::table();
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} {$where}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} {$where} ORDER BY updated_at DESC LIMIT %d OFFSET %d", $per_page, ( $paged - 1 ) * $per_page ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$cards = array(
			array( 'Carritos abandonados', number_format_i18n( $stats['abandoned'] + $stats['recovered'] ) ),
			array( 'Valor perdido (sin recuperar)', wc_price( $stats['abandoned_value'] ) ),
			array( 'Carritos recuperados', number_format_i18n( $stats['recovered'] ) ),
			array( 'Ingresos recuperados', wc_price( $stats['recovered_value'] ) ),
			array( 'Tasa de recuperación', $stats['recovery_rate'] . '%' ),
			array( 'Carritos activos ahora', number_format_i18n( $stats['active'] ) ),
		);
		?>
		<form method="get" style="margin:16px 0;">
			<input type="hidden" name="page" value="<?php echo esc_attr( self::SLUG ); ?>" />
			<label>Periodo:
				<select name="days">
					<?php foreach ( array( 7, 30, 90, 365 ) as $d ) : ?>
						<option value="<?php echo (int) $d; ?>" <?php selected( $days, $d ); ?>>Últimos <?php echo (int) $d; ?> días</option>
					<?php endforeach; ?>
				</select>
			</label>
			<label>Estado:
				<select name="status">
					<option value="">Todos</option>
					<?php foreach ( self::STATUS_LABELS as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<button class="button">Filtrar</button>
			<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=kac_export&days=' . $days . '&status=' . $status ), 'kac_export' ) ); ?>">Exportar CSV</a>
			<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=kac_run_now' ), 'kac_run_now' ) ); ?>">Procesar ahora</a>
		</form>

		<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:20px;">
			<?php foreach ( $cards as $card ) : ?>
				<div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:16px;">
					<div style="color:#646970;font-size:12px;"><?php echo esc_html( $card[0] ); ?></div>
					<div style="font-size:22px;font-weight:600;margin-top:6px;"><?php echo wp_kses_post( $card[1] ); ?></div>
				</div>
			<?php endforeach; ?>
		</div>

		<table class="widefat striped">
			<thead>
				<tr>
					<th>Fecha</th>
					<th>Cliente</th>
					<th>Productos</th>
					<th>Total carrito</th>
					<th>Estado</th>
					<th>Correos</th>
					<th>Cupón</th>
					<th>Pedido</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! $rows ) : ?>
					<tr><td colspan="9">No hay carritos en este periodo.</td></tr>
				<?php endif; ?>
				<?php foreach ( $rows as $row ) : ?>
					<?php
					$items = json_decode( (string) $row->cart_contents, true );
					$names = array();
					foreach ( (array) $items as $item ) {
						$names[] = $item['name'] . ' × ' . wc_stock_amount( $item['quantity'] );
					}
					?>
					<tr>
						<td><?php echo esc_html( get_date_from_gmt( $row->updated_at, 'd-m-Y H:i' ) ); ?></td>
						<td>
							<strong><?php echo esc_html( trim( $row->first_name . ' ' . $row->last_name ) ); ?></strong><br />
							<a href="mailto:<?php echo esc_attr( $row->email ); ?>"><?php echo esc_html( $row->email ); ?></a>
							<?php $wa = KAC_WhatsApp::link( $row ); ?>
							<?php if ( $wa ) : ?>
								<br /><a href="<?php echo esc_attr( $wa ); ?>" target="_blank" rel="noopener">WhatsApp <?php echo esc_html( $row->phone ); ?></a>
							<?php endif; ?>
							<?php if ( $row->team_notified ) : ?>
								<br /><small>Equipo avisado</small>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( implode( ', ', $names ) ); ?></td>
						<td><?php echo wp_kses_post( wc_price( $row->cart_total, array( 'currency' => $row->currency ) ) ); ?></td>
						<td><?php echo esc_html( isset( self::STATUS_LABELS[ $row->status ] ) ? self::STATUS_LABELS[ $row->status ] : $row->status ); ?><?php echo $row->link_clicked ? '<br /><small>Hizo clic en el link</small>' : ''; ?></td>
						<td><?php echo (int) $row->emails_sent; ?></td>
						<td><?php echo esc_html( $row->coupon_code ); ?></td>
						<td>
							<?php if ( $row->order_id ) : ?>
								<?php $order = wc_get_order( $row->order_id ); ?>
								<?php if ( $order ) : ?>
									<a href="<?php echo esc_url( $order->get_edit_order_url() ); ?>">#<?php echo esc_html( $order->get_order_number() ); ?></a><br />
									<?php echo wp_kses_post( wc_price( $order->get_total() ) ); ?>
								<?php endif; ?>
							<?php endif; ?>
						</td>
						<td>
							<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=kac_delete_cart&id=' . $row->id ), 'kac_delete_cart' ) ); ?>" onclick="return confirm('¿Eliminar este carrito?');">Eliminar</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
		$pages = (int) ceil( $total / $per_page );
		if ( $pages > 1 ) {
			echo '<div class="tablenav"><div class="tablenav-pages">';
			echo wp_kses_post(
				paginate_links(
					array(
						'base'    => add_query_arg( 'paged', '%#%' ),
						'format'  => '',
						'current' => $paged,
						'total'   => $pages,
					)
				)
			);
			echo '</div></div>';
		}
	}

	/**
	 * Caja de diagnóstico: muestra en qué paso está el sistema y si falló el envío de correos.
	 */
	private static function render_status() {
		global $wpdb;

		$counts = $wpdb->get_results( 'SELECT status, COUNT(*) AS qty FROM ' . KAC_DB::table() . ' GROUP BY status', OBJECT_K ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$count  = function ( $s ) use ( $counts ) {
			return isset( $counts[ $s ] ) ? (int) $counts[ $s ]->qty : 0;
		};
		$total  = array_sum( array_map( 'intval', wp_list_pluck( $counts, 'qty' ) ) );

		$next     = wp_next_scheduled( KAC_CRON_HOOK );
		$cron_off = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;
		$last     = get_option( 'kac_last_run' );
		$error    = get_option( 'kac_last_mail_error' );
		$minutes  = max( 5, (int) KAC_Settings::get( 'abandon_minutes' ) );
		$steps    = KAC_Settings::email_steps();
		$first    = $steps ? reset( $steps ) : null;

		$ago = function ( $ts ) {
			return $ts ? sprintf( 'hace %s', human_time_diff( $ts ) ) : '—';
		};

		$lines = array();

		// 1. Captura.
		if ( ! $total ) {
			$lines[] = array( 'warn', 'Captura de carritos', 'Todavía no se ha guardado ningún carrito. Haz la prueba en incógnito escribiendo email y teléfono en el checkout (sal del campo con Tab o clic afuera).' );
		} else {
			$lines[] = array( 'ok', 'Captura de carritos', sprintf( '%d guardados · %d activos · %d abandonados · %d recuperados.', $total, $count( 'active' ), $count( 'abandoned' ), $count( 'recovered' ) ) );
		}

		// 2. Proceso automático.
		if ( $cron_off ) {
			$lines[] = array( 'warn', 'Proceso automático', 'WP-Cron está desactivado en wp-config.php (DISABLE_WP_CRON). Necesitas un cron real en el hosting, o usa "Procesar ahora".' );
		} elseif ( ! $next ) {
			$lines[] = array( 'bad', 'Proceso automático', 'No está programado. Desactiva y vuelve a activar el plugin.' );
		} else {
			$late    = $next < time() - 15 * MINUTE_IN_SECONDS;
			$lines[] = array( $late ? 'warn' : 'ok', 'Proceso automático', $late ? sprintf( 'Está atrasado (%s). WP-Cron solo corre cuando alguien visita el sitio; configura un cron real o usa "Procesar ahora".', $ago( $next ) ) : sprintf( 'Corre cada 15 minutos. Próxima vez: en %s.', human_time_diff( $next ) ) );
		}

		// 3. Última ejecución.
		if ( $last ) {
			$st      = $last['stats'];
			$lines[] = array(
				( $st['team_failed'] || $st['mail_failed'] ) ? 'bad' : 'ok',
				'Última ejecución',
				sprintf(
					'%s (%s): %d marcados como abandonados · %d avisos al equipo enviados%s · %d correos a clientes enviados%s · %d esperando su hora · %d sin cupón por tener pocos productos.',
					$ago( $last['time'] ),
					'manual' === $last['trigger'] ? 'manual' : 'automática',
					$st['abandoned'],
					$st['team_sent'],
					$st['team_failed'] ? sprintf( ' (%d fallaron)', $st['team_failed'] ) : '',
					$st['mail_sent'],
					$st['mail_failed'] ? sprintf( ' (%d fallaron)', $st['mail_failed'] ) : '',
					$st['waiting'],
					isset( $st['no_coupon'] ) ? $st['no_coupon'] : 0
				),
			);
		} else {
			$lines[] = array( 'warn', 'Última ejecución', 'El proceso aún no ha corrido nunca. Presiona "Procesar ahora".' );
		}

		// 4. Errores de correo.
		if ( $error && ( ! $last || $error['time'] >= $last['time'] - HOUR_IN_SECONDS ) ) {
			$lines[] = array( 'bad', 'Error al enviar correo', sprintf( '%s a %s: %s', $ago( $error['time'] ), $error['to'], $error['message'] ) );
		}

		// 5. Recordatorio de tiempos.
		$lines[] = array(
			'info',
			'Tiempos actuales',
			sprintf(
				'Un carrito pasa a abandonado tras %d min sin actividad. El aviso al equipo sale en la siguiente ejecución%s. El correo 1 al cliente sale %s después de abandonado.',
				$minutes,
				KAC_Settings::get( 'notify_phone_only' ) ? ' (solo si dejó teléfono)' : '',
				$first ? ( $first['delay'] ? $first['delay'] . ' h' : 'inmediatamente' ) : '—'
			),
		);

		$colors = array(
			'ok'   => '#00a32a',
			'warn' => '#dba617',
			'bad'  => '#d63638',
			'info' => '#72aee6',
		);
		?>
		<div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:12px 16px;margin-bottom:20px;">
			<h2 style="margin:4px 0 8px;font-size:15px;">Estado del sistema</h2>
			<?php foreach ( $lines as $line ) : ?>
				<p style="margin:6px 0;padding-left:10px;border-left:4px solid <?php echo esc_attr( $colors[ $line[0] ] ); ?>;">
					<strong><?php echo esc_html( $line[1] ); ?>:</strong> <?php echo esc_html( $line[2] ); ?>
				</p>
			<?php endforeach; ?>
		</div>
		<?php
	}

	private static function render_settings() {
		$s = KAC_Settings::all();

		$field = function ( $key, $label, $type = 'text', $help = '' ) use ( $s ) {
			echo '<tr><th scope="row"><label for="kac_' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td>';
			$name = 'kac[' . esc_attr( $key ) . ']';
			if ( 'checkbox' === $type ) {
				echo '<input type="hidden" name="' . $name . '" value="0" />'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '<input type="checkbox" id="kac_' . esc_attr( $key ) . '" name="' . $name . '" value="1" ' . checked( ! empty( $s[ $key ] ), true, false ) . ' />'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} elseif ( 'textarea' === $type ) {
				echo '<textarea id="kac_' . esc_attr( $key ) . '" name="' . $name . '" rows="5" class="large-text">' . esc_textarea( $s[ $key ] ) . '</textarea>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} elseif ( 'count_mode' === $type ) {
				echo '<select id="kac_' . esc_attr( $key ) . '" name="' . $name . '">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '<option value="units" ' . selected( $s[ $key ], 'units', false ) . '>Unidades (suma de cantidades)</option>';
				echo '<option value="lines" ' . selected( $s[ $key ], 'lines', false ) . '>Productos distintos</option>';
				echo '</select>';
			} elseif ( 'coupon_type' === $type ) {
				echo '<select id="kac_' . esc_attr( $key ) . '" name="' . $name . '">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '<option value="percent" ' . selected( $s[ $key ], 'percent', false ) . '>Porcentaje (%)</option>';
				echo '<option value="fixed_cart" ' . selected( $s[ $key ], 'fixed_cart', false ) . '>Monto fijo</option>';
				echo '</select>';
			} else {
				$class = 'number' === $type ? 'small-text' : 'regular-text';
				echo '<input type="' . esc_attr( $type ) . '" step="any" id="kac_' . esc_attr( $key ) . '" name="' . $name . '" value="' . esc_attr( $s[ $key ] ) . '" class="' . esc_attr( $class ) . '" />'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			if ( $help ) {
				echo '<p class="description">' . esc_html( $help ) . '</p>';
			}
			echo '</td></tr>';
		};
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="kac_save_settings" />
			<?php wp_nonce_field( 'kac_save_settings' ); ?>

			<h2>General</h2>
			<table class="form-table">
				<?php
				$field( 'enabled', 'Activar sistema', 'checkbox' );
				$field( 'abandon_minutes', 'Minutos para considerar abandonado', 'number', 'Tiempo sin actividad antes de marcar el carrito como abandonado.' );
				$field( 'min_cart_total', 'Monto mínimo del carrito', 'number', 'Carritos bajo este monto no se registran (0 = todos).' );
				$field( 'from_name', 'Nombre del remitente', 'text', 'Vacío = el configurado en WooCommerce > Ajustes > Correos electrónicos.' );
				$field( 'from_email', 'Email del remitente', 'email' );
				$field( 'retention_days', 'Días que se guardan los registros', 'number' );
				$field( 'consent_text', 'Aviso bajo el formulario de checkout', 'textarea' );
				?>
			</table>

			<?php for ( $i = 1; $i <= 3; $i++ ) : ?>
				<h2>Correo <?php echo (int) $i; ?></h2>
				<table class="form-table">
					<?php
					$field( "email{$i}_enabled", 'Activado', 'checkbox' );
					$field( "email{$i}_delay", 'Enviar después de (horas)', 'number', 'Horas desde que el carrito fue marcado como abandonado.' );
					$field( "email{$i}_subject", 'Asunto' );
					$field( "email{$i}_heading", 'Título dentro del correo' );
					$field( "email{$i}_body", 'Mensaje', 'textarea', 'Variables: {first_name}, {cart_total}, {coupon_code}, {coupon_amount}, {coupon_days}, {site_name}' );
					$field( "email{$i}_coupon", 'Incluir cupón de descuento', 'checkbox' );
					?>
				</table>
			<?php endfor; ?>

			<h2>Aviso al equipo + WhatsApp</h2>
			<p>Cuando un carrito queda abandonado, les llega un correo con los datos del cliente y un botón que abre WhatsApp con el mensaje ya escrito.</p>
			<table class="form-table">
				<?php
				$field( 'notify_enabled', 'Enviar aviso al equipo', 'checkbox' );
				$field( 'notify_email', 'Email del equipo', 'text', 'Puedes poner varios separados por coma. Vacío = email del administrador.' );
				$field( 'notify_phone_only', 'Solo carritos con teléfono', 'checkbox', 'Si está activo, solo avisa cuando el cliente dejó su teléfono (para poder escribirle).' );
				$field( 'notify_min_total', 'Monto mínimo para avisar', 'number', 'Ej: 300000 para que solo avise por hornos. 0 = todos.' );
				$field( 'wa_message', 'Mensaje de WhatsApp', 'textarea', 'Variables: {first_name}, {main_product} (el producto de mayor valor), {products} (todos), {cart_total}, {recover_url}, {coupon_code}, {site_name}. Para negrita en WhatsApp usa *asteriscos*.' );
				?>
			</table>

			<h2>Cupón</h2>
			<table class="form-table">
				<?php
				$field( 'coupon_type', 'Tipo de descuento', 'coupon_type' );
				$field( 'coupon_amount', 'Monto / porcentaje', 'number' );
				$field( 'coupon_days', 'Días de validez', 'number' );
				$field( 'coupon_min_total', 'Compra mínima para usar el cupón', 'number', '0 = sin mínimo.' );
				$field( 'coupon_min_items', 'Mínimo de productos para enviar cupón', 'number', 'Carritos con menos productos no reciben el correo con cupón (la secuencia termina en el correo anterior). 0 o 1 = todos reciben cupón.' );
				$field( 'coupon_count_mode', 'Cómo contar los productos', 'count_mode', 'Unidades: 3 harinas iguales cuentan como 3. Productos distintos: 3 harinas iguales cuentan como 1.' );
				?>
			</table>

			<?php submit_button( 'Guardar configuración' ); ?>
		</form>

		<hr />
		<h2>Enviar correo de prueba</h2>
		<p>Usa el último carrito capturado para mostrar cómo se ve el correo (el cupón de la prueba no es real).</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="kac_send_test" />
			<?php wp_nonce_field( 'kac_send_test' ); ?>
			<select name="step">
				<option value="1">Correo 1</option>
				<option value="2">Correo 2</option>
				<option value="3">Correo 3</option>
				<option value="team">Aviso al equipo (WhatsApp)</option>
			</select>
			<input type="email" name="to" value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>" class="regular-text" />
			<?php submit_button( 'Enviar prueba', 'secondary', 'submit', false ); ?>
		</form>
		<?php
	}

	public static function save_settings() {
		self::check( 'kac_save_settings' );

		$input    = isset( $_POST['kac'] ) ? (array) wp_unslash( $_POST['kac'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$defaults = KAC_Settings::defaults();
		$clean    = array();

		foreach ( $defaults as $key => $default ) {
			$value = isset( $input[ $key ] ) ? $input[ $key ] : $default;
			if ( is_int( $default ) || is_float( $default ) ) {
				$clean[ $key ] = max( 0, (float) $value );
			} elseif ( false !== strpos( $key, '_body' ) || 'consent_text' === $key ) {
				$clean[ $key ] = sanitize_textarea_field( $value );
			} elseif ( 'wa_message' === $key ) {
				$clean[ $key ] = sanitize_textarea_field( $value );
			} elseif ( 'notify_email' === $key ) {
				$clean[ $key ] = implode( ',', array_filter( array_map( 'sanitize_email', explode( ',', $value ) ), 'is_email' ) );
			} elseif ( 'from_email' === $key ) {
				$clean[ $key ] = sanitize_email( $value );
			} elseif ( 'coupon_count_mode' === $key ) {
				$clean[ $key ] = in_array( $value, array( 'units', 'lines' ), true ) ? $value : 'units';
			} elseif ( 'coupon_type' === $key ) {
				$clean[ $key ] = in_array( $value, array( 'percent', 'fixed_cart' ), true ) ? $value : 'percent';
			} else {
				$clean[ $key ] = sanitize_text_field( $value );
			}
		}
		// Emojis -> entidades, para que no se rompan si la base de datos no es utf8mb4.
		foreach ( $clean as $key => $value ) {
			if ( is_string( $value ) ) {
				$clean[ $key ] = wp_encode_emoji( $value );
			}
		}

		update_option( KAC_Settings::OPTION, $clean );
		wp_safe_redirect( self::url( array( 'tab' => 'settings', 'kac_msg' => 'saved' ) ) );
		exit;
	}

	public static function delete_cart() {
		self::check( 'kac_delete_cart' );
		$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
		if ( $id ) {
			KAC_DB::delete( $id );
		}
		wp_safe_redirect( self::url( array( 'kac_msg' => 'deleted' ) ) );
		exit;
	}

	public static function send_test() {
		global $wpdb;
		self::check( 'kac_send_test' );

		$step = isset( $_POST['step'] ) ? sanitize_key( $_POST['step'] ) : '1';
		$to   = isset( $_POST['to'] ) ? sanitize_email( wp_unslash( $_POST['to'] ) ) : '';

		$row = $wpdb->get_row( 'SELECT * FROM ' . KAC_DB::table() . ' ORDER BY id DESC LIMIT 1' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $row ) {
			wp_safe_redirect( self::url( array( 'tab' => 'settings', 'kac_msg' => 'test_empty' ) ) );
			exit;
		}

		if ( 'team' === $step ) {
			$ok = is_email( $to ) && KAC_Mailer::notify_team( $row, $to );
		} else {
			$ok = is_email( $to ) && KAC_Mailer::send( $row, (int) $step, $to, true );
		}
		wp_safe_redirect( self::url( array( 'tab' => 'settings', 'kac_msg' => $ok ? 'test_ok' : 'test_fail' ) ) );
		exit;
	}

	public static function run_now() {
		self::check( 'kac_run_now' );
		delete_transient( 'kac_cron_lock' );
		$result = KAC_Cron::run( 'manual' );
		wp_safe_redirect( self::url( array( 'kac_msg' => $result ? 'ran' : 'ran_off' ) ) );
		exit;
	}

	public static function export_csv() {
		global $wpdb;
		self::check( 'kac_export' );

		$days   = isset( $_GET['days'] ) ? max( 1, (int) $_GET['days'] ) : 30;
		$status = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '';
		$where  = $wpdb->prepare( 'WHERE created_at >= %s', gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS ) );
		if ( $status && isset( self::STATUS_LABELS[ $status ] ) ) {
			$where .= $wpdb->prepare( ' AND status = %s', $status );
		}
		$rows = $wpdb->get_results( 'SELECT * FROM ' . KAC_DB::table() . " {$where} ORDER BY updated_at DESC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=carritos-abandonados-' . gmdate( 'Y-m-d' ) . '.csv' );

		$out = fopen( 'php://output', 'w' );
		fwrite( $out, "\xEF\xBB\xBF" ); // BOM para que Excel lea bien los acentos.
		fputcsv( $out, array( 'Fecha', 'Email', 'Nombre', 'Apellido', 'Teléfono', 'Productos', 'Total carrito', 'Estado', 'Correos enviados', 'Cupón', 'Pedido', 'Total pedido' ), ';' );
		foreach ( $rows as $r ) {
			$names = array();
			foreach ( (array) json_decode( (string) $r['cart_contents'], true ) as $item ) {
				$names[] = $item['name'] . ' x' . $item['quantity'];
			}
			fputcsv(
				$out,
				array(
					get_date_from_gmt( $r['updated_at'], 'd-m-Y H:i' ),
					$r['email'],
					$r['first_name'],
					$r['last_name'],
					$r['phone'],
					implode( ' | ', $names ),
					round( (float) $r['cart_total'] ),
					isset( self::STATUS_LABELS[ $r['status'] ] ) ? self::STATUS_LABELS[ $r['status'] ] : $r['status'],
					$r['emails_sent'],
					$r['coupon_code'],
					$r['order_id'] ? $r['order_id'] : '',
					round( (float) $r['order_total'] ),
				),
				';'
			);
		}
		fclose( $out );
		exit;
	}
}
