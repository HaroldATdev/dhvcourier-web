<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WPCC_Driver_Sync
 *
 * Inyecta campos DNI/Brevete en formulario WPCargo User Management
 * y mantiene la sincronización bidireccional usuario <-> transportista.
 *
 * HOOKS VERIFICADOS (hooks.php del plugin):
 *   wpcumanage_user_form_middle( $user_data, $is_update )
 *   wpcumanage_after_save_user( $user_data, $data )
 */
class WPCC_Driver_Sync {

	public function __construct() {
		// Formulario de WPCargo User Management
		add_action( 'wpcumanage_user_form_middle', [ $this, 'mostrar_campos_en_form' ], 20, 2 );
		add_action( 'wpcumanage_after_save_user',  [ $this, 'guardar_desde_form' ],     10, 2 );

		// Perfil WP nativo
		add_action( 'show_user_profile',        [ $this, 'mostrar_campos_perfil' ] );
		add_action( 'edit_user_profile',        [ $this, 'mostrar_campos_perfil' ] );
		add_action( 'personal_options_update',  [ $this, 'guardar_campos_perfil' ] );
		add_action( 'edit_user_profile_update', [ $this, 'guardar_campos_perfil' ] );

		// ── Ciclo de vida del usuario ───────────────────────────────────
		// Cuando se elimina un usuario: desvincular su transportista (no borrarlo)
		add_action( 'delete_user',   [ $this, 'al_eliminar_usuario' ], 10, 1 );
		// Cuando se desactiva/cambia el rol: sincronizar estado del transportista
		add_action( 'set_user_role', [ $this, 'al_cambiar_rol' ],      10, 3 );

		// Admin notices para errores
		add_action( 'admin_notices', [ $this, 'mostrar_errores_sync' ] );

		// AJAX: verificar DNI/Brevete en tiempo real
		add_action( 'wp_ajax_wpcc_verificar_dni', [ $this, 'ajax_verificar_dni' ] );

		// Columna en lista de usuarios
		add_filter( 'manage_users_columns',       [ $this, 'columna_header' ] );
		add_filter( 'manage_users_custom_column', [ $this, 'columna_data' ], 10, 3 );
	}

	/* ─── Helpers ─────────────────────────────────────────────────────── */

	private function es_driver( int $user_id ): bool {
		$user = get_userdata( $user_id );
		return $user && in_array( 'wpcargo_driver', (array) $user->roles, true );
	}

	/* ─── AJAX: verificar DNI/Brevete ────────────────────────────────── */

	public function ajax_verificar_dni(): void {
		check_ajax_referer( 'wpcc_nonce', 'nonce' );
		$dni     = sanitize_text_field( wp_unslash( $_POST['dni']     ?? '' ) );
		$brevete = strtoupper( sanitize_text_field( wp_unslash( $_POST['brevete'] ?? '' ) ) );
		$user_id = (int) ( $_POST['user_id'] ?? 0 ); // 0 = creación nueva

		$errores = [];

		if ( ! empty( $dni ) ) {
			$por_dni = WPCC_Transportista::obtener_por_dni( $dni );
			if ( $por_dni ) {
				$otro_user_id   = (int) ( $por_dni->user_id ?? 0 );
				$otro_user_wp   = $otro_user_id > 0 ? get_userdata( $otro_user_id ) : false;
				$otro_es_driver = $otro_user_wp && in_array( 'wpcargo_driver', (array) $otro_user_wp->roles, true );
				$es_rival       = $otro_user_id > 0 && $otro_user_id !== $user_id && $otro_user_wp !== false && $otro_es_driver;

				if ( $es_rival && $por_dni->estado === 'activo' ) {
					$nombre    = WPCC_Transportista::nombre_completo( $por_dni );
					$errores[] = 'DNI ' . esc_html( $dni ) . ' ya esta activo para: ' . esc_html( $nombre ) . '.';
				}
			}
		}

		if ( ! empty( $brevete ) && strlen( $brevete ) >= 4 ) {
			global $wpdb;
			$tabla = $wpdb->prefix . 'wpcc_transportistas';
			$excluir = $user_id > 0 ? $wpdb->prepare( 'AND id != (SELECT COALESCE((SELECT id FROM ' . $tabla . ' WHERE user_id = %d LIMIT 1), 0))', $user_id ) : '';
			$dup = $wpdb->get_row( $wpdb->prepare( // phpcs:ignore
				"SELECT id, nombres, apellidos FROM {$tabla} WHERE brevete = %s AND estado = 'activo' {$excluir} LIMIT 1", // phpcs:ignore
				$brevete
			) );
			if ( $dup ) {
				$nombre    = trim( $dup->nombres . ' ' . $dup->apellidos );
				$errores[] = 'Brevete ' . esc_html( $brevete ) . ' ya esta activo para: ' . esc_html( $nombre ) . '.';
			}
		}

		wp_send_json( [ 'ok' => empty( $errores ), 'errores' => $errores ] );
	}

