<?php
if ( ! defined( 'ABSPATH' ) ) { die; }

/**
 * WPC_Branch_Frontend
 *
 * Una sola página en el dashboard de WPCargo con dos pestañas:
 *   - "Sucursales"         : tabla CRUD  (solo admin)
 *   - "Transferir"         : escáner     (admin + colaborador)
 *
 * Shortcode : [wpcbm-sucursales]   ← guión, igual que todos los plugins WPCargo
 * Tab param : ?wpcbm=sucursales | ?wpcbm=transferir
 */
class WPC_Branch_Frontend {

    public function __construct() {
        // Registrar shortcode con guión (patrón WPCargo: 'wpca-almacen', 'wcmas-masivos', etc.)
        add_shortcode( 'wpcbm-sucursales', [ $this, 'render_shortcode' ] );

        add_action( 'init',                      [ $this, 'asegurar_pagina' ] );
        add_filter( 'wpcfe_after_sidebar_menus', [ $this, 'item_sidebar' ], 30, 1 );
        add_action( 'wp_enqueue_scripts',        [ $this, 'encolar_assets' ], 100 );
    }

    /* ─────────────────────────────────────────────────────────
       PÁGINA ÚNICA DEL DASHBOARD
    ───────────────────────────────────────────────────────── */

    public function asegurar_pagina(): void {
        $this->obtener_o_crear_pagina();
    }

    private function obtener_o_crear_pagina(): int {
        $id = (int) get_option( 'wpcbm_pagina_id' );
        if ( $id && get_post_status( $id ) === 'publish' ) {
            // Asegurar que el ícono del sidebar esté siempre actualizado
            $icon_actual = get_post_meta( $id, 'wpcfe_menu_icon', true );
            if ( $icon_actual !== 'fa-building-o' ) {
                update_post_meta( $id, 'wpcfe_menu_icon', 'fa-building-o' );
            }
            return $id;
        }

        global $wpdb;

        // Buscar página existente con cualquiera de las dos variantes del shortcode
        $id = (int) $wpdb->get_var(
            "SELECT ID FROM {$wpdb->prefix}posts
             WHERE ( post_content LIKE '%[wpcbm-sucursales]%'
                  OR post_content LIKE '%[wpcbm_sucursales]%' )
             AND post_status = 'publish'
             ORDER BY ID ASC LIMIT 1"
        );

        if ( $id ) {
            // Si la página usa el shortcode con guión_bajo, actualizarla al guión
            $post = get_post( $id );
            if ( $post && strpos( $post->post_content, '[wpcbm_sucursales]' ) !== false ) {
                wp_update_post( [
                    'ID'           => $id,
                    'post_content' => str_replace( '[wpcbm_sucursales]', '[wpcbm-sucursales]', $post->post_content ),
                ] );
            }
        } else {
            // Crear nueva página
            $id = (int) wp_insert_post( [
                'post_title'   => 'Sucursales',
                'post_content' => '[wpcbm-sucursales]',
                'post_status'  => 'publish',
                'post_type'    => 'page',
            ] );
        }

        if ( $id ) {
            update_post_meta( $id, '_wp_page_template', 'dashboard.php' );
            update_post_meta( $id, 'wpcfe_menu_icon',   'fa-building-o' );
            update_option( 'wpcbm_pagina_id', $id, false );
        }

        return (int) $id;
    }

    private function url_base(): string {
        $id = (int) get_option( 'wpcbm_pagina_id' );
        return $id ? ( get_permalink( $id ) ?: home_url( '/' ) ) : home_url( '/' );
    }

    private function url_tab( string $tab ): string {
        return add_query_arg( 'wpcbm', $tab, $this->url_base() );
    }

    /* ─────────────────────────────────────────────────────────
       SIDEBAR DE WPCARGO — un solo ítem
    ───────────────────────────────────────────────────────── */

    public function item_sidebar( array $menu ): array {
        $es_admin       = current_user_can( 'manage_options' );
        $es_colaborador = in_array( 'wpcargo_branch_manager', wpcbranch_current_user_role() );

        if ( ! $es_admin && ! $es_colaborador ) return $menu;

        $menu['wpcbm-sucursales'] = [
            'page-id'   => (int) get_option( 'wpcbm_pagina_id' ),
            'label'     => 'Sucursales',
            'permalink' => $this->url_base(),
            'icon'      => 'fa-building-o',
        ];

        return $menu;
    }

