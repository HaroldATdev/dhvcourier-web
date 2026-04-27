<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Guardar configuración
if ( isset( $_POST['wpcfact_save_config'] ) && check_admin_referer( 'wpcfact_config_nonce' ) ) {
	update_option( 'wpcfact_ruc_emisor',          sanitize_text_field( $_POST['wpcfact_ruc_emisor'] ?? '' ) );
	update_option( 'wpcfact_razon_social_emisor',  sanitize_text_field( $_POST['wpcfact_razon_social_emisor'] ?? '' ) );
	update_option( 'wpcfact_direccion_emisor',     sanitize_text_field( $_POST['wpcfact_direccion_emisor'] ?? '' ) );
	update_option( 'wpcfact_codigo_local',         sanitize_text_field( $_POST['wpcfact_codigo_local'] ?? '0000' ) );
	update_option( 'wpcfact_persona_id',           sanitize_text_field( $_POST['wpcfact_persona_id'] ?? '' ) );
	update_option( 'wpcfact_persona_token',        sanitize_text_field( $_POST['wpcfact_persona_token'] ?? '' ) );
	update_option( 'wpcfact_ambiente',             sanitize_text_field( $_POST['wpcfact_ambiente'] ?? 'DEV' ) );
	update_option( 'wpcfact_serie_factura',        sanitize_text_field( $_POST['wpcfact_serie_factura'] ?? 'F001' ) );
	update_option( 'wpcfact_serie_boleta',         sanitize_text_field( $_POST['wpcfact_serie_boleta'] ?? 'B001' ) );
	echo '<div class="notice notice-success is-dismissible"><p><strong>✅ Configuración guardada correctamente.</strong></p></div>';
}

$ruc            = get_option( 'wpcfact_ruc_emisor', '' );
$razon_social   = get_option( 'wpcfact_razon_social_emisor', '' );
$direccion      = get_option( 'wpcfact_direccion_emisor', '' );
$codigo_local   = get_option( 'wpcfact_codigo_local', '0000' );
$persona_id     = get_option( 'wpcfact_persona_id', '' );
$persona_token  = get_option( 'wpcfact_persona_token', '' );
$ambiente       = get_option( 'wpcfact_ambiente', 'DEV' );
$serie_factura  = get_option( 'wpcfact_serie_factura', 'F001' );
$serie_boleta   = get_option( 'wpcfact_serie_boleta', 'B001' );

$configured = ! empty( $persona_id ) && ! empty( $persona_token ) && ! empty( $ruc );
?>

<div class="wrap">
<h1 class="wp-heading-inline">⚙️ Configuración SUNAT / APISUNAT</h1>
<hr class="wp-header-end">

<!-- Estado de configuración -->
<div style="margin: 20px 0; padding: 15px; border-radius: 8px; border-left: 5px solid <?php echo $configured ? '#10b981' : '#f59e0b'; ?>; background: <?php echo $configured ? '#f0fdf4' : '#fffbeb'; ?>;">
	<?php if ( $configured ) : ?>
		<strong style="color:#10b981;">✅ Sistema configurado y listo para emitir comprobantes.</strong>
		<span style="margin-left:15px; background:<?php echo $ambiente === 'PROD' ? '#10b981' : '#f59e0b'; ?>; color:#fff; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:bold;">
			<?php echo esc_html( $ambiente === 'PROD' ? '🟢 PRODUCCIÓN' : '🟡 DESARROLLO' ); ?>
		</span>
	<?php else : ?>
		<strong style="color:#b45309;">⚠️ Faltan credenciales. Complete la configuración para poder emitir comprobantes.</strong>
	<?php endif; ?>
</div>