	/* ─── Inyectar campos en el form de WPCargo User Management ──────── */

	public function mostrar_campos_en_form( $user_data, $is_update ): void {
		$user_id = ( $is_update && is_object( $user_data ) ) ? (int) $user_data->ID : 0;
		$t       = $user_id ? WPCC_Transportista::obtener_por_user_id( $user_id ) : null;
		$dni     = $t ? $t->dni     : get_user_meta( $user_id, 'wpcc_dni',     true );
		$brevete = $t ? $t->brevete : get_user_meta( $user_id, 'wpcc_brevete', true );
		$ya_driver = $user_id && $this->es_driver( $user_id );
		?>
		<div id="wpcc-driver-section" class="row mb-4" style="display:<?php echo $ya_driver ? 'block' : 'none'; ?>">
			<div class="col-sm-12">
				<h2 class="h6 py-2 border-bottom font-weight-bold" style="color:#0073aa;">
					Datos de Transportista (Driver)
				</h2>
			</div>
			<div class="col-md-6">
				<div class="form-group">
					<label for="wpcc_dni">DNI <span style="color:#dc3545">*</span></label>
					<input type="text" id="wpcc_dni" name="wpcc_dni"
					       class="form-control browser-default"
					       maxlength="8" inputmode="numeric"
					       oninput="this.value=this.value.replace(/\D/g,'')"
					       placeholder="12345678"
					       value="<?php echo esc_attr( $dni ); ?>">
					<small class="form-text text-muted">8 digitos numericos.</small>
				</div>
			</div>
			<div class="col-md-6">
				<div class="form-group">
					<label for="wpcc_brevete">Brevete <span style="color:#dc3545">*</span></label>
					<input type="text" id="wpcc_brevete" name="wpcc_brevete"
					       class="form-control browser-default"
					       maxlength="12" style="text-transform:uppercase"
					       oninput="this.value=this.value.toUpperCase()"
					       placeholder="Ej: A-2345"
					       value="<?php echo esc_attr( $brevete ); ?>">
					<small class="form-text text-muted">Formato: A-2345 o Q3-12345.</small>
				</div>
			</div>
		</div>
		<script>
		(function () {
			var ajaxUrl  = '<?php echo esc_url( admin_url("admin-ajax.php") ); ?>';
			var nonce    = '<?php echo wp_create_nonce("wpcc_nonce"); ?>';
			var userId   = <?php echo $user_id ?: 0; ?>;
			var errDni   = false;
			var errBrv   = false;
			var timer    = null;

			// ── mostrar/ocultar error bajo el campo ──────────────────
			function setError(inputId, msg) {
				var input = document.getElementById(inputId);
				if (!input) return;
				var err = input.parentNode.querySelector('.wpcc-err');
				if (!err) {
					err = document.createElement('div');
					err.className = 'wpcc-err';
					err.style.cssText = 'color:#c00;font-weight:700;font-size:12px;margin-top:4px;';
					input.parentNode.appendChild(err);
				}
				err.textContent = msg;
				input.style.border = msg ? '2px solid #c00' : '';
			}

			// ── llamada AJAX ─────────────────────────────────────────
			function verificar(callback) {
				var dni = (document.getElementById('wpcc_dni') || {}).value || '';
				var brv = (document.getElementById('wpcc_brevete') || {}).value || '';
				setError('wpcc_dni', '');
				setError('wpcc_brevete', '');
				errDni = false; errBrv = false;
				if (!dni && !brv) { if (callback) callback(true); return; }
				var fd = new FormData();
				fd.append('action',  'wpcc_verificar_dni');
				fd.append('nonce',   nonce);
				fd.append('dni',     dni);
				fd.append('brevete', brv);
				fd.append('user_id', userId);
				fetch(ajaxUrl, {method:'POST', body:fd})
					.then(function(r){ return r.json(); })
					.then(function(data) {
						(data.errores || []).forEach(function(msg) {
							if (msg.indexOf('DNI') === 0) { setError('wpcc_dni', '⚠ '+msg); errDni = true; }
							else                          { setError('wpcc_brevete', '⚠ '+msg); errBrv = true; }
						});
						if (callback) callback(data.ok);
					})
					.catch(function(){ if (callback) callback(true); }); // si falla AJAX dejar pasar
			}

			// ── mostrar/ocultar sección driver ───────────────────────
			function toggle() {
				var sel = document.querySelector('select[name="_roles[]"]');
				var sec = document.getElementById('wpcc-driver-section');
				if (!sel || !sec) return;
				var vals     = Array.from(sel.options).filter(function(o){return o.selected;}).map(function(o){return o.value;});
				var isDriver = vals.indexOf('wpcargo_driver') !== -1;
				sec.style.display = isDriver ? 'block' : 'none';
				var d = document.getElementById('wpcc_dni');
				var b = document.getElementById('wpcc_brevete');
				if (d) d.required = isDriver;
				if (b) b.required = isDriver;
				if (!isDriver) { setError('wpcc_dni',''); setError('wpcc_brevete',''); }
			}

			document.addEventListener('DOMContentLoaded', function () {
				// toggle rol
				var sel = document.querySelector('select[name="_roles[]"]');
				if (sel) sel.addEventListener('change', toggle);
				if (typeof jQuery !== 'undefined') {
					jQuery(document).on('select2:select select2:unselect change', 'select[name="_roles[]"]', toggle);
				}
				toggle();

				// verificar al escribir (debounce 700ms)
				['wpcc_dni','wpcc_brevete'].forEach(function(id) {
					var el = document.getElementById(id);
					if (!el) return;
					el.addEventListener('input', function(){ clearTimeout(timer); timer = setTimeout(function(){ verificar(null); }, 700); });
					el.addEventListener('blur',  function(){ verificar(null); });
				});

				// ── BLOQUEAR SUBMIT hasta confirmar que DNI es válido ──
				var submitOk = false;
				function onSubmit(e) {
					var sec = document.getElementById('wpcc-driver-section');
					if (!sec || sec.style.display === 'none') return; // no es driver, dejar pasar
					if (submitOk) { submitOk = false; return; } // ya validado, dejar pasar
					e.preventDefault();
					e.stopImmediatePropagation();
					verificar(function(ok) {
						if (!ok) {
							var firstErr = document.querySelector('.wpcc-err');
							if (firstErr) firstErr.scrollIntoView({behavior:'smooth', block:'center'});
							return;
						}
						submitOk = true;
						e.target.submit(); // reenviar — esta vez submitOk=true lo deja pasar
					});
				}
				document.addEventListener('submit', onSubmit, true); // capture=true
			});
		})();
		</script>
		<?php
	}

