<?php include( WPC_INVOICE_PATH.'includes/invoice-print-css.php' ); ?>
<div id="wpcinvoice-print">
    <table cellspacing="0" cellpadding="0" style="width: 100%">
        <?php do_action( 'wpcinvoice_before_company_info', $shipmentDetails ); ?>
        <tr>
            <td class="no-padding section">
                <table style="width: 100%">
                    <tr>
                        <?php do_action('wpcinvoice_comapany_info', $shipmentDetails ); ?>
                        <?php do_action('wpcinvoice_invoicing_info', $shipmentDetails ); ?>
                    </tr>
                </table>
            </td>
        </tr>
        <?php do_action( 'wpcinvoice_before_shipper_info', $shipmentDetails ); ?>
        <tr>
            <td class="no-padding section">
                <table style="width: 100%; padding-top:10px;">
                    <tr>  
                        <?php do_action( 'wpcinvoice_assigned_shipper', $shipmentDetails ); ?>
                        <?php do_action( 'wpcinvoice_invoice_details', $shipmentDetails ); ?>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td class="no-padding section">
                <table style="width: 100%; padding-top:10px;">
                    <tr>  
                        <?php do_action( 'wpcinvoice_shipper_info', $shipmentDetails ); ?>
                        <?php do_action( 'wpcinvoice_receiver_info', $shipmentDetails ); ?>
                    </tr>
                </table>
            </td>
        </tr>
        <?php do_action( 'wpcinvoice_after_shipper_info', $shipmentDetails ); ?>
        <tr>
            <td class="no-padding section">
                <?php do_action('wpcinvoice_before_package_info', $shipmentDetails ); ?>
                <?php do_action('wpcinvoice_package_info', $shipmentDetails ); ?>
                <?php do_action('wpcinvoice_after_package_info', $shipmentDetails ); ?>
            </td>
        </tr>
        <?php do_action( 'wpcinvoice_before_comments_info', $shipmentDetails ); ?>
        <tr>
            <td class="no-padding section" align = "left">
                <h3 class="section-header-1"><?php echo apply_filters( 'wpcinvoice_charges_section', __('INVOICE CHARGES', 'wpcargo-invoice' ) ); ?></h3>
                <table style="width: 100%; padding-top:10px; border:1px solid #cecece;">
                    <?php do_action( 'wpcinvoice_total_info', $shipmentDetails ); ?>
                </table>
            </td>
        </tr>
        <tr>
            <?php do_action( 'wpcinv_invoice_footer', $shipmentDetails ); ?>
        </tr>
        <tr>
            <td>
                <table style="width: 100%">
                    <tr>
                        <?php do_action( 'wpcinvoice_comments_info', $shipmentDetails ); ?>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>