<form method="post" action="">
	<?php wp_nonce_field( 'wpcfact_config_nonce' ); ?>

	<!-- SECCIÓN 1: DATOS DEL EMISOR -->
	<div style="background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:25px; margin-bottom:20px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
		<h2 style="margin-top:0; color:#1e293b; font-size:16px; border-bottom:2px solid #f1f5f9; padding-bottom:10px;">
			🏢 Datos del Emisor (Tu Empresa)
		</h2>

		<table class="form-table" style="margin:0;">
			<tr>
				<th style="width:220px;"><label for="wpcfact_ruc_emisor">RUC del Emisor <span style="color:red;">*</span></label></th>
				<td>
					<input type="text" id="wpcfact_ruc_emisor" name="wpcfact_ruc_emisor"
						value="<?php echo esc_attr( $ruc ); ?>"
						class="regular-text" maxlength="11" placeholder="20XXXXXXXXX"
						style="font-size:15px; padding:8px;">
					<p class="description">RUC de 11 dígitos de la empresa emisora.</p>
				</td>
			</tr>
			<tr>
				<th><label for="wpcfact_razon_social_emisor">Razón Social <span style="color:red;">*</span></label></th>
				<td>
					<input type="text" id="wpcfact_razon_social_emisor" name="wpcfact_razon_social_emisor"
						value="<?php echo esc_attr( $razon_social ); ?>"
						class="large-text" placeholder="DHV COURIER S.A.C."
						style="font-size:15px; padding:8px;">
				</td>
			</tr>
			<tr>
				<th style="width:220px;"><label for="wpcfact_direccion_emisor">Dirección Fiscal</label></th>
				<td>
					<input type="text" id="wpcfact_direccion_emisor" name="wpcfact_direccion_emisor"
						value="<?php echo esc_attr( $direccion ); ?>"
						class="large-text" placeholder="Av. Ejemplo 123, Lima, Lima"
						style="font-size:15px; padding:8px;">
				</td>
			</tr>
			<tr>
				<th><label for="wpcfact_codigo_local">Código de Local Anexo <span style="color:red;">*</span></label></th>
				<td>
					<input type="text" id="wpcfact_codigo_local" name="wpcfact_codigo_local"
						value="<?php echo esc_attr( $codigo_local ); ?>"
						style="width:100px; font-size:15px; padding:8px;" maxlength="4" placeholder="0000">
					<p class="description">Código de 4 dígitos del local registrado en SUNAT. Usa <strong>0000</strong> para la sede principal. Si tienes locales adicionales, usa el código asignado por SUNAT (ej: 0001).</p>
				</td>
			</tr>
		</table>
	</div>

	<!-- SECCIÓN 2: SERIES -->
	<div style="background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:25px; margin-bottom:20px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
		<h2 style="margin-top:0; color:#1e293b; font-size:16px; border-bottom:2px solid #f1f5f9; padding-bottom:10px;">
			📄 Series de Comprobantes
		</h2>
		<table class="form-table" style="margin:0;">
			<tr>
				<th style="width:220px;"><label for="wpcfact_serie_factura">Serie Factura Electrónica</label></th>
				<td>
					<input type="text" id="wpcfact_serie_factura" name="wpcfact_serie_factura"
						value="<?php echo esc_attr( $serie_factura ); ?>"
						style="width:100px; font-size:15px; padding:8px;" maxlength="4" placeholder="F001">
					<p class="description">Debe empezar con "F". Ejemplo: F001</p>
				</td>
			</tr>
			<tr>
				<th><label for="wpcfact_serie_boleta">Serie Boleta Electrónica</label></th>
				<td>
					<input type="text" id="wpcfact_serie_boleta" name="wpcfact_serie_boleta"
						value="<?php echo esc_attr( $serie_boleta ); ?>"
						style="width:100px; font-size:15px; padding:8px;" maxlength="4" placeholder="B001">
					<p class="description">Debe empezar con "B". Ejemplo: B001</p>
				</td>
			</tr>
		</table>
	</div>

	<!-- SECCIÓN 3: APISUNAT CREDENTIALS -->
	<div style="background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:25px; margin-bottom:20px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
		<h2 style="margin-top:0; color:#1e293b; font-size:16px; border-bottom:2px solid #f1f5f9; padding-bottom:10px;">
			🔑 Credenciales APISUNAT
		</h2>
		<p style="color:#475569; margin-bottom:20px;">
			Obtén tus credenciales en <a href="https://apisunat.com" target="_blank">apisunat.com</a> →
			Mi Cuenta → API Credentials.
		</p>
		<table class="form-table" style="margin:0;">
			<tr>
				<th style="width:220px;"><label for="wpcfact_persona_id">Persona ID <span style="color:red;">*</span></label></th>
				<td>
					<input type="text" id="wpcfact_persona_id" name="wpcfact_persona_id"
						value="<?php echo esc_attr( $persona_id ); ?>"
						class="large-text" placeholder="Tu Persona ID de APISUNAT"
						style="font-size:15px; padding:8px;">
				</td>
			</tr>
			<tr>
				<th><label for="wpcfact_persona_token">Persona Token <span style="color:red;">*</span></label></th>
				<td>
					<input type="password" id="wpcfact_persona_token" name="wpcfact_persona_token"
						value="<?php echo esc_attr( $persona_token ); ?>"
						class="large-text" placeholder="Tu Token secreto de APISUNAT"
						style="font-size:15px; padding:8px;">
					<button type="button" onclick="
						var f = document.getElementById('wpcfact_persona_token');
						f.type = f.type === 'password' ? 'text' : 'password';
						this.textContent = f.type === 'password' ? 'Mostrar' : 'Ocultar';
					" style="margin-left:8px; padding:6px 12px; cursor:pointer;">Mostrar</button>
				</td>
			</tr>
			<tr>
				<th><label for="wpcfact_ambiente">Ambiente</label></th>
				<td>
					<select id="wpcfact_ambiente" name="wpcfact_ambiente" style="font-size:15px; padding:8px;">
						<option value="DEV" <?php selected( $ambiente, 'DEV' ); ?>>🟡 Desarrollo (Testing)</option>
						<option value="PROD" <?php selected( $ambiente, 'PROD' ); ?>>🟢 Producción (SUNAT real)</option>
					</select>
					<p class="description" style="color:#b45309;">
						⚠️ Usa <strong>Desarrollo</strong> para pruebas. Cambia a <strong>Producción</strong> solo cuando estés listo.
					</p>
				</td>
			</tr>
		</table>
	</div>

	<p class="submit">
		<button type="submit" name="wpcfact_save_config" class="button button-primary button-large">
			💾 Guardar Configuración
		</button>
	</p>
</form>
</div>
