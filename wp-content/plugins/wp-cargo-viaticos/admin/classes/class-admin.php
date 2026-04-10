<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPCV_Admin {

	public function __construct() {
		add_action( 'admin_menu',                         [ $this, 'registrar_menu' ] );
		add_action( 'admin_post_wpcv_guardar',            [ $this, 'handle_guardar' ] );
		add_action( 'admin_post_wpcv_ampliar',            [ $this, 'handle_ampliar' ] );
		add_action( 'admin_post_wpcv_tipo_agregar',       [ $this, 'handle_tipo_agregar' ] );
		add_action( 'admin_post_wpcv_tipo_eliminar',      [ $this, 'handle_tipo_eliminar' ] );
		add_action( 'admin_post_wpcv_admin_gasto_guardar',[ $this, 'handle_gasto_guardar' ] );
		add_action( 'admin_post_wpcv_admin_gasto_del',    [ $this, 'handle_gasto_eliminar' ] );
		add_action( 'admin_post_wpcv_admin_cerrar',       [ $this, 'handle_cerrar' ] );
		add_action( 'admin_post_wpcv_export_csv',         [ $this, 'handle_export_csv' ] );
	}

	public function registrar_menu(): void {
		add_menu_page( 'Viáticos', 'Viáticos', 'manage_options', 'wp-cargo-viaticos',
			[ $this, 'pagina_listado' ], 'dashicons-money-alt', 33 );
		add_submenu_page( 'wp-cargo-viaticos', 'Viáticos',          'Listado',          'manage_options', 'wp-cargo-viaticos',    [ $this, 'pagina_listado' ] );
		add_submenu_page( 'wp-cargo-viaticos', 'Nuevo Viático',     '+ Nuevo Viático',  'manage_options', 'wpcv-nuevo',           [ $this, 'pagina_form' ] );
		add_submenu_page( 'wp-cargo-viaticos', 'Historial',         'Historial',        'manage_options', 'wpcv-historial',       [ $this, 'pagina_historial' ] );
		add_submenu_page( 'wp-cargo-viaticos', 'Reportes',          'Reportes',         'manage_options', 'wpcv-reportes',        [ $this, 'pagina_reportes' ] );
		add_submenu_page( 'wp-cargo-viaticos', 'Tipos de Gasto',    'Tipos de Gasto',   'manage_options', 'wpcv-tipos',           [ $this, 'pagina_tipos' ] );
	}

	/* ── PÁGINAS ───────────────────────────────────────────────────────── */

	public function pagina_listado(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		$transportista_id = (int) ( $_GET['transportista_id'] ?? 0 );
		$estado           = sanitize_text_field( wp_unslash( $_GET['estado'] ?? '' ) );
		$mensaje          = sanitize_key( $_GET['mensaje'] ?? '' );
		$viaticos         = WPCV_Viatico::obtener_todos( compact( 'transportista_id', 'estado' ) );
		$transportistas   = WPCV_Viatico::obtener_transportistas();
		wpcv_include_template( 'admin/list.tpl.php', compact( 'viaticos', 'transportistas', 'transportista_id', 'estado', 'mensaje' ) );
	}

	public function pagina_form(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		$id      = (int) ( $_GET['id'] ?? 0 );
		$viatico = $id ? WPCV_Viatico::obtener_por_id( $id ) : null;
		$gastos  = $id ? WPCV_Gasto::obtener_por_viatico( $id ) : [];
		$error   = sanitize_text_field( urldecode( $_GET['error'] ?? '' ) );
		$transportistas = WPCV_Viatico::obtener_transportistas();
		wpcv_include_template( 'admin/form.tpl.php', compact( 'id', 'viatico', 'gastos', 'error', 'transportistas' ) );
	}

	public function pagina_historial(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		$transportista_id = (int) ( $_GET['transportista_id'] ?? 0 );
		$desde            = sanitize_text_field( wp_unslash( $_GET['desde'] ?? '' ) );
		$hasta            = sanitize_text_field( wp_unslash( $_GET['hasta'] ?? '' ) );
		$estado           = sanitize_text_field( wp_unslash( $_GET['estado'] ?? '' ) );
		$transportistas   = WPCV_Viatico::obtener_transportistas();
		if ( $transportista_id ) {
			$historial = WPCV_Viatico::historial_por_transportista( $transportista_id, compact( 'desde', 'hasta', 'estado' ) );
		} else {
			// Sin transportista: mostrar todos con filtros de fecha y estado
			$historial = WPCV_Viatico::reporte( [ 'desde' => $desde, 'hasta' => $hasta, 'estado' => $estado ] );
		}
		wpcv_include_template( 'admin/historial.tpl.php', compact( 'historial', 'transportistas', 'transportista_id', 'desde', 'hasta', 'estado' ) );
	}

	public function pagina_reportes(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		$transportista_id = (int) ( $_GET['transportista_id'] ?? 0 );
		$ruta             = sanitize_text_field( wp_unslash( $_GET['ruta']   ?? '' ) );
		$estado           = sanitize_text_field( wp_unslash( $_GET['estado'] ?? '' ) );
		$periodo          = sanitize_key( $_GET['periodo'] ?? '' );
		$desde            = sanitize_text_field( wp_unslash( $_GET['desde'] ?? '' ) );
		$hasta            = sanitize_text_field( wp_unslash( $_GET['hasta'] ?? '' ) );
		$transportistas   = WPCV_Viatico::obtener_transportistas();
		$datos            = WPCV_Viatico::reporte( compact( 'transportista_id', 'ruta', 'estado', 'periodo', 'desde', 'hasta' ) );
		wpcv_include_template( 'admin/reportes.tpl.php', compact( 'datos', 'transportistas', 'transportista_id', 'ruta', 'estado', 'periodo', 'desde', 'hasta' ) );
	}

	public function pagina_tipos(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		$tipos  = WPCV_Tipos_Gasto::obtener();
		$error  = sanitize_text_field( urldecode( $_GET['error'] ?? '' ) );
		$ok     = sanitize_key( $_GET['ok'] ?? '' );
		wpcv_include_template( 'admin/tipos.tpl.php', compact( 'tipos', 'error', 'ok' ) );
	}

	/* ── HANDLERS ──────────────────────────────────────────────────────── */

	public function handle_guardar(): void {
		check_admin_referer( 'wpcv_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		$id    = (int) ( $_POST['id'] ?? 0 );
		$datos = [
			'transportista_id' => (int)   ( $_POST['transportista_id'] ?? 0 ),
			'ruta'             => sanitize_text_field( wp_unslash( $_POST['ruta']             ?? '' ) ),
			'monto_asignado'   => (float)  ( $_POST['monto_asignado'] ?? 0 ),
			'fecha_asignacion' => sanitize_text_field( wp_unslash( $_POST['fecha_asignacion'] ?? '' ) ),
			'notas'            => sanitize_textarea_field( wp_unslash( $_POST['notas']        ?? '' ) ),
		];
		$result = $id ? WPCV_Viatico::actualizar( $id, $datos ) : WPCV_Viatico::crear( $datos );
		if ( is_wp_error( $result ) ) {
			$params = [ 'page' => 'wpcv-nuevo', 'error' => rawurlencode( $result->get_error_message() ) ];
			if ( $id ) $params['id'] = $id;
			wp_redirect( add_query_arg( $params, admin_url( 'admin.php' ) ) );
		} else {
			wp_redirect( add_query_arg( [ 'page' => 'wp-cargo-viaticos', 'mensaje' => $id ? 'actualizado' : 'guardado' ], admin_url( 'admin.php' ) ) );
		}
		exit;
	}

	public function handle_ampliar(): void {
		check_admin_referer( 'wpcv_ampliar_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		$id       = (int)   ( $_POST['id']        ?? 0 );
		$adicional = (float) ( $_POST['adicional'] ?? 0 );
		$result   = WPCV_Viatico::ampliar( $id, $adicional );
		$err = is_wp_error( $result ) ? rawurlencode( $result->get_error_message() ) : '';
		$params = [ 'page' => 'wpcv-nuevo', 'id' => $id, 'mensaje' => $err ? '' : 'ampliado' ];
		if ( $err ) $params['error'] = $err;
		wp_redirect( add_query_arg( $params, admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_cerrar(): void {
		check_admin_referer( 'wpcv_cerrar_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		WPCV_Viatico::cerrar( (int) ( $_POST['id'] ?? 0 ) );
		wp_redirect( add_query_arg( [ 'page' => 'wp-cargo-viaticos', 'mensaje' => 'cerrado' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_tipo_agregar(): void {
		check_admin_referer( 'wpcv_tipo_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		$result = WPCV_Tipos_Gasto::agregar( sanitize_text_field( wp_unslash( $_POST['tipo'] ?? '' ) ) );
		$params = [ 'page' => 'wpcv-tipos' ];
		if ( is_wp_error( $result ) ) $params['error'] = rawurlencode( $result->get_error_message() );
		else $params['ok'] = 'agregado';
		wp_redirect( add_query_arg( $params, admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_tipo_eliminar(): void {
		check_admin_referer( 'wpcv_tipo_del_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		WPCV_Tipos_Gasto::eliminar( sanitize_text_field( wp_unslash( $_POST['tipo'] ?? '' ) ) );
		wp_redirect( add_query_arg( [ 'page' => 'wpcv-tipos', 'ok' => 'eliminado' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_gasto_guardar(): void {
		check_admin_referer( 'wpcv_gasto_admin_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		$viatico_id = (int) ( $_POST['viatico_id'] ?? 0 );
		$datos = [
			'tipo'        => sanitize_text_field( wp_unslash( $_POST['tipo']        ?? '' ) ),
			'monto'       => $_POST['monto'] ?? 0,
			'descripcion' => sanitize_text_field( wp_unslash( $_POST['descripcion'] ?? '' ) ),
		];
		$archivo = $_FILES['sustento'] ?? null;
		$result  = WPCV_Gasto::crear( $viatico_id, $datos, $archivo );
		$params  = [ 'page' => 'wpcv-nuevo', 'id' => $viatico_id ];
		if ( is_wp_error( $result ) ) $params['error'] = rawurlencode( $result->get_error_message() );
		else $params['mensaje'] = 'gasto_registrado';
		wp_redirect( add_query_arg( $params, admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_gasto_eliminar(): void {
		check_admin_referer( 'wpcv_gasto_del_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sin permisos.' );
		$viatico_id = (int) ( $_POST['viatico_id'] ?? 0 );
		WPCV_Gasto::eliminar( (int) ( $_POST['gasto_id'] ?? 0 ) );
		wp_redirect( add_query_arg( [ 'page' => 'wpcv-nuevo', 'id' => $viatico_id, 'mensaje' => 'gasto_eliminado' ], admin_url( 'admin.php' ) ) );
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
		fprintf( $out, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) ); // UTF-8 BOM
		fputcsv( $out, [ 'ID', 'Transportista', 'Ruta', 'Fecha', 'Asignado', 'Gastado', 'Diferencia', 'Estado' ] );
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
}

new WPCV_Admin();
