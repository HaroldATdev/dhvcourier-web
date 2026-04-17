<div id="wpcinvoice-table-wrapper" class="table-responsive">
    <?php do_action( 'wpcinvoice_before_table', $wpcinvoice_query ); ?>
    <table id="wpcinvoice-list" class="table table-hover table-sm">
        <thead>
            <tr>
                <th class="form-check">
                    <input class="form-check-input " id="wpcfe-select-all" type="checkbox"/>
                    <label class="form-check-label" for="materialChecked2"></label>
                </th>
                <?php do_action( 'wpcinvoice_table_header' ); ?>
            </tr>
        </thead>
        <tbody>
            <?php if ( $wpcinvoice_query->have_posts() ) : while ( $wpcinvoice_query->have_posts() ) : $wpcinvoice_query->the_post(); ?>
            <?php
                $invoice_id     = get_post_meta( get_the_ID(), '__wpcinvoice_id', true );
                $status         = get_post_meta( $invoice_id, '__wpcinvoice_status', true );
            ?>
            <tr id="shipment-<?php echo $invoice_id; ?>" class="shipment-row <?php echo wpcfe_to_slug( $status ); ?>">
                <td class="form-check">
                    <input class="wpcfe-shipments form-check-input " type="checkbox" name="wpcfe-shipments[]" value="<?php echo $invoice_id; ?>" data-sid="<?php echo get_the_ID(); ?>" data-number="<?php echo get_the_title($invoice_id); ?>" data-status="<?php echo $status; ?>">
                    <label class="form-check-label" for="materialChecked2"></label>
                </td>
                <?php do_action( 'wpcinvoice_table_data', get_the_ID(), $invoice_id ); ?>
            </tr>
            <?php endwhile; endif; ?>
        </tbody>
    </table>
    <?php do_action( 'wpcinvoice_after_table', $wpcinvoice_query ); ?>
    <div class="row">
        <section class="col-md-5">
            <?php
                printf(
                    '<p class="note note-primary">Showing %s to %s of %s entries.</p>',
                    $record_start,
                    $record_end,
                    number_format($number_records)
                );
            ?>
        </section>
        <section class="col-md-7"><?php wpcfe_bootstrap_pagination( array( 'custom_query' => $wpcinvoice_query ) ); ?></section>
    </div>
    <?php do_action( 'wpcinvoice_after_table_pagination', $wpcinvoice_query ); ?>
</div>