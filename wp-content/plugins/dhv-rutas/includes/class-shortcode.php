<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class DHV_Rutas_Shortcode {

    public function __construct() {
        add_shortcode( 'dhv_recojo', array( $this, 'render_recojo' ) );
    }

    public function render_recojo( $atts ) {
        if ( ! is_user_logged_in() ) {
            return '<p>' . __( 'Debes iniciar sesión para ver tus rutas.', 'dhv-rutas' ) . '</p>';
        }

        $driver_id = get_current_user_id();
        $grouped   = DHV_Rutas_DB::get_recojo_shipments( $driver_id );

        ob_start();

        // --- CSS inline (garantiza que siempre cargue, sin depender del enqueue) ---
        $css_file = DHV_RUTAS_PATH . 'assets/css/dhv-rutas.css';
        if ( file_exists( $css_file ) ) {
            echo '<style id="dhv-rutas-inline">' . file_get_contents( $css_file ) . '</style>';
        }

        // --- HTML del template ---
        include DHV_RUTAS_PATH . 'templates/recojo-view.php';

        // --- JS inline: dhvRutas se declara DENTRO del script, antes del IIFE ---
        $ajax_url = admin_url( 'admin-ajax.php' );
        $nonce    = wp_create_nonce( 'dhv_rutas_nonce' );
        $js_file  = DHV_RUTAS_PATH . 'assets/js/dhv-rutas.js';

        if ( file_exists( $js_file ) ) {
            $js_content = file_get_contents( $js_file );

            // Inyectamos dhvRutas como variable global ANTES del IIFE,
            // dentro del mismo bloque <script> para evitar race conditions.
            echo '<script id="dhv-rutas-inline-js">'
                . 'window.dhvRutas = {'
                .     '"ajax_url":' . wp_json_encode( $ajax_url ) . ','
                .     '"nonce":'    . wp_json_encode( $nonce )    . ''
                . '};'
                . $js_content
                . '</script>';
        }

        return ob_get_clean();
    }
}

new DHV_Rutas_Shortcode();
