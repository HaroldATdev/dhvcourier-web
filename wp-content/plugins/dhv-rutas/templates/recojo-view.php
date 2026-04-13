<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="dhv-recojo-wrap">

    <div class="dhv-recojo-header">
        <span class="dhv-recojo-icon">📦</span>
        <div>
            <h2 class="dhv-recojo-title">Lista de Recojos</h2>
            <p class="dhv-recojo-date"><?php echo date_i18n( 'd/m/Y' ); ?></p>
        </div>
    </div>

    <?php if ( empty( $grouped ) ) : ?>
        <div class="dhv-empty-state">
            <span class="dhv-empty-icon">🎉</span>
            <p>No tienes pedidos de recojo pendientes por hoy.</p>
        </div>
    <?php else : ?>

        <?php foreach ( $grouped as $cliente => $pedidos ) :
            $slug = 'dhv-cliente-' . sanitize_title( $cliente );
            $total = count( $pedidos );
        ?>
        <div class="dhv-cliente-card" data-cliente="<?php echo esc_attr( $cliente ); ?>">

            <!-- Cabecera del cliente (toggle) -->
            <div class="dhv-cliente-header" data-toggle="<?php echo esc_attr( $slug ); ?>">
                <div class="dhv-cliente-info">
                    <span class="dhv-cliente-avatar"><?php echo esc_html( mb_substr( $cliente, 0, 1 ) ); ?></span>
                    <span class="dhv-cliente-name"><?php echo esc_html( $cliente ); ?></span>
                </div>
                <div class="dhv-cliente-meta">
                    <span class="dhv-badge"><?php echo $total; ?> recojo(s)</span>
                    <span class="dhv-toggle-icon">▶</span>
                </div>
            </div>

            <!-- Contenido desplegable -->
            <div class="dhv-cliente-body" id="<?php echo esc_attr( $slug ); ?>">

                <!-- Barra de acción masiva -->
                <div class="dhv-bulk-bar">
                    <label class="dhv-check-label">
                        <input type="checkbox" class="dhv-select-all" data-group="<?php echo esc_attr( $slug ); ?>">
                        <span>Seleccionar todos</span>
                    </label>
                    <div class="dhv-bulk-controls">
                        <select class="dhv-status-select dhv-bulk-status" data-group="<?php echo esc_attr( $slug ); ?>">
                            <option value="">-- Estado masivo --</option>
                            <option value="Pendiente">Pendiente</option>
                            <option value="En espera">En espera</option>
                            <option value="Devuelto">Devuelto</option>
                            <option value="Entregado">Entregado</option>
                        </select>
                        <button class="dhv-btn dhv-btn-primary dhv-bulk-apply" data-group="<?php echo esc_attr( $slug ); ?>">
                            ⚡ Aplicar a seleccionados
                        </button>
                        <span class="dhv-selected-count" data-group="<?php echo esc_attr( $slug ); ?>">0 seleccionado(s)</span>
                    </div>
                </div>

                <!-- Lista de pedidos -->
                <?php foreach ( $pedidos as $pedido ) : ?>
                <div class="dhv-pedido-row" data-id="<?php echo esc_attr( $pedido['id'] ); ?>">
                    <div class="dhv-pedido-left">
                        <input type="checkbox"
                               class="dhv-pedido-check"
                               data-group="<?php echo esc_attr( $slug ); ?>"
                               data-id="<?php echo esc_attr( $pedido['id'] ); ?>">
                        <div class="dhv-pedido-info">
                            <span class="dhv-tracking-dot"></span>
                            <strong class="dhv-tracking-num"><?php echo esc_html( $pedido['tracking'] ); ?></strong>
                        </div>
                    </div>
                    <div class="dhv-pedido-right">
                        <select class="dhv-status-select dhv-single-status"
                                data-id="<?php echo esc_attr( $pedido['id'] ); ?>">
                            <option value="Pendiente"  <?php selected( $pedido['estado'], 'Pendiente' ); ?>>Pendiente</option>
                            <option value="En espera"  <?php selected( $pedido['estado'], 'En espera' ); ?>>En espera</option>
                            <option value="Devuelto"   <?php selected( $pedido['estado'], 'Devuelto' ); ?>>Devuelto</option>
                            <option value="Entregado"  <?php selected( $pedido['estado'], 'Entregado' ); ?>>Entregado</option>
                        </select>
                        <button class="dhv-btn dhv-btn-apply dhv-single-apply"
                                data-id="<?php echo esc_attr( $pedido['id'] ); ?>">
                            Aplicar
                        </button>
                        <span class="dhv-status-badge dhv-estado-<?php echo esc_attr( sanitize_title( $pedido['estado'] ) ); ?>">
                            <?php echo esc_html( $pedido['estado'] ); ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>

            </div><!-- /.dhv-cliente-body -->
        </div><!-- /.dhv-cliente-card -->
        <?php endforeach; ?>

    <?php endif; ?>

</div><!-- /.dhv-recojo-wrap -->

<!-- Toast de notificación -->
<div class="dhv-toast" id="dhvToast"></div>
