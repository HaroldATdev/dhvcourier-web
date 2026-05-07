<style>
#wpcbranch-restriction { padding: 18px 22px; }
#wpcbranch-restriction h3.hndle { font-size:14px; font-weight:700; margin:0 0 12px; padding-bottom:10px; border-bottom:2px solid #1a9bcf; color:#1a7eb8; }
.wpcbranch-chk-row { display:flex !important; flex-direction:row !important; align-items:center !important; flex-wrap:nowrap !important; margin:8px 0 !important; padding:0 !important; gap:8px !important; }
.wpcbranch-chk-row input[type="checkbox"] {
    -webkit-appearance:checkbox !important;
    appearance:checkbox !important;
    display:inline-block !important;
    visibility:visible !important;
    opacity:1 !important;
    width:16px !important;
    height:16px !important;
    min-width:16px !important;
    min-height:16px !important;
    margin:0 !important;
    padding:0 !important;
    position:static !important;
    float:none !important;
    flex-shrink:0 !important;
    cursor:pointer !important;
    accent-color:#1a9bcf;
    border:1px solid #aaa !important;
    background:#fff !important;
    transform:none !important;
}
.wpcbranch-chk-row label { font-size:13px; color:#333; cursor:pointer; margin:0 !important; padding:0 !important; line-height:1.4; }
.wpcbranch-note { font-size:12px; color:#777; font-style:italic; margin-top:10px !important; }
</style>
<div class="postbox">
    <div id="wpcbranch-restriction" class="inside">
        <h3 class="hndle"><?php esc_html_e('Configuración de Restricciones de Sucursal', 'wpcargo-branches' ); ?></h3>
        <div class="wpcbranch-chk-row">
            <input id="wpcbranch_restrict_all_employees" type="checkbox" class="wpcbranch_access" name="wpcbranch_restrict_all_employees" value="1" <?php checked( get_option('wpcbranch_restrict_all_employees'), 1 ); ?>>
            <label for="wpcbranch_restrict_all_employees"><?php esc_html_e('Restringir Colaborador de Sucursal para acceder a todos los Empleados?', 'wpcargo-branches' ); ?></label>
        </div>
        <div class="wpcbranch-chk-row">
            <input id="wpcbranch_restrict_all_agents" type="checkbox" class="wpcbranch_access" name="wpcbranch_restrict_all_agents" value="1" <?php checked( get_option('wpcbranch_restrict_all_agents'), 1 ); ?>>
            <label for="wpcbranch_restrict_all_agents"><?php esc_html_e('Restringir Colaborador de Sucursal para acceder a todos los Agentes?', 'wpcargo-branches' ); ?></label>
        </div>
        <div class="wpcbranch-chk-row">
            <input id="wpcbranch_restrict_all_clients" type="checkbox" class="wpcbranch_access" name="wpcbranch_restrict_all_clients" value="1" <?php checked( get_option('wpcbranch_restrict_all_clients'), 1 ); ?>>
            <label for="wpcbranch_restrict_all_clients"><?php esc_html_e('Restringir Colaborador de Sucursal para acceder a todos los Clientes?', 'wpcargo-branches' ); ?></label>
        </div>
        <div class="wpcbranch-chk-row">
            <input id="wpcbranch_restrict_all_drivers" type="checkbox" class="wpcbranch_access" name="wpcbranch_restrict_all_drivers" value="1" <?php checked( get_option('wpcbranch_restrict_all_drivers'), 1 ); ?>>
            <label for="wpcbranch_restrict_all_drivers"><?php esc_html_e('Restringir Colaborador de Sucursal para acceder a todos los Conductores?', 'wpcargo-branches' ); ?></label>
        </div>
        <div class="wpcbranch-chk-row">
            <input id="wpcbranch_dynamic_prefix_suffix" type="checkbox" class="wpcbranch_access" name="wpcbranch_dynamic_prefix_suffix" value="1" <?php checked( get_option('wpcbranch_dynamic_prefix_suffix'), 1 ); ?>>
            <label for="wpcbranch_dynamic_prefix_suffix"><?php esc_html_e('¿Habilitar prefijo y sufijo dinámico en la generación del número de seguimiento?', 'wpcargo-branches' ); ?></label>
        </div>
        <div class="wpcbranch-note"><i><?php esc_html_e('Nota: Si la restricción está habilitada, el Colaborador de Sucursal solo podrá ver usuarios asignados a su sucursal', 'wpcargo-branches' ); ?></i></div>
    </div>
</div>