    /* ─────────────────────────────────────────────────────────
       ASSETS  (solo en la página de sucursales)
    ───────────────────────────────────────────────────────── */

    public function encolar_assets(): void {
        $page_id = (int) get_option( 'wpcbm_pagina_id' );
        if ( ! $page_id || (int) get_queried_object_id() !== $page_id ) return;

        wp_enqueue_style(
            'wpcbm-select2-css',
            'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css',
            [], WPC_BRANCHES_VERSION
        );
        wp_enqueue_script(
            'wpcbm-select2-js',
            'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js',
            [ 'jquery' ], WPC_BRANCHES_VERSION, true
        );
        wp_enqueue_style(
            'wpcbm-frontend-style',
            WPC_BRANCHES_URL . 'admin/assets/css/branch-manager-admin.css',
            [], WPC_BRANCHES_VERSION
        );
        wp_enqueue_script(
            'branch-manager-scripts',
            WPC_BRANCHES_URL . 'admin/assets/js/branch-manager-admin.js',
            [ 'jquery', 'wpcbm-select2-js' ], WPC_BRANCHES_VERSION, true
        );
        wp_localize_script( 'branch-manager-scripts', 'wpcBMAjaxHandler', [
            'ajaxurl'            => admin_url( 'admin-ajax.php' ),
            'nonce'              => wp_create_nonce( 'wpc_bm_ajax' ),
            'errormessage'       => __( 'Algo salió mal. Por favor recarga la página.', 'wpcargo-branches' ),
            'deleteConfirmation' => __( '¿Estás seguro de que deseas eliminar esta sucursal?', 'wpcargo-branches' ),
            'transferSuccess'    => __( 'Transferencia completada con éxito.', 'wpcargo-branches' ),
            'transferError'      => __( 'Error al transferir el envío.', 'wpcargo-branches' ),
        ] );
        wp_enqueue_script(
            'wpcbm-frontend-scripts',
            WPC_BRANCHES_URL . 'assets/js/scripts.js',
            [ 'jquery' ], WPC_BRANCHES_VERSION, true
        );
        wp_localize_script( 'wpcbm-frontend-scripts', 'wpcBMFrontendAjaxHandler', [
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'wpc_bm_ajax' ),
        ] );
    }

    /* ─────────────────────────────────────────────────────────
       SHORTCODE PRINCIPAL
    ───────────────────────────────────────────────────────── */

    public function render_shortcode(): string {
        $es_admin       = current_user_can( 'manage_options' );
        $es_colaborador = in_array( 'wpcargo_branch_manager', wpcbranch_current_user_role() );

        if ( ! $es_admin && ! $es_colaborador ) {
            return '<div class="alert alert-warning"><i class="fa fa-lock mr-2"></i>'
                 . esc_html__( 'Acceso restringido.', 'wpcargo-branches' ) . '</div>';
        }

        // Pestaña activa
        $tab = sanitize_key( $_GET['wpcbm'] ?? '' );
        if ( ! $es_admin ) {
            $tab = 'transferir'; // colaborador solo ve transferencia
        }
        if ( ! in_array( $tab, [ 'sucursales', 'transferir' ], true ) ) {
            $tab = 'sucursales'; // default admin
        }

        ob_start();
        ?>
        <div class="wpcbm-wrap">

            <?php $this->render_tabs( $tab, $es_admin ); ?>

            <?php if ( $tab === 'sucursales' && $es_admin ) : ?>
                <?php $this->render_tab_sucursales(); ?>
            <?php elseif ( $tab === 'transferir' ) : ?>
                <?php $this->render_tab_transferir(); ?>
            <?php endif; ?>

        </div>
        <?php $this->render_estilos(); ?>
        <?php
        return ob_get_clean();
    }

    /* ─────────────────────────────────────────────────────────
       TABS
    ───────────────────────────────────────────────────────── */

    private function render_tabs( string $tab_activo, bool $es_admin ): void {
        $tabs = [];
        if ( $es_admin ) {
            $tabs['sucursales'] = [ 'label' => 'Sucursales',          'icon' => 'fa-code-branch' ];
        }
        $tabs['transferir'] = [ 'label' => 'Transferir a Sucursal', 'icon' => 'fa-exchange-alt' ];
        ?>
        <ul class="nav nav-pills mb-3 flex-wrap" style="gap:.25rem;">
            <?php foreach ( $tabs as $key => $tab ) :
                $url    = $this->url_tab( $key );
                $active = $tab_activo === $key ? 'active' : '';
            ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $active; ?>" href="<?php echo esc_url( $url ); ?>">
                    <i class="fa <?php echo esc_attr( $tab['icon'] ); ?> mr-1"></i>
                    <?php echo esc_html( $tab['label'] ); ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
        <hr class="mt-0 mb-3">
        <?php
    }

    /* ─────────────────────────────────────────────────────────
       TAB: SUCURSALES
    ───────────────────────────────────────────────────────── */

    private function render_tab_sucursales(): void {
        global $wpcargo;
        $all_branches = wpcbm_get_all_branch( -1 );
        ?>
        <div class="wpcbm-tab-content">

            <?php /* ── Branch Restriction Settings (igual que en el admin WP) ── */ ?>
            <div class="wpcbm-restriction-wrap mb-3">
                <?php require WPC_BRANCHES_PATH . 'admin/templates/branch-restriction.tpl.php'; ?>
            </div>

            <div class="wpcbm-topbar">
                <a id="add-branch" href="#" class="btn btn-primary btn-sm">
                    <i class="fa fa-plus mr-1"></i>
                    <?php esc_html_e( 'Nueva Sucursal', 'wpcargo-branches' ); ?>
                </a>
            </div>
            <div id="wpc-branch-wrapper" class="wpcbm-table-wrap mt-3">
                <?php require WPC_BRANCHES_PATH . 'admin/templates/manage-branch.tpl.php'; ?>
            </div>
            <?php require WPC_BRANCHES_PATH . 'admin/templates/add-branch.tpl.php'; ?>
            <?php require WPC_BRANCHES_PATH . 'admin/templates/edit-branch.tpl.php'; ?>
        </div>
        <script>
        jQuery(document).ready(function($){
            /*
             * Mantener los selectores de colaboradores como select nativo multiple.
             */
            function wpcbmInitModalSelects() {
                $('.select-bm').each(function(){
                    if ( typeof $.fn.select2 !== 'undefined' && $(this).hasClass('select2-hidden-accessible') ) {
                        $(this).select2('destroy');
                    }

                    this.style.setProperty('display', 'block', 'important');
                    this.style.setProperty('visibility', 'visible', 'important');
                    this.style.setProperty('opacity', '1', 'important');
                    this.style.setProperty('width', '100%', 'important');
                    $(this).attr('size', 6);
                });
            }

            // Abrir modal Agregar
            $('#add-branch').on('click', function(e){
                e.preventDefault();
                $('#addBranchModal').css({ display: 'block' });
                setTimeout( wpcbmInitModalSelects, 80 );
            });

            /*
             * INTERCEPT el click de Editar para manejar correctamente los branch_manager.
             * El JS original hace .val(value) con el valor serializado PHP — no funciona en Select2.
             * Aquí interceptamos ANTES de que el JS original abra el modal y seteamos los valores.
             */
            $('body').on('click', '#wpc-branch-wrapper .edit', function(e){
                // Dejamos que branch-manager-admin.js corra su AJAX normalmente.
                // Usamos un MutationObserver + polling para capturar cuando el modal se muestra,
                // y luego fijamos el select de branch_manager correctamente.
            });

            // Observer sobre el modal de edición: cuando aparece, parsear y setear branch_manager
            if ( typeof MutationObserver !== 'undefined' ) {
                var editModalObs = new MutationObserver(function(mutations){
                    mutations.forEach(function(m){
                        if ( m.type === 'attributes' && m.attributeName === 'style' ) {
                            if ( $('#editBranchModal').css('display') === 'block' ) {
                                setTimeout(function(){
                                    wpcbmInitModalSelects();
                                    wpcbmFixBranchManagerSelect('#edit-branch #update-branch_manager');
                                }, 120);
                            }
                        }
                    });
                });
                var $editModalEl = document.getElementById('editBranchModal');
                if ( $editModalEl ) {
                    editModalObs.observe( $editModalEl, { attributes: true, attributeFilter: ['style'] } );
                }
            }

            /*
             * Parsear el valor del select#update-branch_manager.
             * El valor puede venir como array JSON o string serializado PHP.
             */
            function wpcbmFixBranchManagerSelect( selector ) {
                var $sel = $(selector);
                if ( ! $sel.length ) return;
                var rawVal = $sel.val(); // puede ser string serializado, array, o null
                var ids = [];

                if ( Array.isArray(rawVal) ) {
                    ids = rawVal.filter(function(v){ return v && v !== ''; });
                } else if ( typeof rawVal === 'string' && rawVal.length > 0 ) {
                    // Intentar parsear como JSON
                    try { ids = JSON.parse(rawVal); } catch(e) {}
                    // Si sigue siendo string, extraer números con regex (cubre serializado PHP)
                    if ( ! Array.isArray(ids) ) {
                        var matches = rawVal.match(/\d+/g);
                        ids = matches ? matches : [];
                    }
                }

                // Convertir a strings y filtrar vacíos
                ids = ids.map(function(v){ return String(v); }).filter(function(v){ return v !== ''; });

                if ( ids.length > 0 ) {
                    $sel.val(ids).trigger('change');
                } else {
                    $sel.val(null).trigger('change');
                }
            }

            // Forzar visibilidad de checkboxes con JS también (doble seguro)
            function wpcbmFixCheckboxes() {
                $('#wpcbranch-restriction .wpcbranch-chk-row input[type="checkbox"]').each(function(){
                    this.style.setProperty('display',    'inline-block', 'important');
                    this.style.setProperty('visibility', 'visible',      'important');
                    this.style.setProperty('opacity',    '1',            'important');
                    this.style.setProperty('width',      '16px',         'important');
                    this.style.setProperty('height',     '16px',         'important');
                    this.style.setProperty('position',   'static',       'important');
                    this.style.setProperty('float',      'none',         'important');
                    this.style.setProperty('-webkit-appearance', 'checkbox', 'important');
                    this.style.setProperty('appearance', 'checkbox', 'important');
                });
            }
            wpcbmFixCheckboxes();
            // Re-aplicar por si el DOM cambia
            setTimeout(wpcbmFixCheckboxes, 500);
        });
        </script>
        <?php
    }

    /* ─────────────────────────────────────────────────────────
       TAB: TRANSFERIR
    ───────────────────────────────────────────────────────── */

    private function render_tab_transferir(): void {
        $all_branch = wpcbm_get_all_branch( -1 );
        ?>
        <div class="wpcbm-tab-content">
            <?php require WPC_BRANCHES_PATH . 'admin/templates/transfer.tpl.php'; ?>
        </div>
        <script>
        jQuery(document).ready(function($){
            /*
             * El dashboard frontend de WPCargo oculta los <select> nativos via CSS
             * y espera que Select2 los reemplace. Inicializamos #shipment-branch
             * explicitamente para que sea visible y funcional.
             */
            function initTransferSelect2() {
                if ( typeof $.fn.select2 !== 'undefined' ) {
                    // Destruir instancia previa si existe para evitar duplicados
                    if ( $('#shipment-branch').hasClass('select2-hidden-accessible') ) {
                        $('#shipment-branch').select2('destroy');
                    }
                    // Forzar visibilidad nativa antes de que Select2 tome el control
                    $('#shipment-branch').css({ display: 'block', width: '100%' });
                    $('#shipment-branch').select2({
                        placeholder : '-- Seleccionar Sucursal --',
                        width        : '100%',
                        allowClear   : false,
                    });
                    // Cuando el usuario elige una sucursal, mover el foco al campo de seguimiento
                    $('#shipment-branch').on( 'change', function(){
                        $('#shipment-number').focus();
                    });
                } else {
                    // Fallback: si Select2 no esta disponible, mostrar el select nativo
                    $('#shipment-branch').css({ display: 'block', width: '100%' });
                }
            }

            // Intentar inmediatamente
            initTransferSelect2();

            // Si Select2 aún no cargó (depende de script async), reintentar
            if ( typeof $.fn.select2 === 'undefined' ) {
                var retries = 0;
                var retryInterval = setInterval(function(){
                    retries++;
                    if ( typeof $.fn.select2 !== 'undefined' ) {
                        initTransferSelect2();
                        clearInterval(retryInterval);
                    }
                    if ( retries >= 20 ) {
                        // fallback definitivo: mostrar select nativo
                        $('#shipment-branch').css({ display: 'block', width: '100%' });
                        clearInterval(retryInterval);
                    }
                }, 150);
            }
        });
        </script>
        <?php
    }

    /* ─────────────────────────────────────────────────────────
       ESTILOS INLINE
    ───────────────────────────────────────────────────────── */

    private function render_estilos(): void { ?>
        <style>
        /* ── Wrapper ── */
        .wpcbm-wrap { padding: 0; }
        .wpcbm-topbar { display:flex; align-items:center; justify-content:flex-end; margin-bottom:8px; }

        /* ── Tabs ── */
        .wpcbm-wrap .nav-pills .nav-link {
            border-radius:6px; font-size:13px; color:#555; padding:7px 14px;
            transition:background .15s,color .15s;
        }
        .wpcbm-wrap .nav-pills .nav-link:hover  { background:#e8f4fb; color:#1a7eb8; }
        .wpcbm-wrap .nav-pills .nav-link.active { background:#1a9bcf; color:#fff; }
        .wpcbm-wrap hr { border-color:#e5e5e5; }

        /* ── Tabla de sucursales: cabecera celeste ── */
        .wpcbm-table-wrap { overflow-x:auto; }
        .wpcbm-table-wrap .wpcargo-table { width:100%; border-collapse:collapse; font-size:13px; }

        /* Pisar tanto el CSS del plugin como el tema de WPCargo */
        .wpcbm-table-wrap .wpcargo-table thead th,
        .wpcbm-table-wrap .wpcargo-table th,
        .wpcbm-table-wrap .branch-manager-list th {
            background-color:#1a9bcf !important;
            color:#fff !important;
            padding:10px 14px !important;
            text-align:left !important;
            border:1px solid #1588b5 !important;
            white-space:nowrap;
            line-height:1.5 !important;
        }
        .wpcbm-table-wrap .wpcargo-table td {
            padding:9px 14px; border-bottom:1px solid #eef2f5; vertical-align:middle; font-size:13px;
        }
        .wpcbm-table-wrap .wpcargo-table tr:last-child td { border-bottom:none; }
        .wpcbm-table-wrap .wpcargo-table tbody tr:hover { background:#f0f8fd; }
        .wpcbm-table-wrap .action { display:flex; gap:6px; }
        .wpcbm-table-wrap .action a { text-decoration:none; color:#555; font-size:16px; }
        .wpcbm-table-wrap .action a:hover { color:#1a9bcf; }
        .wpcbm-table-wrap .action a.delete:hover { color:#c0392b; }

        /* ── Configuración de Restricciones ── */
        .wpcbm-restriction-wrap .postbox {
            border:1px solid #d0e8f5; border-radius:8px;
            background:#fff; margin-bottom:18px;
            box-shadow:0 1px 4px rgba(26,155,207,.08);
        }
        .wpcbm-restriction-wrap .postbox .inside { padding:18px 22px; }
        .wpcbm-restriction-wrap .postbox h3.hndle {
            font-size:14px; font-weight:700; margin:0 0 14px; padding-bottom:12px;
            border-bottom:2px solid #1a9bcf; color:#1a7eb8;
        }
        /* Forzar visibilidad de checkboxes (el tema de WPCargo los oculta) */
        .wpcbm-restriction-wrap input[type="checkbox"],
        #wpcbranch-restriction input[type="checkbox"] {
            display:inline-block !important;
            visibility:visible !important;
            opacity:1 !important;
            width:16px !important; height:16px !important;
            margin:0 8px 0 0 !important;
            vertical-align:middle !important;
            position:relative !important;
            float:none !important;
            accent-color:#1a9bcf;
            cursor:pointer;
            flex-shrink:0;
        }
        .wpcbm-restriction-wrap .postbox p {
            margin:8px 0; display:flex; align-items:center; flex-wrap:nowrap; font-size:13px; color:#333; gap:0;
        }
        .wpcbm-restriction-wrap .postbox p label { cursor:pointer; }
        .wpcbm-restriction-wrap .postbox p.description {
            color:#777; font-size:12px; margin-top:10px; font-style:italic; display:block;
        }

        /* ── Modales: diseño limpio estilo WPCargo dashboard ── */
        .wpcbm-wrap .modal {
            position:fixed !important; z-index:99999 !important; left:0 !important; top:0 !important;
            width:100% !important; height:100% !important; overflow-y:auto !important;
            background:rgba(0,0,0,.55) !important;
        }
        .wpcbm-wrap .modal .modal-content {
            background:#fff; margin:4% auto;
            border-radius:10px; width:94%; max-width:560px;
            box-shadow:0 8px 40px rgba(0,0,0,.25);
            padding:0 !important;
        }
        .wpcbm-wrap .modal .header {
            background:linear-gradient(135deg,#1a9bcf,#1565a8);
            padding:16px 22px; border-radius:10px 10px 0 0;
            border-bottom:none;
        }
        .wpcbm-wrap .modal .header h1 {
            font-size:16px; margin:0; color:#fff; font-weight:700;
            display:flex; justify-content:space-between; align-items:center;
        }
        .wpcbm-wrap .modal .header .close {
            cursor:pointer; font-size:24px; line-height:1; color:rgba(255,255,255,.8);
            font-style:normal; transition:color .15s;
        }
        .wpcbm-wrap .modal .header .close:hover { color:#fff; }
        .wpcbm-wrap .modal .content { padding:22px 26px 26px; }

        /* Formulario interno del modal */
        .add-branch-table { width:100%; border-collapse:collapse; }
        .add-branch-table tr td { padding:7px 4px; vertical-align:middle; }
        .add-branch-table tr td:first-child {
            width:36%; font-weight:600; font-size:13px; color:#444; padding-right:10px;
        }
        .add-branch-table input[type=text] {
            width:100%; box-sizing:border-box;
            border:1px solid #cdd8e0; border-radius:5px; padding:7px 10px;
            font-size:13px; transition:border-color .2s;
        }
        .add-branch-table input[type=text]:focus {
            outline:none; border-color:#1a9bcf; box-shadow:0 0 0 3px rgba(26,155,207,.15);
        }

        /* Forzar select nativo de colaboradores en modales */
        .wpcbm-wrap .modal select.select-bm {
            display:block !important;
            visibility:visible !important;
            opacity:1 !important;
            width:100% !important;
        }

        /* Botón submit del modal */
        .wpcbm-btn-submit,
        .add-branch-table .wpcbm-btn-submit {
            display:inline-block; margin-top:10px;
            background:#1a9bcf; color:#fff !important;
            border:none; border-radius:6px;
            padding:9px 24px; font-size:14px; font-weight:600;
            cursor:pointer; transition:background .2s, transform .1s;
            text-decoration:none;
        }
        .wpcbm-btn-submit:hover { background:#1588b5; color:#fff !important; transform:translateY(-1px); }
        .wpcbm-btn-submit:active { transform:translateY(0); }

        /* Select2 en modales */
        .wpcbm-wrap .select2-container { width:100% !important; }
        .wpcbm-wrap .select2-container--default .select2-selection--multiple {
            border:1px solid #cdd8e0; border-radius:5px; min-height:36px;
        }
        .wpcbm-wrap .select2-container--default.select2-container--focus
            .select2-selection--multiple {
            border-color:#1a9bcf; box-shadow:0 0 0 3px rgba(26,155,207,.15);
        }

        /* ── Tab Transferir ── */
        .wpcbm-wrap .postbox { border:1px solid #d0e8f5; border-radius:8px; background:#fff; }
        .wpcbm-wrap .postbox .inside { padding:22px; }
        #transfer-shipment-branch { width:100%; border-collapse:collapse; }
        #transfer-shipment-branch th {
            padding:8px 0; font-weight:700; font-size:13px; color:#1a7eb8;
        }
        #transfer-shipment-branch td { padding:8px 10px 8px 0; }
        #transfer-shipment-branch #shipment-number {
            width:100%; padding:8px 12px;
            border:1px solid #cdd8e0; border-radius:5px;
            font-size:13px; box-sizing:border-box; transition:border-color .2s;
        }
        #transfer-shipment-branch #shipment-number:focus {
            outline:none; border-color:#1a9bcf; box-shadow:0 0 0 3px rgba(26,155,207,.15);
        }
        /* Select2 para sucursal en transfer */
        #transfer-shipment-branch .select2-container { width:100% !important; min-width:200px; }
        /* Evitar que el tema oculte el <select> nativo antes de que Select2 lo reemplace */
        #transfer-shipment-branch #shipment-branch:not(.select2-hidden-accessible) {
            display:block !important;
            visibility:visible !important;
            opacity:1 !important;
            width:100% !important;
        }

        /* Mensajes de resultado */
        .transfer-message {
            position:fixed; top:20px; right:20px; z-index:99999;
            padding:12px 20px; border-radius:8px; font-weight:600; font-size:13px;
            box-shadow:0 4px 16px rgba(0,0,0,.15);
        }
        .transfer-message.success { background:#d4edda; color:#155724; border:1px solid #b8dfc4; }
        .transfer-message.error   { background:#f8d7da; color:#721c24; border:1px solid #f1b2b8; }
        </style>
    <?php }
}

new WPC_Branch_Frontend();
