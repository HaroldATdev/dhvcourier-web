<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px">
	<div>
		<h5 style="margin:0 0 2px 0">Registrar Gasto</h5>
		<small style="color:#888"><?php echo esc_html( $viatico->ruta ); ?></small>
	</div>
	<a href="<?php echo esc_url( wpcv_frontend_url( [ 'wpcv' => 'gastos', 'viatico_id' => $viatico->id ] ) ); ?>"
	   class="btn btn-outline-secondary btn-sm">
		<i class="fa fa-arrow-left" style="margin-right:4px"></i> Volver
	</a>
</div>

<?php $saldo = WPCV_Viatico::saldo( $viatico ); ?>
<div style="background:<?php echo $saldo > 0 ? '#d1ecf1' : '#fff3cd'; ?>;border:1px solid <?php echo $saldo > 0 ? '#bee5eb' : '#ffeeba'; ?>;border-radius:6px;padding:10px 14px;margin-bottom:16px;font-size:.95rem">
	Saldo disponible: <strong><?php echo esc_html( wpcv_monto( $saldo ) ); ?></strong>
</div>

<?php if ( ! empty( $error ) ) : ?>
	<div style="background:#f8d7da;border:1px solid #f5c6cb;border-radius:6px;padding:10px 14px;margin-bottom:16px;color:#721c24">
		<strong>Error:</strong> <?php echo esc_html( $error ); ?>
	</div>
<?php endif; ?>

<?php
// Admin usa admin-post.php; Driver usa la página frontend (no tiene acceso wp-admin)
$form_action = wpcv_es_admin()
	? admin_url( 'admin-post.php' )
	: wpcv_frontend_url();
?>
<form method="post" action="<?php echo esc_url( $form_action ); ?>" enctype="multipart/form-data">
	<?php wp_nonce_field( 'wpcv_fe_gasto_nonce' ); ?>
	<input type="hidden" name="action"     value="wpcv_gasto_guardar_fe">
	<input type="hidden" name="viatico_id" value="<?php echo (int) $viatico->id; ?>">

	<div style="background:#fff;border:1px solid #dee2e6;border-radius:6px;padding:20px;margin-bottom:16px">

		<!-- Tipo de gasto -->
		<div style="margin-bottom:16px">
			<div style="display:block;font-weight:600;margin-bottom:6px;font-size:.875rem;white-space:nowrap">
				Tipo de gasto <span style="color:#dc3545">*</span>
			</div>
			<select name="tipo" required class="browser-default"
			        style="width:100%;max-width:280px;height:34px;border:1px solid #ced4da;border-radius:4px;padding:4px 8px;font-size:.875rem;background:#fff">
				<option value="">— Seleccionar —</option>
				<?php foreach ( WPCV_Gasto::tipos() as $tipo ) : ?>
					<option value="<?php echo esc_attr( $tipo ); ?>"><?php echo esc_html( $tipo ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<!-- Monto -->
		<div style="margin-bottom:16px">
			<div style="display:block;font-weight:600;margin-bottom:6px;font-size:.875rem;white-space:nowrap">
				Monto (S/) <span style="color:#dc3545">*</span>
			</div>
			<input type="number" name="monto" required min="0.01" step="0.01"
			       max="<?php echo esc_attr( number_format( $saldo, 2, '.', '' ) ); ?>"
			       style="width:100%;max-width:180px;height:34px;border:1px solid #ced4da;border-radius:4px;padding:4px 10px;font-size:.875rem">
			<div style="font-size:.8rem;color:#888;margin-top:4px">Máximo: <?php echo esc_html( wpcv_monto( $saldo ) ); ?></div>
		</div>

		<!-- Descripción -->
		<div style="margin-bottom:16px">
			<div style="display:block;font-weight:600;margin-bottom:6px;font-size:.875rem;white-space:nowrap">Descripción</div>
			<input type="text" name="descripcion" placeholder="Ej: Grifo Repsol km 45"
			       style="width:100%;max-width:400px;height:34px;border:1px solid #ced4da;border-radius:4px;padding:4px 10px;font-size:.875rem">
		</div>

		<!-- Sustento -->
		<div style="margin-bottom:4px">
			<div style="display:block;font-weight:600;margin-bottom:6px;font-size:.875rem;white-space:nowrap">
				Sustento (foto / PDF) <span style="color:#dc3545">*</span>
			</div>
			<input type="file" name="sustento" accept="image/*,.pdf" required
			       style="font-size:.875rem">
			<div style="font-size:.8rem;color:#888;margin-top:4px">PNG, JPG o PDF. Máximo 5 MB. Obligatorio.</div>
		</div>

	</div>

	<button type="submit" class="btn btn-primary btn-sm" style="margin-right:8px">
		<i class="fa fa-save" style="margin-right:4px"></i> Registrar Gasto
	</button>
	<a href="<?php echo esc_url( wpcv_frontend_url( [ 'wpcv' => 'gastos', 'viatico_id' => $viatico->id ] ) ); ?>"
	   class="btn btn-outline-secondary btn-sm">Cancelar</a>
</form>
