<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPCV_Frontend {

	public function __construct() {
		add_shortcode( 'wpcv-viaticos',                  [ $this, 'render_shortcode' ] );
		add_filter( 'wpcfe_after_sidebar_menus',         [ $this, 'sidebar_item' ], 25, 1 );
		add_action( 'wp_enqueue_scripts',                [ $this, 'encolar_assets' ], 100 );
		add_action( 'admin_post_wpcv_guardar_fe',        [ $this, 'handle_guardar' ] );
		add_action( 'admin_post_wpcv_cerrar_fe',         [ $this, 'handle_cerrar' ] );
		add_action( 'admin_post_wpcv_ampliar_fe',        [ $this, 'handle_ampliar' ] );
		add_action( 'admin_post_wpcv_gasto_guardar_fe',  [ $this, 'handle_gasto_guardar' ] );
		add_action( 'admin_post_wpcv_gasto_eliminar_fe', [ $this, 'handle_gasto_eliminar' ] );
		add_action( 'admin_post_wpcv_export_csv',        [ $this, 'handle_export_csv' ] );

		// Para usuarios sin acceso a wp-admin (drivers): procesar formularios via init
		add_action( 'init', [ $this, 'handle_frontend_post' ], 5 );
	}

	public function sidebar_item( array $menu ): array {
		if ( ! wpcv_es_admin() && ! wpcv_es_driver() ) return $menu;
		$menu['wpcv-menu'] = [
			'page-id'   => wpcv_get_frontend_page_id(),
			'label'     => __( 'Viáticos', 'wp-cargo-viaticos' ),
			'permalink' => wpcv_frontend_url(),
			'icon'      => 'fa-money',
		];
		return $menu;
	}

	public function encolar_assets(): void {
		if ( (int) get_queried_object_id() !== wpcv_get_frontend_page_id() ) return;
		wp_enqueue_style( 'wpcv-frontend', WPCV_URL . 'admin/assets/css/frontend.css', [], WPCV_VERSION );
	}

	public function render_shortcode(): string {
		// Admins: acceso total. Drivers: vista restringida. Otros: sin acceso.
		if ( ! wpcv_es_admin() && ! wpcv_es_driver() ) {
			return '<p>' . esc_html__( 'Acceso restringido.', 'wp-cargo-viaticos' ) . '</p>';
		}
		ob_start();
		$action = sanitize_key( $_GET['wpcv'] ?? '' );
		if ( $action === 'gastos' || $action === 'gasto_add' ) {
			$this->render_gastos();
		} elseif ( in_array( $action, [ 'add', 'edit' ], true ) && wpcv_es_admin() ) {
			// Solo admin puede crear/editar viáticos
			$this->render_form();
		} elseif ( $action === 'historial' ) {
			$this->render_historial();
		} elseif ( $action === 'reportes' && wpcv_es_admin() ) {
			$this->render_reportes();
		} else {
			$this->render_list();
		}
		return ob_get_clean();
	}

	private function render_list(): void {
		$es_driver        = ! wpcv_es_admin();
		$driver_tid       = wpcv_driver_transportista_id();
		$transportista_id = $es_driver ? $driver_tid : (int) ( $_GET['transportista_id'] ?? 0 );
		$estado           = sanitize_text_field( wp_unslash( $_GET['estado'] ?? '' ) );
		$mensaje          = sanitize_key( $_GET['msg'] ?? '' );
		$viaticos         = WPCV_Viatico::obtener_todos( compact( 'transportista_id', 'estado' ) );
		$transportistas   = wpcv_es_admin() ? WPCV_Viatico::obtener_transportistas() : [];
		$page_url         = wpcv_frontend_url();
		wpcv_include_template( 'frontend/list.tpl.php', compact( 'viaticos', 'transportistas', 'transportista_id', 'estado', 'mensaje', 'page_url', 'es_driver' ) );
	}

	private function render_form(): void {
		$id             = (int) ( $_GET['id'] ?? 0 );
		$viatico        = $id ? WPCV_Viatico::obtener_por_id( $id ) : null;
		$transportistas = WPCV_Viatico::obtener_transportistas();
		$page_url       = wpcv_frontend_url();
		$uid            = get_current_user_id();
		$flash          = get_transient( 'wpcv_flash_' . wp_get_session_token() );
		if ( $flash ) delete_transient( 'wpcv_flash_' . wp_get_session_token() );
		$error = $flash['error'] ?? '';
		$prev  = $flash['prev']  ?? null;
		wpcv_include_template( 'frontend/form.tpl.php', compact( 'id', 'viatico', 'transportistas', 'error', 'prev', 'page_url' ) );
	}

	private function render_gastos(): void {
		$viatico_id = (int) ( $_GET['viatico_id'] ?? 0 );
		$viatico    = $viatico_id ? WPCV_Viatico::obtener_por_id( $viatico_id ) : null;
		$page_url   = wpcv_frontend_url();
		if ( ! $viatico ) { echo '<div class="alert alert-danger">Viático no encontrado.</div>'; return; }
		// Driver solo puede ver sus propios viáticos
		if ( ! wpcv_es_admin() ) {
			$driver_tid = wpcv_driver_transportista_id();
			if ( (int) $viatico->transportista_id !== $driver_tid ) {
				echo '<div class="alert alert-danger">Sin acceso a este viático.</div>'; return;
			}
		}

		$transportista_nombre = 'ID #' . $viatico->transportista_id;
		foreach ( WPCV_Viatico::obtener_transportistas() as $t ) {
			if ( (int) $t->id === (int) $viatico->transportista_id ) { $transportista_nombre = trim( ( $t->nombres ?? '' ) . ' ' . ( $t->apellidos ?? '' ) ) ?: $t->nombre; break; }
		}

		$action    = sanitize_key( $_GET['wpcv'] ?? '' );
		$msg_gasto = sanitize_key( $_GET['msg']  ?? '' );

		if ( $action === 'gasto_add' ) {
			$uid   = get_current_user_id();
			$flash = get_transient( 'wpcv_gasto_flash_' . wp_get_session_token() );
			if ( $flash ) delete_transient( 'wpcv_gasto_flash_' . wp_get_session_token() );
			$error = $flash['error'] ?? '';
			wpcv_include_template( 'frontend/gastos/form.tpl.php', compact( 'viatico', 'error', 'page_url' ) );
		} else {
			$es_driver = ! wpcv_es_admin();
			$gastos = WPCV_Gasto::obtener_por_viatico( $viatico_id );
			wpcv_include_template( 'frontend/gastos/list.tpl.php', compact( 'viatico', 'gastos', 'transportista_nombre', 'msg_gasto', 'page_url', 'es_driver' ) );
		}
	}

	/* ── admin_post handlers (bypasan WPCargo, van directo a WordPress) ── */

	public function handle_guardar(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		check_admin_referer( 'wpcv_fe_nonce' );

		$uid   = get_current_user_id();
		$id    = (int) ( $_POST['id'] ?? 0 );
		$datos = [
			'transportista_id' => (int)   ( $_POST['transportista_id'] ?? 0 ),
			'ruta'             => sanitize_text_field( wp_unslash( $_POST['ruta']             ?? '' ) ),
			'monto_asignado'   => (float)  ( $_POST['monto_asignado'] ?? 0 ),
			'fecha_asignacion' => sanitize_text_field( wp_unslash( $_POST['fecha_asignacion'] ?? '' ) ),
			'notas'            => sanitize_textarea_field( wp_unslash( $_POST['notas']        ?? '' ) ),
		];

		if ( $id ) {
			$v = WPCV_Viatico::obtener_por_id( $id );
			if ( $v && $v->estado === 'cerrado' ) {
				set_transient( 'wpcv_flash_' . wp_get_session_token(), [ 'error' => 'Este viático está cerrado y no puede editarse.' ], 60 );
				wp_safe_redirect( wpcv_frontend_url( [ 'wpcv' => 'edit', 'id' => $id ] ) );
				exit;
			}
		}

		$result = $id ? WPCV_Viatico::actualizar( $id, $datos ) : WPCV_Viatico::crear( $datos );

		if ( is_wp_error( $result ) ) {
			set_transient( 'wpcv_flash_' . wp_get_session_token(), [ 'error' => $result->get_error_message(), 'prev' => $datos ], 60 );
			$params = [ 'wpcv' => $id ? 'edit' : 'add' ];
			if ( $id ) $params['id'] = $id;
			wp_safe_redirect( wpcv_frontend_url( $params ) );
		} else {
			wp_safe_redirect( wpcv_frontend_url( [ 'msg' => $id ? 'actualizado' : 'guardado' ] ) );
		}
		exit;
	}

	public function handle_cerrar(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		check_admin_referer( 'wpcv_fe_cerrar_nonce' );
		WPCV_Viatico::cerrar( (int) ( $_POST['id'] ?? 0 ) );
		wp_safe_redirect( wpcv_frontend_url( [ 'msg' => 'cerrado' ] ) );
		exit;
	}

	public function handle_gasto_guardar(): void {
		if ( ! wpcv_es_admin() && ! wpcv_es_driver() ) wp_die( 'Sin permisos.' );
		// Driver: verificar que el viático le pertenece
		if ( ! wpcv_es_admin() ) {
			$vid = (int) ( $_POST['viatico_id'] ?? 0 );
			$v   = $vid ? WPCV_Viatico::obtener_por_id( $vid ) : null;
			if ( ! $v || (int) $v->transportista_id !== wpcv_driver_transportista_id() ) wp_die( 'Sin permisos.' );
		}
		check_admin_referer( 'wpcv_fe_gasto_nonce' );
		$uid        = get_current_user_id();
		$viatico_id = (int) ( $_POST['viatico_id'] ?? 0 );
		$datos      = [
			'tipo'        => sanitize_text_field( wp_unslash( $_POST['tipo']        ?? '' ) ),
			'monto'       => $_POST['monto'] ?? 0,
			'descripcion' => sanitize_text_field( wp_unslash( $_POST['descripcion'] ?? '' ) ),
		];
		$archivo = $_FILES['sustento'] ?? null;
		$result  = WPCV_Gasto::crear( $viatico_id, $datos, $archivo );

		if ( is_wp_error( $result ) ) {
			set_transient( 'wpcv_gasto_flash_' . wp_get_session_token(), [ 'error' => $result->get_error_message() ], 60 );
			wp_safe_redirect( wpcv_frontend_url( [ 'wpcv' => 'gasto_add', 'viatico_id' => $viatico_id ] ) );
		} else {
			wp_safe_redirect( wpcv_frontend_url( [ 'wpcv' => 'gastos', 'viatico_id' => $viatico_id, 'msg' => 'guardado' ] ) );
		}
		exit;
	}

	public function handle_gasto_eliminar(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		check_admin_referer( 'wpcv_fe_del_gasto' );
		$viatico_id = (int) ( $_POST['viatico_id'] ?? 0 );
		WPCV_Gasto::eliminar( (int) ( $_POST['gasto_id'] ?? 0 ) );
		wp_safe_redirect( wpcv_frontend_url( [ 'wpcv' => 'gastos', 'viatico_id' => $viatico_id, 'msg' => 'eliminado' ] ) );
		exit;
	}

	public function handle_ampliar(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		check_admin_referer( 'wpcv_fe_ampliar_nonce' );
		$id        = (int)   ( $_POST['id']        ?? 0 );
		$adicional = (float) ( $_POST['adicional'] ?? 0 );
		$result    = WPCV_Viatico::ampliar( $id, $adicional );
		if ( is_wp_error( $result ) ) {
			$token = wp_get_session_token();
			set_transient( 'wpcv_flash_' . $token, [ 'error' => $result->get_error_message() ], 60 );
		}
		wp_safe_redirect( wpcv_frontend_url( [ 'wpcv' => 'edit', 'id' => $id, 'msg' => is_wp_error( $result ) ? '' : 'ampliado' ] ) );
		exit;
	}

	public function handle_export_csv(): void {
		check_admin_referer( 'wpcv_export_csv' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		$filtros = [
			'transportista_id' => (int) ( $_POST['transportista_id'] ?? 0 ),
			'ruta'             => sanitize_text_field( wp_unslash( $_POST['ruta']    ?? '' ) ),
			'estado'           => sanitize_text_field( wp_unslash( $_POST['estado']  ?? '' ) ),
			'periodo'          => sanitize_key( $_POST['periodo'] ?? '' ),
			'desde'            => sanitize_text_field( wp_unslash( $_POST['desde']  ?? '' ) ),
			'hasta'            => sanitize_text_field( wp_unslash( $_POST['hasta']  ?? '' ) ),
		];
		$datos = WPCV_Viatico::reporte( $filtros );

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="viaticos-' . date( 'Y-m-d' ) . '.csv"' );
		$out = fopen( 'php://output', 'w' );
		fprintf( $out, chr(0xEF) . chr(0xBB) . chr(0xBF) ); // UTF-8 BOM para Excel
		fputcsv( $out, [ 'ID', 'Transportista', 'Ruta', 'Fecha', 'Asignado (S/)', 'Ejecutado (S/)', 'Diferencia (S/)', 'Estado' ] );
		foreach ( $datos as $r ) {
			fputcsv( $out, [
				$r->id,
				$r->transportista_nombre ?? '',
				$r->ruta,
				$r->fecha_asignacion,
				number_format( (float) $r->monto_asignado, 2, '.', '' ),
				number_format( (float) $r->monto_usado,    2, '.', '' ),
				number_format( (float) $r->diferencia,     2, '.', '' ),
				$r->estado,
			] );
		}
		fclose( $out );
		exit;
	}

	private function render_historial(): void {
		$es_driver = ! wpcv_es_admin();
		$desde     = sanitize_text_field( wp_unslash( $_GET['desde'] ?? '' ) );
		$hasta     = sanitize_text_field( wp_unslash( $_GET['hasta'] ?? '' ) );
		$estado    = sanitize_text_field( wp_unslash( $_GET['estado'] ?? '' ) );
		$page_url  = wpcv_frontend_url();

		if ( $es_driver ) {
			// Driver: solo su propio historial
			$transportista_id = wpcv_driver_transportista_id();
			$transportistas   = [];
			$historial        = WPCV_Viatico::historial_por_transportista( $transportista_id, compact( 'desde', 'hasta', 'estado' ) );
		} else {
			// Admin: todos los transportistas
			$transportista_id = (int) ( $_GET['transportista_id'] ?? 0 );
			$transportistas   = WPCV_Viatico::obtener_transportistas();
			if ( $transportista_id ) {
				$historial = WPCV_Viatico::historial_por_transportista( $transportista_id, compact( 'desde', 'hasta', 'estado' ) );
			} else {
				$historial = WPCV_Viatico::reporte( [ 'desde' => $desde, 'hasta' => $hasta, 'estado' => $estado ] );
			}
		}
		wpcv_include_template( 'frontend/historial.tpl.php', compact( 'historial', 'transportistas', 'transportista_id', 'desde', 'hasta', 'estado', 'page_url', 'es_driver' ) );
	}

	private function render_reportes(): void {
		$transportista_id = (int) ( $_GET['transportista_id'] ?? 0 );
		$ruta             = sanitize_text_field( wp_unslash( $_GET['ruta']    ?? '' ) );
		$estado           = sanitize_text_field( wp_unslash( $_GET['estado']  ?? '' ) );
		$periodo          = sanitize_key( $_GET['periodo'] ?? '' );
		$desde            = sanitize_text_field( wp_unslash( $_GET['desde']   ?? '' ) );
		$hasta            = sanitize_text_field( wp_unslash( $_GET['hasta']   ?? '' ) );
		$transportistas   = WPCV_Viatico::obtener_transportistas();
		$datos            = WPCV_Viatico::reporte( compact( 'transportista_id', 'ruta', 'estado', 'periodo', 'desde', 'hasta' ) );
		$page_url         = wpcv_frontend_url();
		wpcv_include_template( 'frontend/reportes.tpl.php', compact( 'datos', 'transportistas', 'transportista_id', 'ruta', 'estado', 'periodo', 'desde', 'hasta', 'page_url' ) );
	}
	public function handle_frontend_post(): void {
		if ( empty( $_POST['action'] ) ) return;
		$action = sanitize_key( $_POST['action'] );

		// Solo para drivers (admins ya usan admin_post que funciona bien)
		if ( wpcv_es_admin() ) return;
		if ( ! wpcv_es_driver() ) return;

		if ( $action === 'wpcv_gasto_guardar_fe' ) {
			$this->handle_gasto_guardar();
		}
	}
}

new WPCV_Frontend();