	/* ─── Guardar desde el form de WPCargo User Management ───────────── */

	public function guardar_desde_form( $user_data, $data ): void {
		$user_id = 0;
		if ( is_object( $user_data ) && isset( $user_data->ID ) ) {
			$user_id = (int) $user_data->ID;
		} elseif ( is_numeric( $user_data ) ) {
			$user_id = (int) $user_data;
		}
		if ( ! $user_id ) return;

		$roles_post = (array) ( $data['_roles'] ?? [] );
		if ( ! $this->es_driver( $user_id ) && ! in_array( 'wpcargo_driver', $roles_post, true ) ) return;

		$dni     = sanitize_text_field( $data['wpcc_dni']     ?? '' );
		$brevete = strtoupper( sanitize_text_field( $data['wpcc_brevete'] ?? '' ) );
		if ( empty( $dni ) || empty( $brevete ) ) return;

		update_user_meta( $user_id, 'wpcc_dni',     $dni );
		update_user_meta( $user_id, 'wpcc_brevete', $brevete );

		$user  = get_userdata( $user_id );
		$datos = [
			'nombres'  => sanitize_text_field( $data['first_name'] ?? get_user_meta( $user_id, 'first_name', true ) ) ?: $user->display_name,
			'apellidos'=> sanitize_text_field( $data['last_name']  ?? get_user_meta( $user_id, 'last_name',  true ) ),
			'dni'      => $dni,
			'brevete'  => $brevete,
			'telefono' => sanitize_text_field( $data['billing_phone'] ?? get_user_meta( $user_id, 'billing_phone', true ) ),
			'email'    => $user->user_email,
		];

		$result = WPCC_Transportista::sincronizar_desde_usuario( $user_id, $datos );
		if ( is_wp_error( $result ) ) {
			set_transient( 'wpcc_sync_error_' . get_current_user_id(), $result->get_error_message(), 60 );
		}
	}

