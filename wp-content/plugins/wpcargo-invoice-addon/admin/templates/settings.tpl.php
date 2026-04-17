<?php
global $wpcargo;
?>
<style>
    table#invoice_meta_tags, table#invoice_meta_tags tr td, table#invoice_meta_tags tr th {
        border: 1px solid #000;
        border-collapse: collapse;
        padding: 2px;
    }
    #wpcinvoice-print  table {
        border-spacing: 0;
        table-layout: fixed;
        border-collapse: collapse;    
    }
    #wpcinvoice-print th{
        width: auto;
    }
    .company-name {
        font-size: 42px;
    }
    .border {
        border: 1px solid #000;
    }
    .invoice-label {
        font-size: 42px;
        font-weight: 600;
    }
    .header {
        background-color: #000;
        padding: 8px 5px;
        color: #fff;
    }
    #wpcinvoice-print .no-padding {
        padding: 0;
    }
    #wpcinvoice-print table th, #wpcinvoice-print table td {
        padding: 15px 10px;
        vertical-align: top;
    }
</style>

<div class="postbox" id="wpcinvoice-print">
    <div class="inside" style="overflow: hidden;">
        <div id="tags-wrapper" class="one-fourth first" style="background-color: #dbf5e0; padding: 6px;">
            <h3><?php esc_html_e('WPCargo Merge Tags','wpcargo'); ?></h3>
            <p class="description"><?php esc_html_e('Note: Use this tags for email setup to display shipment data.', 'wpcargo-invoice'); ?></p>
            <table id="invoice_meta_tags">
                <tr>
                    <th><?php esc_html_e('Code', 'wpcargo'); ?></th>
                    <th><?php esc_html_e('Label', 'wpcargo'); ?></th>
                </tr>
                <?php
                    foreach ( $invoice_meta_tags as $key => $value ) {
                        ?>
                        <tr>
                            <td><?php echo $key; ?></td>
                            <td><?php echo stripslashes( $value ); ?></td>
                        </tr>
                        <?php
                    }
                ?>
            </table>
        </div>
        <!-- Invoice template starts here -->
        <div class="three-fourths">
            <form method="POST" action="options.php">
                <?php settings_fields( 'wpcinvoice_settings_group' ); ?>
                <?php do_settings_sections( 'wpcinvoice_settings_group' ); ?>
                <table class="form-table" id="wpcinvoice-print-table" cellspacing="0" cellpadding="0">
                    <tr>
                        <td class="no-padding">
                            <table style="width: 100%">
                                <tr>
                                    <td width="50%" class="border">
                                        <table style="width: 100%">
                                            <tr>
                                                <td width="20%"><img style="width: 100%" src="<?php echo $company_logo; ?>"></td>
                                                <td><span class="company-name"><?php echo $company_name; ?></span></td>
                                            </tr>
                                            <tr>
                                                <td colspan="2">
                                                    <textarea rows="6" style="width: 100%;" rows="6" name="wpcinvoice_settings[company_address]" placeholder="<?php echo wpcinvoice_default_company_addresss(); ?>"><?php echo wpcinvoice_display_options( $invoice_options, 'company_address' ); ?></textarea>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td width="50%" class="border" align="right">                                        
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="no-padding">
                            <table style="width: 100%;">
                                <tr>
                                    <td class="border">                                    
                                        <div>
                                            <h3 class="header"><?php echo apply_filters( 'wpcinvoice_shipper_header', __('Shipper Information', 'wpcargo-invoice' ) ); ?></h3>
                                            <textarea rows="6" style="width: 100%;" rows="14" name="wpcinvoice_settings[shipper_info]" placeholder="<?php echo wpcinvoice_default_shipper_invoice(); ?>"><?php echo wpcinvoice_display_options( $invoice_options, 'shipper_info' ); ?></textarea>

                                        </div>
                                       
                                    </td>
                                    <td class="border">
                                        <div>
                                            <h3 class="header"><?php echo apply_filters( 'wpcinvoice_receiver_header', __('Receiver Information', 'wpcargo-invoice' ) ); ?></h3>
                                            <textarea rows="6" style="width: 100%;" rows="14" name="wpcinvoice_settings[receiver_info]" placeholder="<?php echo wpcinvoice_default_receiver_invoice(); ?>"><?php echo wpcinvoice_display_options( $invoice_options, 'receiver_info' ); ?></textarea>
                                        </div>                        
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <?php if( !empty( wpcinvoice_package_fields() ) ): ?>
                    <tr>
                        <td class="no-padding">
                            <table style="width: 100%;">
                                <tr>
                                    <td class="border">
                                        <h3 class="header"><?php echo apply_filters( 'wpcinvoice_package_header', __('PACKAGE DETAILS', 'wpcargo-invoice' ) ); ?></h3>
                                        <table class="table wpcargo-table" style="width:100%;">
                                            <tr>
                                                <?php foreach ( wpcinvoice_package_fields() as $key => $value): ?>
                                                    <?php 
                                                    if( in_array( $key, wpcargo_package_dim_meta() ) && !wpcargo_package_settings()->dim_unit_enable ){
                                                        continue;
                                                    }
                                                    ?>
                                                    <th align="left" class="border"><?php echo $value['label']; ?></th>
                                                <?php endforeach; ?>
                                            </tr>
                                            <tr>
                                                <?php foreach( wpcinvoice_package_fields() as $key => $value ): ?>
                                                    <td class="border"></td>
                                                <?php endforeach; ?>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td class="no-padding">
                            <table style="width: 100%">
                                <tr>
                                    <td align="right" style="vertical-align: top;" class="border">
                                        <p><strong><?php echo apply_filters( 'wpcinvoice_subtotal_label', __('Subtotal: 0.00', 'wpcargo-invoice' ) ); ?></strong></p>
                                        <p><strong><?php echo apply_filters( 'wpcinvoice_tax_label', __('Tax: 0.00', 'wpcargo-invoice' ) ); ?></strong></p>
                                        <p><strong><?php echo apply_filters( 'wpcinvoice_total_label', __('Total: 0.00', 'wpcargo-invoice' ) ); ?></strong></p>
                                        <p>
                                            <textarea rows="6" style="width: 100%; text-align: right;" rows="6" name="wpcinvoice_settings[thankyou_message]" placeholder="<?php echo wpcinvoice_default_thankyou_invoice(); ?>"><?php echo wpcinvoice_display_options( $invoice_options, 'thankyou_message' ); ?></textarea>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="border">
                                        <div>
                                            <h3 class="header"><?php echo apply_filters( 'wpcinvoice_comment_header', __('Comments', 'wpcargo-invoice' ) ); ?></h3>
                                            <textarea style="width: 100%" rows="6" name="wpcinvoice_settings[comment]" placeholder="<?php echo wpcinvoice_default_comment_invoice(); ?>"><?php echo wpcinvoice_display_options( $invoice_options, 'comment' ); ?></textarea>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                <input class="primary button-primary" type="submit" name="submit" value="<?php esc_html_e('Save Settings', 'wpcargo-invoice' ); ?>" />
            </form>
        </div>
        
    </div>
</div>