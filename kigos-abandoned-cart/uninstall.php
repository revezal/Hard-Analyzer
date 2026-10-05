<?php
/**
 * Limpieza al eliminar el plugin desde WordPress.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'kac_carts' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
delete_option( 'kac_settings' );
delete_option( 'kac_db_version' );
delete_option( 'kac_unsubscribed' );
delete_transient( 'kac_cron_lock' );
wp_clear_scheduled_hook( 'kac_process_carts' );
