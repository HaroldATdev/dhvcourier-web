<div class="table-top form-group">
    <?php do_action( 'wpcinvoice_before_filter' ); ?>
    <button id="invoiceBulkUpdate" class="wpcinvoice-bulk-update btn btn-info btn-sm waves-effect waves-light" data-toggle="modal" data-target="#invoiceUpdateModal"><i class="fa fa-edit text-white"></i> <?php _e('Update', 'wpcargo-invoice' ); ?></button>
    <?php if( !empty( $print_options ) ): ?>
    <div class="wpcinvoice-bulk-print-wrapper dropdown" style="display:inline-block !important;">
        <!--Trigger-->
        <button class="btn btn-default btn-lg dropdown-toggle m-0 py-1 px-2" type="button" data-toggle="dropdown"
            aria-haspopup="true" aria-expanded="false" style="font-size: .64rem; padding: .5rem 1.6rem !important;"><i class="fa fa-print"></i><span class="mx-2"><?php esc_html_e('Print', 'wpcargo-invoice'); ?></span></button>
        <!--Menu-->
        <div class="dropdown-menu dropdown-primary">
            <?php foreach( $print_options as $print_key => $print_label ): ?>
                <a class="wpcinvoice-bulk-print dropdown-item print-<?php echo $print_key; ?> py-1" data-type="<?php echo $print_key; ?>" href="#"><?php echo $print_label; ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <a id="wpcinvoice-ie" href="<?php echo get_permalink( $invoice_page ).'?wpcinvoice=export'; ?>" class="wpcinvoice-ie-btn btn btn-sm btn-success waves-effect waves-light"><?php echo wpcinvoice_export_heading_label(); ?></a>
    <?php endif; ?>
    <form id="wpcfe-search" class="float-md-none float-lg-right" action="<?php echo $page_url; ?>" method="get">
        <div class="form-sm">
            <label for="search-shipment" class="sr-only"><?php _e('Invoice Number', 'wpcargo-invoice' ); ?></label>
            <input type="text" class="form-control form-control-sm" name="wpcinvoice" id="search-shipment" placeholder="<?php _e('Invoice Number', 'wpcargo-invoice' ); ?>" value="<?php echo $__wpcinvoice; ?>">
            <button type="submit" class="btn btn-primary btn-sm mx-md-0 ml-2"><?php esc_html_e('Search', 'wpcargo-invoice' ); ?></button>
        </div>
    </form>
    <?php do_action( 'wpcinvoice_after_filter' ); ?>
</div>