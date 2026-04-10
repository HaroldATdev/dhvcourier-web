<?php
if ( ! defined( 'ABSPATH' ) ) exit;

<?php
/**
 * Plugin Name: WP Cargo Tipo de Envio
 * Version: 2.5.0
 * Author: DHV Courier
 * Description: Gestión de modalidades, tarifarios y cotizador DHV.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WPCTE_VERSION', '2.5.0' );
define( 'WPCTE_PATH',    plugin_dir_path( __FILE__ ) );
define( 'WPCTE_URL',     plugin_dir_url( __FILE__ ) );

/* ======================================================================
   TARIFARIO CENTRAL — editable desde admin
   ====================================================================== */
function wpcte_tarifario() {
    $saved = get_option( 'wpcte_tarifario', null );
    if ( $saved && is_array( $saved ) ) {
        // Asegurar que items de mercadería sean asociativos
        if ( isset( $saved['mercaderia']['categorias'] ) ) {
            foreach ( $saved['mercaderia']['categorias'] as $key => &$cat ) {
                if ( empty($cat['items']) ) $cat['items'] = [];
                elseif ( isset($cat['items']) && array_values($cat['items']) === $cat['items'] ) {
                    $cat['items'] = []; // era array indexado, resetear
                }
                if ( !isset($cat['rutas']) ) $cat['rutas'] = [];
            }
            unset($cat);
        }
        return $saved;
    }
    return wpcte_tarifario_default();
}

function wpcte_tarifario_default() {
    return array(
        'lima_lima'     => array( 'tipo'=>'lima_lima',     'titulo'=>'DHV-Dentro de Lima',        'vehiculos'=>array(), 'distritos'=>array(), 'perifericas'=>array() ),
        'carga_general' => array( 'tipo'=>'carga_general', 'titulo'=>'DHV-Carga General',         'rutas'=>array() ),
        'mercaderia' => array(
            'tipo'             => 'mercaderia',
            'titulo'           => 'DHV-Mercadería Frecuente',
            'lugares_lima'     => array(),
            'lugares_provincia'=> array(),
            'categorias'       => array(),
        ),
        'aereo' => array(
            'tipo'  => 'aereo',
            'titulo'=> 'DHV-Aéreos',
            'rutas' => array(),
        ),
        'sobres' => array(
            'tipo'              => 'sobres',
            'titulo'            => 'DHV-Sobres',
            'lugares_lima'      => array(),
            'lugares_provincia' => array(),
            'tarifas' => array(
                'Lima - Lima'           => array('activo'=>true,'agencia'=>7, 'domicilio'=>12,'devolucion'=>3),
                'Lima - Provincia'      => array('activo'=>true,'agencia'=>7, 'domicilio'=>12,'devolucion'=>3),
                'Provincia - Provincia' => array('activo'=>true,'agencia'=>5, 'domicilio'=>10,'devolucion'=>2),
            ),
        ),
    );
}
function wpcte_lugares() {
    $tar = wpcte_tarifario();

    // Orígenes y destinos de Lima: vienen de los distritos + periféricas
    $distritos   = array_keys( $tar['lima_lima']['distritos']   ?? [] );
    $perifericas = array_keys( $tar['lima_lima']['perifericas'] ?? [] );
    $all_lima    = array_merge( $distritos, $perifericas );

    // Provincias: todos los destinos de carga general que no están en lima
    $provincias = [];
    foreach ( $tar['carga_general']['rutas'] ?? [] as $ruta ) {
        foreach ( array_keys( $ruta['destinos'] ?? [] ) as $dest ) {
            if ( ! in_array( $dest, $all_lima ) && ! in_array( $dest, $provincias ) ) {
                $provincias[] = $dest;
            }
        }
    }

    // Orígenes aéreos: keys de rutas de aereo
    $aereo_origenes = array_keys( $tar['aereo']['rutas'] ?? [] );
    // Destinos por origen aéreo
    $aereo_rutas = [];
    foreach ( $tar['aereo']['rutas'] ?? [] as $desde => $ruta ) {
        $aereo_rutas[$desde] = array_keys( $ruta['destinos'] ?? [] );
    }

    return array(
        'lima'              => $distritos,
        'lima_perifericas'  => $perifericas,
        'provincias'        => $provincias,
        'aereo_origenes'    => $aereo_origenes,
        'aereo_rutas'       => $aereo_rutas,
        'merc_lima'         => $tar['mercaderia']['lugares_lima']      ?? [],
        'merc_provincia'    => $tar['mercaderia']['lugares_provincia']  ?? [],
        'sobres_lima'       => $tar['sobres']['lugares_lima']           ?? [],
        'sobres_provincia'  => $tar['sobres']['lugares_provincia']      ?? [],
    );
}
/* ======================================================================
   REGLAS: qué modalidades aparecen en cada tipo de envío
   ====================================================================== */
function wpcte_modalidades_por_tipo() {
    return array(
        'puerta_puerta' => array( 'lima_lima', 'mercaderia', 'sobres', 'carga_general' ),
        'agencia'       => array( 'lima_lima', 'aereo', 'sobres', 'carga_general' ),
        'almacen'       => array( 'lima_lima', 'carga_general' ),
    );
}
