<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="dhv-entrega-wrap">

    <div class="dhv-entrega-header">
        <span class="dhv-entrega-icon">🚚</span>
        <div>
            <h2 class="dhv-entrega-title">Lista de Entregas</h2>
            <p class="dhv-entrega-date"><?php echo date_i18n( 'd/m/Y' ); ?></p>
        </div>
    </div>

    <?php if ( empty( $grouped ) ) : ?>
        <div class="dhv-empty-state">
            <span class="dhv-empty-icon">🎉</span>
            <p>No tienes pedidos de entrega pendientes por hoy.</p>
        </div>
    <?php else : ?>

        <?php foreach ( $grouped as $destinatario => $pedidos ) :
            $slug  = 'dhv-dest-' . sanitize_title( $destinatario );
            $total = count( $pedidos );
        ?>
        <div class="dhv-cliente-card" data-dest="<?php echo esc_attr( $destinatario ); ?>">

            <!-- Cabecera del destinatario (toggle) -->
            <div class="dhv-cliente-header dhv-entrega-cliente-header" data-toggle="<?php echo esc_attr( $slug ); ?>">
                <div class="dhv-cliente-info">
                    <span class="dhv-cliente-avatar dhv-entrega-avatar"><?php echo esc_html( mb_substr( $destinatario, 0, 1 ) ); ?></span>
                    <span class="dhv-cliente-name"><?php echo esc_html( $destinatario ); ?></span>
                </div>
                <div class="dhv-cliente-meta">
                    <span class="dhv-badge"><?php echo $total; ?> entrega(s)</span>
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
                        <select class="dhv-status-select dhv-bulk-status dhv-entrega-bulk-status" data-group="<?php echo esc_attr( $slug ); ?>">
                            <option value="">-- Estado masivo --</option>
                            <option value="En ruta">En ruta</option>
                            <option value="Entregado">Entregado</option>
                            <option value="Devuelto">Devuelto</option>
                            <option value="Reprogramado">Reprogramado</option>
                            <option value="Anulado">Anulado</option>
                        </select>
                        <button class="dhv-btn dhv-btn-entrega dhv-bulk-apply dhv-entrega-bulk-apply" data-group="<?php echo esc_attr( $slug ); ?>">
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
                            <span class="dhv-tracking-dot dhv-entrega-dot"></span>
                            <div>
                                <strong class="dhv-tracking-num"><?php echo esc_html( $pedido['tracking'] ); ?></strong>
                                <div class="dhv-pedido-direccion">
                                    <?php if ( ! empty( $pedido['direccion'] ) ) : ?>
                                        <span class="dhv-dir-icon">📍</span>
                                        <?php echo esc_html( $pedido['direccion'] ); ?>
                                        <?php if ( ! empty( $pedido['lugar'] ) ) : ?>
                                            — <em><?php echo esc_html( $pedido['lugar'] ); ?></em>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if ( ! empty( $pedido['telefono'] ) ) : ?>
                                        &nbsp;·&nbsp;<span class="dhv-dir-icon">📞</span>
                                        <a href="tel:<?php echo esc_attr( $pedido['telefono'] ); ?>" class="dhv-tel-link">
                                            <?php echo esc_html( $pedido['telefono'] ); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="dhv-pedido-right">
                        <select class="dhv-status-select dhv-single-status dhv-entrega-single-status"
                                data-id="<?php echo esc_attr( $pedido['id'] ); ?>">
                            <option value="En ruta"      <?php selected( $pedido['estado'], 'En ruta' ); ?>>En ruta</option>
                            <option value="Entregado"    <?php selected( $pedido['estado'], 'Entregado' ); ?>>Entregado</option>
                            <option value="Devuelto"     <?php selected( $pedido['estado'], 'Devuelto' ); ?>>Devuelto</option>
                            <option value="Reprogramado" <?php selected( $pedido['estado'], 'Reprogramado' ); ?>>Reprogramado</option>
                            <option value="Anulado"      <?php selected( $pedido['estado'], 'Anulado' ); ?>>Anulado</option>
                        </select>
                        <button class="dhv-btn dhv-btn-apply dhv-single-apply dhv-entrega-single-apply"
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

</div><!-- /.dhv-entrega-wrap -->

<!-- Toast de notificación (compartido) -->
<?php if ( ! defined( 'DHV_TOAST_RENDERED' ) ) : define( 'DHV_TOAST_RENDERED', true ); ?>
<div class="dhv-toast" id="dhvToast"></div>
<?php endif; ?>