	/* ─── Perfil WP nativo ────────────────────────────────────────────── */

	public function mostrar_campos_perfil( WP_User $user ): void {
		if ( ! $this->es_driver( $user->ID ) ) return;
		$t = WPCC_Transportista::obtener_por_user_id( $user->ID );
		?>
		<h2>Datos de Transportista</h2>
		<table class="form-table" role="presentation">
			<tr>
				<th><label for="wpcc_dni">DNI</label></th>
				<td>
					<input type="text" id="wpcc_dni" name="wpcc_dni"
					       class="small-text" maxlength="8" inputmode="numeric"
					       oninput="this.value=this.value.replace(/\D/g,'')"
					       value="<?php echo esc_attr( $t ? $t->dni : get_user_meta( $user->ID, 'wpcc_dni', true ) ); ?>">
				</td>
			</tr>
			<tr>
				<th><label for="wpcc_brevete">Brevete</label></th>
				<td>
					<input type="text" id="wpcc_brevete" name="wpcc_brevete"
					       class="regular-text" maxlength="12"
					       style="text-transform:uppercase"
					       oninput="this.value=this.value.toUpperCase()"
					       placeholder="Ej: A-2345"
					       value="<?php echo esc_attr( $t ? $t->brevete : get_user_meta( $user->ID, 'wpcc_brevete', true ) ); ?>">
				</td>
			</tr>
			<?php if ( $t ) : ?>
			<tr>
				<th>Estado</th>
				<td>
					<span style="font-weight:600;color:<?php echo $t->estado === 'activo' ? '#00a32a' : '#888'; ?>">
						<?php echo esc_html( ucfirst( $t->estado ) ); ?>
					</span>
					&mdash;
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcc-editar&id=' . $t->id ) ); ?>">
						Ver ficha
					</a>
				</td>
			</tr>
			<?php endif; ?>
		</table>
		<?php
	}

	public function guardar_campos_perfil( int $user_id ): void {
		if ( ! current_user_can( 'edit_user', $user_id ) ) return;
		if ( ! $this->es_driver( $user_id ) ) return;
		if ( ! isset( $_POST['wpcc_dni'] ) ) return;

		$dni     = sanitize_text_field( wp_unslash( $_POST['wpcc_dni']     ?? '' ) );
		$brevete = strtoupper( sanitize_text_field( wp_unslash( $_POST['wpcc_brevete'] ?? '' ) ) );

		update_user_meta( $user_id, 'wpcc_dni',     $dni );
		update_user_meta( $user_id, 'wpcc_brevete', $brevete );

		$user  = get_userdata( $user_id );
		$datos = [
			'nombres'  => get_user_meta( $user_id, 'first_name', true ) ?: $user->display_name,
			'apellidos'=> get_user_meta( $user_id, 'last_name',  true ) ?: '',
			'dni'      => $dni,
			'brevete'  => $brevete,
			'telefono' => sanitize_text_field( wp_unslash( $_POST['wpcc_telefono'] ?? '' ) ),
			'email'    => $user->user_email,
		];

		$result = WPCC_Transportista::sincronizar_desde_usuario( $user_id, $datos );
		if ( is_wp_error( $result ) ) {
			set_transient( 'wpcc_sync_error_' . get_current_user_id(), $result->get_error_message(), 60 );
		}
	}

