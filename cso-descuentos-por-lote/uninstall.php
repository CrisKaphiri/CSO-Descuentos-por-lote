<?php
/**
 * Se ejecuta automáticamente cuando alguien desinstala (elimina) el plugin
 * desde el panel de Plugins de WordPress. Limpia las opciones guardadas
 * en la base de datos para no dejar datos huérfanos.
 */

// Seguridad: este archivo solo debe ejecutarse en el proceso real de desinstalación de WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option( 'csodl_tiers' );
delete_option( 'csodl_exclude_tags' );
delete_option( 'csodl_enable_minicart' );
delete_option( 'csodl_enable_cart_top' );
delete_option( 'csodl_enable_checkout' );

// Si el sitio usa Multisite y el plugin estuvo activo a nivel de red,
// limpiamos también las opciones de cada subsitio.
if ( is_multisite() ) {
    global $wpdb;
    $site_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );

    foreach ( $site_ids as $site_id ) {
        switch_to_blog( $site_id );
        delete_option( 'csodl_tiers' );
        delete_option( 'csodl_exclude_tags' );
        delete_option( 'csodl_enable_minicart' );
        delete_option( 'csodl_enable_cart_top' );
        delete_option( 'csodl_enable_checkout' );
        restore_current_blog();
    }
}
