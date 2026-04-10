<?php if ( ! defined( 'ABSPATH' ) ) exit;
$es_edicion = ! is_null( $transportista );

// Valores iniciales (respeta flash si hubo error)
$val = [
	'nombres'  => $es_edicion ? $transportista->nombres             : ( $prev['nombres']   ?? '' ),
	'apellidos'=> $es_edicion ? $transportista->apellidos           : ( $prev['apellidos'] ?? '' ),
	'dni'      => $es_edicion ? $transportista->dni                 : ( $prev['dni']       ?? '' ),
	'brevete'  => $es_edicion ? $transportista->brevete             : ( $prev['brevete']   ?? '' ),
	'telefono' => $es_edicion ? ( $transportista->telefono  ?? '' ) : ( $prev['telefono']  ?? '' ),
	'email'    => $es_edicion ? ( $transportista->email     ?? '' ) : ( $prev['email']     ?? '' ),
];
if ( ! empty( $prev ) ) {
	$val['nombres']   = $prev['nombres']   ?? $val['nombres'];
	$val['apellidos'] = $prev['apellidos'] ?? $val['apellidos'];
	$val['dni']       = $prev['dni']       ?? $val['dni'];
	$val['brevete']   = $prev['brevete']   ?? $val['brevete'];
	$val['telefono']  = $prev['telefono']  ?? $val['telefono'];
	$val['email']     = $prev['email']     ?? $val['email'];
}
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
	<h5 style="margin:0;font-weight:700">
		Editar Transportista
		<?php if ( $es_edicion && ! empty( $transportista->user_id ) ) : ?>
			<span style="font-size:.8rem;color:#2271b1;font-weight:400;margin-left:8px">
				<i class="fa fa-link"></i> Usuario Driver vinculado
			</span>
		<?php endif; ?>
	</h5>
	<a href="<?php echo esc_url( $page_url ); ?>" class="btn btn-outline-secondary btn-sm">
		<i class="fa fa-arrow-left" style="margin-right:4px"></i> Volver
	</a>
</div>

<?php if ( ! empty( $error ) ) : ?>
	<div style="background:#f8d7da;border:1px solid #f5c6cb;border-radius:6px;padding:10px 14px;margin-bottom:14px;color:#721c24">
		<strong>Error:</strong> <?php echo esc_html( $error ); ?>
	</div>
<?php endif; ?>

<?php if ( $es_edicion && ! empty( $transportista->user_id ) ) : ?>
<div style="background:#d1ecf1;border:1px solid #bee5eb;border-radius:6px;padding:10px 14px;margin-bottom:14px;font-size:.875rem;color:#0c5460">
	<i class="fa fa-info-circle"></i>
	Los cambios se sincronizarán automáticamente con el perfil del usuario WPDriver asociado.
</div>
<?php endif; ?>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<?php wp_nonce_field( 'wpcc_fe_nonce' ); ?>
	<input type="hidden" name="action" value="wpcc_guardar_fe">
	<input type="hidden" name="id"     value="<?php echo (int) $transportista->id; ?>">

	<div style="background:#fff;border:1px solid #dee2e6;border-radius:6px;padding:20px;margin-bottom:14px">

		<!-- Nombres -->
		<div style="margin-bottom:14px">
			<div style="font-weight:600;margin-bottom:5px;font-size:.875rem">
				Nombres <span style="color:#dc3545">*</span>
			</div>
			<input type="text" name="nombres" required class="browser-default"
			       style="width:100%;max-width:320px;height:34px;border:1px solid #ced4da;border-radius:4px;padding:4px 10px;font-size:.875rem"
			       placeholder="Primer y segundo nombre"
			       value="<?php echo esc_attr( $val['nombres'] ); ?>">
		</div>

		<!-- Apellidos -->
		<div style="margin-bottom:14px">
			<div style="font-weight:600;margin-bottom:5px;font-size:.875rem">
				Apellidos <span style="color:#dc3545">*</span>
			</div>
			<input type="text" name="apellidos" required class="browser-default"
			       style="width:100%;max-width:320px;height:34px;border:1px solid #ced4da;border-radius:4px;padding:4px 10px;font-size:.875rem"
			       placeholder="Apellido paterno y materno"
			       value="<?php echo esc_attr( $val['apellidos'] ); ?>">
		</div>

		<!-- DNI -->
		<div style="margin-bottom:14px">
			<div style="font-weight:600;margin-bottom:5px;font-size:.875rem">
				DNI <span style="color:#dc3545">*</span>
			</div>
			<input type="text" name="dni" required class="browser-default"
			       maxlength="8" pattern="[0-9]{8}" inputmode="numeric"
			       oninput="this.value=this.value.replace(/\D/g,'')"
			       style="width:100%;max-width:160px;height:34px;border:1px solid #ced4da;border-radius:4px;padding:4px 10px;font-size:.875rem"
			       value="<?php echo esc_attr( $val['dni'] ); ?>">
			<div style="font-size:.8rem;color:#888;margin-top:3px">8 dígitos numéricos. Único.</div>
		</div>

		<!-- Brevete -->
		<div style="margin-bottom:14px">
			<div style="font-weight:600;margin-bottom:5px;font-size:.875rem">
				Brevete <span style="color:#dc3545">*</span>
			</div>
			<input type="text" name="brevete" required class="browser-default"
			       maxlength="12"
			       oninput="this.value=this.value.toUpperCase()"
			       placeholder="Ej: A-2345"
			       style="width:100%;max-width:180px;height:34px;border:1px solid #ced4da;border-radius:4px;padding:4px 10px;font-size:.875rem;text-transform:uppercase"
			       value="<?php echo esc_attr( $val['brevete'] ); ?>">
			<div style="font-size:.8rem;color:#888;margin-top:3px">Formato: A-2345 o Q3-12345.</div>
		</div>

		<!-- Teléfono -->
		<div style="margin-bottom:14px">
			<div style="font-weight:600;margin-bottom:5px;font-size:.875rem">Teléfono</div>
			<input type="tel" name="telefono" class="browser-default"
			       maxlength="9" pattern="[0-9]{9}" inputmode="numeric"
			       oninput="this.value=this.value.replace(/\D/g,'')"
			       style="width:100%;max-width:160px;height:34px;border:1px solid #ced4da;border-radius:4px;padding:4px 10px;font-size:.875rem"
			       value="<?php echo esc_attr( $val['telefono'] ); ?>">
			<div style="font-size:.8rem;color:#888;margin-top:3px">9 dígitos numéricos.</div>
		</div>

		<!-- Email -->
		<div style="margin-bottom:4px">
			<div style="font-weight:600;margin-bottom:5px;font-size:.875rem">Email de contacto</div>
			<input type="email" name="email" class="browser-default"
			       style="width:100%;max-width:320px;height:34px;border:1px solid #ced4da;border-radius:4px;padding:4px 10px;font-size:.875rem"
			       value="<?php echo esc_attr( $val['email'] ); ?>">
		</div>

	</div>

	<button type="submit" class="btn btn-primary btn-sm" style="margin-right:8px">
		<i class="fa fa-save" style="margin-right:4px"></i> Actualizar Transportista
	</button>
	<a href="<?php echo esc_url( $page_url ); ?>" class="btn btn-outline-secondary btn-sm">Cancelar</a>
</form>