	/* ─── Ciclo de vida del usuario ───────────────────────────────────── */

	/**
	 * Cuando se ELIMINA un usuario WP:
	 * - Poner su transportista en INACTIVO
	 * - Quitar el user_id del transportista (queda huérfano para poder reasignarse)
	 */
	public function al_eliminar_usuario( int $user_id ): void {
		global $wpdb;
		$tabla = $wpdb->prefix . 'wpcc_transportistas';
		$wpdb->update(
			$tabla,
			[ 'estado' => 'inactivo', 'user_id' => null ],
			[ 'user_id' => $user_id ],
			[ '%s', null ],
			[ '%d' ]
		); // phpcs:ignore
	}

	/**
	 * Cuando se CAMBIA EL ROL de un usuario:
	 * - Si pierde el rol driver → transportista INACTIVO (sin quitar user_id)
	 * - Si recupera el rol driver → transportista ACTIVO
	 */
	public function al_cambiar_rol( int $user_id, string $rol_nuevo, array $roles_anteriores ): void {
		global $wpdb;
		$tabla = $wpdb->prefix . 'wpcc_transportistas';
		$tenia_driver  = in_array( 'wpcargo_driver', $roles_anteriores, true );
		$tiene_driver  = ( $rol_nuevo === 'wpcargo_driver' );

		if ( $tenia_driver && ! $tiene_driver ) {
			// Perdió el rol driver → inactivar
			$wpdb->update( $tabla, [ 'estado' => 'inactivo' ], [ 'user_id' => $user_id ], [ '%s' ], [ '%d' ] ); // phpcs:ignore
		} elseif ( ! $tenia_driver && $tiene_driver ) {
			// Ganó el rol driver → reactivar si existe
			$wpdb->update( $tabla, [ 'estado' => 'activo' ], [ 'user_id' => $user_id ], [ '%s' ], [ '%d' ] ); // phpcs:ignore
		}
	}

	/* ─── Admin notice ────────────────────────────────────────────────── */

	public function mostrar_errores_sync(): void {
		$key   = 'wpcc_sync_error_' . get_current_user_id();
		$error = get_transient( $key );
		if ( ! $error ) return;
		delete_transient( $key );
		echo '<div class="notice notice-error is-dismissible"><p><strong>WP Cargo Carrier:</strong> ' . esc_html( $error ) . '</p></div>';
	}

	/* ─── Columna en lista de usuarios ───────────────────────────────── */

	public function columna_header( array $columns ): array {
		$columns['wpcc_transportista'] = 'Transportista';
		return $columns;
	}

	public function columna_data( string $output, string $column, int $user_id ): string {
		if ( $column !== 'wpcc_transportista' ) return $output;
		if ( ! $this->es_driver( $user_id ) ) return '&mdash;';
		$t = WPCC_Transportista::obtener_por_user_id( $user_id );
		if ( ! $t ) {
			return '<span style="color:#d63638;font-size:11px">Sin ficha</span> '
			     . '<a href="' . esc_url( admin_url( 'user-edit.php?user_id=' . $user_id ) ) . '" style="font-size:11px">Completar</a>';
		}
		$color = $t->estado === 'activo' ? '#00a32a' : '#888';
		return '<a href="' . esc_url( admin_url( 'admin.php?page=wpcc-editar&id=' . $t->id ) ) . '" style="font-size:11px">'
		     . esc_html( $t->nombres . ' ' . $t->apellidos ) . '</a>'
		     . '<br><span style="color:' . $color . ';font-size:10px">' . esc_html( ucfirst( $t->estado ) ) . '</span>';
	}
}

new WPCC_Driver_Sync();
