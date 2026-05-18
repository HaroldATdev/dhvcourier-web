<?php $wpcfe_print_options = wpcfe_print_options(); ?>
<?php do_action('wpcfe_before_shipment_table_wrapper'); ?>
<div id="shipment-filters" class="filters-card mb-4">
	<div class="filters-body row wpcfe-filter">
		<?php require_once( wpcfe_include_template( 'filter-shipment' ) ); ?>
	</div>
</div>
<div class="shipments-wrapper mb-4" style="visibility: visible; animation-name: fadeIn;">
    <div class="shipments-body">
		<div id="shipments-table-list" class="content">-
			<?php if ( $wpc_shipments->have_posts() ) : ?>
			<div class="table-top form-group">
				<div class="float-md-none float-lg-right">
					<form action="<?php echo $page_url; ?>" method="get">
						<select id="wpcfesort" name="wpcfesort" class="form-control browser-default">
							<option ><?php echo __('Show entries', 'wpcargo-frontend-manager' ); ?></option>
							<?php foreach( $wpcfesort_list as $list ): ?>
							<option value="<?php echo $list ?>" <?php echo $list == $wpcfesort ? 'selected' : '' ;?>><?php echo $list ?> <?php echo __('entries', 'wpcargo-frontend-manager' ); ?></option>
							<?php endforeach; ?>
						</select>
					</form>
				</div>
				<?php if( !empty( $wpcfe_print_options ) ): ?>
				<div class="wpcfe-bulkprint-wrapper dropdown" style="display:inline-block !important;">
				<!--Trigger-->
					<button class="btn btn-default btn-lg dropdown-toggle m-0 py-1 px-2" type="button"
						aria-haspopup="true" aria-expanded="false"><i class="fa fa-print"></i><span class="mx-2"><?php esc_html_e('Print', 'wpcargo-frontend-manager'); ?></span></button>
					<!--Menu-->
					<div class="dropdown-menu dropdown-primary">
						<?php foreach( $wpcfe_print_options as $print_key => $print_label ): ?>
							<a class="wpcfe-bulk-print dropdown-item print-<?php echo $print_key; ?> py-1" data-type="<?php echo $print_key; ?>" href="#"><?php echo $print_label; ?></a>
						<?php endforeach; ?>
					</div>
				</div>
				<?php endif; ?>
				<?php if( can_wpcfe_delete_shipment() ): ?>
					<button class="remove-shipments btn btn-danger btn-sm"><i class="fa fa-trash text-white"></i> <?php _e('Delete', 'wpcargo-frontend-manager'); ?></button>
				<?php endif; ?>
				<?php do_action( 'wpcfe_before_after_shipment_table' ); ?>
			</div>
			<?php do_action('wpcfe_after_shipment_table_actions'); ?>
			<style>
                /* Forzamos que la tabla no sea elástica */
                #shipment-list {
                    table-layout: fixed !important;
                    width: 100% !important;
                }
                /* Atacamos las columnas 3 (Remitente) y 5 (Destinatario) */
                #shipment-list tbody td:nth-child(3), 
                #shipment-list tbody td:nth-child(5),
                #shipment-list tbody td.no-space {
                    max-width: 130px !important;
                    min-width: 130px !important;
                    white-space: nowrap !important;
                    overflow: hidden !important;
                    text-overflow: ellipsis !important;
                    display: table-cell !important;
                }
                /* Para que se pueda leer al pasar el mouse */
                #shipment-list tbody td:nth-child(3):hover, 
                #shipment-list tbody td:nth-child(5):hover,
                #shipment-list tbody td.no-space:hover {
                    overflow: visible !important;
                    white-space: normal !important;
                    background: #fff !important;
                    position: relative;
                    z-index: 100;
                    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                }
            </style>
			<div class="card m-0 mb-2">
				<div class="card-body table-responsive">
					<?php do_action( 'wpcfe_before_shipment_table' ); ?>
					<table id="shipment-list" class="table table-hover table-sm">
						<thead>
							<tr>
								<th class="form-check">
									<input class="form-check-input " id="wpcfe-select-all" type="checkbox"/>
									<label class="form-check-label" for="materialChecked2"></label>
								</th>
								<?php do_action( 'wpcfe_shipment_before_tracking_number_header' ); ?>
								<?php do_action( 'wpcfe_shipment_after_tracking_number_header' ); ?>
								<?php do_action( 'wpcfe_shipment_table_header' ); ?>
								<?php do_action( 'wpcfe_shipment_table_header_action' ); ?>
							</tr>
							<style>
							/* Contenedor de la celda */
							#shipments-table-list th.form-check,
							#shipments-table-list td.form-check {
								padding: 10px 5px !important; /* Espacio controlado */
								width: 35px !important;       /* Ancho fijo para la columna */
								min-width: 35px !important;
								text-align: center !important;
								vertical-align: top !important; /* Alineado arriba con el texto */
							}

							/* Ajuste del checkbox real */
							#shipments-table-list .form-check-input {
								margin: 0 !important;
								cursor: pointer !important;
								position: static !important; /* Quitamos el relative para evitar bloqueos */
								opacity: 1 !important;       /* Aseguramos que sea visible y clickeable */
								z-index: 10 !important;      /* Lo traemos al frente */
							}

							/* Desactivamos el label para que no robe el clic si está vacío */
							#shipments-table-list .form-check-label {
								display: none !important; 
							}

							/* Alineación específica para el checkbox del header */
							#wpcfe-select-all {
								margin-top: 5px !important;
							}
							</style>
						</thead>
						<tbody>
							<?php	
							do_action( 'wpcfe_before_shipment_table_row', $wpc_shipments, $args ); 				
							while ( $wpc_shipments->have_posts() ) {
								$wpc_shipments->the_post();
								$status  		= get_post_meta( get_the_ID(), 'wpcargo_status', true );
								?>
								<tr id="shipment-<?php echo get_the_ID(); ?>" class="shipment-row <?php echo wpcfe_to_slug( $status ); ?>">
									<td class="form-check">
									  <input class="wpcfe-shipments form-check-input " type="checkbox" name="wpcfe-shipments[]" value="<?php echo get_the_ID(); ?>" data-number="<?php echo get_the_title(); ?>">
									  <label class="form-check-label" for="materialChecked2"></label>
									</td>
									<?php do_action( 'wpcfe_shipment_before_tracking_number_data', get_the_ID() ); ?>
									<?php do_action( 'wpcfe_shipment_after_tracking_number_data', get_the_ID() ); ?>
									<?php do_action( 'wpcfe_shipment_table_data', get_the_ID() ); ?>
									<?php do_action( 'wpcfe_shipment_table_data_action', get_the_ID() ); ?>				
								</tr>
								<?php
							} // end while
							do_action( 'wpcfe_after_shipment_table_row', $wpc_shipments, $args );
							?>
						</tbody>
					</table>
				</div>
			</div>
			<?php if( !empty( $wpcfe_print_options ) ): ?>
				<div class="wpcfe-bulkprint-wrapper dropdown" style="display:inline-block !important;">
				<!--Trigger-->
					<button class="btn btn-default btn-lg dropdown-toggle m-0 py-1 px-2" type="button"
						aria-haspopup="true" aria-expanded="false"><i class="fa fa-print"></i><span class="mx-2"><?php esc_html_e('Print', 'wpcargo-frontend-manager'); ?></span></button>
					<!--Menu-->
					<div class="dropdown-menu dropdown-primary">
						<?php foreach( $wpcfe_print_options as $print_key => $print_label ): ?>
							<a class="wpcfe-bulk-print dropdown-item print-<?php echo $print_key; ?> py-1" data-type="<?php echo $print_key; ?>" href="#"><?php echo $print_label; ?></a>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
			<?php if( can_wpcfe_delete_shipment() ): ?>
				<button class="remove-shipments btn btn-danger btn-sm"><i class="fa fa-trash text-white"></i> <?php esc_html_e('Delete', 'wpcargo-frontend-manager'); ?></button>
			<?php endif; ?>
			<?php do_action( 'wpcfe_before_after_shipment_table' ); ?>
			<div class="row my-2">
				<section class="col-md-5">
					<p class="note note-primary">
						<?php _e('Showing', 'wpcargo-fm'); echo ' '.$record_start.' '; _e('to', 'wpcargo-fm'); echo ' '.$record_end.' '; _e('of', 'wpcargo-fm'); echo ' '.number_format($number_records).' '; _e('entries', 'wpcargo-fm'); ?>
					</p>
				</section>
				<section class="col-md-7"><?php wpcfe_bootstrap_pagination( array( 'custom_query' => $wpc_shipments ) ); ?></section>
			</div>
			<?php else: ?>
				<i class="fa fa-inbox d-block p-2 text-center text-danger" style="font-size: 4rem;"></i>
				<h3 class="text-center text-danger"><?php _e('No shipment found.', 'wpcargo-frontend-manager'); ?></h3>
				<?php if( array_key_exists( 's', $args ) && !empty( $args['s'] ) ): ?>
					<p class="text-center text-danger"><?php printf( __('Searched:  "%s"', 'wpcargo-frontend-manager'), $args['s'] ); ?></p>
				<?php else: ?>
					<?php $shipment_date_range_notification = sprintf( __('%s to %s', 'wpcargo-frontend-manager'), wpcfe_formatted_date( $date_start ), wpcfe_formatted_date( $date_end ) ); ?>
					<p class="text-center text-danger"><?php echo apply_filters( 'shipment_date_range_notification', $shipment_date_range_notification, $date_start, $date_end  ); ?></p>
				<?php endif; ?>
			<?php endif; ?>			
		</div>
	</div>
</div>
<?php do_action('wpcfe_after_shipment_data'); ?>