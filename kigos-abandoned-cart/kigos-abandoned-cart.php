<?php
/**
 * Plugin Name:       Kigos Carritos Abandonados
 * Description:       Captura carritos abandonados de WooCommerce, envía una secuencia de correos de recuperación (con cupón opcional) y mide los ingresos recuperados.
 * Version:           1.3.2
 * Author:            Kigos Chile
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * WC requires at least: 7.0
 * Text Domain:       kigos-abandoned-cart
 */

defined( 'ABSPATH' ) || exit;

define( 'KAC_VERSION', '1.3.2' );
define( 'KAC_FILE', __FILE__ );
define( 'KAC_PATH', plugin_dir_path( __FILE__ ) );
define( 'KAC_URL', plugin_dir_url( __FILE__ ) );
define( 'KAC_CRON_HOOK', 'kac_process_carts' );

require_once KAC_PATH . 'includes/class-kac-settings.php';
require_once KAC_PATH . 'includes/class-kac-db.php';
require_once KAC_PATH . 'includes/class-kac-tracker.php';
require_once KAC_PATH . 'includes/class-kac-recovery.php';
require_once KAC_PATH . 'includes/class-kac-mailer.php';
require_once KAC_PATH . 'includes/class-kac-whatsapp.php';
require_once KAC_PATH . 'includes/class-kac-cron.php';
require_once KAC_PATH . 'includes/class-kac-admin.php';

// Compatibilidad con HPOS (tablas de pedidos de alto rendimiento).
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', KAC_FILE, true );
		}
	}
);

register_activation_hook( KAC_FILE, array( 'KAC_DB', 'install' ) );
register_activation_hook( KAC_FILE, array( 'KAC_Cron', 'schedule' ) );
register_deactivation_hook( KAC_FILE, array( 'KAC_Cron', 'unschedule' ) );

add_action(
	'plugins_loaded',
	function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error"><p><strong>Kigos Carritos Abandonados</strong> requiere WooCommerce activo.</p></div>';
				}
			);
			return;
		}

		KAC_DB::maybe_upgrade();
		KAC_Tracker::init();
		KAC_Recovery::init();
		KAC_Cron::init();

		if ( is_admin() ) {
			KAC_Admin::init();
		}
	}
);
